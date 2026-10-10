<?php

require_once __DIR__ . '/includes/inicio.php';

$pdo  = conectar();
$id   = get_int('id');
$fase = null;

if ($id) {
    $fase = fase_para_docente($id_docente, $id);
    if (!$fase) {
        mensaje('error', 'La fase no existe o no pertenece a sus cursos.');
        redirigir('fases.php');
    }
    $id_curso = (int) $fase['id_curso'];
} else {
    $cursos = cursos_docente($id_docente);
    $id_curso = get_int('curso') ?: (int) ($_POST['id_curso'] ?? 0) ?: (count($cursos) === 1 ? (int) $cursos[0]['id_curso'] : 0);
    if (!$id_curso) {
        +
        $titulo = 'Nueva fase'; $seccion = 'fases'; $migas = [['Fases', 'fases.php'], ['Nueva fase', null]];
        require __DIR__ . '/includes/header.php'; ?>
        <div class="tarjeta" style="max-width:640px"><h2>¿Para qué curso es la fase?</h2>
            <div class="d-carpetas"><?php foreach ($cursos as $c): ?>
                <a class="d-carpeta" href="fase_form.php?curso=<?= (int) $c['id_curso'] ?>"><span class="d-carpeta-num">Curso</span><h3><?= e($c['nombre_curso']) ?></h3>
                <p><?= (int) $c['total_grupos'] ?> grupos · <?= (int) $c['total_fases'] ?> fases</p></a>
            <?php endforeach; ?></div>
            <?php if (!$cursos): ?><p class="vacio">No tiene cursos asignados.</p><?php endif; ?>
        </div>
        <?php require __DIR__ . '/includes/footer.php';
        exit;
    }
}

$curso = curso_para_docente($id_docente, $id_curso);
if (!$curso) {
    mensaje('error', 'Ese curso no está asignado a usted.');
    redirigir('fases.php');
}

$grupos_curso = grupos_de_curso($id_curso);
$incentivos_curso = incentivos(['id_curso' => $id_curso]);
$criterios = $fase ? criterios_de_fase($id) : [];
$volver_grupo = get_int('grupo');
$volver_tesis = get_int('tesis');


$d = $fase ?: [
    'nombre_fase' => '', 'descripcion' => '', 'objetivo' => '', 'instrucciones' => '', 'ejemplo' => '',
    'evidencias' => '', 'requisitos_siguiente' => '', 'fecha_inicio' => date('Y-m-d'), 'fecha_limite' => '',
    'duracion_dias' => '', 'peso' => '', 'estado' => 'Publicada', 'tipo_entrega' => 'cualquiera',
];
$grupos_sel = $fase ? ids_grupos_de_fase($id)
    : ($volver_grupo && in_array($volver_grupo, array_map('intval', array_column($grupos_curso, 'id_grupo')), true)
        ? [$volver_grupo] : array_map('intval', array_column($grupos_curso, 'id_grupo')));
$incentivos_sel = $fase ? array_map('intval', array_column(array_filter($incentivos_curso, fn($i) => (int) $i['id_fase'] === $id), 'id_incentivo_grupal')) : [];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf($_SERVER['REQUEST_URI']);

    $t = fn($k, $max = 20000) => mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $max);
    $d = [
        'nombre_fase'          => $t('nombre_fase', 150),
        'descripcion'          => $t('descripcion'),
        'objetivo'             => $t('objetivo'),
        'instrucciones'        => $t('instrucciones'),
        'ejemplo'              => $t('ejemplo'),
        'evidencias'           => $t('evidencias'),
        'requisitos_siguiente' => $t('requisitos_siguiente'),
        'fecha_inicio'         => $t('fecha_inicio', 10),
        'fecha_limite'         => $t('fecha_limite', 10),
        'duracion_dias'        => $t('duracion_dias', 5),
        'peso'                 => str_replace(',', '.', $t('peso', 8)),
        'estado'               => $_POST['estado'] ?? 'Publicada',
        'tipo_entrega'         => $_POST['tipo_entrega'] ?? 'cualquiera',
    ];
    $grupos_sel = array_map('intval', (array) ($_POST['grupos'] ?? []));
    $incentivos_sel = array_map('intval', (array) ($_POST['incentivos'] ?? []));

    if ($d['nombre_fase'] === '')   $errores[] = 'Escriba el nombre de la fase.';
    if ($d['descripcion'] === '')   $errores[] = 'Escriba una descripción breve de la fase.';
    if (!isset(ESTADOS_FASE[$d['estado']]))       $errores[] = 'Seleccione un estado válido.';
    if (!isset(TIPOS_ENTREGA[$d['tipo_entrega']])) $errores[] = 'Seleccione un tipo de entrega válido.';

    $fecha_ok = function (string $f): bool { $x = DateTime::createFromFormat('Y-m-d', $f); return $x && $x->format('Y-m-d') === $f; };
    foreach (['fecha_inicio' => 'inicio', 'fecha_limite' => 'límite'] as $k => $txt) {
        if ($d[$k] !== '' && !$fecha_ok($d[$k])) $errores[] = "La fecha de $txt no es válida.";
    }
    if ($d['duracion_dias'] !== '' && (!ctype_digit($d['duracion_dias']) || (int) $d['duracion_dias'] < 1 || (int) $d['duracion_dias'] > 730)) {
        $errores[] = 'La duración debe ser un número de días entre 1 y 730.';
    }
    
    if (!$errores && $d['fecha_inicio'] !== '') {
        if ($d['fecha_limite'] === '' && $d['duracion_dias'] !== '') {
            $d['fecha_limite'] = (new DateTime($d['fecha_inicio']))->modify('+' . (int) $d['duracion_dias'] . ' days')->format('Y-m-d');
        } elseif ($d['fecha_limite'] !== '' && $d['duracion_dias'] === '') {
            $d['duracion_dias'] = (string) max(1, (int) (new DateTime($d['fecha_inicio']))->diff(new DateTime($d['fecha_limite']))->format('%r%a'));
        }
    }
    if ($d['fecha_inicio'] !== '' && $d['fecha_limite'] !== '' && $d['fecha_limite'] < $d['fecha_inicio']) {
        $errores[] = 'La fecha límite no puede ser anterior a la fecha de inicio.';
    }
    if ($d['peso'] !== '' && (!is_numeric($d['peso']) || (float) $d['peso'] < 0 || (float) $d['peso'] > 100)) {
        $errores[] = 'El peso debe ser un porcentaje entre 0 y 100.';
    }
    if (!$grupos_sel) $errores[] = 'Asigne la fase al menos a un grupo.';

    
    $criterios_post = [];
    foreach ((array) ($_POST['criterio_nombre'] ?? []) as $i => $nombre) {
        $nombre = mb_substr(trim((string) $nombre), 0, 150);
        $peso_c = str_replace(',', '.', trim((string) ($_POST['criterio_peso'][$i] ?? '')));
        $desc_c = mb_substr(trim((string) ($_POST['criterio_desc'][$i] ?? '')), 0, 2000);
        $id_c   = (int) ($_POST['criterio_id'][$i] ?? 0);
        if ($nombre === '') continue;
        if ($peso_c !== '' && (!is_numeric($peso_c) || (float) $peso_c < 0 || (float) $peso_c > 100)) {
            $errores[] = "El peso del criterio «{$nombre}» debe estar entre 0 y 100.";
        }
        $criterios_post[] = ['id_criterio' => $id_c, 'nombre' => $nombre, 'descripcion' => $desc_c, 'peso' => $peso_c === '' ? null : (float) $peso_c];
    }
    $criterios = $criterios_post;

    // Archivo guía
    $guia_nueva = null;
    if (($_FILES['guia']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        [$guia_nueva, $err] = guardar_archivo($_FILES['guia'], 'guias', TIPOS_ENTREGA['cualquiera'][1]);
        if ($err) $errores[] = 'Archivo guía: ' . $err;
    }

    if (!$errores) {
        $nulo = fn($v) => $v === '' ? null : $v;
        try {
            $pdo->beginTransaction();
            $valores = [
                $d['nombre_fase'], $d['descripcion'], $nulo($d['objetivo']), $nulo($d['ejemplo']), $nulo($d['instrucciones']),
                $nulo($d['evidencias']), $nulo($d['requisitos_siguiente']), $d['tipo_entrega'],
                $nulo($d['duracion_dias']), $nulo($d['fecha_inicio']), $nulo($d['fecha_limite']), $nulo($d['peso']),
                $d['estado'] === 'Borrador' ? 0 : 1, $d['estado'],
            ];
            if ($fase) {
                $pdo->prepare('UPDATE fases SET nombre_fase = ?, descripcion = ?, objetivo = ?, ejemplo = ?, instrucciones = ?,
                                      evidencias = ?, requisitos_siguiente = ?, tipo_entrega = ?, duracion_dias = ?, fecha_inicio = ?,
                                      fecha_limite = ?, peso = ?, activa = ?, estado = ?, fecha_modificacion = NOW()
                               WHERE id_fase = ?')->execute([...$valores, $id]);
            } else {
                $stmt = $pdo->prepare('SELECT COALESCE(MAX(orden), 0) + 1 FROM fases WHERE id_curso = ?');
                $stmt->execute([$id_curso]);
                $orden = (int) $stmt->fetchColumn();
                $pdo->prepare('INSERT INTO fases (nombre_fase, descripcion, objetivo, ejemplo, instrucciones, evidencias, requisitos_siguiente,
                                                  tipo_entrega, duracion_dias, fecha_inicio, fecha_limite, peso, activa, estado,
                                                  id_curso, orden, id_profesor_creador)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute([...$valores, $id_curso, $orden, $id_docente]);
                $id = (int) $pdo->lastInsertId();
            }

            // Guía
            if ($guia_nueva) {
                $pdo->prepare('UPDATE fases SET archivo_guia = ?, archivo_guia_nombre = ? WHERE id_fase = ?')
                    ->execute([$guia_nueva['ruta'], $guia_nueva['nombre'], $id]);
            } elseif ($fase && !empty($_POST['quitar_guia'])) {
                $pdo->prepare('UPDATE fases SET archivo_guia = NULL, archivo_guia_nombre = NULL WHERE id_fase = ?')->execute([$id]);
            }

            
            $existentes = array_map('intval', array_column(criterios_de_fase($id), 'id_criterio'));
            $conservados = [];
            foreach ($criterios_post as $orden_c => $c) {
                if ($c['id_criterio'] && in_array($c['id_criterio'], $existentes, true)) {
                    $pdo->prepare('UPDATE criterios_fase SET nombre = ?, descripcion = ?, peso = ?, orden = ? WHERE id_criterio = ? AND id_fase = ?')
                        ->execute([$c['nombre'], $c['descripcion'] ?: null, $c['peso'], $orden_c + 1, $c['id_criterio'], $id]);
                    $conservados[] = $c['id_criterio'];
                } else {
                    $pdo->prepare('INSERT INTO criterios_fase (id_fase, nombre, descripcion, peso, orden) VALUES (?, ?, ?, ?, ?)')
                        ->execute([$id, $c['nombre'], $c['descripcion'] ?: null, $c['peso'], $orden_c + 1]);
                }
            }
            $no_borrados = 0;
            foreach (array_diff($existentes, $conservados) as $id_c) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM calificacion_criterio WHERE id_criterio = ?');
                $stmt->execute([$id_c]);
                if ((int) $stmt->fetchColumn() > 0) { $no_borrados++; continue; }
                $pdo->prepare('DELETE FROM criterios_fase WHERE id_criterio = ? AND id_fase = ?')->execute([$id_c, $id]);
            }

            
            $ids_inc_curso = array_map('intval', array_column($incentivos_curso, 'id_incentivo_grupal'));
            $incentivos_sel = array_values(array_intersect($incentivos_sel, $ids_inc_curso));
            $pdo->prepare('UPDATE incentivos_grupales SET id_fase = NULL WHERE id_fase = ? AND id_curso = ?')->execute([$id, $id_curso]);
            if ($incentivos_sel) {
                $pdo->prepare('UPDATE incentivos_grupales SET id_fase = ? WHERE id_curso = ? AND id_incentivo_grupal IN (' . marcas($incentivos_sel) . ')')
                    ->execute([$id, $id_curso, ...$incentivos_sel]);
            }

            
            $fase_guardada = fase_para_docente($id_docente, $id);
            $no_quitados = asignar_fase_a_grupos($fase_guardada, $grupos_sel);

            $pdo->commit();

            if ($guia_nueva && $fase && $fase['archivo_guia']) @unlink(CARPETA_UPLOADS . '/guias/' . basename($fase['archivo_guia']));
            if ($fase && !$guia_nueva && !empty($_POST['quitar_guia']) && $fase['archivo_guia']) @unlink(CARPETA_UPLOADS . '/guias/' . basename($fase['archivo_guia']));

            mensaje('exito', $fase ? 'Fase actualizada. Los estudiantes ya ven los cambios.' : 'Fase creada y asignada a ' . count($grupos_sel) . ' grupo(s).');
            if ($no_quitados) mensaje('error', 'No se quitó la fase de ' . implode(', ', $no_quitados) . ' porque ya tiene entregas allí.');
            if ($no_borrados) mensaje('error', "$no_borrados criterio(s) no se eliminaron porque ya tienen notas registradas.");
            redirigir('fase.php?id=' . $id);
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($guia_nueva) @unlink(CARPETA_UPLOADS . '/guias/' . $guia_nueva['ruta']);
            error_log('fase_form: ' . $ex->getMessage());
            $errores[] = 'No se pudo guardar la fase. ' . ($ex instanceof PDOException && $ex->getCode() === '45000' ? $ex->errorInfo[2] : 'Intente de nuevo.');
        }
    }
}


$filas_criterios = $criterios;
if (!$filas_criterios) {
    foreach (['Investigación', 'Organización', 'Cumplimiento', 'Calidad del trabajo'] as $n) $filas_criterios[] = ['id_criterio' => 0, 'nombre' => $fase ? '' : $n, 'descripcion' => '', 'peso' => null];
}
$filas_criterios[] = ['id_criterio' => 0, 'nombre' => '', 'descripcion' => '', 'peso' => null];

$titulo  = $fase ? 'Editar fase' : 'Nueva fase';
$subtitulo = 'Curso ' . $curso['nombre_curso'] . ($fase ? ' · Fase ' . $fase['orden'] . ' · ' . $fase['nombre_fase'] : '');
$seccion = 'fases';
$migas   = [['Fases', 'fases.php?curso=' . $id_curso], ['Curso ' . $curso['nombre_curso'], 'fases.php?curso=' . $id_curso]];
if ($fase) $migas[] = [$fase['nombre_fase'], 'fase.php?id=' . $id];
$migas[] = [$fase ? 'Editar' : 'Nueva fase', null];
require __DIR__ . '/includes/header.php';
$accion = $fase ? 'fase_form.php?id=' . $id : 'fase_form.php?curso=' . $id_curso . ($volver_grupo ? '&grupo=' . $volver_grupo : '') . ($volver_tesis ? '&tesis=' . $volver_tesis : '');
?>

<?php if ($errores): ?>
    <div class="alerta alerta-error"><strong>Revise el formulario:</strong><ul><?php foreach ($errores as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" action="<?= e($accion) ?>" class="formulario" enctype="multipart/form-data" style="max-width:980px">
    <?= campo_csrf() ?>
    <input type="hidden" name="id_curso" value="<?= $id_curso ?>">
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= TAMANO_MAXIMO ?>">

    <fieldset class="d-form-seccion">
        <legend>1. Información de la fase</legend>
        <label for="nombre_fase">Nombre de la fase *</label>
        <input id="nombre_fase" name="nombre_fase" required maxlength="150" value="<?= e($d['nombre_fase']) ?>" placeholder="Ej: Planteamiento del proyecto">
        <label for="descripcion">Descripción *</label>
        <textarea id="descripcion" name="descripcion" required style="min-height:80px" placeholder="Explicación breve de lo que se trabaja en esta fase"><?= e($d['descripcion']) ?></textarea>
        <label for="objetivo">Objetivo</label>
        <textarea id="objetivo" name="objetivo" style="min-height:70px" placeholder="¿Qué deben lograr los estudiantes al terminar la fase?"><?= e($d['objetivo'] ?? '') ?></textarea>
        <label for="instrucciones">Instrucciones para los estudiantes</label>
        <textarea id="instrucciones" name="instrucciones" placeholder="Paso a paso de lo que deben hacer"><?= e($d['instrucciones'] ?? '') ?></textarea>
        <label for="ejemplo">Ejemplo </label>
        <textarea id="ejemplo" name="ejemplo" style="min-height:60px"><?= e($d['ejemplo'] ?? '') ?></textarea>
    </fieldset>

    <fieldset class="d-form-seccion">
        <legend>2. Tiempos y estado</legend>
        <div class="fila">
            <div><label for="fecha_inicio">Fecha de inicio</label><input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= e($d['fecha_inicio'] ?? '') ?>"></div>
            <div><label for="duracion_dias">Duración (días)</label><input type="number" min="1" max="730" id="duracion_dias" name="duracion_dias" value="<?= e($d['duracion_dias'] ?? '') ?>"></div>
            <div><label for="fecha_limite">Fecha límite</label><input type="date" id="fecha_limite" name="fecha_limite" value="<?= e($d['fecha_limite'] ?? '') ?>"></div>
        </div>

        <div class="fila">
            <div><label for="estado">Estado</label>
                <select id="estado" name="estado"><?php foreach (ESTADOS_FASE as $k => $txt): ?>
                    <option value="<?= e($k) ?>" <?= $d['estado'] === $k ? 'selected' : '' ?>><?= e($txt) ?></option><?php endforeach; ?></select></div>
            <div><label for="peso">Peso dentro del proyecto (%)</label><input type="number" step="0.01" min="0" max="100" id="peso" name="peso" value="<?= e($d['peso'] !== null && $d['peso'] !== '' ? rtrim(rtrim((string) $d['peso'], '0'), '.') : '') ?>" placeholder="Ej: 20"></div>
        </div>
    </fieldset>

    <fieldset class="d-form-seccion">
        <legend>3. Trabajos y requisitos</legend>
        <label for="evidencias">Evidencias o trabajos requeridos</label>
        <textarea id="evidencias" name="evidencias" style="min-height:80px" placeholder="Una por línea. Ej:&#10;Documento con el planteamiento del problema&#10;Árbol de problemas"><?= e($d['evidencias'] ?? '') ?></textarea>
        <div class="fila">
            <div><label for="tipo_entrega">Formato de entrega</label>
                <select id="tipo_entrega" name="tipo_entrega"><?php foreach (TIPOS_ENTREGA as $k => [$txt, $ext]): ?>
                    <option value="<?= e($k) ?>" <?= $d['tipo_entrega'] === $k ? 'selected' : '' ?>><?= e($txt) ?></option><?php endforeach; ?></select></div>
            <div><label for="guia">Archivo guía (opcional, máx. 10 MB)</label><input type="file" id="guia" name="guia">
                <?php if ($fase && $fase['archivo_guia']): ?>
                    <p class="ayuda">Actual: <a href="archivo.php?guia=<?= $id ?>" target="_blank"><?= e($fase['archivo_guia_nombre']) ?></a>
                        <label class="casilla" style="margin:4px 0 0"><input type="checkbox" name="quitar_guia" value="1"> Quitar archivo</label></p>
                <?php endif; ?></div>
        </div>
        <label for="requisitos_siguiente">Requisitos para pasar a la siguiente fase</label>
        <textarea id="requisitos_siguiente" name="requisitos_siguiente" style="min-height:70px" ><?= e($d['requisitos_siguiente'] ?? '') ?></textarea>
    </fieldset>

    <fieldset class="d-form-seccion">
        <legend>4. Criterios de evaluación</legend>
        <p class="ayuda">Defina los criterios con los que calificará la fase. Si todos tienen peso, la nota final se calcula ponderada; si no, se promedia. Deje el nombre vacío para quitar un criterio.</p>
        <div class="d-criterio-fila" style="font-size:12px;color:var(--texto-suave);font-weight:700;text-transform:uppercase"><span>Criterio</span><span>Descripción</span><span>Peso %</span><span></span></div>
        <div id="criterios">
            <?php foreach ($filas_criterios as $c): ?>
                <div class="d-criterio-fila">
                    <input type="hidden" name="criterio_id[]" value="<?= (int) $c['id_criterio'] ?>">
                    <input name="criterio_nombre[]" maxlength="150" value="<?= e($c['nombre']) ?>" placeholder="Ej: Presentación">
                    <input name="criterio_desc[]" maxlength="2000" value="<?= e($c['descripcion']) ?>" placeholder="Qué se evalúa">
                    <input name="criterio_peso[]" type="number" step="0.01" min="0" max="100" value="<?= $c['peso'] !== null ? e(rtrim(rtrim((string) $c['peso'], '0'), '.')) : '' ?>">
                    <button type="button" class="btn-quitar" title="Quitar" onclick="var f=this.parentNode; f.querySelector('[name=\'criterio_nombre[]\']').value=''; f.hidden=true;">×</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-secundario btn-chico" id="agregar-criterio"><?= icono('mas', 14) ?> Agregar criterio</button>
    </fieldset>

    <fieldset class="d-form-seccion">
        <legend>5. Grupos e incentivos</legend>
        <label>Grupos que tendrán esta fase *</label>
        <div class="d-checks">
            <?php foreach ($grupos_curso as $g): ?>
                <label><input type="checkbox" name="grupos[]" value="<?= (int) $g['id_grupo'] ?>" <?= in_array((int) $g['id_grupo'], $grupos_sel, true) ? 'checked' : '' ?>>
                    <?= e($g['nombre_grupo']) ?> <small class="d-tenue">(<?= (int) $g['total_estudiantes'] ?>)</small></label>
            <?php endforeach; ?>
            <?php if (!$grupos_curso): ?><p class="ayuda">El curso aún no tiene grupos. Pídale al administrador que los cree.</p><?php endif; ?>
        </div>
        <label>Incentivos relacionados</label>
        <?php if ($incentivos_curso): ?>
            <div class="d-checks">
                <?php foreach ($incentivos_curso as $i): ?>
                    <label><input type="checkbox" name="incentivos[]" value="<?= (int) $i['id_incentivo_grupal'] ?>" <?= in_array((int) $i['id_incentivo_grupal'], $incentivos_sel, true) ? 'checked' : '' ?>>
                        <?= e(($i['icono'] ?: '🏆') . ' ' . $i['nombre']) ?></label>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="ayuda">No hay incentivos en este curso. <a href="incentivo_form.php?curso=<?= $id_curso ?>">Crear un incentivo</a>.</p>
        <?php endif; ?>
    </fieldset>

    <div class="botones">
        <button type="submit" class="btn btn-primario"><?= $fase ? 'Guardar cambios' : 'Crear fase' ?></button>
        <a href="<?= $fase ? 'fase.php?id=' . $id : ($volver_tesis ? 'proyecto.php?id=' . $volver_tesis . '&tab=fases' : 'fases.php?curso=' . $id_curso) ?>" class="btn btn-secundario">Cancelar</a>
    </div>
</form>

<template id="plantilla-criterio">
    <div class="d-criterio-fila">
        <input type="hidden" name="criterio_id[]" value="0">
        <input name="criterio_nombre[]" maxlength="150" placeholder="Nombre del criterio">
        <input name="criterio_desc[]" maxlength="2000" placeholder="Qué se evalúa">
        <input name="criterio_peso[]" type="number" step="0.01" min="0" max="100">
        <button type="button" class="btn-quitar" title="Quitar" onclick="this.parentNode.remove()">×</button>
    </div>
</template>
<script>
document.getElementById('agregar-criterio').addEventListener('click', function () {
    var t = document.getElementById('plantilla-criterio').content.cloneNode(true);
    document.getElementById('criterios').appendChild(t);
});
(function () {   
    var ini = document.getElementById('fecha_inicio'), dur = document.getElementById('duracion_dias'), lim = document.getElementById('fecha_limite');
    function sumar() {
        if (!ini.value || !dur.value) return;
        var d = new Date(ini.value + 'T00:00:00'); d.setDate(d.getDate() + parseInt(dur.value, 10));
        lim.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    function dias() {
        if (!ini.value || !lim.value) return;
        var n = Math.round((new Date(lim.value) - new Date(ini.value)) / 86400000);
        if (n > 0) dur.value = n;
    }
    ini.addEventListener('change', sumar); dur.addEventListener('input', sumar); lim.addEventListener('change', dias);
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
