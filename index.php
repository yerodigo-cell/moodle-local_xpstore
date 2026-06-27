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
 * index.php - Premium version.
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

global $USER, $PAGE, $OUTPUT, $DB, $SESSION;

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
    $costo = required_param('costo', PARAM_INT);

    $productosraw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
    $items = array_filter(explode(',', $productosraw));
    $limitecompra = 0;

    foreach ($items as $it) {
        $ptipo = substr($it, 0, 1);
        $pparts = explode(':', substr($it, 1));
        if ($ptipo === $tipo && (int)$pparts[0] === $cmid) {
            $limitecompra = (int)($pparts[5] ?? 0);
            break;
        }
    }

    $limitealcanzado = false;
    if ($limitecompra > 0) {
        $comprasprevias = $DB->count_records(
            'local_xpstore_gastos',
            ['userid' => $USER->id, 'itemid' => $cmid, 'itemtype' => $tipo]
        );
        if ($comprasprevias >= $limitecompra) {
            $limitealcanzado = true;
        }
    }

    if ($limitealcanzado) {
        redirect(new moodle_url($url, ['status' => 'limit']));
    } else {
        if (local_xpstore_purchase($USER->id, $tipo, $cmid, $costo, $courseid)) {
            local_xpstore_deliver_product($USER->id, $cmid, $tipo, $courseid);
            redirect(new moodle_url(
                $url,
                ['status' => 'success', 'cmid' => $cmid, 'tipo_compra' => $tipo]
            ));
        } else {
            redirect(new moodle_url($url, ['status' => 'error']));
        }
    }
}

$status = optional_param('status', '', PARAM_ALPHA);
$boughtcmid = optional_param('cmid', 0, PARAM_INT);
$tipocompra = optional_param('tipo_compra', '', PARAM_ALPHA);

$saldo = local_xpstore_get_balance($USER->id, $courseid);
$productosraw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
$todoslosproductos = array_filter(array_map('trim', explode(',', $productosraw)));
$isteacher = has_capability('moodle/course:update', $context) || is_siteadmin();
$modinfo = get_fast_modinfo($courseid);

$cpstore = get_config('local_xpstore', 'color_primary_course_' . $courseid) ?: '#0056D2';
$cbstore = get_config('local_xpstore', 'color_secondary_course_' . $courseid) ?: '#00C9A7';
$cistore = get_config('local_xpstore', 'color_icon_course_' . $courseid) ?: $cpstore;
$ccstore = get_config('local_xpstore', 'color_cat_icon_course_' . $courseid) ?: $cpstore;

$caticons = json_decode(get_config('local_xpstore', 'cat_icons_course_' . $courseid), true) ?: [];

$storecategories = [];
foreach ($todoslosproductos as $item) {
    $tipochar = substr($item, 0, 1);
    $rest = substr($item, 1);
    $parts = explode(':', $rest);

    if (count($parts) >= 2) {
        $cid = $parts[0] ?? '';
        $costo = $parts[1] ?? '';
        $ncustom = $parts[2] ?? '';
        $boost = $parts[3] ?? '0';
        $nombrecat = !empty($parts[4]) ? trim($parts[4]) : get_string('defaultcategory', 'local_xpstore');
        $limite = (int)($parts[5] ?? 0);

        $cms = $modinfo->get_cms();
        if (isset($cms[$cid])) {
            $cm = $cms[$cid];
            $nreal = $cm->name;
            $iconmap = [
                'Q' => 'bolt',
                'A' => 'file-text',
                'F' => 'comments',
                'G' => 'star',
                'S' => 'unlock-alt',
            ];
            $icon = $iconmap[$tipochar] ?? 'gift';

            $comprasparams = ['userid' => $USER->id, 'itemid' => $cid, 'itemtype' => $tipochar];
            $comprasactuales = $DB->count_records('local_xpstore_gastos', $comprasparams);
            $limitealcanzado = ($limite > 0 && $comprasactuales >= $limite);

            $isbonus = ($tipochar == 'G' && !empty($boost) && $boost != '0');
            $isspecial = ($tipochar == 'S');

            $boughtthis = ($status === 'success' && $boughtcmid == $cid && $tipocompra == $tipochar);
            $gotogradebook = ($boughtthis && $tipochar == 'G');

            $cmurl = isset($modinfo->cms[$cid]) ?
                $modinfo->cms[$cid]->url->out(false) :
                (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false);
            $gradebookurl = (new moodle_url('/grade/report/user/index.php', ['id' => $courseid]))->out(false);

            $disabled = ($saldo < $costo);
            $btntext = $disabled ? get_string('insuficiente', 'local_xpstore') : get_string('canjear', 'local_xpstore');

            $storecategories[$nombrecat][] = [
                'tipo' => $tipochar,
                'cid' => $cid,
                'costo' => $costo,
                'displayname' => $ncustom ?: $nreal,
                'nreal' => $nreal,
                'icon' => $icon,
                'boost' => $boost,
                'is_bonus' => $isbonus,
                'is_special' => $isspecial,
                'limite' => $limite,
                'has_limit' => ($limite > 0),
                'comprasactuales' => $comprasactuales,
                'limitealcanzado' => $limitealcanzado,
                'bought_this' => $boughtthis,
                'gotogradebook' => $gotogradebook,
                'cmurl' => $cmurl,
                'gradebookurl' => $gradebookurl,
                'disabled' => $disabled,
                'btntext' => $btntext,
                'str_points' => get_string('points', 'local_xpstore'),
                'str_specialcontent' => get_string('specialcontent', 'local_xpstore'),
                'str_redemptions_count' => get_string('redemptions_count', 'local_xpstore'),
                'str_gotoactivity' => get_string('gotoactivity', 'local_xpstore'),
                'str_gotogradebook' => get_string('gotogradebook', 'local_xpstore'),
                'str_soldout' => get_string('soldout', 'local_xpstore'),
                'actionurl' => $url->out(false),
                'sesskey' => sesskey(),
            ];
        }
    }
}

$formattedcategories = [];
foreach ($storecategories as $nombreseccion => $productos) {
    $caticon = isset($caticons[$nombreseccion]) ? $caticons[$nombreseccion] : 'trophy';

    $formattedcategories[] = [
        'nombreseccion' => $nombreseccion,
        'caticon' => $caticon,
        'productos' => $productos,
    ];
}

$isgradesuccess = ($status === 'success' && $tipocompra === 'G');
$redirecturl = '';
$strgotodest = '';
$strsuccessunlock = '';
if ($status === 'success') {
    $itemname = '';
    $activityname = '';
    $successicon = 'gift';
    foreach ($todoslosproductos as $it) {
        $ptipo = substr($it, 0, 1);
        $pparts = explode(':', substr($it, 1));
        if ($ptipo === $tipocompra && (int)$pparts[0] === $boughtcmid) {
            $itemname = !empty($pparts[2]) ? $pparts[2] :
                (isset($modinfo->cms[$boughtcmid]) ? $modinfo->cms[$boughtcmid]->name : '');
            $itemcat = !empty($pparts[4]) ? trim($pparts[4]) : get_string('defaultcategory', 'local_xpstore');
            $successicon = isset($caticons[$itemcat]) ? $caticons[$itemcat] : 'trophy';
            break;
        }
    }
    if (isset($modinfo->cms[$boughtcmid])) {
        $activityname = $modinfo->cms[$boughtcmid]->name;
    }
    if (empty($itemname)) {
        $itemname = get_string('points', 'local_xpstore');
    }

    $a = new stdClass();
    $a->reward = $itemname;
    $a->activity = $activityname;

    if ($tipocompra === 'G') {
        $redirecturl = (new moodle_url('/grade/report/user/index.php', ['id' => $courseid]))->out(false);
        $strgotodest = get_string('gotogradebook', 'local_xpstore');
        $strsuccessunlock = get_string('success_unlock_gradebook', 'local_xpstore', $a);
    } else {
        $redirecturl = isset($modinfo->cms[$boughtcmid]) ?
            $modinfo->cms[$boughtcmid]->url->out(false) :
            (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false);
        $strgotodest = get_string('gotoactivity', 'local_xpstore');
        $strsuccessunlock = get_string('success_unlock_reward', 'local_xpstore', $a);
    }
    $strsuccessunlock = addslashes($strsuccessunlock);
} else if ($status === 'error') {
    \core\notification::add(get_string('insuficiente', 'local_xpstore'), \core\output\notification::NOTIFY_ERROR);
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
    'status_success' => ($status === 'success'),
    'status_error' => ($status === 'error'),
    'status_limit' => ($status === 'limit'),
    'is_grade_success' => $isgradesuccess,
    'redirecturl' => $redirecturl,
    'str_goto_dest' => $strgotodest,
    'success_icon' => $successicon ?? 'gift',
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
