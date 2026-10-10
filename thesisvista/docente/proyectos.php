<?php

require_once __DIR__ . '/includes/inicio.php';

$todos = proyectos_docente($id_docente);
$ver   = $_GET['ver'] ?? 'dirigidos';
$dirigidos = array_values(array_filter($todos, fn($p) => (int) $p['es_director'] === 1));
$lista = $ver === 'todos' ? $todos : $dirigidos;
if ($ver !== 'todos' && !$dirigidos) { $lista = $todos; $ver = 'todos'; }

$buscar = trim($_GET['q'] ?? '');
if ($buscar !== '') {
    $lista = array_filter($lista, fn($p) => mb_stripos($p['titulo'] . ' ' . $p['est_nombre'] . ' ' . $p['est_apellido'] . ' ' . $p['nombre_grupo'], $buscar) !== false);
}

// Progreso por grupo (se calcula una vez por grupo)
$progreso = [];
foreach ($lista as $p) {
    if ($p['id_grupo'] && !isset($progreso[$p['id_grupo']])) $progreso[$p['id_grupo']] = progreso_grupo((int) $p['id_grupo']);
}
$clase_tesis = ['Borrador' => 'etiqueta-gris', 'En revisión' => 'etiqueta-violeta', 'Aprobada' => 'etiqueta-verde', 'Rechazada' => 'etiqueta-roja'];

$titulo  = 'Mis proyectos';
$seccion = 'proyectos';
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<nav class="d-pestanas">
    <a href="?ver=dirigidos" class="<?= $ver !== 'todos' ? 'activa' : '' ?>">Dirigidos por mí <span class="d-contador"><?= count($dirigidos) ?></span></a>
    <a href="?ver=todos" class="<?= $ver === 'todos' ? 'activa' : '' ?>">Todos los de mis cursos <span class="d-contador"><?= count($todos) ?></span></a>
</nav>

<form class="filtros" method="get">
    <input type="hidden" name="ver" value="<?= e($ver) ?>">
    <input type="search" name="q" value="<?= e($buscar) ?>" placeholder="Buscar por título, estudiante o grupo…">
    <button class="btn btn-secundario">Buscar</button>
</form>

<?php if (!$lista): ?>
    <div class="tarjeta"><p class="vacio">No hay proyectos para mostrar. Los proyectos los registra el administrador y se vinculan a un grupo de sus cursos.</p></div>
<?php else: ?>
<div class="d-carpetas">
    <?php foreach ($lista as $p): $pr = $p['id_grupo'] ? $progreso[$p['id_grupo']] : null; ?>
        <article class="d-carpeta">
            <span class="d-carpeta-num">Proyecto<?= (int) $p['es_director'] ? ' · lo dirijo' : '' ?></span>
            <h3><a class="d-carpeta-enlace" href="proyecto.php?id=<?= (int) $p['id_tesis'] ?>" style="color:inherit;text-decoration:none"><?= e($p['titulo']) ?></a></h3>
            <p><?= e(mb_strimwidth($p['resumen'], 0, 110, '…')) ?></p>
            <div class="d-carpeta-datos">
                <span><?= icono('perfil', 14) ?><?= e($p['est_nombre'] . ' ' . $p['est_apellido']) ?></span>
                <span><?= icono('grupo', 14) ?><?= $p['nombre_grupo'] ? e('Curso ' . $p['nombre_curso'] . ' · ' . $p['nombre_grupo']) : 'Sin grupo' ?></span>
            </div>
            <?php if ($pr): ?>
                <?= barra($pr['porcentaje']) ?>
                <small class="d-tenue"><?= $pr['fase_actual'] ? 'Fase actual: ' . (int) $pr['fase_actual']['orden'] . ' · ' . e($pr['fase_actual']['nombre_fase']) : ($pr['total'] ? 'Todas las fases terminadas' : 'Sin fases asignadas') ?></small>
            <?php endif; ?>
            <div class="d-carpeta-pie">
                <span class="etiqueta <?= $clase_tesis[$p['estado']] ?? 'etiqueta-gris' ?>"><?= e($p['estado']) ?></span>
                <small>Director(a): <?= e($p['prof_nombre'] . ' ' . $p['prof_apellido']) ?></small>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
