<?php
require_once __DIR__ . '/../includes/docente_init.php';

$id_tesis = entero($_GET['tesis'] ?? 0);
$id_fase  = entero($_GET['fase'] ?? 0);

$tesis = null;
$fases = [];
if ($id_tesis) {
    $tesis = obtener_tesis($pdo, $doc, $id_tesis);
    if (!$tesis) {
        denegar('Este proyecto no pertenece a tus grupos asignados.');
    }
    $fases = fases_de_tesis($pdo, $id_tesis, (int)$tesis['id_curso']);
    if ($id_fase && !in_array($id_fase, array_map('intval', array_column($fases, 'id_fase')), true)) {
        $id_fase = 0;
    }
    $ids = [$id_tesis];
} else {
    $ids = ids_tesis_docente($pdo, $doc);
}

$historial = historial_revisiones($pdo, $ids, $id_fase ?: null);

// Lista de proyectos para el selector
$todos = ids_tesis_docente($pdo, $doc);
$in = marcadores($todos);
$st = $pdo->prepare("SELECT t.id_tesis, t.titulo, g.nombre_grupo FROM tesis t LEFT JOIN grupos g ON g.id_grupo = t.id_grupo
                      WHERE t.id_tesis IN ($in) ORDER BY g.nombre_grupo, t.titulo");
$st->execute($todos);
$proyectos = $st->fetchAll();

$titulo_pagina = 'Historial de revisiones';
$pagina_activa = 'revisiones';
require __DIR__ . '/../includes/header.php';
?>

<div class="migas">
    <a href="revisiones.php">Revisiones</a> /
    <?php if ($tesis): ?><a href="proyecto.php?id=<?= $id_tesis ?>"><?= e($tesis['titulo']) ?></a> / <?php endif; ?>
    Historial
</div>

<section class="card card-suave">
    <form method="get" class="form-linea">
        <label class="campo">Proyecto
            <select name="tesis">
                <option value="0">Todos mis proyectos</option>
                <?php foreach ($proyectos as $p): ?>
                    <option value="<?= (int)$p['id_tesis'] ?>" <?= (int)$p['id_tesis'] === $id_tesis ? 'selected' : '' ?>>
                        <?= e(($p['nombre_grupo'] ?? 'Sin grupo') . ' · ' . $p['titulo']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php if ($fases): ?>
        <label class="campo">Fase
            <select name="fase">
                <option value="0">Todas</option>
                <?php foreach ($fases as $f): ?>
                    <option value="<?= (int)$f['id_fase'] ?>" <?= (int)$f['id_fase'] === $id_fase ? 'selected' : '' ?>>
                        Fase <?= (int)$f['orden'] ?> — <?= e($f['nombre_fase']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php endif; ?>
        <button class="btn">Filtrar</button>
    </form>
</section>

<section class="card">
    <?php if (!$historial): ?>
        <p class="vacio">No hay revisiones ni comentarios registrados.</p>
    <?php else: ?>
    <div class="tabla-contenedor">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th><?php if (!$tesis): ?><th>Proyecto</th><?php endif; ?><th>Fase</th><th>Documento</th>
                    <th>Comentario</th><th>Corrección</th><th>Estado</th><th>Realizada por</th><th class="acciones"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($historial as $h): ?>
                <tr>
                    <td class="chico"><?= fecha_hora($h['fecha']) ?></td>
                    <?php if (!$tesis): ?><td><a href="proyecto.php?id=<?= (int)$h['id_tesis'] ?>"><?= e($h['titulo']) ?></a></td><?php endif; ?>
                    <td><?= $h['nombre_fase'] ? e($h['nombre_fase']) : '<span class="texto-suave">General</span>' ?></td>
                    <td><?= $h['nombre_documento'] ? e($h['nombre_documento']) : '<span class="texto-suave">—</span>' ?></td>
                    <td><?= $h['comentario'] ? nl2br(e($h['comentario'])) : '<span class="texto-suave">—</span>' ?></td>
                    <td>
                        <?= $h['observacion'] ? nl2br(e($h['observacion'])) : '<span class="texto-suave">—</span>' ?>
                        <?php if ($h['recomendacion']): ?><br><span class="chico texto-suave">Recomendación: <?= e($h['recomendacion']) ?></span><?php endif; ?>
                        <?php if ($h['calificacion'] !== null): ?><br><span class="chico texto-suave">Calificación: <?= e($h['calificacion']) ?></span><?php endif; ?>
                    </td>
                    <td><?= $h['tipo'] === 'revision' ? badge($h['estado']) : '<span class="badge b-neutro">Comentario</span>' ?></td>
                    <td><?= e($h['nombre'] . ' ' . $h['apellido']) ?><br>
                        <span class="texto-suave chico"><?= e($h['rol'] === 'profesor' ? 'Docente' : ucfirst($h['rol'])) ?></span></td>
                    <td class="acciones">
                        <?php if ($h['tipo'] === 'revision' && (int)$h['id_autor'] === $doc): ?>
                            <a class="btn btn-secundario btn-chico" href="revision.php?editar=<?= (int)$h['id_correccion'] ?>">Editar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
