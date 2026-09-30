<?php
require_once __DIR__ . '/../includes/docente_init.php';

$id_tesis = entero($_GET['tesis'] ?? $_POST['id_tesis'] ?? 0);
$id_fase  = entero($_GET['fase'] ?? $_POST['id_fase'] ?? 0);

$tesis = obtener_tesis($pdo, $doc, $id_tesis);
if (!$tesis) {
    denegar('Este proyecto no pertenece a tus grupos asignados.');
}
$fases  = fases_de_tesis($pdo, $id_tesis, (int)$tesis['id_curso']);
$por_id = array_column($fases, null, 'id_fase');
if (!isset($por_id[$id_fase])) {
    denegar('La fase no pertenece al curso de este proyecto.');
}
$fase = $por_id[$id_fase];
$volver = 'docente/fase.php?tesis=' . $id_tesis . '&fase=' . $id_fase;

/* ------------------------------------------------------------------
   Acciones
   ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'estado_fase') {
        $estado = in_array($_POST['estado'] ?? '', ESTADOS_FASE, true) ? $_POST['estado'] : 'Pendiente';
        $limite = ($_POST['fecha_limite'] ?? '') !== '' && strtotime($_POST['fecha_limite']) ? date('Y-m-d', strtotime($_POST['fecha_limite'])) : null;
        $obs    = trim($_POST['observaciones'] ?? '');
        $hoy    = date('Y-m-d');

        $inicio     = $fase['fecha_inicio'] ?: (in_array($estado, ['En progreso', 'Atrasada', 'Completada'], true) ? $hoy : null);
        $completada = $estado === 'Completada' ? ($fase['fecha_completada'] ?: $hoy) : null;

        $pdo->prepare(
            'INSERT INTO tesis_fase (id_tesis, id_fase, estado, fecha_inicio, fecha_limite, fecha_completada, observaciones)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE estado = VALUES(estado), fecha_inicio = VALUES(fecha_inicio),
                 fecha_limite = VALUES(fecha_limite), fecha_completada = VALUES(fecha_completada),
                 observaciones = VALUES(observaciones)')
            ->execute([$id_tesis, $id_fase, $estado, $inicio, $limite, $completada, $obs === '' ? null : $obs]);
        flash('ok', 'Estado de la fase actualizado.');
    }

    if ($accion === 'agregar_requisito') {
        $desc = trim($_POST['descripcion'] ?? '');
        $alcance_general = ($_POST['alcance'] ?? '') === 'general';
        if ($desc === '') {
            flash('error', 'Escribe la descripción del requisito.');
        } else {
            $orden = $pdo->prepare('SELECT COALESCE(MAX(orden), 0) + 1 FROM requisitos_fase WHERE id_fase = ?');
            $orden->execute([$id_fase]);
            $pdo->prepare('INSERT INTO requisitos_fase (id_fase, id_tesis, descripcion, obligatorio, orden, id_profesor, fecha_creacion)
                           VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$id_fase, $alcance_general ? null : $id_tesis, mb_substr($desc, 0, 500),
                           !empty($_POST['obligatorio']) ? 1 : 0, (int)$orden->fetchColumn(), $doc, date('Y-m-d H:i:s')]);
            flash('ok', 'Requisito agregado.');
        }
    }

    if ($accion === 'eliminar_requisito') {
        // Solo el docente que lo creó puede eliminarlo
        $del = $pdo->prepare('DELETE FROM requisitos_fase WHERE id_requisito = ? AND id_fase = ? AND id_profesor = ?
                                AND (id_tesis IS NULL OR id_tesis = ?)');
        $del->execute([entero($_POST['id_requisito'] ?? 0), $id_fase, $doc, $id_tesis]);
        flash($del->rowCount() ? 'ok' : 'error', $del->rowCount() ? 'Requisito eliminado.' : 'Solo puedes eliminar requisitos que tú creaste.');
    }
    redirigir($volver);
}

/* ------------------------------------------------------------------
   Consultas
   ------------------------------------------------------------------ */
$st = $pdo->prepare('SELECT r.*, u.nombre, u.apellido FROM requisitos_fase r JOIN usuarios u ON u.usuario_id = r.id_profesor
                      WHERE r.id_fase = ? AND (r.id_tesis IS NULL OR r.id_tesis = ?) ORDER BY r.orden, r.id_requisito');
$st->execute([$id_fase, $id_tesis]);
$requisitos = $st->fetchAll();

$documentos = documentos_de_tesis($pdo, $id_tesis, $id_fase);
$historial  = historial_revisiones($pdo, [$id_tesis], $id_fase);
$revisiones = array_values(array_filter($historial, fn($h) => $h['tipo'] === 'revision'));
$comentarios = array_values(array_filter($historial, fn($h) => $h['comentario'] !== null && $h['comentario'] !== ''));
$pr  = resumen_progreso($fases);
$hoy = date('Y-m-d');

$titulo_pagina = 'Fase ' . $fase['orden'] . ' — ' . $fase['nombre_fase'];
$pagina_activa = 'proyectos';
require __DIR__ . '/../includes/header.php';
?>

<div class="migas">
    <a href="proyectos.php">Proyectos</a> /
    <a href="proyecto.php?id=<?= $id_tesis ?>"><?= e($tesis['titulo']) ?></a> /
    Fase <?= (int)$fase['orden'] ?>
</div>

<section class="card">
    <div class="card-titulo">
        <h3>Fases de «<?= e($tesis['titulo']) ?>» · <?= e($tesis['nombre_grupo'] ?? 'Sin grupo') ?></h3>
        <div class="progreso-fila" style="min-width:220px"><?= barra_progreso($pr['porcentaje']) ?><strong><?= $pr['porcentaje'] ?>%</strong></div>
    </div>
    <div class="carpetas">
        <?php foreach ($fases as $f): $cls = strtolower(str_replace(' ', '-', $f['estado'])); ?>
        <a class="carpeta <?= e($cls) ?> <?= (int)$f['id_fase'] === $id_fase ? 'seleccionada' : '' ?>"
           href="fase.php?tesis=<?= $id_tesis ?>&fase=<?= (int)$f['id_fase'] ?>">
            <span class="fase-num">Fase <?= (int)$f['orden'] ?></span>
            <span class="fase-nombre"><?= e($f['nombre_fase']) ?></span>
            <?= simbolo_fase($f['estado']) ?> <?= badge($f['estado']) ?>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<div class="dos-col">
    <div>
        <section class="card">
            <div class="card-titulo">
                <div>
                    <div class="etiqueta-superior">Fase <?= (int)$fase['orden'] ?></div>
                    <h2><?= e($fase['nombre_fase']) ?></h2>
                </div>
                <?= badge($fase['estado']) ?>
            </div>
            <p><?= e($fase['descripcion']) ?></p>
            <?php if ($fase['ejemplo']): ?>
                <div class="comentario-item"><div class="meta">Ejemplo de referencia</div><p><?= e($fase['ejemplo']) ?></p></div>
            <?php endif; ?>

            <dl class="ficha" style="margin-top:1rem">
                <dt>Estado</dt><dd><?= e($fase['estado']) ?></dd>
                <dt>Fecha de inicio</dt><dd><?= fecha($fase['fecha_inicio']) ?></dd>
                <dt>Fecha de entrega</dt>
                <dd class="<?= $fase['fecha_limite'] && $fase['fecha_limite'] < $hoy && $fase['estado'] !== 'Completada' ? 'texto-vencido' : '' ?>">
                    <?= fecha($fase['fecha_limite']) ?></dd>
                <dt>Completada el</dt><dd><?= fecha($fase['fecha_completada']) ?></dd>
                <dt>Duración sugerida</dt><dd><?= $fase['duracion_dias'] ? (int)$fase['duracion_dias'] . ' días' : '—' ?></dd>
                <dt>Observaciones</dt><dd><?= $fase['observaciones'] ? e($fase['observaciones']) : '—' ?></dd>
            </dl>
        </section>

        <section class="card">
            <div class="card-titulo"><h2>Requisitos</h2></div>
            <?php if (!$requisitos): ?>
                <p class="texto-suave">No se han definido requisitos para esta fase.</p>
            <?php else: ?>
            <ul class="lista-actividad">
                <?php foreach ($requisitos as $r): ?>
                <li>
                    <span class="punto <?= $r['obligatorio'] ? 'revision' : '' ?>"></span>
                    <div style="flex:1">
                        <?= e($r['descripcion']) ?><br>
                        <span class="texto-suave chico"><?= $r['obligatorio'] ? 'Obligatorio' : 'Opcional' ?> ·
                            <?= $r['id_tesis'] ? 'Solo este proyecto' : 'Todos los proyectos' ?> ·
                            <?= e($r['nombre'] . ' ' . $r['apellido']) ?></span>
                    </div>
                    <?php if ((int)$r['id_profesor'] === $doc): ?>
                    <form method="post">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="accion" value="eliminar_requisito">
                        <input type="hidden" name="id_tesis" value="<?= $id_tesis ?>">
                        <input type="hidden" name="id_fase" value="<?= $id_fase ?>">
                        <input type="hidden" name="id_requisito" value="<?= (int)$r['id_requisito'] ?>">
                        <button class="btn btn-peligro btn-chico">Quitar</button>
                    </form>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <form method="post" class="formulario" style="margin-top:1rem">
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="agregar_requisito">
                <input type="hidden" name="id_tesis" value="<?= $id_tesis ?>">
                <input type="hidden" name="id_fase" value="<?= $id_fase ?>">
                <label>Nuevo requisito
                    <input type="text" name="descripcion" maxlength="500" placeholder="Ej: Incluir mínimo 5 referencias bibliográficas">
                </label>
                <div class="form-linea">
                    <select name="alcance">
                        <option value="proyecto">Solo este proyecto</option>
                        <option value="general">Todos los proyectos del curso</option>
                    </select>
                    <label class="check"><input type="checkbox" name="obligatorio" value="1" checked> Obligatorio</label>
                    <button class="btn btn-secundario btn-chico">Agregar requisito</button>
                </div>
            </form>
        </section>

        <section class="card">
            <div class="card-titulo">
                <h2>Documentos enviados</h2>
                <a class="btn btn-secundario btn-chico" href="revision.php?tesis=<?= $id_tesis ?>&fase=<?= $id_fase ?>">Revisar fase sin documento</a>
            </div>
            <?php if (!$documentos): ?>
                <p class="texto-suave">El grupo no ha enviado documentos en esta fase.</p>
            <?php else: ?>
            <div class="tabla-contenedor">
                <table>
                    <thead><tr><th>Documento</th><th>Envío</th><th>Última modificación</th><th>Estado</th><th class="acciones"></th></tr></thead>
                    <tbody>
                    <?php foreach ($documentos as $d): ?>
                        <tr>
                            <td><strong><?= e($d['nombre_documento']) ?></strong><br><span class="texto-suave chico"><?= e($d['tipo_documento']) ?></span></td>
                            <td><?= fecha($d['fecha_subida']) ?></td>
                            <td><?= fecha_hora($d['fecha_modificacion']) ?></td>
                            <td><?= badge($d['estado']) ?></td>
                            <td class="acciones"><a class="btn btn-chico" href="revision.php?doc=<?= (int)$d['id_documento'] ?>">Revisar documento</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card-titulo">
                <h2>Historial de revisiones de la fase</h2>
                <a class="chico" href="historial.php?tesis=<?= $id_tesis ?>&fase=<?= $id_fase ?>">Ver en tabla</a>
            </div>
            <?php if (!$revisiones): ?>
                <p class="texto-suave">No hay revisiones registradas en esta fase.</p>
            <?php else: foreach ($revisiones as $r): ?>
                <div class="revision-item">
                    <div class="cabecera">
                        <div><strong><?= e($r['nombre_documento'] ?? 'Revisión de la fase') ?></strong>
                            <span class="texto-suave chico"> · <?= fecha_hora($r['fecha']) ?> · <?= e($r['nombre'] . ' ' . $r['apellido']) ?></span></div>
                        <div class="botones"><?= badge($r['estado']) ?>
                            <?php if ((int)$r['id_autor'] === $doc): ?>
                                <a class="btn btn-secundario btn-chico" href="revision.php?editar=<?= (int)$r['id_correccion'] ?>">Editar</a>
                            <?php endif; ?></div>
                    </div>
                    <dl>
                        <?php if ($r['comentario']): ?><dt>Comentario</dt><dd><?= e($r['comentario']) ?></dd><?php endif; ?>
                        <?php if ($r['observacion'] !== ''): ?><dt>Corrección</dt><dd><?= e($r['observacion']) ?></dd><?php endif; ?>
                        <?php if ($r['recomendacion']): ?><dt>Recomendación</dt><dd><?= e($r['recomendacion']) ?></dd><?php endif; ?>
                        <?php if ($r['calificacion'] !== null): ?><dt>Calificación</dt><dd><?= e($r['calificacion']) ?> / 5.0</dd><?php endif; ?>
                    </dl>
                </div>
            <?php endforeach; endif; ?>
        </section>
    </div>

    <aside>
        <section class="card">
            <h3>Actualizar estado de la fase</h3>
            <form method="post" class="formulario">
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="estado_fase">
                <input type="hidden" name="id_tesis" value="<?= $id_tesis ?>">
                <input type="hidden" name="id_fase" value="<?= $id_fase ?>">
                <label>Estado
                    <select name="estado">
                        <?php foreach (ESTADOS_FASE as $es): ?>
                            <option <?= $fase['estado'] === $es ? 'selected' : '' ?>><?= e($es) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Fecha de entrega
                    <input type="date" name="fecha_limite" value="<?= e($fase['fecha_limite']) ?>">
                </label>
                <label>Observaciones para el grupo
                    <textarea name="observaciones" placeholder="Visible para los estudiantes"><?= e($fase['observaciones']) ?></textarea>
                </label>
                <button class="btn">Guardar</button>
            </form>
            <p class="texto-suave chico" style="margin-top:.75rem">Para avanzar el grupo a otra fase usa
                <a href="proyecto.php?id=<?= $id_tesis ?>">Establecer siguiente fase</a>.</p>
        </section>

        <section class="card">
            <h3>Comentarios anteriores</h3>
            <?php if (!$comentarios): ?>
                <p class="texto-suave chico">No hay comentarios en esta fase.</p>
            <?php else: foreach ($comentarios as $c): ?>
                <div class="comentario-item <?= $c['rol'] === 'estudiante' ? 'de-estudiante' : '' ?>">
                    <div class="meta"><?= e($c['nombre'] . ' ' . $c['apellido']) ?> · <?= fecha_hora($c['fecha']) ?></div>
                    <p><?= e($c['comentario']) ?></p>
                </div>
            <?php endforeach; endif; ?>
        </section>
    </aside>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
