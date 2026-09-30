<?php
/** Mi grupo: integrantes y docentes del curso. Solo consulta (el grupo lo gestiona el docente/administrador). */
require __DIR__ . '/_base.php';

$g           = $ctx['grupo'];
$integrantes = est_integrantes($pdo, (int)$g['id_grupo']);
$docentes    = est_docentes_curso($pdo, (int)$g['id_curso']);
$idDirector  = $tesis ? (int)$tesis['id_profesor'] : 0;

layout_inicio('Mi grupo', 'grupo.php', $ctx);
encabezado_pagina($g['nombre_grupo'], $g['nombre_curso'] . ($g['ficha'] ? ' · Ficha ' . $g['ficha'] : ''));
?>
<?php if (count($ctx['grupos']) > 1): ?>
  <p class="texto-suave">También perteneces a:
    <?= e(implode(', ', array_map(fn($x) => $x['nombre_grupo'] . ' (' . $x['nombre_curso'] . ')',
        array_filter($ctx['grupos'], fn($x) => (int)$x['id_grupo'] !== (int)$g['id_grupo'])))) ?></p>
<?php endif; ?>

<section class="tarjeta bloque">
  <div class="bloque-cabecera">
    <h2><?= icono('grupo') ?> Integrantes (<?= count($integrantes) ?>)</h2>
    <span class="solo-lectura"><?= icono('candado') ?> Asignado por el docente</span>
  </div>
  <div class="tabla-contenedor">
    <table class="tabla">
      <thead><tr><th>Nombre</th><th>Rol</th><th>Curso</th><th>Estado</th></tr></thead>
      <tbody>
      <?php foreach ($integrantes as $m): $yo = (int)$m['usuario_id'] === $idEst; ?>
        <tr class="<?= $yo ? 'fila-yo' : '' ?>">
          <td data-titulo="Nombre">
            <span class="persona"><span class="avatar"><?= e(iniciales($m['nombre'], $m['apellido'])) ?></span>
            <?= e($m['nombre'] . ' ' . $m['apellido']) ?><?= $yo ? ' <em>(tú)</em>' : '' ?></span>
          </td>
          <td data-titulo="Rol"><?= $m['rol_grupo'] ? e($m['rol_grupo']) : '<span class="texto-suave">Sin rol asignado</span>' ?></td>
          <td data-titulo="Curso"><?= e($m['nombre_curso']) ?></td>
          <td data-titulo="Estado"><?= badge($m['estado']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="tarjeta bloque">
  <div class="bloque-cabecera"><h2><?= icono('birrete') ?> Docentes del curso</h2></div>
  <?php if (!$docentes) estado_vacio('perfil', 'Sin docentes asignados', 'El administrador aún no asigna docentes a este curso.'); ?>
  <ul class="personas">
    <?php foreach ($docentes as $d): ?>
      <li>
        <span class="avatar avatar-docente"><?= e(iniciales($d['nombre'], $d['apellido'])) ?></span>
        <div><strong><?= e($d['nombre'] . ' ' . $d['apellido']) ?></strong>
          <small><?= (int)$d['usuario_id'] === $idDirector ? 'Director de tu proyecto' : 'Docente del curso' ?></small></div>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php layout_fin(); ?>
