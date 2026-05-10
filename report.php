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

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('moodle/course:update', $context);

$url = new moodle_url('/local/xpstore/report.php', array('id' => $courseid));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('audit', 'local_xpstore'));

// --- Lógica para Reiniciar el Ciclo ---
$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'reset' && confirm_sesskey()) {
    $reset_sql = "DELETE g FROM {local_xpstore_gastos} g 
                  JOIN {course_modules} cm ON g.itemid = cm.id 
                  WHERE cm.course = ?";
    $DB->execute($reset_sql, array($courseid));
    redirect($url, get_string('productdeleted', 'local_xpstore'));
}

// Pre-cargar categorías de la tienda para asignarlas a los registros
$config_raw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
$items_raw = array_filter(explode(',', $config_raw));
$map_categorias = array();

foreach ($items_raw as $item) {
    $tipo_char = strtoupper(substr($item, 0, 1));
    $rest = substr($item, 1);
    $parts = explode(':', $rest);
    if (count($parts) >= 2) {
        $cid = $parts[0] ?? '';
        $cat = !empty($parts[4]) ? trim($parts[4]) : get_string('defaultcategory', 'local_xpstore');
        $map_categorias[$tipo_char][$cid] = $cat;
    }
}

// Cargar información de los módulos del curso para los links
$modinfo = get_fast_modinfo($courseid);

echo $OUTPUT->header();
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');
    
    .report-wrapper { 
        font-family: 'Montserrat', sans-serif; 
        background: #f4f6f9; 
        padding: 30px; 
        border-radius: 20px; 
    }
    
    .header-container { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 30px; 
    }
    
    .header-title h2 { 
        font-weight: bold; 
        margin: 0; 
        color: #333; 
    }
    
    .header-title p { 
        color: #777; 
        margin: 0; 
        font-size: 0.95rem; 
    }
    
    .btn-tienda { 
        background: white; 
        border: 1px solid #ccc; 
        color: #555; 
        padding: 8px 20px; 
        border-radius: 50px; 
        font-weight: bold; 
        text-decoration: none !important; 
        margin-right: 10px; 
        transition: 0.2s; 
    }
    
    .btn-tienda:hover { 
        background: #f0f0f0; 
    }
    
    .btn-reset { 
        background: #c93a3a; 
        color: white; 
        padding: 8px 20px; 
        border-radius: 50px; 
        font-weight: bold; 
        text-decoration: none !important; 
        border: none; 
        transition: 0.2s; 
    }
    
    .btn-reset:hover { 
        background: #a82e2e; 
        color: white; 
    }
    
    .user-card { 
        background: white; 
        border-radius: 15px; 
        margin-bottom: 15px; 
        box-shadow: 0 4px 6px rgba(0,0,0,0.02); 
        overflow: hidden; 
    }
    
    .card-header-user { 
        display: flex; 
        align-items: center; 
        padding: 15px 25px; 
        cursor: pointer; 
        transition: 0.2s; 
    }
    
    .card-header-user:hover { 
        background: #fafafa; 
    }
    
    .u-avatar-container img.userpicture { 
        width: 50px !important; 
        height: 50px !important; 
        border-radius: 50%; 
        border: 2px solid #7d2ae8; 
        margin-right: 15px; 
        display: block; 
    }
    
    .u-info { 
        flex-grow: 1; 
        min-width: 0; 
        padding-right: 10px; 
    } 
    
    .u-name { 
        font-weight: bold; 
        font-size: 1.1rem; 
        color: #222; 
        margin-bottom: 2px; 
        white-space: nowrap; 
        overflow: hidden; 
        text-overflow: ellipsis; 
    }
    
    .u-email { 
        color: #888; 
        font-size: 0.85rem; 
        white-space: nowrap; 
        overflow: hidden; 
        text-overflow: ellipsis; 
    }
    
    .badge-total { 
        background: #7d2ae8; 
        color: white; 
        padding: 5px 15px; 
        border-radius: 20px; 
        font-weight: bold; 
        font-size: 0.85rem; 
        margin-right: 15px; 
        white-space: nowrap; 
        flex-shrink: 0; 
    }
    
    .card-body-logs { 
        display: none; 
        padding: 20px 25px; 
        border-top: 1px solid #eee; 
        background: #fff; 
    }
    
    .table-logs { 
        width: 100%; 
        border-collapse: collapse; 
    }
    
    .table-logs th { 
        text-align: left; 
        padding: 10px; 
        color: #aaa; 
        font-size: 0.75rem; 
        text-transform: uppercase; 
        letter-spacing: 1px; 
        border-bottom: 2px solid #f0f0f0; 
    }
    
    .table-logs td { 
        padding: 15px 10px; 
        border-bottom: 1px solid #f5f5f5; 
        vertical-align: middle; 
    }
    
    .badge-tipo { 
        padding: 5px 12px; 
        border-radius: 8px; 
        font-size: 0.75rem; 
        font-weight: bold; 
        color: white; 
        white-space: nowrap; 
    }
    
    .bg-q { background: #17a2b8; } 
    .bg-a { background: #007bff; } 
    .bg-g { background: #ffc107; color: #000; } 
    .bg-s { background: #00c4cc; } 
    .bg-f { background: #6c757d; }

    /* ========================================================
       MOBILE FIXES - RESPONSIVE DESIGN
       ======================================================== */
    @media (max-width: 768px) {
        .report-wrapper { padding: 15px; }
        .header-container { 
            flex-direction: column; 
            align-items: flex-start; 
            gap: 15px; 
        }
        .header-container > div:last-child { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 10px; 
            width: 100%; 
        }
        .btn-tienda { margin-right: 0; }
        .card-header-user { padding: 15px; }
        .u-avatar-container img.userpicture { 
            width: 40px !important; 
            height: 40px !important; 
            margin-right: 10px; 
        }
        .u-name { font-size: 1rem; }
        .u-email { font-size: 0.75rem; }
        .badge-total { 
            padding: 5px 10px; 
            font-size: 0.75rem; 
            margin-right: 10px; 
        }
        .card-body-logs { 
            padding: 15px; 
            overflow-x: auto; 
            -webkit-overflow-scrolling: touch; 
        }
        .table-logs th, .table-logs td { 
            white-space: nowrap; 
            padding: 10px 8px; 
        }
    }
</style>

<div class="report-wrapper">
    <div class="header-container">
        <div class="header-title">
            <h2><?php echo get_string('reporttitle', 'local_xpstore'); ?></h2>
            <p><?php echo get_string('reportsubtitle', 'local_xpstore'); ?></p>
        </div>
        <div>
            <a href="<?php echo new moodle_url('/local/xpstore/index.php', array('id' => $courseid)); ?>" 
               class="btn-tienda">
                <i class="fa fa-arrow-left"></i> <?php echo get_string('tiendaxp', 'local_xpstore'); ?>
            </a>
            <a href="<?php echo $url->out(false); ?>&action=reset&sesskey=<?php echo sesskey(); ?>" 
               class="btn-reset" 
               onclick="return confirm('<?php echo get_string('confirmreset', 'local_xpstore'); ?>')">
                <i class="fa fa-trash"></i> <?php echo get_string('resetcycle', 'local_xpstore'); ?>
            </a>
        </div>
    </div>

    <?php
    $sql = "SELECT g.*, u.firstname, u.lastname, u.picture, u.imagealt, u.email 
            FROM {local_xpstore_gastos} g 
            JOIN {user} u ON g.userid = u.id 
            JOIN {course_modules} cm ON g.itemid = cm.id 
            WHERE cm.course = ? 
            ORDER BY u.firstname ASC, g.timecreated DESC";
    
    $logs = $DB->get_records_sql($sql, array($courseid));
    $agrupados = array();

    if ($logs) {
        foreach ($logs as $log) {
            $agrupados[$log->userid][] = $log;
        }

        foreach ($agrupados as $userid => $user_logs) {
            $first_log = $user_logs[0];
            
            // --- FIX DE AVATARES ROBUSTO ---
            $real_user = core_user::get_user($userid);
            $user_pic_html = $OUTPUT->user_picture($real_user, array('size' => 50, 'link' => false));
            
            // Formato limpio Nombre Apellido
            $user_fullname = $real_user->firstname . ' ' . $real_user->lastname;
            $total_canjes = count($user_logs);
            
            // Link al perfil del estudiante
            $profile_url = new moodle_url('/user/view.php', array('id' => $userid, 'course' => $courseid));
    ?>
        <div class="user-card">
            <div class="card-header-user" onclick="toggleLogs(<?php echo $userid; ?>)">
                <div class="u-avatar-container">
                    <?php echo $user_pic_html; ?>
                </div>
                <div class="u-info">
                    <div class="u-name">
                        <a href="<?php echo $profile_url; ?>" 
                           onclick="event.stopPropagation();" 
                           style="color: inherit; text-decoration: none;" 
                           onmouseover="this.style.textDecoration='underline'" 
                           onmouseout="this.style.textDecoration='none'" 
                           target="_blank">
                            <?php echo $user_fullname; ?>
                        </a>
                    </div>
                    <div class="u-email"><?php echo $real_user->email; ?></div>
                </div>
                <div class="badge-total">
                    <?php echo $total_canjes . ' ' . get_string('redemptions', 'local_xpstore'); ?>
                </div>
                <i class="fa fa-chevron-down text-muted" id="icon-<?php echo $userid; ?>"></i>
            </div>
            
            <div class="card-body-logs" id="logs-<?php echo $userid; ?>">
                <table class="table-logs">
                    <thead>
                        <tr>
                            <th><?php echo get_string('activity', 'local_xpstore'); ?></th>
                            <th><?php echo get_string('colcategory', 'local_xpstore'); ?></th>
                            <th><?php echo get_string('type', 'local_xpstore'); ?></th>
                            <th class="text-center"><?php echo get_string('cost', 'local_xpstore'); ?></th>
                            <th class="text-right"><?php echo get_string('date', 'local_xpstore'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($user_logs as $log) {
                            $cm = $DB->get_record('course_modules', array('id' => $log->itemid));
                            
                            if ($cm) {
                                $modname = $DB->get_field('modules', 'name', array('id' => $cm->module));
                                $activity_name = $DB->get_field($modname, 'name', array('id' => $cm->instance));
                            } else {
                                $activity_name = 'Actividad / Recurso eliminado';
                            }
                            
                            $tipo_str = strtolower($log->itemtype);
                            $tipo_char_upper = strtoupper($tipo_str);
                            
                            $label_tipo = get_string_manager()->string_exists('type_'.$tipo_str, 'local_xpstore') 
                                ? get_string('type_'.$tipo_str, 'local_xpstore') 
                                : 'Legacy';

                            // 1. Obtener la categoría cruzando con la configuración
                            $categoria_texto = isset($map_categorias[$tipo_char_upper][$log->itemid]) 
                                ? $map_categorias[$tipo_char_upper][$log->itemid] 
                                : '-';

                            // 2. Generar el enlace inteligente a la actividad
                            $cm_url = '';
                            if ($tipo_str === 'g') {
                                // Redirige al reporte de calificaciones ESPECÍFICO de este estudiante
                                $cm_url = new moodle_url(
                                    '/grade/report/user/index.php', 
                                    array('id' => $courseid, 'userid' => $log->userid)
                                );
                            } else if (isset($modinfo->cms[$log->itemid])) {
                                $cm_url = $modinfo->cms[$log->itemid]->url;
                            }

                            // 3. Formatear el HTML de la actividad
                            $activity_html = htmlspecialchars($activity_name);
                            if (!empty($cm_url) && $activity_name !== 'Actividad / Recurso eliminado') {
                                $activity_html = '<a href="' . $cm_url . '" target="_blank" ' .
                                                 'style="color: #0056D2; text-decoration: none; transition: 0.2s;" ' .
                                                 'onmouseover="this.style.color=\'#00C9A7\'" ' .
                                                 'onmouseout="this.style.color=\'#0056D2\'">' .
                                                 '<i class="fa fa-external-link mr-1" style="font-size: 0.8rem;"></i>' . 
                                                 $activity_html . 
                                                 '</a>';
                            }
                        ?>
                        <tr>
                            <td class="font-weight-bold" style="font-size: 0.9rem; color: #444;">
                                <?php echo $activity_html; ?>
                            </td>
                            <td>
                                <span style="background: #f0f0f0; color: #555; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: bold; white-space: nowrap;">
                                    <?php echo htmlspecialchars($categoria_texto); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-tipo bg-<?php echo $tipo_str; ?>">
                                    <?php echo $label_tipo; ?>
                                </span>
                            </td>
                            <td class="text-center font-weight-bold text-primary">
                                <?php echo $log->amount; ?> XP
                            </td>
                            <td class="text-right small text-muted">
                                <?php echo userdate($log->timecreated, get_string('strftimedatetime', 'langconfig')); ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php 
        }
    } else {
        echo "<div class='text-center py-5 text-muted'>" . 
             get_string('nopurchases', 'local_xpstore') . 
             "</div>";
    }
    ?>
</div>

<script>
    function toggleLogs(userid) {
        var logsDiv = document.getElementById('logs-' + userid);
        var iconDiv = document.getElementById('icon-' + userid);
        if (logsDiv.style.display === 'block') {
            logsDiv.style.display = 'none';
            iconDiv.className = 'fa fa-chevron-down text-muted';
        } else {
            logsDiv.style.display = 'block';
            iconDiv.className = 'fa fa-chevron-up text-muted';
        }
    }
</script>

<?php echo $OUTPUT->footer(); ?>
