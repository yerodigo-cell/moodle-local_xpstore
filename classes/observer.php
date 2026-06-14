<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_xpstore;

defined('MOODLE_INTERNAL') || die();

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
     * @param \core\event\course_restored $event
     */
    public static function course_restored(\core\event\course_restored $event) {
        global $DB;

        $newcourseid = $event->objectid;

        // The regex looks for: /local/xpstore/widget*.php?id=OLD_ID
        // and replaces OLD_ID with the $newcourseid. We use capture groups to find the old ID.
        $pattern = '/(\/local\/xpstore\/[^"\'?]+\.php\?id=)(\d+)/';
        $replacement = '${1}' . $newcourseid;

        $oldcourseid = null;

        // Fix labels and find old course id
        if ($DB->get_manager()->table_exists('label')) {
            $labels = $DB->get_records('label', ['course' => $newcourseid]);
            foreach ($labels as $label) {
                if (strpos($label->intro, '/local/xpstore/') !== false) {
                    if (!$oldcourseid && preg_match($pattern, $label->intro, $matches)) {
                        $oldcourseid = (int)$matches[2];
                    }
                    $newintro = preg_replace($pattern, $replacement, $label->intro);
                    if ($newintro !== $label->intro) {
                        $DB->set_field('label', 'intro', $newintro, ['id' => $label->id]);
                    }
                }
            }
        }

        // Fix pages and find old course id if not found yet
        if ($DB->get_manager()->table_exists('page')) {
            $pages = $DB->get_records('page', ['course' => $newcourseid]);
            foreach ($pages as $page) {
                if (strpos($page->intro, '/local/xpstore/') !== false || strpos($page->content, '/local/xpstore/') !== false) {
                    if (!$oldcourseid && preg_match($pattern, $page->intro, $matches)) {
                        $oldcourseid = (int)$matches[2];
                    }
                    if (!$oldcourseid && preg_match($pattern, $page->content, $matches)) {
                        $oldcourseid = (int)$matches[2];
                    }
                    
                    $newintro = preg_replace($pattern, $replacement, $page->intro);
                    $newcontent = preg_replace($pattern, $replacement, $page->content);
                    if ($newintro !== $page->intro || $newcontent !== $page->content) {
                        $DB->set_field('page', 'intro', $newintro, ['id' => $page->id]);
                        $DB->set_field('page', 'content', $newcontent, ['id' => $page->id]);
                    }
                }
            }
        }

        // Fallback to Moodle event data if we still don't have the old course id
        if (!$oldcourseid && isset($event->other['originalcourseid'])) {
            $oldcourseid = $event->other['originalcourseid'];
        }

        // Copy store configuration and map CMIDs
        if ($oldcourseid && $oldcourseid != $newcourseid) {
            // 1. Copy simple string configs (colors, icons)
            $keys = [
                'color_primary',
                'color_secondary',
                'color_icon',
                'color_cat_icon',
                'cat_icons'
            ];
            foreach ($keys as $key) {
                $oldval = get_config('local_xpstore', $key . '_course_' . $oldcourseid);
                if ($oldval !== false) {
                    set_config($key . '_course_' . $newcourseid, $oldval, 'local_xpstore');
                }
            }

            // 2. Copy and map the catalog string
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
                        
                        // Find the old cm's name and modname
                        if (isset($oldmodinfo->cms[$oldcmid])) {
                            $oldcm = $oldmodinfo->cms[$oldcmid];
                            $oldname = $oldcm->name;
                            $oldmodname = $oldcm->modname;

                            // Search for the corresponding cm in the new course
                            $newcmid = null;
                            foreach ($newmodinfo->get_cms() as $newcm) {
                                if ($newcm->name === $oldname && $newcm->modname === $oldmodname) {
                                    $newcmid = $newcm->id;
                                    break;
                                }
                            }

                            // If we found it, update the ID in the catalog string and in the widget URLs
                            if ($newcmid) {
                                $parts[0] = $newcmid;
                                $newcatalogparts[] = $tipochar . implode(':', $parts);

                                // Replace cmid=OLD in all labels/pages so individual widgets keep working
                                $cmid_pattern = '/([?&]|&amp;)cmid=' . $oldcmid . '([&"\']|&amp;)/';
                                $cmid_replacement = '${1}cmid=' . $newcmid . '${2}';

                                if ($DB->get_manager()->table_exists('label')) {
                                    $all_labels = $DB->get_records('label', ['course' => $newcourseid]);
                                    foreach ($all_labels as $l) {
                                        if (strpos($l->intro, 'cmid=' . $oldcmid) !== false) {
                                            $newl = preg_replace($cmid_pattern, $cmid_replacement, $l->intro);
                                            if ($newl !== $l->intro) {
                                                $DB->set_field('label', 'intro', $newl, ['id' => $l->id]);
                                            }
                                        }
                                    }
                                }

                                if ($DB->get_manager()->table_exists('page')) {
                                    $all_pages = $DB->get_records('page', ['course' => $newcourseid]);
                                    foreach ($all_pages as $p) {
                                        if (strpos($p->intro, 'cmid=' . $oldcmid) !== false) {
                                            $newintro = preg_replace($cmid_pattern, $cmid_replacement, $p->intro);
                                            if ($newintro !== $p->intro) {
                                                $DB->set_field('page', 'intro', $newintro, ['id' => $p->id]);
                                            }
                                        }
                                        if (strpos($p->content, 'cmid=' . $oldcmid) !== false) {
                                            $newcontent = preg_replace($cmid_pattern, $cmid_replacement, $p->content);
                                            if ($newcontent !== $p->content) {
                                                $DB->set_field('page', 'content', $newcontent, ['id' => $p->id]);
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
