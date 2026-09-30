<?php
require_once __DIR__ . '/../includes/docente_init.php';

$ids_grupos = ids_grupos_docente($pdo, $doc);
$ids_tesis  = ids_tesis_docente($pdo, $doc);
$hoy        = date('Y-m-d');
$en7dias    = date('Y-m-d', strtotime('+7 days'));

$in = marcadores($ids_tesis);

// Proyectos en proceso (no aprobados ni rechazados)
$st = $pdo->prepare("SELECT COUNT(*) FROM tesis WHERE estado IN ('Borrador','En revisión') AND id_tesis IN ($in)");
$st->execute($ids_tesis);
$en_proceso = (int)$st->fetchColumn();

// Proyectos con documentos que requieren revisión del docente
$st = $pdo->prepare("SELECT COUNT(DISTINCT id_tesis) FROM documento
                      WHERE estado IN ('Entregado','Corregido','En revisión') AND id_tesis IN ($in)");
$st->execute($ids_tesis);
$requieren_revision = (int)$st->fetchColumn();

// Correcciones pendientes
$pendientes = correcciones_pendientes($pdo, $doc);

// Fases próximas a entregar (fecha límite en los próximos 7 días) y vencidas
$st = $pdo->prepare(
    "SELECT tf.id_tesis, tf.id_fase, tf.estado, tf.fecha_limite, f.nombre_fase, f.orden,
            t.titulo, g.nombre_grupo
       FROM tesis_fase tf
       JOIN fases f ON f.id_fase = tf.id_fase
       JOIN tesis t ON t.id_tesis = tf.id_tesis
       LEFT JOIN grupos g ON g.id_grupo = t.id_grupo
      WHERE tf.estado <> 'Completada' AND tf.fecha_limite IS NOT NULL
        AND tf.fecha_limite <= ? AND tf.id_tesis IN ($in)
      ORDER BY tf.fecha_limite");
$st->execute(array_merge([$en7dias], $ids_tesis));
$entregas = $st->fetchAll();
$proximas = array_filter($entregas, fn($e) => $e['fecha_limite'] >= $hoy);

// Documentos por revisar
$st = $pdo->prepare(
    "SELECT d.id_documento, d.nombre_documento, d.estado, d.fecha_subida, d.fecha_modificacion,
            t.titulo, g.nombre_grupo, f.nombre_fase
       FROM documento d
       JOIN tesis t ON t.id_tesis = d.id_tesis
       LEFT JOIN grupos g ON g.id_grupo = t.id_grupo
       LEFT JOIN fases f ON f.id_fase = d.id_fase
      WHERE d.estado IN ('Entregado','Corregido','En revisión') AND d.id_tesis IN ($in)
      ORDER BY COALESCE(d.fecha_modificacion, d.fecha_subida) DESC
      LIMIT 6");
$st->execute($ids_tesis);
$por_revisar = $st->fetchAll();

$actividad = ultimas_actividades($pdo, ids_tesis_docente($pdo, $doc), 8);

$titulo_pagina = 'Inicio';
$pagina_activa = 'inicio';
require __DIR__ . '/../includes/header.php';
?>

<section class="saludo">
    <h2>Hola, <?= e($docente['nombre'] . ' ' . $docente['apellido']) ?></h2>
    <p>Estos son los proyectos y grupos que tienes asignados.</p>
</section>

<section class="estadisticas">
    <a class="estadistica" href="grupos.php">
        <div class="numero"><?= count($ids_grupos) ?></div>
        <div class="rotulo">Grupos asignados</div>
    </a>
    <a class="estadistica" href="proyectos.php">
        <div class="numero"><?= $en_proceso ?></div>
        <div class="rotulo">Proyectos en proceso</div>
    </a>
    <a class="estadistica aviso" href="revisiones.php">
        <div class="numero"><?= $requieren_revision ?></div>
        <div class="rotulo">Proyectos que requieren revisión</div>
    </a>
    <a class="estadistica alerta" href="correcciones.php">
        <div class="numero"><?= count($pendientes) ?></div>
        <div class="rotulo">Correcciones pendientes</div>
    </a>
    <div class="estadistica">
        <div class="numero"><?= count($proximas) ?></div>
        <div class="rotulo">Fases por entregar (7 días)</div>
    </div>
</section>

<div class="dos-col">
    <div>
        <section class="card">
            <div class="card-titulo">
                <h2>Documentos por revisar</h2>
                <a href="revisiones.php" class="chico">Ver todos</a>
            </div>
            <?php if (!$por_revisar): ?>
                <p class="texto-suave">No hay documentos pendientes de revisión.</p>
            <?php else: ?>
            <div class="tabla-contenedor">
                <table>
                    <thead><tr><th>Documento</th><th>Grupo / Proyecto</th><th>Fase</th><th>Estado</th><th class="acciones"></th></tr></thead>
                    <tbody>
                    <?php foreach ($por_revisar as $d): ?>
                        <tr>
                            <td><strong><?= e($d['nombre_documento']) ?></strong><br>
                                <span class="texto-suave chico">Enviado <?= fecha($d['fecha_subida']) ?></span></td>
                            <td><?= e($d['nombre_grupo'] ?? 'Sin grupo') ?><br><span class="texto-suave chico"><?= e($d['titulo']) ?></span></td>
                            <td><?= e($d['nombre_fase'] ?? 'Sin fase') ?></td>
                            <td><?= badge($d['estado']) ?></td>
                            <td class="acciones"><a class="btn btn-chico" href="revision.php?doc=<?= (int)$d['id_documento'] ?>">Revisar</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Actividad reciente</h2>
            <?php if (!$actividad): ?>
                <p class="texto-suave">Aún no hay actividad registrada.</p>
            <?php else: ?>
            <ul class="lista-actividad">
                <?php foreach ($actividad as $a): ?>
                <li>
                    <span class="punto <?= e($a['tipo']) ?>"></span>
                    <div>
                        <strong><?= e($a['texto']) ?></strong>
                        <?php if ($a['nombre_fase']): ?> · <?= e($a['nombre_fase']) ?><?php endif; ?><br>
                        <a class="chico" href="proyecto.php?id=<?= (int)$a['id_tesis'] ?>"><?= e($a['titulo']) ?></a>
                        <span class="texto-suave chico"> · <?= fecha_hora($a['fecha']) ?></span>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </section>
    </div>

    <aside>
        <section class="card">
            <h2>Próximas entregas</h2>
            <?php if (!$entregas): ?>
                <p class="texto-suave">No hay fases con fecha límite en los próximos 7 días.</p>
            <?php else: ?>
            <ul class="lista-actividad">
                <?php foreach ($entregas as $en): $vencida = $en['fecha_limite'] < $hoy; ?>
                <li>
                    <span class="punto <?= $vencida ? 'vencida' : 'fase' ?>"></span>
                    <div>
                        <a href="fase.php?tesis=<?= (int)$en['id_tesis'] ?>&fase=<?= (int)$en['id_fase'] ?>">
                            <strong>Fase <?= (int)$en['orden'] ?> — <?= e($en['nombre_fase']) ?></strong></a><br>
                        <span class="chico"><?= e($en['nombre_grupo'] ?? 'Sin grupo') ?> · <?= e($en['titulo']) ?></span><br>
                        <span class="chico <?= $vencida ? 'texto-vencido' : 'texto-suave' ?>">
                            <?= $vencida ? 'Venció el' : 'Entrega' ?> <?= fecha($en['fecha_limite']) ?>
                        </span>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </section>

        <section class="card card-suave">
            <h3>Correcciones pendientes</h3>
            <?php if (!$pendientes): ?>
                <p class="texto-suave chico">Ningún grupo tiene correcciones pendientes.</p>
            <?php else: ?>
                <?php foreach (array_slice($pendientes, 0, 4) as $p): ?>
                    <p class="chico"><strong><?= e($p['nombre_grupo'] ?? 'Sin grupo') ?></strong> ·
                        <?= e($p['nombre_fase'] ?? 'Sin fase') ?><br>
                        <span class="texto-suave">Última revisión: <?= fecha($p['ultima_revision']) ?></span></p>
                <?php endforeach; ?>
                <a class="btn btn-secundario btn-chico" href="correcciones.php">Ver correcciones</a>
            <?php endif; ?>
        </section>
    </aside>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
