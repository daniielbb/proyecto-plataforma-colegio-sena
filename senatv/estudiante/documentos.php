<?php
/** Documentos del proyecto organizados por fase (carpetas desplegables, sin JavaScript). */
require __DIR__ . '/_base.php';

layout_inicio('Documentos', 'documentos.php', $ctx);
encabezado_pagina('Documentos', 'Documentos de tu proyecto agrupados por fase. Solo consulta.');

if (!$tesis) {
    estado_vacio('documento', 'Sin proyecto', 'Los documentos aparecerán cuando tu proyecto esté registrado.');
    layout_fin();
    exit;
}

$docs = est_documentos($pdo, (int)$tesis['id_tesis']);

// Agrupar por fase respetando el orden de las fases del curso
$porFase = [];
foreach ($ctx['fases'] as $f) {
    $porFase[$f['id_fase']] = ['fase' => $f, 'docs' => []];
}
$sinFase = [];
foreach ($docs as $d) {
    if ($d['id_fase'] && isset($porFase[$d['id_fase']])) {
        $porFase[$d['id_fase']]['docs'][] = $d;
    } else {
        $sinFase[] = $d;
    }
}
?>
<p class="texto-suave"><?= count($docs) ?> documento<?= count($docs) === 1 ? '' : 's' ?> en total.</p>

<div class="arbol">
  <?php foreach ($porFase as $grupo):
      $f = $grupo['fase']; $n = count($grupo['docs']); ?>
    <details class="arbol-carpeta" <?= $n ? 'open' : '' ?>>
      <summary>
        <span class="arbol-icono"><?= icono($n ? 'carpeta-abierta' : 'carpeta') ?></span>
        <span class="arbol-nombre">Fase <?= (int)$f['orden'] ?> · <?= e($f['nombre_fase']) ?></span>
        <span class="arbol-contador"><?= $n ?></span>
        <?= badge($f['estado']) ?>
      </summary>
      <?php if ($n): ?>
        <ul class="docs"><?php foreach ($grupo['docs'] as $d) fila_documento($d); ?></ul>
      <?php else: ?>
        <p class="arbol-vacio">Carpeta vacía</p>
      <?php endif; ?>
    </details>
  <?php endforeach; ?>

  <?php if ($sinFase): ?>
    <details class="arbol-carpeta" open>
      <summary>
        <span class="arbol-icono"><?= icono('carpeta-abierta') ?></span>
        <span class="arbol-nombre">Otros documentos (sin fase asignada)</span>
        <span class="arbol-contador"><?= count($sinFase) ?></span>
      </summary>
      <ul class="docs"><?php foreach ($sinFase as $d) fila_documento($d); ?></ul>
    </details>
  <?php endif; ?>
</div>
<?php layout_fin(); ?>
