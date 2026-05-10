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
