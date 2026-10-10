<?php
require_once __DIR__ . '/includes/inicio.php';

$cursos = cursos_docente($id_docente);
$f = [
    'id_curso'    => get_int('curso'),
    'id_grupo'    => get_int('grupo'),
    'id_fase'     => get_int('fase'),
    'estado'      => $_GET['estado'] ?? '',
    'por_revisar' => ($_GET['ver'] ?? 'revisar') === 'revisar',
];
if ($f['id_curso'] && !docente_en_curso($id_docente, $f['id_curso'])) $f['id_curso'] = 0;
$ver = ($_GET['ver'] ?? 'revisar');
$grupos = grupos_docente($id_docente);
$fases_op = [];
foreach ($cursos as $c) if (!$f['id_curso'] || (int) $c['id_curso'] === $f['id_curso']) foreach (fases_de_curso((int) $c['id_curso']) as $x) $fases_op[] = $x + ['nombre_curso' => $c['nombre_curso']];

$entregas   = $ver === 'pendientes' ? [] : entregas_docente($id_docente, $f);
$pendientes = $ver === 'pendientes' ? trabajos_pendientes_docente($id_docente, $f) : [];

$titulo  = 'Trabajos';
$seccion = 'trabajos';
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
$qs = fn(array $extra) => '?' . http_build_query(array_filter(array_merge(['curso' => $f['id_curso'], 'grupo' => $f['id_grupo'], 'fase' => $f['id_fase']], $extra)));
?>

<nav class="d-pestanas">
    <a href="<?= e($qs(['ver' => 'revisar'])) ?>" class="<?= $ver === 'revisar' ? 'activa' : '' ?>">Por revisar <span class="d-contador"><?= total_por_revisar($id_docente) ?></span></a>
    <a href="<?= e($qs(['ver' => 'todos'])) ?>" class="<?= $ver === 'todos' ? 'activa' : '' ?>">Todos los entregados</a>
    <a href="<?= e($qs(['ver' => 'pendientes'])) ?>" class="<?= $ver === 'pendientes' ? 'activa' : '' ?>">Pendientes por entregar</a>
</nav>

<form class="filtros d-filtros" method="get">
    <input type="hidden" name="ver" value="<?= e($ver) ?>">
    <select name="curso"><option value="">Todos los cursos</option>
        <?php foreach ($cursos as $c): ?><option value="<?= (int) $c['id_curso'] ?>" <?= $f['id_curso'] === (int) $c['id_curso'] ? 'selected' : '' ?>>Curso <?= e($c['nombre_curso']) ?></option><?php endforeach; ?></select>
    <select name="grupo"><option value="">Todos los grupos</option>
        <?php foreach ($grupos as $g): if ($f['id_curso'] && (int) $g['id_curso'] !== $f['id_curso']) continue; ?>
            <option value="<?= (int) $g['id_grupo'] ?>" <?= $f['id_grupo'] === (int) $g['id_grupo'] ? 'selected' : '' ?>><?= e($g['nombre_curso'] . ' · ' . $g['nombre_grupo']) ?></option><?php endforeach; ?></select>
    <select name="fase"><option value="">Todas las fases</option>
        <?php foreach ($fases_op as $x): ?><option value="<?= (int) $x['id_fase'] ?>" <?= $f['id_fase'] === (int) $x['id_fase'] ? 'selected' : '' ?>><?= e($x['nombre_curso'] . ' · Fase ' . $x['orden'] . ' · ' . $x['nombre_fase']) ?></option><?php endforeach; ?></select>
    <?php if ($ver === 'todos'): ?>
        <select name="estado"><option value="">Cualquier estado</option>
            <?php foreach (ESTADOS_ENTREGA as $s): ?><option <?= $f['estado'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
    <?php endif; ?>
    <button class="btn btn-secundario">Filtrar</button>
</form>

<section class="tarjeta">
    <?php if ($ver === 'pendientes'): ?>
        <?php if (!$pendientes): ?>
            <p class="vacio">No hay trabajos pendientes con estos filtros.</p>
        <?php else: ?>
            <div class="tabla-contenedor"><table>
                <thead><tr><th>Grupo</th><th>Fase</th><th>Estado</th><th>Fecha límite</th><th>Entregas</th><th></th></tr></thead>
                <tbody><?php foreach ($pendientes as $t): ?>
                    <tr><td><strong><?= e($t['nombre_grupo']) ?></strong><br><small>Curso <?= e($t['nombre_curso']) ?></small></td>
                        <td>Fase <?= (int) $t['orden'] ?> · <?= e($t['nombre_fase']) ?></td>
                        <td><?= etiqueta($t['estado']) ?></td>
                        <td><?= limite($t['fecha_limite']) ?></td>
                        <td><?= (int) $t['total_entregas'] ?></td>
                        <td><a class="btn btn-secundario btn-chico" href="grupo_fase.php?grupo=<?= (int) $t['id_grupo'] ?>&fase=<?= (int) $t['id_fase'] ?>">Ver</a></td></tr>
                <?php endforeach; ?></tbody>
            </table></div>
        <?php endif; ?>
    <?php else: ?>
        <?php require __DIR__ . '/includes/tabla_entregas.php'; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
