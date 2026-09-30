<?php
/**
 * DASHBOARD del estudiante.
 * Flujo: grupo -> proyecto -> fase actual -> progreso -> documentos -> correcciones -> comentarios
 */
require __DIR__ . '/_base.php';

$fa          = $ctx['fase_actual'];
$prog        = $ctx['progreso'];
$correcciones = $tesis ? est_correcciones($pdo, (int)$tesis['id_tesis'], null, 3) : [];
$comentarios  = $tesis ? est_comentarios($pdo, (int)$tesis['id_tesis'], null, 3) : [];
$ultima       = $correcciones[0] ?? null;
$nDocs        = $tesis ? count(est_documentos($pdo, (int)$tesis['id_tesis'])) : 0;

layout_inicio('Inicio', 'inicio.php', $ctx);
?>
<div class="bienvenida">
  <div>
    <h1>Hola, <?= e($ctx['usuario']['nombre']) ?></h1>
    <p>Este es el resumen de tu proyecto académico.</p>
  </div>
  <?php if ($ctx['novedades']['total'] > 0): ?>
    <a class="aviso-novedad" href="<?= e(url('estudiante/correcciones.php')) ?>">
      <?= icono('campana') ?> Tienes <?= (int)$ctx['novedades']['total'] ?> novedad<?= $ctx['novedades']['total'] > 1 ? 'es' : '' ?> de tu docente
    </a>
  <?php endif; ?>
</div>

<?php if (!$tesis): ?>
  <?php estado_vacio('proyecto', 'Aún no tienes un proyecto registrado',
      'Ya perteneces al ' . $ctx['grupo']['nombre_grupo'] . '. Tu docente registrará el proyecto y sus fases; cuando lo haga aparecerán aquí.'); ?>
<?php else: ?>

<section class="resumen">
  <article class="tarjeta dato">
    <span class="dato-icono"><?= icono('proyecto') ?></span>
    <span class="dato-etiqueta">Proyecto</span>
    <strong class="dato-valor"><?= e($tesis['titulo']) ?></strong>
    <?= badge($tesis['estado']) ?>
  </article>

  <article class="tarjeta dato">
    <span class="dato-icono"><?= icono('grupo') ?></span>
    <span class="dato-etiqueta">Grupo</span>
    <strong class="dato-valor"><?= e($ctx['grupo']['nombre_grupo']) ?></strong>
    <small><?= e($ctx['grupo']['nombre_curso']) ?></small>
  </article>

  <article class="tarjeta dato">
    <span class="dato-icono"><?= icono('carpeta-abierta') ?></span>
    <span class="dato-etiqueta">Fase actual</span>
    <?php if ($fa): ?>
      <strong class="dato-valor">Fase <?= (int)$fa['orden'] ?> · <?= e($fa['nombre_fase']) ?></strong>
      <?= badge($fa['todas_completas'] ? 'Completada' : $fa['estado']) ?>
    <?php else: ?>
      <strong class="dato-valor">Sin fases definidas</strong>
    <?php endif; ?>
  </article>

  <article class="tarjeta dato">
    <span class="dato-icono"><?= icono('progreso') ?></span>
    <span class="dato-etiqueta">Progreso</span>
    <strong class="dato-valor dato-grande"><?= (int)$prog['porcentaje'] ?>%</strong>
    <?php barra_progreso($prog['porcentaje']); ?>
  </article>

  <article class="tarjeta dato">
    <span class="dato-icono"><?= icono('correccion') ?></span>
    <span class="dato-etiqueta">Última corrección</span>
    <?php if ($ultima): ?>
      <strong class="dato-valor"><?= e($ultima['nombre_documento'] ?: $ultima['nombre_fase'] ?: 'Proyecto') ?></strong>
      <small>Corregido el <?= e(fecha_larga($ultima['fecha'], false)) ?></small>
    <?php else: ?>
      <strong class="dato-valor">Sin correcciones</strong>
      <small>Tu docente aún no ha registrado correcciones.</small>
    <?php endif; ?>
  </article>

  <article class="tarjeta dato">
    <span class="dato-icono"><?= icono('calendario') ?></span>
    <span class="dato-etiqueta">Próxima entrega</span>
    <?php if ($fa && !$fa['todas_completas'] && $fa['fecha_entrega']):
        $dias = dias_restantes($fa['fecha_entrega']); ?>
      <strong class="dato-valor"><?= e(fecha_larga($fa['fecha_entrega'], false)) ?></strong>
      <small class="<?= $dias < 0 ? 'texto-alerta' : '' ?>"><?= e(texto_dias($dias)) ?><?= $fa['fecha_estimada'] ? ' · estimada' : '' ?></small>
    <?php elseif ($fa && $fa['todas_completas']): ?>
      <strong class="dato-valor">Proyecto completo</strong>
    <?php else: ?>
      <strong class="dato-valor">Por definir</strong>
      <small>El docente asignará la fecha.</small>
    <?php endif; ?>
  </article>
</section>

<section class="tarjeta bloque">
  <div class="bloque-cabecera">
    <h2><?= icono('progreso') ?> Progreso del proyecto</h2>
    <a href="<?= e(url('estudiante/fases.php')) ?>" class="enlace">Ver fases <?= icono('flecha') ?></a>
  </div>
  <?php barra_progreso($prog['porcentaje'], $prog['porcentaje'] . '% completado · ' . $prog['completadas'] . ' de ' . $prog['total'] . ' fases'); ?>

  <ol class="pasos">
    <?php foreach ($ctx['fases'] as $f): ?>
      <li class="paso paso-<?= clase_estado($f['estado']) ?><?= $fa && $f['id_fase'] === $fa['id_fase'] ? ' paso-actual' : '' ?>">
        <a href="<?= e(url('estudiante/fase.php?id=' . (int)$f['id_fase'])) ?>">
          <span class="paso-num"><?= $f['estado_bd'] === 'Completada' ? icono('check') : (int)$f['orden'] ?></span>
          <span class="paso-nombre"><?= e($f['nombre_fase']) ?></span>
          <small><?= e($f['estado']) ?></small>
        </a>
      </li>
    <?php endforeach; ?>
  </ol>

  <?php if ($fa): ?>
    <p class="siguiente-paso">
      <?= icono('flecha') ?><span>
      <?php if ($fa['todas_completas']): ?>
        Completaste todas las fases. Espera la valoración final de tu docente.
      <?php elseif ($fa['estado'] === 'Requiere corrección'): ?>
        Tu docente pidió ajustes en la <strong>Fase <?= (int)$fa['orden'] ?></strong>. Revisa la corrección antes de continuar.
      <?php elseif ($fa['estado_bd'] === 'Pendiente'): ?>
        La <strong>Fase <?= (int)$fa['orden'] ?></strong> aún no ha sido habilitada por tu docente.
      <?php else: ?>
        Continúa trabajando en la <strong>Fase <?= (int)$fa['orden'] ?> · <?= e($fa['nombre_fase']) ?></strong>. Cuando el docente la apruebe, avanzarás a la siguiente.
      <?php endif; ?></span>
    </p>
  <?php endif; ?>
</section>

<div class="dos-columnas">
  <section class="tarjeta bloque">
    <div class="bloque-cabecera">
      <h2><?= icono('correccion') ?> Últimas correcciones</h2>
      <a href="<?= e(url('estudiante/correcciones.php')) ?>" class="enlace">Ver todas <?= icono('flecha') ?></a>
    </div>
    <?php if (!$correcciones) estado_vacio('correccion', 'Sin correcciones', 'Cuando tu docente revise un documento verás aquí sus correcciones.'); ?>
    <div class="lista-tarjetas">
      <?php foreach ($correcciones as $c) tarjeta_correccion($c); ?>
    </div>
  </section>

  <section class="tarjeta bloque">
    <div class="bloque-cabecera">
      <h2><?= icono('comentario') ?> Comentarios del docente</h2>
      <a href="<?= e(url('estudiante/comentarios.php')) ?>" class="enlace">Ver todos <?= icono('flecha') ?></a>
    </div>
    <?php if (!$comentarios) estado_vacio('comentario', 'Sin comentarios', 'Aquí aparecerán los comentarios que tu docente escriba sobre tu trabajo.'); ?>
    <div class="lista-tarjetas">
      <?php foreach ($comentarios as $c) tarjeta_comentario($c); ?>
    </div>
  </section>
</div>

<p class="texto-suave centrado"><?= icono('documento') ?> Tienes <?= $nDocs ?> documento<?= $nDocs === 1 ? '' : 's' ?> registrado<?= $nDocs === 1 ? '' : 's' ?> en tu proyecto.
  <a href="<?= e(url('estudiante/documentos.php')) ?>">Ver documentos</a></p>

<?php endif; ?>
<?php layout_fin(); ?>
