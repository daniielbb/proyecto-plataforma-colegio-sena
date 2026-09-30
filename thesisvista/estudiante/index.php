<?php
/**
 * THESISVISTA - Entrada del módulo ESTUDIANTE (pendiente de desarrollo).
 *
 * Cuando exista estudiante/dashboard.php, esta página redirige allí automáticamente.
 */
if (is_file(__DIR__ . '/dashboard.php')) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../includes/seguridad.php';
$usuario = requerir_rol('estudiante');
$titulo_modulo = 'Estudiante';
require __DIR__ . '/../includes/pendiente.php';
