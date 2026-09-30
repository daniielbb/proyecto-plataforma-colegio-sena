<?php
require_once __DIR__ . '/../includes/docente_init.php';

$ids = ids_tesis_docente($pdo, $doc);
$in  = marcadores($ids);

$filtros = [
    'pendientes' => ['Por revisar', ['Entregado', 'Corregido', 'En revisión']],
    'Entregado'  => ['Pendiente de revisión', ['Entregado']],
    'En revisión'=> ['En revisión', ['En revisión']],
    'Requiere ajustes' => ['Requiere correcciones', ['Requiere ajustes']],
    'Corregido'  => ['Corregido', ['Corregido']],
    'Aprobado'   => ['Aprobado', ['Aprobado']],
    'todos'      => ['Todos', array_keys(ESTADOS_DOCUMENTO)],
];
$f = $_GET['f'] ?? 'pendientes';
if (!isset($filtros[$f])) {
    $f = 'pendientes';
}
$estados = $filtros[$f][1];
$in_est  = implode(',', array_fill(0, count($estados), '?'));

$st = $pdo->prepare(
    "SELECT d.*, t.titulo, g.id_grupo, g.nombre_grupo, f.nombre_fase, f.orden,
            (SELECT MAX(r.fecha) FROM correcciones r WHERE r.id_documento = d.id_documento) AS ultima_revision
       FROM documento d
       JOIN tesis t ON t.id_tesis = d.id_tesis
       LEFT JOIN grupos g ON g.id_grupo = t.id_grupo
       LEFT JOIN fases f ON f.id_fase = d.id_fase
      WHERE d.id_tesis IN ($in) AND d.estado IN ($in_est)
      ORDER BY FIELD(d.estado, 'Corregido', 'Entregado', 'En revisión', 'Requiere ajustes', 'Aprobado'),
               COALESCE(d.fecha_modificacion, d.fecha_subida) DESC");
$st->execute(array_merge($ids, $estados));
$documentos = $st->fetchAll();

$titulo_pagina = 'Revisiones';
$pagina_activa = 'revisiones';
require __DIR__ . '/../includes/header.php';
?>

<div class="cabecera-detalle" style="margin-bottom:1rem">
    <p class="texto-suave" style="margin:0">Documentos entregados por tus grupos. Entra a cada uno para registrar la revisión.</p>
    <a class="btn btn-secundario" href="historial.php"><?= icono('historial') ?> Historial de revisiones</a>
</div>

<nav class="filtros">
    <?php foreach ($filtros as $clave => [$texto]): ?>
        <a href="?f=<?= urlencode($clave) ?>" class="<?= $f === $clave ? 'activo' : '' ?>"><?= e($texto) ?></a>
    <?php endforeach; ?>
</nav>

<section class="card">
    <?php if (!$documentos): ?>
        <p class="vacio">No hay documentos en este estado.</p>
    <?php else: ?>
    <div class="tabla-contenedor">
        <table>
            <thead>
                <tr><th>Documento</th><th>Grupo</th><th>Fase</th><th>Fecha de envío</th><th>Última modificación</th><th>Estado</th><th class="acciones"></th></tr>
            </thead>
            <tbody>
            <?php foreach ($documentos as $d): ?>
                <tr>
                    <td><strong><?= e($d['nombre_documento']) ?></strong><br>
                        <span class="texto-suave chico"><?= e($d['titulo']) ?> · <?= e($d['tipo_documento']) ?></span></td>
                    <td><?= e($d['nombre_grupo'] ?? 'Sin grupo') ?></td>
                    <td><?= $d['nombre_fase'] ? 'Fase ' . (int)$d['orden'] . ' — ' . e($d['nombre_fase']) : '<span class="texto-suave">Sin fase</span>' ?></td>
                    <td><?= fecha($d['fecha_subida']) ?></td>
                    <td><?= fecha_hora($d['fecha_modificacion']) ?></td>
                    <td><?= badge($d['estado']) ?>
                        <?php if ($d['ultima_revision']): ?><br><span class="texto-suave chico">Revisado <?= fecha($d['ultima_revision']) ?></span><?php endif; ?></td>
                    <td class="acciones"><a class="btn btn-chico" href="revision.php?doc=<?= (int)$d['id_documento'] ?>">Revisar documento</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
