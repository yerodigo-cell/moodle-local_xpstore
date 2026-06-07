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

/**
 * widget_history.php - Embedded button to navigate to history.
 */
require_once(__DIR__ . '/../../config.php');

global $PAGE, $OUTPUT, $DB;

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);
require_login($course);


$url = new moodle_url('/local/xpstore/widget_history.php', ['id' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($context);

// Embedded mode so Moodle menus are not loaded.
$PAGE->set_pagelayout('embedded');

echo $OUTPUT->header();

// Target URL: The real history page.
$historyurl = new moodle_url('/local/xpstore/history.php', ['id' => $courseid]);

// Query custom colors per course.
$cpstore = get_config('local_xpstore', 'color_primary_course_' . $courseid) ?: '#0056D2';
$cbstore = get_config('local_xpstore', 'color_secondary_course_' . $courseid) ?: '#00C9A7';

$templatedata = [
    'historyurl' => $historyurl->out(false),
    'cpstore' => $cpstore,
    'cbstore' => $cbstore,
    'str_history' => get_string('history', 'local_xpstore')
];

echo $OUTPUT->render_from_template('local_xpstore/widget_history', $templatedata);

echo $OUTPUT->footer();
