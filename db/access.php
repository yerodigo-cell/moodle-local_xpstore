<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'local/xpstore:manage' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ),
    ),
);