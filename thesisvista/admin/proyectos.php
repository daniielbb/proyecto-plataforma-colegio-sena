<?php

require_once __DIR__ . '/../includes/seguridad.php';
require_once __DIR__ . '/../includes/funciones_admin.php';

$admin = requerir_rol('administrador');


$buscar      = trim($_GET['buscar'] ?? '');
$estado      = $_GET['estado'] ?? '';
$id_profesor = (int) ($_GET['profesor'] ?? 0);
if (!in_array($estado, ESTADOS_TESIS, true)) $estado = '';

$sql = "SELECT t.id_tesis, t.titulo, t.estado, t.fecha_registro,
               e.nombre AS est_nombre, e.apellido AS est_apellido,
               p.nombre AS prof_nombre, p.apellido AS prof_apellido,
               g.nombre_grupo,
               (SELECT COUNT(*) FROM tesis_fase tf WHERE tf.id_tesis = t.id_tesis) AS total_fases,
               (SELECT COUNT(*) FROM tesis_fase tf WHERE tf.id_tesis = t.id_tesis AND tf.estado = 'Completada') AS completadas
        FROM tesis t
        JOIN usuarios e ON e.usuario_id = t.id_estudiante
        JOIN usuarios p ON p.usuario_id = t.id_profesor
        LEFT JOIN grupos g ON g.id_grupo = t.id_grupo
        WHERE 1 = 1";
$parametros = [];

if ($buscar !== '') {
    $sql .= ' AND (t.titulo LIKE ? OR e.nombre LIKE ? OR e.apellido LIKE ?)';
    $comodin = '%' . $buscar . '%';
    array_push($parametros, $comodin, $comodin, $comodin);
}
if ($estado !== '') {
    $sql .= ' AND t.estado = ?';
    $parametros[] = $estado;
}
if ($id_profesor > 0) {
    $sql .= ' AND t.id_profesor = ?';
    $parametros[] = $id_profesor;
}
$sql .= ' ORDER BY t.id_tesis DESC';

$stmt = conectar()->prepare($sql);
$stmt->execute($parametros);
$proyectos = $stmt->fetchAll();

$profesores = usuarios_por_rol('profesor');

$titulo  = 'Gestión de proyectos / tesis';
$seccion = 'proyectos';
require __DIR__ . '/../includes/header.php';
?>

<div class="tarjeta">
    <div class="tarjeta-cabecera">
        <h2>Proyectos registrados (<?= count($proyectos) ?>)</h2>
        <a href="crear_proyecto.php" class="btn btn-primario">+ Crear proyecto</a>
    </div>

    <form method="get" action="proyectos.php" class="filtros">
        <input type="text" name="buscar" value="<?= e($buscar) ?>" placeholder="Buscar por título o estudiante">
        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach (ESTADOS_TESIS as $valor): ?>
                <option value="<?= e($valor) ?>" <?= $estado === $valor ? 'selected' : '' ?>><?= e($valor) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="profesor">
            <option value="0">Todos los docentes</option>
            <?php foreach ($profesores as $p): ?>
                <option value="<?= (int) $p['usuario_id'] ?>" <?= $id_profesor === (int) $p['usuario_id'] ? 'selected' : '' ?>>
                    <?= e($p['nombre'] . ' ' . $p['apellido']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secundario">Filtrar</button>
        <?php if ($buscar !== '' || $estado !== '' || $id_profesor > 0): ?>
            <a href="proyectos.php" class="btn btn-secundario">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="tabla-contenedor">
        <table>
            <thead>
                <tr><th>ID</th><th>Título</th><th>Estudiante</th><th>Docente</th><th>Grupo</th><th>Estado</th><th>Avance</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($proyectos as $t):
                $avance = $t['total_fases'] > 0 ? round($t['completadas'] / $t['total_fases'] * 100) : 0; ?>
                <tr>
                    <td><?= (int) $t['id_tesis'] ?></td>
                    <td><strong><?= e($t['titulo']) ?></strong><br><small>Registro: <?= e($t['fecha_registro']) ?></small></td>
                    <td><?= e($t['est_nombre'] . ' ' . $t['est_apellido']) ?></td>
                    <td><?= e($t['prof_nombre'] . ' ' . $t['prof_apellido']) ?></td>
                    <td><?= $t['nombre_grupo'] !== null ? e($t['nombre_grupo']) : '<small>Sin grupo</small>' ?></td>
                    <td><span class="etiqueta <?= clase_estado($t['estado']) ?>"><?= e($t['estado']) ?></span></td>
                    <td><?= $t['total_fases'] > 0 ? $avance . ' %' : '<small>Sin fases</small>' ?></td>
                    <td><div class="acciones">
                        <a class="btn btn-secundario btn-chico" href="ver_proyecto.php?id=<?= (int) $t['id_tesis'] ?>">Consultar</a>
                        <a class="btn btn-secundario btn-chico" href="editar_proyecto.php?id=<?= (int) $t['id_tesis'] ?>">Editar</a>
                        <a class="btn btn-secundario btn-chico" href="asignar_profesor.php?id=<?= (int) $t['id_tesis'] ?>">Asignar docente</a>
                        <a class="btn btn-peligro btn-chico" href="eliminar_proyecto.php?id=<?= (int) $t['id_tesis'] ?>">Eliminar</a>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$proyectos): ?>
                <tr><td colspan="8" class="vacio">No se encontraron proyectos.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>