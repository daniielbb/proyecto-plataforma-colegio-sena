<?php

require_once __DIR__ . '/../../includes/academico.php';
require_once __DIR__ . '/componentes.php';

$docente    = requerir_rol('profesor');
$id_docente = (int) $docente['usuario_id'];

if (!modulo_docente_instalado()) {
    http_response_code(503);
    ?><!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Módulo docente · THESISVISTA</title><link rel="stylesheet" href="../css/admin.css"></head>
<body class="pagina-login"><main class="login-caja">
  <div class="login-marca"><span class="logo">TV</span><h1>Falta un paso</h1>
  <p>Para usar el panel docente, importe <strong>sql/actualizacion_docente.sql</strong> en la base <strong>senatv</strong> desde phpMyAdmin. No borra ningún dato.</p></div>
  <a href="../logout.php" class="btn btn-primario btn-bloque">Cerrar sesión</a>
</main></body></html><?php
    exit;
}

/** Atajo: lee un entero de GET. */
function get_int(string $clave): int
{
    return (int) ($_GET[$clave] ?? 0);
}

/** Atajo: valida CSRF o vuelve con error. */
function exigir_csrf(string $volver): void
{
    if (!csrf_valido()) {
        mensaje('error', 'El formulario expiró. Intente de nuevo.');
        redirigir($volver);
    }
}
