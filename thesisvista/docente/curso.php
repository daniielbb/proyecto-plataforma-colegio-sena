<?php

require_once __DIR__ . '/includes/inicio.php';

$id    = get_int('id');
$curso = curso_para_docente($id_docente, $id);
if (!$curso) {
    mensaje('error', 'Ese curso no está asignado a usted.');
    redirigir('cursos.php');
}
$grupos = grupos_de_curso($id);
$fases  = fases_de_curso($id);
$pdo = conectar();
$stmt = $pdo->prepare("SELECT en.id_grupo, COUNT(*) FROM entregas en JOIN grupos g ON g.id_grupo = en.id_grupo
                       WHERE g.id_curso = ? AND en.estado IN ('Entregado', 'Corregido', 'En revisión')
                         AND en.version = (SELECT MAX(x.version) FROM entregas x WHERE x.id_grupo = en.id_grupo AND x.id_fase = en.id_fase AND x.id_estudiante = en.id_estudiante)
                       GROUP BY en.id_grupo");
$stmt->execute([$id]);
$por_revisar = array_map('intval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR));
$nombre = $curso['nombre_curso'] . (is_numeric($curso['nombre_curso']) ? '°' : '');

$titulo  = 'Curso ' . $nombre;
$subtitulo = count($grupos) . ' grupo(s) · ' . count($fases) . ' fase(s)';
$seccion = 'cursos';
$migas = [['Mis cursos', 'cursos.php'], [$nombre, null]];
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<div class="d-barra-acciones" style="margin-bottom:6px">
    <a class="btn btn-secundario btn-chico" href="fases.php?curso=<?= $id ?>"><?= icono('fases', 15) ?> Fases del curso</a>
    <a class="btn btn-secundario btn-chico" href="avances.php?curso=<?= $id ?>"><?= icono('avance', 15) ?> Avance del curso</a>
    <a class="btn btn-secundario btn-chico" href="trabajos.php?curso=<?= $id ?>"><?= icono('trabajos', 15) ?> Trabajos del curso</a>
</div>

<?php if (!$grupos): ?>
    <div class="tarjeta"><p class="vacio">Este curso aún no tiene grupos. El administrador los crea en “Gestión de grupos”.</p></div>
<?php else: ?>
<div class="d-carpetas">
    <?php foreach ($grupos as $g): $pr = progreso_grupo((int) $g['id_grupo']); $n = $por_revisar[(int) $g['id_grupo']] ?? 0; ?>
        <a class="d-carpeta" href="grupo.php?id=<?= (int) $g['id_grupo'] ?>">
            <span class="d-carpeta-num"><?= e($nombre) ?> · Grupo</span>
            <h3><?= e($g['nombre_grupo']) ?></h3>
            <div class="d-carpeta-datos">
                <span><?= icono('perfil', 14) ?><?= (int) $g['total_estudiantes'] ?> integrantes</span>
                <span><?= icono('fases', 14) ?><?= $pr['terminadas'] ?>/<?= $pr['total'] ?> fases</span>
            </div>
            <?= barra($pr['porcentaje']) ?>
            <div class="d-carpeta-pie">
                <small><?= $pr['fase_actual'] ? 'Fase ' . (int) $pr['fase_actual']['orden'] . ' · ' . e($pr['fase_actual']['nombre_fase']) : ($pr['total'] ? 'Fases terminadas' : 'Sin fases') ?></small>
                <?= $n ? '<span class="etiqueta etiqueta-violeta">' . $n . ' por revisar</span>' : ($pr['fase_actual'] ? etiqueta($pr['fase_actual']['estado']) : '') ?>
            </div>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
