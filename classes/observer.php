<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_xpstore;

/**
 * Event observer for local_xpstore.
 *
 * @package    local_xpstore
 * @copyright  2026 EduPlugins Studio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Checks whether the user performing a course restore is authorized to copy
     * local_xpstore configuration and custom images from the source course on this site.
     *
     * @param \core\event\course_restored $event
     * @param int $oldcourseid
     * @return bool
     */
    protected static function can_copy_from_source_course(\core\event\course_restored $event, int $oldcourseid): bool {
        global $DB;

        if ($oldcourseid <= 0 || $oldcourseid === (int)$event->objectid) {
            return false;
        }

        // If Moodle explicitly marks the backup as coming from a different site, never read local course data.
        if (isset($event->other['samesite']) && empty($event->other['samesite'])) {
            return false;
        }

        if (!$DB->record_exists('course', ['id' => $oldcourseid])) {
            return false;
        }

        $oldcontext = \context_course::instance($oldcourseid, IGNORE_MISSING);
        if (!$oldcontext) {
            return false;
        }

        $actorid = (int)$event->userid;
        // Automated CLI/cron course copy tasks run with userid 0 or admin.
        if ($actorid <= 0 || is_siteadmin($actorid)) {
            return true;
        }

        return has_capability('local/xpstore:manage', $oldcontext, $actorid)
            || has_capability('moodle/course:update', $oldcontext, $actorid)
            || has_capability('moodle/backup:backupcourse', $oldcontext, $actorid);
    }

    /**
     * Builds a 1-to-1 mapping of course module IDs from $oldcourseid to $newcourseid
     * so duplicate activity names do not collapse onto the first matching module.
     *
     * @param \course_modinfo $oldmodinfo
     * @param \course_modinfo $newmodinfo
     * @return array Map of oldcmid => newcmid
     */
    protected static function build_cm_map(\course_modinfo $oldmodinfo, \course_modinfo $newmodinfo): array {
        $map = [];
        $usednewids = [];
        $newcms = $newmodinfo->get_cms();

        // Pass 1: Match by (modname, sectionnum, name) in order.
        foreach ($oldmodinfo->get_cms() as $oldcm) {
            foreach ($newcms as $newcm) {
                if (isset($usednewids[$newcm->id])) {
                    continue;
                }
                if (
                    $newcm->modname === $oldcm->modname &&
                    (int)$newcm->sectionnum === (int)$oldcm->sectionnum &&
                    $newcm->name === $oldcm->name
                ) {
                    $map[(int)$oldcm->id] = (int)$newcm->id;
                    $usednewids[$newcm->id] = true;
                    break;
                }
            }
        }

        // Pass 2: Match remaining modules by (modname, name) in order.
        foreach ($oldmodinfo->get_cms() as $oldcm) {
            if (isset($map[(int)$oldcm->id])) {
                continue;
            }
            foreach ($newcms as $newcm) {
                if (isset($usednewids[$newcm->id])) {
                    continue;
                }
                if ($newcm->modname === $oldcm->modname && $newcm->name === $oldcm->name) {
                    $map[(int)$oldcm->id] = (int)$newcm->id;
                    $usednewids[$newcm->id] = true;
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * Intercepts course restores/clones to update hardcoded widget IDs in HTML labels and pages.
     *
     * @param \core\event\course_restored $event The course restored event.
     */
    public static function course_restored(\core\event\course_restored $event) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/local/xpstore/lib.php');
        require_once($CFG->dirroot . '/group/lib.php');

        $newcourseid = (int)$event->objectid;
        if ($newcourseid <= 0 || !$DB->record_exists('course', ['id' => $newcourseid])) {
            return;
        }

        // The regex looks for: /local/xpstore/widget*.php?id=OLD_ID
        // and replaces OLD_ID with $newcourseid.
        $pattern = '/(\/local\/xpstore\/[^"\'?]+\.php\?id=)(\d+)/';
        $replacement = '${1}' . $newcourseid;

        $detectedoldcourseid = null;

        // Get all unique module names present in the new course.
        $modinfo = get_fast_modinfo($newcourseid);
        $modnames = array_unique(array_map(function ($cm) {
            return $cm->modname;
        }, $modinfo->get_cms()));

        // Fix all activities and detect embedded old course id.
        foreach ($modnames as $modname) {
            if ($DB->get_manager()->table_exists($modname)) {
                $records = $DB->get_records($modname, ['course' => $newcourseid]);
                foreach ($records as $record) {
                    $updated = false;

                    if (isset($record->intro) && strpos($record->intro, '/local/xpstore/') !== false) {
                        if (!$detectedoldcourseid && preg_match($pattern, $record->intro, $matches)) {
                            $detectedoldcourseid = (int)$matches[2];
                        }
                        $newintro = preg_replace($pattern, $replacement, $record->intro);
                        if ($newintro !== $record->intro) {
                            $record->intro = $newintro;
                            $updated = true;
                        }
                    }

                    if (isset($record->content) && strpos($record->content, '/local/xpstore/') !== false) {
                        if (!$detectedoldcourseid && preg_match($pattern, $record->content, $matches)) {
                            $detectedoldcourseid = (int)$matches[2];
                        }
                        $newcontent = preg_replace($pattern, $replacement, $record->content);
                        if ($newcontent !== $record->content) {
                            $record->content = $newcontent;
                            $updated = true;
                        }
                    }

                    if ($updated) {
                        $DB->update_record($modname, $record);
                    }
                }
            }
        }

        // Also fix widget URLs inside course section summaries ({course_sections}.summary).
        $sections = $DB->get_records('course_sections', ['course' => $newcourseid]);
        foreach ($sections as $section) {
            if (!empty($section->summary) && strpos($section->summary, '/local/xpstore/') !== false) {
                if (!$detectedoldcourseid && preg_match($pattern, $section->summary, $matches)) {
                    $detectedoldcourseid = (int)$matches[2];
                }
                $newsummary = preg_replace($pattern, $replacement, $section->summary);
                if ($newsummary !== $section->summary) {
                    $section->summary = $newsummary;
                    $DB->update_record('course_sections', $section);
                }
            }
        }

        // Prefer Moodle's authoritative originalcourseid when available, falling back to detected widget URL ID.
        $oldcourseid = !empty($event->other['originalcourseid'])
            ? (int)$event->other['originalcourseid']
            : (int)$detectedoldcourseid;

        // Copy store configuration and map CMIDs only if authorized on the source course.
        if ($oldcourseid > 0 && self::can_copy_from_source_course($event, $oldcourseid)) {
            // 1. Copy simple string configs (colors, icons, menu visibility).
            $colorkeys = [
                'color_primary' => '#0056D2',
                'color_secondary' => '#00C9A7',
                'color_icon' => '#0056D2',
                'color_cat_icon' => '#0056D2',
            ];
            foreach ($colorkeys as $key => $defaulthex) {
                $oldval = get_config('local_xpstore', $key . '_course_' . $oldcourseid);
                if ($oldval !== false) {
                    $cleanval = local_xpstore_clean_hex_color((string)$oldval, $defaulthex);
                    set_config($key . '_course_' . $newcourseid, $cleanval, 'local_xpstore');
                }
            }

            foreach (['cat_icons', 'show_menu'] as $key) {
                $oldval = get_config('local_xpstore', $key . '_course_' . $oldcourseid);
                if ($oldval !== false) {
                    set_config($key . '_course_' . $newcourseid, $oldval, 'local_xpstore');
                }
            }

            // 2. Copy and map the catalog entries (preserving all 12 fields including bonusmode).
            $oldentries = local_xpstore_parse_catalog($oldcourseid, false);
            if (!empty($oldentries)) {
                $oldmodinfo = get_fast_modinfo($oldcourseid);
                $newmodinfo = get_fast_modinfo($newcourseid);
                $cmidmap = self::build_cm_map($oldmodinfo, $newmodinfo);

                $newmanualitems = $DB->get_records(
                    'grade_items',
                    ['courseid' => $newcourseid, 'itemtype' => 'manual'],
                    'sortorder ASC, id ASC'
                );
                $usedmanualids = [];

                $newcatalogparts = [];
                $seenproductkeys = [];

                foreach ($oldentries as $entry) {
                    $tipochar = $entry['tipo'];
                    $oldcmid = (int)$entry['cmid'];
                    $newcmid = null;

                    if ($tipochar === 'M') {
                        $oldgi = $DB->get_record('grade_items', ['id' => $oldcmid, 'itemtype' => 'manual']);
                        if ($oldgi) {
                            foreach ($newmanualitems as $candgi) {
                                if (isset($usedmanualids[$candgi->id])) {
                                    continue;
                                }
                                if ((string)$candgi->itemname === (string)$oldgi->itemname) {
                                    $newcmid = (int)$candgi->id;
                                    $usedmanualids[$candgi->id] = true;
                                    break;
                                }
                            }
                        }
                    } else if ($tipochar === 'C') {
                        $newitemid = $DB->get_field('grade_items', 'id', [
                            'courseid' => $newcourseid,
                            'itemtype' => 'course',
                        ]);
                        if ($newitemid) {
                            $newcmid = (int)$newitemid;
                        }
                    } else {
                        if (isset($cmidmap[$oldcmid])) {
                            $newcmid = $cmidmap[$oldcmid];
                        }
                    }

                    if ($newcmid) {
                        $productkey = $tipochar . $newcmid;
                        if (isset($seenproductkeys[$productkey])) {
                            continue;
                        }
                        $seenproductkeys[$productkey] = true;

                        $entry['cmid'] = $newcmid;

                        // Map prerequisite CMID using the 1-to-1 CM map.
                        if (!empty($entry['requisito'])) {
                            $oldreqid = (int)$entry['requisito'];
                            $entry['requisito'] = $cmidmap[$oldreqid] ?? 0;
                        }

                        // Copy custom card image file to the new course context if present.
                        if (!empty($entry['icono']) && strpos($entry['icono'], 'img_') === 0) {
                            $imgitemid = (int)substr($entry['icono'], 4);
                            if ($imgitemid > 0) {
                                $oldcontext = \context_course::instance($oldcourseid, IGNORE_MISSING);
                                $newcontext = \context_course::instance($newcourseid, IGNORE_MISSING);
                                if ($oldcontext && $newcontext) {
                                    $fs = get_file_storage();
                                    $oldfiles = $fs->get_area_files(
                                        $oldcontext->id,
                                        'local_xpstore',
                                        'cardimage',
                                        $imgitemid,
                                        'id DESC',
                                        false
                                    );
                                    foreach ($oldfiles as $oldfile) {
                                        if (
                                            !$fs->file_exists(
                                                $newcontext->id,
                                                'local_xpstore',
                                                'cardimage',
                                                $imgitemid,
                                                $oldfile->get_filepath(),
                                                $oldfile->get_filename()
                                            )
                                        ) {
                                            $filerecord = [
                                                'contextid' => $newcontext->id,
                                                'component' => 'local_xpstore',
                                                'filearea'  => 'cardimage',
                                                'itemid'    => $imgitemid,
                                                'filepath'  => $oldfile->get_filepath(),
                                                'filename'  => $oldfile->get_filename(),
                                            ];
                                            $fs->create_file_from_storedfile($filerecord, $oldfile);
                                        }
                                    }
                                }
                            }
                        }

                        // Ensure VIP Group and group restriction point to the new course's group ID.
                        if ($tipochar === 'S') {
                            $groupname = ($entry['nombre'] !== '')
                                ? $entry['nombre']
                                : get_string('specialcontent', 'local_xpstore');
                            $oldgroupid = (int)groups_get_group_by_name($oldcourseid, $groupname);
                            $newgroupid = groups_get_group_by_name($newcourseid, $groupname);
                            if (!$newgroupid) {
                                $gdata = new \stdClass();
                                $gdata->courseid = $newcourseid;
                                $gdata->name = $groupname;
                                $newgroupid = groups_create_group($gdata);
                            }
                            if ($newgroupid) {
                                local_xpstore_apply_special_restriction(
                                    $newcmid,
                                    (int)$newgroupid,
                                    $newcourseid,
                                    $oldgroupid > 0 ? $oldgroupid : 0
                                );
                            }
                        }

                        $newcatalogparts[] = local_xpstore_serialize_catalog_item($entry);

                        // Replace cmid=OLD in all activities so individual widgets keep working.
                        $qtipo = preg_quote($tipochar, '/');
                        $qold = preg_quote((string)$oldcmid, '/');
                        $cmidpattern = '/([?&]|&amp;)(tipo=' . $qtipo . '([&]|&amp;)cmid=' . $qold .
                                '|cmid=' . $qold . '([&]|&amp;)tipo=' . $qtipo . ')([&"\']|&amp;)/';
                        $cmidreplacement = function ($matches) use ($tipochar, $newcmid) {
                            $amp = isset($matches[3]) && $matches[3] ? $matches[3] : (isset($matches[4]) ? $matches[4] : '&');
                            return $matches[1] . 'tipo=' . $tipochar . $amp . 'cmid=' . $newcmid . $matches[5];
                        };

                        // Fix 'Unlock' availability restrictions that contain U{oldcmid}.
                        if ($tipochar === 'U') {
                            $oldproductid = 'U' . $oldcmid;
                            $newproductid = 'U' . $newcmid;
                            $cmrecords = $DB->get_records('course_modules', ['course' => $newcourseid]);
                            foreach ($cmrecords as $cmrec) {
                                if (
                                    !empty($cmrec->availability) &&
                                    strpos($cmrec->availability, '"' . $oldproductid . '"') !== false
                                ) {
                                    $newavail = str_replace(
                                        '"' . $oldproductid . '"',
                                        '"' . $newproductid . '"',
                                        $cmrec->availability
                                    );
                                    if ($newavail !== $cmrec->availability) {
                                        $cmrec->availability = $newavail;
                                        $DB->update_record('course_modules', $cmrec);
                                    }
                                }
                            }
                        }

                        foreach ($modnames as $modname) {
                            if ($DB->get_manager()->table_exists($modname)) {
                                $records = $DB->get_records($modname, ['course' => $newcourseid]);
                                foreach ($records as $record) {
                                    $updated = false;

                                    if (isset($record->intro) && strpos($record->intro, 'cmid=' . $oldcmid) !== false) {
                                        $newintro = preg_replace_callback($cmidpattern, $cmidreplacement, $record->intro);
                                        if ($newintro !== $record->intro) {
                                            $record->intro = $newintro;
                                            $updated = true;
                                        }
                                    }

                                    if (isset($record->content) && strpos($record->content, 'cmid=' . $oldcmid) !== false) {
                                        $newcontent = preg_replace_callback(
                                            $cmidpattern,
                                            $cmidreplacement,
                                            $record->content
                                        );
                                        if ($newcontent !== $record->content) {
                                            $record->content = $newcontent;
                                            $updated = true;
                                        }
                                    }

                                    if ($updated) {
                                        $DB->update_record($modname, $record);
                                    }
                                }
                            }
                        }

                        $sections = $DB->get_records('course_sections', ['course' => $newcourseid]);
                        foreach ($sections as $section) {
                            if (!empty($section->summary) && strpos($section->summary, 'cmid=' . $oldcmid) !== false) {
                                $newsummary = preg_replace_callback($cmidpattern, $cmidreplacement, $section->summary);
                                if ($newsummary !== $section->summary) {
                                    $section->summary = $newsummary;
                                    $DB->update_record('course_sections', $section);
                                }
                            }
                        }
                    }
                }

                if (!empty($newcatalogparts)) {
                    $newcatalog = implode(',', $newcatalogparts);
                    set_config('catalog_course_' . $newcourseid, $newcatalog, 'local_xpstore');
                }
            }
        }

        rebuild_course_cache($newcourseid, true);
    }

    /**
     * Re-applies accumulated item and course final grade bonus points whenever a user's grades
     * are updated or recalculated so teacher re-grading and automatic course aggregation stay accurate.
     *
     * @param \core\event\user_graded $event The user graded event.
     */
    public static function user_graded(\core\event\user_graded $event) {
        global $DB, $CFG;

        static $processingitems = [];

        $courseid = (int)$event->courseid;
        $userid = (int)$event->relateduserid;
        $gradeid = (int)$event->objectid;
        if ($courseid <= 0 || $userid <= 0 || $gradeid <= 0) {
            return;
        }

        require_once($CFG->dirroot . '/local/xpstore/lib.php');

        // Skip events emitted while local_xpstore_regrade_course_with_bonus is recalculating pure course totals.
        if (local_xpstore_course_regrade_guard($courseid, $userid)) {
            return;
        }

        $sql = "SELECT gi.*
                  FROM {grade_grades} gg
                  JOIN {grade_items} gi ON gi.id = gg.itemid
                 WHERE gg.id = ?";
        $gradeitem = $DB->get_record_sql($sql, [$gradeid]);
        if (!$gradeitem || (int)$gradeitem->courseid !== $courseid) {
            return;
        }

        if ($gradeitem->itemtype === 'course') {
            $hascoursebonus = $DB->record_exists_select(
                'local_xpstore_gastos',
                "userid = ? AND itemtype = 'C' AND itemid = ?",
                [$userid, (int)$gradeitem->id]
            ) || $DB->record_exists('local_xpstore_grade_state', [
                'itemid' => (int)$gradeitem->id,
                'userid' => $userid,
            ]);
            if ($hascoursebonus) {
                local_xpstore_sync_course_grade_bonus($courseid, $userid, true, false);
            }
            return;
        }

        if ($gradeitem->itemtype === 'mod' || $gradeitem->itemtype === 'manual') {
            $guardkey = $courseid . '_' . $userid . '_' . (int)$gradeitem->id;
            if (!empty($processingitems[$guardkey])) {
                return;
            }
            $processingitems[$guardkey] = true;

            try {
                $exptype = ($gradeitem->itemtype === 'manual') ? 'M' : 'G';
                $hasitembonus = $DB->record_exists_select(
                    'local_xpstore_gastos',
                    "userid = ? AND itemtype = ? AND (courseid = ? OR courseid = 0)",
                    [$userid, $exptype, $courseid]
                ) || $DB->record_exists('local_xpstore_grade_state', [
                    'itemid' => (int)$gradeitem->id,
                    'userid' => $userid,
                ]);
                if ($hasitembonus) {
                    local_xpstore_sync_item_grade_bonus($courseid, $gradeitem, $userid, true);
                }

                // If the student also holds an active Course Total ('C') bonus in this course,
                // force a clean course aggregation from basegrade and re-apply the course bonus.
                $courseitem = $DB->get_record('grade_items', [
                    'courseid' => $courseid,
                    'itemtype' => 'course',
                ]);
                if ($courseitem) {
                    $hascoursebonus = $DB->record_exists_select(
                        'local_xpstore_gastos',
                        "userid = ? AND itemtype = 'C' AND itemid = ?",
                        [$userid, (int)$courseitem->id]
                    ) || $DB->record_exists('local_xpstore_grade_state', [
                        'itemid' => (int)$courseitem->id,
                        'userid' => $userid,
                    ]);
                    if ($hascoursebonus) {
                        local_xpstore_regrade_course_with_bonus($courseid, $userid);
                    }
                }
            } finally {
                unset($processingitems[$guardkey]);
            }
        }
    }
}
