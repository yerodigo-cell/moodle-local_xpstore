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
 * config.php
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
if (!local_xpstore_can_manage($context)) {
    throw new required_capability_exception($context, 'local/xpstore:manage', 'nopermissions', '');
}

$url = new moodle_url('/local/xpstore/config.php', ['id' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('configtitle', 'local_xpstore'));

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$tab = optional_param('tab', '', PARAM_TEXT);

$catalogkey = 'catalog_course_' . $courseid;

// Validates that a target cmid/gradeitem belongs to the given course.
$validatetarget = function (int $courseid, string $tipo, int $cmid) use ($DB): bool {
    if ($cmid <= 0) {
        return false;
    }
    if ($tipo === 'M') {
        return $DB->record_exists('grade_items', ['id' => $cmid, 'courseid' => $courseid, 'itemtype' => 'manual']);
    }
    if ($tipo === 'C') {
        return $DB->record_exists('grade_items', ['id' => $cmid, 'courseid' => $courseid, 'itemtype' => 'course']);
    }
    if (in_array($tipo, ['Q', 'A', 'F', 'G', 'S', 'U'], true)) {
        return $DB->record_exists('course_modules', ['id' => $cmid, 'course' => $courseid]);
    }
    return false;
};

if ($action === 'add' && confirm_sesskey()) {
    $tipo = strtoupper(required_param('tipo', PARAM_ALPHA));
    $cmidraw = required_param('cmid', PARAM_ALPHANUM);

    if ($tipo === 'G' && stripos($cmidraw, 'm') === 0) {
        $tipo = 'M';
        $cmidraw = substr($cmidraw, 1);
    } else if ($tipo === 'G' && stripos($cmidraw, 'c') === 0) {
        $tipo = 'C';
        $cmidraw = substr($cmidraw, 1);
    }
    $cmid = (int)$cmidraw;

    if (!$validatetarget($courseid, $tipo, $cmid)) {
        redirect($url, get_string('error_transaction', 'local_xpstore'), null, \core\output\notification::NOTIFY_ERROR);
    }

    // Prevent duplicate (tipo, cmid) entries in the same course catalog.
    $existingcatalog = local_xpstore_parse_catalog($courseid, true);
    if (isset($existingcatalog[$tipo . $cmid])) {
        redirect($url, get_string('duplicateproduct', 'local_xpstore'), null, \core\output\notification::NOTIFY_WARNING);
    }

    $costo = max(0, required_param('costo', PARAM_INT));
    $nombre = trim(required_param('nombre', PARAM_TEXT));
    $valornota = max(0.0, optional_param('valor_nota', 0.0, PARAM_FLOAT));
    $bonusmode = (optional_param('bonusmode', 0, PARAM_INT) === 1) ? 1 : 0;
    $categoria = trim(optional_param('categoria', '', PARAM_TEXT));
    $limite = max(0, optional_param('limite', 0, PARAM_INT));
    $requisito = max(0, optional_param('requisito', 0, PARAM_INT));
    if ($requisito > 0 && !$DB->record_exists('course_modules', ['id' => $requisito, 'course' => $courseid])) {
        $requisito = 0;
    }
    $hiddenwhenlocked = optional_param('hiddenwhenlocked', 0, PARAM_INT) ? 1 : 0;
    $minlevel = max(0, optional_param('minlevel', 0, PARAM_INT));
    $hidebylevel = (optional_param('hidebylevel', 1, PARAM_INT) === 1) ? 1 : 0;
    $icono = optional_param('icono', '', PARAM_ALPHANUMEXT);

    if (isset($_FILES['icono_file']) && $_FILES['icono_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploaderr = null;
        $savedicon = local_xpstore_save_card_image($courseid, $_FILES['icono_file'], '', $uploaderr);
        if ($savedicon === false) {
            redirect($url, $uploaderr, null, \core\output\notification::NOTIFY_ERROR);
        }
        $icono = $savedicon;
    } else {
        $icono = local_xpstore_clean_icon($icono, local_xpstore_get_default_card_icon($tipo));
    }

    $newitem = local_xpstore_serialize_catalog_item([
        'tipo' => $tipo,
        'cmid' => $cmid,
        'costo' => $costo,
        'nombre' => $nombre,
        'valornota' => $valornota,
        'categoria' => $categoria,
        'limite' => $limite,
        'requisito' => $requisito,
        'hiddenwhenlocked' => $hiddenwhenlocked,
        'icono' => $icono,
        'minlevel' => $minlevel,
        'hidebylevel' => $hidebylevel,
        'bonusmode' => $bonusmode,
    ]);

    $currentconfig = get_config('local_xpstore', $catalogkey) ?: '';
    $updated = trim($currentconfig) !== '' ? $currentconfig . ',' . $newitem : $newitem;
    set_config($catalogkey, $updated, 'local_xpstore');

    if ($tipo === 'S') {
        $groupname = ($nombre !== '') ? $nombre : 'VIP';
        $group = $DB->get_record('groups', ['courseid' => $courseid, 'name' => $groupname]);
        if (!$group) {
            require_once($CFG->dirroot . '/group/lib.php');
            $newgroup = new stdClass();
            $newgroup->courseid = $courseid;
            $newgroup->name = $groupname;
            $groupid = groups_create_group($newgroup);
        } else {
            $groupid = $group->id;
        }
        local_xpstore_apply_special_restriction($cmid, $groupid, $courseid);
    } else if ($tipo === 'U') {
        $productid = $tipo . $cmid;
        local_xpstore_apply_unlock_restriction($cmid, $productid, $courseid, $hiddenwhenlocked);
    } else if ($tipo === 'F') {
        local_xpstore_get_or_create_forum_role();
    }

    redirect($url, get_string('productadded', 'local_xpstore'));
}

if ($action === 'edit_save' && confirm_sesskey()) {
    $olditem = required_param('old_item', PARAM_RAW);
    $oldparsed = local_xpstore_parse_catalog_entry($olditem);

    $tipo = strtoupper(required_param('tipo', PARAM_ALPHA));
    $cmidraw = required_param('cmid', PARAM_ALPHANUM);

    if ($tipo === 'G' && stripos($cmidraw, 'm') === 0) {
        $tipo = 'M';
        $cmidraw = substr($cmidraw, 1);
    } else if ($tipo === 'G' && stripos($cmidraw, 'c') === 0) {
        $tipo = 'C';
        $cmidraw = substr($cmidraw, 1);
    }
    $cmid = (int)$cmidraw;

    if (!$validatetarget($courseid, $tipo, $cmid)) {
        redirect($url, get_string('error_transaction', 'local_xpstore'), null, \core\output\notification::NOTIFY_ERROR);
    }

    $existinglist = local_xpstore_parse_catalog($courseid, false);
    foreach ($existinglist as $entry) {
        if ($entry['rawitem'] !== trim($olditem) && $entry['key'] === ($tipo . $cmid)) {
            redirect($url, get_string('duplicateproduct', 'local_xpstore'), null, \core\output\notification::NOTIFY_WARNING);
        }
    }

    $costo = max(0, required_param('costo', PARAM_INT));
    $nombre = trim(required_param('nombre', PARAM_TEXT));
    $valornota = max(0.0, optional_param('valor_nota', 0.0, PARAM_FLOAT));
    $bonusmode = (optional_param('bonusmode', 0, PARAM_INT) === 1) ? 1 : 0;
    $categoria = trim(optional_param('categoria', '', PARAM_TEXT));
    $limite = max(0, optional_param('limite', 0, PARAM_INT));
    $requisito = max(0, optional_param('requisito', 0, PARAM_INT));
    if ($requisito > 0 && !$DB->record_exists('course_modules', ['id' => $requisito, 'course' => $courseid])) {
        $requisito = 0;
    }
    $hiddenwhenlocked = optional_param('hiddenwhenlocked', 0, PARAM_INT) ? 1 : 0;
    $minlevel = max(0, optional_param('minlevel', 0, PARAM_INT));
    $hidebylevel = (optional_param('hidebylevel', 1, PARAM_INT) === 1) ? 1 : 0;
    $oldicon = $oldparsed ? $oldparsed['icono'] : '';

    $icono = optional_param('icono', '', PARAM_ALPHANUMEXT);
    if (isset($_FILES['icono_file']) && $_FILES['icono_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploaderr = null;
        $savedicon = local_xpstore_save_card_image($courseid, $_FILES['icono_file'], $oldicon, $uploaderr);
        if ($savedicon === false) {
            redirect($url, $uploaderr, null, \core\output\notification::NOTIFY_ERROR);
        }
        $icono = $savedicon;
    } else {
        $icono = local_xpstore_clean_icon($icono, local_xpstore_get_default_card_icon($tipo));
        if (local_xpstore_is_custom_card_image($oldicon) && !local_xpstore_is_custom_card_image($icono)) {
            local_xpstore_delete_card_image($courseid, $oldicon);
        }
    }

    $newitem = local_xpstore_serialize_catalog_item([
        'tipo' => $tipo,
        'cmid' => $cmid,
        'costo' => $costo,
        'nombre' => $nombre,
        'valornota' => $valornota,
        'categoria' => $categoria,
        'limite' => $limite,
        'requisito' => $requisito,
        'hiddenwhenlocked' => $hiddenwhenlocked,
        'icono' => $icono,
        'minlevel' => $minlevel,
        'hidebylevel' => $hidebylevel,
        'bonusmode' => $bonusmode,
    ]);

    $currentconfig = get_config('local_xpstore', $catalogkey) ?: '';
    $items = array_filter(explode(',', $currentconfig), function ($v) {
        return trim($v) !== '';
    });

    foreach ($items as $key => $item) {
        if (trim($item) === trim($olditem)) {
            $items[$key] = $newitem;
            break;
        }
    }

    set_config($catalogkey, implode(',', $items), 'local_xpstore');

    if ($tipo === 'S') {
        require_once($CFG->dirroot . '/group/lib.php');
        $groupname = ($nombre !== '') ? $nombre : 'VIP';
        $oldgroupname = ($oldparsed && $oldparsed['tipo'] === 'S' && (int)$oldparsed['cmid'] === $cmid)
            ? (($oldparsed['nombre'] !== '') ? $oldparsed['nombre'] : 'VIP')
            : '';
        $oldgroup = ($oldgroupname !== '' && $oldgroupname !== $groupname)
            ? $DB->get_record('groups', ['courseid' => $courseid, 'name' => $oldgroupname])
            : null;
        $oldgroupid = $oldgroup ? (int)$oldgroup->id : 0;

        $group = $DB->get_record('groups', ['courseid' => $courseid, 'name' => $groupname]);
        if (!$group) {
            $newgroup = new stdClass();
            $newgroup->courseid = $courseid;
            $newgroup->name = $groupname;
            $groupid = groups_create_group($newgroup);
        } else {
            $groupid = $group->id;
        }

        // Migrate existing 'S' buyers into the renamed group and update stored rewardname.
        $existingbuyers = $DB->get_records_select(
            'local_xpstore_gastos',
            'itemtype = ? AND itemid = ? AND (courseid = ? OR courseid = 0)',
            ['S', $cmid, $courseid]
        );
        foreach ($existingbuyers as $b) {
            if (!groups_is_member($groupid, (int)$b->userid)) {
                groups_add_member($groupid, (int)$b->userid);
            }
            if ($oldgroup && $oldgroup->id != $groupid && groups_is_member($oldgroup->id, (int)$b->userid)) {
                groups_remove_member($oldgroup->id, (int)$b->userid);
            }
            $b->rewardname = $groupname;
            $b->courseid = $courseid;
            $DB->update_record('local_xpstore_gastos', $b);
        }

        local_xpstore_apply_special_restriction($cmid, $groupid, $courseid, $oldgroupid);
    } else if ($tipo === 'U') {
        $productid = $tipo . $cmid;
        local_xpstore_apply_unlock_restriction($cmid, $productid, $courseid, $hiddenwhenlocked);
    } else if ($tipo === 'F') {
        local_xpstore_get_or_create_forum_role();
    } else if (in_array($tipo, ['G', 'M', 'C'], true)) {
        // Update stored gradevalue/bonusmode on existing purchases of this bonus and re-sync grades.
        $existingbonuses = $DB->get_records_select(
            'local_xpstore_gastos',
            'itemtype = ? AND itemid = ? AND (courseid = ? OR courseid = 0)',
            [$tipo, $cmid, $courseid]
        );
        $affectedusers = [];
        foreach ($existingbonuses as $eb) {
            $eb->courseid = $courseid;
            $eb->gradevalue = (float)$valornota;
            $eb->bonusmode = $bonusmode;
            if ($nombre !== '') {
                $eb->rewardname = $nombre;
            }
            $DB->update_record('local_xpstore_gastos', $eb);
            $affectedusers[(int)$eb->userid] = (int)$eb->userid;
        }

        if ($tipo === 'C') {
            foreach ($affectedusers as $uid) {
                local_xpstore_sync_course_grade_bonus($courseid, $uid, false);
            }
        } else if ($tipo === 'M') {
            $gi = $DB->get_record('grade_items', ['id' => $cmid, 'courseid' => $courseid]);
            if ($gi) {
                foreach ($affectedusers as $uid) {
                    local_xpstore_sync_item_grade_bonus($courseid, $gi, $uid, false);
                }
            }
        } else if ($tipo === 'G') {
            $cm = $DB->get_record('course_modules', ['id' => $cmid, 'course' => $courseid]);
            if ($cm) {
                $itemmodule = $DB->get_field('modules', 'name', ['id' => $cm->module]);
                $gradeitems = $itemmodule
                    ? local_xpstore_get_mod_grade_items($courseid, $itemmodule, $cm->instance)
                    : [];
                foreach ($affectedusers as $uid) {
                    foreach ($gradeitems as $gi) {
                        local_xpstore_sync_item_grade_bonus($courseid, $gi, $uid, false);
                    }
                }
            }
        }
    }

    redirect($url, get_string('productupdated', 'local_xpstore'));
}

if ($action === 'delete' && confirm_sesskey()) {
    $itemtodelete = required_param('item', PARAM_RAW);
    $currentconfig = get_config('local_xpstore', $catalogkey) ?: '';

    $items = array_filter(explode(',', $currentconfig), function ($v) {
        return trim($v) !== '';
    });
    $newitems = [];
    $deletedone = false;
    foreach ($items as $i) {
        if (!$deletedone && trim($i) === trim($itemtodelete)) {
            $deletedone = true;
            $parseddel = local_xpstore_parse_catalog_entry($i);
            if ($parseddel && $parseddel['icono'] !== '') {
                local_xpstore_delete_card_image($courseid, $parseddel['icono']);
            }
        } else {
            $newitems[] = trim($i);
        }
    }

    set_config($catalogkey, implode(',', $newitems), 'local_xpstore');
    redirect($url, get_string('productdeleted', 'local_xpstore'));
}

if ($action === 'bulkdelete' && confirm_sesskey()) {
    $itemstodelete = optional_param_array('bulkdeleteitems', [], PARAM_RAW);
    if (!empty($itemstodelete)) {
        $deleteset = array_map('trim', $itemstodelete);
        $currentconfig = get_config('local_xpstore', $catalogkey) ?: '';
        $items = array_filter(explode(',', $currentconfig), function ($v) {
            return trim($v) !== '';
        });
        $newitems = [];
        foreach ($items as $i) {
            if (!in_array(trim($i), $deleteset, true)) {
                $newitems[] = trim($i);
            } else {
                $parseddel = local_xpstore_parse_catalog_entry($i);
                if ($parseddel && $parseddel['icono'] !== '') {
                    local_xpstore_delete_card_image($courseid, $parseddel['icono']);
                }
            }
        }
        set_config($catalogkey, implode(',', $newitems), 'local_xpstore');
        redirect($url, get_string('productdeleted', 'local_xpstore'));
    } else {
        redirect($url);
    }
}

if ($action === 'deleteall' && confirm_sesskey()) {
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'local_xpstore', 'cardimage');
    set_config($catalogkey, '', 'local_xpstore');
    redirect($url, get_string('deletedall', 'local_xpstore'));
}

if ($action === 'reorder' && confirm_sesskey()) {
    $rawitems = optional_param_array('rawitems', [], PARAM_RAW);
    if (!empty($rawitems)) {
        $currentconfig = get_config('local_xpstore', $catalogkey) ?: '';
        $existing = array_values(array_filter(array_map('trim', explode(',', $currentconfig))));
        $remaining = $existing;
        $reordered = [];

        // Only accept exact items that already exist in this course's catalog.
        foreach ($rawitems as $candidate) {
            $cand = trim($candidate);
            $pos = array_search($cand, $remaining, true);
            if ($pos !== false) {
                $reordered[] = $cand;
                unset($remaining[$pos]);
            }
        }
        foreach ($remaining as $leftover) {
            $reordered[] = $leftover;
        }

        if (!empty($reordered)) {
            set_config($catalogkey, implode(',', $reordered), 'local_xpstore');
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true]);
    die();
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
    $cp = local_xpstore_clean_hex_color(required_param('color_primary', PARAM_TEXT), '#0056D2');
    $cb = local_xpstore_clean_hex_color(required_param('color_secondary', PARAM_TEXT), '#00C9A7');
    $ci = local_xpstore_clean_hex_color(required_param('color_icon', PARAM_TEXT), $cp);
    $cc = local_xpstore_clean_hex_color(required_param('color_cat_icon', PARAM_TEXT), $cp);

    set_config('color_primary_course_' . $courseid, $cp, 'local_xpstore');
    set_config('color_secondary_course_' . $courseid, $cb, 'local_xpstore');
    set_config('color_icon_course_' . $courseid, $ci, 'local_xpstore');
    set_config('color_cat_icon_course_' . $courseid, $cc, 'local_xpstore');

    $caticons = [];
    $parsedlist = local_xpstore_parse_catalog($courseid, false);
    $updateditems = [];

    foreach ($parsedlist as $idx => $entry) {
        $cat = ($entry['categoria'] !== '') ? $entry['categoria'] : get_string('defaultcategory', 'local_xpstore');
        $inputname = 'caticon_' . md5($cat);
        $iconval = optional_param($inputname, null, PARAM_ALPHANUMEXT);
        if ($iconval !== null) {
            $cleanedcaticon = local_xpstore_clean_icon($iconval, 'trophy');
            if (!local_xpstore_is_custom_card_image($cleanedcaticon)) {
                $caticons[$cat] = $cleanedcaticon;
            }
        }

        $itemkey = $entry['tipo'] . $entry['cmid'] . '_' . $idx;
        $oldcardicon = $entry['icono'];
        $cardiconval = optional_param('cardicon_' . $itemkey, null, PARAM_ALPHANUMEXT);
        $fileinputname = 'cardiconfile_' . $itemkey;

        if (isset($_FILES[$fileinputname]) && $_FILES[$fileinputname]['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploaderr = null;
            $savedicon = local_xpstore_save_card_image($courseid, $_FILES[$fileinputname], $oldcardicon, $uploaderr);
            if ($savedicon !== false) {
                $cardiconval = $savedicon;
            } else {
                \core\notification::add($uploaderr, \core\output\notification::NOTIFY_ERROR);
            }
        } else if (
            $cardiconval !== null && $cardiconval !== '' &&
            !local_xpstore_is_custom_card_image($cardiconval) &&
            local_xpstore_is_custom_card_image($oldcardicon)
        ) {
            local_xpstore_delete_card_image($courseid, $oldcardicon);
        }

        if ($cardiconval !== null && $cardiconval !== '') {
            $entry['icono'] = local_xpstore_clean_icon($cardiconval, $oldcardicon);
        }

        $updateditems[] = local_xpstore_serialize_catalog_item($entry);
    }

    set_config($catalogkey, implode(',', $updateditems), 'local_xpstore');
    set_config('cat_icons_course_' . $courseid, json_encode($caticons), 'local_xpstore');
    redirect(new moodle_url($url, ['tab' => 'settings']), get_string('changessaved'));
}

$cpactual = local_xpstore_clean_hex_color(
    get_config('local_xpstore', 'color_primary_course_' . $courseid) ?: '#0056D2',
    '#0056D2'
);
$cbactual = local_xpstore_clean_hex_color(
    get_config('local_xpstore', 'color_secondary_course_' . $courseid) ?: '#00C9A7',
    '#00C9A7'
);
$ciactual = local_xpstore_clean_hex_color(
    get_config('local_xpstore', 'color_icon_course_' . $courseid) ?: $cpactual,
    $cpactual
);
$ccactual = local_xpstore_clean_hex_color(
    get_config('local_xpstore', 'color_cat_icon_course_' . $courseid) ?: $cpactual,
    $cpactual
);

$isediting = false;
$etipo = '';
$ecmid = 0;
$ecosto = '';
$ecat = '';
$enombre = '';
$evalor = '0';
$ebonusmode = 0;
$elimite = '0';
$erequisito = 0;
$ehiddenwhenlocked = 0;
$eicono = 'bolt';
$eminlevel = '0';
$ehidebylevel = 1;
$olditemval = '';

if ($action === 'load_edit') {
    $itemtoedit = required_param('item', PARAM_RAW);
    $parsededit = local_xpstore_parse_catalog_entry($itemtoedit);

    if ($parsededit) {
        $tipochar = $parsededit['tipo'];
        $etipo = $tipochar;
        if ($etipo === 'M') {
            $etipo = 'G';
            $ecmid = 'm' . $parsededit['cmid'];
        } else if ($etipo === 'C') {
            $etipo = 'G';
            $ecmid = 'c' . $parsededit['cmid'];
        } else {
            $ecmid = (int)$parsededit['cmid'];
        }
        $ecosto = (string)$parsededit['costo'];
        $enombre = $parsededit['nombre'];
        $evalor = $parsededit['valornota'];
        $ebonusmode = (int)$parsededit['bonusmode'];
        $ecat = $parsededit['categoria'];
        $elimite = (string)$parsededit['limite'];
        $erequisito = (int)$parsededit['requisito'];
        $ehiddenwhenlocked = (int)$parsededit['hiddenwhenlocked'];
        $eicono = ($parsededit['icono'] !== '')
            ? $parsededit['icono']
            : local_xpstore_get_default_card_icon($tipochar);
        $eminlevel = (string)$parsededit['minlevel'];
        $ehidebylevel = (int)$parsededit['hidebylevel'];
        $olditemval = $parsededit['rawitem'];
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
    'U' => get_string('type_u', 'local_xpstore'),
];
foreach ($availabletypes as $val => $label) {
    $typeoptions[] = [
        'value' => $val,
        'valuelower' => strtolower($val),
        'label' => $label,
        'selected' => ($etipo === $val),
    ];
}

$activityoptions = [];
$requirementoptions = [
    ['id' => 0, 'name' => get_string('none'), 'selected' => ($erequisito == 0)],
];
$modinfo = get_fast_modinfo($courseid);
global $OUTPUT;

$getcmiconhtml = function ($cm) {
    $iconurl = $cm->get_icon_url()->out();
    if (in_array($cm->modname, ['subsection', 'hvp', 'h5pactivity'])) {
        return '<span class="xpstore-icon-container" style="width: 28px; height: 28px; min-width: 28px; max-width: 28px; ' .
               'display: inline-block; text-align: center; line-height: 28px; margin: 0 8px 0 0; padding: 0; ' .
               'vertical-align: middle; overflow: hidden;">' .
               '<img src="' . s($iconurl) . '" alt="" style="vertical-align: middle; width: 24px; height: 24px;">' .
               '</span>';
    } else {
        $purpose = 'default';
        if (method_exists($cm, 'get_purpose')) {
            $moodlepurpose = $cm->get_purpose();
        }

        $purposes = [
            'assign' => 'assessment', 'quiz' => 'assessment', 'workshop' => 'assessment',
            'certificatebeautiful' => 'assessment', 'coursecertificate' => 'assessment',
            'choice' => 'communication', 'feedback' => 'communication', 'chat' => 'communication',
            'bigbluebuttonbn' => 'communication', 'zoom' => 'communication',
            'book' => 'content', 'folder' => 'content', 'label' => 'content', 'page' => 'content',
            'qbank' => 'content', 'resource' => 'content', 'url' => 'content', 'emubook' => 'content',
            'videotrack' => 'content', 'codeframe' => 'content',
            'data' => 'collaboration', 'database' => 'collaboration', 'forum' => 'collaboration',
            'glossary' => 'collaboration', 'wiki' => 'collaboration', 'diary' => 'collaboration',
            'imscp' => 'interactive_content', 'lesson' => 'interactive_content', 'scorm' => 'interactive_content',
            'attendance' => 'administration', 'lti' => 'other',
        ];

        $purposecolors = [
            'assessment' => '#ec4899',
            'communication' => '#fe5701',
            'content' => '#00a5ad',
            'collaboration' => '#8b5cf6',
            'interactive_content' => '#3c73b8',
            'administration' => '#5d63f6',
            'other' => '#6c757d',
            'default' => '#6c757d',
        ];

        if (isset($purposes[$cm->modname])) {
            $purpose = $purposes[$cm->modname];
        } else if (isset($moodlepurpose) && isset($purposecolors[$moodlepurpose])) {
            $purpose = $moodlepurpose;
        }

        $bgcolor = isset($purposecolors[$purpose]) ? $purposecolors[$purpose] : $purposecolors['default'];

        return '<span class="xpstore-icon-container" ' .
               'style="width: 28px; height: 28px; min-width: 28px; max-width: 28px; display: inline-block; ' .
               'text-align: center; line-height: 28px; border-radius: 6px; margin: 0 8px 0 0; padding: 0; ' .
               'vertical-align: middle; overflow: hidden;">' .
               '<span style="background-color: ' . $bgcolor . '; display: inline-block; ' .
               'vertical-align: middle; width: 26px; height: 26px; min-width: 26px; min-height: 26px; ' .
               '-webkit-mask-image: url(' . s($iconurl) . '); -webkit-mask-size: 100%; ' .
               '-webkit-mask-position: center; -webkit-mask-repeat: no-repeat; ' .
               'mask-image: url(' . s($iconurl) . '); mask-size: 100%; mask-position: center; ' .
               'mask-repeat: no-repeat;"></span></span>';
    }
};

require_once($CFG->libdir . '/gradelib.php');
$coursegradeitem = \grade_item::fetch_course_item($courseid);
if ($coursegradeitem && (int)$coursegradeitem->gradetype === 1) {
    $coursetotalname = local_xpstore_get_course_grade_item_name($coursegradeitem->id);
    $courseiconhtml = '<span class="xpstore-icon-container" style="width: 28px; height: 28px; min-width: 28px; max-width: 28px; ' .
                      'display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; ' .
                      'margin: 0 8px 0 0; padding: 0; vertical-align: middle; overflow: hidden; ' .
                      'background-color: #f59e0b; color: #fff;">' .
                      '<i class="fa fa-trophy" style="font-size: 14px; line-height: 1; margin: 0; padding: 0;"></i>' .
                      '</span>';
    $activityoptions[] = [
        'id' => 'c' . $coursegradeitem->id,
        'modname' => 'manual',
        'gradable' => 1,
        'name' => $courseiconhtml . $coursetotalname,
        'selected' => ('c' . $coursegradeitem->id == $ecmid),
    ];
}

// Preload numeric-gradable mod items in a single query.
$modvaluegradeitems = $DB->get_records_select(
    'grade_items',
    "courseid = ? AND itemtype = 'mod' AND gradetype = 1",
    [$courseid],
    '',
    'id, itemmodule, iteminstance'
);
$gradablemodmap = [];
foreach ($modvaluegradeitems as $mgi) {
    $gradablemodmap[$mgi->itemmodule . '_' . (int)$mgi->iteminstance] = true;
}

foreach ($modinfo->get_cms() as $cm) {
    if ($cm->has_view() && !in_array($cm->modname, ['label', 'resource', 'contentview', 'qbank'])) {
        $iconhtml = $getcmiconhtml($cm);
        $namehtml = $iconhtml . $cm->get_formatted_name();
        $isgradable = !empty($gradablemodmap[$cm->modname . '_' . (int)$cm->instance]) ? 1 : 0;

        $activityoptions[] = [
            'id' => $cm->id,
            'modname' => $cm->modname,
            'gradable' => $isgradable,
            'name' => $namehtml,
            'selected' => ($cm->id == $ecmid),
        ];
        $requirementoptions[] = [
            'id' => $cm->id,
            'name' => $namehtml,
            'selected' => ($cm->id == $erequisito),
        ];
    }
}

$manualitems = $DB->get_records('grade_items', ['courseid' => $courseid, 'itemtype' => 'manual']);
foreach ($manualitems as $item) {
    if ((int)$item->gradetype !== 1) {
        continue;
    }
    try {
        $iconurl = $OUTPUT->image_url('i/manual_item')->out();
    } catch (Exception $e) {
        $iconurl = '';
    }

    $iconhtml = '<span class="xpstore-icon-container" style="width: 28px; height: 28px; min-width: 28px; max-width: 28px; ' .
                'display: inline-block; text-align: center; line-height: 28px; margin: 0 8px 0 0; padding: 0; ' .
                'vertical-align: middle; overflow: hidden;">' .
                '<img src="' . s($iconurl) . '" alt="" style="vertical-align: middle; width: 16px; height: 16px;">' .
                '</span>';
    $namehtml = $iconhtml . format_string($item->itemname);

    $activityoptions[] = [
        'id' => 'm' . $item->id,
        'modname' => 'manual',
        'gradable' => 1,
        'name' => $namehtml,
        'selected' => ('m' . $item->id == $ecmid),
    ];
}

$parsedcataloglist = local_xpstore_parse_catalog($courseid, false);
$categoriasunicas = [];
$hascategories = false;
$categoryiconoptions = [];
$categoryiframeoptions = [];
$cardiconoptions = [];
$catalogitems = [];
$hasitems = !empty($parsedcataloglist);

$sm = get_string_manager();
$strcustomimage = $sm->string_exists('customimage', 'local_xpstore')
    ? get_string('customimage', 'local_xpstore') : 'Imagen personalizada';
$struploadimage = $sm->string_exists('uploadimage', 'local_xpstore')
    ? get_string('uploadimage', 'local_xpstore') : 'Subir imagen';
$strmaximagesize = $sm->string_exists('maximagesize', 'local_xpstore')
    ? get_string('maximagesize', 'local_xpstore') : 'Máx. 256 KB (PNG, JPG, GIF, WEBP)';
$strerrorimagesize = $sm->string_exists('error_imagesize', 'local_xpstore')
    ? get_string('error_imagesize', 'local_xpstore') : 'La imagen supera el tamaño máximo permitido de 256 KB.';

$availableicons = local_xpstore_get_available_icons();
$eiscustomimage = local_xpstore_is_custom_card_image($eicono);
$eiconourl = $eiscustomimage ? local_xpstore_get_card_image_url($courseid, $eicono) : '';
if ($eiscustomimage && !$eiconourl) {
    $eicono = local_xpstore_get_default_card_icon($etipo ?: 'Q');
    $eiscustomimage = false;
}
$formiconoptions = [];
foreach ($availableicons as $icoval => $icolabel) {
    $formiconoptions[] = [
        'value' => $icoval,
        'label' => $icolabel,
        'selected' => ($eicono === $icoval),
    ];
}
$eiconolabel = $eiscustomimage ? $strcustomimage : ($availableicons[$eicono] ?? $eicono);

$cms = $modinfo->get_cms();

if ($hasitems) {
    foreach ($parsedcataloglist as $entry) {
        $cat = ($entry['categoria'] !== '') ? $entry['categoria'] : get_string('defaultcategory', 'local_xpstore');
        $categoriasunicas[$cat] = $cat;
    }

    if (!empty($categoriasunicas)) {
        $hascategories = true;
        $savedicons = json_decode(get_config('local_xpstore', 'cat_icons_course_' . $courseid) ?: '', true) ?: [];

        foreach ($categoriasunicas as $catname) {
            $rawicon = $savedicons[$catname] ?? 'trophy';
            $currenticon = local_xpstore_clean_icon($rawicon, 'trophy');
            if (local_xpstore_is_custom_card_image($currenticon)) {
                $currenticon = 'trophy';
            }
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

    foreach ($parsedcataloglist as $idx => $entry) {
        $tipo = $entry['tipo'];
        $cid = (int)$entry['cmid'];
        $cost = (int)$entry['costo'];
        $name = $entry['nombre'];
        $val = $entry['valornota'];
        $cat = ($entry['categoria'] !== '') ? $entry['categoria'] : get_string('defaultcategory', 'local_xpstore');
        $limiteactual = (int)$entry['limite'];
        $requisito = (int)$entry['requisito'];
        $minlevelactual = (int)$entry['minlevel'];
        $hidebylevelactual = (int)$entry['hidebylevel'];
        $bonusmodeactual = (int)$entry['bonusmode'];

        $defaultcardicon = local_xpstore_get_default_card_icon($tipo);
        $cardicon = ($entry['icono'] !== '') ? local_xpstore_clean_icon($entry['icono'], $defaultcardicon) : $defaultcardicon;
        $iscustomimg = local_xpstore_is_custom_card_image($cardicon);
        $cardimgurl = $iscustomimg ? local_xpstore_get_card_image_url($courseid, $cardicon) : '';
        if ($iscustomimg && !$cardimgurl) {
            $cardicon = $defaultcardicon;
            $iscustomimg = false;
        }

        $iconhtml = '';
        if ($tipo !== 'M' && $tipo !== 'C' && (!isset($cms[$cid]) || !empty($cms[$cid]->deletioninprogress))) {
            continue;
        }
        if ($tipo === 'M') {
            $gi = $DB->get_record('grade_items', ['id' => $cid, 'courseid' => $courseid, 'itemtype' => 'manual']);
            if (!$gi) {
                continue;
            }
            $realname = format_string($gi->itemname);
            $labeltipo = get_string('type_g', 'local_xpstore');
            try {
                $iconurl = $OUTPUT->image_url('i/manual_item')->out();
                $iconhtml = '<span class="xpstore-icon-container" ' .
                            'style="width: 28px; height: 28px; min-width: 28px; max-width: 28px; ' .
                            'display: inline-block; text-align: center; line-height: 28px; ' .
                            'margin: 0 8px 0 0; padding: 0; ' .
                            'vertical-align: middle; overflow: hidden;">' .
                            '<img src="' . s($iconurl) . '" alt="" style="vertical-align: middle; width: 16px; height: 16px;">' .
                            '</span>';
            } catch (Exception $e) {
                $iconhtml = '';
            }
        } else if ($tipo === 'C') {
            $gi = $DB->get_record('grade_items', ['id' => $cid, 'courseid' => $courseid, 'itemtype' => 'course']);
            if (!$gi) {
                continue;
            }
            $realname = local_xpstore_get_course_grade_item_name($cid);
            $labeltipo = get_string('type_g', 'local_xpstore');
            $iconhtml = '<span class="xpstore-icon-container" ' .
                        'style="width: 28px; height: 28px; min-width: 28px; max-width: 28px; ' .
                        'display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; ' .
                        'margin: 0 8px 0 0; padding: 0; vertical-align: middle; overflow: hidden; ' .
                        'background-color: #f59e0b; color: #fff;">' .
                        '<i class="fa fa-trophy" style="font-size: 14px; line-height: 1; margin: 0; padding: 0;"></i>' .
                        '</span>';
        } else {
            $cm = $cms[$cid];
            $realname = format_string($cm->name);
            $labeltipo = get_string('type_' . strtolower($tipo), 'local_xpstore');
            $iconhtml = $getcmiconhtml($cm);
        }
        $realnamehtml = $iconhtml . $realname;

        $reqname = '';
        if ($requisito > 0) {
            if (isset($cms[$requisito]) && empty($cms[$requisito]->deletioninprogress)) {
                $reqname = format_string($cms[$requisito]->name);
            } else {
                $reqname = get_string('activitydeleted', 'local_xpstore');
            }
        }

        global $CFG;
        $widgeturl = $CFG->wwwroot . "/local/xpstore/widget.php?id={$courseid}&tipo={$tipo}&cmid={$cid}";
        $iframecode = '<iframe src="' . $widgeturl . '" ' .
            'style="width: 280px !important; max-width: 100%; height: 350px !important; border: none; ' .
            'overflow: hidden; border-radius: 15px; display: inline-block; margin: 10px;" ' .
            'scrolling="no" allowtransparency="true"></iframe>';

        $cardiconlist = [];
        foreach ($availableicons as $icoval => $icolabel) {
            $cardiconlist[] = [
                'value' => $icoval,
                'label' => $icolabel,
                'selected' => ($cardicon === $icoval),
            ];
        }

        $cardiconoptions[] = [
            'itemkey' => $tipo . $cid . '_' . $idx,
            'cardlabel' => ($name !== '') ? $name : $realname,
            'realname' => $realname,
            'tipolower' => (in_array($tipo, ['M', 'C'], true) ? 'g' : strtolower($tipo)),
            'labeltipo' => $labeltipo,
            'currenticon' => $cardicon,
            'is_custom_image' => $iscustomimg,
            'icon_url' => $cardimgurl,
            'icons' => $cardiconlist,
        ];

        $rawitem = $entry['rawitem'];
        $catalogitems[] = [
            'tipolower' => (in_array($tipo, ['M', 'C'], true) ? 'g' : strtolower($tipo)),
            'labeltipo' => $labeltipo,
            'is_unlock' => ($tipo === 'U'),
            'hiddenwhenlocked' => (int)$entry['hiddenwhenlocked'],
            'cat' => $cat,
            'realname' => $realname,
            'realnamehtml' => $realnamehtml,
            'cost' => $cost,
            'has_item_limit' => ($limiteactual > 0),
            'limiteactual' => $limiteactual,
            'displayname' => ($name !== '') ? $name : $realname,
            'icon' => $cardicon,
            'is_custom_image' => $iscustomimg,
            'icon_url' => $cardimgurl,
            'is_grade_boost' => in_array($tipo, ['G', 'M', 'C'], true),
            'bonusmode_wait' => ($bonusmodeactual === 1),
            'val' => $val,
            'has_req' => ($requisito > 0),
            'req_name' => $reqname,
            'has_minlevel' => ($minlevelactual > 0),
            'minlevel' => $minlevelactual,
            'hidebylevel' => ($hidebylevelactual === 1),
            'iframecode' => $iframecode,
            'rawitem' => $rawitem,
            'editurl' => (new moodle_url($url, ['action' => 'load_edit', 'item' => $rawitem]))->out(false),
            'deleteurl' => (new moodle_url($url, ['action' => 'delete', 'sesskey' => sesskey(), 'item' => $rawitem]))->out(false),
        ];
    }

    $hasitems = !empty($catalogitems);
}

global $CFG;
$urlstore = $CFG->wwwroot . "/local/xpstore/widget_category.php?id={$courseid}";
$iframestore = '<iframe src="' . $urlstore . '" width="100%" height="800" ' .
    'style="border: none; border-radius: 15px; overflow: hidden;" ' .
    'scrolling="no" allowfullscreen allowtransparency="true"></iframe>';

$urlhistoryw = $CFG->wwwroot . "/local/xpstore/widget_history.php?id={$courseid}";
$iframehistory = '<iframe src="' . $urlhistoryw . '" width="100%" height="150" ' .
    'style="border: none; overflow: hidden;" scrolling="no"></iframe>';

$urlbalancew = $CFG->wwwroot . "/local/xpstore/widget_balance.php?id={$courseid}";
$iframebalance = '<iframe src="' . $urlbalancew . '" width="100%" height="180" ' .
    'style="border: none; overflow: hidden;" scrolling="no"></iframe>';

$menuvisible = get_config('local_xpstore', 'show_menu_course_' . $courseid);
if ($menuvisible === false) {
    $menuvisible = '0';
}

$activetab = ($tab === 'settings' || in_array($action, ['savesettings', 'resetcolors', 'togglemenu'], true))
    ? 'settings'
    : 'products';

$navdata = local_xpstore_get_navigation_data($courseid, $activetab);
$navdata['isteacher'] = true;

$templatedata = array_merge([
    'str_configtitle' => get_string('configtitle', 'local_xpstore'),
    'url' => $url->out(false),
    'sesskey' => sesskey(),
    'menu_is_visible' => ($menuvisible === '1'),
    'str_hide_menu_tooltip' => get_string('hide_menu_tooltip', 'local_xpstore'),
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

    'str_requirement' => $sm->string_exists('requirement', 'local_xpstore') ?
        get_string('requirement', 'local_xpstore') : 'Requisito',
    'str_chooserequirement' => $sm->string_exists('chooserequirement', 'local_xpstore') ?
        get_string('chooserequirement', 'local_xpstore') : 'Elige un requisito',
    'str_requires' => $sm->string_exists('requires', 'local_xpstore') ?
        get_string('requires', 'local_xpstore') : 'Requiere',
    'str_requires_short' => $sm->string_exists('requires_short', 'local_xpstore') ?
        get_string('requires_short', 'local_xpstore') : 'Requiere',
    'help_requirement' => $OUTPUT->help_icon('requirement', 'local_xpstore'),
    'help_hiddenwhenlocked' => $OUTPUT->help_icon('hiddenwhenlocked', 'local_xpstore'),
    'requirement_options' => $requirementoptions,
    'erequisito' => $erequisito,
    'ehiddenwhenlocked' => $ehiddenwhenlocked,

    'str_hideactivity' => get_string('hideactivity', 'local_xpstore'),
    'str_showrestricted' => get_string('showrestricted', 'local_xpstore'),
    'str_hidecompletely' => get_string('hidecompletely', 'local_xpstore'),

    'str_minlevel' => $sm->string_exists('minlevel', 'local_xpstore') ?
        get_string('minlevel', 'local_xpstore') : 'Nivel mínimo (Level Up!)',
    'help_minlevel' => $OUTPUT->help_icon('minlevel', 'local_xpstore'),
    'str_minlevelzero' => $sm->string_exists('minlevelzero', 'local_xpstore') ?
        get_string('minlevelzero', 'local_xpstore') : '0 = Cualquier nivel',
    'str_minlevel_short' => $sm->string_exists('minlevel_short', 'local_xpstore') ?
        get_string('minlevel_short', 'local_xpstore') : 'Nivel mín.',
    'str_levelvisibility' => $sm->string_exists('levelvisibility', 'local_xpstore') ?
        get_string('levelvisibility', 'local_xpstore') : 'Si no alcanza el nivel',
    'str_hidecardbylevel' => $sm->string_exists('hidecardbylevel', 'local_xpstore') ?
        get_string('hidecardbylevel', 'local_xpstore') : 'Ocultar tarjeta hasta alcanzar el nivel',
    'str_showlockedbylevel' => $sm->string_exists('showlockedbylevel', 'local_xpstore') ?
        get_string('showlockedbylevel', 'local_xpstore') : 'Mostrar bloqueada (Requiere Nivel X)',
    'eminlevel' => $eminlevel,
    'ehidebylevel' => ($ehidebylevel === 1),
    'has_eminlevel' => ((int)$eminlevel > 0),

    'str_cost' => get_string('cost', 'local_xpstore'),
    'ecosto' => $ecosto,

    'str_limit' => get_string('limit', 'local_xpstore'),
    'str_limitzero' => get_string('limitzero', 'local_xpstore'),
    'elimite' => $elimite,

    'str_category' => get_string('category', 'local_xpstore'),
    'ecat' => $ecat,

    'str_label' => get_string('label', 'local_xpstore'),
    'enombre' => $enombre,

    'str_cardicon' => $sm->string_exists('cardicon', 'local_xpstore') ?
        get_string('cardicon', 'local_xpstore') : 'Icono de la Tarjeta',
    'eicono' => $eicono,
    'eis_custom_image' => $eiscustomimage,
    'eiconourl' => $eiconourl,
    'eiconolabel' => $eiconolabel,
    'form_icon_options' => $formiconoptions,
    'str_uploadimage' => $struploadimage,
    'str_maximagesize' => $strmaximagesize,
    'str_errorimagesize' => $strerrorimagesize,
    'str_customimage' => $strcustomimage,

    'str_gradepoints' => get_string('gradepoints', 'local_xpstore'),
    'evalor' => $evalor,
    'ebonusmode' => ($ebonusmode === 1),
    'str_bonusmode' => get_string('bonusmode', 'local_xpstore'),
    'help_bonusmode' => $OUTPUT->help_icon('bonusmode', 'local_xpstore'),
    'str_bonusmode_immediate' => get_string('bonusmode_immediate', 'local_xpstore'),
    'str_bonusmode_wait' => get_string('bonusmode_wait', 'local_xpstore'),

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
    'str_cardicons' => $sm->string_exists('cardicons', 'local_xpstore') ?
        get_string('cardicons', 'local_xpstore') : 'Iconos de Tarjetas',
    'card_icon_options' => $cardiconoptions,
    'str_saveicons' => get_string('saveicons', 'local_xpstore'),

    'str_widget_panel_title' => get_string('widget_panel_title', 'local_xpstore'),
    'str_widget_panel_desc' => get_string('widget_panel_desc', 'local_xpstore'),

    'iframestore' => $iframestore,
    'str_full_store' => get_string('full_store', 'local_xpstore'),

    'iframehistory' => $iframehistory,
    'str_history_button' => get_string('history_button', 'local_xpstore'),

    'iframebalance' => $iframebalance,
    'str_balance_button' => get_string('balance_button', 'local_xpstore'),

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
    'Error',
]);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_xpstore/config_page', $templatedata);
echo $OUTPUT->footer();
