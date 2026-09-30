<?php
/** Últimas correcciones del docente (más reciente -> más antigua). Filtro opcional por fase. */
require __DIR__ . '/_base.php';

layout_inicio('Correcciones', 'correcciones.php', $ctx);
encabezado_pagina('Últimas correcciones', 'Revisiones realizadas por tu docente, desde la más reciente.');

if (!$tesis) {
    estado_vacio('correccion', 'Sin proyecto', 'Las correcciones aparecerán cuando tu proyecto esté registrado.');
    layout_fin();
    exit;
}

// Filtro por fase: solo se acepta si la fase pertenece al proyecto del estudiante
$idFase = get_id('fase');
if ($idFase && !est_buscar_fase($ctx['fases'], $idFase)) {
    $idFase = null;
}
$todas        = est_correcciones($pdo, (int)$tesis['id_tesis']);
$correcciones = $idFase ? array_values(array_filter($todas, fn($c) => (int)$c['id_fase'] === $idFase)) : $todas;

$conteo = ['Requiere ajustes' => 0, 'En revisión' => 0, 'Aprobada' => 0];
foreach ($todas as $c) {
    $conteo[$c['estado']] = ($conteo[$c['estado']] ?? 0) + 1;
}
?>
<section class="conteos">
  <div class="tarjeta mini"><span>Requieren ajustes</span><strong><?= $conteo['Requiere ajustes'] ?></strong></div>
  <div class="tarjeta mini"><span>En revisión</span><strong><?= $conteo['En revisión'] ?></strong></div>
  <div class="tarjeta mini"><span>Aprobadas</span><strong><?= $conteo['Aprobada'] ?></strong></div>
</section>

<nav class="filtros" aria-label="Filtrar por fase">
  <a class="<?= $idFase ? '' : 'activo' ?>" href="<?= e(url('estudiante/correcciones.php')) ?>">Todas</a>
  <?php foreach ($ctx['fases'] as $f): if (!$f['n_correcciones']) continue; ?>
    <a class="<?= $idFase === (int)$f['id_fase'] ? 'activo' : '' ?>" href="<?= e(url('estudiante/correcciones.php?fase=' . (int)$f['id_fase'])) ?>">Fase <?= (int)$f['orden'] ?></a>
  <?php endforeach; ?>
</nav>

<?php if (!$correcciones) estado_vacio('correccion', 'Sin correcciones', 'Tu docente aún no ha registrado correcciones.'); ?>
<div class="lista-tarjetas">
  <?php foreach ($correcciones as $i => $c): ?>
    <?php if ($i === 0 && !$idFase): ?><span class="etiqueta-mini etiqueta-destacada">Más reciente</span><?php endif; ?>
    <?php tarjeta_correccion($c); ?>
  <?php endforeach; ?>
</div>
<?php layout_fin(); ?>
