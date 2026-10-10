<?php

require_once __DIR__ . '/includes/inicio.php';
exigir_grupo($grupo);

$fases = fases_estudiante($id_grupo, $id_estudiante);
$comentarios = comentarios_estudiante($id_grupo, $id_estudiante);
$filtro = get_int('fase');
if ($filtro) $comentarios = array_values(array_filter($comentarios, fn($c) => (int) $c['id_fase'] === $filtro));

$por_fase = [];
foreach ($comentarios as $c) $por_fase[$c['id_fase'] ? 'Fase ' . $c['orden'] . ' · ' . $c['nombre_fase'] : 'Proyecto en general'][] = $c;
uksort($por_fase, 'strnatcmp');

$titulo    = 'Comentarios del docente';
$subtitulo = 'Retroalimentación para tu grupo y para ti';
$seccion   = 'comentarios';
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<form method="get" class="filtros d-filtros">
    <select name="fase" onchange="this.form.submit()" aria-label="Filtrar por fase">
        <option value="">Todas las fases</option>
        <?php foreach ($fases as $f): ?>
            <option value="<?= (int) $f['id_fase'] ?>" <?= $filtro === (int) $f['id_fase'] ? 'selected' : '' ?>>Fase <?= (int) $f['orden'] ?> · <?= e($f['nombre_fase']) ?></option>
        <?php endforeach; ?>
    </select>
    <noscript><button class="btn btn-secundario">Filtrar</button></noscript>
</form>

<?php if (!$por_fase): ?>
    <section class="tarjeta"><p class="vacio">No hay comentarios del docente<?= $filtro ? ' en esta fase' : '' ?>.</p></section>
<?php endif; ?>
<?php foreach ($por_fase as $nombre => $lista): ?>
    <section class="tarjeta">
        <h2><?= e($nombre) ?> <small class="d-tenue">· <?= count($lista) ?></small></h2>
        <?= lista_comentarios($lista, false) ?>
    </section>
<?php endforeach; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
