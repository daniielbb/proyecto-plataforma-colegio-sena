<?php
/** Detalle de una corrección. Se valida que pertenezca al proyecto del estudiante. */
require __DIR__ . '/_base.php';

$id = get_id('id');
$c  = ($tesis && $id) ? est_correccion($pdo, (int)$tesis['id_tesis'], $id) : null;

if (!$c) {
    http_response_code(404);
    layout_inicio('Corrección', 'correcciones.php', $ctx);
    estado_vacio('correccion', 'Corrección no encontrada', 'La corrección no existe o no pertenece a tu proyecto.');
    echo '<p class="centrado"><a class="boton" href="' . e(url('estudiante/correcciones.php')) . '">Volver</a></p>';
    layout_fin();
    exit;
}

layout_inicio('Corrección', 'correcciones.php', $ctx);

// Historial de correcciones del mismo documento (o de la misma fase si no hay documento)
$relacionadas = array_filter(
    est_correcciones($pdo, (int)$tesis['id_tesis'], $c['id_fase'] ? (int)$c['id_fase'] : null),
    fn($x) => (int)$x['id_correccion'] !== (int)$c['id_correccion']
        && ($c['id_documento'] ? (int)$x['id_documento'] === (int)$c['id_documento'] : true)
);

encabezado_pagina('Detalle de la corrección', '', 'estudiante/correcciones.php');
?>
<article class="tarjeta bloque correccion-detalle correccion-<?= clase_estado($c['estado']) ?>">
  <header class="bloque-cabecera">
    <div>
      <span class="etiqueta-mini">Documento</span>
      <h2><?= e($c['nombre_documento'] ?: 'Revisión general de la fase') ?></h2>
    </div>
    <?= badge($c['estado']) ?>
  </header>

  <dl class="ficha">
    <div><dt>Fecha</dt><dd><?= e(fecha_larga($c['fecha'])) ?></dd></div>
    <div><dt>Docente</dt><dd><?= e($c['prof_nombre'] . ' ' . $c['prof_apellido']) ?></dd></div>
    <div><dt>Fase</dt><dd>
      <?php if ($c['id_fase']): ?>
        <a href="<?= e(url('estudiante/fase.php?id=' . (int)$c['id_fase'])) ?>">Fase <?= (int)$c['orden'] ?> · <?= e($c['nombre_fase']) ?></a>
      <?php else: ?>—<?php endif; ?></dd></div>
    <div><dt>Nota / puntos</dt><dd><?= $c['calificacion'] !== null ? e($c['calificacion']) : 'Sin calificación' ?></dd></div>
    <?php if ($c['nombre_documento']): ?>
      <div><dt>Tipo de documento</dt><dd><?= e($c['tipo_documento']) ?> · subido el <?= e(fecha_corta($c['fecha_subida'])) ?></dd></div>
    <?php endif; ?>
  </dl>

  <h3 class="subtitulo">Observación del docente</h3>
  <blockquote class="observacion"><?= nl2br(e($c['observacion'])) ?></blockquote>
  <p class="solo-lectura"><?= icono('candado') ?> Esta corrección es de solo lectura. Si tienes dudas, consúltalas con tu docente en clase.</p>
</article>

<?php if ($relacionadas): ?>
<section class="tarjeta bloque">
  <div class="bloque-cabecera"><h2><?= icono('reloj') ?> Otras correcciones relacionadas</h2></div>
  <div class="lista-tarjetas"><?php foreach ($relacionadas as $r) tarjeta_correccion($r); ?></div>
</section>
<?php endif; ?>
<?php layout_fin(); ?>
