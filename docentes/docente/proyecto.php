<?php
require_once __DIR__ . '/../includes/docente_init.php';

$id_tesis = entero($_GET['id'] ?? $_POST['id_tesis'] ?? 0);
$tesis = obtener_tesis($pdo, $doc, $id_tesis);
if (!$tesis) {
    denegar('Este proyecto no pertenece a tus grupos asignados.');
}
$fases = fases_de_tesis($pdo, $id_tesis, (int)$tesis['id_curso']);

/* ------------------------------------------------------------------
   Acciones
   ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = $_POST['accion'] ?? '';
    $hoy = date('Y-m-d');

    if ($accion === 'siguiente_fase') {
        $id_nueva   = entero($_POST['id_fase'] ?? 0);   // 0 = solo completar la fase actual
        $completar  = !empty($_POST['completar_actual']);
        $por_id     = array_column($fases, null, 'id_fase');

        if ($id_nueva && !isset($por_id[$id_nueva])) {
            flash('error', 'La fase seleccionada no pertenece al curso de este proyecto.');
        } elseif ($id_nueva && in_array($por_id[$id_nueva]['estado'], ['En progreso', 'Atrasada'], true)) {
            flash('info', 'Esa fase ya es la fase actual del proyecto.');
        } else {
            $pdo->beginTransaction();
            try {
                // 1) Garantiza un registro en tesis_fase por cada fase activa del curso
                $ins = $pdo->prepare("INSERT IGNORE INTO tesis_fase (id_tesis, id_fase, estado) VALUES (?, ?, 'Pendiente')");
                foreach ($fases as $f) {
                    $ins->execute([$id_tesis, $f['id_fase']]);
                }
                // 2) Cierra la(s) fase(s) actual(es)
                $cerrar = $completar
                    ? $pdo->prepare("UPDATE tesis_fase SET estado = 'Completada', fecha_completada = ?
                                      WHERE id_tesis = ? AND estado IN ('En progreso','Atrasada') AND id_fase <> ?")
                    : $pdo->prepare("UPDATE tesis_fase SET estado = 'Pendiente', fecha_completada = NULL
                                      WHERE id_tesis = ? AND estado IN ('En progreso','Atrasada') AND id_fase <> ?");
                $completar ? $cerrar->execute([$hoy, $id_tesis, $id_nueva]) : $cerrar->execute([$id_tesis, $id_nueva]);

                // 3) Abre la nueva fase
                if ($id_nueva) {
                    $dias   = $por_id[$id_nueva]['duracion_dias'];
                    $limite = $dias ? date('Y-m-d', strtotime("+$dias days")) : null;
                    $pdo->prepare("UPDATE tesis_fase SET estado = 'En progreso', fecha_inicio = ?, fecha_limite = ?, fecha_completada = NULL
                                    WHERE id_tesis = ? AND id_fase = ?")
                        ->execute([$hoy, $limite, $id_tesis, $id_nueva]);
                }
                $pdo->commit();
                flash('ok', $id_nueva
                    ? 'Fase actual actualizada a "' . $por_id[$id_nueva]['nombre_fase'] . '".'
                    : 'Se marcó la fase actual como completada.');
            } catch (Throwable $ex) {
                $pdo->rollBack();
                flash('error', 'No fue posible actualizar la fase.');
            }
        }
    }

    if ($accion === 'estado_proyecto') {
        $estado = $_POST['estado'] ?? '';
        if (in_array($estado, ESTADOS_TESIS, true)) {
            $pdo->prepare('UPDATE tesis SET estado = ? WHERE id_tesis = ?')->execute([$estado, $id_tesis]);
            flash('ok', 'Estado del proyecto actualizado a "' . $estado . '".');
        }
    }
    redirigir('docente/proyecto.php?id=' . $id_tesis);
}

$pr         = resumen_progreso($fases);
$documentos = documentos_de_tesis($pdo, $id_tesis);
$actividad  = ultimas_actividades($pdo, [$id_tesis], 8);
$hoy        = date('Y-m-d');

$titulo_pagina = 'Proyecto';
$pagina_activa = 'proyectos';
require __DIR__ . '/../includes/header.php';
?>

<div class="migas">
    <a href="proyectos.php">Proyectos</a> /
    <?php if ($tesis['id_grupo'] && puede_ver_grupo($pdo, $doc, (int)$tesis['id_grupo'])): ?>
        <a href="grupo.php?id=<?= (int)$tesis['id_grupo'] ?>"><?= e($tesis['nombre_grupo']) ?></a> /
    <?php endif; ?>
    <?= e($tesis['titulo']) ?>
</div>

<section class="card">
    <div class="cabecera-detalle">
        <div style="flex:1;min-width:260px">
            <div class="etiqueta-superior"><?= e($tesis['nombre_grupo'] ?? 'Sin grupo') ?></div>
            <h2><?= e($tesis['titulo']) ?></h2>
            <p class="texto-suave"><?= e($tesis['resumen']) ?></p>
        </div>
        <dl class="ficha">
            <dt>Estado</dt><dd><?= badge($tesis['estado']) ?></dd>
            <dt>Registrado por</dt><dd><?= e($tesis['est_nombre'] . ' ' . $tesis['est_apellido']) ?></dd>
            <dt>Docente asignado</dt><dd><?= e($tesis['prof_nombre'] . ' ' . $tesis['prof_apellido']) ?></dd>
            <dt>Curso</dt><dd><?= e($tesis['nombre_curso'] ?? '—') ?></dd>
            <dt>Fecha de registro</dt><dd><?= fecha($tesis['fecha_registro']) ?></dd>
        </dl>
    </div>
    <div class="botones" style="margin-top:1rem">
        <a class="btn btn-secundario" href="historial.php?tesis=<?= $id_tesis ?>"><?= icono('historial') ?> Historial de revisiones</a>
    </div>
</section>

<div class="dos-col">
    <div>
        <section class="card">
            <div class="etiqueta-superior">Progreso del proyecto</div>
            <div class="progreso-fila">
                <?= barra_progreso($pr['porcentaje'], true) ?>
                <span class="porcentaje-grande"><?= $pr['porcentaje'] ?>%</span>
            </div>
            <p class="texto-suave chico" style="margin-top:.5rem">
                <?= $pr['completadas'] ?> de <?= $pr['total'] ?> fases completadas ·
                Fase actual: <strong><?= $pr['actual'] ? e($pr['actual']['nombre_fase']) : 'Todas completadas' ?></strong>
            </p>
            <ul class="pasos">
                <?php foreach ($fases as $f): $cls = strtolower(str_replace(' ', '-', $f['estado'])); ?>
                <li class="<?= e($cls) ?>">
                    <span class="simbolo"><?= simbolo_fase($f['estado']) ?></span>
                    <span class="nombre">Fase <?= (int)$f['orden'] ?> — <?= e($f['nombre_fase']) ?></span>
                    <span class="fecha"><?= e($f['estado']) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="card">
            <h2>Fases del proyecto</h2>
            <?php if (!$fases): ?>
                <p class="texto-suave">El curso no tiene fases activas.</p>
            <?php else: ?>
            <div class="carpetas">
                <?php foreach ($fases as $f): $cls = strtolower(str_replace(' ', '-', $f['estado']));
                      $vencida = $f['fecha_limite'] && $f['fecha_limite'] < $hoy && $f['estado'] !== 'Completada'; ?>
                <a class="carpeta <?= e($cls) ?>" href="fase.php?tesis=<?= $id_tesis ?>&fase=<?= (int)$f['id_fase'] ?>">
                    <span class="fase-num">Fase <?= (int)$f['orden'] ?></span>
                    <span class="fase-nombre"><?= e($f['nombre_fase']) ?></span>
                    <?= simbolo_fase($f['estado']) ?> <?= badge($f['estado']) ?>
                    <div class="fase-meta">
                        <?= (int)$f['total_documentos'] ?> documento(s)
                        <?php if ($f['fecha_limite']): ?><br><span class="<?= $vencida ? 'texto-vencido' : '' ?>">Límite: <?= fecha($f['fecha_limite']) ?></span><?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Documentos</h2>
            <?php if (!$documentos): ?>
                <p class="texto-suave">El grupo aún no ha entregado documentos para este proyecto.</p>
            <?php else: ?>
            <div class="tabla-contenedor">
                <table>
                    <thead><tr><th>Documento</th><th>Fase</th><th>Envío</th><th>Estado</th><th class="acciones"></th></tr></thead>
                    <tbody>
                    <?php foreach ($documentos as $d): ?>
                        <tr>
                            <td><strong><?= e($d['nombre_documento']) ?></strong><br><span class="texto-suave chico"><?= e($d['tipo_documento']) ?></span></td>
                            <td><?= e($d['nombre_fase'] ?? 'Sin fase') ?></td>
                            <td><?= fecha($d['fecha_subida']) ?></td>
                            <td><?= badge($d['estado']) ?></td>
                            <td class="acciones"><a class="btn btn-chico" href="revision.php?doc=<?= (int)$d['id_documento'] ?>">Revisar documento</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <aside>
        <section class="card">
            <h3>Establecer siguiente fase</h3>
            <p class="texto-suave chico">Cuando el grupo haya avanzado correctamente, selecciona la fase en la que continuará.
                La nueva fase aparecerá en la interfaz del estudiante.</p>
            <form method="post" class="formulario">
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="siguiente_fase">
                <input type="hidden" name="id_tesis" value="<?= $id_tesis ?>">
                <label>Nueva fase actual
                    <select name="id_fase" required>
                        <?php
                        $sugerida = null;
                        foreach ($fases as $f) {
                            if ($pr['actual'] && $f['orden'] > $pr['actual']['orden'] && $f['estado'] !== 'Completada') { $sugerida = $f['id_fase']; break; }
                        }
                        if (!$pr['en_curso'] && $pr['actual']) { $sugerida = $pr['actual']['id_fase']; }
                        foreach ($fases as $f): ?>
                            <option value="<?= (int)$f['id_fase'] ?>" <?= (int)$f['id_fase'] === (int)$sugerida ? 'selected' : '' ?>>
                                Fase <?= (int)$f['orden'] ?> — <?= e($f['nombre_fase']) ?> (<?= e($f['estado']) ?>)
                            </option>
                        <?php endforeach; ?>
                        <option value="0" <?= $sugerida === null ? 'selected' : '' ?>>Ninguna: finalizar la fase actual</option>
                    </select>
                </label>
                <label class="check"><input type="checkbox" name="completar_actual" value="1" checked>
                    Marcar como completada la fase actual<?= $pr['en_curso'] ? ' (' . e($pr['actual']['nombre_fase']) . ')' : '' ?></label>
                <span class="ayuda texto-suave chico">La fecha límite se calcula con la duración sugerida de la fase.</span>
                <button class="btn">Guardar fase</button>
            </form>
        </section>

        <section class="card">
            <h3>Estado del proyecto</h3>
            <form method="post" class="formulario">
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="estado_proyecto">
                <input type="hidden" name="id_tesis" value="<?= $id_tesis ?>">
                <select name="estado">
                    <?php foreach (ESTADOS_TESIS as $es): ?>
                        <option <?= $tesis['estado'] === $es ? 'selected' : '' ?>><?= e($es) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-secundario">Actualizar estado</button>
            </form>
        </section>

        <section class="card">
            <h3>Últimas actividades</h3>
            <?php if (!$actividad): ?>
                <p class="texto-suave chico">Sin actividad registrada.</p>
            <?php else: ?>
            <ul class="lista-actividad">
                <?php foreach ($actividad as $a): ?>
                <li><span class="punto <?= e($a['tipo']) ?>"></span>
                    <div class="chico"><strong><?= e($a['texto']) ?></strong><?= $a['nombre_fase'] ? ' · ' . e($a['nombre_fase']) : '' ?><br>
                        <span class="texto-suave"><?= fecha_hora($a['fecha']) ?></span></div></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </section>
    </aside>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
