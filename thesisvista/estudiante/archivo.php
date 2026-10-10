<?php

require_once __DIR__ . '/includes/inicio.php';

$descargar = !empty($_GET['descargar']);

if ($id = get_int('entrega')) {
    $en = entrega_para_estudiante($id_estudiante, $id);
    if (!$en) { http_response_code(403); exit('No tiene permiso para ver este archivo.'); }
    enviar_archivo('entregas', $en['archivo_ruta'], $en['nombre_original'], $descargar);
}

if ($id = get_int('guia')) {
    exigir_grupo($grupo);
    $fase = buscar_fase(fases_estudiante($id_grupo, $id_estudiante), $id);
    if (!$fase || $fase['bloqueada'] || !$fase['archivo_guia']) { http_response_code(403); exit('Archivo no disponible.'); }
    enviar_archivo('guias', $fase['archivo_guia'], $fase['archivo_guia_nombre'], $descargar);
}

http_response_code(400);
exit('Solicitud no válida.');
