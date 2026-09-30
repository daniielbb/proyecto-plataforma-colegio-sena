<?php
/** Perfil del estudiante (consulta) y logros obtenidos (estudiante_incentivo). */
require __DIR__ . '/_base.php';

$u      = $ctx['usuario'];
$logros = est_incentivos($pdo, $idEst);

layout_inicio('Perfil', 'perfil.php', $ctx);
encabezado_pagina('Mi perfil', 'Tus datos registrados en la plataforma.');
?>
<div class="dos-columnas">
  <section class="tarjeta bloque perfil">
    <div class="perfil-cabecera">
      <span class="avatar avatar-xl"><?= e(iniciales($u['nombre'], $u['apellido'])) ?></span>
      <div>
        <h2><?= e($u['nombre'] . ' ' . $u['apellido']) ?></h2>
        <span class="rol-etiqueta">Estudiante</span>
      </div>
    </div>
    <dl class="ficha">
      <div><dt>Correo</dt><dd><?= e($u['correo']) ?></dd></div>
      <div><dt>Curso</dt><dd><?= $ctx['grupo'] ? e($ctx['grupo']['nombre_curso']) : '—' ?></dd></div>
      <div><dt>Grupo</dt><dd><?= $ctx['grupo'] ? e($ctx['grupo']['nombre_grupo']) : 'Sin grupo asignado' ?></dd></div>
      <div><dt>Ficha</dt><dd><?= $ctx['grupo'] && $ctx['grupo']['ficha'] ? e($ctx['grupo']['ficha']) : '—' ?></dd></div>
      <div><dt>Proyecto</dt><dd><?= $tesis ? e($tesis['titulo']) : 'Sin proyecto registrado' ?></dd></div>
      <div><dt>Acceso anterior</dt><dd><?= e(fecha_hora($_SESSION['acceso_anterior'] ?? null)) ?></dd></div>
    </dl>
    <p class="texto-suave">Para actualizar tus datos comunícate con el administrador de la plataforma.</p>
  </section>

  <section class="tarjeta bloque">
    <div class="bloque-cabecera"><h2><?= icono('medalla') ?> Mis logros</h2></div>
    <?php if (!$logros) estado_vacio('medalla', 'Aún sin logros', 'Completa tus fases a tiempo para obtener incentivos.'); ?>
    <ul class="logros">
      <?php foreach ($logros as $l): ?>
        <li>
          <span class="logro-icono"><?= $l['icono'] ? e($l['icono']) : icono('medalla') ?></span>
          <div><strong><?= e($l['nombre']) ?></strong><small><?= e($l['descripcion']) ?></small>
            <small class="texto-suave">Obtenido el <?= e(fecha_larga($l['fecha_obtenido'])) ?></small></div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>
<?php layout_fin(); ?>
