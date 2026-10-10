<?php

require_once __DIR__ . '/includes/inicio.php';

$cursos  = cursos_docente($id_docente);
$ids_cur = ids_cursos_docente($id_docente);
$id_grupo = get_int('grupo');
if ($id_grupo && !grupo_para_docente($id_docente, $id_grupo)) $id_grupo = 0;
$tipo = $_GET['tipo'] ?? '';
$solo_mios = ($_GET['de'] ?? 'mios') === 'mios';

$filtro = ['cursos' => $ids_cur, 'tipo' => $tipo];
if ($id_grupo)  $filtro['id_grupo'] = $id_grupo;
if ($solo_mios) $filtro['id_profesor'] = $id_docente;
$lista  = $ids_cur ? comentarios($filtro) : [];
$grupos = grupos_docente($id_docente);
$volver = 'comentarios.php?' . http_build_query(['grupo' => $id_grupo ?: null, 'tipo' => $tipo ?: null, 'de' => $solo_mios ? 'mios' : 'todos']);

$titulo  = 'Comentarios';
$seccion = 'comentarios';
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<form class="filtros d-filtros" method="get">
    <select name="de">
        <option value="mios" <?= $solo_mios ? 'selected' : '' ?>>Escritos por mí</option>
        <option value="todos" <?= !$solo_mios ? 'selected' : '' ?>>De todos los docentes de mis cursos</option></select>
    <select name="grupo"><option value="">Todos los grupos</option>
        <?php foreach ($grupos as $g): ?><option value="<?= (int) $g['id_grupo'] ?>" <?= $id_grupo === (int) $g['id_grupo'] ? 'selected' : '' ?>><?= e($g['nombre_curso'] . ' · ' . $g['nombre_grupo']) ?></option><?php endforeach; ?></select>
    <select name="tipo"><option value="">Todos los tipos</option>
        <?php foreach (TIPOS_COMENTARIO as $t): ?><option <?= $tipo === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select>
    <button class="btn btn-secundario">Filtrar</button>
</form>

<section class="tarjeta">
    <h2><?= count($lista) ?> comentario(s)</h2>
    <?= lista_comentarios($lista, $id_docente, true, $volver) ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
