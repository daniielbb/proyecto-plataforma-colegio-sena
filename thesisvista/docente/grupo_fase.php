<?php

require_once __DIR__ . '/includes/inicio.php';

$pdo      = conectar();
$id_grupo = get_int('grupo') ?: (int) ($_POST['grupo'] ?? 0);
$id_fase  = get_int('fase') ?: (int) ($_POST['fase'] ?? 0);
$grupo    = grupo_para_docente($id_docente, $id_grupo);
$fase     = $grupo ? fase_para_docente($id_docente, $id_fase) : null;
if (!$grupo || !$fase || (int) $fase['id_curso'] !== (int) $grupo['id_curso']) {
    mensaje('error', 'La fase o el grupo no existen o no pertenecen a sus cursos.');
    redirigir('cursos.php');
}
$stmt = $pdo->prepare('SELECT * FROM fase_grupo WHERE id_fase = ? AND id_grupo = ?');
$stmt->execute([$id_fase, $id_grupo]);
$avance = $stmt->fetch();
if (!$avance) {
    mensaje('error', 'Esta fase no está asignada al grupo ' . $grupo['nombre_grupo'] . '. Puede asignarla al editar la fase.');
    redirigir('grupo.php?id=' . $id_grupo);
}

$url_fase = 'grupo_fase.php?grupo=' . $id_grupo . '&fase=' . $id_fase;
$url = $url_fase;
$integrantes = integrantes_grupo($id_grupo);
$ids_integrantes = array_map('intval', array_column($integrantes, 'usuario_id'));
$entregas = entregas_de_grupo_fase($id_grupo, $id_fase);
$ids_entregas = array_map('intval', array_column($entregas, 'id_entrega'));
$criterios = criterios_de_fase($id_fase);

$leer_destino = function () use ($ids_integrantes, $ids_entregas): array {
    $est = (int) ($_POST['id_estudiante'] ?? 0);
    $ent = (int) ($_POST['id_entrega'] ?? 0);
    if (($est && !in_array($est, $ids_integrantes, true)) || ($ent && !in_array($ent, $ids_entregas, true))) {
        throw new InvalidArgumentException('El estudiante o el trabajo seleccionado no pertenece a este grupo y fase.');
    }
    return [$est ?: null, $ent ?: null];
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf($url_fase);
    $accion = $_POST['accion'] ?? '';
    $comentario = mb_substr(trim((string) ($_POST['comentario'] ?? '')), 0, 5000);

    try {
        if ($accion === 'estado') {
            $estado = $_POST['estado'] ?? '';
            $permitidos = ['En progreso', 'En revisión', 'Requiere corrección', 'Aprobada', 'Completada'];
            if (!in_array($estado, $permitidos, true)) throw new InvalidArgumentException('Seleccione una acción válida.');
            if ($estado === 'Requiere corrección' && $comentario === '') throw new InvalidArgumentException('Explique qué debe corregir el grupo.');
            $porcentaje = null;
            if ($estado === 'En progreso') {
                $porcentaje = (int) ($_POST['porcentaje'] ?? -1);
                if ($porcentaje < 0 || $porcentaje > 99) throw new InvalidArgumentException('El avance debe estar entre 0 y 99 %. Para 100 % apruebe la fase.');
            }
            $estado_entrega = ['En revisión' => 'En revisión', 'Requiere corrección' => 'Requiere corrección', 'Aprobada' => 'Aprobado', 'Completada' => 'Aprobado'][$estado] ?? null;

            $pdo->beginTransaction();
            actualizar_avance($id_fase, $id_grupo, $estado, $porcentaje, $id_docente);
            $n = $estado_entrega ? revisar_ultimas_entregas($id_docente, $id_grupo, $id_fase, $estado_entrega, $comentario ?: null) : 0;
            if ($comentario !== '') {
                guardar_comentario($id_docente, $grupo, $id_fase, $estado === 'Requiere corrección' ? 'Corrección' : 'Observación', $comentario);
            }
            $pdo->commit();
            mensaje('exito', 'La fase quedó en «' . $estado . '» para ' . $grupo['nombre_grupo'] . ($n ? " ($n trabajo(s) actualizados)" : '') . '. El grupo ya lo ve.');
            redirigir($url_fase);
        }

        if ($accion === 'comentar') {
            $tipo = $_POST['tipo'] ?? '';
            if (!in_array($tipo, TIPOS_COMENTARIO, true)) throw new InvalidArgumentException('Seleccione el tipo de comentario.');
            if ($comentario === '') throw new InvalidArgumentException('Escriba el comentario.');
            [$est, $ent] = $leer_destino();
            guardar_comentario($id_docente, $grupo, $id_fase, $tipo, $comentario, $est, $ent);
            mensaje('exito', 'Comentario publicado. El grupo ya puede verlo.');
            redirigir($url_fase . '#retro');
        }

        if ($accion === 'calificar') {
            [$est, $ent] = $leer_destino();
            $notas_crit = [];
            foreach ($criterios as $c) {
                $v = $_POST['criterio'][$c['id_criterio']] ?? '';
                if (trim((string) $v) === '') continue;
                $n = leer_nota($v);
                if ($n === null) throw new InvalidArgumentException('La nota de «' . $c['nombre'] . '» debe estar entre 0.0 y 5.0.');
                $notas_crit[(int) $c['id_criterio']] = $n;
            }
            if ($notas_crit && count($notas_crit) !== count($criterios)) {
                throw new InvalidArgumentException('Califique todos los criterios o deje todos vacíos y escriba la nota final.');
            }
            $nota = $notas_crit ? nota_desde_criterios($criterios, $notas_crit) : leer_nota($_POST['nota'] ?? '');
            if ($nota === null) throw new InvalidArgumentException('Escriba una nota entre 0.0 y 5.0.');
            $obs = mb_substr(trim((string) ($_POST['observacion'] ?? '')), 0, 5000) ?: null;

            $pdo->beginTransaction();
            guardar_calificacion($id_docente, $grupo, $id_fase, $nota, $obs, $est, $ent, $notas_crit);
            $pdo->commit();
            mensaje('exito', 'Nota ' . formato_nota($nota) . ' guardada' . ($est ? ' para el estudiante.' : ' para el grupo.'));
            redirigir($url_fase . '#calificacion');
        }
    } catch (InvalidArgumentException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        mensaje('error', $ex->getMessage());
        redirigir($url_fase);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('grupo_fase: ' . $ex->getMessage());
        mensaje('error', $ex instanceof PDOException && ($ex->errorInfo[0] ?? '') === '45000' ? $ex->errorInfo[2] : 'No se pudo guardar. Intente de nuevo.');
        redirigir($url_fase);
    }
}

$notas = calificaciones(['id_grupo' => $id_grupo, 'id_fase' => $id_fase]);
$comentarios_fase = comentarios(['id_grupo' => $id_grupo, 'id_fase' => $id_fase]);
$incentivos_disp = array_filter(incentivos(['id_curso' => (int) $grupo['id_curso'], 'estado' => 'Activo']),
    fn($i) => !$i['id_fase'] || (int) $i['id_fase'] === $id_fase);
$otorgados = array_filter(incentivos_otorgados(['id_grupo' => $id_grupo]), fn($o) => !$o['id_fase'] || (int) $o['id_fase'] === $id_fase);
$evidencias = array_filter(array_map('trim', explode("\n", (string) $fase['evidencias'])));

$sel = get_int('entrega');
$entrega_sel = null;
foreach ($entregas as $en) if ((int) $en['id_entrega'] === $sel) $entrega_sel = $en;
if (!$entrega_sel && $entregas) $entrega_sel = $entregas[0];

$editar = null;
$editar_id = get_int('editar_nota');
foreach ($notas as $n) {
    if ((int) $n['id_calificacion'] === $editar_id && (int) $n['id_profesor'] === (int) $id_docente) $editar = $n;
}
if (!$editar) {
    foreach ($notas as $n) if ((int) $n['id_profesor'] === (int) $id_docente && $n['id_estudiante'] === null && $n['id_entrega'] === null) { $editar = $n; break; }
}
$notas_editar = $editar ? array_column(notas_por_criterio((int) $editar['id_calificacion']), 'nota', 'id_criterio') : [];

$por_estudiante = [];
foreach ($entregas as $en) $por_estudiante[(int) $en['id_estudiante']][] = $en;
$sin_entrega = array_filter($integrantes, fn($u) => !isset($por_estudiante[(int) $u['usuario_id']]));

$curso_txt = $grupo['nombre_curso'] . (is_numeric($grupo['nombre_curso']) ? '°' : '');
$titulo  = 'Fase ' . $fase['orden'] . ' · ' . $fase['nombre_fase'];
$subtitulo = $grupo['nombre_grupo'] . ' · Curso ' . $curso_txt;
$seccion = 'cursos';
$migas = [['Mis cursos', 'cursos.php'], [$curso_txt, 'curso.php?id=' . (int) $grupo['id_curso']],
          [$grupo['nombre_grupo'], 'grupo.php?id=' . $id_grupo], ['Fase ' . $fase['orden'], null]];
$auto_recargar = false;
require __DIR__ . '/includes/header.php';

$url_fase = 'grupo_fase.php?grupo=' . (int) $id_grupo . '&fase=' . (int) $id_fase;
$url = $url_fase;

$opciones_destino = function (?int $est_sel = null, ?int $ent_sel = null) use ($integrantes, $entregas): string {
    $h = '<div class="fila"><div><label>Para</label><select name="id_estudiante"><option value="">Todo el grupo</option>';
    foreach ($integrantes as $u) $h .= '<option value="' . (int) $u['usuario_id'] . '" ' . ($est_sel === (int) $u['usuario_id'] ? 'selected' : '') . '>' . e($u['nombre'] . ' ' . $u['apellido']) . '</option>';
    $h .= '</select></div><div><label>Trabajo (opcional)</label><select name="id_entrega"><option value="">La fase en general</option>';
    foreach ($entregas as $en) $h .= '<option value="' . (int) $en['id_entrega'] . '" ' . ($ent_sel === (int) $en['id_entrega'] ? 'selected' : '') . '>' . e($en['nombre_original'] . ' · v' . $en['version'] . ' · ' . $en['nombre']) . '</option>';
    return $h . '</select></div></div>';
};
?>

<section class="tarjeta" style="padding:18px 22px">
    <dl class="d-ficha">
        <div><dt>Estado del grupo</dt><dd><?= etiqueta($avance['estado']) ?></dd></div>
        <div><dt>Avance</dt><dd><?= barra(porcentaje_fase($avance)) ?></dd></div>
        <div><dt>Fecha límite</dt><dd><?= limite($fase['fecha_limite'], fase_terminada($avance['estado'])) ?></dd></div>
        <div><dt>Entregas</dt><dd><?= count($entregas) ?> versión(es) · <?= count($por_estudiante) ?>/<?= count($integrantes) ?> estudiantes</dd></div>
        <div><dt>Última actualización</dt><dd><?= $avance['fecha_actualizacion'] ? e(fecha_hora($avance['fecha_actualizacion'])) : '—' ?></dd></div>
    </dl>
</section>

<div class="d-columnas">
    <div>
        <section class="tarjeta">
            <div class="tarjeta-cabecera"><h2>Trabajos entregados</h2>
                <small class="d-tenue">Formato pedido: <?= e(TIPOS_ENTREGA[$fase['tipo_entrega']][0] ?? '') ?></small></div>
            <?php if (!$entregas): ?>
                <p class="vacio">El grupo todavía no ha entregado trabajos en esta fase.<br>
                    Cuando un estudiante suba su archivo aparecerá aquí automáticamente.</p>
            <?php else: ?>
                <?php foreach ($por_estudiante as $versiones): $u = $versiones[0]; ?>
                    <h3 style="font-size:15px;margin:14px 0 8px"><?= e($u['nombre'] . ' ' . $u['apellido']) ?> <small class="d-tenue">· <?= count($versiones) ?> versión(es)</small></h3>
                    <ul class="d-entregas">
                        <?php foreach ($versiones as $i => $en): $revs = revisiones_de_entrega((int) $en['id_entrega']); ?>
                            <li class="d-entrega <?= $i ? 'antigua' : '' ?>">
                                <span class="d-entrega-ico"><?= e($en['extension']) ?></span>
                                <div class="d-entrega-cuerpo">
                                    <strong><?= e($en['nombre_original']) ?></strong> <?= etiqueta($en['estado']) ?><?= $i ? ' <small>(versión anterior)</small>' : '' ?><br>
                                    <small>Versión <?= (int) $en['version'] ?> · <?= e(tamano_legible((int) $en['tamano'])) ?> · <?= e(fecha_hora($en['fecha_subida'])) ?>
                                        <?php if ($fase['fecha_limite'] && substr($en['fecha_subida'], 0, 10) > $fase['fecha_limite']): ?><span class="d-plazo vencido">fuera de plazo</span><?php endif; ?></small>
                                    <?php if ($en['comentario_estudiante']): ?><blockquote><?= nl2br(e($en['comentario_estudiante'])) ?></blockquote><?php endif; ?>
                                    <?php if ($revs): ?>
                                        <details style="margin-top:6px"><summary><small>Historial de revisión (<?= count($revs) ?>)</small></summary>
                                            <ul class="lista-simple"><?php foreach ($revs as $r): ?>
                                                <li><small><?= etiqueta($r['estado']) ?> · <?= e($r['nombre'] . ' ' . $r['apellido']) ?> · <?= e(fecha_hora($r['fecha'])) ?></small>
                                                    <?= $r['comentario'] ? '<br><small>' . nl2br(e($r['comentario'])) . '</small>' : '' ?></li>
                                            <?php endforeach; ?></ul></details>
                                    <?php endif; ?>
                                </div>
                                <div class="acciones">
                                    <a class="btn btn-secundario btn-chico" href="<?= e($url_fase) ?>&amp;entrega=<?= (int) $en['id_entrega'] ?>#visor" title="Ver aquí"><?= icono('ojo', 14) ?></a>
                                    <a class="btn btn-secundario btn-chico" href="archivo.php?entrega=<?= (int) $en['id_entrega'] ?>&amp;descargar=1" title="Descargar"><?= icono('descargar', 14) ?></a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
                <?php if ($sin_entrega): ?>
                    <p class="d-tenue" style="margin-top:14px"><strong>Sin entrega:</strong> <?= e(implode(', ', array_map(fn($u) => $u['nombre'] . ' ' . $u['apellido'], $sin_entrega))) ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <?php if ($entrega_sel): $ext = strtolower($entrega_sel['extension']); ?>
            <section class="tarjeta" id="visor">
                <div class="tarjeta-cabecera"><h2>Vista del trabajo</h2>
                    <a class="btn btn-secundario btn-chico" target="_blank" rel="noopener" href="archivo.php?entrega=<?= (int) $entrega_sel['id_entrega'] ?>">Abrir en otra pestaña</a></div>
                <p style="margin-top:0"><strong><?= e($entrega_sel['nombre_original']) ?></strong> · v<?= (int) $entrega_sel['version'] ?> · <?= e($entrega_sel['nombre'] . ' ' . $entrega_sel['apellido']) ?></p>
                <?php if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)): ?>
                    <img class="d-visor-img" src="archivo.php?entrega=<?= (int) $entrega_sel['id_entrega'] ?>" alt="Trabajo entregado">
                <?php elseif (in_array($ext, ['pdf', 'txt'], true)): ?>
                    <iframe class="d-visor" src="archivo.php?entrega=<?= (int) $entrega_sel['id_entrega'] ?>" title="Trabajo entregado"></iframe>
                <?php else: ?>
                    <p class="vacio">Este formato (<?= e(strtoupper($ext)) ?>) no se puede mostrar en el navegador.
                        <a href="archivo.php?entrega=<?= (int) $entrega_sel['id_entrega'] ?>&amp;descargar=1">Descargar el archivo</a></p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="tarjeta" id="retro">
            <h2>Retroalimentación del docente</h2>
            <form method="post" action="<?= e($url_fase) ?>" class="formulario" style="margin-bottom:18px">
                <?= campo_csrf() ?><input type="hidden" name="accion" value="comentar">
                <div class="fila">
                    <div><label>Tipo</label><select name="tipo"><?php foreach (TIPOS_COMENTARIO as $t): ?><option><?= e($t) ?></option><?php endforeach; ?></select></div>
                </div>
                <?= $opciones_destino(null, null) ?>
                <label>Comentario</label>
                <textarea name="comentario" required maxlength="5000" style="min-height:100px" placeholder="Observaciones, correcciones o recomendaciones para el grupo…"></textarea>
                <div class="botones"><button class="btn btn-primario"><?= icono('comentario', 16) ?> Publicar comentario</button></div>
            </form>
            <?= lista_comentarios($comentarios_fase, $id_docente, false, $url_fase . '#retro') ?>
        </section>

        <details class="tarjeta">
            <summary><strong>Instrucciones y requisitos de la fase</strong></summary>
            <div style="margin-top:14px">
                <?php if ($fase['objetivo']): ?><div class="d-bloque"><h3>Objetivo</h3><p class="d-texto"><?= e($fase['objetivo']) ?></p></div><?php endif; ?>
                <div class="d-bloque"><h3>Instrucciones</h3><p class="d-texto"><?= e($fase['instrucciones'] ?: $fase['descripcion']) ?></p></div>
                <?php if ($evidencias): ?><div class="d-bloque"><h3>Evidencias requeridas</h3><ul class="d-lista-check"><?php foreach ($evidencias as $ev): ?><li><?= e($ev) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                <?php if ($fase['requisitos_siguiente']): ?><div class="d-bloque"><h3>Para pasar a la siguiente fase</h3><p class="d-texto"><?= e($fase['requisitos_siguiente']) ?></p></div><?php endif; ?>
                <a href="fase.php?id=<?= (int) $id_fase ?>">Ver la fase completa</a>
            </div>
        </details>
    </div>

    <div>
        <section class="tarjeta">
            <h2>Estado de la fase</h2>
            <form method="post" action="<?= e($url_fase) ?>" class="formulario">
                <?= campo_csrf() ?><input type="hidden" name="accion" value="estado">
                <?php foreach ([
                    'En revisión'         => 'Estoy revisando el trabajo',
                    'Requiere corrección' => 'Pedir correcciones (escriba qué corregir)',
                    'Aprobada'            => 'Aprobar la fase',
                    'Completada'          => 'Marcar como completada (cumple los requisitos)',
                    'En progreso'         => 'Registrar avance parcial',
                ] as $est => $txt): ?>
                    <label class="casilla d-opcion" style="margin:6px 0">
                        <input type="radio" name="estado" value="<?= e($est) ?>" <?= $avance['estado'] === $est ? 'checked' : '' ?> required>
                        <span><?= etiqueta($est) ?> <small class="d-tenue"><?= e($txt) ?></small></span></label>
                <?php endforeach; ?>
                <label for="porcentaje">Avance registrado (solo para “En progreso”)</label>
                <input type="number" id="porcentaje" name="porcentaje" min="0" max="99" value="<?= fase_terminada($avance['estado']) ? 99 : (int) $avance['porcentaje_avance'] ?>">
                <label for="com-estado">Comentario para el grupo</label>
                <textarea id="com-estado" name="comentario" style="min-height:80px" maxlength="5000" placeholder="Obligatorio si pide correcciones"></textarea>
                <button class="btn btn-primario btn-bloque">Actualizar estado</button>
            </form>
        </section>

        <section class="tarjeta" id="calificacion">
            <h2><?= $editar ? 'Editar calificación' : 'Calificación' ?></h2>
            <form method="post" action="<?= e($url_fase) ?>" class="formulario">
                <?= campo_csrf() ?><input type="hidden" name="accion" value="calificar">
                <?= $opciones_destino($editar ? ($editar['id_estudiante'] !== null ? (int) $editar['id_estudiante'] : null) : null,
                                      $editar ? ($editar['id_entrega'] !== null ? (int) $editar['id_entrega'] : null) : null) ?>
                <?php if ($criterios): ?>
                    <label>Nota por criterio (0.0 – 5.0)</label>
                    <table class="d-criterios-nota"><tbody>
                    <?php foreach ($criterios as $c): ?>
                        <tr><td><strong><?= e($c['nombre']) ?></strong><?= $c['peso'] !== null ? ' <small class="d-tenue">(' . e(rtrim(rtrim($c['peso'], '0'), '.')) . '%)</small>' : '' ?>
                            <?= $c['descripcion'] ? '<br><small class="d-tenue">' . e($c['descripcion']) . '</small>' : '' ?></td>
                            <td><input type="number" step="0.1" min="0" max="5" name="criterio[<?= (int) $c['id_criterio'] ?>]" class="nota-criterio"
                                 data-peso="<?= e((string) ($c['peso'] ?? '')) ?>" value="<?= isset($notas_editar[$c['id_criterio']]) ? e(number_format((float) $notas_editar[$c['id_criterio']], 1)) : '' ?>"></td></tr>
                    <?php endforeach; ?>
                    </tbody></table>
                    <div class="d-final"><span>Nota final calculada:</span><span class="d-nota-grande" id="nota-final"><?= $editar ? formato_nota($editar['nota']) : '—' ?></span></div>
                    <p class="ayuda">Si prefiere no usar criterios, déjelos vacíos y escriba la nota final:</p>
                <?php endif; ?>
                <label for="nota">Nota final (0.0 – 5.0)</label>
                <input type="number" step="0.1" min="0" max="5" id="nota" name="nota" value="<?= $editar && !$notas_editar ? e(number_format((float) $editar['nota'], 1)) : '' ?>">
                <label for="observacion">Observación de la nota</label>
                <textarea id="observacion" name="observacion" style="min-height:70px" maxlength="5000"><?= e($editar['observacion'] ?? '') ?></textarea>
                <button class="btn btn-primario btn-bloque"><?= icono('nota', 16) ?> Guardar nota</button>
                <p class="ayuda">Nota aprobatoria: <?= formato_nota(NOTA_APROBATORIA) ?>. Si ya existe una nota suya para el mismo destino, se actualiza y el cambio queda en el historial.</p>
            </form>

            <?php if ($notas): ?>
                <h3 style="margin-top:20px;font-size:16px">Calificaciones registradas</h3>
                <ul class="lista-simple">
                    <?php foreach ($notas as $n): $hist = historial_calificacion((int) $n['id_calificacion']); $det = notas_por_criterio((int) $n['id_calificacion']); ?>
                        <li>
                            <span class="d-nota <?= (float) $n['nota'] >= NOTA_APROBATORIA ? 'aprobada' : 'baja' ?>"><?= formato_nota($n['nota']) ?></span>
                            <strong><?= $n['est_nombre'] ? e($n['est_nombre'] . ' ' . $n['est_apellido']) : 'Todo el grupo' ?></strong>
                            <?= $n['version'] ? '<small>· ' . e($n['nombre_original']) . ' v' . (int) $n['version'] . '</small>' : '' ?><br>
                            <small><?= e($n['nombre'] . ' ' . $n['apellido']) ?> · <?= e(fecha_hora($n['fecha_modificacion'] ?? $n['fecha'])) ?></small>
                            <?php if ((int) $n['id_profesor'] === (int) $id_docente): ?>
                                <a class="d-enlace" href="grupo_fase.php?grupo=<?= (int) $id_grupo ?>&amp;fase=<?= (int) $id_fase ?>&amp;editar_nota=<?= (int) $n['id_calificacion'] ?>#calificacion">Editar</a>
                            <?php endif; ?>
                            <?php if ($det): ?><br><small class="d-tenue"><?= e(implode(' · ', array_map(fn($x) => $x['nombre'] . ': ' . formato_nota($x['nota']), $det))) ?></small><?php endif; ?>
                            <?php if ($n['observacion']): ?><br><small><?= nl2br(e($n['observacion'])) ?></small><?php endif; ?>
                            <?php if (count($hist) > 1): ?>
                                <details><summary><small>Calificaciones anteriores (<?= count($hist) - 1 ?>)</small></summary><ul class="lista-simple">
                                    <?php foreach (array_slice($hist, 0, -1) as $h): ?>
                                        <li><small><?= formato_nota($h['nota_anterior']) ?> → <strong><?= formato_nota($h['nota_nueva']) ?></strong> · <?= e(fecha_hora($h['fecha'])) ?></small></li>
                                    <?php endforeach; ?></ul></details>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="tarjeta">
            <h2>Incentivos</h2>
            <?php if ($incentivos_disp): ?>
                <form method="post" action="incentivo_accion.php" class="formulario">
                    <?= campo_csrf() ?><input type="hidden" name="accion" value="otorgar">
                    <input type="hidden" name="id_grupo" value="<?= (int) $id_grupo ?>"><input type="hidden" name="volver" value="<?= e($url_fase) ?>">
                    <label>Incentivo</label>
                    <select name="id" required><?php foreach ($incentivos_disp as $i): ?>
                        <option value="<?= (int) $i['id_incentivo_grupal'] ?>"><?= e(($i['icono'] ?: '🏆') . ' ' . $i['nombre']) ?></option><?php endforeach; ?></select>
                    <label>Para</label>
                    <select name="id_estudiante"><option value="">Todo el grupo</option>
                        <?php foreach ($integrantes as $u): ?><option value="<?= (int) $u['usuario_id'] ?>"><?= e($u['nombre'] . ' ' . $u['apellido']) ?></option><?php endforeach; ?></select>
                    <label>Observación</label>
                    <input name="observacion" maxlength="1000" placeholder="Por qué lo obtuvo">
                    <button class="btn btn-secundario btn-bloque"><?= icono('incentivo', 16) ?> Otorgar incentivo</button>
                </form>
            <?php else: ?>
                <p class="d-tenue">No hay incentivos activos para esta fase. <a href="incentivo_form.php?curso=<?= (int) $grupo['id_curso'] ?>&amp;fase=<?= (int) $id_fase ?>">Crear uno</a></p>
            <?php endif; ?>
            <div style="margin-top:16px"><?= lista_otorgados(array_values($otorgados), false) ?></div>
        </section>
    </div>
</div>

<?php if ($criterios): ?>
<script>
(function () {
    var campos = document.querySelectorAll('.nota-criterio'), salida = document.getElementById('nota-final');
    function calcular() {
        var suma = 0, pesos = 0, llenos = 0, todosConPeso = true;
        campos.forEach(function (c) { if (!(parseFloat(c.dataset.peso) > 0)) todosConPeso = false; });
        campos.forEach(function (c) {
            if (c.value === '') return;
            var w = todosConPeso ? parseFloat(c.dataset.peso) : 1;
            suma += parseFloat(c.value) * w; pesos += w; llenos++;
        });
        salida.textContent = llenos === campos.length ? (Math.round(suma / pesos * 10) / 10).toFixed(1) : '—';
    }
    campos.forEach(function (c) { c.addEventListener('input', calcular); });
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>