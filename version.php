<?php

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_xpstore';
$plugin->version   = 2026050600;
$plugin->requires  = 2024100700; // Versión de Moodle 4.5 (Recomendado)
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = 'v2.0';

// Dependencias requeridas para que el plugin funcione
$plugin->dependencies = array(
    'block_xp' => ANY_VERSION // Exige que Level Up XP esté instalado
);
