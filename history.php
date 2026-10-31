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

// Show return button (navigates back to previous page or widget host).
$showinmenu = get_config('local_xpstore', 'show_menu_course_' . $courseid);
$isteacher = local_xpstore_can_manage($context);
$showbackbutton = true;

$templatedata = [
    'str_history' => get_string('history', 'local_xpstore'),
    'showbackbutton' => $showbackbutton,
    'storeurl' => ($showinmenu === '1' || $isteacher)
        ? (new moodle_url('/local/xpstore/index.php', ['id' => $courseid]))->out(false)
        : (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false),
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

// Read parsed catalog to fetch fallback custom labels and applied values.
$catalogmap = local_xpstore_parse_catalog($courseid, true);

$sql = "SELECT g.*, cm.module, cm.instance, gi.itemname AS gi_itemname
          FROM {local_xpstore_gastos} g
     LEFT JOIN {course_modules} cm ON cm.id = g.itemid AND g.itemtype NOT IN ('M', 'C')
     LEFT JOIN {grade_items} gi ON gi.id = g.itemid AND g.itemtype IN ('M', 'C')
         WHERE g.userid = ?
           AND (g.courseid = ? OR (g.courseid = 0 AND (cm.course = ? OR gi.courseid = ?)))
      ORDER BY g.timecreated DESC, g.id DESC";

$logs = $DB->get_records_sql($sql, [$USER->id, $courseid, $courseid, $courseid]);

if ($logs) {
    $templatedata['has_logs'] = true;
    $totalgastado = 0;

    foreach ($logs as $log) {
        $amount = max(0, (int)$log->amount);
        $totalgastado += $amount;

        if ($log->itemtype === 'M') {
            $activityname = !empty($log->gi_itemname)
                ? $log->gi_itemname
                : get_string('activitydeleted', 'local_xpstore');
        } else if ($log->itemtype === 'C') {
            if ($DB->record_exists('grade_items', ['id' => $log->itemid])) {
                $activityname = local_xpstore_get_course_grade_item_name($log->itemid);
            } else {
                $activityname = get_string('activitydeleted', 'local_xpstore');
            }
        } else {
            $activityname = '';
            if (!empty($log->module)) {
                $modname = $DB->get_field('modules', 'name', ['id' => $log->module]);
                if (!empty($modname) && $DB->get_manager()->table_exists($modname)) {
                    $instname = $DB->get_field($modname, 'name', ['id' => $log->instance]);
                    if ($instname !== false && $instname !== '') {
                        $activityname = $instname;
                    }
                }
            }
            if ($activityname === '') {
                $activityname = get_string('activitydeleted', 'local_xpstore');
            }
        }

        $tipostr = strtolower($log->itemtype);
        $tipocharupper = strtoupper($tipostr);
        $itemkey = $tipocharupper . (int)$log->itemid;

        $studentlabel = local_xpstore_get_student_badge_label($tipocharupper);
        $labeltipo = ($studentlabel !== '') ? $studentlabel : 'Legacy';

        $badgeclass = $tipostr;
        if (in_array($tipocharupper, ['M', 'C'], true)) {
            $badgeclass = 'g';
        } else if ($tipocharupper === 'S') {
            $badgeclass = 'u';
        } else if ($tipocharupper === 'F') {
            $badgeclass = 'a';
        }

        $isgrade = in_array($tipocharupper, ['G', 'M', 'C'], true);
        $gradeurl = '';
        $cmurl = '';

        if ($isgrade) {
            $gradeurl = (new moodle_url('/grade/report/user/index.php', ['id' => $courseid]))->out(false);
        } else {
            $cmurl = (isset($modinfo->cms[$log->itemid]) && $modinfo->cms[$log->itemid]->url)
                ? $modinfo->cms[$log->itemid]->url->out(false)
                : (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false);
        }

        $catentry = $catalogmap[$itemkey] ?? null;
        $customlabel = '';
        if (isset($log->rewardname) && trim((string)$log->rewardname) !== '') {
            $customlabel = trim((string)$log->rewardname);
        } else if ($catentry && $catentry['nombre'] !== '') {
            $customlabel = $catentry['nombre'];
        }

        $valnum = 0.0;
        if (isset($log->gradevalue) && $log->gradevalue !== null && (float)$log->gradevalue > 0) {
            $valnum = (float)$log->gradevalue;
        } else if ($catentry && (float)$catentry['valornota'] > 0) {
            $valnum = (float)$catentry['valornota'];
        }

        if ($isgrade && $valnum > 0) {
            $suffix = ' (+' . $valnum . ' pts)';
            $customlabel = ($customlabel !== '') ? ($customlabel . $suffix) : trim($suffix);
        }

        $cleanactivity = html_entity_decode(
            format_string($activityname, true, ['context' => $context]),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        $cleancustomlabel = ($customlabel !== '') ? html_entity_decode(
            format_string($customlabel, true, ['context' => $context]),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        ) : '';

        $templatedata['logs'][] = [
            'activityname' => $cleanactivity,
            'customlabel' => $cleancustomlabel,
            'tipostr' => $badgeclass,
            'labeltipo' => $labeltipo,
            'amount' => $amount,
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
