<?php
/**
 * Detalle de una fase ("abrir la carpeta").
 * El id recibido por URL se valida contra las fases del curso del estudiante:
 * no es posible consultar fases de otros cursos ni datos de otros proyectos.
 */
require __DIR__ . '/_base.php';

$idFase = get_id('id');
$fase   = ($tesis && $idFase) ? est_buscar_fase($ctx['fases'], $idFase) : null;

if (!$fase) {
    http_response_code(404);
    layout_inicio('Fase no encontrada', 'fases.php', $ctx);
    estado_vacio('carpeta', 'Fase no encontrada', 'La fase solicitada no existe o no pertenece a tu proyecto.');
    echo '<p class="centrado"><a class="boton" href="' . e(url('estudiante/fases.php')) . '">Volver a las fases</a></p>';
    layout_fin();
    exit;
}

$idTesis      = (int)$tesis['id_tesis'];
$documentos   = est_documentos($pdo, $idTesis, $idFase);
$correcciones = est_correcciones($pdo, $idTesis, $idFase);
$comentarios  = est_comentarios($pdo, $idTesis, $idFase);
$requisitos   = est_requisitos($pdo, $idFase, $idTesis);
$dias         = $fase['estado_bd'] !== 'Completada' ? dias_restantes($fase['fecha_entrega']) : null;

layout_inicio('Fase ' . $fase['orden'], 'fases.php', $ctx);
?>
<nav class="migas" aria-label="Ruta">
  <a href="<?= e(url('estudiante/fases.php')) ?>"><?= icono('carpeta') ?> Fases</a>
  <?= icono('flecha') ?>
  <span>Fase <?= (int)$fase['orden'] ?> · <?= e($fase['nombre_fase']) ?></span>
</nav>

<section class="tarjeta fase-cabecera fase-<?= clase_estado($fase['estado']) ?>">
  <span class="fase-icono"><?= icono('carpeta-abierta') ?></span>
  <div class="fase-titulos">
    <span class="etiqueta-mini">Fase <?= (int)$fase['orden'] ?> de <?= count($ctx['fases']) ?></span>
    <h1><?= e($fase['nombre_fase']) ?></h1>
    <p><?= e($fase['descripcion']) ?></p>
  </div>
  <?= badge($fase['estado']) ?>
</section>

<section class="fase-fechas">
  <div class="tarjeta mini"><?= icono('calendario') ?><span>Inicio</span><strong><?= e(fecha_corta($fase['fecha_inicio'])) ?></strong></div>
  <div class="tarjeta mini"><?= icono('reloj') ?><span>Fecha de entrega<?= $fase['fecha_estimada'] ? ' (estimada)' : '' ?></span>
    <strong><?= e(fecha_corta($fase['fecha_entrega'])) ?></strong>
    <?php if ($dias !== null && $fase['fecha_entrega']): ?><small class="<?= $dias < 0 ? 'texto-alerta' : '' ?>"><?= e(texto_dias($dias)) ?></small><?php endif; ?>
  </div>
  <div class="tarjeta mini"><?= icono('check') ?><span>Completada</span><strong><?= e(fecha_corta($fase['fecha_completada'])) ?></strong></div>
  <div class="tarjeta mini"><?= icono('lista') ?><span>Duración sugerida</span><strong><?= $fase['duracion_dias'] ? (int)$fase['duracion_dias'] . ' días' : '—' ?></strong></div>
</section>

<div class="dos-columnas">
  <section class="tarjeta bloque">
    <div class="bloque-cabecera"><h2><?= icono('lista') ?> Requisitos e indicaciones del docente</h2></div>
    <?php if ($fase['observaciones']): ?>
      <div class="nota-docente"><strong>Indicaciones para tu proyecto</strong><p><?= nl2br(e($fase['observaciones'])) ?></p></div>
    <?php endif; ?>
    <?php if ($requisitos): ?>
      <ul class="requisitos">
        <?php foreach ($requisitos as $r): ?>
          <li><?= icono('check') ?>
            <span><?= e($r['descripcion']) ?>
              <?php if (!$r['obligatorio']): ?><em class="etiqueta-opcional">Opcional</em><?php endif; ?>
              <?php if ($r['id_tesis']): ?><em class="etiqueta-opcional">Solo para tu proyecto</em><?php endif; ?>
            </span></li>
        <?php endforeach; ?>
      </ul>
    <?php elseif (!$fase['observaciones']): ?>
      <p class="texto-suave">El docente no ha registrado requisitos específicos para esta fase.</p>
    <?php endif; ?>
    <?php if ($fase['ejemplo']): ?>
      <div class="ejemplo"><strong>Ejemplo de referencia</strong><p><?= nl2br(e($fase['ejemplo'])) ?></p></div>
    <?php endif; ?>
  </section>

  <section class="tarjeta bloque">
    <div class="bloque-cabecera"><h2><?= icono('documento') ?> Documentos de la fase</h2></div>
    <?php if (!$documentos) estado_vacio('documento', 'Carpeta vacía', 'No hay documentos registrados en esta fase.'); ?>
    <ul class="docs"><?php foreach ($documentos as $d) fila_documento($d); ?></ul>
  </section>
</div>

<section class="tarjeta bloque">
  <div class="bloque-cabecera"><h2><?= icono('correccion') ?> Correcciones de esta fase</h2></div>
  <?php if (!$correcciones) estado_vacio('correccion', 'Sin correcciones', 'El docente aún no ha registrado correcciones para esta fase.'); ?>
  <div class="lista-tarjetas"><?php foreach ($correcciones as $c) tarjeta_correccion($c); ?></div>
</section>

<section class="tarjeta bloque">
  <div class="bloque-cabecera"><h2><?= icono('comentario') ?> Comentarios del docente</h2><span class="solo-lectura"><?= icono('candado') ?> Solo lectura</span></div>
  <?php if (!$comentarios) estado_vacio('comentario', 'Sin comentarios', 'No hay comentarios del docente en esta fase.'); ?>
  <div class="lista-tarjetas"><?php foreach ($comentarios as $c) tarjeta_comentario($c); ?></div>
</section>

<nav class="paginacion-fases">
  <?php if ($fase['anterior']): ?>
    <a class="boton boton-claro" href="<?= e(url('estudiante/fase.php?id=' . (int)$fase['anterior']['id_fase'])) ?>"><?= icono('volver') ?> Fase <?= (int)$fase['anterior']['orden'] ?></a>
  <?php else: ?><span></span><?php endif; ?>
  <?php if ($fase['siguiente']): ?>
    <a class="boton boton-claro" href="<?= e(url('estudiante/fase.php?id=' . (int)$fase['siguiente']['id_fase'])) ?>">Fase <?= (int)$fase['siguiente']['orden'] ?> <?= icono('flecha') ?></a>
  <?php endif; ?>
</nav>
<?php layout_fin(); ?>
