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
 * widget_history.php - Botón embebido para ir al historial.
 */
require_once(__DIR__ . '/../../config.php');

global $PAGE, $OUTPUT, $DB;

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);
require_login($course);

$url = new moodle_url('/local/xpstore/widget_history.php', ['id' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($context);

// Modo embebido para que no cargue menús de Moodle.
$PAGE->set_pagelayout('embedded'); 

echo $OUTPUT->header();

// URL destino: El historial real.
$historyurl = new moodle_url('/local/xpstore/history.php', ['id' => $courseid]);

// Consulta de colores personalizados por curso.
$cpstore = get_config('local_xpstore', 'color_primary_course_' . $courseid) ?: '#0056D2';
$cbstore = get_config('local_xpstore', 'color_secondary_course_' . $courseid) ?: '#00C9A7';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');
    
    :root { 
        --cp: <?php echo $cpstore; ?>; 
        --cb: <?php echo $cbstore; ?>; 
        --gradient: linear-gradient(135deg, var(--cp) 0%, var(--cb) 100%); 
    }

    /* FORZAR ICONOS GRUESOS */
    .fa, .fas, .fa-solid { font-weight: 900 !important; }  

    /* FONDO BLANCO PURO PARA EVITAR HEREDAR EL GRIS */
    body, #page-wrapper, #page, #region-main { background: #ffffff !important; }

    body {
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        overflow: visible; /* Permite que la sombra respire */
        font-family: 'Montserrat', sans-serif;
    }

    .btn-history-widget {
        background: var(--gradient);
        background-repeat: no-repeat;
        color: white !important;
        text-decoration: none !important;
        padding: 12px 28px;
        border-radius: 50px;
        font-size: 1.05rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        /* Sombra neutralizada para que combine con cualquier color */
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
        border: none !important;
        outline: none !important;
        margin: 25px; /* Obliga a la caja a darle espacio a la sombra */
    }

    .btn-history-widget:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 30px rgba(0, 0, 0, 0.25);
    }

    .btn-history-widget i {
        font-size: 1.2rem;
    }
</style>

<a href="<?php echo $historyurl; ?>" target="_top" class="btn-history-widget">
    <i class="fa fa-history"></i>
    <?php echo get_string('history', 'local_xpstore'); ?>
</a>

<?php echo $OUTPUT->footer(); ?>