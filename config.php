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
 * config.php
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('moodle/course:update', $context);

$url = new moodle_url('/local/xpstore/config.php', ['id' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('configtitle', 'local_xpstore'));

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$tab = optional_param('tab', '', PARAM_TEXT);

$catalogkey = 'catalog_course_' . $courseid;



if ($action === 'add' && confirm_sesskey()) {
    $tipo = required_param('tipo', PARAM_ALPHA);
    $cmid = required_param('cmid', PARAM_INT);
    $costo = required_param('costo', PARAM_INT);
    $nombre = required_param('nombre', PARAM_TEXT);
    $valornota = optional_param('valor_nota', '0', PARAM_FLOAT);
    $categoria = optional_param('categoria', '', PARAM_TEXT);
    $limite = optional_param('limite', 0, PARAM_INT);

    $categorialimpia = str_replace(':', '-', trim($categoria));
    $currentconfig = get_config('local_xpstore', $catalogkey) ?: '';

    $newitem = "{$tipo}{$cmid}:{$costo}:{$nombre}:{$valornota}:{$categorialimpia}:{$limite}";
    $updated = $currentconfig ? $currentconfig . ',' . $newitem : $newitem;
    set_config($catalogkey, $updated, 'local_xpstore');

    if ($tipo === 'S') {
        $groupparams = ['courseid' => $courseid, 'name' => $nombre];
        $group = $DB->get_record('groups', $groupparams);
        if (!$group) {
            require_once($CFG->dirroot . '/group/lib.php');
            $newgroup = new stdClass();
            $newgroup->courseid = $courseid;
            $newgroup->name = $nombre;
            $groupid = groups_create_group($newgroup);
        } else {
            $groupid = $group->id;
        }

        // Auto-apply group restriction to the activity.
        local_xpstore_apply_special_restriction($cmid, $groupid, $courseid);
    }
    redirect($url, get_string('productadded', 'local_xpstore'));
}

if ($action === 'edit_save' && confirm_sesskey()) {
    $olditem = required_param('old_item', PARAM_TEXT);
    $tipo = required_param('tipo', PARAM_ALPHA);
    $cmid = required_param('cmid', PARAM_INT);
    $costo = required_param('costo', PARAM_INT);
    $nombre = required_param('nombre', PARAM_TEXT);
    $valornota = optional_param('valor_nota', '0', PARAM_FLOAT);
    $categoria = optional_param('categoria', '', PARAM_TEXT);
    $limite = optional_param('limite', 0, PARAM_INT);

    $categorialimpia = str_replace(':', '-', trim($categoria));
    $newitem = "{$tipo}{$cmid}:{$costo}:{$nombre}:{$valornota}:{$categorialimpia}:{$limite}";

    $currentconfig = get_config('local_xpstore', $catalogkey) ?: '';
    $items = explode(',', $currentconfig);

    foreach ($items as $key => $item) {
        if (trim($item) === trim($olditem)) {
            $items[$key] = $newitem;
            break;
        }
    }

    set_config($catalogkey, implode(',', $items), 'local_xpstore');

    if ($tipo === 'S') {
        $groupparams = ['courseid' => $courseid, 'name' => $nombre];
        $group = $DB->get_record('groups', $groupparams);
        if (!$group) {
            require_once($CFG->dirroot . '/group/lib.php');
            $newgroup = new stdClass();
            $newgroup->courseid = $courseid;
            $newgroup->name = $nombre;
            $groupid = groups_create_group($newgroup);
        } else {
            $groupid = $group->id;
        }

        // Auto-apply group restriction to the updated activity.
        local_xpstore_apply_special_restriction($cmid, $groupid, $courseid);
    }

    redirect($url, get_string('productupdated', 'local_xpstore'));
}

if ($action === 'delete' && confirm_sesskey()) {
    $itemtodelete = required_param('item', PARAM_TEXT);
    $currentconfig = get_config('local_xpstore', $catalogkey) ?: '';

    $items = explode(',', $currentconfig);
    $newitems = [];
    foreach ($items as $i) {
        if (trim($i) !== trim($itemtodelete)) {
            $newitems[] = $i;
        }
    }

    set_config($catalogkey, implode(',', $newitems), 'local_xpstore');
    redirect($url, get_string('productdeleted', 'local_xpstore'));
}

if ($action === 'bulkdelete' && confirm_sesskey()) {
    $itemstodelete = optional_param_array('bulkdeleteitems', [], PARAM_TEXT);
    if (!empty($itemstodelete)) {
        $currentconfig = get_config('local_xpstore', $catalogkey) ?: '';
        $items = explode(',', $currentconfig);
        $newitems = [];
        foreach ($items as $i) {
            if (!in_array(trim($i), $itemstodelete)) {
                $newitems[] = $i;
            }
        }
        set_config($catalogkey, implode(',', $newitems), 'local_xpstore');
        redirect($url, get_string('productdeleted', 'local_xpstore'));
    } else {
        redirect($url);
    }
}

if ($action === 'deleteall' && confirm_sesskey()) {
    set_config($catalogkey, '', 'local_xpstore');
    redirect($url, get_string('deletedall', 'local_xpstore'));
}

if ($action === 'resetcolors' && confirm_sesskey()) {
    unset_config('color_primary_course_' . $courseid, 'local_xpstore');
    unset_config('color_secondary_course_' . $courseid, 'local_xpstore');
    unset_config('color_icon_course_' . $courseid, 'local_xpstore');
    unset_config('color_cat_icon_course_' . $courseid, 'local_xpstore');
    redirect(new moodle_url($url, ['tab' => 'settings']), get_string('colorsreset', 'local_xpstore'));
}

// Save all settings (colors and icons).
if ($action === 'savesettings' && confirm_sesskey()) {
    $cp = required_param('color_primary', PARAM_TEXT);
    $cb = required_param('color_secondary', PARAM_TEXT);
    $ci = required_param('color_icon', PARAM_TEXT);
    $cc = required_param('color_cat_icon', PARAM_TEXT);

    set_config('color_primary_course_' . $courseid, $cp, 'local_xpstore');
    set_config('color_secondary_course_' . $courseid, $cb, 'local_xpstore');
    set_config('color_icon_course_' . $courseid, $ci, 'local_xpstore');
    set_config('color_cat_icon_course_' . $courseid, $cc, 'local_xpstore');

    $caticons = [];
    $configraw = get_config('local_xpstore', $catalogkey) ?: '';
    $itemsraw = array_filter(explode(',', $configraw));
    foreach ($itemsraw as $it) {
        $parts = explode(':', substr($it, 1));
        if (count($parts) >= 2) {
            $cat = !empty($parts[4]) ? trim($parts[4]) : get_string('defaultcategory', 'local_xpstore');
            $inputname = 'caticon_' . md5($cat);
            $iconval = optional_param($inputname, null, PARAM_TEXT);
            if ($iconval !== null) {
                $caticons[$cat] = $iconval;
            }
        }
    }
    set_config('cat_icons_course_' . $courseid, json_encode($caticons), 'local_xpstore');
    redirect(new moodle_url($url, ['tab' => 'settings']), get_string('changessaved'));
}

$cpactual = get_config('local_xpstore', 'color_primary_course_' . $courseid) ?: '#0056D2';
$cbactual = get_config('local_xpstore', 'color_secondary_course_' . $courseid) ?: '#00C9A7';
$ciactual = get_config('local_xpstore', 'color_icon_course_' . $courseid) ?: $cpactual;
$ccactual = get_config('local_xpstore', 'color_cat_icon_course_' . $courseid) ?: $cpactual;

$isediting = false;
$etipo = '';
$ecmid = 0;
$ecosto = '';
$ecat = '';
$enombre = '';
$evalor = '0';
$elimite = '0';
$olditemval = '';

if ($action === 'load_edit') {
    $itemtoedit = required_param('item', PARAM_TEXT);
    $tipochar = substr($itemtoedit, 0, 1);
    $rest = substr($itemtoedit, 1);
    $parts = explode(':', $rest);

    if (count($parts) >= 2) {
        $etipo = $tipochar;
        $ecmid = (int)$parts[0];
        $ecosto = $parts[1];
        $enombre = $parts[2];
        $evalor = $parts[3];
        $ecat = $parts[4] ?? '';
        $elimite = $parts[5] ?? '0';
        $olditemval = $itemtoedit;
        $isediting = true;
    }
}

$typeoptions = [];
$availabletypes = [
    'Q' => get_string('type_q', 'local_xpstore'),
    'A' => get_string('type_a', 'local_xpstore'),
    'G' => get_string('type_g', 'local_xpstore'),
    'F' => get_string('type_f', 'local_xpstore'),
    'S' => get_string('type_s', 'local_xpstore'),
];
foreach ($availabletypes as $val => $label) {
    $typeoptions[] = [
        'value' => $val,
        'label' => $label,
        'selected' => ($etipo === $val),
    ];
}

$activityoptions = [];
$modinfo = get_fast_modinfo($courseid);
foreach ($modinfo->get_cms() as $cm) {
    if ($cm->has_view() && !in_array($cm->modname, ['label', 'resource', 'contentview'])) {
        $activityoptions[] = [
            'id' => $cm->id,
            'modname' => $cm->modname,
            'name' => "[" . strtoupper($cm->modname) . "] " . $cm->get_formatted_name(),
            'selected' => ($cm->id == $ecmid),
        ];
    }
}

$configraw = get_config('local_xpstore', $catalogkey) ?: '';
$categoriasunicas = [];
$hascategories = false;
$categoryiconoptions = [];
$categoryiframeoptions = [];
$catalogitems = [];
$hasitems = false;

if (!empty($configraw)) {
    $hasitems = true;
    $itemsraw = array_filter(explode(',', $configraw));

    foreach ($itemsraw as $it) {
        $parts = explode(':', substr($it, 1));
        if (count($parts) >= 2) {
            $cat = !empty($parts[4]) ? trim($parts[4]) : get_string('defaultcategory', 'local_xpstore');
            $categoriasunicas[$cat] = $cat;
        }
    }

    if (!empty($categoriasunicas)) {
        $hascategories = true;
        $savedicons = json_decode(get_config('local_xpstore', 'cat_icons_course_' . $courseid), true) ?: [];
        $availableicons = [
            'trophy' => get_string('icon_trophy', 'local_xpstore'),
            'star' => get_string('icon_star', 'local_xpstore'),
            'medal' => get_string('icon_medal', 'local_xpstore'),
            'award' => get_string('icon_award', 'local_xpstore'),
            'money' => get_string('icon_money', 'local_xpstore'),
            'diamond' => get_string('icon_diamond', 'local_xpstore'),
            'gift' => get_string('icon_gift', 'local_xpstore'),
            'shopping-cart' => get_string('icon_cart', 'local_xpstore'),
            'bolt' => get_string('icon_bolt', 'local_xpstore'),
            'shield' => get_string('icon_shield', 'local_xpstore'),
                        'gamepad' => get_string('icon_gamepad', 'local_xpstore'),
            'heart' => get_string('icon_heart', 'local_xpstore'),
            'ticket' => get_string('icon_ticket', 'local_xpstore'),
            'magic' => get_string('icon_magic', 'local_xpstore'),
            'rocket' => get_string('icon_rocket', 'local_xpstore'),
            'graduation-cap' => get_string('icon_graduation', 'local_xpstore'),
            'puzzle-piece' => get_string('icon_puzzle', 'local_xpstore'),
            'music' => get_string('icon_music', 'local_xpstore'),
            'camera' => get_string('icon_camera', 'local_xpstore'),
            'globe' => get_string('icon_globe', 'local_xpstore'),
            'book' => get_string('icon_book', 'local_xpstore'),
        ];

        foreach ($categoriasunicas as $catname) {
            $currenticon = isset($savedicons[$catname]) ? $savedicons[$catname] : 'trophy';
            $iconlist = [];
            foreach ($availableicons as $icoval => $icolabel) {
                $iconlist[] = [
                    'value' => $icoval,
                    'label' => $icolabel,
                    'selected' => ($currenticon === $icoval),
                ];
            }

            $categoryiconoptions[] = [
                'catname' => $catname,
                'cathash' => md5($catname),
                'currenticon' => $currenticon,
                'icons' => $iconlist,
            ];

            global $CFG;
            $urlcat = $CFG->wwwroot . "/local/xpstore/widget_category.php?id={$courseid}&cat=" . rawurlencode($catname);
            $iframecat = '<iframe src="' . $urlcat . '" width="100%" height="650" ' .
                'style="border: none; border-radius: 15px; overflow: hidden;" ' .
                'scrolling="no" allowfullscreen allowtransparency="true"></iframe>';

            $categoryiframeoptions[] = [
                'catname' => $catname,
                'iframecat' => $iframecat,
                'currenticon' => $currenticon,
            ];
        }
    }

    foreach ($itemsraw as $item) {
        $tipo = substr($item, 0, 1);
        $rest = substr($item, 1);
        $parts = explode(':', $rest);
        if (count($parts) >= 2) {
            $cid = $parts[0] ?? '';
            $cost = $parts[1] ?? '';
            $name = $parts[2] ?? '';
            $val = $parts[3] ?? '0';
            $cat = !empty($parts[4]) ? trim($parts[4]) : get_string('defaultcategory', 'local_xpstore');
            $limiteactual = isset($parts[5]) ? (int)$parts[5] : 0;

            $modinfo = get_fast_modinfo($courseid);
            $cms = $modinfo->get_cms();
            if (!isset($cms[$cid])) {
                continue;
            }
            $cm = $cms[$cid];
            $realname = $cm->name;
            $labeltipo = get_string('type_' . strtolower($tipo), 'local_xpstore');

            global $CFG;
            $widgeturl = $CFG->wwwroot . "/local/xpstore/widget.php?id={$courseid}&tipo={$tipo}&cmid={$cid}";
            $iframecode = '<iframe src="' . $widgeturl . '" ' .
                'style="width: 280px !important; max-width: 100%; height: 350px !important; border: none; ' .
                'overflow: hidden; border-radius: 15px; display: inline-block; margin: 10px;" ' .
                'scrolling="no" allowtransparency="true"></iframe>';

            $catalogitems[] = [
                'tipolower' => strtolower($tipo),
                'labeltipo' => $labeltipo,
                'cat' => $cat,
                'realname' => $realname,
                'cost' => $cost,
                'has_item_limit' => ($limiteactual > 0),
                'limiteactual' => $limiteactual,
                'displayname' => $name ?: $realname,
                'is_grade_boost' => ($tipo == 'G'),
                'val' => $val,
                'iframecode' => $iframecode,
                'rawitem' => $item,
                'editurl' => (new moodle_url($url, ['action' => 'load_edit', 'item' => $item]))->out(false),
                'deleteurl' => (new moodle_url($url, ['action' => 'delete', 'sesskey' => sesskey(), 'item' => $item]))->out(false),
            ];
        }
    }
}

global $CFG;
$urlstore = $CFG->wwwroot . "/local/xpstore/widget_category.php?id={$courseid}";
$iframestore = '<iframe src="' . $urlstore . '" width="100%" height="800" ' .
    'style="border: none; border-radius: 15px; overflow: hidden;" ' .
    'scrolling="no" allowfullscreen allowtransparency="true"></iframe>';

$urlhistoryw = $CFG->wwwroot . "/local/xpstore/widget_history.php?id={$courseid}";
$iframehistory = '<iframe src="' . $urlhistoryw . '" width="100%" height="150" ' .
    'style="border: none; overflow: hidden;" scrolling="no"></iframe>';

$activetab = ($tab === 'settings' || in_array($action, ['savesettings', 'resetcolors', 'togglemenu'])) ? 'settings' : 'products';

$navdata = local_xpstore_get_navigation_data($courseid, $activetab);
$navdata['isteacher'] = true;

$templatedata = array_merge([
    'str_configtitle' => get_string('configtitle', 'local_xpstore'),
    'url' => $url->out(false),
    'sesskey' => sesskey(),
    'menu_is_visible' => ($menuvisible === '1'),
    'str_hide_menu_tooltip' => get_string('hide_menu_tooltip', 'local_xpstore'), // Needs translation if missing.
    'str_show_menu_tooltip' => get_string('show_menu_tooltip', 'local_xpstore'),
    'str_menuvisible' => get_string('menuvisible', 'local_xpstore'),
    'str_menuhidden' => get_string('menuhidden', 'local_xpstore'),
    'help_menuvisibility' => $OUTPUT->help_icon('menuvisibility', 'local_xpstore'),
    'str_tiendaxp' => get_string('tiendaxp', 'local_xpstore'),
    'isediting' => $isediting,
    'str_edit' => get_string('edit', 'local_xpstore'),
    'str_addproduct' => get_string('addproduct', 'local_xpstore'),
    'olditemval' => $olditemval,

    'str_type' => get_string('type', 'local_xpstore'),
    'help_type' => $OUTPUT->help_icon('type', 'local_xpstore'),
    'help_label' => $OUTPUT->help_icon('label', 'local_xpstore'),
    'str_choosetype' => get_string('choosetype', 'local_xpstore'),
    'type_options' => $typeoptions,
    'etipo' => $etipo,

    'str_activity' => get_string('activity', 'local_xpstore'),
    'str_chooseactivity' => get_string('chooseactivity', 'local_xpstore'),
    'activity_options' => $activityoptions,
    'ecmid' => $ecmid,

    'str_cost' => get_string('cost', 'local_xpstore'),
    'ecosto' => $ecosto,

    'str_limit' => get_string('limit', 'local_xpstore'),
    'str_limitzero' => get_string('limitzero', 'local_xpstore'),
    'elimite' => $elimite,

    'str_category' => get_string('category', 'local_xpstore'),
    'ecat' => $ecat,

    'str_label' => get_string('label', 'local_xpstore'),
    'enombre' => $enombre,

    'str_gradepoints' => get_string('gradepoints', 'local_xpstore'),
    'evalor' => $evalor,

    'str_cancel' => get_string('cancel', 'local_xpstore'),
    'str_update' => get_string('update', 'local_xpstore'),
    'str_add' => get_string('add', 'local_xpstore'),

    'str_colorconfig' => get_string('colorconfig', 'local_xpstore'),
    'str_predefinedpalettes' => get_string('predefinedpalettes', 'local_xpstore'),
    'cpactual' => $cpactual,
    'cbactual' => $cbactual,
    'ciactual' => $ciactual,
    'ccactual' => $ccactual,

    'str_primarycolor' => get_string('primarycolor', 'local_xpstore'),
    'str_secondarycolor' => get_string('secondarycolor', 'local_xpstore'),
    'str_iconcolor' => get_string('iconcolor', 'local_xpstore'),
    'str_color_cat_icon' => get_string('color_cat_icon', 'local_xpstore'),

    'resetcolorsurl' => (new moodle_url($url, ['action' => 'resetcolors', 'sesskey' => sesskey()]))->out(false),
    'str_confirmresetcolors' => get_string('confirmresetcolors', 'local_xpstore'),
    'str_resetcolors' => get_string('resetcolors', 'local_xpstore'),
    'str_savecolors' => get_string('savecolors', 'local_xpstore'),
    'str_savechanges' => get_string('savechanges'),

    'has_categories' => $hascategories,
    'str_categoryicons' => get_string('categoryicons', 'local_xpstore'),
    'category_icon_options' => $categoryiconoptions,
    'str_saveicons' => get_string('saveicons', 'local_xpstore'),

    'str_widget_panel_title' => get_string('widget_panel_title', 'local_xpstore'),
    'str_widget_panel_desc' => get_string('widget_panel_desc', 'local_xpstore'),

    'iframestore' => $iframestore,
    'str_full_store' => get_string('full_store', 'local_xpstore'),

    'iframehistory' => $iframehistory,
    'str_history_button' => get_string('history_button', 'local_xpstore'),

    'category_iframe_options' => $categoryiframeoptions,
    'str_category_short' => get_string('category_short', 'local_xpstore'),

    'str_currentcatalog' => get_string('currentcatalog', 'local_xpstore'),
    'has_items' => $hasitems,
    'deleteallurl' => (new moodle_url($url, ['action' => 'deleteall', 'sesskey' => sesskey()]))->out(false),
    'str_confirmdeleteall' => get_string('confirmdeleteall', 'local_xpstore'),
    'str_deleteall' => get_string('deleteall', 'local_xpstore'),

    'str_coltype' => get_string('coltype', 'local_xpstore'),
    'str_colcategory' => get_string('colcategory', 'local_xpstore'),
    'str_colactivity' => get_string('colactivity', 'local_xpstore'),
    'str_colcost' => get_string('colcost', 'local_xpstore'),
    'str_collabel' => get_string('collabel', 'local_xpstore'),
    'str_colaction' => get_string('colaction', 'local_xpstore'),
    'str_norewardscreated' => get_string('norewardscreated', 'local_xpstore'),

    'catalog_items' => $catalogitems,
    'str_copysinglecard' => get_string('copysinglecard', 'local_xpstore'),
    'str_delete' => get_string('delete', 'local_xpstore'),
    'str_confirmdelete' => get_string('confirmdelete', 'local_xpstore'),
], $navdata);

$PAGE->requires->js_call_amd('local_xpstore/copywidget', 'init', [
    get_string('copyalert', 'local_xpstore'),
    'Error', // Simple string for error, or could use another string.
]);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_xpstore/config_page', $templatedata);
echo $OUTPUT->footer();
