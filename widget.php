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
 * widget.php - Embedded individual product widget.
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

global $USER, $PAGE, $OUTPUT, $DB;

$courseid = required_param('id', PARAM_INT);
$cmidreq = required_param('cmid', PARAM_INT);
$tiporeq = required_param('tipo', PARAM_ALPHA);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);
require_login($course);


$urlparams = [
    'id' => $courseid,
    'cmid' => $cmidreq,
    'tipo' => $tiporeq,
];
$url = new moodle_url('/local/xpstore/widget.php', $urlparams);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('embedded');

$productosraw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
$items = array_filter(explode(',', $productosraw));
$producto = null;

foreach ($items as $item) {
    $tipochar = substr($item, 0, 1);
    $rest = substr($item, 1);
    $parts = explode(':', $rest);

    if (count($parts) >= 2 && (int)$parts[0] === $cmidreq && $tipochar === $tiporeq) {
        $cm = $DB->get_record('course_modules', ['id' => (int)$parts[0], 'course' => $courseid]);
        if ($cm) {
            $modname = $DB->get_field('modules', 'name', ['id' => $cm->module]);
            $producto = [
                'tipo' => $tipochar,
                'cid' => (int)$parts[0],
                'costo' => (int)($parts[1] ?? 0),
                'n_custom' => $parts[2] ?? '',
                'boost' => $parts[3] ?? '0',
                'cat' => isset($parts[4]) && trim($parts[4]) !== ''
                    ? trim($parts[4])
                    : get_string('defaultcategory', 'local_xpstore'),
                'limite' => (int)($parts[5] ?? 0),
                'n_real' => $DB->get_field($modname, 'name', ['id' => $cm->instance]),
            ];
        }
        break;
    }
}

if (!$producto) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('widgeterror', 'local_xpstore'), 'error');
    echo $OUTPUT->footer();
    die();
}

$comprasparams = [
    'userid' => $USER->id,
    'itemid' => $producto['cid'],
    'itemtype' => $producto['tipo'],
];
$comprasactuales = $DB->count_records('local_xpstore_gastos', $comprasparams);
$limitealcanzado = ($producto['limite'] > 0 && $comprasactuales >= $producto['limite']);

$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'comprar' && confirm_sesskey()) {
    if ($limitealcanzado) {
        redirect(new moodle_url($url));
    } else {
        if (local_xpstore_purchase($USER->id, $tiporeq, $cmidreq, $producto['costo'], $courseid)) {
            local_xpstore_deliver_product($USER->id, $cmidreq, $tiporeq, $courseid);
            redirect(new moodle_url($url, ['status' => 'success']));
        } else {
            redirect(new moodle_url($url, ['status' => 'error']));
        }
    }
}

$status = optional_param('status', '', PARAM_ALPHA);
$saldo = local_xpstore_get_balance($USER->id, $courseid);

echo $OUTPUT->header();

$iconmap = [
    'Q' => 'bolt',
    'A' => 'file-text',
    'F' => 'comments',
    'G' => 'star',
    'S' => 'unlock-alt',
];
$icon = $iconmap[$producto['tipo']] ?? 'gift';
$successicon = $icon;

$caticonsraw = get_config('local_xpstore', 'cat_icons_course_' . $courseid);
if ($caticonsraw) {
    $caticons = json_decode($caticonsraw, true);
    if (isset($producto['cat']) && isset($caticons[$producto['cat']])) {
        $successicon = $caticons[$producto['cat']];
    }
}

$cpstore = get_config('local_xpstore', 'color_primary_course_' . $courseid) ?: '#0056D2';
$cbstore = get_config('local_xpstore', 'color_secondary_course_' . $courseid) ?: '#00C9A7';
$cistore = get_config('local_xpstore', 'color_icon_course_' . $courseid) ?: '#ff9800';

$isbonus = ($producto['tipo'] == 'G' && $producto['boost'] != '0');
$isspecial = ($producto['tipo'] == 'S');
$statussuccess = ($status === 'success');

$desturl = null;
$strsuccessunlock = '';
if ($statussuccess) {
    $dest = ($producto['tipo'] == 'G') ? '/grade/report/user/index.php' : '/course/view.php';
    $destmoodleurl = new moodle_url($dest, ['id' => $courseid]);

    $activityname = '';
    $modinfo = get_fast_modinfo($courseid);
    if ($producto['tipo'] != 'G') {
        $destmoodleurl = $modinfo->cms[$producto['cid']]->url ?? $destmoodleurl;
    }
    if (isset($modinfo->cms[$producto['cid']])) {
        $activityname = $modinfo->cms[$producto['cid']]->name;
    }
    $desturl = $destmoodleurl->out(false);

    $a = new stdClass();
    $a->reward = $producto['n_custom'] ?: $producto['n_real'];
    if (empty($a->reward)) {
        $a->reward = get_string('points', 'local_xpstore');
    }
    $a->activity = $activityname ?: get_string('course', 'local_xpstore');

    if ($producto['tipo'] == 'G') {
        $strsuccessunlock = get_string('success_unlock_gradebook', 'local_xpstore', $a);
    } else {
        $strsuccessunlock = get_string('success_unlock_reward', 'local_xpstore', $a);
    }
}

$disabled = ($saldo < $producto['costo']);
$btntext = $disabled ? get_string('insuficiente', 'local_xpstore') : get_string('canjear', 'local_xpstore');

$templatedata = [
    'cpstore' => $cpstore,
    'cbstore' => $cbstore,
    'cistore' => $cistore,
    'saldo' => $saldo,
    'status_success' => $statussuccess,
    'icon' => $icon,
    'is_bonus' => $isbonus,
    'boost' => $producto['boost'],
    'is_special' => $isspecial,
    'displayname' => $producto['n_custom'] ?: $producto['n_real'],
    'n_real' => $producto['n_real'],
    'desturl' => $desturl,
    'costo' => $producto['costo'],
    'has_limit' => ($producto['limite'] > 0),
    'comprasactuales' => $comprasactuales,
    'limite' => $producto['limite'],
    'actionurl' => $url->out(false),
    'sesskey' => sesskey(),
    'limitealcanzado' => $limitealcanzado,
    'disabled' => $disabled,
    'btntext' => $btntext,
    'str_balance' => get_string('balance', 'local_xpstore'),
    'str_widgetunlocked' => get_string('widgetunlocked', 'local_xpstore'),
    'str_widgetunlockeddesc' => get_string('widgetunlockeddesc', 'local_xpstore'),
    'str_points' => get_string('points', 'local_xpstore'),
    'str_specialcontent' => get_string('specialcontent', 'local_xpstore'),
    'str_goto_dest' => ($producto['tipo'] == 'G') ?
        get_string('gotogradebook', 'local_xpstore') :
        get_string('gotoactivity', 'local_xpstore'),
    'success_icon' => $successicon,
    'str_success_unlock' => $strsuccessunlock,
    'str_congratulations' => get_string('congratulations', 'local_xpstore'),
    'str_redemptions_count' => get_string('redemptions_count', 'local_xpstore'),
    'str_soldout' => get_string('soldout', 'local_xpstore'),
];

echo $OUTPUT->render_from_template('local_xpstore/widget', $templatedata);

echo $OUTPUT->footer();
