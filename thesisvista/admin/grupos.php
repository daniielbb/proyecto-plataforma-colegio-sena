<?php

require_once __DIR__ . '/../includes/seguridad.php';
require_once __DIR__ . '/../includes/funciones_admin.php';

$admin = requerir_rol('administrador');

$buscar   = trim($_GET['buscar'] ?? '');
$id_curso = (int) ($_GET['curso'] ?? 0);

$sql = "SELECT g.id_grupo, g.nombre_grupo, g.fecha_creacion, c.id_curso, c.nombre_curso,
               (SELECT COUNT(*) FROM estudiante_grupo eg WHERE eg.id_grupo = g.id_grupo) AS total_estudiantes,
               (SELECT GROUP_CONCAT(CONCAT(u.nombre, ' ', u.apellido) ORDER BY u.nombre, u.apellido SEPARATOR '|')
                  FROM estudiante_grupo eg JOIN usuarios u ON u.usuario_id = eg.id_estudiante
                 WHERE eg.id_grupo = g.id_grupo) AS nombres_estudiantes,
               (SELECT COUNT(*) FROM tesis t WHERE t.id_grupo = g.id_grupo) AS total_proyectos,
               (SELECT COUNT(*) FROM docente_curso dc WHERE dc.id_curso = g.id_curso) AS total_docentes
        FROM grupos g
        JOIN cursos c ON c.id_curso = g.id_curso
        WHERE 1 = 1";
$parametros = [];

if ($buscar !== '') {
    $sql .= " AND (g.nombre_grupo LIKE ? OR EXISTS (
                SELECT 1 FROM estudiante_grupo eg JOIN usuarios u ON u.usuario_id = eg.id_estudiante
                 WHERE eg.id_grupo = g.id_grupo AND CONCAT(u.nombre, ' ', u.apellido) LIKE ?))";
    $comodin = '%' . $buscar . '%';
    array_push($parametros, $comodin, $comodin);
}
if ($id_curso > 0) {
    $sql .= ' AND g.id_curso = ?';
    $parametros[] = $id_curso;
}
$sql .= ' ORDER BY c.nombre_curso, g.nombre_grupo';

$stmt = conectar()->prepare($sql);
$stmt->execute($parametros);
$grupos = $stmt->fetchAll();

$cursos = lista_cursos();

$titulo  = 'Gestión de grupos';
$seccion = 'grupos';
require __DIR__ . '/../includes/header.php';
?>

<div class="tarjeta">
    <div class="tarjeta-cabecera">
        <h2>Grupos registrados (<?= count($grupos) ?>)</h2>
        <a href="crear_grupo.php" class="btn btn-primario">+ Crear grupo</a>
    </div>

    <form method="get" action="grupos.php" class="filtros">
        <input type="text" name="buscar" value="<?= e($buscar) ?>" placeholder="Buscar por grupo o estudiante">
        <select name="curso">
            <option value="0">Todos los cursos</option>
            <?php foreach ($cursos as $c): ?>
                <option value="<?= (int) $c['id_curso'] ?>" <?= $id_curso === (int) $c['id_curso'] ? 'selected' : '' ?>><?= e($c['nombre_curso']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secundario">Filtrar</button>
        <?php if ($buscar !== '' || $id_curso > 0): ?>
            <a href="grupos.php" class="btn btn-secundario">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="tabla-contenedor">
        <table>
            <thead>
                <tr><th>ID</th><th>Grupo</th><th>Curso</th><th>Estudiantes</th><th>Proyectos</th><th>Docentes</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($grupos as $g):
                $nombres = $g['nombres_estudiantes'] !== null ? explode('|', $g['nombres_estudiantes']) : [];
                $muestra = array_slice($nombres, 0, 4);
                $resto   = count($nombres) - count($muestra); ?>
                <tr>
                    <td><?= (int) $g['id_grupo'] ?></td>
                    <td><strong><?= e($g['nombre_grupo']) ?></strong><br><small>Creado: <?= e($g['fecha_creacion']) ?></small></td>
                    <td><?= e($g['nombre_curso']) ?></td>
                    <td>
                        <strong><?= (int) $g['total_estudiantes'] ?></strong>
                        <?php if ($muestra): ?>
                            <br><small><?= e(implode(', ', $muestra)) ?><?= $resto > 0 ? ' y ' . $resto . ' más' : '' ?></small>
                        <?php else: ?>
                            <br><small>Sin estudiantes</small>
                        <?php endif; ?>
                    </td>
                    <td><?= (int) $g['total_proyectos'] ?></td>
                    <td><?= (int) $g['total_docentes'] ?></td>
                    <td><div class="acciones">
                        <a class="btn btn-secundario btn-chico" href="ver_grupo.php?id=<?= (int) $g['id_grupo'] ?>">Consultar</a>
                        <a class="btn btn-secundario btn-chico" href="editar_grupo.php?id=<?= (int) $g['id_grupo'] ?>">Editar</a>
                        <a class="btn btn-peligro btn-chico" href="eliminar_grupo.php?id=<?= (int) $g['id_grupo'] ?>">Eliminar</a>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$grupos): ?>
                <tr><td colspan="7" class="vacio">No se encontraron grupos.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>