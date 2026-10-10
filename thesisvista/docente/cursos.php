<?php
require_once __DIR__ . '/includes/inicio.php';

$cursos = cursos_docente($id_docente);

foreach ($cursos as &$c) {
    $pcts = [];
    foreach (grupos_de_curso((int) $c['id_curso']) as $g) {
        $pr = progreso_grupo((int) $g['id_grupo']);
        if ($pr['total']) $pcts[] = $pr['porcentaje'];
    }
    $c['progreso'] = $pcts ? (int) round(array_sum($pcts) / count($pcts)) : 0;
}
unset($c);

$titulo  = 'Cursos y grupos';
$seccion = 'cursos';
$migas = [['Mis cursos', null]];
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<?php if (!$cursos): ?>
    <div class="tarjeta"><p class="vacio">No tiene cursos asignados. El administrador asigna los cursos a cada docente.</p></div>
<?php else: ?>
<div class="d-carpetas">
    <?php foreach ($cursos as $c): ?>
        <a class="d-carpeta" href="curso.php?id=<?= (int) $c['id_curso'] ?>">
            <span class="d-carpeta-num">Curso</span>
            <h3 style="font-size:30px"><?= e($c['nombre_curso']) ?><?= is_numeric($c['nombre_curso']) ? '°' : '' ?></h3>
            <?php if (!empty($c['ficha'])): ?><p>Ficha <?= e($c['ficha']) ?></p><?php endif; ?>
            <div class="d-carpeta-datos">
                <span><?= icono('grupo', 14) ?><?= (int) $c['total_grupos'] ?> grupos</span>
                <span><?= icono('perfil', 14) ?><?= (int) $c['total_estudiantes'] ?> estudiantes</span>
                <span><?= icono('fases', 14) ?><?= (int) $c['total_fases'] ?> fases</span>
            </div>
            <?= barra($c['progreso']) ?>
            <small class="d-tenue">Progreso promedio de los grupos</small>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
