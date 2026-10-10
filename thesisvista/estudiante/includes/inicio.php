<?php

require_once __DIR__ . '/../../includes/academico.php';
require_once __DIR__ . '/datos.php';
require_once __DIR__ . '/componentes.php';

$estudiante    = requerir_rol('estudiante');
$id_estudiante = (int) $estudiante['usuario_id'];

if (!modulo_docente_instalado() || !modulo_estudiante_instalado()) {
    http_response_code(503);
    ?><!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Panel estudiante · THESISVISTA</title><link rel="stylesheet" href="../css/admin.css"></head>
<body class="pagina-login"><main class="login-caja">
  <div class="login-marca"><span class="logo">TV</span><h1>Falta un paso</h1>
  <p>El administrador debe importar <strong>sql/actualizacion_docente.sql</strong> y luego
     <strong>sql/actualizacion_estudiante.sql</strong> en la base <strong>senatv</strong>. No borran ningún dato.</p></div>
  <a href="../logout.php" class="btn btn-primario btn-bloque">Cerrar sesión</a>
</main></body></html><?php
    exit;
}


$grupos = grupos_estudiante($id_estudiante);
$ids_grupos = array_map('intval', array_column($grupos, 'id_grupo'));

if (isset($_GET['grupo']) && in_array((int) $_GET['grupo'], $ids_grupos, true)) {
    $_SESSION['grupo_estudiante'] = (int) $_GET['grupo'];
}
if (!in_array((int) ($_SESSION['grupo_estudiante'] ?? 0), $ids_grupos, true)) {
    $_SESSION['grupo_estudiante'] = $ids_grupos[0] ?? 0;
}
$id_grupo = (int) $_SESSION['grupo_estudiante'];
$grupo = null;
foreach ($grupos as $g) if ((int) $g['id_grupo'] === $id_grupo) $grupo = $g;

function exigir_grupo(?array $grupo): void
{
    if (!$grupo) {
        mensaje('error', 'Todavía no tiene un grupo asignado. El administrador debe asignarlo.');
        redirigir('dashboard.php');
    }
}


function get_int(string $clave): int
{
    return (int) ($_GET[$clave] ?? 0);
}


function exigir_csrf(string $volver): void
{
    if (!csrf_valido()) {
        mensaje('error', 'El formulario expiró. Intente de nuevo.');
        redirigir($volver);
    }
}
