<?php

require_once __DIR__ . '/../includes/seguridad.php';
require_once __DIR__ . '/../includes/funciones_admin.php';

$admin = requerir_rol('administrador');
$pdo   = conectar();

$id    = (int) ($_GET['id'] ?? 0);
$tesis = buscar_tesis($id);
if (!$tesis) {
    mensaje('error', 'El proyecto solicitado no existe.');
    redirigir('proyectos.php');
}


$stmt = $pdo->prepare('SELECT total_fases, fases_completadas, porcentaje_avance FROM vista_progreso_tesis WHERE id_tesis = ?');
$stmt->execute([$id]);
$progreso   = $stmt->fetch() ?: ['total_fases' => 0, 'fases_completadas' => 0, 'porcentaje_avance' => 0];
$porcentaje = (float) ($progreso['porcentaje_avance'] ?? 0);


$stmt = $pdo->prepare('SELECT f.orden, f.nombre_fase, tf.estado, tf.fecha_inicio, tf.fecha_limite, tf.fecha_completada
                       FROM tesis_fase tf JOIN fases f ON f.id_fase = tf.id_fase
                       WHERE tf.id_tesis = ? ORDER BY f.orden');
$stmt->execute([$id]);
$fases = $stmt->fetchAll();


$stmt = $pdo->prepare('SELECT nombre_documento, tipo_documento, fecha_subida FROM documento
                       WHERE id_tesis = ? ORDER BY fecha_subida DESC, id_documento DESC');
$stmt->execute([$id]);
$documentos = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT c.comentario, c.fecha, u.nombre, u.apellido, u.rol, f.nombre_fase
                       FROM comentarios c
                       JOIN usuarios u ON u.usuario_id = c.id_usuario
                       LEFT JOIN fases f ON f.id_fase = c.id_fase
                       WHERE c.id_tesis = ? ORDER BY c.fecha DESC');
$stmt->execute([$id]);
$comentarios = $stmt->fetchAll();

// Todos los estudiantes del grupo participan en el proyecto
$integrantes = $tesis['id_grupo'] !== null ? estudiantes_de_grupo((int) $tesis['id_grupo']) : [];

$clase_fase = ['Pendiente' => 'etiqueta-gris', 'En progreso' => 'etiqueta-ambar',
               'Completada' => 'etiqueta-verde', 'Atrasada' => 'etiqueta-roja'];

$titulo  = 'Consultar proyecto';
$seccion = 'proyectos';
require __DIR__ . '/../includes/header.php';
?>

<div class="tarjeta">
    <div class="tarjeta-cabecera">
        <h2><?= e($tesis['titulo']) ?></h2>
        <div class="acciones">
            <a class="btn btn-secundario" href="editar_proyecto.php?id=<?= $id ?>">Editar</a>
            <a class="btn btn-secundario" href="asignar_profesor.php?id=<?= $id ?>">Asignar docente</a>
            <a class="btn btn-peligro" href="eliminar_proyecto.php?id=<?= $id ?>">Eliminar</a>
        </div>
    </div>

    <dl class="detalle">
        <dt>ID</dt>               <dd><?= $id ?></dd>
        <dt>Estado</dt>           <dd><span class="etiqueta <?= clase_estado($tesis['estado']) ?>"><?= e($tesis['estado']) ?></span></dd>
        <dt>Fecha de registro</dt><dd><?= e($tesis['fecha_registro']) ?></dd>
        <dt>Responsable</dt>      <dd><?= e($tesis['est_nombre'] . ' ' . $tesis['est_apellido']) ?> <small>· <?= e($tesis['est_correo']) ?></small></dd>
        <?php if ($integrantes): ?>
        <dt>Integrantes (<?= count($integrantes) ?>)</dt>
        <dd><?php foreach ($integrantes as $i): ?>
                <?= e($i['nombre'] . ' ' . $i['apellido']) ?> <small>· <?= e($i['correo']) ?></small><br>
            <?php endforeach; ?></dd>
        <?php endif; ?>
        <dt>Docente</dt>          <dd><?= e($tesis['prof_nombre'] . ' ' . $tesis['prof_apellido']) ?> <small>· <?= e($tesis['prof_correo']) ?></small></dd>
        <dt>Grupo / curso</dt>
        <dd><?php if ($tesis['nombre_grupo'] !== null): ?>
                <a href="ver_grupo.php?id=<?= (int) $tesis['id_grupo'] ?>"><?= e($tesis['nombre_grupo']) ?></a>
                <?= e(' · ' . "grado " . $tesis['nombre_curso'] ) ?>
            <?php else: ?>
                Sin grupo asignado
            <?php endif; ?></dd>
        <dt>Resumen</dt>          <dd><?= nl2br(e($tesis['resumen'])) ?></dd>
    </dl>
</div>

<div class="rejilla-2">
    <div class="tarjeta">
        <h2>Avance por fases</h2>
        <div class="barra"><span style="width: <?= $porcentaje ?>%"></span></div>
        <p><strong><?= $porcentaje ?> %</strong> · <?= (int) $progreso['fases_completadas'] ?> de <?= (int) $progreso['total_fases'] ?> fases completadas</p>

        <?php if ($fases): ?>
            <div class="tabla-contenedor">
                <table>
                    <thead><tr><th>#</th><th>Fase</th><th>Estado</th><th>Fecha límite</th></tr></thead>
                    <tbody>
                    <?php foreach ($fases as $f): ?>
                        <tr>
                            <td><?= (int) $f['orden'] ?></td>
                            <td><?= e($f['nombre_fase']) ?></td>
                            <td><span class="etiqueta <?= $clase_fase[$f['estado']] ?? 'etiqueta-gris' ?>"><?= e($f['estado']) ?></span></td>
                            <td><?= e($f['fecha_limite'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="vacio">Este proyecto no tiene fases registradas. Puede crearlas desde <a href="editar_proyecto.php?id=<?= $id ?>">Editar</a> (requiere grupo).</p>
        <?php endif; ?>
    </div>

    <div>
        <div class="tarjeta">
            <h2>Documentos (<?= count($documentos) ?>)</h2>
            <ul class="lista-simple">
                <?php foreach ($documentos as $d): ?>
                    <li><strong><?= e($d['nombre_documento']) ?></strong> · <?= e($d['tipo_documento']) ?><br>
                        <small>Subido: <?= e($d['fecha_subida']) ?></small></li>
                <?php endforeach; ?>
                <?php if (!$documentos): ?><li class="vacio">Sin documentos.</li><?php endif; ?>
            </ul>
        </div>

        <div class="tarjeta">
            <h2>Comentarios (<?= count($comentarios) ?>)</h2>
            <ul class="lista-simple">
                <?php foreach ($comentarios as $c): ?>
                    <li><?= nl2br(e($c['comentario'])) ?><br>
                        <small><?= e($c['nombre'] . ' ' . $c['apellido']) ?> (<?= e(nombre_rol($c['rol'])) ?>)
                            · <?= e($c['fecha']) ?><?= $c['nombre_fase'] ? ' · ' . e($c['nombre_fase']) : '' ?></small></li>
                <?php endforeach; ?>
                <?php if (!$comentarios): ?><li class="vacio">Sin comentarios.</li><?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<a href="proyectos.php" class="btn btn-secundario">← Volver a proyectos</a>

<?php require __DIR__ . '/../includes/footer.php'; ?>