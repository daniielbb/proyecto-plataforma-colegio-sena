<?php

require_once __DIR__ . '/includes/inicio.php';
exigir_grupo($grupo);

$fases    = fases_estudiante($id_grupo, $id_estudiante);
$progreso = resumen_progreso($id_grupo, $fases);
$incentivos = incentivos_estudiante($grupo, $id_estudiante);
$inc_por_fase = [];
foreach ($incentivos as $i) if ($i['id_fase']) $inc_por_fase[(int) $i['id_fase']][] = $i;

$titulo    = 'Progreso del proyecto';
$subtitulo = $grupo['nombre_grupo'] . ' · fases configuradas por el docente';
$seccion   = 'fases';
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<section class="tarjeta">
    <div class="tarjeta-cabecera"><h2>Progreso general</h2><span class="d-tenue"><small><?= $progreso['terminadas'] ?> de <?= $progreso['total'] ?> fases completadas</small></span></div>
    <?= barra($progreso['porcentaje'], true, 'grande') ?>
    <ul class="e-estados">
        <?php foreach ($progreso['por_estado'] as $estado => $n): ?>
            <li><span class="etiqueta <?= ESTADOS_ESTUDIANTE[$estado] ?>"><?= e($estado) ?></span><strong><?= $n ?></strong></li>
        <?php endforeach; ?>
    </ul>
    
</section>

<?php if (!$fases): ?>
    <p class="vacio">El docente todavía no ha publicado fases para tu grupo. Aparecerán aquí automáticamente.</p>
<?php endif; ?>

<div class="d-carpetas">
    <?php foreach ($fases as $f): ?>
        <article class="d-carpeta e-fase <?= $f['bloqueada'] ? 'bloqueada' : '' ?>">
            <span class="d-carpeta-num">Fase <?= (int) $f['orden'] ?></span>
            <h3><a class="d-carpeta-enlace" href="fase.php?id=<?= (int) $f['id_fase'] ?>"><?= e($f['nombre_fase']) ?></a></h3>
            <div><?= etiqueta_fase($f) ?></div>
            <p><?= e(mb_strimwidth($f['descripcion'], 0, 160, '…')) ?></p>
            <?php if ($f['bloqueada']): ?>
                <p class="e-condicion"><?= icono('candado', 14) ?> <?= e($f['motivo_bloqueo']) ?></p>
            <?php else: ?>
                <?= barra($f['porcentaje']) ?>
            <?php endif; ?>
            <div class="d-carpeta-datos">
                <span><?= icono('calendario', 14) ?> <?= limite($f['fecha_limite'], fase_terminada($f['estado'])) ?></span>
                <?php if (!$f['bloqueada']): ?>
                    <span><?= icono('archivo', 14) ?> <?= (int) $f['total_entregas'] ?> entrega(s)</span>
                    <span><?= icono('comentario', 14) ?> <?= (int) $f['total_comentarios'] ?></span>
                <?php endif; ?>
                <?php if (!empty($inc_por_fase[(int) $f['id_fase']])): ?>
                    <span><?= icono('incentivo', 14) ?> <?= e(implode(' ', array_map(fn($i) => $i['icono'] ?: '🏆', $inc_por_fase[(int) $f['id_fase']]))) ?></span>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
