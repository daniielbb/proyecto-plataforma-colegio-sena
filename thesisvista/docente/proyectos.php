<?php
require_once __DIR__ . '/../includes/docente_init.php';

$ids = ids_tesis_docente($pdo, $doc);
$in  = marcadores($ids);

$filtro = $_GET['estado'] ?? '';
$sql = "SELECT t.id_tesis, t.titulo, t.estado, t.fecha_registro, t.id_profesor,
               ue.nombre AS est_nombre, ue.apellido AS est_apellido,
               up.nombre AS prof_nombre, up.apellido AS prof_apellido,
               g.id_grupo, g.nombre_grupo,
               COALESCE(g.id_curso, (SELECT dc.id_curso FROM docente_curso dc WHERE dc.id_profesor = t.id_profesor
                                      ORDER BY dc.id_curso LIMIT 1)) AS id_curso
          FROM tesis t
          JOIN usuarios ue ON ue.usuario_id = t.id_estudiante
          JOIN usuarios up ON up.usuario_id = t.id_profesor
          LEFT JOIN grupos g ON g.id_grupo = t.id_grupo
         WHERE t.id_tesis IN ($in)";
$params = $ids;
if (in_array($filtro, ESTADOS_TESIS, true)) {
    $sql .= ' AND t.estado = ?';
    $params[] = $filtro;
}
$sql .= ' ORDER BY g.nombre_grupo, t.titulo';
$st = $pdo->prepare($sql);
$st->execute($params);
$proyectos = $st->fetchAll();
foreach ($proyectos as &$p) {
    $p['progreso'] = resumen_progreso(fases_de_tesis($pdo, (int)$p['id_tesis'], (int)$p['id_curso']));
}
unset($p);

$titulo_pagina = 'Proyectos';
$pagina_activa = 'proyectos';
require __DIR__ . '/../includes/header.php';
?>

<nav class="filtros">
    <a href="proyectos.php" class="<?= $filtro === '' ? 'activo' : '' ?>">Todos</a>
    <?php foreach (ESTADOS_TESIS as $es): ?>
        <a href="?estado=<?= urlencode($es) ?>" class="<?= $filtro === $es ? 'activo' : '' ?>"><?= e($es) ?></a>
    <?php endforeach; ?>
</nav>

<section class="card">
    <?php if (!$proyectos): ?>
        <p class="vacio">No hay proyectos para mostrar.</p>
    <?php else: ?>
    <div class="tabla-contenedor">
        <table>
            <thead>
                <tr><th>Proyecto</th><th>Grupo</th><th>Docente asignado</th><th>Fase actual</th><th style="min-width:160px">Progreso</th><th>Estado</th><th class="acciones"></th></tr>
            </thead>
            <tbody>
            <?php foreach ($proyectos as $p): ?>
                <tr>
                    <td><strong><?= e($p['titulo']) ?></strong><br>
                        <span class="texto-suave chico">Registrado por <?= e($p['est_nombre'] . ' ' . $p['est_apellido']) ?> · <?= fecha($p['fecha_registro']) ?></span></td>
                    <td><?php if ($p['id_grupo'] && puede_ver_grupo($pdo, $doc, (int)$p['id_grupo'])): ?>
                            <a href="grupo.php?id=<?= (int)$p['id_grupo'] ?>"><?= e($p['nombre_grupo']) ?></a>
                        <?php else: ?><?= e($p['nombre_grupo'] ?? 'Sin grupo') ?><?php endif; ?></td>
                    <td><?= e($p['prof_nombre'] . ' ' . $p['prof_apellido']) ?><?= (int)$p['id_profesor'] === $doc ? ' <span class="badge b-neutro">Tú</span>' : '' ?></td>
                    <td><?= $p['progreso']['actual'] ? e($p['progreso']['actual']['nombre_fase']) : '<span class="texto-suave">Completado</span>' ?></td>
                    <td><div class="progreso-fila"><?= barra_progreso($p['progreso']['porcentaje']) ?><strong><?= $p['progreso']['porcentaje'] ?>%</strong></div></td>
                    <td><?= badge($p['estado']) ?></td>
                    <td class="acciones"><a class="btn btn-chico" href="proyecto.php?id=<?= (int)$p['id_tesis'] ?>">Ver proyecto</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
