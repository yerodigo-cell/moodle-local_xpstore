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

/**
 * Extend course navigation with XP Store link.
 *
 * @param navigation_node $parentnode The parent node.
 * @param stdClass $course The course object.
 * @param context_course $context The course context.
 * @return void
 */
function local_xpstore_extend_navigation_course(navigation_node $parentnode, $course, $context) {
    global $DB;

    // 1. Detector de Level Up! en el curso.
    $hasxp = $DB->record_exists('block_instances', [
        'blockname' => 'xp',
        'parentcontextid' => $context->id
    ]);

    // Si el bloque Level Up! no está activo en este curso, no mostramos el menú a nadie.
    if (!$hasxp) {
        return;
    }

    // 2. Verificamos la configuración de visibilidad ESPECÍFICA DE ESTE CURSO.
    $showinmenu = get_config('local_xpstore', 'show_menu_course_' . $course->id);
    
    // LA MAGIA AQUÍ: Si el menú está oculto ('0') Y el usuario NO es profesor (no puede editar), lo ocultamos.
    if ($showinmenu === '0' && !has_capability('moodle/course:update', $context)) {
        return; // Salimos, ocultando el menú solo para los estudiantes.
    }

    // 3. Si es estudiante (y está visible) o es profesor (siempre visible), dibujamos el menú de la tienda.
    $url = new moodle_url('/local/xpstore/index.php', ['id' => $course->id]);
    $shopnode = navigation_node::create(
        get_string('tiendaxp', 'local_xpstore'), 
        $url, 
        navigation_node::TYPE_SETTING, 
        null, 
        'tiendaxp', 
        new pix_icon('i/store', '')
    );
    $parentnode->add_node($shopnode);

    // Botón de Auditoría exclusivo para profesores.
    if (has_capability('moodle/course:update', $context)) {
        $reporturl = new moodle_url('/local/xpstore/report.php', ['id' => $course->id]);
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

/**
 * Obtiene el saldo disponible de un usuario en un curso.
 *
 * @param int $userid The user ID.
 * @param int $courseid The course ID.
 * @return int Saldo disponible.
 */
function local_xpstore_get_saldo($userid, $courseid) {
    global $DB;
    $xp = $DB->get_field_sql(
        "SELECT SUM(xp) FROM {block_xp_log} WHERE userid = ? AND courseid = ?", 
        [$userid, $courseid]
    );
    if (!$xp) {
        $xp = $DB->get_field_sql(
            "SELECT xp FROM {block_xp} WHERE userid = ? AND courseid = ?", 
            [$userid, $courseid]
        );
    }
    $totalxp = (int)($xp ?: 0);
    
    $sqlg = "SELECT SUM(g.amount) 
             FROM {local_xpstore_gastos} g 
             JOIN {course_modules} cm ON g.itemid = cm.id 
             WHERE g.userid = ? AND cm.course = ?";
    $gastos = $DB->get_field_sql($sqlg, [$userid, $courseid]) ?: 0;
    
    return max(0, $totalxp - (int)$gastos);
}

/**
 * Registra una compra en la base de datos.
 *
 * @param int $userid The user ID.
 * @param string $tipo The item type.
 * @param int $cmid The course module ID.
 * @param int $costo The cost of the item.
 * @param int $courseid The course ID.
 * @return bool True if successful, false otherwise.
 */
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

/**
 * Entrega el producto comprado al usuario modificando overrides o calificaciones.
 *
 * @param int $userid The user ID.
 * @param int $cmid The course module ID.
 * @param string $tipo The item type.
 * @param int $courseid The course ID (optional).
 * @return bool True if successful, false otherwise.
 */
function local_xpstore_entregar_producto($userid, $cmid, $tipo, $courseid = null) {
    global $DB;
    
    try {
        $cm = $DB->get_record('course_modules', ['id' => $cmid], '*', MUST_EXIST);
        $deadline = time() + (24 * 60 * 60);

        // Si no mandaron el ID del curso, tratamos de sacarlo del Course Module.
        if (!$courseid) {
            $courseid = $cm->course;
        }

        // FIX: Leer el catálogo exclusivo del curso en lugar de la variable global obsoleta.
        $configraw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
        $itemsconfig = explode(',', $configraw);
        $nombrepremio = "VIP";
        $valornota = 0.5;

        foreach ($itemsconfig as $item) {
            $parts = explode(':', trim($item));
            // Tipo + CMID, p. ej. 'G123'.
            if (isset($parts[0]) && $parts[0] === $tipo . $cmid) {
                $nombrepremio = isset($parts[2]) ? trim($parts[2]) : "VIP";
                // Rescata el valor real que configuró el profe en el parámetro 4.
                $valornota = isset($parts[3]) ? (float)$parts[3] : 0.5;
                break;
            }
        }

        if ($tipo === 'Q') {
            $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
            $override = $DB->get_record('quiz_overrides', ['quiz' => $quiz->id, 'userid' => $userid]);
            
            if ($override) {
                $override->attempts = ($override->attempts ?: $quiz->attempts) + 1;
                $override->timeclose = max($override->timeclose, $deadline);
                $DB->update_record('quiz_overrides', $override);
            } else {
                $new = (object)[
                    'quiz' => $quiz->id, 
                    'userid' => $userid, 
                    'attempts' => ($quiz->attempts ?: 1) + 1, 
                    'timeclose' => $deadline
                ];
                $DB->insert_record('quiz_overrides', $new);
            }
        } else if ($tipo === 'A') {
            $assign = $DB->get_record('assign', ['id' => $cm->instance], '*', MUST_EXIST);
            $override = $DB->get_record('assign_overrides', ['assignid' => $assign->id, 'userid' => $userid]);
            
            if ($override) {
                $override->duedate = $deadline;
                $override->cutoffdate = $deadline;
                $DB->update_record('assign_overrides', $override);
            } else {
                $new = (object)[
                    'assignid' => $assign->id, 
                    'userid' => $userid, 
                    'duedate' => $deadline, 
                    'cutoffdate' => $deadline
                ];
                $DB->insert_record('assign_overrides', $new);
            }
            
            $flag = $DB->get_record('assign_user_flags', ['assignid' => $assign->id, 'userid' => $userid]);
            
            if ($flag) {
                $flag->extensionduedate = $deadline;
                $flag->locked = 0;
                $DB->update_record('assign_user_flags', $flag);
            } else {
                $newflag = (object)[
                    'assignid' => $assign->id, 
                    'userid' => $userid, 
                    'extensionduedate' => $deadline, 
                    'locked' => 0, 
                    'mailed' => 0
                ];
                $DB->insert_record('assign_user_flags', $newflag);
            }
        } else if ($tipo === 'G') {
            $itemmodule = $DB->get_field('modules', 'name', ['id' => $cm->module]);
            $gradeitem = $DB->get_record('grade_items', [
                'itemtype' => 'mod', 
                'itemmodule' => $itemmodule, 
                'iteminstance' => $cm->instance, 
                'courseid' => $cm->course
            ]);
            
            if ($gradeitem) {
                $grade = $DB->get_record('grade_grades', ['itemid' => $gradeitem->id, 'userid' => $userid]);
                
                if ($grade) {
                    $nueva = $grade->finalgrade + $valornota;
                    $grade->finalgrade = ($nueva > $gradeitem->grademax) ? $gradeitem->grademax : $nueva;
                    $grade->overridden = time();
                    $DB->update_record('grade_grades', $grade);
                } else {
                    $newgrade = (object)[
                        'itemid' => $gradeitem->id, 
                        'userid' => $userid, 
                        'rawgrade' => $valornota, 
                        'finalgrade' => $valornota, 
                        'overridden' => time(), 
                        'timecreated' => time(), 
                        'timemodified' => time()
                    ];
                    $DB->insert_record('grade_grades', $newgrade);
                }
                $DB->set_field('grade_items', 'needsupdate', 1, ['courseid' => $cm->course]);
            }
        } else if ($tipo === 'S') {
            $group = $DB->get_record('groups', ['courseid' => $cm->course, 'name' => $nombrepremio]);
            
            if (!$group) {
                $newgroup = (object)[
                    'courseid' => $cm->course, 
                    'name' => $nombrepremio, 
                    'timecreated' => time(), 
                    'timemodified' => time()
                ];
                $groupid = $DB->insert_record('groups', $newgroup);
            } else { 
                $groupid = $group->id; 
            }
            
            if (!$DB->record_exists('groups_members', ['groupid' => $groupid, 'userid' => $userid])) {
                $newmember = (object)[
                    'groupid' => $groupid, 
                    'userid' => $userid, 
                    'timeadded' => time()
                ];
                $DB->insert_record('groups_members', $newmember);
            }
        }

        rebuild_course_cache($cm->course, true);
        return true;
    } catch (Exception $e) { 
        return false; 
    }
}

/**
 * Add elements to the reset course form.
 *
 * @param MoodleQuickForm $mform The form.
 * @return void
 */
function local_xpstore_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'tiendagamificadaheader', get_string('tiendaxp', 'local_xpstore'));
    $mform->addElement('advcheckbox', 'reset_tiendagamificada_gastos', get_string('resethistory', 'local_xpstore'));
}

/**
 * Reset userdata for the store.
 *
 * @param stdClass $data The data submitted.
 * @return array Status array.
 */
function local_xpstore_reset_userdata($data) {
    global $DB;
    if (!empty($data->reset_tiendagamificada_gastos)) {
        $sql = "DELETE g FROM {local_xpstore_gastos} g 
                JOIN {course_modules} cm ON g.itemid = cm.id 
                WHERE cm.course = ?";
        $DB->execute($sql, [$data->courseid]);
    }
    return [];
}