<?php
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

$url = new moodle_url('/local/xpstore/config.php', array('id' => $courseid));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('configtitle', 'local_xpstore'));

$action = optional_param('action', '', PARAM_ALPHANUMEXT);

$catalog_key = 'catalog_course_' . $courseid;

if ($action === 'togglemenu' && confirm_sesskey()) {
    $current_menu = get_config('local_xpstore', 'show_menu_course_' . $courseid);
    $new_menu = ($current_menu === '0') ? '1' : '0';
    set_config('show_menu_course_' . $courseid, $new_menu, 'local_xpstore');
    redirect($url);
}

$menu_visible = get_config('local_xpstore', 'show_menu_course_' . $courseid);
if ($menu_visible === false) { $menu_visible = '1'; }

if ($action === 'add' && confirm_sesskey()) {
    $tipo = required_param('tipo', PARAM_ALPHA);
    $cmid = required_param('cmid', PARAM_INT);
    $costo = required_param('costo', PARAM_INT);
    $nombre = required_param('nombre', PARAM_TEXT);
    $valor_nota = optional_param('valor_nota', '0', PARAM_FLOAT);
    $categoria = optional_param('categoria', '', PARAM_TEXT);
    $limite = optional_param('limite', 0, PARAM_INT);
    
    $categoria_limpia = str_replace(':', '-', trim($categoria));
    $current_config = get_config('local_xpstore', $catalog_key) ?: '';
    
    $new_item = "{$tipo}{$cmid}:{$costo}:{$nombre}:{$valor_nota}:{$categoria_limpia}:{$limite}";
    $updated = $current_config ? $current_config . ',' . $new_item : $new_item;
    set_config($catalog_key, $updated, 'local_xpstore');

    if ($tipo === 'S') {
        if (!$DB->record_exists('groups', array('courseid' => $courseid, 'name' => $nombre))) {
            $DB->insert_record('groups', (object)['courseid' => $courseid, 'name' => $nombre, 'timecreated' => time(), 'timemodified' => time()]);
        }
    }
    redirect($url, get_string('productadded', 'local_xpstore'));
}

if ($action === 'edit_save' && confirm_sesskey()) {
    $old_item = required_param('old_item', PARAM_RAW);
    $tipo = required_param('tipo', PARAM_ALPHA);
    $cmid = required_param('cmid', PARAM_INT);
    $costo = required_param('costo', PARAM_INT);
    $nombre = required_param('nombre', PARAM_TEXT);
    $valor_nota = optional_param('valor_nota', '0', PARAM_FLOAT);
    $categoria = optional_param('categoria', '', PARAM_TEXT);
    $limite = optional_param('limite', 0, PARAM_INT);
    
    $categoria_limpia = str_replace(':', '-', trim($categoria));
    $new_item = "{$tipo}{$cmid}:{$costo}:{$nombre}:{$valor_nota}:{$categoria_limpia}:{$limite}";
    
    $current_config = get_config('local_xpstore', $catalog_key) ?: '';
    $items = explode(',', $current_config);
    
    foreach ($items as $key => $item) {
        if (trim($item) === trim($old_item)) {
            $items[$key] = $new_item;
            break;
        }
    }
    
    set_config($catalog_key, implode(',', $items), 'local_xpstore');
    redirect($url, get_string('productupdated', 'local_xpstore'));
}

if ($action === 'delete' && confirm_sesskey()) {
    $item_to_delete = required_param('item', PARAM_RAW);
    $current_config = get_config('local_xpstore', $catalog_key) ?: '';
    $items = array_filter(explode(',', $current_config), function($i) use ($item_to_delete) {
        return trim($i) !== trim($item_to_delete);
    });
    set_config($catalog_key, implode(',', $items), 'local_xpstore');
    redirect($url, get_string('productdeleted', 'local_xpstore'));
}

if ($action === 'deleteall' && confirm_sesskey()) {
    set_config($catalog_key, '', 'local_xpstore');
    redirect($url, get_string('deletedall', 'local_xpstore'));
}

// --- GUARDADO DE COLORES ---
if ($action === 'savecolors' && confirm_sesskey()) {
    $cp = required_param('color_primary', PARAM_TEXT);
    $cb = required_param('color_secondary', PARAM_TEXT);
    $ci = required_param('color_icon', PARAM_TEXT);
    $cc = required_param('color_cat_icon', PARAM_TEXT); 
    
    set_config('color_primary_course_' . $courseid, $cp, 'local_xpstore');
    set_config('color_secondary_course_' . $courseid, $cb, 'local_xpstore');
    set_config('color_icon_course_' . $courseid, $ci, 'local_xpstore');
    set_config('color_cat_icon_course_' . $courseid, $cc, 'local_xpstore'); 
    redirect($url, get_string('colorssaved', 'local_xpstore'));
}

if ($action === 'resetcolors' && confirm_sesskey()) {
    unset_config('color_primary_course_' . $courseid, 'local_xpstore');
    unset_config('color_secondary_course_' . $courseid, 'local_xpstore');
    unset_config('color_icon_course_' . $courseid, 'local_xpstore');
    unset_config('color_cat_icon_course_' . $courseid, 'local_xpstore'); 
    redirect($url, get_string('colorsreset', 'local_xpstore'));
}

// --- GUARDADO DE ICONOS DE CATEGORÍA ---
if ($action === 'savecaticons' && confirm_sesskey()) {
    $cat_icons = [];
    $config_raw = get_config('local_xpstore', $catalog_key) ?: '';
    $items_raw = array_filter(explode(',', $config_raw));
    foreach ($items_raw as $it) {
        $parts = explode(':', substr($it, 1));
        if (count($parts) >= 2) {
            $cat = !empty($parts[4]) ? trim($parts[4]) : get_string('defaultcategory', 'local_xpstore');
            $input_name = 'caticon_' . md5($cat);
            if (isset($_POST[$input_name])) {
                $cat_icons[$cat] = clean_param($_POST[$input_name], PARAM_TEXT);
            }
        }
    }
    set_config('cat_icons_course_' . $courseid, json_encode($cat_icons), 'local_xpstore');
    redirect($url, get_string('icons_saved', 'local_xpstore'));
}

$cp_actual = get_config('local_xpstore', 'color_primary_course_' . $courseid) ?: '#0056D2';
$cb_actual = get_config('local_xpstore', 'color_secondary_course_' . $courseid) ?: '#00C9A7';
$ci_actual = get_config('local_xpstore', 'color_icon_course_' . $courseid) ?: '#ff9800'; 
// NUEVO: Recupera el valor actual o, si no existe, usa el color primario como respaldo
$cc_actual = get_config('local_xpstore', 'color_cat_icon_course_' . $courseid) ?: $cp_actual; 

$is_editing = false;
$e_tipo = ''; $e_cmid = 0; $e_costo = ''; $e_cat = ''; $e_nombre = ''; $e_valor = '0'; $e_limite = '0';
$old_item_val = '';

if ($action === 'load_edit') {
    $item_to_edit = required_param('item', PARAM_RAW);
    $tipo_char = substr($item_to_edit, 0, 1);
    $rest = substr($item_to_edit, 1);
    $parts = explode(':', $rest);
    
    if (count($parts) >= 2) {
        $e_tipo = $tipo_char;
        $e_cmid = (int)$parts[0];
        $e_costo = $parts[1];
        $e_nombre = $parts[2];
        $e_valor = $parts[3];
        $e_cat = $parts[4] ?? '';
        $e_limite = $parts[5] ?? '0';
        $old_item_val = $item_to_edit;
        $is_editing = true;
    }
}

echo $OUTPUT->header();
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');
    .config-wrapper { font-family: 'Montserrat', sans-serif; background: white; padding: 30px; border-radius: 20px; }
    
    .header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .header-title h2 { font-weight: bold; margin: 0; color: #333; }
    .btn-tienda { background: white; border: 1px solid #ccc; color: #555; padding: 8px 20px; border-radius: 50px; font-weight: bold; text-decoration: none !important; transition: 0.2s; }
    .btn-tienda:hover { background: #f0f0f0; }

    .form-box { background: #f8f9fa; padding: 25px; border-radius: 15px; border: 1px solid #eee; margin-bottom: 30px; transition: 0.3s; }
    .form-box.editing { background: #fff3cd; border-color: #ffeeba; }
    .btn-ucm-add { background: linear-gradient(135deg, #0056D2 0%, #00C9A7 100%); color: white !important; border: none; font-weight: bold; border-radius: 10px; padding: 10px 25px; transition: 0.3s; }
    .btn-ucm-edit { background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); color: white !important; border: none; font-weight: bold; border-radius: 10px; padding: 10px 25px; transition: 0.3s; }
    
    .table-responsive-wrapper { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 15px; }
    .table-products { width: 100%; border-collapse: separate; border-spacing: 0 10px; min-width: 900px; }
    .table-products tr { background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.03); transition: 0.2s; }
    .table-products tr:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.08); }
    .table-products td { vertical-align: middle; }
    
    .badge-tipo { padding: 5px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: bold; color: white; text-transform: uppercase; white-space: nowrap; display: inline-block; }
    .bg-q { background: #17a2b8; } .bg-a { background: #007bff; } .bg-g { background: #ffc107; color: #000; } .bg-s { background: #00c4cc; }
    .form-control-sm { height: 38px !important; }
    
    .widget-generator { background: #e0f7fa; border: 1px solid #b2ebf2; border-radius: 15px; padding: 20px; margin-bottom: 30px; }
    .btn-copy-w { background: white; border: 1px solid #00acc1; color: #00838f; border-radius: 8px; padding: 6px 15px; font-weight: bold; font-size: 0.85rem; transition: 0.2s; margin-right: 10px; margin-bottom: 10px; display: inline-block; }
    .btn-copy-w:hover { background: #00acc1; color: white; }

    @media (max-width: 768px) {
        .config-wrapper { padding: 15px; }
        .header-container { flex-direction: column; align-items: flex-start; gap: 15px; }
        .header-container > div:last-child { display: flex; flex-wrap: wrap; gap: 10px; width: 100%; }
        .btn-tienda { margin-right: 0; }
        .catalog-header-mobile { flex-direction: column !important; align-items: flex-start !important; gap: 15px; }
    }
</style>

<div class="config-wrapper">
    <div class="header-container">
        <div class="header-title">
            <h2><i class="fa fa-cog mr-2 text-muted"></i> <?php echo get_string('configtitle', 'local_xpstore'); ?></h2>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <form method="POST" action="<?php echo $url; ?>" style="margin: 0;">
                    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                    <input type="hidden" name="action" value="togglemenu">
                    <?php if ($menu_visible === '1'): ?>
                        <button type="submit" class="btn btn-sm btn-success" style="border-radius: 50px; font-weight: bold; padding: 8px 20px;" title="Ocultar del menú lateral">
                            <i class="fa fa-eye"></i> <?php echo get_string('menuvisible', 'local_xpstore'); ?>
                        </button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-sm btn-secondary" style="border-radius: 50px; font-weight: bold; padding: 8px 20px;" title="Mostrar en el menú lateral">
                            <i class="fa fa-eye-slash"></i> <?php echo get_string('menuhidden', 'local_xpstore'); ?>
                        </button>
                    <?php endif; ?>
                </form>
                <?php echo $OUTPUT->help_icon('menuvisibility', 'local_xpstore'); ?>
            </div>
            <a href="<?php echo new moodle_url('/local/xpstore/index.php', array('id' => $courseid)); ?>" class="btn-tienda m-0">
                <i class="fa fa-arrow-left"></i> <?php echo get_string('tiendaxp', 'local_xpstore'); ?>
            </a>
        </div>
    </div>

    <div class="form-box <?php echo $is_editing ? 'editing' : ''; ?>">
        <h5 class="mb-3 font-weight-bold d-flex align-items-center">
            <?php echo $is_editing ? '<i class="fa fa-pencil mr-2 text-warning"></i> ' . get_string('edit', 'local_xpstore') : get_string('addproduct', 'local_xpstore'); ?>
        </h5>
        
        <form method="POST" action="<?php echo $url; ?>" class="row align-items-end">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
            <input type="hidden" name="action" value="<?php echo $is_editing ? 'edit_save' : 'add'; ?>">
            <?php if ($is_editing): ?><input type="hidden" name="old_item" value="<?php echo htmlspecialchars($old_item_val); ?>"><?php endif; ?>
            
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <label class="small font-weight-bold mb-1 d-flex align-items-center" style="gap: 5px;">
                    <?php echo get_string('type', 'local_xpstore'); ?>
                    <?php echo $OUTPUT->help_icon('type', 'local_xpstore'); ?>
                </label>
                <select name="tipo" class="form-control form-control-sm" required>
                    <option value="" disabled <?php echo ($e_tipo == '') ? 'selected' : ''; ?>><?php echo get_string('choosetype', 'local_xpstore'); ?></option>
                    <option value="Q" <?php echo ($e_tipo == 'Q') ? 'selected' : ''; ?>><?php echo get_string('type_q', 'local_xpstore'); ?></option>
                    <option value="A" <?php echo ($e_tipo == 'A') ? 'selected' : ''; ?>><?php echo get_string('type_a', 'local_xpstore'); ?></option>
                    <option value="G" <?php echo ($e_tipo == 'G') ? 'selected' : ''; ?>><?php echo get_string('type_g', 'local_xpstore'); ?></option>
                    <option value="S" <?php echo ($e_tipo == 'S') ? 'selected' : ''; ?>><?php echo get_string('type_s', 'local_xpstore'); ?></option>
                </select>
            </div>

            <div class="col-12 col-md-8 col-lg-3 mb-3">
                <label class="small font-weight-bold mb-1" style="display:block;"><?php echo get_string('activity', 'local_xpstore'); ?></label>
                <select name="cmid" class="form-control form-control-sm" required>
                    <option value="" disabled <?php echo ($e_cmid == 0) ? 'selected' : ''; ?>><?php echo get_string('chooseactivity', 'local_xpstore'); ?></option>
                    <?php
                    $modinfo = get_fast_modinfo($courseid);
                    foreach ($modinfo->get_cms() as $cm) {
                        if ($cm->has_view() && !in_array($cm->modname, ['label', 'resource', 'contentview'])) {
                            $selected = ($cm->id == $e_cmid) ? 'selected' : '';
                            echo "<option value='{$cm->id}' {$selected}>[" . strtoupper($cm->modname) . "] " . $cm->get_formatted_name() . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="col-6 col-md-3 col-lg-1 mb-3">
                <label class="small font-weight-bold mb-1" style="display:block;"><?php echo get_string('cost', 'local_xpstore'); ?></label>
                <input type="number" name="costo" class="form-control form-control-sm" required value="<?php echo $e_costo; ?>">
            </div>

            <div class="col-6 col-md-3 col-lg-1 mb-3">
                <label class="small font-weight-bold mb-1" style="display:block; color:#0056D2;"><?php echo get_string('limit', 'local_xpstore'); ?></label>
                <input type="number" name="limite" min="0" class="form-control form-control-sm" placeholder="<?php echo get_string('limitzero', 'local_xpstore'); ?>" value="<?php echo $e_limite; ?>">
            </div>

            <div class="col-12 col-md-3 col-lg-2 mb-3">
                <label class="small font-weight-bold mb-1" style="display:block; line-height:1.2;"><?php echo get_string('category', 'local_xpstore'); ?></label>
                <input type="text" name="categoria" class="form-control form-control-sm" value="<?php echo htmlspecialchars($e_cat); ?>">
            </div>

            <div class="col-12 col-md-4 col-lg-2 mb-3">
                <label class="small font-weight-bold mb-1" style="display:block;"><?php echo get_string('label', 'local_xpstore'); ?></label>
                <input type="text" name="nombre" class="form-control form-control-sm" value="<?php echo htmlspecialchars($e_nombre); ?>">
            </div>

            <div class="col-6 col-md-2 col-lg-1 mb-3">
                <label class="small font-weight-bold mb-1" style="display:block;"><?php echo get_string('gradepoints', 'local_xpstore'); ?></label>
                <input type="number" step="0.1" name="valor_nota" class="form-control form-control-sm" value="<?php echo $e_valor; ?>">
            </div>

            <div class="col-12 d-flex justify-content-end mt-2">
                <?php if ($is_editing): ?>
                    <a href="<?php echo $url; ?>" class="btn btn-secondary px-4 py-2 mr-2" style="border-radius: 10px;"><?php echo get_string('cancel', 'local_xpstore'); ?></a>
                    <button type="submit" class="btn-ucm-edit px-4 py-2"><?php echo get_string('update', 'local_xpstore'); ?></button>
                <?php else: ?>
                    <button type="submit" class="btn-ucm-add px-4 py-2"><?php echo get_string('add', 'local_xpstore'); ?></button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="form-box" style="background: #e8f4f8; border-color: #bde0eb;">
        <h5 class="mb-3 font-weight-bold">
            <i class="fa fa-paint-brush mr-2" style="color: <?php echo htmlspecialchars($cp_actual); ?>;"></i> 
            <?php echo get_string('colorconfig', 'local_xpstore'); ?>
        </h5>
        
        <form method="POST" action="<?php echo $url; ?>" class="row align-items-end">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
            <input type="hidden" name="action" value="savecolors">

            <div class="col-6 col-md-3 col-lg-2 mb-3">
                <label class="small font-weight-bold mb-1" style="display:block;"><?php echo get_string('primarycolor', 'local_xpstore'); ?></label>
                <input type="color" name="color_primary" class="form-control" style="height: 40px; padding: 2px; cursor: pointer;" value="<?php echo htmlspecialchars($cp_actual); ?>">
            </div>

            <div class="col-6 col-md-3 col-lg-2 mb-3">
                <label class="small font-weight-bold mb-1" style="display:block;"><?php echo get_string('secondarycolor', 'local_xpstore'); ?></label>
                <input type="color" name="color_secondary" class="form-control" style="height: 40px; padding: 2px; cursor: pointer;" value="<?php echo htmlspecialchars($cb_actual); ?>">
            </div>

            <div class="col-6 col-md-3 col-lg-2 mb-3">
                <label class="small font-weight-bold mb-1" style="display:block;"><?php echo get_string('iconcolor', 'local_xpstore'); ?></label>
                <input type="color" name="color_icon" class="form-control" style="height: 40px; padding: 2px; cursor: pointer;" value="<?php echo htmlspecialchars($ci_actual); ?>">
            </div>
            
            <div class="col-6 col-md-3 col-lg-2 mb-3">
                <label class="small font-weight-bold mb-1" style="display:block;"><?php echo get_string('color_cat_icon', 'local_xpstore'); ?></label>
                <input type="color" name="color_cat_icon" class="form-control" style="height: 40px; padding: 2px; cursor: pointer;" value="<?php echo htmlspecialchars($cc_actual); ?>">
            </div>

            <div class="col-12 col-md-12 col-lg-4 mb-3 d-flex gap-2 justify-content-end">
                <a href="<?php echo $url; ?>&action=resetcolors&sesskey=<?php echo sesskey(); ?>" class="btn btn-outline-secondary px-3 py-2 font-weight-bold w-50" style="border-radius: 10px; background: white;" onclick="return confirm('<?php echo get_string('confirmresetcolors', 'local_xpstore'); ?>')">
                    <i class="fa fa-undo"></i> <?php echo get_string('resetcolors', 'local_xpstore'); ?>
                </a>
                <button type="submit" class="btn px-3 py-2 font-weight-bold w-50" style="background: <?php echo htmlspecialchars($cp_actual); ?>; color: white; border-radius: 10px; margin-left: 10px;">
                    <?php echo get_string('savecolors', 'local_xpstore'); ?>
                </button>
            </div>
        </form>
    </div>

    <?php 
    $config_raw = get_config('local_xpstore', $catalog_key) ?: '';
    if (!empty($config_raw)): 
        $items_raw = array_filter(explode(',', $config_raw));
        $categorias_unicas = array();
        
        foreach ($items_raw as $it) {
            $parts = explode(':', substr($it, 1));
            if (count($parts) >= 2) {
                $cat = !empty($parts[4]) ? trim($parts[4]) : get_string('defaultcategory', 'local_xpstore');
                $categorias_unicas[$cat] = $cat;
            }
        }
        global $CFG;
    ?>
    
    <div class="form-box" style="background: #fff4e6; border-color: #ffe8cc;">
        <h5 class="mb-3 font-weight-bold">
            <i class="fa fa-star mr-2" style="color: #ff9800;"></i> 
            <?php echo get_string('categoryicons', 'local_xpstore'); ?>
        </h5>
        
        <form method="POST" action="<?php echo $url; ?>" class="row align-items-end">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
            <input type="hidden" name="action" value="savecaticons">
            
            <?php 
            $saved_icons = json_decode(get_config('local_xpstore', 'cat_icons_course_' . $courseid), true) ?: [];
            $available_icons = [
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
                'gamepad' => get_string('icon_gamepad', 'local_xpstore')
            ];
            
            foreach ($categorias_unicas as $cat_name): 
                $current_icon = isset($saved_icons[$cat_name]) ? $saved_icons[$cat_name] : 'trophy';
            ?>
            <div class="col-12 col-md-3 mb-3">
                <label class="small font-weight-bold mb-1" style="display:block;"><?php echo htmlspecialchars($cat_name); ?></label>
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                        <span class="input-group-text" style="background: white;"><i class="fa fa-<?php echo htmlspecialchars($current_icon); ?>" id="preview_<?php echo md5($cat_name); ?>" style="color: <?php echo htmlspecialchars($cc_actual); ?>; font-size: 1.1rem; width: 20px; text-align: center;"></i></span>
                    </div>
                    <select name="caticon_<?php echo md5($cat_name); ?>" class="form-control" onchange="document.getElementById('preview_<?php echo md5($cat_name); ?>').className = 'fa fa-' + this.value">
                        <?php foreach ($available_icons as $ico_val => $ico_label): ?>
                            <option value="<?php echo $ico_val; ?>" <?php echo ($current_icon === $ico_val) ? 'selected' : ''; ?>><?php echo $ico_label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="col-12 d-flex justify-content-end mt-2">
                <button type="submit" class="btn px-4 py-2 font-weight-bold" style="background: #ff9800; color: white; border-radius: 10px;">
                    <i class="fa fa-save"></i> <?php echo get_string('saveicons', 'local_xpstore'); ?>
                </button>
            </div>
        </form>
    </div>

    <div class="widget-generator">
        <h6 class="font-weight-bold" style="color: #00838f;">
            <i class="fa fa-share-alt"></i> <?php echo get_string('widget_panel_title', 'local_xpstore'); ?>
        </h6>
        <p class="small text-muted mb-3">
            <?php echo get_string('widget_panel_desc', 'local_xpstore'); ?>
        </p>
        <div>
            <?php 
            $url_store = $CFG->wwwroot . "/local/xpstore/widget_category.php?id={$courseid}";
            $iframe_store = '<iframe src="'.$url_store.'" width="100%" height="700" style="border: none; border-radius: 15px;" allowfullscreen></iframe>';
            ?>
            <button class="btn-copy-w" style="background: #00838f; color: white;" onclick="copiarWidget('<?php echo htmlspecialchars($iframe_store, ENT_QUOTES); ?>')">
                <i class="fa fa-code"></i> <?php echo get_string('full_store', 'local_xpstore'); ?>
            </button>
            
            <?php 
            $url_history_w = $CFG->wwwroot . "/local/xpstore/widget_history.php?id={$courseid}";
            $iframe_history = '<iframe src="'.$url_history_w.'" width="100%" height="120" style="border: none; overflow: hidden;" scrolling="no"></iframe>';
            ?>
            <button class="btn-copy-w" style="background: white; border-color: #7d2ae8; color: #7d2ae8;" onclick="copiarWidget('<?php echo htmlspecialchars($iframe_history, ENT_QUOTES); ?>')">
                <i class="fa fa-history"></i> <?php echo get_string('history_button', 'local_xpstore'); ?>
            </button>
            
            <?php 
            foreach ($categorias_unicas as $cat_name) {
                $url_cat = $CFG->wwwroot . "/local/xpstore/widget_category.php?id={$courseid}&cat=".rawurlencode($cat_name);
                $iframe_cat = '<iframe src="'.$url_cat.'" width="100%" height="650" style="border: none; border-radius: 15px;" allowfullscreen></iframe>';
                ?>
                <button class="btn-copy-w" onclick="copiarWidget('<?php echo htmlspecialchars($iframe_cat, ENT_QUOTES); ?>')">
                    <i class="fa fa-folder-open"></i> <?php echo get_string('category_short', 'local_xpstore'); ?> <?php echo htmlspecialchars($cat_name); ?>
                </button>
                <?php
            }
            ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-end mb-2 mt-4 catalog-header-mobile">
        <h5 class="m-0 font-weight-bold text-muted" style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;"><?php echo get_string('currentcatalog', 'local_xpstore'); ?></h5>
        <?php if (!empty($config_raw)): ?>
            <a href="<?php echo $url; ?>&action=deleteall&sesskey=<?php echo sesskey(); ?>" class="btn btn-sm btn-outline-danger" style="border-radius: 20px; font-weight: bold; font-size: 0.8rem;" onclick="return confirm('<?php echo get_string('confirmdeleteall', 'local_xpstore'); ?>')">
                <i class="fa fa-trash"></i> <?php echo get_string('deleteall', 'local_xpstore'); ?>
            </a>
        <?php endif; ?>
    </div>

    <div class="table-responsive-wrapper">
        <table class="table-products">
            <thead>
                <tr style="background: transparent;">
                    <th class="small px-3"><?php echo get_string('coltype', 'local_xpstore'); ?></th>
                    <th class="small"><?php echo get_string('colcategory', 'local_xpstore'); ?></th>
                    <th class="small"><?php echo get_string('colactivity', 'local_xpstore'); ?></th>
                    <th class="small text-center"><?php echo get_string('colcost', 'local_xpstore'); ?></th>
                    <th class="small text-center" style="color: #0056D2;"><?php echo get_string('limit', 'local_xpstore'); ?></th>
                    <th class="small"><?php echo get_string('collabel', 'local_xpstore'); ?></th>
                    <th class="small text-right px-3"><?php echo get_string('colaction', 'local_xpstore'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $items = array_filter(explode(',', $config_raw));
                if (empty($items)) {
                    echo "<tr><td colspan='7' class='text-center text-muted py-4'>" . get_string('norewardscreated', 'local_xpstore') . "</td></tr>";
                }
                foreach ($items as $item) {
                    $tipo = substr($item, 0, 1);
                    $rest = substr($item, 1);
                    $parts = explode(':', $rest);
                    if (count($parts) >= 2) {
                        $cid = $parts[0] ?? '';
                        $cost = $parts[1] ?? '';
                        $name = $parts[2] ?? '';
                        $val = $parts[3] ?? '0';
                        $cat = !empty($parts[4]) ? trim($parts[4]) : get_string('defaultcategory', 'local_xpstore');
                        $limite_actual = isset($parts[5]) ? (int)$parts[5] : 0;
                        
                        $cm = $DB->get_record('course_modules', array('id' => $cid, 'course' => $courseid));
                        if (!$cm) continue;
                        
                        $real_name = $DB->get_field($DB->get_field('modules', 'name', array('id' => $cm->module)), 'name', array('id' => $cm->instance));
                        $label_tipo = get_string('type_'.strtolower($tipo), 'local_xpstore');
                        
                        global $CFG;
                        $widget_url = $CFG->wwwroot . "/local/xpstore/widget.php?id={$courseid}&tipo={$tipo}&cmid={$cid}";
                        $iframe_code = '<iframe src="'.$widget_url.'" style="width: 280px !important; max-width: 100%; height: 350px !important; border: none; overflow: hidden; border-radius: 15px; display: inline-block; margin: 10px;" scrolling="no"></iframe>';
                ?>
                    <tr>
                        <td class="px-3"><span class="badge-tipo bg-<?php echo strtolower($tipo); ?>"><?php echo $label_tipo; ?></span></td>
                        <td><span style="background: #f0f0f0; color: #555; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: bold;"><?php echo htmlspecialchars($cat); ?></span></td>
                        <td class="small"><?php echo htmlspecialchars($real_name); ?></td>
                        <td class="text-center font-weight-bold text-primary"><?php echo $cost; ?> XP</td>
                        <td class="text-center font-weight-bold" style="color: #666; font-size: 0.9rem;">
                            <?php echo ($limite_actual > 0) ? $limite_actual : '<span style="font-size: 1.2rem;">∞</span>'; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($name ?: $real_name); ?></strong> <?php if ($tipo == 'G') echo "(+$val)"; ?></td>
                        <td class="text-right px-3" style="min-width: 110px;">
                            <button type="button" class="btn btn-sm btn-info text-white mr-1" title="<?php echo get_string('copysinglecard', 'local_xpstore'); ?>" onclick="copiarWidget('<?php echo htmlspecialchars($iframe_code, ENT_QUOTES); ?>')">
                                <i class="fa fa-code"></i>
                            </button>
                            <a href="<?php echo $url; ?>&action=load_edit&item=<?php echo urlencode($item); ?>" class="btn btn-sm text-warning" title="<?php echo get_string('edit', 'local_xpstore'); ?>">
                                <i class="fa fa-pencil"></i>
                            </a>
                            <a href="<?php echo $url; ?>&action=delete&sesskey=<?php echo sesskey(); ?>&item=<?php echo urlencode($item); ?>" class="btn btn-sm text-danger" title="<?php echo get_string('delete', 'local_xpstore'); ?>" onclick="return confirm('<?php echo get_string('confirmdelete', 'local_xpstore'); ?>')">
                                <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php } } ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function copiarWidget(codigo) { navigator.clipboard.writeText(codigo).then(function() { alert("<?php echo get_string('copyalert', 'local_xpstore'); ?>"); }, function(err) { alert("Error: " + err); }); }
</script>
<?php echo $OUTPUT->footer(); ?>
