<?php
/**
 * Arranque común de TODAS las páginas del estudiante.
 *  1. Verifica sesión y rol 'estudiante' (si no, 403).
 *  2. Bloquea cualquier método distinto de GET (módulo solo lectura).
 *  3. Carga el contexto del estudiante desde la BD.
 *  4. Aplica el flujo: sin grupo -> pantalla informativa.
 */
if (!defined('SENATV_ESTUDIANTE')) {
    define('SENATV_ESTUDIANTE', true);
}
require_once __DIR__ . '/../includes/sesion.php';
require_once __DIR__ . '/../includes/iconos.php';
require_once __DIR__ . '/../includes/modelo_estudiante.php';
require_once __DIR__ . '/../includes/layout_estudiante.php';

requiere_rol('estudiante');
solo_lectura();

$pdo   = db();
$idEst = (int)$_SESSION['usuario_id'];
$ctx   = est_contexto($pdo, $idEst);

if (!$ctx['usuario']) {           // el usuario fue eliminado o cambió de rol
    cerrar_sesion();
    redirigir('login.php');
}

$paginaActual = basename($_SERVER['SCRIPT_NAME']);
$tieneGrupo   = $ctx['grupo'] !== null;
$tesis        = $ctx['tesis'];

// ¿Tiene grupo asignado? Si no, solo puede ver la pantalla informativa y su perfil.
if (!$tieneGrupo && !in_array($paginaActual, ['sin_grupo.php', 'perfil.php'], true)) {
    redirigir('estudiante/sin_grupo.php');
}
if ($tieneGrupo && $paginaActual === 'sin_grupo.php') {
    redirigir('estudiante/inicio.php');
}
