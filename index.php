<?php
/**
 * index.php - Versión Premium
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

global $USER, $PAGE, $OUTPUT, $DB, $SESSION;

$courseid = optional_param('id', 0, PARAM_INT);
if ($courseid <= 0) { if (!empty($PAGE->course->id) && $PAGE->course->id != SITEID) { $courseid = $PAGE->course->id; } }

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);
require_login($course);

$url = new moodle_url('/local/xpstore/index.php', array('id' => $courseid));
$historyurl = new moodle_url('/local/xpstore/history.php', array('id' => $courseid));
$config_url = new moodle_url('/local/xpstore/config.php', array('id' => $courseid));
$report_url = new moodle_url('/local/xpstore/report.php', array('id' => $courseid));

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('storetitle', 'local_xpstore') . ' - ' . $course->shortname);

$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'comprar' && confirm_sesskey()) {
    $cmid = required_param('cmid', PARAM_INT);
    $tipo = required_param('tipo', PARAM_ALPHA);
    $costo = required_param('costo', PARAM_INT);
    
    $productos_raw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
    $items = array_filter(explode(',', $productos_raw));
    $limite_compra = 0;
    
    foreach ($items as $it) {
        $p_tipo = substr($it, 0, 1);
        $p_parts = explode(':', substr($it, 1));
        if ($p_tipo === $tipo && (int)$p_parts[0] === $cmid) {
            $limite_compra = (int)($p_parts[5] ?? 0);
            break;
        }
    }

    $limite_alcanzado = false;
    if ($limite_compra > 0) {
        $compras_previas = $DB->count_records('local_xpstore_gastos', array('userid' => $USER->id, 'itemid' => $cmid, 'itemtype' => $tipo));
        if ($compras_previas >= $limite_compra) { $limite_alcanzado = true; }
    }

    if ($limite_alcanzado) {
        redirect(new moodle_url($url, array('status' => 'limit')));
    } else {
        if (local_xpstore_comprar($USER->id, $tipo, $cmid, $costo, $courseid)) {
            local_xpstore_entregar_producto($USER->id, $cmid, $tipo, $courseid);
            redirect(new moodle_url($url, array('status' => 'success', 'cmid' => $cmid, 'tipo_compra' => $tipo)));
        } else {
            redirect(new moodle_url($url, array('status' => 'error')));
        }
    }
}

echo $OUTPUT->header();

$status = optional_param('status', '', PARAM_ALPHA);
$bought_cmid = optional_param('cmid', 0, PARAM_INT);
$tipo_compra = optional_param('tipo_compra', '', PARAM_ALPHA);

if ($status === 'success') {
    if ($tipo_compra === 'G') {
        echo '<div class="alert alert-success text-center mb-3" style="border-radius: 15px; border: none; background: #d4edda; color: #155724; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">';
        echo '<div style="font-size: 2rem; margin-bottom: 10px; color: #28a745;"><i class="fa fa-star"></i></div>';
        echo '<h5 class="font-weight-bold mb-3">' . get_string('exito', 'local_xpstore') . '</h5>';
        $grade_url = new moodle_url('/grade/report/user/index.php', array('id' => $courseid));
        echo '<a href="' . $grade_url . '" class="btn btn-success rounded-pill px-4 py-2" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; font-size: 1rem; font-weight: bold; box-shadow: 0 4px 10px rgba(40,167,69,0.3);"><i class="fa fa-bar-chart mr-2"></i> ' . get_string('gotogradebook', 'local_xpstore') . '</a></div>';
    } else {
        echo $OUTPUT->notification(get_string('exito', 'local_xpstore'), 'success');
    }
}

if ($status === 'error') {
    echo $OUTPUT->notification(get_string('insuficiente', 'local_xpstore'), 'error');
} elseif ($status === 'limit') {
    echo '<div class="alert alert-warning text-center mb-3" style="border-radius: 10px; padding: 15px; font-weight: bold;">'.get_string('limitreached', 'local_xpstore').'</div>';
}

$saldo = local_xpstore_get_saldo($USER->id, $courseid);
$productos_raw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
$todos_los_productos = array_filter(array_map('trim', explode(',', $productos_raw)));
$is_teacher = has_capability('moodle/course:update', $context) || is_siteadmin();
$modinfo = get_fast_modinfo($courseid);

$cp_store = get_config('local_xpstore', 'color_primary_course_' . $courseid) ?: '#0056D2';
$cb_store = get_config('local_xpstore', 'color_secondary_course_' . $courseid) ?: '#00C9A7';
$ci_store = get_config('local_xpstore', 'color_icon_course_' . $courseid) ?: '#ff9800'; 
// NUEVA VARIABLE: Color de iconos de categorías
$cc_store = get_config('local_xpstore', 'color_cat_icon_course_' . $courseid) ?: '#e83e8c';

// Recuperamos los iconos configurados para las categorías
$cat_icons = json_decode(get_config('local_xpstore', 'cat_icons_course_' . $courseid), true) ?: [];
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');
    
    :root { 
        --cp: <?php echo $cp_store; ?>; 
        --cb: <?php echo $cb_store; ?>; 
        --ci: <?php echo $ci_store; ?>; 
        --cc: <?php echo $cc_store; ?>; /* <-- NUEVA VARIABLE CSS APLICADA */
        --gradient: linear-gradient(135deg, var(--cp) 0%, var(--cb) 100%); 
    }
    
    /* FORZAR ICONOS GRUESOS */
    .fa, .fas, .fa-solid { font-weight: 900 !important; }
    
    body, #page-wrapper, #page, #region-main { background: #ffffff !important; }
    
    .tienda-wrapper { font-family: 'Montserrat', sans-serif; background: #ffffff; padding: 25px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
    .wallet-header { background: var(--gradient); border-radius: 20px; padding: 25px 20px; color: white; text-align: center; box-shadow: 0 8px 20px rgba(0, 86, 210, 0.15); margin-bottom: 30px; position: relative; }
    .xp-balance { font-size: 2.8rem; font-weight: 800; margin: 0; line-height: 1.1; }
    .admin-controls { position: absolute; top: 12px; right: 12px; display: flex; gap: 6px; z-index: 100; }
    .admin-btn { background: white !important; color: var(--cp) !important; padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: bold; text-decoration: none !important; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: inline-block; }
    .product-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 240px)); justify-content: center; gap: 15px; }
    .p-card { background: white; border-radius: 16px; padding: 20px 15px; text-align: center; border: 1px solid #f0f0f0; transition: transform 0.2s; display: flex; flex-direction: column; justify-content: space-between; }
    .p-card:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0,0,0,0.06); }
    .grade-badge { background: #ffc107; color: #000; font-weight: bold; padding: 3px 10px; border-radius: 8px; font-size: 0.75rem; display: inline-block; margin-bottom: 8px; }
    .w-limit { font-size: 0.75rem; color: #777; font-weight: bold; margin-bottom: 8px; }
    .btn-buy { background: var(--gradient); color: white; border: none; border-radius: 10px; padding: 10px; font-weight: 700; width: 100%; margin-top: 12px; font-size: 0.9rem; transition: 0.2s; }
    .btn-buy:disabled { background: #e0e0e0; color: #888; cursor: not-allowed; }
    .btn-go-activity { background: #28a745; color: white; border: none; border-radius: 10px; padding: 10px; font-weight: 700; width: 100%; margin-top: 12px; font-size: 0.9rem; display: inline-block; text-decoration: none !important; }
    @media (max-width: 768px) {
        .wallet-header { display: flex; flex-direction: column; align-items: center; padding: 20px 15px; }
        .admin-controls { position: relative; top: 0; right: 0; margin-bottom: 20px; justify-content: center; flex-wrap: wrap; width: 100%; }
        .admin-btn { font-size: 0.8rem; padding: 8px 15px; }
        .xp-balance { font-size: 2.3rem; }
    }
</style>

<div class="tienda-wrapper">
    <div class="wallet-header">
        <?php if ($is_teacher): ?>
            <div class="admin-controls">
                <a href="<?php echo $report_url; ?>" class="admin-btn" style="background: var(--cb) !important; color: white !important;"><i class="fa fa-list-alt"></i> <?php echo get_string('audit', 'local_xpstore'); ?></a>
                <a href="<?php echo $config_url; ?>" class="admin-btn"><i class="fa fa-cog"></i> <?php echo get_string('configure', 'local_xpstore'); ?></a>
            </div>
        <?php endif; ?>
        <h6 class="text-uppercase small mb-1" style="letter-spacing: 1px; opacity: 0.9;"><?php echo get_string('saldo', 'local_xpstore'); ?></h6>
        <div class="xp-balance"><?php echo $saldo; ?> XP</div>
        <div class="mt-3"><a href="<?php echo $historyurl; ?>" class="btn btn-outline-light rounded-pill px-3 py-1" style="font-size: 0.85rem;"><i class="fa fa-history"></i> <?php echo get_string('history', 'local_xpstore'); ?></a></div>
    </div>

    <?php
    $categorias_tienda = array();
    foreach ($todos_los_productos as $item) {
        $tipo_char = substr($item, 0, 1);
        $rest = substr($item, 1);
        $parts = explode(':', $rest);
        if (count($parts) >= 2) {
            $cid = $parts[0] ?? ''; $costo = $parts[1] ?? ''; $n_custom = $parts[2] ?? '';
            $boost = $parts[3] ?? '0'; $nombre_cat = !empty($parts[4]) ? trim($parts[4]) : get_string('defaultcategory', 'local_xpstore');
            $limite = (int)($parts[5] ?? 0);
            $cm = $DB->get_record('course_modules', array('id' => $cid, 'course' => $courseid));
            if ($cm) { $categorias_tienda[$nombre_cat][] = array('tipo' => $tipo_char, 'cid' => $cid, 'costo' => $costo, 'n_custom' => $n_custom, 'boost' => $boost, 'limite' => $limite, 'cm' => $cm); }
        }
    }
    
    if (empty($categorias_tienda)) {
        echo '<div class="alert alert-info text-center" style="border-radius: 15px; border: none; background: #e9ecef; color: #495057; padding: 25px;"><i class="fa fa-shopping-basket fa-2x mb-2" style="color: #adb5bd;"></i><h5 class="mb-1">' . get_string('storeempty_title', 'local_xpstore') . '</h5><p class="small m-0">' . get_string('storeempty_desc', 'local_xpstore') . '</p></div>';
    } else {
        foreach ($categorias_tienda as $nombre_seccion => $productos) {
            // LÓGICA DE ICONO DE CATEGORÍA
            $cat_icon = isset($cat_icons[$nombre_seccion]) ? $cat_icons[$nombre_seccion] : 'trophy';
            
            echo '<h4 class="mt-4 mb-4 font-weight-bold" style="color: #333; font-size: 1.8rem; text-align: center; letter-spacing: -0.5px;">';
            // APLICAMOS EL NUEVO COLOR AL ICONO DE CATEGORÍA CON var(--cc)
            echo '<i class="fa fa-' . htmlspecialchars($cat_icon) . ' mr-2" style="color: var(--cc);"></i> ' . htmlspecialchars($nombre_seccion);
            echo '</h4><div class="product-grid" style="margin-bottom: 50px;">';
            
            foreach ($productos as $p) {
                $tipo = $p['tipo']; $cid = $p['cid']; $costo = $p['costo']; $n_custom = $p['n_custom']; $boost = $p['boost']; $limite = $p['limite']; $cm = $p['cm'];
                $modname = $DB->get_field('modules', 'name', array('id' => $cm->module));
                $n_real = $DB->get_field($modname, 'name', array('id' => $cm->instance));
                $icon_map = ['Q'=>'bolt', 'A'=>'file-text', 'F'=>'comments', 'G'=>'star', 'S'=>'unlock-alt'];
                $icon = $icon_map[$tipo] ?? 'gift';
                
                $compras_actuales = $DB->count_records('local_xpstore_gastos', array('userid' => $USER->id, 'itemid' => $cid, 'itemtype' => $tipo));
                $limite_alcanzado = ($limite > 0 && $compras_actuales >= $limite);
                ?>
                <div class="p-card">
                    <div>
                        <div style="font-size: 20px; color: var(--ci); margin-bottom: 10px;"><i class="fa fa-<?php echo $icon; ?>"></i></div>
                        <?php if ($tipo == 'G' && !empty($boost) && $boost != '0'): ?>
                            <div class="grade-badge">+<?php echo htmlspecialchars($boost); ?> <?php echo get_string('points', 'local_xpstore'); ?></div>
                        <?php endif; ?>
                        <?php if ($tipo == 'S'): ?>
                            <div class="grade-badge" style="background: var(--cb); color: white;"><?php echo get_string('specialcontent', 'local_xpstore'); ?></div>
                        <?php endif; ?>
                        <div class="font-weight-bold" style="font-size: 1rem; line-height: 1.2; margin-bottom: 4px;"><?php echo htmlspecialchars($n_custom ?: $n_real); ?></div>
                        <div class="text-muted small mb-2" style="font-size: 0.75rem;"><?php echo htmlspecialchars($n_real); ?></div>
                    </div>
                    <div>
                        <div class="h5 font-weight-bold text-primary m-0"><?php echo $costo; ?> XP</div>
                        <?php if ($limite > 0): ?>
                            <div class="w-limit"><?php echo get_string('redemptions_count', 'local_xpstore'); ?> <?php echo $compras_actuales; ?> / <?php echo $limite; ?></div>
                        <?php else: ?><div style="margin-bottom: 8px;"></div><?php endif; ?>
                        
                        <?php if ($status === 'success' && $bought_cmid == $cid && $tipo_compra == $tipo && $tipo != 'G'): 
                            $cm_url = isset($modinfo->cms[$cid]) ? $modinfo->cms[$cid]->url : new moodle_url('/course/view.php', array('id' => $courseid)); ?>
                            <a href="<?php echo $cm_url; ?>" class="btn-go-activity"><i class="fa fa-external-link mr-1"></i> <?php echo get_string('gotoactivity', 'local_xpstore'); ?></a>
                        <?php else: ?>
                            <form method="POST" action="<?php echo $url; ?>">
                                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                                <input type="hidden" name="action" value="comprar"><input type="hidden" name="cmid" value="<?php echo $cid; ?>">
                                <input type="hidden" name="tipo" value="<?php echo $tipo; ?>"><input type="hidden" name="costo" value="<?php echo $costo; ?>">
                                <?php if ($limite_alcanzado): ?>
                                    <button type="button" class="btn-buy" disabled style="background: #e0e0e0; color: #888;"><?php echo get_string('soldout', 'local_xpstore'); ?></button>
                                <?php else: ?>
                                    <button type="submit" class="btn-buy" <?php echo ($saldo < $costo) ? 'disabled' : ''; ?>><?php echo ($saldo < $costo) ? get_string('insuficiente', 'local_xpstore') : get_string('canjear', 'local_xpstore'); ?></button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            }
            echo '</div>'; 
        }
    }
    ?>
</div>
<?php echo $OUTPUT->footer(); ?>