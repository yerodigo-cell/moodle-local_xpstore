<?php
/**
 * history.php - Historial del Estudiante
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

global $USER, $PAGE, $OUTPUT, $DB;

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);

$url = new moodle_url('/local/xpstore/history.php', array('id' => $courseid));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('history', 'local_xpstore'));

echo $OUTPUT->header();

// Cargamos la información de los módulos para extraer las URLs reales
$modinfo = get_fast_modinfo($courseid);

// --- LÓGICA DE SEGURIDAD PARA EL BOTÓN DE REGRESAR ---
$showinmenu = get_config('local_xpstore', 'show_menu_course_' . $courseid);
$is_teacher = has_capability('moodle/course:update', $context);

// El botón se muestra si la tienda está visible para todos, O si el que mira es un profesor
$show_back_button = ($showinmenu !== '0' || $is_teacher);
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');
    
    .history-wrapper { font-family: 'Montserrat', sans-serif; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
    .header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .header-title h2 { font-weight: bold; margin: 0; color: #333; }
    
    .btn-tienda { background: white; border: 1px solid #ccc; color: #555; padding: 8px 20px; border-radius: 50px; font-weight: bold; text-decoration: none !important; transition: 0.2s; display: inline-block; }
    .btn-tienda:hover { background: #f0f0f0; }
    
    /* FIX PARA TABLA MÓVIL */
    .table-responsive-wrapper { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 10px; }
    .table-history { width: 100%; border-collapse: separate; border-spacing: 0 10px; min-width: 750px; }
    
    .table-history thead th { border: none; color: #888; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; padding: 10px 20px; white-space: nowrap; }
    .table-history tbody tr { background: #f8f9fa; transition: 0.3s; }
    .table-history tbody tr:hover { background: #f1f1f1; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
    .table-history td { padding: 15px 20px; border: none; vertical-align: middle; }
    .td-first { border-radius: 12px 0 0 12px; }
    .td-last { border-radius: 0 12px 12px 0; }
    
    /* FIX PARA EVITAR EL CORTE DE LOS BADGES */
    .badge-tipo { padding: 5px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: bold; color: white; white-space: nowrap; display: inline-block; }
    .bg-q { background: #17a2b8; } .bg-a { background: #007bff; } .bg-g { background: #ffc107; color: #000; } .bg-s { background: #00c4cc; } .bg-f { background: #6c757d; }
    
    .btn-action-history { display: inline-block; padding: 8px 15px; border-radius: 10px; font-weight: bold; font-size: 0.85rem; text-decoration: none !important; transition: 0.2s; white-space: nowrap; }
    .btn-activity { background: #e8f5e9; color: #28a745; }
    .btn-activity:hover { background: #28a745; color: white; }
    .btn-grade { background: #fff3cd; color: #856404; }
    .btn-grade:hover { background: #ffc107; color: #000; }

    /* ========================================================
       MOBILE FIXES - RESPONSIVE DESIGN
       ======================================================== */
    @media (max-width: 768px) {
        .history-wrapper { padding: 15px; }
        .header-container { flex-direction: column; align-items: flex-start; gap: 15px; }
        .btn-tienda { width: 100%; text-align: center; box-sizing: border-box; }
        .table-history td { padding: 12px 15px; }
    }
</style>

<div class="history-wrapper">
    <div class="header-container">
        <div class="header-title">
            <h2><i class="fa fa-history mr-2" style="color: #0056D2;"></i> <?php echo get_string('history', 'local_xpstore'); ?></h2>
        </div>
        
        <?php if ($show_back_button): ?>
            <a href="<?php echo new moodle_url('/local/xpstore/index.php', array('id' => $courseid)); ?>" class="btn-tienda">
                <i class="fa fa-arrow-left"></i> <?php echo get_string('tiendaxp', 'local_xpstore'); ?>
            </a>
        <?php endif; ?>
    </div>

    <div class="table-responsive-wrapper">
        <table class="table-history">
            <thead>
                <tr>
                    <th><?php echo get_string('activity', 'local_xpstore'); ?></th>
                    <th><?php echo get_string('type', 'local_xpstore'); ?></th>
                    <th class="text-center"><?php echo get_string('cost', 'local_xpstore'); ?></th>
                    <th><?php echo get_string('date', 'local_xpstore'); ?></th>
                    <th class="text-right"><?php echo get_string('action', 'local_xpstore'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Consultamos únicamente los gastos del usuario actual en este curso
                $sql = "SELECT g.*, cm.module, cm.instance 
                        FROM {local_xpstore_gastos} g 
                        JOIN {course_modules} cm ON g.itemid = cm.id 
                        WHERE g.userid = ? AND cm.course = ? 
                        ORDER BY g.timecreated DESC";
                
                $logs = $DB->get_records_sql($sql, array($USER->id, $courseid));

                if ($logs) {
                    foreach ($logs as $log) {
                        $modname = $DB->get_field('modules', 'name', array('id' => $log->module));
                        $activity_name = $DB->get_field($modname, 'name', array('id' => $log->instance));
                        
                        $tipo_str = strtolower($log->itemtype);
                        $label_tipo = get_string_manager()->string_exists('type_'.$tipo_str, 'local_xpstore') 
                            ? get_string('type_'.$tipo_str, 'local_xpstore') 
                            : 'Legacy';
                ?>
                    <tr>
                        <td class="td-first font-weight-bold" style="color: #444; white-space: nowrap;">
                            <?php echo htmlspecialchars($activity_name); ?>
                        </td>
                        <td>
                            <span class="badge-tipo bg-<?php echo $tipo_str; ?>"><?php echo $label_tipo; ?></span>
                        </td>
                        <td class="text-center font-weight-bold text-primary" style="white-space: nowrap;">
                            <?php echo $log->amount; ?> XP
                        </td>
                        <td class="small text-muted" style="white-space: nowrap;">
                            <?php echo userdate($log->timecreated, get_string('strftimedatetime', 'langconfig')); ?>
                        </td>
                        <td class="td-last text-right">
                            <?php 
                            if ($log->itemtype === 'G') {
                                // Enlace al calificador para Puntos Extra
                                $grade_url = new moodle_url('/grade/report/user/index.php', array('id' => $courseid));
                                echo '<a href="' . $grade_url . '" class="btn-action-history btn-grade"><i class="fa fa-bar-chart"></i> ' . get_string('gotogradebook', 'local_xpstore') . '</a>';
                            } else {
                                // Enlace directo a la actividad para Quiz, Tareas y VIP
                                $cm_url = isset($modinfo->cms[$log->itemid]) ? $modinfo->cms[$log->itemid]->url : new moodle_url('/course/view.php', array('id' => $courseid));
                                echo '<a href="' . $cm_url . '" class="btn-action-history btn-activity"><i class="fa fa-external-link"></i> ' . get_string('gotoactivity', 'local_xpstore') . '</a>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php 
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-center py-5 text-muted' style='border-radius: 12px;'>" . get_string('nopurchases', 'local_xpstore', 'No tienes canjes aún.') . "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php echo $OUTPUT->footer(); ?>