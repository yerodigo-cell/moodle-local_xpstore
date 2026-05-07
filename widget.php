<?php
/**
 * XP Store (local_xpstore)
 *
 * @package     local_xpstore
 * @copyright   2026 Yeison Díaz
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * widget.php - Recompensa Individual
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

global $USER, $PAGE, $OUTPUT, $DB;

$courseid = required_param('id', PARAM_INT);
$cmid_req = required_param('cmid', PARAM_INT);
$tipo_req = required_param('tipo', PARAM_ALPHA);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);
require_login($course);

$url = new moodle_url('/local/xpstore/widget.php', array('id' => $courseid, 'cmid' => $cmid_req, 'tipo' => $tipo_req));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('embedded'); 

$productos_raw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
$items = array_filter(explode(',', $productos_raw));
$producto = null;

foreach ($items as $item) {
    $tipo_char = substr($item, 0, 1);
    $rest = substr($item, 1);
    $parts = explode(':', $rest);
    if (count($parts) >= 2 && (int)$parts[0] === $cmid_req && $tipo_char === $tipo_req) {
        $cm = $DB->get_record('course_modules', array('id' => (int)$parts[0], 'course' => $courseid));
        if ($cm) {
            $modname = $DB->get_field('modules', 'name', array('id' => $cm->module));
            $producto = array(
                'tipo' => $tipo_char, 'cid' => (int)$parts[0], 'costo' => (int)($parts[1] ?? 0),
                'n_custom' => $parts[2] ?? '', 'boost' => $parts[3] ?? '0',
                'limite' => (int)($parts[5] ?? 0),
                'n_real' => $DB->get_field($modname, 'name', array('id' => $cm->instance))
            );
        }
        break;
    }
}

if (!$producto) { echo $OUTPUT->header(); echo "<div style='padding:20px; text-align:center;'>Producto no disponible.</div>"; echo $OUTPUT->footer(); die(); }

$compras_actuales = $DB->count_records('local_xpstore_gastos', array('userid' => $USER->id, 'itemid' => $producto['cid'], 'itemtype' => $producto['tipo']));
$limite_alcanzado = ($producto['limite'] > 0 && $compras_actuales >= $producto['limite']);

$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'comprar' && confirm_sesskey()) {
    if ($limite_alcanzado) {
        redirect(new moodle_url($url));
    } else {
        if (local_xpstore_comprar($USER->id, $tipo_req, $cmid_req, $producto['costo'], $courseid)) {
            local_xpstore_entregar_producto($USER->id, $cmid_req, $tipo_req, $courseid);
            redirect(new moodle_url($url, array('status' => 'success')));
        } else {
            redirect(new moodle_url($url, array('status' => 'error')));
        }
    }
}

$status = optional_param('status', '', PARAM_ALPHA);
$saldo = local_xpstore_get_saldo($USER->id, $courseid);

echo $OUTPUT->header();
$icon_map = ['Q'=>'bolt', 'A'=>'file-text', 'F'=>'comments', 'G'=>'star', 'S'=>'unlock-alt'];
$icon = $icon_map[$producto['tipo']] ?? 'gift';
$cp_store = get_config('local_xpstore', 'color_primary_course_' . $courseid) ?: '#0056D2';
$cb_store = get_config('local_xpstore', 'color_secondary_course_' . $courseid) ?: '#00C9A7';
$ci_store = get_config('local_xpstore', 'color_icon_course_' . $courseid) ?: '#ff9800'; 
?>

<!-- NUEVO LINK FONTAWESOME 6 Y STYLES -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');
    
    :root { 
        --cp: <?php echo $cp_store; ?>; 
        --cb: <?php echo $cb_store; ?>; 
        --ci: <?php echo $ci_store; ?>; 
        --gradient: linear-gradient(135deg, var(--cp) 0%, var(--cb) 100%); 
    }
    
    /* FORZAR ICONOS GRUESOS */
    .fa, .fas, .fa-solid { font-weight: 900 !important; }
    
    /* FONDO BLANCO PURO PARA EVITAR HEREDAR EL GRIS DEL NUEVO MOODLE */
    body, #page-wrapper, #page, #region-main { background: #ffffff !important; }
    body { margin: 0; padding: 10px; overflow: hidden; }
    
    .widget-card { font-family: 'Montserrat', sans-serif; background: white; border-radius: 18px; text-align: center; border: 1px solid #eee; height: 320px; box-shadow: 0 8px 20px rgba(0,0,0,0.06); display: flex; flex-direction: column; overflow: hidden; transition: transform 0.3s ease; box-sizing: border-box; }
    .widget-card:hover { transform: translateY(-3px); }
    .mini-wallet { background: var(--gradient); padding: 8px; color: white; font-size: 0.75rem; font-weight: bold; letter-spacing: 1px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15); }
    .card-body { padding: 15px; flex-grow: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; }
    .w-icon { font-size: 24px; color: var(--ci); margin-bottom: 8px; animation: pulse 2s infinite; }
    @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.1); } 100% { transform: scale(1); } }
    .w-title { font-weight: bold; font-size: 0.95rem; color: #222; line-height: 1.2; margin-bottom: 4px; padding: 0 10px; }
    .w-subtitle { font-size: 0.75rem; color: #999; margin-bottom: 12px; padding: 0 10px; }
    .w-badge { background: #ffc107; color: #000; font-size: 0.7rem; font-weight: bold; padding: 4px 10px; border-radius: 20px; display: inline-block; margin-bottom: 8px; white-space: nowrap; }
    .card-footer { padding: 15px; background: #fafafa; border-top: 1px solid #f0f0f0; }
    .w-cost { font-size: 1.2rem; font-weight: 800; color: #333; margin-bottom: 2px; }
    .w-limit { font-size: 0.7rem; color: #777; font-weight: bold; margin-bottom: 8px; }
    .btn-buy { background: var(--gradient); color: white; border: none; border-radius: 10px; padding: 8px; font-weight: bold; width: 100%; cursor: pointer; transition: 0.2s; font-size: 0.85rem;}
    .btn-buy:hover { opacity: 0.9; }
    .btn-buy:disabled { background: #e0e0e0; color: #888; cursor: not-allowed; }
    .btn-success-w { background: #28a745; color: white; border: none; border-radius: 10px; padding: 10px; font-weight: bold; width: 100%; display: block; text-decoration: none !important; font-size: 0.85rem; }
</style>

<div class="widget-card">
    <div class="mini-wallet"><?php echo get_string('balance', 'local_xpstore'); ?>:&nbsp;<?php echo $saldo; ?> XP</div>
    <div class="card-body">
        <?php if ($status === 'success'): ?>
            <div class="w-icon" style="color: #28a745; animation: none;"><i class="fa fa-check-circle"></i></div>
            <div class="w-title"><?php echo get_string('widgetunlocked', 'local_xpstore'); ?></div>
            <div class="w-subtitle"><?php echo get_string('widgetunlockeddesc', 'local_xpstore'); ?></div>
        <?php else: ?>
            <div class="w-icon"><i class="fa fa-<?php echo $icon; ?>"></i></div>
            <?php if ($producto['tipo'] == 'G' && $producto['boost'] != '0'): ?><div class="w-badge">+<?php echo htmlspecialchars($producto['boost']); ?> <?php echo get_string('points', 'local_xpstore'); ?></div>
            <?php elseif ($producto['tipo'] == 'S'): ?><div class="w-badge" style="background: var(--cb); color: white;"><?php echo get_string('specialcontent', 'local_xpstore'); ?></div><?php endif; ?>
            <div class="w-title"><?php echo htmlspecialchars($producto['n_custom'] ?: $producto['n_real']); ?></div>
            <div class="w-subtitle"><?php echo htmlspecialchars($producto['n_real']); ?></div>
        <?php endif; ?>
    </div>
    <div class="card-footer">
        <?php if ($status === 'success'): 
            $dest = ($producto['tipo'] == 'G') ? '/grade/report/user/index.php' : '/course/view.php';
            $dest_url = new moodle_url($dest, array('id' => $courseid));
            if ($producto['tipo'] != 'G') { $modinfo = get_fast_modinfo($courseid); $dest_url = $modinfo->cms[$producto['cid']]->url ?? $dest_url; }
        ?>
            <a href="<?php echo $dest_url; ?>" target="_top" class="btn-success-w"><?php echo ($producto['tipo'] == 'G') ? get_string('gotogradebook', 'local_xpstore') : get_string('gotoactivity', 'local_xpstore'); ?></a>
        <?php else: ?>
            <div class="w-cost"><?php echo $producto['costo']; ?> XP</div>
            <?php if ($producto['limite'] > 0): ?>
                <div class="w-limit"><?php echo get_string('redemptions_count', 'local_xpstore'); ?> <?php echo $compras_actuales; ?> / <?php echo $producto['limite']; ?></div>
            <?php else: ?><div style="margin-bottom: 8px;"></div><?php endif; ?>
            <form method="POST" action="<?php echo $url; ?>">
                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>"><input type="hidden" name="action" value="comprar">
                <?php if ($limite_alcanzado): ?>
                    <button type="button" class="btn-buy" disabled style="background: #e0e0e0; color: #888;"><?php echo get_string('soldout', 'local_xpstore'); ?></button>
                <?php else: ?>
                    <button type="submit" class="btn-buy" <?php echo ($saldo < $producto['costo']) ? 'disabled' : ''; ?>><?php echo ($saldo < $producto['costo']) ? get_string('insuficiente', 'local_xpstore') : get_string('canjear', 'local_xpstore'); ?></button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php echo $OUTPUT->footer(); ?>
