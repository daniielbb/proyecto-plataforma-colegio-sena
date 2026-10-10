<?php

require_once __DIR__ . '/includes/inicio.php';
exigir_grupo($grupo);
if (!ESTUDIANTE_VE_NOTAS) redirigir('dashboard.php');

$notas = calificaciones_estudiante($id_grupo, $id_estudiante);

$titulo    = 'Calificaciones';
$subtitulo = 'Notas registradas por el docente (escala ' . formato_nota(NOTA_MIN) . ' – ' . formato_nota(NOTA_MAX) . ')';
$seccion   = 'notas';
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<section class="tarjeta">
    <?php if (!$notas): ?>
        <p class="vacio">El docente aún no ha registrado calificaciones para tu grupo.</p>
    <?php else: ?>
        <div class="tabla-contenedor"><table>
            <thead><tr><th>Fase</th><th>Para</th><th>Nota</th><th>Docente</th><th>Observación</th></tr></thead>
            <tbody>
            <?php foreach ($notas as $n): ?>
                <tr>
                    <td><a href="fase.php?id=<?= (int) $n['id_fase'] ?>">Fase <?= (int) $n['orden'] ?> · <?= e($n['nombre_fase']) ?></a></td>
                    <td><?= $n['id_estudiante'] ? 'Tú' : 'Todo el grupo' ?><?= $n['version'] ? '<br><small>' . e($n['nombre_original']) . ' v' . (int) $n['version'] . '</small>' : '' ?></td>
                    <td><span class="d-nota <?= (float) $n['nota'] >= NOTA_APROBATORIA ? 'aprobada' : 'baja' ?>"><?= formato_nota($n['nota']) ?></span></td>
                    <td><small><?= e($n['nombre'] . ' ' . $n['apellido']) ?><br><?= e(fecha_corta($n['fecha_modificacion'] ?? $n['fecha'])) ?></small></td>
                    <td><small><?= nl2br(e($n['observacion'] ?? '')) ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <p class="ayuda d-tenue"><small>Nota aprobatoria: <?= formato_nota(NOTA_APROBATORIA) ?>.</small></p>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
