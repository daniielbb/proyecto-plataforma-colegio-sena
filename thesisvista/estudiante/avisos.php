<?php

require_once __DIR__ . '/includes/inicio.php';
exigir_grupo($grupo);

$fases  = fases_estudiante($id_grupo, $id_estudiante);
$avisos = avisos_estudiante($grupo, $id_estudiante, $fases);   
marcar_avisos_vistos($id_estudiante);                         

$titulo    = 'Avisos';
$subtitulo = 'Comentarios, revisiones, fases desbloqueadas, entregas, fechas límite e incentivos de tu grupo';
$seccion   = 'avisos';
require __DIR__ . '/includes/header.php';
?>

<section class="tarjeta">
    <?= lista_avisos($avisos) ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
