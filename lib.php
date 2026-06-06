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
 * @copyright   2026 Yeison Díaz
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

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
        'parentcontextid' => $context->id
    ]);

    // If Level Up! block is not active in this course, do not show the menu to anyone.
    if (!$hasxp) {
        return;
    }

    // 2. Check the specific visibility configuration for THIS COURSE.
    $showinmenu = get_config('local_xpstore', 'show_menu_course_' . $course->id);
    
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

    // Audit button exclusively for teachers.
    if (has_capability('moodle/course:update', $context)) {
        $reporturl = new moodle_url('/local/xpstore/report.php', ['id' => $course->id]);
        $parentnode->add_node(navigation_node::create(
            get_string('audit', 'local_xpstore'), 
            $reporturl, 
            navigation_node::TYPE_SETTING, 
            null, 
            'tiendareport', 
            new pix_icon('i/report', '')
        ));
    }
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
             JOIN {course_modules} cm ON g.itemid = cm.id 
             WHERE g.userid = ? AND cm.course = ?";
    $expenses = $DB->get_field_sql($sqlg, [$userid, $courseid]) ?: 0;
    
    return max(0, $totalxp - (int)$expenses);
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
    global $DB;
    
    try {
        $cm = $DB->get_record('course_modules', ['id' => $cmid], '*', MUST_EXIST);
        $deadline = time() + (24 * 60 * 60);

        // If course ID was not sent, we try to get it from the Course Module.
        if (!$courseid) {
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
                    'timeclose' => $deadline
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
                    'cutoffdate' => $deadline
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
                    'mailed' => 0
                ];
                $DB->insert_record('assign_user_flags', $newflag);
            }
        } else if ($type === 'G') {
            $itemmodule = $DB->get_field('modules', 'name', ['id' => $cm->module]);
            $gradeitem = $DB->get_record('grade_items', [
                'itemtype' => 'mod', 
                'itemmodule' => $itemmodule, 
                'iteminstance' => $cm->instance, 
                'courseid' => $cm->course
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
                        'timemodified' => time()
                    ];
                    $DB->insert_record('grade_grades', $newgrade);
                }

            }
        } else if ($type === 'S') {
            $group = $DB->get_record('groups', ['courseid' => $cm->course, 'name' => $rewardname]);
            
            if (!$group) {
                $newgroup = (object)[
                    'courseid' => $cm->course, 
                    'name' => $rewardname, 
                    'timecreated' => time(), 
                    'timemodified' => time()
                ];
                $groupid = $DB->insert_record('groups', $newgroup);
            } else { 
                $groupid = $group->id; 
            }
            
            if (!$DB->record_exists('groups_members', ['groupid' => $groupid, 'userid' => $userid])) {
                $newmember = (object)[
                    'groupid' => $groupid, 
                    'userid' => $userid, 
                    'timeadded' => time()
                ];
                $DB->insert_record('groups_members', $newmember);
            }
        }

        rebuild_course_cache($cm->course, true);
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
        $sql = "DELETE g FROM {local_xpstore_gastos} g 
                JOIN {course_modules} cm ON g.itemid = cm.id 
                WHERE cm.course = ?";
        $DB->execute($sql, [$data->courseid]);
    }
    return [];
}