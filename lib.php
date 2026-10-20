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
             LEFT JOIN {course_modules} cm ON g.itemid = cm.id AND g.itemtype != 'M'
             LEFT JOIN {grade_items} gi ON g.itemid = gi.id AND g.itemtype = 'M'
             WHERE g.userid = ? AND (cm.course = ? OR gi.courseid = ?)";
    $expenses = $DB->get_field_sql($sqlg, [$userid, $courseid, $courseid]) ?: 0;

    return max(0, $totalxp - (int)$expenses);
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
            // Fallback to direct DB lookup below.
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
        if ($type !== 'M') {
            $cm = $DB->get_record('course_modules', ['id' => $cmid], '*', MUST_EXIST);
        }
        $deadline = time() + (3 * 24 * 60 * 60);

        // If course ID was not sent, we try to get it from the Course Module.
        if (!$courseid && $cm) {
            $courseid = $cm->course;
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

            $flag = $DB->get_record('assign_user_flags', ['assignid' => $assign->id, 'userid' => $userid]);

            if ($flag) {
                $flag->extensionduedate = $deadline;
                $flag->locked = 0;
                $DB->update_record('assign_user_flags', $flag);
            } else {
                $newflag = (object)[
                    'assignid' => $assign->id,
                    'userid' => $userid,
                    'extensionduedate' => $deadline,
                    'locked' => 0,
                    'mailed' => 0,
                ];
                $DB->insert_record('assign_user_flags', $newflag);
            }
        } else if ($type === 'G') {
            $itemmodule = $DB->get_field('modules', 'name', ['id' => $cm->module]);
            $gradeitem = $DB->get_record('grade_items', [
                'itemtype' => 'mod',
                'itemmodule' => $itemmodule,
                'iteminstance' => $cm->instance,
                'courseid' => $cm->course,
            ]);

            if ($gradeitem) {
                $grade = $DB->get_record('grade_grades', ['itemid' => $gradeitem->id, 'userid' => $userid]);

                if ($grade) {
                    $newvalue = $grade->finalgrade + $gradevalue;
                    $grade->finalgrade = ($newvalue > $gradeitem->grademax) ? $gradeitem->grademax : $newvalue;
                    $grade->overridden = time();
                    $DB->update_record('grade_grades', $grade);
                } else {
                    $newgrade = (object)[
                        'itemid' => $gradeitem->id,
                        'userid' => $userid,
                        'rawgrade' => $gradevalue,
                        'finalgrade' => $gradevalue,
                        'overridden' => time(),
                        'timecreated' => time(),
                        'timemodified' => time(),
                    ];
                    $DB->insert_record('grade_grades', $newgrade);
                }
            }
        } else if ($type === 'M') {
            $gradeitem = $DB->get_record('grade_items', ['id' => $cmid]);

            if ($gradeitem) {
                $grade = $DB->get_record('grade_grades', ['itemid' => $gradeitem->id, 'userid' => $userid]);

                if ($grade) {
                    $newvalue = $grade->finalgrade + $gradevalue;
                    $grade->finalgrade = ($newvalue > $gradeitem->grademax) ? $gradeitem->grademax : $newvalue;
                    $grade->overridden = time();
                    $DB->update_record('grade_grades', $grade);
                } else {
                    $newgrade = (object)[
                        'itemid' => $gradeitem->id,
                        'userid' => $userid,
                        'rawgrade' => $gradevalue,
                        'finalgrade' => $gradevalue,
                        'overridden' => time(),
                        'timecreated' => time(),
                        'timemodified' => time(),
                    ];
                    $DB->insert_record('grade_grades', $newgrade);
                }
            }
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
 * Reset userdata for the store.
 *
 * @param stdClass $data The data submitted.
 * @return array Status array.
 */
function local_xpstore_reset_userdata($data) {
    global $DB;
    if (!empty($data->reset_tiendagamificada_gastos)) {
        $sql = "SELECT g.id FROM {local_xpstore_gastos} g
                LEFT JOIN {course_modules} cm ON g.itemid = cm.id AND g.itemtype != 'M'
                LEFT JOIN {grade_items} gi ON g.itemid = gi.id AND g.itemtype = 'M'
                WHERE cm.course = ? OR gi.courseid = ?";
        $gastos = $DB->get_fieldset_sql($sql, [$data->courseid, $data->courseid]);
        if (!empty($gastos)) {
            $DB->delete_records_list('local_xpstore_gastos', 'id', $gastos);
        }
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

    if ($uploadedfile['error'] !== UPLOAD_ERR_OK || empty($uploadedfile['tmp_name']) || !is_uploaded_file($uploadedfile['tmp_name'])) {
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
