<?php
require_once __DIR__ . '/includes/inicio.php';

$cursos = cursos_docente($id_docente);
$id_curso = get_int('curso') ?: (int) ($cursos[0]['id_curso'] ?? 0);
$curso = $id_curso ? curso_para_docente($id_docente, $id_curso) : null;
if ($id_curso && !$curso) {
    mensaje('error', 'Ese curso no está asignado a usted.');
    redirigir('avances.php');
}
$fases  = $curso ? array_values(array_filter(fases_de_curso($id_curso), fn($f) => $f['estado'] !== 'Borrador')) : [];
$grupos = $curso ? grupos_de_curso($id_curso) : [];
$filas = [];
foreach ($grupos as $g) {
    $fg = fases_de_grupo((int) $g['id_grupo']);
    $filas[] = ['grupo' => $g, 'fases' => array_column($fg, null, 'id_fase'), 'progreso' => progreso_grupo((int) $g['id_grupo'], $fg)];
}
$promedio = $filas ? (int) round(array_sum(array_map(fn($f) => $f['progreso']['porcentaje'], $filas)) / count($filas)) : 0;

$titulo  = 'Avances';
$seccion = 'avances';
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<?php if (!$cursos): ?>
    <div class="tarjeta"><p class="vacio">No tiene cursos asignados.</p></div>
<?php else: ?>
<?= pestanas_cursos($cursos, $id_curso, 'avances.php') ?>

<section class="tarjeta">
    <div class="tarjeta-cabecera">
        <h2>Curso <?= e($curso['nombre_curso']) ?> · progreso promedio</h2>
        <div style="min-width:300px"><?= barra($promedio, true, 'grande') ?></div>
    </div>
    <?php if (!$grupos || !$fases): ?>
        <p class="vacio"><?= !$grupos ? 'El curso no tiene grupos.' : 'El curso no tiene fases publicadas.' ?></p>
    <?php else: ?>
        <div class="tabla-contenedor">
            <table class="d-matriz">
                <thead><tr><th>Grupo</th>
                    <?php foreach ($fases as $f): ?><th title="<?= e($f['nombre_fase']) ?>">Fase <?= (int) $f['orden'] ?><br><small style="text-transform:none;font-weight:400"><?= e(mb_strimwidth($f['nombre_fase'], 0, 18, '…')) ?></small></th><?php endforeach; ?>
                    <th style="min-width:180px">General</th></tr></thead>
                <tbody>
                <?php foreach ($filas as $fila): $g = $fila['grupo']; ?>
                    <tr>
                        <td><a href="grupo.php?id=<?= (int) $g['id_grupo'] ?>"><strong><?= e($g['nombre_grupo']) ?></strong></a><br><small><?= (int) $g['total_estudiantes'] ?> integrantes</small></td>
                        <?php foreach ($fases as $f): $c = $fila['fases'][$f['id_fase']] ?? null; ?>
                            <td><?php if (!$c): ?><small class="d-tenue">No asignada</small><?php else: ?>
                                <a class="d-celda <?= fase_terminada($c['estado']) ? 'hecha' : '' ?>" href="grupo_fase.php?grupo=<?= (int) $g['id_grupo'] ?>&fase=<?= (int) $f['id_fase'] ?>">
                                    <strong><?= simbolo_estado($c['estado']) ?> <?= (int) $c['porcentaje'] ?>%</strong>
                                    <span class="d-mini"><span style="width:<?= (int) $c['porcentaje'] ?>%"></span></span>
                                    <small><?= e($c['estado']) ?></small></a>
                            <?php endif; ?></td>
                        <?php endforeach; ?>
                        <td><?= barra($fila['progreso']['porcentaje']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="d-tenue" style="margin-bottom:0"><small>✓ aprobada/completada (100 %) · ● en curso (avance registrado) · ! requiere corrección · ○ pendiente.
            Si todas las fases tienen peso, el progreso general es ponderado.</small></p>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
