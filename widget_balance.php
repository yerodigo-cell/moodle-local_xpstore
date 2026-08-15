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
 * widget_balance.php - Embedded widget to display only balance.
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

global $PAGE, $OUTPUT, $DB, $USER;

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);
require_login($course);

$url = new moodle_url('/local/xpstore/widget_balance.php', ['id' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($context);

// Embedded mode so Moodle menus are not loaded.
$PAGE->set_pagelayout('embedded');

echo $OUTPUT->header();

// Query custom colors per course.
$cpstore = get_config('local_xpstore', 'color_primary_course_' . $courseid) ?: '#0056D2';
$cbstore = get_config('local_xpstore', 'color_secondary_course_' . $courseid) ?: '#00C9A7';

$saldo = local_xpstore_get_balance($USER->id, $courseid);

$templatedata = [
    'cpstore' => $cpstore,
    'cbstore' => $cbstore,
    'saldo' => $saldo,
    'str_balance' => get_string('balance', 'local_xpstore'),
];

echo $OUTPUT->render_from_template('local_xpstore/widget_balance', $templatedata);

echo $OUTPUT->footer();
