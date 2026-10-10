<?php

require_once __DIR__ . '/includes/inicio.php';
exigir_grupo($grupo);

$id_fase = get_int('id') ?: (int) ($_POST['id'] ?? 0);
$fases   = fases_estudiante($id_grupo, $id_estudiante);
$fase    = buscar_fase($fases, $id_fase);
if (!$fase) {   
    mensaje('error', 'La fase no existe o no está asignada a tu grupo.');
    redirigir('fases.php');
}
$url = 'fase.php?id=' . $id_fase;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        mensaje('error', 'El archivo es demasiado grande para el servidor (máximo ' . tamano_legible(TAMANO_MAXIMO) . ').');
        redirigir($url);
    }
    exigir_csrf($url);
    if (($_POST['accion'] ?? '') !== 'entregar') redirigir($url);

    if (!$fase['puede_entregar']) {           // bloqueada, cerrada, en revisión o aprobada
        mensaje('error', 'No puedes entregar en esta fase: ' . $fase['motivo_no_entrega']);
        redirigir($url);
    }
    $comentario = mb_substr(trim((string) ($_POST['comentario'] ?? '')), 0, 2000) ?: null;
    try {
        [$id_entrega, $error] = registrar_entrega($id_estudiante, $id_grupo, $id_fase, $_FILES['archivo'] ?? [], $comentario);
        if ($error) {
            mensaje('error', $error);
        } else {
            mensaje('exito', 'Tu entrega se subió correctamente. El docente ya puede verla.');
        }
    } catch (PDOException $ex) {
        error_log('estudiante/fase entrega: ' . $ex->getMessage());
        mensaje('error', ($ex->errorInfo[0] ?? '') === '45000' ? $ex->errorInfo[2] : 'No se pudo registrar la entrega. Intente de nuevo.');
    }
    redirigir($url . '#entregas');
}

$entregas    = $fase['bloqueada'] ? [] : entregas_estudiante($id_grupo, null, $id_fase);
$mias        = array_values(array_filter($entregas, fn($en) => (int) $en['id_estudiante'] === $id_estudiante));
$companeros  = array_values(array_filter($entregas, fn($en) => (int) $en['id_estudiante'] !== $id_estudiante));
$comentarios = $fase['bloqueada'] ? [] : comentarios_estudiante($id_grupo, $id_estudiante, $id_fase);
$incentivos  = array_values(array_filter(incentivos_estudiante($grupo, $id_estudiante), fn($i) => (int) $i['id_fase'] === $id_fase));
$notas       = $fase['bloqueada'] ? [] : array_values(array_filter(calificaciones_estudiante($id_grupo, $id_estudiante), fn($n) => (int) $n['id_fase'] === $id_fase));
$evidencias  = array_filter(array_map('trim', explode("\n", (string) $fase['evidencias'])));
$extensiones = TIPOS_ENTREGA[$fase['tipo_entrega']][1] ?? TIPOS_ENTREGA['cualquiera'][1];

$titulo    = 'Fase ' . $fase['orden'] . ' · ' . $fase['nombre_fase'];
$subtitulo = $grupo['nombre_grupo'] . ' · Curso ' . $grupo['nombre_curso'];
$seccion   = 'fases';
$migas     = [['Progreso del proyecto', 'fases.php'], ['Fase ' . $fase['orden'], null]];
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<section class="tarjeta" style="padding:18px 22px">
    <dl class="d-ficha">
        <div><dt>Estado</dt><dd><?= etiqueta_fase($fase) ?></dd></div>
        <?php if (!$fase['bloqueada']): ?><div><dt>Avance</dt><dd><?= barra($fase['porcentaje']) ?></dd></div><?php endif; ?>
        <div><dt>Fecha de inicio</dt><dd><?= $fase['fecha_inicio'] ? e(fecha_corta($fase['fecha_inicio'])) : '—' ?></dd></div>
        <div><dt>Fecha límite</dt><dd><?= limite($fase['fecha_limite'], fase_terminada($fase['estado'])) ?></dd></div>
        <?php if (!$fase['bloqueada']): ?><div><dt>Formato de entrega</dt><dd><?= e(TIPOS_ENTREGA[$fase['tipo_entrega']][0] ?? 'Cualquier formato') ?></dd></div><?php endif; ?>
    </dl>
</section>

<?php if ($fase['bloqueada']): ?>
    <section class="tarjeta e-bloqueo">
        <span class="e-bloqueo-ico"><?= icono('candado', 34) ?></span>
        <div>
            <h2>Esta fase está bloqueada</h2>
            <p><?= e($fase['descripcion']) ?></p>
            <p class="e-condicion"><strong>Condición para desbloquearla:</strong> <?= e($fase['motivo_bloqueo']) ?></p>
            <?php if (!empty($fase['fase_requerida']['requisitos_siguiente'])): ?>
                <div class="d-bloque"><h3>Requisitos indicados por el docente</h3>
                    <p class="d-texto"><?= e($fase['fase_requerida']['requisitos_siguiente']) ?></p></div>
            <?php endif; ?>
            <p class="d-tenue"><small>Cuando el docente la habilite, esta página se actualizará sola y podrás ver las instrucciones y subir tu entrega.</small></p>
            <?php if (!empty($fase['fase_requerida'])): ?>
                <a class="btn btn-secundario" href="fase.php?id=<?= (int) $fase['fase_requerida']['id_fase'] ?>">Ir a la Fase <?= (int) $fase['fase_requerida']['orden'] ?></a>
            <?php endif; ?>
        </div>
    </section>
    <?php if ($incentivos): ?>
        <section class="tarjeta"><h2>Incentivos de esta fase</h2><div class="d-incentivos"><?php foreach ($incentivos as $i) echo tarjeta_incentivo($i); ?></div></section>
    <?php endif; ?>
    <?php require __DIR__ . '/includes/footer.php'; exit; ?>
<?php endif; ?>

<div class="d-columnas">
    <div>
        <section class="tarjeta">
            <h2>¿Qué hay que hacer?</h2>
            <div class="d-bloque"><h3>Descripción</h3><p class="d-texto"><?= e($fase['descripcion']) ?></p></div>
            <?php if ($fase['objetivo']): ?><div class="d-bloque"><h3>Objetivo</h3><p class="d-texto"><?= e($fase['objetivo']) ?></p></div><?php endif; ?>
            <?php if ($fase['instrucciones']): ?><div class="d-bloque"><h3>Instrucciones</h3><p class="d-texto"><?= e($fase['instrucciones']) ?></p></div><?php endif; ?>
            <?php if ($evidencias): ?><div class="d-bloque"><h3>Evidencias requeridas</h3><ul class="d-lista-check"><?php foreach ($evidencias as $ev): ?><li><?= e($ev) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <?php if ($fase['ejemplo']): ?><div class="d-bloque"><h3>Ejemplo</h3><p class="d-texto"><?= e($fase['ejemplo']) ?></p></div><?php endif; ?>
            <?php if ($fase['requisitos_siguiente']): ?><div class="d-bloque"><h3>Para pasar a la siguiente fase</h3><p class="d-texto"><?= e($fase['requisitos_siguiente']) ?></p></div><?php endif; ?>
            <?php if ($fase['archivo_guia']): ?>
                <div class="d-bloque"><h3>Archivo guía del docente</h3>
                    <a class="btn btn-secundario btn-chico" target="_blank" rel="noopener" href="archivo.php?guia=<?= $id_fase ?>"><?= icono('ojo', 14) ?> Ver</a>
                    <a class="btn btn-secundario btn-chico" href="archivo.php?guia=<?= $id_fase ?>&amp;descargar=1"><?= icono('descargar', 14) ?> <?= e($fase['archivo_guia_nombre']) ?></a></div>
            <?php endif; ?>
        </section>

        <section class="tarjeta" id="comentarios">
            <h2 class="d-seccion-titulo"><?= icono('comentario') ?> Comentarios del docente</h2>
            <?= lista_comentarios($comentarios, false) ?>
        </section>

        <section class="tarjeta" id="entregas">
            <h2 class="d-seccion-titulo"><?= icono('archivo') ?> Mis archivos en esta fase</h2>
            <?= $mias ? lista_entregas($mias, $id_estudiante) : '<p class="vacio">Aún no has subido archivos en esta fase.</p>' ?>
            <?php if ($companeros): ?>
                <h3 style="font-size:15px;margin:20px 0 8px">Entregas de mis compañeros de grupo</h3>
                <?= lista_entregas($companeros, $id_estudiante) ?>
            <?php endif; ?>
        </section>
    </div>

    <div>
        <section class="tarjeta" id="subir">
            <h2 class="d-seccion-titulo"><?= icono('subir') ?> Subir entrega</h2>
            <?php if ($fase['puede_entregar']): ?>
                <?php if ($fase['estado'] === 'Requiere corrección'): ?>
                    <div class="alerta alerta-aviso">El docente pidió correcciones. Revisa sus comentarios y sube la nueva versión.</div>
                <?php elseif ($mias): ?>
                    <p class="d-tenue"><small>Ya tienes <?= count($mias) ?> versión(es). Si subes otro archivo, reemplaza tu entrega actual
                        (las versiones anteriores quedan en el historial). Puedes hacerlo hasta que el docente la revise o cierre la fase.</small></p>
                <?php endif; ?>
                <form method="post" action="<?= e($url) ?>" enctype="multipart/form-data" class="formulario"
                      data-confirmar="¿Enviar este archivo como entrega de la Fase <?= (int) $fase['orden'] ?>?">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="accion" value="entregar"><input type="hidden" name="id" value="<?= $id_fase ?>">
                    <input type="hidden" name="MAX_FILE_SIZE" value="<?= TAMANO_MAXIMO ?>">
                    <label for="archivo">Adjuntar archivo</label>
                    <input type="file" id="archivo" name="archivo" required accept="<?= e(implode(',', array_map(fn($x) => '.' . $x, $extensiones))) ?>">
                    <p class="ayuda">Formatos: <?= e(strtoupper(implode(', ', $extensiones))) ?> · máximo <?= e(tamano_legible(TAMANO_MAXIMO)) ?>.</p>
                    <label for="comentario">Mensaje para el docente (opcional)</label>
                    <textarea id="comentario" name="comentario" maxlength="2000" style="min-height:80px" placeholder="Ej.: Adjunto la justificación con los cambios del segundo punto."></textarea>
                    <button class="btn btn-primario btn-bloque"><?= icono('subir', 16) ?> <?= $mias ? 'Subir nueva versión' : 'Subir entrega' ?></button>
                </form>
            <?php else: ?>
                <p class="e-condicion"><?= icono('candado', 14) ?> <?= e($fase['motivo_no_entrega']) ?></p>
            <?php endif; ?>
        </section>

        <?php if ($notas): ?>
            <section class="tarjeta">
                <h2 class="d-seccion-titulo"><?= icono('nota') ?> Calificación</h2>
                <ul class="lista-simple">
                    <?php foreach ($notas as $n): ?>
                        <li><span class="d-nota <?= (float) $n['nota'] >= NOTA_APROBATORIA ? 'aprobada' : 'baja' ?>"><?= formato_nota($n['nota']) ?></span>
                            <strong><?= $n['id_estudiante'] ? 'Tu nota' : 'Nota del grupo' ?></strong>
                            <?= $n['version'] ? '<small>· ' . e($n['nombre_original']) . ' v' . (int) $n['version'] . '</small>' : '' ?><br>
                            <small><?= e($n['nombre'] . ' ' . $n['apellido']) ?> · <?= e(fecha_hora($n['fecha_modificacion'] ?? $n['fecha'])) ?></small>
                            <?= $n['observacion'] ? '<br><small>' . nl2br(e($n['observacion'])) . '</small>' : '' ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <section class="tarjeta">
            <h2 class="d-seccion-titulo"><?= icono('incentivo') ?> Incentivos de esta fase</h2>
            <?php if ($incentivos): ?>
                <div class="d-incentivos e-una-columna"><?php foreach ($incentivos as $i) echo tarjeta_incentivo($i); ?></div>
            <?php else: ?>
                <p class="d-tenue" style="margin:0">El docente no asoció incentivos a esta fase.</p>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
