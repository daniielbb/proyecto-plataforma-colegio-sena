<?php

require_once __DIR__ . '/includes/inicio.php';

$cursos = cursos_docente($id_docente);
$id_curso = get_int('curso') ?: (int) ($cursos[0]['id_curso'] ?? 0);
$curso = $id_curso ? curso_para_docente($id_docente, $id_curso) : null;
if ($id_curso && !$curso) {
    mensaje('error', 'Ese curso no está asignado a usted.');
    redirigir('fases.php');
}
$fases = $curso ? fases_de_curso($id_curso) : [];
$suma_pesos = array_sum(array_map(fn($f) => (float) $f['peso'], array_filter($fases, fn($f) => $f['estado'] !== 'Borrador')));
$con_peso = count(array_filter($fases, fn($f) => $f['peso'] !== null));

$titulo  = 'Fases';
$seccion = 'fases';
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<?php if (!$cursos): ?>
    <div class="tarjeta"><p class="vacio">No tiene cursos asignados todavía.</p></div>
<?php else: ?>

<?= pestanas_cursos($cursos, $id_curso, 'fases.php') ?>

<div class="tarjeta-cabecera">
    <h2 style="margin:0">Curso <?= e($curso['nombre_curso']) ?> · <?= count($fases) ?> fase(s)</h2>
    <a href="fase_form.php?curso=<?= $id_curso ?>" class="btn btn-primario"><?= icono('mas', 16) ?> Nueva fase</a>
</div>

<?php if ($con_peso && abs($suma_pesos - 100) > 0.01): ?>
    <div class="alerta alerta-aviso">Los pesos de las fases publicadas suman <strong><?= e(rtrim(rtrim(number_format($suma_pesos, 2), '0'), '.')) ?>%</strong>.
        Para que el progreso y la nota acumulada sean ponderados, todas las fases deben tener peso y sumar 100%.</div>
<?php endif; ?>

<div class="d-carpetas">
    <?php foreach ($fases as $i => $f): ?>
        <article class="d-carpeta <?= strtolower($f['estado']) ?>">
            <div class="d-carpeta-orden">
                <form method="post" action="fase_accion.php"><?= campo_csrf() ?>
                    <input type="hidden" name="id" value="<?= (int) $f['id_fase'] ?>"><input type="hidden" name="accion" value="subir">
                    <button title="Mover antes" <?= $i === 0 ? 'disabled' : '' ?>><?= icono('arriba', 15) ?></button></form>
                <form method="post" action="fase_accion.php"><?= campo_csrf() ?>
                    <input type="hidden" name="id" value="<?= (int) $f['id_fase'] ?>"><input type="hidden" name="accion" value="bajar">
                    <button title="Mover después" <?= $i === count($fases) - 1 ? 'disabled' : '' ?>><?= icono('abajo', 15) ?></button></form>
            </div>
            <span class="d-carpeta-num">Fase <?= (int) $f['orden'] ?></span>
            <h3><a class="d-carpeta-enlace" href="fase.php?id=<?= (int) $f['id_fase'] ?>" style="color:inherit;text-decoration:none"><?= e($f['nombre_fase']) ?></a></h3>
            <p><?= e(mb_strimwidth($f['descripcion'], 0, 110, '…')) ?></p>
            <div class="d-carpeta-datos">
                <span><?= icono('calendario', 14) ?><?= $f['fecha_inicio'] ? e(fecha_corta($f['fecha_inicio'])) . ' → ' : '' ?><?= limite($f['fecha_limite']) ?></span>
                <?php if ($f['duracion_dias']): ?><span><?= icono('reloj', 14) ?><?= (int) $f['duracion_dias'] ?> días</span><?php endif; ?>
                <?php if ($f['peso'] !== null): ?><span>Peso <?= e(rtrim(rtrim($f['peso'], '0'), '.')) ?>%</span><?php endif; ?>
                <span><?= icono('grupo', 14) ?><?= (int) $f['grupos_terminados'] ?>/<?= (int) $f['total_grupos'] ?> grupos terminaron</span>
                <?php if ((int) $f['total_criterios']): ?><span><?= icono('check', 14) ?><?= (int) $f['total_criterios'] ?> criterios</span><?php endif; ?>
            </div>
            <div class="d-carpeta-pie">
                <?= etiqueta($f['estado']) ?>
                <?php if ((int) $f['grupos_por_revisar']): ?><span class="etiqueta etiqueta-violeta"><?= (int) $f['grupos_por_revisar'] ?> por revisar</span><?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    <a class="d-carpeta nueva" href="fase_form.php?curso=<?= $id_curso ?>"><?= icono('mas', 28) ?><strong>Crear nueva fase</strong><small>Curso <?= e($curso['nombre_curso']) ?></small></a>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
