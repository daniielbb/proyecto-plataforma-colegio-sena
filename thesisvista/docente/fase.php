<?php

require_once __DIR__ . '/includes/inicio.php';

$id   = get_int('id');
$fase = fase_para_docente($id_docente, $id);
if (!$fase) {
    mensaje('error', 'La fase no existe o no pertenece a sus cursos.');
    redirigir('fases.php');
}
$criterios  = criterios_de_fase($id);
$grupos     = avance_grupos_en_fase($id);
$incentivos = incentivos(['id_fase' => $id]);
$motivo_no_eliminar = motivo_no_eliminar_fase($id_docente, $fase);
$evidencias = array_filter(array_map('trim', explode("\n", (string) $fase['evidencias'])));

$titulo  = 'Fase ' . $fase['orden'] . ' · ' . $fase['nombre_fase'];
$subtitulo = 'Curso ' . $fase['nombre_curso'] . ' · creada por ' . $fase['creador_nombre'] . ' ' . $fase['creador_apellido'];
$seccion = 'fases';
$migas   = [['Fases', 'fases.php?curso=' . (int) $fase['id_curso']], ['Curso ' . $fase['nombre_curso'], 'fases.php?curso=' . (int) $fase['id_curso']], ['Fase ' . $fase['orden'], null]];
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<div class="d-barra-acciones" style="margin-bottom:18px">
    <?= etiqueta($fase['estado']) ?>
    <a class="btn btn-primario btn-chico" href="fase_form.php?id=<?= $id ?>"><?= icono('editar', 15) ?> Editar fase</a>
    <button type="button" class="btn btn-secundario btn-chico" data-abrir="modal-estado">Cambiar estado</button>
    <?php if (!$motivo_no_eliminar): ?>
        <form method="post" action="fase_accion.php" data-confirmar="¿Eliminar definitivamente esta fase? Esta acción no se puede deshacer." style="display:inline">
            <?= campo_csrf() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="accion" value="eliminar">
            <button class="btn btn-peligro btn-chico">Eliminar</button></form>
    <?php else: ?>
        <small class="d-tenue" title="<?= e($motivo_no_eliminar) ?>">No se puede eliminar: <?= e($motivo_no_eliminar) ?></small>
    <?php endif; ?>
</div>

<dialog class="d-modal" id="modal-estado">
    <form method="post" action="fase_accion.php" class="formulario">
        <?= campo_csrf() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="accion" value="estado">
        <h3>Estado de la fase</h3>
        <?php foreach (ESTADOS_FASE as $k => $txt): ?>
            <label class="casilla d-opcion"><input type="radio" name="estado" value="<?= e($k) ?>" <?= $fase['estado'] === $k ? 'checked' : '' ?>> <?= e($txt) ?></label>
        <?php endforeach; ?>
        <div class="botones"><button class="btn btn-primario">Guardar</button><button type="button" class="btn btn-secundario" data-cerrar>Cancelar</button></div>
    </form>
</dialog>

<div class="d-columnas">
    <div>
        <section class="tarjeta">
            <dl class="d-ficha" style="margin-bottom:20px">
                <div><dt>Inicio</dt><dd><?= e(fecha_corta($fase['fecha_inicio'])) ?></dd></div>
                <div><dt>Fecha límite</dt><dd><?= limite($fase['fecha_limite']) ?></dd></div>
                <div><dt>Duración</dt><dd><?= $fase['duracion_dias'] ? (int) $fase['duracion_dias'] . ' días' : '—' ?></dd></div>
                <div><dt>Peso en el proyecto</dt><dd><?= $fase['peso'] !== null ? e(rtrim(rtrim($fase['peso'], '0'), '.')) . '%' : 'Sin peso' ?></dd></div>
                <div><dt>Formato de entrega</dt><dd><?= e(TIPOS_ENTREGA[$fase['tipo_entrega']][0] ?? $fase['tipo_entrega']) ?></dd></div>
                <div><dt>Archivo guía</dt><dd><?= $fase['archivo_guia'] ? '<a href="archivo.php?guia=' . $id . '" target="_blank">' . e($fase['archivo_guia_nombre']) . '</a>' : '—' ?></dd></div>
            </dl>
            <div class="d-bloque"><h3>Descripción</h3><p class="d-texto"><?= e($fase['descripcion']) ?></p></div>
            <?php if ($fase['objetivo']): ?><div class="d-bloque"><h3>Objetivo</h3><p class="d-texto"><?= e($fase['objetivo']) ?></p></div><?php endif; ?>
            <?php if ($fase['instrucciones']): ?><div class="d-bloque"><h3>Instrucciones</h3><p class="d-texto"><?= e($fase['instrucciones']) ?></p></div><?php endif; ?>
            <?php if ($evidencias): ?><div class="d-bloque"><h3>Evidencias requeridas</h3><ul class="d-lista-check"><?php foreach ($evidencias as $ev): ?><li><?= e($ev) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <?php if ($fase['ejemplo']): ?><div class="d-bloque"><h3>Ejemplo</h3><p class="d-texto"><?= e($fase['ejemplo']) ?></p></div><?php endif; ?>
            <?php if ($fase['requisitos_siguiente']): ?><div class="d-bloque"><h3>Requisitos para pasar a la siguiente fase</h3><p class="d-texto"><?= e($fase['requisitos_siguiente']) ?></p></div><?php endif; ?>
        </section>

        <section class="tarjeta">
            <div class="tarjeta-cabecera"><h2>Avance de los grupos</h2><small class="d-tenue"><?= count($grupos) ?> grupo(s) asignado(s)</small></div>
            <?php if (!$grupos): ?>
                <p class="vacio">La fase no está asignada a ningún grupo. <a href="fase_form.php?id=<?= $id ?>">Asignar grupos</a></p>
            <?php else: ?>
                <div class="tabla-contenedor"><table>
                    <thead><tr><th>Grupo</th><th>Estado</th><th style="width:28%">Avance</th><th>Entregas</th><th>Nota</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($grupos as $g): ?>
                        <tr>
                            <td><strong><?= e($g['nombre_grupo']) ?></strong><br><small><?= (int) $g['total_estudiantes'] ?> estudiante(s)</small></td>
                            <td><?= etiqueta($g['estado']) ?><?php if ($g['fecha_actualizacion']): ?><br><small><?= e(hace($g['fecha_actualizacion'])) ?></small><?php endif; ?></td>
                            <td><?= barra(porcentaje_fase($g)) ?></td>
                            <td><?= (int) $g['total_entregas'] ?><?= $g['ultima_entrega'] ? '<br><small>' . e(hace($g['ultima_entrega'])) . '</small>' : '' ?></td>
                            <td><?= $g['nota_grupo'] !== null ? '<span class="d-nota ' . ((float) $g['nota_grupo'] >= NOTA_APROBATORIA ? 'aprobada' : 'baja') . '">' . formato_nota($g['nota_grupo']) . '</span>' : '—' ?></td>
                            <td><a class="btn btn-primario btn-chico" href="grupo_fase.php?grupo=<?= (int) $g['id_grupo'] ?>&fase=<?= $id ?>">Revisar</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </section>
    </div>

    <div>
        <section class="tarjeta">
            <h2>Criterios de evaluación</h2>
            <?php if (!$criterios): ?>
                <p class="vacio">Sin criterios. La nota se asignará directamente. <a href="fase_form.php?id=<?= $id ?>">Agregar criterios</a></p>
            <?php else: ?>
                <ul class="lista-simple">
                    <?php foreach ($criterios as $c): ?>
                        <li><strong><?= e($c['nombre']) ?></strong><?= $c['peso'] !== null ? ' <span class="etiqueta etiqueta-cafe">' . e(rtrim(rtrim($c['peso'], '0'), '.')) . '%</span>' : '' ?>
                            <?= $c['descripcion'] ? '<br><small class="d-tenue">' . e($c['descripcion']) . '</small>' : '' ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="tarjeta">
            <div class="tarjeta-cabecera"><h2>Incentivos de la fase</h2>
                <a class="btn btn-secundario btn-chico" href="incentivo_form.php?curso=<?= (int) $fase['id_curso'] ?>&fase=<?= $id ?>"><?= icono('mas', 14) ?> Crear</a></div>
            <?php if (!$incentivos): ?>
                <p class="vacio">No hay incentivos asociados a esta fase.</p>
            <?php else: ?>
                <ul class="d-otorgados">
                    <?php foreach ($incentivos as $i): ?>
                        <li><span class="d-medalla"><?= e($i['icono'] ?: '🏆') ?></span><div>
                            <a href="incentivo.php?id=<?= (int) $i['id_incentivo_grupal'] ?>"><strong><?= e($i['nombre']) ?></strong></a> <?= etiqueta($i['estado']) ?><br>
                            <small><?= e($i['criterio']) ?></small><br><small class="d-tenue">Otorgado <?= (int) $i['total_otorgados'] ?> vez/veces</small></div></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
