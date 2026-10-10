<?php

require_once __DIR__ . '/includes/inicio.php';

$cursos  = cursos_docente($id_docente);
$ids_cur = ids_cursos_docente($id_docente);
$id_curso = get_int('curso');
if ($id_curso && !docente_en_curso($id_docente, $id_curso)) $id_curso = 0;
$id_grupo = get_int('grupo');
$solo_mias = !empty($_GET['mias']);

$filtro = ['cursos' => $id_curso ? [$id_curso] : $ids_cur];
if ($id_grupo) $filtro['id_grupo'] = $id_grupo;
if ($solo_mias) $filtro['id_profesor'] = $id_docente;
$notas  = $ids_cur ? calificaciones($filtro) : [];
$grupos = grupos_docente($id_docente);

$promedio = $notas ? round(array_sum(array_column($notas, 'nota')) / count($notas), 1) : null;
$aprobadas = count(array_filter($notas, fn($n) => (float) $n['nota'] >= NOTA_APROBATORIA));

$titulo  = 'Calificaciones';
$seccion = 'calificaciones';
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<section class="d-resumen">
    <div class="d-cifra"><?= icono('nota') ?><strong><?= count($notas) ?></strong><span>Notas registradas</span></div>
    <div class="d-cifra"><?= icono('avance') ?><strong><?= formato_nota($promedio) ?></strong><span>Promedio</span></div>
    <div class="d-cifra"><?= icono('check') ?><strong><?= $aprobadas ?></strong><span>Aprobatorias (≥ <?= formato_nota(NOTA_APROBATORIA) ?>)</span></div>
</section>

<form class="filtros d-filtros" method="get">
    <select name="curso"><option value="">Todos los cursos</option>
        <?php foreach ($cursos as $c): ?><option value="<?= (int) $c['id_curso'] ?>" <?= $id_curso === (int) $c['id_curso'] ? 'selected' : '' ?>>Curso <?= e($c['nombre_curso']) ?></option><?php endforeach; ?></select>
    <select name="grupo"><option value="">Todos los grupos</option>
        <?php foreach ($grupos as $g): ?><option value="<?= (int) $g['id_grupo'] ?>" <?= $id_grupo === (int) $g['id_grupo'] ? 'selected' : '' ?>><?= e($g['nombre_curso'] . ' · ' . $g['nombre_grupo']) ?></option><?php endforeach; ?></select>
    <label class="d-casilla-linea"><input type="checkbox" name="mias" value="1" <?= $solo_mias ? 'checked' : '' ?>> Solo las mías</label>
    <button class="btn btn-secundario">Filtrar</button>
</form>

<section class="tarjeta">
    <?= tabla_calificaciones($notas) ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
