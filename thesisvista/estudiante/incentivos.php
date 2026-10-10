<?php

require_once __DIR__ . '/includes/inicio.php';
exigir_grupo($grupo);

$incentivos = incentivos_estudiante($grupo, $id_estudiante);
$insignias  = insignias_estudiante($id_estudiante);
$cuenta = ['Obtenido' => 0, 'Pendiente' => 0, 'No obtenido' => 0];
foreach ($incentivos as $i) $cuenta[$i['resultado']]++;

$titulo    = 'Incentivos';
$subtitulo = 'Lo que tu docente reconoce en las fases y logros del proyecto';
$seccion   = 'incentivos';
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<div class="d-resumen">
    <div class="d-cifra"><?= icono('incentivo') ?><strong><?= $cuenta['Obtenido'] ?></strong><span>Obtenidos</span></div>
    <div class="d-cifra"><?= icono('reloj') ?><strong><?= $cuenta['Pendiente'] ?></strong><span>Pendientes</span></div>
    <div class="d-cifra"><?= icono('nota') ?><strong><?= count($insignias) ?></strong><span>Insignias</span></div>
</div>

<section class="tarjeta">
    <h2>Incentivos del grupo</h2>
    <?php if ($incentivos): ?>
        <div class="d-incentivos"><?php foreach ($incentivos as $i) echo tarjeta_incentivo($i); ?></div>
    <?php else: ?>
        <p class="vacio">Tu docente aún no ha definido incentivos para tu grupo.</p>
    <?php endif; ?>
</section>

<?php if ($insignias): ?>
    <section class="tarjeta">
        <h2>Mis insignias</h2>
        <ul class="d-otorgados">
            <?php foreach ($insignias as $b): ?>
                <li><span class="d-medalla"><?= e($b['icono'] ?: '🏅') ?></span>
                    <div><strong><?= e($b['nombre']) ?></strong><br><small><?= e($b['descripcion']) ?> · <?= e(fecha_corta($b['fecha_obtenido'])) ?>
                        <?= $b['titulo'] ? ' · ' . e($b['titulo']) : '' ?></small></div></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
