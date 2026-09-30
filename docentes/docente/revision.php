<?php
/**
 * Registrar o editar una revisión del docente.
 *   revision.php?doc=ID              -> revisar un documento
 *   revision.php?tesis=ID&fase=ID    -> revisar una fase (sin documento)
 *   revision.php?editar=ID           -> editar una revisión propia
 *
 * Una revisión guarda:
 *   - correcciones  : estado, corrección (observacion), recomendación, calificación
 *   - comentarios   : comentario del docente (misma fecha que la revisión)
 *   - documento     : estado del documento (si la revisión es sobre un documento)
 */
require_once __DIR__ . '/../includes/docente_init.php';

$editar   = entero($_GET['editar'] ?? $_POST['editar'] ?? 0);
$id_doc   = entero($_GET['doc'] ?? $_POST['doc'] ?? 0);
$id_tesis = entero($_GET['tesis'] ?? $_POST['tesis'] ?? 0);
$id_fase  = entero($_GET['fase'] ?? $_POST['fase'] ?? 0);

$revision   = null;   // revisión que se edita
$comentario_existente = null;

if ($editar) {
    $st = $pdo->prepare('SELECT * FROM correcciones WHERE id_correccion = ?');
    $st->execute([$editar]);
    $revision = $st->fetch();
    if (!$revision || (int)$revision['id_profesor'] !== $doc || !puede_ver_tesis($pdo, $doc, (int)$revision['id_tesis'])) {
        denegar('Solo puedes editar las revisiones que tú registraste.');
    }
    $id_doc   = (int)$revision['id_documento'];
    $id_tesis = (int)$revision['id_tesis'];
    $id_fase  = (int)$revision['id_fase'];

    $st = $pdo->prepare('SELECT * FROM comentarios WHERE id_tesis = ? AND id_fase <=> ? AND id_documento <=> ?
                          AND id_usuario = ? AND fecha = ? LIMIT 1');
    $st->execute([$id_tesis, $revision['id_fase'], $revision['id_documento'], $doc, $revision['fecha']]);
    $comentario_existente = $st->fetch() ?: null;
}

$documento = null;
if ($id_doc) {
    $st = $pdo->prepare('SELECT * FROM documento WHERE id_documento = ?');
    $st->execute([$id_doc]);
    $documento = $st->fetch();
    if (!$documento || !puede_ver_tesis($pdo, $doc, (int)$documento['id_tesis'])) {
        denegar('Este documento no pertenece a tus grupos asignados.');
    }
    $id_tesis = (int)$documento['id_tesis'];
    if (!$editar) {
        $id_fase = (int)$documento['id_fase'];
    }
}

$tesis = obtener_tesis($pdo, $doc, $id_tesis);
if (!$tesis) {
    denegar('Este proyecto no pertenece a tus grupos asignados.');
}
$fases  = fases_de_tesis($pdo, $id_tesis, (int)$tesis['id_curso']);
$por_id = array_column($fases, null, 'id_fase');
if ($id_fase && !isset($por_id[$id_fase])) {
    denegar('La fase no pertenece al curso de este proyecto.');
}
if (!$id_doc && !$id_fase) {
    denegar('Selecciona un documento o una fase para revisar.');
}
$fase = $id_fase ? $por_id[$id_fase] : null;

/* ------------------------------------------------------------------
   Guardar
   ------------------------------------------------------------------ */
$errores = [];
$datos = [
    'comentario'    => $comentario_existente['comentario'] ?? '',
    'observacion'   => $revision['observacion'] ?? '',
    'recomendacion' => $revision['recomendacion'] ?? '',
    'calificacion'  => $revision['calificacion'] ?? '',
    'estado'        => $revision['estado'] ?? 'Requiere ajustes',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $datos = [
        'comentario'    => trim($_POST['comentario'] ?? ''),
        'observacion'   => trim($_POST['observacion'] ?? ''),
        'recomendacion' => trim($_POST['recomendacion'] ?? ''),
        'calificacion'  => trim(str_replace(',', '.', $_POST['calificacion'] ?? '')),
        'estado'        => $_POST['estado'] ?? '',
    ];
    if (!array_key_exists($datos['estado'], ESTADOS_REVISION)) {
        $errores[] = 'Selecciona un estado de revisión válido.';
    }
    $nota = null;
    if ($datos['calificacion'] !== '') {
        if (!is_numeric($datos['calificacion']) || $datos['calificacion'] < 0 || $datos['calificacion'] > 5) {
            $errores[] = 'La calificación debe estar entre 0.0 y 5.0.';
        } else {
            $nota = round((float)$datos['calificacion'], 1);
        }
    }
    if ($datos['estado'] === 'Requiere ajustes' && $datos['observacion'] === '') {
        $errores[] = 'Describe la corrección que el grupo debe realizar.';
    }

    if (!$errores) {
        $pdo->beginTransaction();
        try {
            $fase_db = $id_fase ?: null;
            $doc_db  = $id_doc ?: null;

            if ($revision) {
                // ---- Edición de una revisión propia
                $pdo->prepare('UPDATE correcciones SET estado = ?, observacion = ?, recomendacion = ?, calificacion = ?
                                WHERE id_correccion = ? AND id_profesor = ?')
                    ->execute([$datos['estado'], $datos['observacion'], $datos['recomendacion'] ?: null, $nota, $editar, $doc]);
                $fecha_rev = $revision['fecha'];

                if ($comentario_existente && $datos['comentario'] !== '') {
                    $pdo->prepare('UPDATE comentarios SET comentario = ? WHERE id_comentario = ? AND id_usuario = ?')
                        ->execute([$datos['comentario'], $comentario_existente['id_comentario'], $doc]);
                } elseif ($comentario_existente) {
                    $pdo->prepare('DELETE FROM comentarios WHERE id_comentario = ? AND id_usuario = ?')
                        ->execute([$comentario_existente['id_comentario'], $doc]);
                } elseif ($datos['comentario'] !== '') {
                    $pdo->prepare('INSERT INTO comentarios (id_tesis, id_fase, id_documento, id_usuario, comentario, fecha) VALUES (?, ?, ?, ?, ?, ?)')
                        ->execute([$id_tesis, $fase_db, $doc_db, $doc, $datos['comentario'], $fecha_rev]);
                }

                // El estado del documento solo cambia si esta es su revisión más reciente
                $es_ultima = false;
                if ($doc_db) {
                    $st = $pdo->prepare('SELECT id_correccion FROM correcciones WHERE id_documento = ? ORDER BY fecha DESC, id_correccion DESC LIMIT 1');
                    $st->execute([$doc_db]);
                    $es_ultima = (int)$st->fetchColumn() === $editar;
                }
            } else {
                // ---- Nueva revisión
                $fecha_rev = date('Y-m-d H:i:s');
                $pdo->prepare('INSERT INTO correcciones (id_tesis, id_fase, id_documento, id_profesor, estado, observacion, recomendacion, calificacion, fecha)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$id_tesis, $fase_db, $doc_db, $doc, $datos['estado'], $datos['observacion'],
                               $datos['recomendacion'] ?: null, $nota, $fecha_rev]);
                if ($datos['comentario'] !== '') {
                    $pdo->prepare('INSERT INTO comentarios (id_tesis, id_fase, id_documento, id_usuario, comentario, fecha) VALUES (?, ?, ?, ?, ?, ?)')
                        ->execute([$id_tesis, $fase_db, $doc_db, $doc, $datos['comentario'], $fecha_rev]);
                }
                $es_ultima = (bool)$doc_db;
            }

            if ($doc_db && $es_ultima) {
                // fecha_modificacion = fecha_modificacion evita que ON UPDATE la cambie:
                // esa fecha corresponde a la última modificación del estudiante.
                $pdo->prepare('UPDATE documento SET estado = ?, fecha_modificacion = fecha_modificacion WHERE id_documento = ?')
                    ->execute([estado_rev_a_doc($datos['estado']), $doc_db]);
            }
            $pdo->commit();

            $msg = $revision ? 'Revisión actualizada.' : 'Revisión guardada. El grupo podrá verla en su interfaz.';
            if ($datos['estado'] === 'Aprobada' && $fase && $fase['estado'] !== 'Completada') {
                $msg .= ' Si el grupo terminó esta fase, puedes establecer la siguiente fase desde el proyecto.';
            }
            flash('ok', $msg);
            redirigir($id_fase ? "docente/fase.php?tesis=$id_tesis&fase=$id_fase" : "docente/proyecto.php?id=$id_tesis");
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $errores[] = 'No fue posible guardar la revisión. Intenta de nuevo.';
        }
    }
}

/* ------------------------------------------------------------------
   Revisiones anteriores del mismo documento o fase
   ------------------------------------------------------------------ */
$anteriores = array_values(array_filter(
    historial_revisiones($pdo, [$id_tesis], $id_fase ?: null),
    fn($h) => $h['tipo'] === 'revision'
        && ($id_doc ? (int)$h['id_documento'] === $id_doc : $h['id_documento'] === null)
        && (int)$h['id_correccion'] !== $editar
));

$titulo_pagina = $revision ? 'Editar revisión' : ($documento ? 'Revisar documento' : 'Revisar fase');
$pagina_activa = 'revisiones';
require __DIR__ . '/../includes/header.php';
?>

<div class="migas">
    <a href="revisiones.php">Revisiones</a> /
    <a href="proyecto.php?id=<?= $id_tesis ?>"><?= e($tesis['titulo']) ?></a>
    <?php if ($fase): ?> / <a href="fase.php?tesis=<?= $id_tesis ?>&fase=<?= $id_fase ?>">Fase <?= (int)$fase['orden'] ?></a><?php endif; ?>
</div>

<div class="dos-col">
    <div>
        <section class="card">
            <div class="card-titulo">
                <div>
                    <div class="etiqueta-superior"><?= $documento ? 'Documento' : 'Fase sin documento' ?></div>
                    <h2><?= e($documento['nombre_documento'] ?? $fase['nombre_fase']) ?></h2>
                </div>
                <?= $documento ? badge($documento['estado']) : badge($fase['estado']) ?>
            </div>
            <dl class="ficha">
                <dt>Grupo</dt><dd><?= e($tesis['nombre_grupo'] ?? 'Sin grupo') ?></dd>
                <dt>Proyecto</dt><dd><?= e($tesis['titulo']) ?></dd>
                <dt>Fase</dt><dd><?= $fase ? 'Fase ' . (int)$fase['orden'] . ' — ' . e($fase['nombre_fase']) : 'Sin fase asociada' ?></dd>
                <?php if ($documento): ?>
                    <dt>Tipo</dt><dd><?= e($documento['tipo_documento']) ?></dd>
                    <dt>Fecha de envío</dt><dd><?= fecha($documento['fecha_subida']) ?></dd>
                    <dt>Última modificación</dt><dd><?= fecha_hora($documento['fecha_modificacion']) ?></dd>
                    <dt>Estado de revisión</dt><dd><?= e(ESTADOS_DOCUMENTO[$documento['estado']] ?? $documento['estado']) ?></dd>
                <?php endif; ?>
            </dl>
            <?php if ($documento): ?>
                <div class="botones" style="margin-top:1rem">
                    <?php if ($documento['ruta_archivo']): ?>
                        <a class="btn btn-secundario" href="ver_documento.php?id=<?= $id_doc ?>" target="_blank"><?= icono('documento') ?> Abrir documento</a>
                    <?php else: ?>
                        <span class="texto-suave chico">Este documento no tiene un archivo adjunto registrado.</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2><?= $revision ? 'Editar revisión' : 'Revisión' ?></h2>
            <?php foreach ($errores as $er): ?><div class="alerta alerta-error"><?= e($er) ?></div><?php endforeach; ?>
            <form method="post" class="formulario">
                <?= csrf_campo() ?>
                <input type="hidden" name="editar" value="<?= $editar ?>">
                <input type="hidden" name="doc" value="<?= $id_doc ?>">
                <input type="hidden" name="tesis" value="<?= $id_tesis ?>">
                <input type="hidden" name="fase" value="<?= $id_fase ?>">

                <label>Comentario
                    <span class="ayuda">Apreciación general sobre el trabajo entregado.</span>
                    <textarea name="comentario" placeholder="Ej: El planteamiento debe explicar con mayor claridad la problemática."><?= e($datos['comentario']) ?></textarea>
                </label>
                <label>Corrección
                    <span class="ayuda">Cambio concreto que el grupo debe realizar. Obligatoria si el estado es «Requiere correcciones».</span>
                    <textarea name="observacion" placeholder="Ej: Reformular el segundo párrafo."><?= e($datos['observacion']) ?></textarea>
                </label>
                <label>Recomendación
                    <textarea name="recomendacion" placeholder="Ej: Apoyarse en al menos dos fuentes recientes."><?= e($datos['recomendacion']) ?></textarea>
                </label>
                <div class="fila-campos">
                    <label>Estado de la revisión
                        <select name="estado" required>
                            <?php foreach (ESTADOS_REVISION as $valor => $etiqueta): ?>
                                <option value="<?= e($valor) ?>" <?= $datos['estado'] === $valor ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Calificación (opcional)
                        <input type="number" name="calificacion" min="0" max="5" step="0.1" placeholder="0.0 – 5.0" value="<?= e($datos['calificacion']) ?>">
                    </label>
                </div>
                <div class="botones">
                    <button class="btn" type="submit">Guardar revisión</button>
                    <a class="btn btn-secundario" href="<?= $id_fase ? "fase.php?tesis=$id_tesis&fase=$id_fase" : "proyecto.php?id=$id_tesis" ?>">Cancelar</a>
                </div>
            </form>
        </section>
    </div>

    <aside>
        <section class="card">
            <h3>Revisiones anteriores</h3>
            <?php if (!$anteriores): ?>
                <p class="texto-suave chico">Es la primera revisión de <?= $documento ? 'este documento' : 'esta fase' ?>.</p>
            <?php else: foreach ($anteriores as $r): ?>
                <div class="revision-item">
                    <div class="cabecera"><span class="chico texto-suave"><?= fecha_hora($r['fecha']) ?></span><?= badge($r['estado']) ?></div>
                    <?php if ($r['comentario']): ?><p class="chico"><strong>Comentario:</strong> <?= e($r['comentario']) ?></p><?php endif; ?>
                    <?php if ($r['observacion'] !== ''): ?><p class="chico"><strong>Corrección:</strong> <?= e($r['observacion']) ?></p><?php endif; ?>
                    <?php if ($r['recomendacion']): ?><p class="chico"><strong>Recomendación:</strong> <?= e($r['recomendacion']) ?></p><?php endif; ?>
                    <span class="texto-suave chico"><?= e($r['nombre'] . ' ' . $r['apellido']) ?></span>
                    <?php if ((int)$r['id_autor'] === $doc): ?>
                        · <a class="chico" href="revision.php?editar=<?= (int)$r['id_correccion'] ?>">Editar</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
        </section>
        <?php if ($fase && $fase['descripcion']): ?>
        <section class="card card-suave">
            <h3>Sobre la fase</h3>
            <p class="chico"><?= e($fase['descripcion']) ?></p>
        </section>
        <?php endif; ?>
    </aside>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
