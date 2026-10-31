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
 * index.php - Premium version.
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

global $USER, $PAGE, $OUTPUT, $DB, $CFG;

$courseid = optional_param('id', 0, PARAM_INT);
if ($courseid <= 0) {
    if (!empty($PAGE->course->id) && $PAGE->course->id != SITEID) {
        $courseid = $PAGE->course->id;
    }
}

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);
require_login($course);

$url = new moodle_url('/local/xpstore/index.php', ['id' => $courseid]);
$historyurl = new moodle_url('/local/xpstore/history.php', ['id' => $courseid]);
$configurl = new moodle_url('/local/xpstore/config.php', ['id' => $courseid]);
$reporturl = new moodle_url('/local/xpstore/report.php', ['id' => $courseid]);

$action = optional_param('action', '', PARAM_ALPHA);

if ($action === 'togglemenu' && confirm_sesskey()) {
    if (!local_xpstore_can_manage($context)) {
        throw new required_capability_exception($context, 'local/xpstore:manage', 'nopermissions', '');
    }
    $currentmenu = get_config('local_xpstore', 'show_menu_course_' . $courseid);
    if ($currentmenu === false) {
        $currentmenu = '0';
    }
    $newmenu = ($currentmenu === '0') ? '1' : '0';
    set_config('show_menu_course_' . $courseid, $newmenu, 'local_xpstore');
    redirect($url);
}

$menuvisible = get_config('local_xpstore', 'show_menu_course_' . $courseid);
if ($menuvisible === false) {
    $menuvisible = '0';
}

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('storetitle', 'local_xpstore') . ' - ' . $course->shortname);

if ($action === 'comprar' && confirm_sesskey()) {
    $cmid = required_param('cmid', PARAM_INT);
    $tipo = required_param('tipo', PARAM_ALPHA);

    $result = local_xpstore_process_redemption($course, $USER->id, $tipo, $cmid);
    if (!empty($result['success'])) {
        redirect(new moodle_url(
            $url,
            ['status' => 'success', 'cmid' => $cmid, 'tipo_compra' => strtoupper($tipo)]
        ));
    } else if (($result['code'] ?? '') === 'limit') {
        redirect(new moodle_url($url, ['status' => 'limit']));
    } else if (($result['code'] ?? '') === 'req_failed') {
        redirect(new moodle_url($url, ['status' => 'req_failed']));
    } else {
        redirect(new moodle_url($url, ['status' => 'error']));
    }
}

$status = optional_param('status', '', PARAM_ALPHANUMEXT);
$boughtcmid = optional_param('cmid', 0, PARAM_INT);
$tipocompra = strtoupper(optional_param('tipo_compra', '', PARAM_ALPHA));

$saldo = local_xpstore_get_balance($USER->id, $courseid);
$userlevel = local_xpstore_get_user_level($USER->id, $courseid);
$catalog = local_xpstore_parse_catalog($courseid);
$isteacher = local_xpstore_can_manage($context) || is_siteadmin();
$modinfo = get_fast_modinfo($courseid);
$cms = $modinfo->get_cms();

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
$ccstore = local_xpstore_clean_hex_color(
    get_config('local_xpstore', 'color_cat_icon_course_' . $courseid) ?: $cpstore,
    $cpstore
);

$caticons = json_decode(get_config('local_xpstore', 'cat_icons_course_' . $courseid) ?: '', true) ?: [];

$completion = null;
$storecategories = [];

foreach ($catalog as $item) {
    $tipochar = $item['tipo'];
    $cid = (int)$item['cmid'];
    $costo = (int)$item['costo'];
    $ncustom = $item['nombre'];
    $boost = $item['valornota'];
    $nombrecat = ($item['categoria'] !== '') ? $item['categoria'] : get_string('defaultcategory', 'local_xpstore');
    $limite = (int)$item['limite'];
    $requisito = (int)$item['requisito'];
    $minlevel = (int)$item['minlevel'];
    $hidebylevel = (int)$item['hidebylevel'];
    $nivelcumplido = ($minlevel <= 0 || $userlevel >= $minlevel);

    if (!$nivelcumplido && $hidebylevel === 1) {
        continue;
    }

    if ($tipochar === 'M') {
        $gi = $DB->get_record('grade_items', ['id' => $cid, 'courseid' => $courseid, 'itemtype' => 'manual']);
        if (!$gi) {
            continue;
        }
        $nreal = format_string($gi->itemname);
    } else if ($tipochar === 'C') {
        $gi = $DB->get_record('grade_items', ['id' => $cid, 'courseid' => $courseid, 'itemtype' => 'course']);
        if (!$gi) {
            continue;
        }
        $nreal = local_xpstore_get_course_grade_item_name($cid);
    } else if (isset($cms[$cid]) && empty($cms[$cid]->deletioninprogress)) {
        $cm = $cms[$cid];
        $nreal = format_string($cm->name);
    } else {
        continue;
    }

    $defaulticon = local_xpstore_get_default_card_icon($tipochar);
    $icon = ($item['icono'] !== '') ? local_xpstore_clean_icon($item['icono'], $defaulticon) : $defaulticon;
    $iscustomimg = local_xpstore_is_custom_card_image($icon);
    $iconurl = $iscustomimg ? local_xpstore_get_card_image_url($courseid, $icon) : '';
    if ($iscustomimg && !$iconurl) {
        $icon = $defaulticon;
        $iscustomimg = false;
    }

    $comprasactuales = (int)$DB->count_records_select(
        'local_xpstore_gastos',
        'userid = ? AND itemid = ? AND itemtype = ? AND (courseid = ? OR courseid = 0)',
        [$USER->id, $cid, $tipochar, $courseid]
    );
    $limitealcanzado = ($limite > 0 && $comprasactuales >= $limite);

    $isbonus = in_array($tipochar, ['G', 'M', 'C'], true);
    $isspecial = ($tipochar === 'S');
    $isunlock = ($tipochar === 'U');

    $boughtthis = ($status === 'success' && $boughtcmid === $cid && $tipocompra === $tipochar);
    $gotogradebook = ($boughtthis && $isbonus);

    if ($tipochar === 'M' || $tipochar === 'C') {
        $cmurl = (new moodle_url('/grade/report/user/index.php', ['id' => $courseid]))->out(false);
    } else {
        $cmurl = (isset($modinfo->cms[$cid]) && $modinfo->cms[$cid]->url)
            ? $modinfo->cms[$cid]->url->out(false)
            : (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false);
    }
    $gradebookurl = (new moodle_url('/grade/report/user/index.php', ['id' => $courseid]))->out(false);

    $requisitocumplido = true;
    $reqname = '';
    if ($requisito > 0) {
        if (isset($cms[$requisito]) && empty($cms[$requisito]->deletioninprogress)) {
            if ($completion === null) {
                require_once($CFG->libdir . '/completionlib.php');
                $completion = new completion_info($course);
            }
            $reqcminfo = $cms[$requisito];
            $reqname = format_string($reqcminfo->name);
            $completiondata = $completion->get_data($reqcminfo, false, $USER->id);
            $requisitocumplido = (
                $completiondata->completionstate == COMPLETION_COMPLETE ||
                $completiondata->completionstate == COMPLETION_COMPLETE_PASS
            );
        } else {
            // Fail-closed if prerequisite activity was deleted.
            $requisitocumplido = false;
            $reqname = get_string('activitydeleted', 'local_xpstore');
        }
    }

    $disabled = ($saldo < $costo) || (!$requisitocumplido) || (!$nivelcumplido);
    $btntext = get_string('canjear', 'local_xpstore');
    if ($disabled) {
        if (!$nivelcumplido) {
            $sm = get_string_manager();
            $btntext = $sm->string_exists('requires_level', 'local_xpstore')
                ? get_string('requires_level', 'local_xpstore', $minlevel)
                : ('Requiere Nivel ' . $minlevel);
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

    $storecategories[$nombrecat][] = [
        'tipo' => $tipochar,
        'cid' => $cid,
        'costo' => $costo,
        'displayname' => ($ncustom !== '') ? $ncustom : $nreal,
        'nreal' => $nreal,
        'icon' => $icon,
        'is_custom_image' => $iscustomimg,
        'icon_url' => $iconurl,
        'boost' => $boost,
        'badge_text' => local_xpstore_get_student_badge_label($tipochar, $boost),
        'is_bonus' => $isbonus,
        'is_special' => $isspecial,
        'is_unlock' => $isunlock,
        'limite' => $limite,
        'has_limit' => ($limite > 0),
        'comprasactuales' => $comprasactuales,
        'limitealcanzado' => $limitealcanzado,
        'locked_by_req' => (!$requisitocumplido || !$nivelcumplido),
        'req_name' => $reqname,
        'bought_this' => $boughtthis,
        'gotogradebook' => $gotogradebook,
        'cmurl' => $cmurl,
        'gradebookurl' => $gradebookurl,
        'disabled' => $disabled,
        'btntext' => $btntext,
        'str_points' => get_string('points', 'local_xpstore'),
        'str_specialcontent' => get_string('specialcontent', 'local_xpstore'),
        'str_unlockcontent' => get_string('type_u', 'local_xpstore'),
        'str_redemptions_count' => get_string('redemptions_count', 'local_xpstore'),
        'str_gotoactivity' => get_string('gotoactivity', 'local_xpstore'),
        'str_gotogradebook' => get_string('gotogradebook', 'local_xpstore'),
        'str_soldout' => get_string('soldout', 'local_xpstore'),
        'actionurl' => $url->out(false),
        'sesskey' => sesskey(),
    ];
}

$formattedcategories = [];
foreach ($storecategories as $nombreseccion => $productos) {
    $rawcaticon = $caticons[$nombreseccion] ?? 'trophy';
    $caticon = local_xpstore_clean_icon($rawcaticon, 'trophy');
    if (local_xpstore_is_custom_card_image($caticon)) {
        $caticon = 'trophy';
    }

    $formattedcategories[] = [
        'nombreseccion' => $nombreseccion,
        'caticon' => $caticon,
        'productos' => $productos,
    ];
}

$isgradesuccess = ($status === 'success' && in_array($tipocompra, ['G', 'M', 'C'], true));
$redirecturl = '';
$strgotodest = '';
$strsuccessunlock = '';
$successicon = 'gift';
$successiscustomimg = false;
$successiconurl = '';

if ($status === 'success') {
    $itemname = '';
    $activityname = '';
    $boughtkey = $tipocompra . $boughtcmid;

    if (isset($catalog[$boughtkey])) {
        $catitem = $catalog[$boughtkey];
        $itemcat = ($catitem['categoria'] !== '') ? $catitem['categoria'] : get_string('defaultcategory', 'local_xpstore');
        $fallbackcaticon = local_xpstore_clean_icon($caticons[$itemcat] ?? 'trophy', 'trophy');
        if (local_xpstore_is_custom_card_image($fallbackcaticon)) {
            $fallbackcaticon = 'trophy';
        }
        $successicon = ($catitem['icono'] !== '')
            ? local_xpstore_clean_icon($catitem['icono'], $fallbackcaticon)
            : $fallbackcaticon;
        $successiscustomimg = local_xpstore_is_custom_card_image($successicon);
        $successiconurl = $successiscustomimg ? local_xpstore_get_card_image_url($courseid, $successicon) : '';
        if ($successiscustomimg && !$successiconurl) {
            $successicon = $fallbackcaticon;
            $successiscustomimg = false;
        }
        $itemname = $catitem['nombre'];
    }

    if ($tipocompra === 'M') {
        $rawact = $DB->get_field('grade_items', 'itemname', ['id' => $boughtcmid, 'courseid' => $courseid]) ?: '';
        $activityname = $rawact !== '' ? format_string($rawact) : '';
    } else if ($tipocompra === 'C') {
        $activityname = local_xpstore_get_course_grade_item_name($boughtcmid);
    } else if (isset($modinfo->cms[$boughtcmid])) {
        $activityname = format_string($modinfo->cms[$boughtcmid]->name);
    }

    if ($itemname === '') {
        $itemname = $activityname !== '' ? $activityname : get_string('points', 'local_xpstore');
    }

    $a = new stdClass();
    $a->reward = s($itemname);
    $a->activity = s($activityname);

    if (in_array($tipocompra, ['G', 'M', 'C'], true)) {
        $redirecturl = (new moodle_url('/grade/report/user/index.php', ['id' => $courseid]))->out(false);
        $strgotodest = get_string('gotogradebook', 'local_xpstore');
        $strsuccessunlock = get_string('success_unlock_gradebook', 'local_xpstore', $a);
    } else {
        $redirecturl = (isset($modinfo->cms[$boughtcmid]) && $modinfo->cms[$boughtcmid]->url)
            ? $modinfo->cms[$boughtcmid]->url->out(false)
            : (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false);
        $strgotodest = get_string('gotoactivity', 'local_xpstore');
        $strsuccessunlock = get_string('success_unlock_reward', 'local_xpstore', $a);
    }
} else if ($status === 'error') {
    \core\notification::add(get_string('insuficiente', 'local_xpstore'), \core\output\notification::NOTIFY_ERROR);
} else if ($status === 'req_failed') {
    \core\notification::add(get_string('error_prereq', 'local_xpstore'), \core\output\notification::NOTIFY_WARNING);
}

$navdata = local_xpstore_get_navigation_data($courseid, 'store');
$navdata['isteacher'] = $isteacher;
$navdata['show_toggle_menu_btn'] = true;
$navdata['menu_is_visible'] = ($menuvisible === '1');
$navdata['str_hide_menu_tooltip'] = get_string('hide_menu_tooltip', 'local_xpstore');
$navdata['str_show_menu_tooltip'] = get_string('show_menu_tooltip', 'local_xpstore');
$navdata['str_menuvisible'] = get_string('menuvisible', 'local_xpstore');
$navdata['str_menuhidden'] = get_string('menuhidden', 'local_xpstore');
$navdata['help_menuvisibility'] = $OUTPUT->help_icon('menuvisibility', 'local_xpstore');
$navdata['togglemenuurl'] = (new moodle_url('/local/xpstore/index.php', ['id' => $courseid]))->out(false);
$navdata['sesskey'] = sesskey();

$templatedata = array_merge([
    'cpstore' => $cpstore,
    'cbstore' => $cbstore,
    'cistore' => $cistore,
    'ccstore' => $ccstore,
    'bcchannel' => local_xpstore_get_broadcast_channel($courseid, $USER->id),
    'status_success' => ($status === 'success'),
    'status_error' => ($status === 'error'),
    'status_limit' => ($status === 'limit'),
    'is_grade_success' => $isgradesuccess,
    'redirecturl' => $redirecturl,
    'str_goto_dest' => $strgotodest,
    'success_icon' => $successicon,
    'success_is_custom_image' => $successiscustomimg,
    'success_icon_url' => $successiconurl,
    'str_success_unlock' => $strsuccessunlock,
    'str_congratulations' => get_string('congratulations', 'local_xpstore'),
    'str_exito' => get_string('exito', 'local_xpstore'),
    'str_limitreached' => get_string('limitreached', 'local_xpstore'),
    'str_saldo' => get_string('saldo', 'local_xpstore'),
    'saldo' => $saldo,
    'historyurl' => $historyurl->out(false),
    'str_history' => get_string('history', 'local_xpstore'),
    'str_configure' => get_string('configure', 'local_xpstore'),
    'isempty' => empty($formattedcategories),
    'str_storeempty_title' => get_string('storeempty_title', 'local_xpstore'),
    'str_storeempty_desc' => get_string('storeempty_desc', 'local_xpstore'),
    'categoriastienda' => $formattedcategories,
    'gradebookurl' => (new moodle_url('/grade/report/user/index.php', ['id' => $courseid]))->out(false),
    'str_gotogradebook' => get_string('gotogradebook', 'local_xpstore'),
], $navdata);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_xpstore/store_catalog', $templatedata);
echo $OUTPUT->footer();
