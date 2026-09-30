<?php
require_once __DIR__ . '/../includes/docente_init.php';

$ids = ids_grupos_docente($pdo, $doc);
$in  = marcadores($ids);
$st = $pdo->prepare(
    "SELECT g.id_grupo, g.nombre_grupo, g.id_curso, c.nombre_curso, c.ficha,
            (SELECT COUNT(*) FROM estudiante_grupo eg WHERE eg.id_grupo = g.id_grupo AND eg.estado = 'Activo') AS integrantes
       FROM grupos g
       JOIN cursos c ON c.id_curso = g.id_curso
      WHERE g.id_grupo IN ($in)
      ORDER BY g.nombre_grupo");
$st->execute($ids);
$grupos = $st->fetchAll();

foreach ($grupos as &$g) {
    $g['proyectos'] = proyectos_de_grupo($pdo, $doc, (int)$g['id_grupo'], (int)$g['id_curso']);
}
unset($g);

$titulo_pagina = 'Mis grupos';
$pagina_activa = 'grupos';
require __DIR__ . '/../includes/header.php';
?>

<p class="texto-suave">Grupos de los cursos que tienes asignados. Selecciona uno para ver su detalle, integrantes y fases.</p>

<?php if (!$grupos): ?>
    <div class="card vacio">
        <h2>Sin grupos asignados</h2>
        <p>Todavía no tienes cursos ni grupos asignados. El administrador es quien realiza esta asignación.</p>
    </div>
<?php else: ?>
<div class="rejilla">
    <?php foreach ($grupos as $g): $unico = count($g['proyectos']) === 1 ? $g['proyectos'][0] : null; ?>
    <article class="card grupo-card">
        <div class="grupo-nombre"><?= e($g['nombre_grupo']) ?></div>
        <h3><?= $unico ? 'Proyecto: ' . e($unico['titulo']) : e(count($g['proyectos']) . ' proyecto(s)') ?></h3>

        <ul class="datos">
            <li><span>Curso</span><strong><?= e($g['nombre_curso']) ?><?= $g['ficha'] ? ' · ' . e($g['ficha']) : '' ?></strong></li>
            <li><span>Integrantes</span><strong><?= (int)$g['integrantes'] ?></strong></li>
            <?php if ($unico): ?>
                <li><span>Fase actual</span><strong><?= $unico['progreso']['actual'] ? e($unico['progreso']['actual']['nombre_fase']) : 'Todas completadas' ?></strong></li>
                <li><span>Estado</span><strong><?= badge($unico['estado']) ?></strong></li>
            <?php endif; ?>
        </ul>

        <?php if ($unico): ?>
            <div class="progreso-fila" style="margin-bottom:1rem">
                <?= barra_progreso($unico['progreso']['porcentaje']) ?>
                <strong><?= $unico['progreso']['porcentaje'] ?>%</strong>
            </div>
        <?php elseif ($g['proyectos']): ?>
            <?php foreach ($g['proyectos'] as $p): ?>
                <div class="mini-proyecto">
                    <a href="proyecto.php?id=<?= (int)$p['id_tesis'] ?>"><strong><?= e($p['titulo']) ?></strong></a>
                    <div class="texto-suave chico">
                        <?= $p['progreso']['actual'] ? 'Fase: ' . e($p['progreso']['actual']['nombre_fase']) : 'Todas las fases completadas' ?>
                    </div>
                    <div class="progreso-fila"><?= barra_progreso($p['progreso']['porcentaje']) ?><strong><?= $p['progreso']['porcentaje'] ?>%</strong></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="texto-suave chico">Este grupo aún no tiene proyectos registrados.</p>
        <?php endif; ?>

        <div class="acciones botones" style="margin-top:1rem">
            <a class="btn" href="grupo.php?id=<?= (int)$g['id_grupo'] ?>">Ver grupo</a>
            <?php if ($unico): ?>
                <a class="btn btn-secundario" href="proyecto.php?id=<?= (int)$unico['id_tesis'] ?>">Ver proyecto</a>
            <?php endif; ?>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
