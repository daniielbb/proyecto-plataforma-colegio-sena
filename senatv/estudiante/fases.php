<?php
/** Fases del proyecto representadas como carpetas. */
require __DIR__ . '/_base.php';

layout_inicio('Fases', 'fases.php', $ctx);
encabezado_pagina('Fases del proyecto', 'Entra a cada carpeta para ver sus documentos, correcciones, comentarios y requisitos.');

if (!$tesis) {
    estado_vacio('carpeta', 'Sin proyecto', 'Las fases aparecerán cuando tu docente registre tu proyecto.');
} elseif (!$ctx['fases']) {
    estado_vacio('carpeta', 'Sin fases definidas', 'Tu docente aún no ha establecido las fases del curso.');
} else {
    $prog = $ctx['progreso'];
    echo '<section class="tarjeta bloque bloque-compacto">';
    barra_progreso($prog['porcentaje'], $prog['porcentaje'] . '% completado · ' . $prog['completadas'] . ' de ' . $prog['total'] . ' fases');
    echo '<ul class="leyenda">'
       . '<li>' . badge('Completada') . '</li><li>' . badge('En progreso') . '</li>'
       . '<li>' . badge('Requiere corrección') . '</li><li>' . badge('Atrasada') . '</li><li>' . badge('Pendiente') . '</li></ul>';
    echo '</section>';

    echo '<div class="carpetas">';
    $idActual = $ctx['fase_actual'] ? (int)$ctx['fase_actual']['id_fase'] : null;
    foreach ($ctx['fases'] as $f) {
        carpeta_fase($f, $idActual);
    }
    echo '</div>';
}
layout_fin();
