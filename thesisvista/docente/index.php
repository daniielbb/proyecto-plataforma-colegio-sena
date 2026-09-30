<?php
/**
 * THESISVISTA - Entrada del módulo DOCENTE (pendiente de desarrollo).
 *
 * Cuando exista docente/dashboard.php, esta página redirige allí automáticamente.
 */
if (is_file(__DIR__ . '/dashboard.php')) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../includes/seguridad.php';
$usuario = requerir_rol('profesor');
$titulo_modulo = 'Docente';
require __DIR__ . '/../includes/pendiente.php';
