<?php

require_once __DIR__ . '/includes/inicio.php';

$pdo = conectar();
$cursos = cursos_docente($id_docente);
$id = get_int('id');
$inc = null;
if ($id) {
    $inc = incentivo_para_docente($id_docente, $id);
    if (!$inc || (int) $inc['id_profesor'] !== $id_docente) {
        mensaje('error', 'Solo puede editar los incentivos que usted creó.');
        redirigir($inc ? 'incentivo.php?id=' . $id : 'incentivos.php');
    }
}

$d = $inc ?: [
    'nombre' => '', 'icono' => '🏆', 'descripcion' => '', 'criterio' => '', 'valor' => '',
    'id_curso' => get_int('curso') ?: (count($cursos) === 1 ? (int) $cursos[0]['id_curso'] : 0),
    'id_fase' => get_int('fase') ?: null, 'id_tesis' => get_int('tesis') ?: null,
    'fecha_inicio' => date('Y-m-d'), 'fecha_fin' => '', 'estado' => 'Activo',
];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf($_SERVER['REQUEST_URI']);
    $t = fn($k, $max) => mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $max);
    $d = [
        'nombre' => $t('nombre', 150), 'icono' => in_array($_POST['icono'] ?? '', ICONOS_INCENTIVO, true) ? $_POST['icono'] : '🏆',
        'descripcion' => $t('descripcion', 5000), 'criterio' => $t('criterio', 5000), 'valor' => $t('valor', 100),
        'id_curso' => (int) ($_POST['id_curso'] ?? 0), 'id_fase' => (int) ($_POST['id_fase'] ?? 0) ?: null,
        'id_tesis' => (int) ($_POST['id_tesis'] ?? 0) ?: null,
        'fecha_inicio' => $t('fecha_inicio', 10), 'fecha_fin' => $t('fecha_fin', 10),
        'estado' => in_array($_POST['estado'] ?? '', ['Activo', 'Inactivo', 'Finalizado'], true) ? $_POST['estado'] : 'Activo',
    ];
    if ($d['nombre'] === '')   $errores[] = 'Escriba el nombre del incentivo.';
    if ($d['criterio'] === '') $errores[] = 'Escriba el criterio necesario para obtenerlo.';
    if (!docente_en_curso($id_docente, $d['id_curso'])) $errores[] = 'Seleccione uno de sus cursos.';
    if ($d['id_fase']) {
        $f = fase_para_docente($id_docente, $d['id_fase']);
        if (!$f || (int) $f['id_curso'] !== $d['id_curso']) $errores[] = 'La fase elegida no pertenece al curso.';
    }
    if ($d['id_tesis']) {
        $p = tesis_para_docente($id_docente, $d['id_tesis']);
        if (!$p || (int) $p['id_curso'] !== $d['id_curso']) $errores[] = 'El proyecto elegido no pertenece al curso.';
    }
    foreach (['fecha_inicio', 'fecha_fin'] as $k) {
        if ($d[$k] !== '' && !DateTime::createFromFormat('Y-m-d', $d[$k])) $errores[] = 'Revise las fechas.';
    }
    if ($d['fecha_inicio'] && $d['fecha_fin'] && $d['fecha_fin'] < $d['fecha_inicio']) $errores[] = 'La fecha final no puede ser anterior a la inicial.';

    if (!$errores) {
        $v = [$d['nombre'], $d['icono'], $d['descripcion'] ?: null, $d['criterio'], $d['valor'] ?: null, $d['id_curso'],
              $d['id_fase'], $d['id_tesis'], $d['fecha_inicio'] ?: null, $d['fecha_fin'] ?: null, $d['estado']];
        try {
            if ($inc) {
                $pdo->prepare('UPDATE incentivos_grupales SET nombre = ?, icono = ?, descripcion = ?, criterio = ?, valor = ?, id_curso = ?,
                                      id_fase = ?, id_tesis = ?, fecha_inicio = ?, fecha_fin = ?, estado = ?
                               WHERE id_incentivo_grupal = ? AND id_profesor = ?')->execute([...$v, $id, $id_docente]);
            } else {
                $pdo->prepare("INSERT INTO incentivos_grupales (nombre, icono, descripcion, criterio, valor, id_curso, id_fase, id_tesis,
                                      fecha_inicio, fecha_fin, estado, id_profesor, tipo_criterio)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'personalizado')")->execute([...$v, $id_docente]);
                $id = (int) $pdo->lastInsertId();
            }
            mensaje('exito', $inc ? 'Incentivo actualizado.' : 'Incentivo creado. Ya puede otorgarlo a los grupos.');
            redirigir('incentivo.php?id=' . $id);
        } catch (PDOException $ex) {
            error_log('incentivo_form: ' . $ex->getMessage());
            $errores[] = ($ex->errorInfo[0] ?? '') === '45000' ? $ex->errorInfo[2] : 'No se pudo guardar el incentivo.';
        }
    }
}


$fases_op = []; $tesis_op = [];
foreach ($cursos as $c) {
    foreach (fases_de_curso((int) $c['id_curso']) as $f) $fases_op[] = $f;
}
foreach (proyectos_docente($id_docente) as $p) if ($p['id_curso'] && docente_en_curso($id_docente, (int) $p['id_curso'])) $tesis_op[] = $p;

$titulo  = $inc ? 'Editar incentivo' : 'Crear incentivo';
$seccion = 'incentivos';
$migas = [['Incentivos', 'incentivos.php'], [$inc ? $inc['nombre'] : 'Nuevo', null]];
require __DIR__ . '/includes/header.php';
?>

<?php if ($errores): ?>
    <div class="alerta alerta-error"><ul><?php foreach ($errores as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="formulario tarjeta" style="max-width:820px">
    <?= campo_csrf() ?>
    <label>Ícono</label>
    <div class="d-iconos">
        <?php foreach (ICONOS_INCENTIVO as $ic): ?>
            <label><input type="radio" name="icono" value="<?= e($ic) ?>" <?= ($d['icono'] ?: '🏆') === $ic ? 'checked' : '' ?>><span><?= e($ic) ?></span></label>
        <?php endforeach; ?>
    </div>
    <label for="nombre">Nombre del incentivo *</label>
    <input id="nombre" name="nombre" required maxlength="150" value="<?= e($d['nombre']) ?>" placeholder="Ej: Equipo destacado">
    <label for="descripcion">Descripción</label>
    <textarea id="descripcion" name="descripcion" style="min-height:70px"><?= e($d['descripcion'] ?? '') ?></textarea>
    <label for="criterio">Criterio para obtenerlo *</label>
    <textarea id="criterio" name="criterio" required style="min-height:80px" placeholder="Ej: Completar la fase antes de la fecha límite y cumplir todos los criterios establecidos."><?= e($d['criterio']) ?></textarea>
    <div class="fila">
        <div><label for="valor">Premio / valor</label><input id="valor" name="valor" maxlength="100" value="<?= e($d['valor'] ?? '') ?>" placeholder="Ej: +0.5 en la nota final"></div>
        <div><label for="estado">Estado</label><select id="estado" name="estado">
            <?php foreach (['Activo', 'Inactivo', 'Finalizado'] as $s): ?><option <?= $d['estado'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="fila">
        <div><label for="id_curso">Curso *</label><select id="id_curso" name="id_curso" required>
            <option value="">Seleccione…</option>
            <?php foreach ($cursos as $c): ?><option value="<?= (int) $c['id_curso'] ?>" <?= (int) $d['id_curso'] === (int) $c['id_curso'] ? 'selected' : '' ?>>Curso <?= e($c['nombre_curso']) ?></option><?php endforeach; ?></select></div>
        <div><label for="id_fase">Fase asociada (opcional)</label><select id="id_fase" name="id_fase">
            <option value="">Cualquier fase</option>
            <?php foreach ($fases_op as $f): ?><option value="<?= (int) $f['id_fase'] ?>" data-curso="<?= (int) $f['id_curso'] ?>" <?= (int) $d['id_fase'] === (int) $f['id_fase'] ? 'selected' : '' ?>>Fase <?= (int) $f['orden'] ?> · <?= e($f['nombre_fase']) ?></option><?php endforeach; ?></select></div>
        <div><label for="id_tesis">Proyecto asociado (opcional)</label><select id="id_tesis" name="id_tesis">
            <option value="">Todos los proyectos</option>
            <?php foreach ($tesis_op as $p): ?><option value="<?= (int) $p['id_tesis'] ?>" data-curso="<?= (int) $p['id_curso'] ?>" <?= (int) $d['id_tesis'] === (int) $p['id_tesis'] ? 'selected' : '' ?>><?= e($p['titulo'] . ' · ' . $p['nombre_grupo']) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="fila">
        <div><label for="fecha_inicio">Vigente desde</label><input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= e($d['fecha_inicio'] ?? '') ?>"></div>
        <div><label for="fecha_fin">Hasta</label><input type="date" id="fecha_fin" name="fecha_fin" value="<?= e($d['fecha_fin'] ?? '') ?>"></div>
    </div>
    <div class="botones">
        <button class="btn btn-primario"><?= $inc ? 'Guardar cambios' : 'Crear incentivo' ?></button>
        <a class="btn btn-secundario" href="<?= $inc ? 'incentivo.php?id=' . $id : 'incentivos.php' ?>">Cancelar</a>
    </div>
</form>

<script>
(function () {   
    var curso = document.getElementById('id_curso');
    function filtrar() {
        ['id_fase', 'id_tesis'].forEach(function (id) {
            var s = document.getElementById(id);
            s.querySelectorAll('option[data-curso]').forEach(function (o) {
                o.hidden = o.dataset.curso !== curso.value;
                if (o.hidden && o.selected) s.value = '';
            });
        });
    }
    curso.addEventListener('change', filtrar); filtrar();
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
