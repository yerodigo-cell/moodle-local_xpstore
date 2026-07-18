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
 * widget_category.php - Embedded store category widget.
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

global $USER, $PAGE, $OUTPUT, $DB;

$courseid = required_param('id', PARAM_INT);
$catreq = optional_param('cat', '', PARAM_TEXT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);
require_login($course);


$urlparams = ['id' => $courseid, 'cat' => $catreq];
$url = new moodle_url('/local/xpstore/widget_category.php', $urlparams);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('embedded');
$PAGE->set_title(get_string('storetitle', 'local_xpstore'));
$PAGE->set_heading($course->fullname);

if (isset($PAGE->blocks)) {
    $PAGE->blocks->show_only_fake_blocks();
}

$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'comprar' && confirm_sesskey()) {
    $cmid = required_param('cmid', PARAM_INT);
    $tipo = required_param('tipo', PARAM_ALPHA);
    $costo = required_param('costo', PARAM_INT);

    $productosraw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
    $items = array_filter(explode(',', $productosraw));
    $limitecompra = 0;
    $requisito = 0;

    foreach ($items as $it) {
        $ptipo = substr($it, 0, 1);
        $pparts = explode(':', substr($it, 1));
        if ($ptipo === $tipo && (int)$pparts[0] === $cmid) {
            $limitecompra = (int)($pparts[5] ?? 0);
            $requisito = (int)($pparts[6] ?? 0);
            break;
        }
    }

    $limitealcanzado = false;
    if ($limitecompra > 0) {
        $comprasparams = ['userid' => $USER->id, 'itemid' => $cmid, 'itemtype' => $tipo];
        $comprasprevias = $DB->count_records('local_xpstore_gastos', $comprasparams);
        if ($comprasprevias >= $limitecompra) {
            $limitealcanzado = true;
        }
    }

    $requisitocumplido = true;
    if ($requisito > 0) {
        require_once($CFG->libdir . '/completionlib.php');
        $completion = new completion_info($course);
        $reqcminfo = get_fast_modinfo($courseid)->get_cm($requisito);
        if ($reqcminfo) {
            $completiondata = $completion->get_data($reqcminfo, false, $USER->id);
            $requisitocumplido = ($completiondata->completionstate == COMPLETION_COMPLETE ||
                $completiondata->completionstate == COMPLETION_COMPLETE_PASS);
        }
    }

    if ($limitealcanzado) {
        redirect(new moodle_url($url, ['status' => 'limit']));
    } else if (!$requisitocumplido) {
        redirect(new moodle_url($url, ['status' => 'req_failed']));
    } else {
        if (local_xpstore_purchase($USER->id, $tipo, $cmid, $costo, $courseid)) {
            local_xpstore_deliver_product($USER->id, $cmid, $tipo, $courseid);
            $successparams = ['status' => 'success', 'cmid' => $cmid, 'tipo_compra' => $tipo];
            redirect(new moodle_url($url, $successparams));
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
$modinfo = get_fast_modinfo($courseid);
$historyurl = new moodle_url('/local/xpstore/history.php', ['id' => $courseid]);

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
        $requisito = (int)($parts[6] ?? 0);

        if ($catreq === '' || strtolower($nombrecat) === strtolower($catreq)) {
            $modinfo = get_fast_modinfo($courseid);
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

                $cmurl = (isset($modinfo->cms[$cid]) && $modinfo->cms[$cid]->url) ?
                    $modinfo->cms[$cid]->url->out(false) :
                    (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false);
                $gradebookurl = (new moodle_url('/grade/report/user/index.php', ['id' => $courseid]))->out(false);

                $requisitocumplido = true;
                $reqname = '';
                if ($requisito > 0) {
                    require_once($CFG->libdir . '/completionlib.php');
                    $completion = new completion_info($course);
                    if (isset($cms[$requisito])) {
                        $reqcminfo = $cms[$requisito];
                        $reqname = $reqcminfo->name;
                        $completiondata = $completion->get_data($reqcminfo, false, $USER->id);
                        $requisitocumplido = ($completiondata->completionstate == COMPLETION_COMPLETE ||
                            $completiondata->completionstate == COMPLETION_COMPLETE_PASS);
                    }
                }

                $disabled = ($saldo < $costo) || (!$requisitocumplido);
                $btntext = get_string('canjear', 'local_xpstore');
                if ($disabled) {
                    if (!$requisitocumplido) {
                        $sm = get_string_manager();
                        $strreq = $sm->string_exists('requires', 'local_xpstore') ?
                            get_string('requires', 'local_xpstore') : 'Requiere';
                        $btntext = $reqname . ' ' . $strreq;
                    } else {
                        $btntext = get_string('insuficiente', 'local_xpstore');
                    }
                }

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
                    'locked_by_req' => !$requisitocumplido,
                    'req_name' => $reqname,
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

$redirecturl = '';
$strgotodest = '';
$strsuccessunlock = '';
if ($status === 'success') {
    $itemname = '';
    $activityname = '';
    $successicon = 'gift';
    foreach ($storecategories as $cat => $prods) {
        foreach ($prods as $p) {
            if ($p['tipo'] === $tipocompra && (int)$p['cid'] === $boughtcmid) {
                $itemname = $p['displayname'];
                $activityname = $p['nreal'];
                $successicon = isset($caticons[$cat]) ? $caticons[$cat] : 'trophy';
                break 2;
            }
        }
    }
    if (empty($itemname)) {
        $itemname = get_string('points', 'local_xpstore');
    }
    if (empty($activityname)) {
        if (isset($modinfo->cms[$boughtcmid])) {
            $activityname = $modinfo->cms[$boughtcmid]->name;
        } else {
            $activityname = get_string('course', 'local_xpstore');
        }
    }

    $a = new stdClass();
    $a->reward = $itemname;
    $a->activity = $activityname;

    if ($tipocompra === 'G') {
        $redirecturl = (new moodle_url('/grade/report/user/index.php', ['id' => $courseid]))->out(false);
        $strgotodest = get_string('gotogradebook', 'local_xpstore');
        $strsuccessunlock = get_string('success_unlock_gradebook', 'local_xpstore', $a);
    } else {
        $redirecturl = (isset($modinfo->cms[$boughtcmid]) && $modinfo->cms[$boughtcmid]->url) ?
            $modinfo->cms[$boughtcmid]->url->out(false) :
            (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false);
        $strgotodest = get_string('gotoactivity', 'local_xpstore');
        $strsuccessunlock = get_string('success_unlock_reward', 'local_xpstore', $a);
    }
    $strsuccessunlock = addslashes($strsuccessunlock);
}

$templatedata = [
    'cpstore' => $cpstore,
    'cbstore' => $cbstore,
    'cistore' => $cistore,
    'ccstore' => $ccstore,
    'status_success' => ($status === 'success'),
    'status_error' => ($status === 'error'),
    'status_limit' => ($status === 'limit'),
    'redirecturl' => $redirecturl,
    'str_goto_dest' => $strgotodest,
    'success_icon' => $successicon ?? 'gift',
    'str_success_unlock' => $strsuccessunlock,
    'str_congratulations' => get_string('congratulations', 'local_xpstore'),
    'str_exito' => get_string('exito', 'local_xpstore'),
    'str_insuficiente' => get_string('insuficiente', 'local_xpstore'),
    'str_limitreached' => get_string('limitreached', 'local_xpstore'),
    'str_saldo' => get_string('saldo', 'local_xpstore'),
    'saldo' => $saldo,
    'historyurl' => $historyurl->out(false),
    'str_history' => get_string('history', 'local_xpstore'),
    'isempty' => empty($formattedcategories),
    'str_widgeterror' => get_string('widgeterror', 'local_xpstore'),
    'categoriastienda' => $formattedcategories,
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_xpstore/widget_category', $templatedata);
echo $OUTPUT->footer();
