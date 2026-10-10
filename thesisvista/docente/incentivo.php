<?php

require_once __DIR__ . '/includes/inicio.php';

$id  = get_int('id');
$inc = incentivo_para_docente($id_docente, $id);
if (!$inc) {
    mensaje('error', 'El incentivo no existe o no pertenece a sus cursos.');
    redirigir('incentivos.php');
}
$grupos    = grupos_de_curso((int) $inc['id_curso']);
$otorgados = incentivos_otorgados(['id_incentivo_grupal' => $id]);
$grupo_pre = get_int('grupo');
$integrantes_por_grupo = [];
foreach ($grupos as $g) $integrantes_por_grupo[(int) $g['id_grupo']] = integrantes_grupo((int) $g['id_grupo']);
$propio = (int) $inc['id_profesor'] === $id_docente;

$titulo  = ($inc['icono'] ?: '🏆') . ' ' . $inc['nombre'];
$subtitulo = 'Curso ' . $inc['nombre_curso'] . ' · creado por ' . $inc['creador_nombre'] . ' ' . $inc['creador_apellido'];
$seccion = 'incentivos';
$migas = [['Incentivos', 'incentivos.php'], [$inc['nombre'], null]];
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<div class="d-columnas">
    <div>
        <section class="tarjeta">
            <div class="tarjeta-cabecera">
                <div style="display:flex;gap:14px;align-items:center"><span class="d-medalla" style="width:64px;height:64px;font-size:36px"><?= e($inc['icono'] ?: '🏆') ?></span>
                    <div><h2 style="margin:0"><?= e($inc['nombre']) ?></h2><?= etiqueta($inc['estado']) ?> <?= $inc['valor'] ? '<span class="etiqueta etiqueta-cafe">' . e($inc['valor']) . '</span>' : '' ?></div></div>
                <?php if ($propio): ?><a class="btn btn-secundario btn-chico" href="incentivo_form.php?id=<?= $id ?>"><?= icono('editar', 14) ?> Editar</a><?php endif; ?>
            </div>
            <?php if ($inc['descripcion']): ?><div class="d-bloque"><h3>Descripción</h3><p class="d-texto"><?= e($inc['descripcion']) ?></p></div><?php endif; ?>
            <div class="d-bloque"><h3>Criterio para obtenerlo</h3><p class="d-texto"><?= e($inc['criterio']) ?></p></div>
            <dl class="d-ficha">
                <div><dt>Fase</dt><dd><?= $inc['nombre_fase'] ? '<a href="fase.php?id=' . (int) $inc['id_fase'] . '">' . e($inc['nombre_fase']) . '</a>' : 'Cualquier fase' ?></dd></div>
                <div><dt>Proyecto</dt><dd><?= $inc['titulo_tesis'] ? '<a href="proyecto.php?id=' . (int) $inc['id_tesis'] . '">' . e($inc['titulo_tesis']) . '</a>' : 'Todos' ?></dd></div>
                <div><dt>Vigencia</dt><dd><?= e(fecha_corta($inc['fecha_inicio'])) ?> → <?= e(fecha_corta($inc['fecha_fin'])) ?></dd></div>
            </dl>
        </section>

        <section class="tarjeta">
            <h2>Obtenido por (<?= count($otorgados) ?>)</h2>
            <?php if (!$otorgados): ?>
                <p class="vacio">Todavía nadie ha obtenido este incentivo.</p>
            <?php else: ?>
                <div class="tabla-contenedor"><table>
                    <thead><tr><th>Grupo</th><th>Estudiante</th><th>Observación</th><th>Fecha</th><th></th></tr></thead>
                    <tbody><?php foreach ($otorgados as $o): ?>
                        <tr><td><a href="grupo.php?id=<?= (int) $o['id_grupo'] ?>"><strong><?= e($o['nombre_grupo']) ?></strong></a></td>
                            <td><?= $o['est_nombre'] ? e($o['est_nombre'] . ' ' . $o['est_apellido']) : '<em>Todo el grupo</em>' ?></td>
                            <td><small><?= nl2br(e($o['observacion'] ?? '')) ?></small></td>
                            <td><small><?= e(fecha_hora($o['fecha'])) ?><br>por <?= e($o['nombre'] . ' ' . $o['apellido']) ?></small></td>
                            <td><?php if ((int) $o['id_profesor'] === $id_docente): ?>
                                <form method="post" action="incentivo_accion.php" data-confirmar="¿Quitar este incentivo?">
                                    <?= campo_csrf() ?><input type="hidden" name="accion" value="revocar"><input type="hidden" name="id" value="<?= $id ?>">
                                    <input type="hidden" name="id_otorgado" value="<?= (int) $o['id_otorgado'] ?>">
                                    <button class="btn btn-secundario btn-chico">Quitar</button></form><?php endif; ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            <?php endif; ?>
        </section>
    </div>

    <section class="tarjeta">
        <h2>Otorgar incentivo</h2>
        <?php if ($inc['estado'] !== 'Activo'): ?>
            <div class="alerta alerta-aviso">El incentivo está <?= e(mb_strtolower($inc['estado'])) ?>. Actívelo para poder otorgarlo.</div>
        <?php elseif (!$grupos): ?>
            <p class="vacio">El curso no tiene grupos.</p>
        <?php else: ?>
            <form method="post" action="incentivo_accion.php" class="formulario">
                <?= campo_csrf() ?><input type="hidden" name="accion" value="otorgar"><input type="hidden" name="id" value="<?= $id ?>">
                <label for="id_grupo">Grupo</label>
                <select id="id_grupo" name="id_grupo" required>
                    <?php foreach ($grupos as $g): ?><option value="<?= (int) $g['id_grupo'] ?>" <?= $grupo_pre === (int) $g['id_grupo'] ? 'selected' : '' ?>><?= e($g['nombre_grupo']) ?></option><?php endforeach; ?>
                </select>
                <label for="id_estudiante">¿Quién lo obtuvo?</label>
                <select id="id_estudiante" name="id_estudiante">
                    <option value="">Todo el grupo</option>
                    <?php foreach ($integrantes_por_grupo as $id_g => $lista): foreach ($lista as $u): ?>
                        <option value="<?= (int) $u['usuario_id'] ?>" data-grupo="<?= $id_g ?>"><?= e($u['nombre'] . ' ' . $u['apellido']) ?></option>
                    <?php endforeach; endforeach; ?>
                </select>
                <label for="observacion">Observación</label>
                <textarea id="observacion" name="observacion" maxlength="1000" style="min-height:80px" placeholder="Ej: Entregó la fase 3 días antes y con todos los criterios cumplidos."></textarea>
                <button class="btn btn-primario btn-bloque"><?= icono('incentivo', 16) ?> Otorgar</button>
            </form>
            <script>
            (function () {
                var g = document.getElementById('id_grupo'), e = document.getElementById('id_estudiante');
                function f() { e.querySelectorAll('option[data-grupo]').forEach(function (o) { o.hidden = o.dataset.grupo !== g.value; if (o.hidden && o.selected) e.value = ''; }); }
                g.addEventListener('change', f); f();
            })();
            </script>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
