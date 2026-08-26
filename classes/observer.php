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
        $modnames = array_unique(array_map(function ($cm) {
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

                        // Search for the corresponding cm or grade item in the new course.
                        $newcmid = null;

                        if ($tipochar === 'M') {
                            // It's a manual grade item, not a course module.
                            $olditemname = $DB->get_field('grade_items', 'itemname', ['id' => $oldcmid]);
                            if ($olditemname) {
                                // Find the new grade item by name in the new course.
                                $newitemid = $DB->get_field('grade_items', 'id', [
                                    'courseid' => $newcourseid,
                                    'itemtype' => 'manual',
                                    'itemname' => $olditemname
                                ]);
                                if ($newitemid) {
                                    $newcmid = $newitemid;
                                }
                            }
                        } else {
                            // Find the old cm's name and modname.
                            if (isset($oldmodinfo->cms[$oldcmid])) {
                                $oldcm = $oldmodinfo->cms[$oldcmid];
                                $oldname = $oldcm->name;
                                $oldmodname = $oldcm->modname;

                                // Search for the corresponding cm in the new course.
                                foreach ($newmodinfo->get_cms() as $newcm) {
                                    if ($newcm->name === $oldname && $newcm->modname === $oldmodname) {
                                        $newcmid = $newcm->id;
                                        break;
                                    }
                                }
                            }
                        }

                            // If we found it, update the ID in the catalog string and in the widget URLs.
                            if ($newcmid) {
                                $parts[0] = $newcmid;

                                // Also map the requirement (requisito) ID if it exists.
                                if (isset($parts[6]) && (int)$parts[6] > 0) {
                                    $oldreqid = (int)$parts[6];
                                    if (isset($oldmodinfo->cms[$oldreqid])) {
                                        $oldreqcm = $oldmodinfo->cms[$oldreqid];
                                        $oldreqname = $oldreqcm->name;
                                        $oldreqmodname = $oldreqcm->modname;

                                        $newreqid = null;
                                        foreach ($newmodinfo->get_cms() as $newreqcm_cand) {
                                            if ($newreqcm_cand->name === $oldreqname && $newreqcm_cand->modname === $oldreqmodname) {
                                                $newreqid = $newreqcm_cand->id;
                                                break;
                                            }
                                        }

                                        if ($newreqid) {
                                            $parts[6] = $newreqid;
                                        } else {
                                            $parts[6] = 0; // Reset if not found
                                        }
                                    }
                                }

                                $newcatalogparts[] = $tipochar . implode(':', $parts);

                                // Replace cmid=OLD in all activities so individual widgets keep working.
                                // We match either tipo=T...cmid=C or cmid=C...tipo=T to avoid false overlap (especially for grade items).
                                $cmidpattern = '/([?&]|&amp;)(tipo=' . preg_quote($tipochar) . '([&]|&amp;)cmid=' . preg_quote($oldcmid) . '|cmid=' . preg_quote($oldcmid) . '([&]|&amp;)tipo=' . preg_quote($tipochar) . ')([&"\']|&amp;)/';
                                // Normalize to tipo=T&cmid=NEW (using the matched ampersand style).
                                $cmidreplacement = function($matches) use ($tipochar, $newcmid) {
                                    $amp = isset($matches[3]) && $matches[3] ? $matches[3] : (isset($matches[4]) ? $matches[4] : '&');
                                    return $matches[1] . 'tipo=' . $tipochar . $amp . 'cmid=' . $newcmid . $matches[5];
                                };

                                // Fix 'Unlock' availability restrictions that contain U{oldcmid}.
                                if ($tipochar === 'U') {
                                    $oldproductid = 'U' . $oldcmid;
                                    $newproductid = 'U' . $newcmid;
                                    $cmrecords = $DB->get_records('course_modules', ['course' => $newcourseid]);
                                    foreach ($cmrecords as $cmrec) {
                                        if (!empty($cmrec->availability) && strpos($cmrec->availability, '"' . $oldproductid . '"') !== false) {
                                            $newavail = str_replace('"' . $oldproductid . '"', '"' . $newproductid . '"', $cmrec->availability);
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
                                                $newcontent = preg_replace_callback($cmidpattern, $cmidreplacement, $record->content);
                                                if ($newcontent !== $record->content) {
                                                    $record->content = $newcontent;
                                                    $updated = true;
                                                }
                                            }

                                            if ($updated) {
                                                $DB->update_record($modname, $record);
                                            }
                                        }
                                    } // end if table exists
                                } // end foreach modnames
                            } // end if newcmid
                    } // end if count parts
                } // end foreach items
                if (!empty($newcatalogparts)) {
                    $newcatalog = implode(',', $newcatalogparts);
                    set_config('catalog_course_' . $newcourseid, $newcatalog, 'local_xpstore');
                }
            }
        }
    }
}
