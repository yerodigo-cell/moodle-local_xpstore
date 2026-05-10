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

defined('MOODLE_INTERNAL') || die();

// --- NAVEGACIÓN (FUNCIÓN ÚNICA Y CORREGIDA) ---
function local_xpstore_extend_navigation_course(navigation_node $parentnode, $course, $context) {
    global $DB;

    // 1. Detector de Level Up! en el curso
    $has_xp = $DB->record_exists('block_instances', array(
        'blockname' => 'xp',
        'parentcontextid' => $context->id
    ));

    // Si el bloque Level Up! no está activo en este curso, no mostramos el menú a nadie
    if (!$has_xp) {
        return;
    }

    // 2. Verificamos la configuración de visibilidad ESPECÍFICA DE ESTE CURSO
    $showinmenu = get_config('local_xpstore', 'show_menu_course_' . $course->id);
    
    // LA MAGIA AQUÍ: Si el menú está oculto ('0') Y el usuario NO es profesor (no puede editar), lo ocultamos.
    if ($showinmenu === '0' && !has_capability('moodle/course:update', $context)) {
        return; // Salimos, ocultando el menú solo para los estudiantes
    }

    // 3. Si es estudiante (y está visible) o es profesor (siempre visible), dibujamos el menú de la tienda
    $url = new moodle_url('/local/xpstore/index.php', array('id' => $course->id));
    $shopnode = navigation_node::create(
        get_string('tiendaxp', 'local_xpstore'), 
        $url, 
        navigation_node::TYPE_SETTING, 
        null, 
        'tiendaxp', 
        new pix_icon('i/store', '')
    );
    $parentnode->add_node($shopnode);

    // Botón de Auditoría exclusivo para profesores
    if (has_capability('moodle/course:update', $context)) {
        $reporturl = new moodle_url('/local/xpstore/report.php', array('id' => $course->id));
        $parentnode->add_node(navigation_node::create(
            get_string('audit', 'local_xpstore'), 
            $reporturl, 
            navigation_node::TYPE_SETTING, 
            null, 
            'tiendareport', 
            new pix_icon('i/report', '')
        ));
    }
}

// --- CONSULTA DE SALDO ---
function local_xpstore_get_saldo($userid, $courseid) {
    global $DB;
    $xp = $DB->get_field_sql("SELECT SUM(xp) FROM {block_xp_log} WHERE userid = ? AND courseid = ?", array($userid, $courseid));
    if (!$xp) {
        $xp = $DB->get_field_sql("SELECT xp FROM {block_xp} WHERE userid = ? AND courseid = ?", array($userid, $courseid));
    }
    $total_xp = (int)($xp ?: 0);
    $sql_g = "SELECT SUM(g.amount) FROM {local_xpstore_gastos} g JOIN {course_modules} cm ON g.itemid = cm.id WHERE g.userid = ? AND cm.course = ?";
    $gastos = $DB->get_field_sql($sql_g, array($userid, $courseid)) ?: 0;
    return max(0, $total_xp - (int)$gastos);
}

// --- PROCESAR COMPRA ---
function local_xpstore_comprar($userid, $tipo, $cmid, $costo, $courseid) {
    global $DB;
    if (local_xpstore_get_saldo($userid, $courseid) >= $costo) {
        $record = new stdClass();
        $record->userid = $userid; 
        $record->itemtype = $tipo; 
        $record->itemid = $cmid;
        $record->amount = $costo; 
        $record->timecreated = time();
        $DB->insert_record('local_xpstore_gastos', $record);
        return true;
    }
    return false;
}

// --- ENTREGA DE PRODUCTOS ---
function local_xpstore_entregar_producto($userid, $cmid, $tipo, $courseid = null) {
    global $DB;
    
    try {
        $cm = $DB->get_record('course_modules', array('id' => $cmid), '*', MUST_EXIST);
        $deadline = time() + (24 * 60 * 60);

        // Si no mandaron el ID del curso, tratamos de sacarlo del Course Module
        if (!$courseid) {
            $courseid = $cm->course;
        }

        // FIX: Leer el catálogo exclusivo del curso en lugar de la variable global obsoleta
        $config_raw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
        $items_config = explode(',', $config_raw);
        $nombre_premio = "VIP";
        $valor_nota = 0.5;

        foreach ($items_config as $item) {
            $parts = explode(':', trim($item));
            // Tipo + CMID, p. ej. 'G123'
            if (isset($parts[0]) && $parts[0] === $tipo . $cmid) {
                $nombre_premio = isset($parts[2]) ? trim($parts[2]) : "VIP";
                // Rescata el valor real que configuró el profe en el parámetro 4
                $valor_nota = isset($parts[3]) ? (float)$parts[3] : 0.5;
                break;
            }
        }

        if ($tipo === 'Q') {
            $quiz = $DB->get_record('quiz', array('id' => $cm->instance), '*', MUST_EXIST);
            $override = $DB->get_record('quiz_overrides', array('quiz' => $quiz->id, 'userid' => $userid));
            if ($override) {
                $override->attempts = ($override->attempts ?: $quiz->attempts) + 1;
                $override->timeclose = max($override->timeclose, $deadline);
                $DB->update_record('quiz_overrides', $override);
            } else {
                $new = (object)['quiz' => $quiz->id, 'userid' => $userid, 'attempts' => ($quiz->attempts ?: 1) + 1, 'timeclose' => $deadline];
                $DB->insert_record('quiz_overrides', $new);
            }
        } 
        else if ($tipo === 'A') {
            $assign = $DB->get_record('assign', array('id' => $cm->instance), '*', MUST_EXIST);
            $override = $DB->get_record('assign_overrides', array('assignid' => $assign->id, 'userid' => $userid));
            if ($override) {
                $override->duedate = $deadline;
                $override->cutoffdate = $deadline;
                $DB->update_record('assign_overrides', $override);
            } else {
                $new = (object)['assignid' => $assign->id, 'userid' => $userid, 'duedate' => $deadline, 'cutoffdate' => $deadline];
                $DB->insert_record('assign_overrides', $new);
            }
            $flag = $DB->get_record('assign_user_flags', array('assignid' => $assign->id, 'userid' => $userid));
            if ($flag) {
                $flag->extensionduedate = $deadline;
                $flag->locked = 0;
                $DB->update_record('assign_user_flags', $flag);
            } else {
                $newflag = (object)['assignid' => $assign->id, 'userid' => $userid, 'extensionduedate' => $deadline, 'locked' => 0, 'mailed' => 0];
                $DB->insert_record('assign_user_flags', $newflag);
            }
        }
        else if ($tipo === 'G') {
            $grade_item = $DB->get_record('grade_items', array('itemtype' => 'mod', 'itemmodule' => $DB->get_field('modules', 'name', array('id' => $cm->module)), 'iteminstance' => $cm->instance, 'courseid' => $cm->course));
            if ($grade_item) {
                $grade = $DB->get_record('grade_grades', array('itemid' => $grade_item->id, 'userid' => $userid));
                if ($grade) {
                    $nueva = $grade->finalgrade + $valor_nota;
                    $grade->finalgrade = ($nueva > $grade_item->grademax) ? $grade_item->grademax : $nueva;
                    $grade->overridden = time();
                    $DB->update_record('grade_grades', $grade);
                } else {
                    $newgrade = (object)['itemid' => $grade_item->id, 'userid' => $userid, 'rawgrade' => $valor_nota, 'finalgrade' => $valor_nota, 'overridden' => time(), 'timecreated' => time(), 'timemodified' => time()];
                    $DB->insert_record('grade_grades', $newgrade);
                }
                $DB->set_field('grade_items', 'needsupdate', 1, array('courseid' => $cm->course));
            }
        }
        else if ($tipo === 'S') {
            $group = $DB->get_record('groups', array('courseid' => $cm->course, 'name' => $nombre_premio));
            if (!$group) {
                $groupid = $DB->insert_record('groups', (object)['courseid' => $cm->course, 'name' => $nombre_premio, 'timecreated' => time(), 'timemodified' => time()]);
            } else { $groupid = $group->id; }
            if (!$DB->record_exists('groups_members', array('groupid' => $groupid, 'userid' => $userid))) {
                $DB->insert_record('groups_members', (object)['groupid' => $groupid, 'userid' => $userid, 'timeadded' => time()]);
            }
        }

        rebuild_course_cache($cm->course, true);
        return true;
    } catch (Exception $e) { return false; }
}

// --- REINICIO DE CURSO ---
function local_xpstore_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'tiendagamificadaheader', get_string('tiendaxp', 'local_xpstore'));
    $mform->addElement('advcheckbox', 'reset_tiendagamificada_gastos', get_string('resethistory', 'local_xpstore'));
}

function local_xpstore_reset_userdata($data) {
    global $DB;
    if (!empty($data->reset_tiendagamificada_gastos)) {
        $DB->execute("DELETE g FROM {local_xpstore_gastos} g JOIN {course_modules} cm ON g.itemid = cm.id WHERE cm.course = ?", array($data->courseid));
    }
    return array();
}
