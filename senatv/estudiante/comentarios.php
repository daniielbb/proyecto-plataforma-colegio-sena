<?php
/**
 * Comentarios del docente — SOLO LECTURA.
 * No existe formulario, botón de enviar, responder, editar ni eliminar.
 * Solo se muestran comentarios cuyo autor tiene rol 'profesor'.
 */
require __DIR__ . '/_base.php';

layout_inicio('Comentarios', 'comentarios.php', $ctx);
encabezado_pagina('Comentarios del docente', 'Retroalimentación escrita por tu docente sobre tu trabajo.');

if (!$tesis) {
    estado_vacio('comentario', 'Sin proyecto', 'Los comentarios aparecerán cuando tu proyecto esté registrado.');
    layout_fin();
    exit;
}

// Filtro: ?fase=ID (validada) o ?fase=general
$filtro = $_GET['fase'] ?? '';
$idFase = null;
if ($filtro === 'general') {
    $idFase = 0;
} elseif (($id = get_id('fase')) && est_buscar_fase($ctx['fases'], $id)) {
    $idFase = $id;
}
$comentarios = est_comentarios($pdo, (int)$tesis['id_tesis'], $idFase);
?>
<p class="solo-lectura aviso-lectura"><?= icono('candado') ?> Esta sección es solo de lectura. Los comentarios los escribe exclusivamente tu docente.</p>

<nav class="filtros" aria-label="Filtrar por fase">
  <a class="<?= $idFase === null ? 'activo' : '' ?>" href="<?= e(url('estudiante/comentarios.php')) ?>">Todos</a>
  <?php foreach ($ctx['fases'] as $f): if (!$f['n_comentarios']) continue; ?>
    <a class="<?= $idFase === (int)$f['id_fase'] ? 'activo' : '' ?>" href="<?= e(url('estudiante/comentarios.php?fase=' . (int)$f['id_fase'])) ?>">Fase <?= (int)$f['orden'] ?></a>
  <?php endforeach; ?>
  <a class="<?= $idFase === 0 ? 'activo' : '' ?>" href="<?= e(url('estudiante/comentarios.php?fase=general')) ?>">Generales</a>
</nav>

<?php if (!$comentarios) estado_vacio('comentario', 'Sin comentarios', 'No hay comentarios del docente para este filtro.'); ?>
<div class="lista-tarjetas">
  <?php foreach ($comentarios as $c) tarjeta_comentario($c); ?>
</div>
<?php layout_fin(); ?>
