<?php
/**
 * XP Store (local_xpstore)
 *
 * @package     local_xpstore
 * @copyright   2026 Yeison Díaz
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Llamamos al nombre del plugin desde el archivo de idioma para que sea bilingüe
    $settings = new admin_settingpage('local_xpstore', get_string('pluginname', 'local_xpstore'));

    $settings->add(new admin_setting_configtextarea(
        'local_xpstore/quiz_id',
        get_string('quiz_id', 'local_xpstore'),      // Título de la caja
        get_string('quiz_id_desc', 'local_xpstore'), // El texto de advertencia (y formato)
        '', // <-- Valor por defecto vacío
        PARAM_RAW
    ));

    $ADMIN->add('localplugins', $settings);
}
