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

/**
 * XP Store (local_xpstore)
 *
 * @package     local_xpstore
 * @copyright   2026 EduPlugins Studio
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Extend course navigation with XP Store link.
 *
 * @param navigation_node $parentnode The parent node.
 * @param stdClass $course The course object.
 * @param context_course $context The course context.
 * @return void
 */
function local_xpstore_extend_navigation_course(navigation_node $parentnode, $course, $context) {
    global $DB;

    // Clean up any legacy 'xpstore' aggregationstatus values in grade_grades.
    if ($DB->record_exists('grade_grades', ['aggregationstatus' => 'xpstore'])) {
        $DB->set_field('grade_grades', 'aggregationstatus', 'unknown', ['aggregationstatus' => 'xpstore']);
    }

    // Ensure no grade_items in this course are stuck with needsupdate = 1 (which causes gradesneedregrading in Single View).
    if ($DB->record_exists('grade_items', ['courseid' => $course->id, 'needsupdate' => 1])) {
        $DB->set_field('grade_items', 'needsupdate', 0, ['courseid' => $course->id, 'needsupdate' => 1]);
    }

    // 1. Detect if Level Up! is in the course.
    $hasxp = $DB->record_exists('block_instances', [
        'blockname' => 'xp',
        'parentcontextid' => $context->id,
    ]);

    // If Level Up! block is not active in this course, do not show the menu to anyone.
    if (!$hasxp) {
        return;
    }

    // 2. Check the specific visibility configuration for THIS COURSE.
    $showinmenu = get_config('local_xpstore', 'show_menu_course_' . $course->id);

    // If it's not set (false), default it to '0' (hidden).
    if ($showinmenu === false) {
        $showinmenu = '0';
    }

    // MAGIC HERE: If the menu is hidden ('0') AND the user is NOT a teacher (cannot update course), hide it.
    if ($showinmenu === '0' && !has_capability('moodle/course:update', $context)) {
        return; // Exit, hiding the menu only for students.
    }

    // 3. If it is a student (and it's visible) or a teacher (always visible), draw the store menu.
    $url = new moodle_url('/local/xpstore/index.php', ['id' => $course->id]);
    $shopnode = navigation_node::create(
        get_string('tiendaxp', 'local_xpstore'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'tiendaxp',
        new pix_icon('i/store', '')
    );
    $parentnode->add_node($shopnode);
}

/**
 * Gets the available balance of a user in a course.
 *
 * @param int $userid The user ID.
 * @param int $courseid The course ID.
 * @return int Available balance.
 */
function local_xpstore_get_balance($userid, $courseid) {
    global $DB;
    $xp = $DB->get_field_sql(
        "SELECT SUM(xp) FROM {block_xp_log} WHERE userid = ? AND courseid = ?",
        [$userid, $courseid]
    );
    if (!$xp) {
        $xp = $DB->get_field_sql(
            "SELECT xp FROM {block_xp} WHERE userid = ? AND courseid = ?",
            [$userid, $courseid]
        );
    }
    $totalxp = (int)($xp ?: 0);

    $sqlg = "SELECT SUM(g.amount)
             FROM {local_xpstore_gastos} g
             LEFT JOIN {course_modules} cm ON g.itemid = cm.id AND g.itemtype NOT IN ('M', 'C')
             LEFT JOIN {grade_items} gi ON g.itemid = gi.id AND g.itemtype IN ('M', 'C')
             WHERE g.userid = ? AND (cm.course = ? OR gi.courseid = ?)";
    $expenses = $DB->get_field_sql($sqlg, [$userid, $courseid, $courseid]) ?: 0;

    return max(0, $totalxp - (int)$expenses);
}

/**
 * Gets the display name for a course total grade item.
 *
 * @param int $itemid The grade_items ID (optional).
 * @return string The course total label.
 */
function local_xpstore_get_course_grade_item_name($itemid = 0) {
    global $DB;
    $customname = '';
    if ($itemid > 0) {
        $customname = (string)$DB->get_field('grade_items', 'itemname', ['id' => $itemid]);
    }
    return trim($customname) !== '' ? $customname : get_string('coursetotal', 'grades');
}

/**
 * Calculates the total accumulated XP Store bonus points for the final course grade of a user.
 *
 * @param int $courseid The course ID.
 * @param int $userid The user ID.
 * @return float Total bonus points to add to the automatic course average.
 */
function local_xpstore_get_user_course_bonus($courseid, $userid) {
    global $DB;

    $sql = "SELECT g.id, g.itemid
            FROM {local_xpstore_gastos} g
            JOIN {grade_items} gi ON g.itemid = gi.id AND g.itemtype = 'C'
            WHERE g.userid = ? AND gi.courseid = ?";
    $purchases = $DB->get_records_sql($sql, [$userid, $courseid]);

    $bonuskey = "course_bonus_{$courseid}_{$userid}";
    if (empty($purchases)) {
        if (get_config('local_xpstore', $bonuskey) !== false) {
            unset_config($bonuskey, 'local_xpstore');
        }
        return 0.0;
    }

    // Check current catalog for 'C' item values.
    $configraw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
    $itemsconfig = array_filter(explode(',', $configraw));
    $catalogvalues = [];
    foreach ($itemsconfig as $item) {
        $parts = explode(':', trim($item));
        if (isset($parts[0]) && strpos($parts[0], 'C') === 0) {
            $cid = (int)substr($parts[0], 1);
            $catalogvalues[$cid] = isset($parts[3]) ? (float)$parts[3] : 0.5;
        }
    }

    $calculated = 0.0;
    $allmatched = true;
    foreach ($purchases as $p) {
        if (isset($catalogvalues[(int)$p->itemid])) {
            $calculated += $catalogvalues[(int)$p->itemid];
        } else {
            $allmatched = false;
        }
    }

    $saved = get_config('local_xpstore', $bonuskey);
    if ($allmatched && $calculated > 0) {
        if ($saved === false || abs((float)$saved - $calculated) > 0.0001) {
            set_config($bonuskey, (string)$calculated, 'local_xpstore');
        }
        return $calculated;
    }

    if ($saved !== false && (float)$saved > 0) {
        return (float)$saved;
    }

    return $calculated > 0 ? $calculated : (count($purchases) * 0.5);
}

/**
 * Merges or removes the XP Store bonus note inside a grade_grades feedback string
 * while preserving any existing teacher feedback.
 *
 * @param string|null $existingfeedback Current feedback text from grade_grades.
 * @param float $totalbonus Total bonus points to display (<= 0 removes the XP Store note).
 * @return string Updated feedback HTML.
 */
function local_xpstore_merge_grade_feedback($existingfeedback, $totalbonus) {
    $current = (string)($existingfeedback ?? '');

    // Remove existing XP Store marker block if present.
    $cleaned = preg_replace('/\s*<!--xpstore-fb-->.*?<!--\/xpstore-fb-->/s', '', $current);
    // Also clean plain legacy one-liner if it matches our exact pattern without markers.
    $cleaned = preg_replace('/^\+\d+(?:\.\d+)?\s+[^\r\n]+XP Store\)?$/iu', '', trim($cleaned));

    if ($totalbonus <= 0) {
        return trim($cleaned);
    }

    $sm = get_string_manager();
    $bonusformatted = rtrim(rtrim(number_format((float)$totalbonus, 2, '.', ''), '0'), '.');
    $bonustext = $sm->string_exists('coursebonus_feedback', 'local_xpstore')
        ? get_string('coursebonus_feedback', 'local_xpstore', $bonusformatted)
        : ('+' . $bonusformatted . ' ' . strtolower(get_string('points', 'local_xpstore')) . ' (XP Store)');

    $wrapped = '<!--xpstore-fb--><div class="xpstore-grade-feedback">' . $bonustext . '</div><!--/xpstore-fb-->';

    if (trim($cleaned) === '') {
        return $wrapped;
    }

    return rtrim($cleaned) . "\n" . $wrapped;
}

/**
 * Recalculates the automatic course total grade for a user and applies any accumulated
 * XP Store course bonus points without marking the course grade as overridden.
 *
 * @param int $courseid The course ID.
 * @param int $userid The user ID.
 * @param bool $fromobserver True when called from user_graded observer right after Moodle's aggregation.
 * @return void
 */
function local_xpstore_sync_course_grade_bonus($courseid, $userid, $fromobserver = false) {
    global $DB, $CFG;
    static $syncing = [];

    $lockkey = (int)$courseid . '_' . (int)$userid;
    if (!empty($syncing[$lockkey])) {
        return;
    }
    $syncing[$lockkey] = true;

    try {
        require_once($CFG->libdir . '/gradelib.php');

        $courseitem = \grade_item::fetch_course_item($courseid);
        if (!$courseitem) {
            $syncing[$lockkey] = false;
            return;
        }

        $totalbonus = local_xpstore_get_user_course_bonus($courseid, $userid);
        $grade = $DB->get_record('grade_grades', [
            'itemid' => $courseitem->id,
            'userid' => $userid,
        ]);

        $grademax = (float)$courseitem->grademax;
        $grademin = (float)$courseitem->grademin;
        $basekey = "course_base_{$courseid}_{$userid}";
        $savedbase = get_config('local_xpstore', $basekey);

        if ($totalbonus > 0) {
            if ($grade) {
                if ($fromobserver || $savedbase === false) {
                    // Moodle just calculated finalgrade as the pure automatic average (or first bonus redemption).
                    $purebase = ($grade->finalgrade !== null) ? (float)$grade->finalgrade : null;
                    set_config($basekey, $purebase !== null ? (string)$purebase : 'null', 'local_xpstore');
                } else {
                    $purebase = ($savedbase === 'null' || $savedbase === '') ? null : (float)$savedbase;
                }

                $baseval = ($purebase !== null) ? $purebase : $grademin;
                $newvalue = min($grademax, $baseval + $totalbonus);

                $grade->finalgrade = $newvalue;
                $grade->overridden = 0;
                $grade->aggregationstatus = 'unknown';
                $grade->feedback = local_xpstore_merge_grade_feedback($grade->feedback ?? '', $totalbonus);
                $grade->feedbackformat = FORMAT_HTML;
                $grade->timemodified = time();
                $DB->update_record('grade_grades', $grade);
            } else {
                set_config($basekey, 'null', 'local_xpstore');
                $newvalue = min($grademax, $grademin + $totalbonus);
                $newgrade = (object)[
                    'itemid' => $courseitem->id,
                    'userid' => $userid,
                    'rawgrade' => null,
                    'rawgrademax' => $grademax,
                    'rawgrademin' => $grademin,
                    'finalgrade' => $newvalue,
                    'overridden' => 0,
                    'aggregationstatus' => 'unknown',
                    'feedback' => local_xpstore_merge_grade_feedback('', $totalbonus),
                    'feedbackformat' => FORMAT_HTML,
                    'timecreated' => time(),
                    'timemodified' => time(),
                ];
                $DB->insert_record('grade_grades', $newgrade);
            }
        } else {
            if ($grade && $savedbase !== false) {
                $purebase = ($savedbase === 'null' || $savedbase === '') ? null : (float)$savedbase;
                $grade->finalgrade = $purebase;
                $grade->overridden = 0;
                $grade->aggregationstatus = 'unknown';
                $grade->feedback = local_xpstore_merge_grade_feedback($grade->feedback ?? '', 0);
                $grade->timemodified = time();
                $DB->update_record('grade_grades', $grade);
            } else if ($grade && $grade->aggregationstatus === 'xpstore') {
                $grade->aggregationstatus = 'unknown';
                $DB->update_record('grade_grades', $grade);
            }
            if ($savedbase !== false) {
                unset_config($basekey, 'local_xpstore');
            }
        }
    } catch (\Throwable $e) {
        debugging('local_xpstore_sync_course_grade_bonus error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

    $syncing[$lockkey] = false;
}

/**
 * Returns the active grade_items record(s) for a course module ('G').
 * Handles modules like Forum and Workshop that create multiple grade_items (itemnumber 0 and 1),
 * selecting the active one(s) where gradetype != GRADE_TYPE_NONE (0).
 *
 * @param int $courseid The course ID.
 * @param string $itemmodule The module name (e.g. 'assign', 'forum', 'quiz').
 * @param int $iteminstance The module instance ID.
 * @return array Array of stdClass grade_items records.
 */
function local_xpstore_get_mod_grade_items($courseid, $itemmodule, $iteminstance) {
    global $DB;

    $items = $DB->get_records('grade_items', [
        'itemtype' => 'mod',
        'itemmodule' => $itemmodule,
        'iteminstance' => $iteminstance,
        'courseid' => $courseid,
    ], 'itemnumber ASC');

    if (empty($items)) {
        return [];
    }

    $active = [];
    foreach ($items as $gi) {
        if ((int)$gi->gradetype !== 0) {
            $active[] = $gi;
        }
    }

    return !empty($active) ? $active : [reset($items)];
}

/**
 * Calculates the total accumulated XP Store bonus points for a specific activity ('G')
 * or manual grade item ('M') for a user.
 *
 * @param int $courseid The course ID.
 * @param stdClass $gradeitem The grade_items record.
 * @param int $userid The user ID.
 * @return float Total bonus points to add to the activity/manual item grade.
 */
function local_xpstore_get_user_item_bonus($courseid, $gradeitem, $userid) {
    global $DB;

    if ($gradeitem->itemtype === 'manual') {
        $type = 'M';
        $itemid = (int)$gradeitem->id;
    } else if ($gradeitem->itemtype === 'mod') {
        $type = 'G';
        $sql = "SELECT cm.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                 WHERE cm.course = ? AND m.name = ? AND cm.instance = ?
              ORDER BY cm.deletioninprogress ASC, cm.id DESC";
        $cmids = $DB->get_fieldset_sql($sql, [$courseid, $gradeitem->itemmodule, $gradeitem->iteminstance]);
        $itemid = !empty($cmids) ? (int)reset($cmids) : 0;
        if ($itemid <= 0) {
            return 0.0;
        }
    } else {
        return 0.0;
    }

    $count = (int)$DB->count_records('local_xpstore_gastos', [
        'userid' => $userid,
        'itemtype' => $type,
        'itemid' => $itemid,
    ]);
    if ($count <= 0) {
        return 0.0;
    }

    $configraw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
    $itemsconfig = array_filter(explode(',', $configraw));
    $unitvalue = 0.5;
    foreach ($itemsconfig as $item) {
        $parts = explode(':', trim($item));
        if (isset($parts[0]) && $parts[0] === $type . $itemid) {
            $unitvalue = isset($parts[3]) ? (float)$parts[3] : 0.5;
            break;
        }
    }

    return $count * $unitvalue;
}

/**
 * Queries the module's own grade table (assign_grades, quiz_grades, forum_grades) to get
 * the true un-bonused score awarded by the activity, or false if the module does not use
 * a standard single grade table.
 *
 * @param stdClass $gradeitem The grade_items record.
 * @param int $userid The user ID.
 * @param bool $known Whether the module's native table was authoritative (out parameter).
 * @return float|null The pure activity grade, or null if not yet graded in the activity.
 */
function local_xpstore_get_module_raw_grade($gradeitem, $userid, &$known = false) {
    global $DB;
    $known = false;

    if ($gradeitem->itemtype !== 'mod') {
        return null;
    }

    $mod = $gradeitem->itemmodule;
    $instance = (int)$gradeitem->iteminstance;

    if ($mod === 'assign') {
        $known = true;
        $val = $DB->get_field_select(
            'assign_grades',
            'grade',
            'assignment = ? AND userid = ? AND grade >= 0',
            [$instance, $userid],
            IGNORE_MULTIPLE
        );
        return ($val !== false && $val !== null) ? (float)$val : null;
    } else if ($mod === 'quiz') {
        $known = true;
        $val = $DB->get_field('quiz_grades', 'grade', ['quiz' => $instance, 'userid' => $userid], IGNORE_MULTIPLE);
        return ($val !== false && $val !== null) ? (float)$val : null;
    } else if ($mod === 'forum' && (int)$gradeitem->itemnumber === 0) {
        $known = true;
        $val = $DB->get_field_select(
            'forum_grades',
            'grade',
            'forum = ? AND userid = ? AND grade >= 0',
            [$instance, $userid],
            IGNORE_MULTIPLE
        );
        return ($val !== false && $val !== null) ? (float)$val : null;
    }

    return null;
}

/**
 * Synchronizes the XP Store bonus points and feedback annotation for a specific activity ('G')
 * or manual grade item ('M') without locking it as overridden, allowing teachers and activities
 * (Assignments, Quizzes, Forums, etc.) to grade normally at any time.
 *
 * @param int $courseid The course ID.
 * @param stdClass $gradeitem The grade_items record.
 * @param int $userid The user ID.
 * @param bool $fromobserver True when triggered from \core\event\user_graded.
 * @return void
 */
function local_xpstore_sync_item_grade_bonus($courseid, $gradeitem, $userid, $fromobserver = false) {
    global $DB, $CFG;
    static $syncingitem = [];

    if (!$gradeitem || empty($gradeitem->id)) {
        return;
    }

    $lockkey = (int)$gradeitem->id . '_' . (int)$userid;
    if (!empty($syncingitem[$lockkey])) {
        return;
    }
    $syncingitem[$lockkey] = true;

    try {
        require_once($CFG->libdir . '/gradelib.php');

        $totalbonus = local_xpstore_get_user_item_bonus($courseid, $gradeitem, $userid);
        $basekey = "item_base_{$gradeitem->id}_{$userid}";
        $ovrkey = "item_ovr_{$gradeitem->id}_{$userid}";
        $savedbase = get_config('local_xpstore', $basekey);

        // If triggered from user_graded observer and user has no bonus on this item,
        // do not touch this grade item at all.
        if ($totalbonus <= 0 && $fromobserver) {
            $syncingitem[$lockkey] = false;
            return;
        }

        $grade = $DB->get_record('grade_grades', ['itemid' => $gradeitem->id, 'userid' => $userid]);
        if (
            $totalbonus <= 0 &&
            $savedbase === false &&
            (!$grade || empty($grade->feedback) || stripos($grade->feedback, 'points from XP Store') === false)
        ) {
            $syncingitem[$lockkey] = false;
            return;
        }
        $grademax = (float)$gradeitem->grademax;
        $grademin = (float)$gradeitem->grademin;

        if ($totalbonus > 0) {
            if ($grade) {
                if ($fromobserver) {
                    $expectedbonused = ($savedbase !== false && $savedbase !== 'null' && $savedbase !== '')
                        ? min($grademax, (float)$savedbase + $totalbonus)
                        : min($grademax, $grademin + $totalbonus);
                    if (
                        $savedbase !== false &&
                        $grade->finalgrade !== null &&
                        abs((float)$grade->finalgrade - $expectedbonused) < 0.0001
                    ) {
                        $purebase = ($savedbase === 'null' || $savedbase === '') ? null : (float)$savedbase;
                    } else {
                        $purebase = ($grade->finalgrade !== null) ? (float)$grade->finalgrade : null;
                        set_config($basekey, $purebase !== null ? (string)$purebase : 'null', 'local_xpstore');
                    }
                } else if ($savedbase === false) {
                    $modknown = false;
                    $modgrade = local_xpstore_get_module_raw_grade($gradeitem, $userid, $modknown);
                    if ($modknown && $modgrade !== null) {
                        $purebase = $modgrade;
                    } else {
                        $purebase = ($grade->finalgrade !== null) ? (float)$grade->finalgrade : null;
                    }
                    set_config($basekey, $purebase !== null ? (string)$purebase : 'null', 'local_xpstore');
                    set_config($ovrkey, '0', 'local_xpstore');
                } else {
                    $purebase = ($savedbase === 'null' || $savedbase === '') ? null : (float)$savedbase;
                }

                $baseval = ($purebase !== null) ? $purebase : $grademin;
                $newvalue = min($grademax, $baseval + $totalbonus);

                // Set both rawgrade and finalgrade so Moodle's grade_regrade_final_grades() preserves
                // finalgrade while overridden remains 0 (keeping Assignment/Forum grading unlocked).
                $grade->rawgrade = $newvalue;
                $grade->finalgrade = $newvalue;
                $grade->overridden = 0;
                $grade->feedback = local_xpstore_merge_grade_feedback($grade->feedback ?? '', $totalbonus);
                $grade->feedbackformat = FORMAT_HTML;
                $grade->timemodified = time();
                $DB->update_record('grade_grades', $grade);
            } else {
                $modknown = false;
                $modgrade = local_xpstore_get_module_raw_grade($gradeitem, $userid, $modknown);
                $purebase = $modknown ? $modgrade : null;
                set_config($basekey, $purebase !== null ? (string)$purebase : 'null', 'local_xpstore');
                set_config($ovrkey, '0', 'local_xpstore');
                $baseval = ($purebase !== null) ? $purebase : $grademin;
                $newvalue = min($grademax, $baseval + $totalbonus);
                $newgrade = (object)[
                    'itemid' => $gradeitem->id,
                    'userid' => $userid,
                    'rawgrade' => $newvalue,
                    'rawgrademax' => $grademax,
                    'rawgrademin' => $grademin,
                    'finalgrade' => $newvalue,
                    'overridden' => 0,
                    'feedback' => local_xpstore_merge_grade_feedback('', $totalbonus),
                    'feedbackformat' => FORMAT_HTML,
                    'timecreated' => time(),
                    'timemodified' => time(),
                ];
                $DB->insert_record('grade_grades', $newgrade);
            }
        } else {
            if ($grade) {
                $modknown = false;
                $modgrade = local_xpstore_get_module_raw_grade($gradeitem, $userid, $modknown);
                if ($savedbase !== false) {
                    $origgrade = ($savedbase === 'null' || $savedbase === '') ? null : (float)$savedbase;
                } else if ($modknown) {
                    $origgrade = $modgrade;
                } else if (
                    $grade->finalgrade !== null &&
                    !empty($grade->feedback) &&
                    preg_match('/\+?([0-9]+(?:\.[0-9]+)?)\s*points from XP Store/i', $grade->feedback, $m)
                ) {
                    $origgrade = max($grademin, (float)$grade->finalgrade - (float)$m[1]);
                } else {
                    $origgrade = null;
                }

                $grade->rawgrade = $origgrade;
                $grade->finalgrade = $origgrade;
                $grade->overridden = 0;
                $grade->feedback = local_xpstore_merge_grade_feedback($grade->feedback ?? '', 0);
                $grade->timemodified = time();
                $DB->update_record('grade_grades', $grade);
            }

            if ($savedbase !== false) {
                unset_config($basekey, 'local_xpstore');
            }
            if (get_config('local_xpstore', $ovrkey) !== false) {
                unset_config($ovrkey, 'local_xpstore');
            }
        }
    } catch (\Throwable $e) {
        debugging('local_xpstore_sync_item_grade_bonus error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

    $syncingitem[$lockkey] = false;

    if (!$fromobserver) {
        try {
            $gitem = \grade_item::fetch(['id' => $gradeitem->id]);
            if ($gitem) {
                $gitem->force_regrading();
            }
            grade_regrade_final_grades($courseid);
        } catch (\Throwable $e) {
            debugging('local_xpstore regrade error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            $DB->set_field('grade_items', 'needsupdate', 0, ['courseid' => $courseid, 'needsupdate' => 1]);
        }
    }
}

/**
 * Gets the current Level Up! (block_xp) level of a user in a course.
 *
 * @param int $userid The user ID.
 * @param int $courseid The course ID.
 * @return int The user's level (defaults to 1).
 */
function local_xpstore_get_user_level($userid, $courseid) {
    global $DB;

    $lvl = null;
    if (class_exists('\block_xp\di')) {
        try {
            $world = \block_xp\di::get('course_world_factory')->get_world($courseid);
            if ($world) {
                $state = $world->get_store()->get_state($userid);
                if ($state && method_exists($state, 'get_level')) {
                    $levelobj = $state->get_level();
                    if ($levelobj && method_exists($levelobj, 'get_level')) {
                        return max(1, (int)$levelobj->get_level());
                    }
                }
            }
        } catch (\Throwable $e) {
            $lvl = null;
        }
    }

    $lvl = $DB->get_field('block_xp', 'lvl', ['userid' => $userid, 'courseid' => $courseid]);
    return $lvl ? max(1, (int)$lvl) : 1;
}

/**
 * Registers a purchase in the database.
 *
 * @param int $userid The user ID.
 * @param string $type The item type.
 * @param int $cmid The course module ID.
 * @param int $cost The cost of the item.
 * @param int $courseid The course ID.
 * @return bool True if successful, false otherwise.
 */
function local_xpstore_purchase($userid, $type, $cmid, $cost, $courseid) {
    global $DB;
    if (local_xpstore_get_balance($userid, $courseid) >= $cost) {
        $record = new stdClass();
        $record->userid = $userid;
        $record->itemtype = $type;
        $record->itemid = $cmid;
        $record->amount = $cost;
        $record->timecreated = time();
        $DB->insert_record('local_xpstore_gastos', $record);
        return true;
    }
    return false;
}

/**
 * Delivers the purchased product to the user modifying overrides or grades.
 *
 * @param int $userid The user ID.
 * @param int $cmid The course module ID.
 * @param string $type The item type.
 * @param int $courseid The course ID (optional).
 * @return bool True if successful, false otherwise.
 */
function local_xpstore_deliver_product($userid, $cmid, $type, $courseid = null) {
    global $DB, $CFG;

    try {
        $cm = null;
        if ($type !== 'M' && $type !== 'C') {
            $cm = $DB->get_record('course_modules', ['id' => $cmid], '*', MUST_EXIST);
        }
        $deadline = time() + (3 * 24 * 60 * 60);

        // If course ID was not sent, we try to get it from the Course Module or Grade Item.
        if (!$courseid && $cm) {
            $courseid = $cm->course;
        } else if (!$courseid && ($type === 'M' || $type === 'C')) {
            $courseid = (int)$DB->get_field('grade_items', 'courseid', ['id' => $cmid]);
        }

        // FIX: Read the exclusive catalog of the course instead of the obsolete global variable.
        $configraw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
        $itemsconfig = explode(',', $configraw);
        $rewardname = "VIP";
        $gradevalue = 0.5;

        foreach ($itemsconfig as $item) {
            $parts = explode(':', trim($item));
            // Type + CMID, e.g. 'G123'.
            if (isset($parts[0]) && $parts[0] === $type . $cmid) {
                $rewardname = isset($parts[2]) ? trim($parts[2]) : "VIP";
                // Rescues the real value configured by the teacher in parameter 4.
                $gradevalue = isset($parts[3]) ? (float)$parts[3] : 0.5;
                break;
            }
        }

        if ($type === 'Q') {
            $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
            $override = $DB->get_record('quiz_overrides', ['quiz' => $quiz->id, 'userid' => $userid]);

            if ($override) {
                $override->attempts = ($override->attempts ?: $quiz->attempts) + 1;
                $override->timeclose = max($override->timeclose, $deadline);
                $DB->update_record('quiz_overrides', $override);
            } else {
                $new = (object)[
                    'quiz' => $quiz->id,
                    'userid' => $userid,
                    'attempts' => ($quiz->attempts ?: 1) + 1,
                    'timeclose' => $deadline,
                ];
                $DB->insert_record('quiz_overrides', $new);
            }
        } else if ($type === 'A') {
            $assign = $DB->get_record('assign', ['id' => $cm->instance], '*', MUST_EXIST);
            $override = $DB->get_record('assign_overrides', ['assignid' => $assign->id, 'userid' => $userid]);

            if ($override) {
                $override->duedate = $deadline;
                $override->cutoffdate = $deadline;
                $DB->update_record('assign_overrides', $override);
            } else {
                $new = (object)[
                    'assignid' => $assign->id,
                    'userid' => $userid,
                    'duedate' => $deadline,
                    'cutoffdate' => $deadline,
                ];
                $DB->insert_record('assign_overrides', $new);
            }

            $flag = $DB->get_record('assign_user_flags', ['assignment' => $assign->id, 'userid' => $userid]);

            if ($flag) {
                $flag->extensionduedate = $deadline;
                $flag->locked = 0;
                $DB->update_record('assign_user_flags', $flag);
            } else {
                $newflag = (object)[
                    'assignment' => $assign->id,
                    'userid' => $userid,
                    'extensionduedate' => $deadline,
                    'locked' => 0,
                    'mailed' => 0,
                ];
                $DB->insert_record('assign_user_flags', $newflag);
            }
        } else if ($type === 'G' || $type === 'M') {
            if ($type === 'G') {
                $itemmodule = $DB->get_field('modules', 'name', ['id' => $cm->module]);
                $gradeitems = local_xpstore_get_mod_grade_items($cm->course, $itemmodule, $cm->instance);
                foreach ($gradeitems as $gradeitem) {
                    local_xpstore_sync_item_grade_bonus($courseid, $gradeitem, $userid, false);
                }
            } else {
                $gradeitem = $DB->get_record('grade_items', ['id' => $cmid]);
                if ($gradeitem) {
                    local_xpstore_sync_item_grade_bonus($courseid, $gradeitem, $userid, false);
                }
            }
        } else if ($type === 'C') {
            $bonuskey = "course_bonus_{$courseid}_{$userid}";
            $currentbonus = (float)(get_config('local_xpstore', $bonuskey) ?: 0);
            set_config($bonuskey, (string)($currentbonus + $gradevalue), 'local_xpstore');
            local_xpstore_sync_course_grade_bonus($courseid, $userid);
        } else if ($type === 'S') {
            require_once($CFG->dirroot . '/group/lib.php');
            $group = $DB->get_record('groups', ['courseid' => $cm->course, 'name' => $rewardname]);

            if (!$group) {
                $newgroup = new stdClass();
                $newgroup->courseid = $cm->course;
                $newgroup->name = $rewardname;
                $groupid = groups_create_group($newgroup);
            } else {
                $groupid = $group->id;
            }

            if (!groups_is_member($groupid, $userid)) {
                groups_add_member($groupid, $userid);
            }
        } else if ($type === 'F') {
            $context = context_module::instance($cm->id);
            $roleid = local_xpstore_get_or_create_forum_role();
            if ($roleid) {
                role_assign($roleid, $userid, $context->id);
            }
        }

        if ($courseid) {
            rebuild_course_cache($courseid, true);
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Add elements to the reset course form.
 *
 * @param MoodleQuickForm $mform The form.
 * @return void
 */
function local_xpstore_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'tiendagamificadaheader', get_string('tiendaxp', 'local_xpstore'));
    $mform->addElement('advcheckbox', 'reset_tiendagamificada_gastos', get_string('resethistory', 'local_xpstore'));
}

/**
 * Scans grade_grades in a course and reverts any leftover XP Store bonus or feedback
 * for items where the user no longer has active XP Store bonus purchases.
 *
 * @param int $courseid The course ID.
 * @param int|null $userid Optional user ID filter.
 * @return void
 */
function local_xpstore_cleanup_orphan_grade_bonuses($courseid, $userid = null) {
    global $DB;

    $like = $DB->sql_like('gg.feedback', '?', false);
    $params = [(int)$courseid, '%points from XP Store%'];
    $usersql = '';
    if ($userid !== null) {
        $usersql = ' AND gg.userid = ?';
        $params[] = (int)$userid;
    }

    $sql = "SELECT gg.id AS gradeid, gg.userid, gi.*
              FROM {grade_grades} gg
              JOIN {grade_items} gi ON gi.id = gg.itemid
             WHERE gi.courseid = ? AND {$like}{$usersql}";
    $rows = $DB->get_records_sql($sql, $params);
    if (empty($rows)) {
        return;
    }

    foreach ($rows as $row) {
        $uid = (int)$row->userid;
        if ($row->itemtype === 'course') {
            if (local_xpstore_get_user_course_bonus($courseid, $uid) <= 0) {
                unset_config("course_bonus_{$courseid}_{$uid}", 'local_xpstore');
                local_xpstore_sync_course_grade_bonus($courseid, $uid);
            }
        } else if ($row->itemtype === 'mod' || $row->itemtype === 'manual') {
            if (local_xpstore_get_user_item_bonus($courseid, $row, $uid) <= 0) {
                local_xpstore_sync_item_grade_bonus($courseid, $row, $uid, false);
            }
        }
    }
}

/**
 * Resets purchases for a course (or a single user in a course) and restores any grades
 * and feedback modified by XP Store bonuses ('G', 'M', and 'C').
 *
 * @param int $courseid The course ID.
 * @param int|null $userid Optional user ID to reset only that user.
 * @return void
 */
function local_xpstore_reset_purchases($courseid, $userid = null) {
    global $DB, $CFG;

    $params = [$courseid, $courseid];
    $usersql = '';
    if ($userid !== null) {
        $usersql = ' AND g.userid = ?';
        $params[] = (int)$userid;
    }

    // Collect all purchases before deleting them so we can revert grade bonuses.
    $sql = "SELECT g.id, g.userid, g.itemtype, g.itemid, cm.module, cm.instance
              FROM {local_xpstore_gastos} g
              LEFT JOIN {course_modules} cm ON g.itemid = cm.id AND g.itemtype NOT IN ('M', 'C')
              LEFT JOIN {grade_items} gi ON g.itemid = gi.id AND g.itemtype IN ('M', 'C')
             WHERE (cm.course = ? OR gi.courseid = ?){$usersql}";
    $records = $DB->get_records_sql($sql, $params);

    if (empty($records)) {
        if ($userid !== null) {
            unset_config("course_bonus_{$courseid}_{$userid}", 'local_xpstore');
            local_xpstore_sync_course_grade_bonus($courseid, $userid);
        }
        local_xpstore_cleanup_orphan_grade_bonuses($courseid, $userid);
        return;
    }

    // Parse current catalog bonus values and reward names for fallback if needed.
    $configraw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
    $itemsconfig = array_filter(explode(',', $configraw));
    $catalogvalues = [];
    $catalogrewards = [];
    foreach ($itemsconfig as $item) {
        $parts = explode(':', trim($item));
        if (!empty($parts[0])) {
            $catalogrewards[$parts[0]] = isset($parts[2]) && trim($parts[2]) !== '' ? trim($parts[2]) : 'VIP';
            $catalogvalues[$parts[0]] = isset($parts[3]) ? (float)$parts[3] : 0.5;
        }
    }

    $gastoids = [];
    $coursebonususers = [];
    $itembonuses = [];

    foreach ($records as $rec) {
        $gastoids[] = $rec->id;
        $uid = (int)$rec->userid;
        if ($rec->itemtype === 'C') {
            $coursebonususers[$uid] = $uid;
        } else if ($rec->itemtype === 'G' || $rec->itemtype === 'M') {
            $key = $rec->itemtype . '_' . $rec->itemid . '_' . $uid;
            if (!isset($itembonuses[$key])) {
                $itembonuses[$key] = [
                    'userid' => $uid,
                    'itemtype' => $rec->itemtype,
                    'itemid' => (int)$rec->itemid,
                    'module' => $rec->module,
                    'instance' => $rec->instance,
                    'count' => 0,
                ];
            }
            $itembonuses[$key]['count']++;
        } else if ($rec->itemtype === 'Q' && !empty($rec->instance)) {
            $DB->delete_records('quiz_overrides', ['quiz' => $rec->instance, 'userid' => $uid]);
        } else if ($rec->itemtype === 'A' && !empty($rec->instance)) {
            $DB->delete_records('assign_overrides', ['assignid' => $rec->instance, 'userid' => $uid]);
            $flag = $DB->get_record('assign_user_flags', ['assignment' => $rec->instance, 'userid' => $uid]);
            if ($flag) {
                $flag->extensionduedate = 0;
                $DB->update_record('assign_user_flags', $flag);
            }
        } else if ($rec->itemtype === 'S') {
            require_once($CFG->dirroot . '/group/lib.php');
            $rewardname = $catalogrewards['S' . $rec->itemid] ?? 'VIP';
            $group = $DB->get_record('groups', ['courseid' => $courseid, 'name' => $rewardname]);
            if ($group && groups_is_member($group->id, $uid)) {
                groups_remove_member($group->id, $uid);
            }
        } else if ($rec->itemtype === 'F') {
            $cmcontext = context_module::instance((int)$rec->itemid, IGNORE_MISSING);
            $roleid = local_xpstore_get_or_create_forum_role();
            if ($cmcontext && $roleid) {
                role_unassign($roleid, $uid, $cmcontext->id);
            }
        }
    }

    $DB->delete_records_list('local_xpstore_gastos', 'id', $gastoids);

    require_once($CFG->libdir . '/gradelib.php');

    // Revert activity ('G') and manual item ('M') bonuses and feedback.
    foreach ($itembonuses as $ib) {
        $gradeitems = [];
        if ($ib['itemtype'] === 'G' && !empty($ib['module'])) {
            $itemmodule = $DB->get_field('modules', 'name', ['id' => $ib['module']]);
            if ($itemmodule) {
                $gradeitems = local_xpstore_get_mod_grade_items($courseid, $itemmodule, $ib['instance']);
            }
        } else if ($ib['itemtype'] === 'M') {
            $gi = $DB->get_record('grade_items', ['id' => $ib['itemid']]);
            if ($gi) {
                $gradeitems = [$gi];
            }
        }

        foreach ($gradeitems as $gradeitem) {
            local_xpstore_sync_item_grade_bonus($courseid, $gradeitem, (int)$ib['userid'], false);
        }
    }

    // Revert course total ('C') bonuses and feedback.
    foreach ($coursebonususers as $uid) {
        unset_config("course_bonus_{$courseid}_{$uid}", 'local_xpstore');
        local_xpstore_sync_course_grade_bonus($courseid, $uid);
    }

    local_xpstore_cleanup_orphan_grade_bonuses($courseid, $userid);
    rebuild_course_cache($courseid, true);
}

/**
 * Deletes/undoes a single purchase record in a course, refunds the XP spent,
 * and reverts the corresponding bonus or benefit ('C', 'G', 'M', 'Q', 'A', 'S', 'F').
 *
 * @param int $courseid The course ID.
 * @param int $gastoid The ID of the purchase in {local_xpstore_gastos}.
 * @return int|null The user ID whose purchase was undone, or null if not found.
 */
function local_xpstore_delete_single_purchase($courseid, $gastoid) {
    global $DB, $CFG;

    $sql = "SELECT g.*, cm.module, cm.instance
              FROM {local_xpstore_gastos} g
              LEFT JOIN {course_modules} cm ON g.itemid = cm.id AND g.itemtype NOT IN ('M', 'C')
              LEFT JOIN {grade_items} gi ON g.itemid = gi.id AND g.itemtype IN ('M', 'C')
             WHERE g.id = ? AND (cm.course = ? OR gi.courseid = ?)";
    $rec = $DB->get_record_sql($sql, [(int)$gastoid, (int)$courseid, (int)$courseid]);
    if (!$rec) {
        return null;
    }

    $userid = (int)$rec->userid;
    $type = $rec->itemtype;
    $itemid = (int)$rec->itemid;

    // Delete the single purchase row (refunds XP balance and restores stock/limits).
    $DB->delete_records('local_xpstore_gastos', ['id' => $rec->id]);

    $remainingcount = (int)$DB->count_records('local_xpstore_gastos', [
        'userid' => $userid,
        'itemtype' => $type,
        'itemid' => $itemid,
    ]);

    // Read catalog config for reward name and bonus value.
    $configraw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
    $itemsconfig = array_filter(explode(',', $configraw));
    $rewardname = 'VIP';
    $gradevalue = 0.5;
    foreach ($itemsconfig as $item) {
        $parts = explode(':', trim($item));
        if (isset($parts[0]) && $parts[0] === $type . $itemid) {
            $rewardname = isset($parts[2]) && trim($parts[2]) !== '' ? trim($parts[2]) : 'VIP';
            $gradevalue = isset($parts[3]) ? (float)$parts[3] : 0.5;
            break;
        }
    }

    if ($type === 'C') {
        $bonuskey = "course_bonus_{$courseid}_{$userid}";
        if ($remainingcount === 0) {
            unset_config($bonuskey, 'local_xpstore');
        } else {
            $currentbonus = (float)(get_config('local_xpstore', $bonuskey) ?: 0);
            $newbonus = max(0.0, $currentbonus - $gradevalue);
            if ($newbonus > 0.0001) {
                set_config($bonuskey, (string)$newbonus, 'local_xpstore');
            } else {
                unset_config($bonuskey, 'local_xpstore');
            }
        }
        local_xpstore_sync_course_grade_bonus($courseid, $userid);
    } else if ($type === 'G' || $type === 'M') {
        require_once($CFG->libdir . '/gradelib.php');
        $gradeitems = [];
        if ($type === 'G' && !empty($rec->module)) {
            $itemmodule = $DB->get_field('modules', 'name', ['id' => $rec->module]);
            if ($itemmodule) {
                $gradeitems = local_xpstore_get_mod_grade_items($courseid, $itemmodule, $rec->instance);
            }
        } else if ($type === 'M') {
            $gi = $DB->get_record('grade_items', ['id' => $itemid]);
            if ($gi) {
                $gradeitems = [$gi];
            }
        }

        foreach ($gradeitems as $gradeitem) {
            local_xpstore_sync_item_grade_bonus($courseid, $gradeitem, $userid, false);
        }
    } else if ($type === 'Q' && !empty($rec->instance)) {
        $override = $DB->get_record('quiz_overrides', ['quiz' => $rec->instance, 'userid' => $userid]);
        if ($override) {
            if ($remainingcount === 0) {
                $DB->delete_records('quiz_overrides', ['id' => $override->id]);
            } else if ((int)$override->attempts > 1) {
                $override->attempts = max(1, (int)$override->attempts - 1);
                $DB->update_record('quiz_overrides', $override);
            }
        }
    } else if ($type === 'A' && !empty($rec->instance) && $remainingcount === 0) {
        $DB->delete_records('assign_overrides', ['assignid' => $rec->instance, 'userid' => $userid]);
        $flag = $DB->get_record('assign_user_flags', ['assignment' => $rec->instance, 'userid' => $userid]);
        if ($flag) {
            $flag->extensionduedate = 0;
            $DB->update_record('assign_user_flags', $flag);
        }
    } else if ($type === 'S' && $remainingcount === 0) {
        require_once($CFG->dirroot . '/group/lib.php');
        $group = $DB->get_record('groups', ['courseid' => $courseid, 'name' => $rewardname]);
        if ($group && groups_is_member($group->id, $userid)) {
            groups_remove_member($group->id, $userid);
        }
    } else if ($type === 'F' && $remainingcount === 0) {
        $cmcontext = context_module::instance($itemid, IGNORE_MISSING);
        $roleid = local_xpstore_get_or_create_forum_role();
        if ($cmcontext && $roleid) {
            role_unassign($roleid, $userid, $cmcontext->id);
        }
    }

    rebuild_course_cache($courseid, true);
    return $userid;
}

/**
 * Reset userdata for the store.
 *
 * @param stdClass $data The data submitted.
 * @return array Status array.
 */
function local_xpstore_reset_userdata($data) {
    if (!empty($data->reset_tiendagamificada_gastos)) {
        local_xpstore_reset_purchases($data->courseid);
    }
    return [];
}

/**
 * Gets or creates the custom role for extending forum deadlines.
 *
 * @return int|bool The role ID or false.
 */
function local_xpstore_get_or_create_forum_role() {
    global $DB, $CFG;
    require_once($CFG->libdir . '/accesslib.php');

    $shortname = 'xpstore_forum_ext';
    if ($role = $DB->get_record('role', ['shortname' => $shortname])) {
        return $role->id;
    }

    $roleid = create_role(
        'XP Store Forum Extension',
        $shortname,
        'Auto-created role to allow extending forum deadlines via XP Store.'
    );
    if ($roleid) {
        set_role_contextlevels($roleid, [CONTEXT_MODULE]);
        $syscontext = context_system::instance();
        assign_capability('mod/forum:canoverridecutoff', CAP_ALLOW, $roleid, $syscontext->id, true);
        return $roleid;
    }

    return false;
}

/**
 * Automates group restriction injection for Special rewards.
 *
 * @param int $cmid The course module ID.
 * @param int $groupid The group ID to restrict access to.
 * @param int $courseid The course ID for cache rebuild.
 */
function local_xpstore_apply_special_restriction($cmid, $groupid, $courseid) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');

    $cm = $DB->get_record('course_modules', ['id' => $cmid]);
    if (!$cm) {
        return;
    }

    $availability = $cm->availability;
    $newrestriction = ['type' => 'group', 'id' => (int)$groupid];

    if (empty($availability)) {
        $tree = [
            'op' => '&',
            'c' => [$newrestriction],
            'showc' => [false],
            'show' => false,
        ];
        $availability = json_encode($tree);
    } else {
        $tree = json_decode($availability, true);
        if (!is_array($tree) || !isset($tree['c']) || !isset($tree['showc'])) {
            // Invalid JSON, overwrite safely.
            $tree = [
                'op' => '&',
                'c' => [$newrestriction],
                'showc' => [false],
                'show' => false,
            ];
            $availability = json_encode($tree);
        } else {
            // Check if this specific group restriction already exists.
            $exists = false;
            foreach ($tree['c'] as $cond) {
                if (isset($cond['type']) && $cond['type'] === 'group' && isset($cond['id']) && $cond['id'] == $groupid) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $tree['c'][] = $newrestriction;
                $tree['showc'][] = false; // Add 'eye closed'.
                $tree['show'] = false; // Ensure root show is false.
                $availability = json_encode($tree);
            }
        }
    }

    if ($cm->availability !== $availability) {
        $DB->set_field('course_modules', 'availability', $availability, ['id' => $cmid]);
        rebuild_course_cache($courseid, true);
    }
}

/**
 * Helper to get the navigation tabs data.
 *
 * @param int $courseid
 * @param string $activetab The currently active tab (store, redemptions, analytics, products, settings)
 * @return array
 */
function local_xpstore_get_navigation_data($courseid, $activetab) {
    return [
        'storeurl' => (new moodle_url('/local/xpstore/index.php', ['id' => $courseid]))->out(false),
        'reporturl' => (new moodle_url('/local/xpstore/report.php', ['id' => $courseid]))->out(false),
        'analyticsurl' => (new moodle_url('/local/xpstore/analytics.php', ['id' => $courseid]))->out(false),
        'productsurl' => (new moodle_url('/local/xpstore/config.php', ['id' => $courseid, 'tab' => 'products']))->out(false),
        'settingsurl' => (new moodle_url('/local/xpstore/config.php', ['id' => $courseid, 'tab' => 'settings']))->out(false),
        'str_store' => get_string('tiendaxp', 'local_xpstore'),
        'str_audit' => get_string('audit', 'local_xpstore'),
        'str_analytics' => get_string('analytics', 'local_xpstore'),
        'str_products' => get_string('products', 'local_xpstore'),

        'str_settings' => get_string('settings', 'local_xpstore'),
        'str_preview' => get_string('preview', 'local_xpstore'),
        'str_style' => get_string('style', 'local_xpstore'),

        'tab_store' => ($activetab === 'store'),
        'tab_redemptions' => ($activetab === 'redemptions'),
        'tab_analytics' => ($activetab === 'analytics'),
        'tab_products' => ($activetab === 'products'),
        'tab_settings' => ($activetab === 'settings'),
    ];
}

/**
 * Automates xpstore restriction injection for Unlock rewards.
 *
 * @param int $cmid The course module ID.
 * @param string $productid The product ID to restrict access to (e.g. U123).
 * @param int $courseid The course ID for cache rebuild.
 * @param int $hiddenwhenlocked Whether to completely hide the activity when locked (1 = hide, 0 = show restricted).
 */
function local_xpstore_apply_unlock_restriction($cmid, $productid, $courseid, $hiddenwhenlocked = 0) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');

    $cm = $DB->get_record('course_modules', ['id' => $cmid]);
    if (!$cm) {
        return;
    }

    $availability = $cm->availability;
    $newrestriction = ['type' => 'xpstore', 'productid' => (string)$productid];

    $showstatus = empty($hiddenwhenlocked) ? true : false;

    if (empty($availability)) {
        $tree = [
            'op' => '&',
            'c' => [$newrestriction],
            'showc' => [$showstatus],
            'show' => $showstatus,
        ];
        $availability = json_encode($tree);
    } else {
        $tree = json_decode($availability, true);
        if (!is_array($tree) || !isset($tree['c']) || !isset($tree['showc'])) {
            // Invalid JSON, overwrite safely.
            $tree = [
                'op' => '&',
                'c' => [$newrestriction],
                'showc' => [$showstatus],
                'show' => $showstatus,
            ];
            $availability = json_encode($tree);
        } else {
            // Check if this specific xpstore restriction already exists.
            $exists = false;
            foreach ($tree['c'] as $index => $cond) {
                if (
                    isset($cond['type']) && $cond['type'] === 'xpstore' &&
                    isset($cond['productid']) && $cond['productid'] == $productid
                ) {
                    $exists = true;
                    // Update existing visibility.
                    if (isset($tree['showc'][$index])) {
                        $tree['showc'][$index] = $showstatus;
                    }
                    break;
                }
            }
            if (!$exists) {
                $tree['c'][] = $newrestriction;
                $tree['showc'][] = $showstatus;
            }

            // Recompute root show property based on all showc properties.
            $allhidden = true;
            foreach ($tree['showc'] as $sc) {
                if ($sc) {
                    $allhidden = false;
                    break;
                }
            }
            $tree['show'] = !$allhidden;
            $availability = json_encode($tree);
        }
    }

    if ($cm->availability !== $availability) {
        $DB->set_field('course_modules', 'availability', $availability, ['id' => $cmid]);
        rebuild_course_cache($courseid, true);
    }
}

/**
 * Returns the default FontAwesome icon name for a given reward type.
 *
 * @param string $type The reward type code (Q, A, F, G, M, S, U).
 * @return string The FontAwesome icon name (without 'fa-').
 */
function local_xpstore_get_default_card_icon($type) {
    $iconmap = [
        'Q' => 'bolt',
        'A' => 'file-text',
        'F' => 'comments',
        'G' => 'star',
        'M' => 'star',
        'C' => 'star',
        'S' => 'unlock',
        'U' => 'unlock',
    ];
    return $iconmap[$type] ?? 'gift';
}

/**
 * Returns the list of available FontAwesome icons for categories and cards.
 *
 * @return array Associative array of icon_name => localized_label.
 */
function local_xpstore_get_available_icons() {
    $sm = get_string_manager();
    return [
        'bolt' => get_string('icon_bolt', 'local_xpstore'),
        'clock-o' => $sm->string_exists('icon_clock', 'local_xpstore')
            ? get_string('icon_clock', 'local_xpstore') : 'Clock',
        'file-text' => $sm->string_exists('icon_file', 'local_xpstore')
            ? get_string('icon_file', 'local_xpstore') : 'Document',
        'comments' => $sm->string_exists('icon_comments', 'local_xpstore')
            ? get_string('icon_comments', 'local_xpstore') : 'Comments',
        'star' => get_string('icon_star', 'local_xpstore'),
        'unlock' => $sm->string_exists('icon_unlock', 'local_xpstore')
            ? get_string('icon_unlock', 'local_xpstore') : 'Unlock',
        'key' => $sm->string_exists('icon_key', 'local_xpstore')
            ? get_string('icon_key', 'local_xpstore') : 'Key',
        'trophy' => get_string('icon_trophy', 'local_xpstore'),
        'medal' => get_string('icon_medal', 'local_xpstore'),
        'award' => get_string('icon_award', 'local_xpstore'),
        'money' => get_string('icon_money', 'local_xpstore'),
        'diamond' => get_string('icon_diamond', 'local_xpstore'),
        'gift' => get_string('icon_gift', 'local_xpstore'),
        'shopping-cart' => get_string('icon_cart', 'local_xpstore'),
        'shield' => get_string('icon_shield', 'local_xpstore'),
        'gamepad' => get_string('icon_gamepad', 'local_xpstore'),
        'heart' => get_string('icon_heart', 'local_xpstore'),
        'ticket' => get_string('icon_ticket', 'local_xpstore'),
        'magic' => get_string('icon_magic', 'local_xpstore'),
        'rocket' => get_string('icon_rocket', 'local_xpstore'),
        'graduation-cap' => get_string('icon_graduation', 'local_xpstore'),
        'puzzle-piece' => get_string('icon_puzzle', 'local_xpstore'),
        'music' => get_string('icon_music', 'local_xpstore'),
        'camera' => get_string('icon_camera', 'local_xpstore'),
        'globe' => get_string('icon_globe', 'local_xpstore'),
        'book' => get_string('icon_book', 'local_xpstore'),
    ];
}

/**
 * Checks whether the given card icon value represents a custom uploaded image.
 *
 * @param string $icon The icon identifier stored in the catalog.
 * @return bool True if it is a custom uploaded image identifier (img_<itemid>).
 */
function local_xpstore_is_custom_card_image($icon) {
    return is_string($icon) && strpos($icon, 'img_') === 0 && (int)substr($icon, 4) > 0;
}

/**
 * Resolves the Moodle pluginfile URL for a custom uploaded card image.
 *
 * @param int $courseid The course ID.
 * @param string $icon The icon identifier (e.g. img_123456).
 * @return string|null The image URL or null if not found.
 */
function local_xpstore_get_card_image_url($courseid, $icon) {
    if (!local_xpstore_is_custom_card_image($icon)) {
        return null;
    }
    $itemid = (int)substr($icon, 4);
    $context = context_course::instance($courseid);
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'local_xpstore', 'cardimage', $itemid, 'id DESC', false);
    if (!empty($files)) {
        $file = reset($files);
        return moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        )->out(false);
    }
    return null;
}

/**
 * Deletes a custom uploaded card image from Moodle file storage.
 *
 * @param int $courseid The course ID.
 * @param string $icon The icon identifier (e.g. img_123456).
 * @return void
 */
function local_xpstore_delete_card_image($courseid, $icon) {
    if (!local_xpstore_is_custom_card_image($icon)) {
        return;
    }
    $itemid = (int)substr($icon, 4);
    $context = context_course::instance($courseid);
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'local_xpstore', 'cardimage', $itemid);
}

/**
 * Validates and saves an uploaded card image (max 256 KB) into Moodle file storage.
 *
 * @param int $courseid The course ID.
 * @param array $uploadedfile The $_FILES entry for the uploaded image.
 * @param string $existingicon Optional previous icon identifier to clean up if it was a custom image.
 * @param string|null $errormsg Output error message if validation fails.
 * @return string|false New icon identifier ('img_' . $itemid) on success, or false on failure.
 */
function local_xpstore_save_card_image($courseid, array $uploadedfile, $existingicon = '', &$errormsg = null) {
    $maxbytes = 256 * 1024; // 256 KB.
    $sm = get_string_manager();

    if (
        !isset($uploadedfile['error']) ||
        $uploadedfile['error'] === UPLOAD_ERR_INI_SIZE ||
        $uploadedfile['error'] === UPLOAD_ERR_FORM_SIZE ||
        (isset($uploadedfile['size']) && $uploadedfile['size'] > $maxbytes)
    ) {
        $errormsg = $sm->string_exists('error_imagesize', 'local_xpstore')
            ? get_string('error_imagesize', 'local_xpstore')
            : 'La imagen supera el tamaño máximo permitido de 256 KB.';
        return false;
    }

    if (
        $uploadedfile['error'] !== UPLOAD_ERR_OK ||
        empty($uploadedfile['tmp_name']) ||
        !is_uploaded_file($uploadedfile['tmp_name'])
    ) {
        $errormsg = get_string('error');
        return false;
    }

    $imginfo = @getimagesize($uploadedfile['tmp_name']);
    $allowedmimes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
    if ($imginfo === false || !in_array($imginfo['mime'], $allowedmimes, true)) {
        $errormsg = $sm->string_exists('error_imagetype', 'local_xpstore')
            ? get_string('error_imagetype', 'local_xpstore')
            : 'Formato de imagen no válido (usa PNG, JPG, GIF o WEBP).';
        return false;
    }

    // Delete old custom image if replacing one.
    if (!empty($existingicon)) {
        local_xpstore_delete_card_image($courseid, $existingicon);
    }

    $context = context_course::instance($courseid);
    $fs = get_file_storage();

    // Generate a unique 9-digit itemid to bust browser caches upon image replacement.
    $itemid = (int)(substr((string)time(), -6) . mt_rand(100, 999));
    while ($fs->get_area_files($context->id, 'local_xpstore', 'cardimage', $itemid, 'id', false)) {
        $itemid = (int)(substr((string)time(), -6) . mt_rand(100, 999));
    }

    $filename = clean_param($uploadedfile['name'], PARAM_FILE);
    if (empty($filename)) {
        $filename = 'card_' . $itemid . '.png';
    }

    $filerecord = [
        'contextid' => $context->id,
        'component' => 'local_xpstore',
        'filearea'  => 'cardimage',
        'itemid'    => $itemid,
        'filepath'  => '/',
        'filename'  => $filename,
    ];

    $fs->create_file_from_pathname($filerecord, $uploadedfile['tmp_name']);
    return 'img_' . $itemid;
}

/**
 * Serves files from the local_xpstore file areas.
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param context $context The context.
 * @param string $filearea The name of the file area.
 * @param array $args Extra arguments (itemid, path).
 * @param bool $forcedownload Whether or not force download.
 * @param array $options Additional options affecting the file serving.
 * @return bool False if the file not found, just send the file otherwise and do not return anything.
 */
function local_xpstore_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_COURSE) {
        return false;
    }

    if ($filearea !== 'cardimage') {
        return false;
    }

    require_login($course, false, $cm);

    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_xpstore', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
