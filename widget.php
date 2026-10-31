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
 * widget.php - Embedded individual product widget.
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

global $USER, $PAGE, $OUTPUT, $DB, $CFG;

$courseid = required_param('id', PARAM_INT);
$cmidreq = required_param('cmid', PARAM_INT);
$tiporeq = strtoupper(required_param('tipo', PARAM_ALPHA));

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

$catalog = local_xpstore_parse_catalog($courseid);
$catkey = $tiporeq . $cmidreq;
$producto = null;
$modinfo = get_fast_modinfo($courseid);
$cms = $modinfo->get_cms();

if (isset($catalog[$catkey])) {
    $catitem = $catalog[$catkey];
    $customicon = $catitem['icono'];
    if ($tiporeq === 'M') {
        $gi = $DB->get_record('grade_items', ['id' => $cmidreq, 'courseid' => $courseid, 'itemtype' => 'manual']);
        if ($gi) {
            $producto = [
                'tipo' => $tiporeq,
                'cid' => $cmidreq,
                'costo' => (int)$catitem['costo'],
                'n_custom' => $catitem['nombre'],
                'boost' => $catitem['valornota'],
                'cat' => ($catitem['categoria'] !== '')
                    ? $catitem['categoria']
                    : get_string('defaultcategory', 'local_xpstore'),
                'limite' => (int)$catitem['limite'],
                'requisito' => (int)$catitem['requisito'],
                'minlevel' => (int)$catitem['minlevel'],
                'hidebylevel' => (int)$catitem['hidebylevel'],
                'icon' => $customicon,
                'n_real' => format_string($gi->itemname),
            ];
        }
    } else if ($tiporeq === 'C') {
        $gi = $DB->get_record('grade_items', ['id' => $cmidreq, 'courseid' => $courseid, 'itemtype' => 'course']);
        if ($gi) {
            $producto = [
                'tipo' => $tiporeq,
                'cid' => $cmidreq,
                'costo' => (int)$catitem['costo'],
                'n_custom' => $catitem['nombre'],
                'boost' => $catitem['valornota'],
                'cat' => ($catitem['categoria'] !== '')
                    ? $catitem['categoria']
                    : get_string('defaultcategory', 'local_xpstore'),
                'limite' => (int)$catitem['limite'],
                'requisito' => (int)$catitem['requisito'],
                'minlevel' => (int)$catitem['minlevel'],
                'hidebylevel' => (int)$catitem['hidebylevel'],
                'icon' => $customicon,
                'n_real' => local_xpstore_get_course_grade_item_name($cmidreq),
            ];
        }
    } else if (isset($cms[$cmidreq]) && empty($cms[$cmidreq]->deletioninprogress)) {
        $cm = $cms[$cmidreq];
        $producto = [
            'tipo' => $tiporeq,
            'cid' => $cmidreq,
            'costo' => (int)$catitem['costo'],
            'n_custom' => $catitem['nombre'],
            'boost' => $catitem['valornota'],
            'cat' => ($catitem['categoria'] !== '')
                ? $catitem['categoria']
                : get_string('defaultcategory', 'local_xpstore'),
            'limite' => (int)$catitem['limite'],
            'requisito' => (int)$catitem['requisito'],
            'minlevel' => (int)$catitem['minlevel'],
            'hidebylevel' => (int)$catitem['hidebylevel'],
            'icon' => $customicon,
            'n_real' => format_string($cm->name),
        ];
    }
}

if (!$producto) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('widgeterror', 'local_xpstore'), 'error');
    echo $OUTPUT->footer();
    die();
}

$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'comprar' && confirm_sesskey()) {
    $result = local_xpstore_process_redemption($course, $USER->id, $tiporeq, $cmidreq);
    if (!empty($result['success'])) {
        redirect(new moodle_url($url, ['status' => 'success']));
    } else if (($result['code'] ?? '') === 'limit') {
        redirect(new moodle_url($url));
    } else {
        redirect(new moodle_url($url, ['status' => 'error']));
    }
}

$comprasactuales = (int)$DB->count_records_select(
    'local_xpstore_gastos',
    'userid = ? AND itemid = ? AND itemtype = ? AND (courseid = ? OR courseid = 0)',
    [$USER->id, $producto['cid'], $producto['tipo'], $courseid]
);
$limitealcanzado = ($producto['limite'] > 0 && $comprasactuales >= $producto['limite']);

$requisitocumplido = true;
$reqname = '';
if ($producto['requisito'] > 0) {
    if (isset($cms[$producto['requisito']]) && empty($cms[$producto['requisito']]->deletioninprogress)) {
        require_once($CFG->libdir . '/completionlib.php');
        $completion = new completion_info($course);
        $reqcminfo = $cms[$producto['requisito']];
        $reqname = format_string($reqcminfo->name);
        $completiondata = $completion->get_data($reqcminfo, false, $USER->id);
        $requisitocumplido = (
            $completiondata->completionstate == COMPLETION_COMPLETE ||
            $completiondata->completionstate == COMPLETION_COMPLETE_PASS
        );
    } else {
        $requisitocumplido = false;
        $reqname = get_string('activitydeleted', 'local_xpstore');
    }
}

$userlevel = local_xpstore_get_user_level($USER->id, $courseid);
$nivelcumplido = ($producto['minlevel'] <= 0 || $userlevel >= $producto['minlevel']);
$hiddenbylevel = (!$nivelcumplido && $producto['hidebylevel'] === 1);

$status = optional_param('status', '', PARAM_ALPHA);
$saldo = local_xpstore_get_balance($USER->id, $courseid);

echo $OUTPUT->header();

$defaulticon = local_xpstore_get_default_card_icon($producto['tipo']);
$icon = !empty($producto['icon']) ? local_xpstore_clean_icon($producto['icon'], $defaulticon) : $defaulticon;
$iscustomimg = local_xpstore_is_custom_card_image($icon);
$iconurl = $iscustomimg ? local_xpstore_get_card_image_url($courseid, $icon) : '';
if ($iscustomimg && !$iconurl) {
    $icon = $defaulticon;
    $iscustomimg = false;
}
$successicon = $icon;
$successiscustomimg = $iscustomimg;
$successiconurl = $iconurl;

if (empty($producto['icon'])) {
    $caticonsraw = get_config('local_xpstore', 'cat_icons_course_' . $courseid);
    if ($caticonsraw) {
        $caticons = json_decode($caticonsraw, true);
        if (isset($producto['cat']) && isset($caticons[$producto['cat']])) {
            $cleanedcat = local_xpstore_clean_icon($caticons[$producto['cat']], $icon);
            if (!local_xpstore_is_custom_card_image($cleanedcat)) {
                $successicon = $cleanedcat;
                $successiscustomimg = false;
                $successiconurl = '';
            }
        }
    }
}

$cpstore = local_xpstore_clean_hex_color(
    get_config('local_xpstore', 'color_primary_course_' . $courseid) ?: '#0056D2',
    '#0056D2'
);
$cbstore = local_xpstore_clean_hex_color(
    get_config('local_xpstore', 'color_secondary_course_' . $courseid) ?: '#00C9A7',
    '#00C9A7'
);
$cistore = local_xpstore_clean_hex_color(
    get_config('local_xpstore', 'color_icon_course_' . $courseid) ?: $cpstore,
    $cpstore
);

$isbonus = in_array($producto['tipo'], ['G', 'M', 'C'], true);
$isspecial = ($producto['tipo'] === 'S');
$isunlock = ($producto['tipo'] === 'U');
$statussuccess = ($status === 'success');

$desturl = null;
$strsuccessunlock = '';
if ($statussuccess) {
    $dest = $isbonus ? '/grade/report/user/index.php' : '/course/view.php';
    $destmoodleurl = new moodle_url($dest, ['id' => $courseid]);

    if (!$isbonus && isset($cms[$producto['cid']]) && $cms[$producto['cid']]->url) {
        $destmoodleurl = $cms[$producto['cid']]->url;
    }
    $desturl = $destmoodleurl->out(false);

    $rewardlabel = ($producto['n_custom'] !== '') ? $producto['n_custom'] : $producto['n_real'];
    if ($rewardlabel === '') {
        $rewardlabel = get_string('points', 'local_xpstore');
    }
    $activityname = ($producto['n_real'] !== '') ? $producto['n_real'] : get_string('course');

    $a = new stdClass();
    $a->reward = s($rewardlabel);
    $a->activity = s($activityname);

    if ($isbonus) {
        $strsuccessunlock = get_string('success_unlock_gradebook', 'local_xpstore', $a);
    } else {
        $strsuccessunlock = get_string('success_unlock_reward', 'local_xpstore', $a);
    }
}

$disabled = ($saldo < $producto['costo']) || (!$requisitocumplido) || (!$nivelcumplido);

$btntext = get_string('canjear', 'local_xpstore');
if ($disabled) {
    if (!$nivelcumplido) {
        $sm = get_string_manager();
        $btntext = $sm->string_exists('requires_level', 'local_xpstore')
            ? get_string('requires_level', 'local_xpstore', $producto['minlevel'])
            : ('Requiere Nivel ' . $producto['minlevel']);
    } else if (!$requisitocumplido) {
        $sm = get_string_manager();
        $strreq = $sm->string_exists('requires_short', 'local_xpstore')
            ? get_string('requires_short', 'local_xpstore')
            : 'Requiere';
        $btntext = $strreq . ' ' . $reqname;
    } else {
        $btntext = get_string('insuficiente', 'local_xpstore');
    }
}

$templatedata = [
    'hidden_by_level' => $hiddenbylevel,
    'cpstore' => $cpstore,
    'cbstore' => $cbstore,
    'cistore' => $cistore,
    'bcchannel' => local_xpstore_get_broadcast_channel($courseid, $USER->id),
    'saldo' => $saldo,
    'status_success' => $statussuccess,
    'icon' => $icon,
    'is_custom_image' => $iscustomimg,
    'icon_url' => $iconurl,
    'is_bonus' => $isbonus,
    'badge_text' => local_xpstore_get_student_badge_label($producto['tipo'], $producto['boost']),
    'boost' => $producto['boost'],
    'is_special' => $isspecial,
    'is_unlock' => $isunlock,
    'displayname' => ($producto['n_custom'] !== '') ? $producto['n_custom'] : $producto['n_real'],
    'n_real' => $producto['n_real'],
    'desturl' => $desturl,
    'costo' => $producto['costo'],
    'has_limit' => ($producto['limite'] > 0),
    'comprasactuales' => $comprasactuales,
    'limite' => $producto['limite'],
    'actionurl' => $url->out(false),
    'sesskey' => sesskey(),
    'limitealcanzado' => $limitealcanzado,
    'locked_by_req' => (!$requisitocumplido || !$nivelcumplido),
    'req_name' => $reqname,
    'disabled' => $disabled,
    'btntext' => $btntext,
    'str_balance' => get_string('balance', 'local_xpstore'),
    'str_widgetunlocked' => get_string('widgetunlocked', 'local_xpstore'),
    'str_widgetunlockeddesc' => get_string('widgetunlockeddesc', 'local_xpstore'),
    'str_points' => get_string('points', 'local_xpstore'),
    'str_specialcontent' => get_string('specialcontent', 'local_xpstore'),
    'str_unlockcontent' => get_string('type_u', 'local_xpstore'),
    'str_goto_dest' => $isbonus
        ? get_string('gotogradebook', 'local_xpstore')
        : get_string('gotoactivity', 'local_xpstore'),
    'success_icon' => $successicon,
    'success_is_custom_image' => $successiscustomimg,
    'success_icon_url' => $successiconurl,
    'str_success_unlock' => $strsuccessunlock,
    'str_congratulations' => get_string('congratulations', 'local_xpstore'),
    'str_redemptions_count' => get_string('redemptions_count', 'local_xpstore'),
    'str_soldout' => get_string('soldout', 'local_xpstore'),
];

echo $OUTPUT->render_from_template('local_xpstore/widget', $templatedata);

echo $OUTPUT->footer();
