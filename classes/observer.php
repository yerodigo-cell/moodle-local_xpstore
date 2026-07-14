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
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Intercepts course restores/clones to update hardcoded widget IDs in HTML labels and pages.
     *
     * @param \core\event\course_restored $event The course restored event.
     */
    public static function course_restored(\core\event\course_restored $event) {
        global $DB;

        $newcourseid = $event->objectid;

        // The regex looks for: /local/xpstore/widget*.php?id=OLD_ID
        // and replaces OLD_ID with the $newcourseid. We use capture groups to find the old ID.
        $pattern = '/(\/local\/xpstore\/[^"\'?]+\.php\?id=)(\d+)/';
        $replacement = '${1}' . $newcourseid;

        $oldcourseid = null;

        // Get all unique module names present in the new course.
        $modinfo = get_fast_modinfo($newcourseid);
        $modnames = array_unique(array_map(function($cm) {
            return $cm->modname;
        }, $modinfo->get_cms()));

        // Fix all activities and find old course id.
        foreach ($modnames as $modname) {
            if ($DB->get_manager()->table_exists($modname)) {
                $records = $DB->get_records($modname, ['course' => $newcourseid]);
                foreach ($records as $record) {
                    $updated = false;

                    if (isset($record->intro) && strpos($record->intro, '/local/xpstore/') !== false) {
                        if (!$oldcourseid && preg_match($pattern, $record->intro, $matches)) {
                            $oldcourseid = (int)$matches[2];
                        }
                        $newintro = preg_replace($pattern, $replacement, $record->intro);
                        if ($newintro !== $record->intro) {
                            $record->intro = $newintro;
                            $updated = true;
                        }
                    }

                    if (isset($record->content) && strpos($record->content, '/local/xpstore/') !== false) {
                        if (!$oldcourseid && preg_match($pattern, $record->content, $matches)) {
                            $oldcourseid = (int)$matches[2];
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

        // Fallback to Moodle event data if we still don't have the old course id.
        if (!$oldcourseid && isset($event->other['originalcourseid'])) {
            $oldcourseid = $event->other['originalcourseid'];
        }

        // Copy store configuration and map CMIDs.
        if ($oldcourseid && $oldcourseid != $newcourseid) {
            // 1. Copy simple string configs (colors, icons).
            $keys = [
                'color_primary',
                'color_secondary',
                'color_icon',
                'color_cat_icon',
                'cat_icons',
                'show_menu',
            ];
            foreach ($keys as $key) {
                $oldval = get_config('local_xpstore', $key . '_course_' . $oldcourseid);
                if ($oldval !== false) {
                    set_config($key . '_course_' . $newcourseid, $oldval, 'local_xpstore');
                }
            }

            // 2. Copy and map the catalog string.
            $oldcatalog = get_config('local_xpstore', 'catalog_course_' . $oldcourseid);
            if (!empty($oldcatalog)) {
                $oldmodinfo = get_fast_modinfo($oldcourseid);
                $newmodinfo = get_fast_modinfo($newcourseid);

                $items = array_filter(array_map('trim', explode(',', $oldcatalog)));
                $newcatalogparts = [];

                foreach ($items as $item) {
                    $tipochar = substr($item, 0, 1);
                    $rest = substr($item, 1);
                    $parts = explode(':', $rest);

                    if (count($parts) >= 2) {
                        $oldcmid = (int)$parts[0];

                        // Find the old cm's name and modname.
                        if (isset($oldmodinfo->cms[$oldcmid])) {
                            $oldcm = $oldmodinfo->cms[$oldcmid];
                            $oldname = $oldcm->name;
                            $oldmodname = $oldcm->modname;

                            // Search for the corresponding cm in the new course.
                            $newcmid = null;
                            foreach ($newmodinfo->get_cms() as $newcm) {
                                if ($newcm->name === $oldname && $newcm->modname === $oldmodname) {
                                    $newcmid = $newcm->id;
                                    break;
                                }
                            }

                            // If we found it, update the ID in the catalog string and in the widget URLs.
                            if ($newcmid) {
                                $parts[0] = $newcmid;
                                $newcatalogparts[] = $tipochar . implode(':', $parts);

                                // Replace cmid=OLD in all activities so individual widgets keep working.
                                $cmidpattern = '/([?&]|&amp;)cmid=' . $oldcmid . '([&"\']|&amp;)/';
                                $cmidreplacement = '${1}cmid=' . $newcmid . '${2}';

                                foreach ($modnames as $modname) {
                                    if ($DB->get_manager()->table_exists($modname)) {
                                        $records = $DB->get_records($modname, ['course' => $newcourseid]);
                                        foreach ($records as $record) {
                                            $updated = false;
                                            
                                            if (isset($record->intro) && strpos($record->intro, 'cmid=' . $oldcmid) !== false) {
                                                $newintro = preg_replace($cmidpattern, $cmidreplacement, $record->intro);
                                                if ($newintro !== $record->intro) {
                                                    $record->intro = $newintro;
                                                    $updated = true;
                                                }
                                            }
                                            
                                            if (isset($record->content) && strpos($record->content, 'cmid=' . $oldcmid) !== false) {
                                                $newcontent = preg_replace($cmidpattern, $cmidreplacement, $record->content);
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
    }
}
