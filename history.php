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
 * history.php - Historial del Estudiante
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

global $USER, $PAGE, $OUTPUT, $DB;

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);


$url = new moodle_url('/local/xpstore/history.php', ['id' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('history', 'local_xpstore'));

echo $OUTPUT->header();

// Load module info to extract real URLs.
$modinfo = get_fast_modinfo($courseid);

// Security logic for the return button.
$showinmenu = get_config('local_xpstore', 'showinmenu_' . $courseid);
$isteacher = has_capability('moodle/course:update', $context) || is_siteadmin();

// Show the button if the store is visible to everyone, or if the user is a teacher.
$showbackbutton = ($showinmenu !== '0' || $isteacher);
$templatedata = [
    'str_history' => get_string('history', 'local_xpstore'),
    'showbackbutton' => $showbackbutton,
    'storeurl' => (new moodle_url('/local/xpstore/index.php', ['id' => $courseid]))->out(false),
    'str_tiendaxp' => get_string('tiendaxp', 'local_xpstore'),
    'str_activity' => get_string('activity', 'local_xpstore'),
    'str_type' => get_string('type', 'local_xpstore'),
    'str_cost' => get_string('cost', 'local_xpstore'),
    'str_date' => get_string('date', 'local_xpstore'),
    'str_action' => get_string('action', 'local_xpstore'),
    'has_logs' => false,
    'logs' => [],
    'str_nopurchases' => get_string('nopurchases', 'local_xpstore'),
    'str_gotogradebook' => get_string('gotogradebook', 'local_xpstore'),
    'str_gotoactivity' => get_string('gotoactivity', 'local_xpstore'),
];

// Read catalog to fetch custom labels and applied values (valornota).
$configraw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
$itemsraw = array_filter(explode(',', $configraw));
$mapcustomlabels = [];
$mapvalores = [];

foreach ($itemsraw as $item) {
    $tipochar = strtoupper(substr($item, 0, 1));
    $rest = substr($item, 1);
    $parts = explode(':', $rest);
    if (count($parts) >= 2) {
        $cid = $parts[0] ?? '';
        $customname = $parts[2] ?? '';
        $valornota = $parts[3] ?? '0';
        if ($customname !== '') {
            $mapcustomlabels[$tipochar][$cid] = $customname;
        }
        if ($valornota !== '0') {
            $mapvalores[$tipochar][$cid] = $valornota;
        }
    }
}

$sql = "SELECT g.*, cm.module, cm.instance, gi.itemname as gi_itemname
        FROM {local_xpstore_gastos} g
        LEFT JOIN {course_modules} cm ON cm.id = g.itemid AND g.itemtype != 'M'
        LEFT JOIN {grade_items} gi ON gi.id = g.itemid AND g.itemtype = 'M'
        WHERE g.userid = ?
        AND (cm.course = ? OR gi.courseid = ?)
        ORDER BY g.timecreated DESC";

$logs = $DB->get_records_sql($sql, [$USER->id, $courseid, $courseid]);

if ($logs) {
    $templatedata['has_logs'] = true;
    $totalgastado = 0;

    foreach ($logs as $log) {
        $totalgastado += $log->amount;
        if ($log->itemtype === 'M') {
            $activityname = $log->gi_itemname;
        } else {
            $modname = $DB->get_field('modules', 'name', ['id' => $log->module]);
            $activityname = $DB->get_field($modname, 'name', ['id' => $log->instance]);
        }

        $tipostr = strtolower($log->itemtype);

        $labeltipo = get_string_manager()->string_exists('type_' . $tipostr, 'local_xpstore')
            ? get_string('type_' . $tipostr, 'local_xpstore')
            : 'Legacy';

        if ($log->itemtype === 'M') {
            $labeltipo = get_string('type_g', 'local_xpstore');
        }

        $isgrade = ($log->itemtype === 'G' || $log->itemtype === 'M');
        $gradeurl = '';
        $cmurl = '';

        if ($isgrade) {
            $gradeurl = (new moodle_url('/grade/report/user/index.php', ['id' => $courseid]))->out(false);
        } else {
            $cmurl = (isset($modinfo->cms[$log->itemid]) && $modinfo->cms[$log->itemid]->url)
                ? $modinfo->cms[$log->itemid]->url->out(false)
                : (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false);
        }

        $tipocharupper = strtoupper($tipostr);
        $customlabel = isset($mapcustomlabels[$tipocharupper][$log->itemid])
            ? $mapcustomlabels[$tipocharupper][$log->itemid]
            : '';

        $valornota = isset($mapvalores[$tipocharupper][$log->itemid])
            ? $mapvalores[$tipocharupper][$log->itemid]
            : '0';

        if (($tipocharupper === 'G' || $tipocharupper === 'M') && $valornota !== '0') {
            $valnum = floatval($valornota);
            $suffix = ' (+' . $valnum . ' pts)';
            $customlabel = $customlabel ? $customlabel . $suffix : $suffix;
        }

        $templatedata['logs'][] = [
            'activityname' => htmlspecialchars($activityname),
            'customlabel' => htmlspecialchars($customlabel),
            'tipostr' => ($log->itemtype === 'M') ? 'g' : $tipostr,
            'labeltipo' => $labeltipo,
            'amount' => $log->amount,
            'date' => userdate($log->timecreated, get_string('strftimedatetime', 'langconfig')),
            'is_grade' => $isgrade,
            'gradeurl' => $gradeurl,
            'cmurl' => $cmurl,
        ];
    }
    $templatedata['str_totalspent'] = get_string('totalspent', 'local_xpstore', $totalgastado);
    $saldodisponible = local_xpstore_get_balance($USER->id, $courseid);
    $templatedata['str_remainingbalance'] = get_string('remainingbalance', 'local_xpstore', $saldodisponible);
}

echo $OUTPUT->render_from_template('local_xpstore/history_page', $templatedata);
echo $OUTPUT->footer();
