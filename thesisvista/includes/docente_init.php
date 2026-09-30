<?php
/**
 * Arranque común de todas las páginas de /docente:
 * conexión, sesión, verificación de rol "profesor".
 */
require_once __DIR__ . '/auth.php';

$docente = requerir_docente($pdo);
$doc     = (int)$docente['usuario_id'];
