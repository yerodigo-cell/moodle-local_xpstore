<?php
/**
 * XP Store (local_xpstore)
 *
 * @package     local_xpstore
 * @copyright   2026 Yeison Díaz
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_xpstore';
$plugin->version   = 2026050800;
$plugin->requires  = 2024100700; // Versión de Moodle 4.5 (Recomendado)
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = 'v1.0.0';

// Dependencias requeridas para que el plugin funcione
$plugin->dependencies = array(
    'block_xp' => ANY_VERSION // Exige que Level Up XP esté instalado
);
