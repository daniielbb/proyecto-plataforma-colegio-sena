<?php
/** Mi proyecto: datos de la tesis, docente director, progreso e historial. */
require __DIR__ . '/_base.php';

layout_inicio('Mi proyecto', 'proyecto.php', $ctx);

if (!$tesis) {
    encabezado_pagina('Mi proyecto');
    estado_vacio('proyecto', 'Aún no tienes un proyecto registrado', 'Tu docente registrará el proyecto de tu grupo. Vuelve a consultar más adelante.');
    layout_fin();
    exit;
}

$prog      = $ctx['progreso'];
$historial = est_historial($pdo, (int)$tesis['id_tesis'], $ctx['fases']);
$iconoHist = ['fase' => 'carpeta-abierta', 'completada' => 'check', 'documento' => 'documento', 'correccion' => 'correccion', 'comentario' => 'comentario'];

encabezado_pagina('Mi proyecto', 'Información general de tu trabajo académico.');
?>
<section class="tarjeta bloque proyecto-ficha">
  <div class="proyecto-titulo">
    <span class="dato-icono"><?= icono('proyecto') ?></span>
    <div>
      <span class="etiqueta-mini">Título del proyecto</span>
      <h2><?= e($tesis['titulo']) ?></h2>
    </div>
    <?= badge($tesis['estado']) ?>
  </div>

  <h3 class="subtitulo">Resumen</h3>
  <p class="texto-largo"><?= nl2br(e($tesis['resumen'])) ?></p>

  <dl class="ficha">
    <div><dt>Docente director</dt><dd><?= e($tesis['prof_nombre'] . ' ' . $tesis['prof_apellido']) ?></dd></div>
    <div><dt>Fecha de registro</dt><dd><?= e(fecha_larga($tesis['fecha_registro'])) ?></dd></div>
    <div><dt>Grupo</dt><dd><?= e($ctx['grupo']['nombre_grupo']) ?></dd></div>
    <div><dt>Curso</dt><dd><?= e($ctx['grupo']['nombre_curso']) ?><?= $ctx['grupo']['ficha'] ? ' · Ficha ' . e($ctx['grupo']['ficha']) : '' ?></dd></div>
  </dl>

  <h3 class="subtitulo">Progreso general</h3>
  <?php barra_progreso($prog['porcentaje'], $prog['porcentaje'] . '% completado · ' . $prog['completadas'] . ' de ' . $prog['total'] . ' fases'); ?>
</section>

<section class="tarjeta bloque">
  <div class="bloque-cabecera"><h2><?= icono('reloj') ?> Historial de avances y correcciones</h2></div>
  <?php if (!$historial) estado_vacio('reloj', 'Sin movimientos', 'Todavía no hay avances registrados.'); ?>
  <ol class="linea-tiempo">
    <?php foreach ($historial as $h): ?>
      <li class="lt-<?= e($h['tipo']) ?>">
        <span class="lt-icono"><?= icono($iconoHist[$h['tipo']]) ?></span>
        <div>
          <strong>
            <?php if (!empty($h['id'])): ?>
              <a href="<?= e(url('estudiante/correccion.php?id=' . (int)$h['id'])) ?>"><?= e($h['titulo']) ?></a>
            <?php else: ?><?= e($h['titulo']) ?><?php endif; ?>
          </strong>
          <small><?= e($h['texto']) ?></small>
        </div>
        <time><?= e(fecha_corta($h['fecha'])) ?></time>
      </li>
    <?php endforeach; ?>
  </ol>
</section>
<?php layout_fin(); ?>
