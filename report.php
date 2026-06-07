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
 * Report.php
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('moodle/course:update', $context);

$url = new moodle_url('/local/xpstore/report.php', ['id' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('audit', 'local_xpstore'));

// Logic to reset the cycle.
$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'reset' && confirm_sesskey()) {
    $resetsql = "DELETE g FROM {local_xpstore_gastos} g
                 JOIN {course_modules} cm ON g.itemid = cm.id
                 WHERE cm.course = ?";
    $DB->execute($resetsql, [$courseid]);
    redirect($url, get_string('productdeleted', 'local_xpstore'));
}

// Pre-load store categories to assign them to records.
$configraw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
$itemsraw = array_filter(explode(',', $configraw));
$mapcategorias = [];

foreach ($itemsraw as $item) {
    $tipochar = strtoupper(substr($item, 0, 1));
    $rest = substr($item, 1);
    $parts = explode(':', $rest);
    if (count($parts) >= 2) {
        $cid = $parts[0] ?? '';
        $cat = !empty($parts[4]) ? trim($parts[4]) : get_string('defaultcategory', 'local_xpstore');
        $mapcategorias[$tipochar][$cid] = $cat;
    }
}

// Load course module info for links.
$modinfo = get_fast_modinfo($courseid);

echo $OUTPUT->header();

$templatedata = [
    'str_reporttitle' => get_string('reporttitle', 'local_xpstore'),
    'str_reportsubtitle' => get_string('reportsubtitle', 'local_xpstore'),
    'storeurl' => (new moodle_url('/local/xpstore/index.php', ['id' => $courseid]))->out(false),
    'str_tiendaxp' => get_string('tiendaxp', 'local_xpstore'),
    'reseturl' => (new moodle_url($url, ['action' => 'reset', 'sesskey' => sesskey()]))->out(false),
    'str_confirmreset' => get_string('confirmreset', 'local_xpstore'),
    'str_resetcycle' => get_string('resetcycle', 'local_xpstore'),
    'str_activity' => get_string('activity', 'local_xpstore'),
    'str_colcategory' => get_string('colcategory', 'local_xpstore'),
    'str_type' => get_string('type', 'local_xpstore'),
    'str_cost' => get_string('cost', 'local_xpstore'),
    'str_date' => get_string('date', 'local_xpstore'),
    'str_nopurchases' => get_string('nopurchases', 'local_xpstore'),
    'str_redemptions' => get_string('redemptions', 'local_xpstore'),
    'has_users' => false,
    'users' => [],
];

$sql = "SELECT g.*, u.firstname, u.lastname, u.picture, u.imagealt, u.email
        FROM {local_xpstore_gastos} g
        JOIN {user} u ON g.userid = u.id
        JOIN {course_modules} cm ON g.itemid = cm.id
        WHERE cm.course = ?
        ORDER BY u.firstname ASC, g.timecreated DESC";

$logs = $DB->get_records_sql($sql, [$courseid]);
$grouped = [];

if ($logs) {
    $templatedata['has_users'] = true;
    foreach ($logs as $log) {
        $grouped[$log->userid][] = $log;
    }

    foreach ($grouped as $userid => $userlogs) {
        $realuser = core_user::get_user($userid);
        $userpichtml = $OUTPUT->user_picture($realuser, ['size' => 50, 'link' => false]);
        $userfullname = $realuser->firstname . ' ' . $realuser->lastname;
        $totalcanjes = count($userlogs);
        $profileurl = (new moodle_url('/user/view.php', ['id' => $userid, 'course' => $courseid]))->out(false);

        $userdata = [
            'userid' => $userid,
            'userpichtml' => $userpichtml,
            'userfullname' => $userfullname,
            'useremail' => $realuser->email,
            'totalcanjes' => $totalcanjes,
            'profileurl' => $profileurl,
            'logs' => [],
        ];

        foreach ($userlogs as $log) {
            $cm = $DB->get_record('course_modules', ['id' => $log->itemid]);

            if ($cm) {
                $modname = $DB->get_field('modules', 'name', ['id' => $cm->module]);
                $activityname = $DB->get_field($modname, 'name', ['id' => $cm->instance]);
            } else {
                $activityname = 'Actividad / Recurso eliminado';
            }

            $tipostr = strtolower($log->itemtype);
            $tipocharupper = strtoupper($tipostr);

            $labeltipo = get_string_manager()->string_exists('type_' . $tipostr, 'local_xpstore')
                ? get_string('type_' . $tipostr, 'local_xpstore')
                : 'Legacy';

            $categoriatexto = isset($mapcategorias[$tipocharupper][$log->itemid])
                ? $mapcategorias[$tipocharupper][$log->itemid]
                : '-';

            $cmurl = '';
            if ($tipostr === 'g') {
                $cmurl = (new moodle_url('/grade/report/user/index.php', 
                    ['id' => $courseid, 'userid' => $log->userid]))->out(false);
            } else if (isset($modinfo->cms[$log->itemid])) {
                $cmurl = $modinfo->cms[$log->itemid]->url->out(false);
            }

            $activityhtml = htmlspecialchars($activityname);
            if (!empty($cmurl) && $activityname !== 'Actividad / Recurso eliminado') {
                $activityhtml = '<a href="' . $cmurl . '" target="_blank" ' .
                                 'style="color: #0056D2; text-decoration: none; transition: 0.2s;" ' .
                                 'onmouseover="this.style.color=\'#00C9A7\'" ' .
                                 'onmouseout="this.style.color=\'#0056D2\'">' .
                                 '<i class="fa fa-external-link mr-1" style="font-size: 0.8rem;"></i>' .
                                 $activityhtml .
                                 '</a>';
            }

            $userdata['logs'][] = [
                'activityhtml' => $activityhtml,
                'categoriatexto' => htmlspecialchars($categoriatexto),
                'tipostr' => $tipostr,
                'labeltipo' => $labeltipo,
                'amount' => $log->amount,
                'date' => userdate($log->timecreated, get_string('strftimedatetime', 'langconfig')),
            ];
        }

        $templatedata['users'][] = $userdata;
    }
}

$PAGE->requires->js_call_amd('local_xpstore/report', 'init', []);

echo $OUTPUT->render_from_template('local_xpstore/report_page', $templatedata);
echo $OUTPUT->footer();
