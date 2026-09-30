<?php
/** THESISVISTA - Gestión de usuarios: lista con búsqueda y filtro por rol */
require_once __DIR__ . '/../includes/seguridad.php';
require_once __DIR__ . '/includes/funciones_admin.php';

$admin = requerir_rol('administrador');

// Filtros recibidos por GET
$buscar = trim($_GET['buscar'] ?? '');
$rol    = $_GET['rol'] ?? '';
if (!isset(ROLES[$rol])) $rol = '';

$sql        = 'SELECT usuario_id, nombre, apellido, correo, rol FROM usuarios WHERE 1 = 1';
$parametros = [];

if ($buscar !== '') {
    $sql .= ' AND (nombre LIKE ? OR apellido LIKE ? OR correo LIKE ?)';
    $comodin = '%' . $buscar . '%';
    array_push($parametros, $comodin, $comodin, $comodin);
}
if ($rol !== '') {
    $sql .= ' AND rol = ?';
    $parametros[] = $rol;
}
$sql .= ' ORDER BY usuario_id';

$stmt = conectar()->prepare($sql);
$stmt->execute($parametros);
$usuarios = $stmt->fetchAll();

$titulo  = 'Gestión de usuarios';
$seccion = 'usuarios';
require __DIR__ . '/includes/header.php';
?>

<div class="tarjeta">
    <div class="tarjeta-cabecera">
        <h2>Usuarios registrados (<?= count($usuarios) ?>)</h2>
        <a href="crear_usuario.php" class="btn btn-primario">+ Crear usuario</a>
    </div>

    <form method="get" action="usuarios.php" class="filtros">
        <input type="text" name="buscar" value="<?= e($buscar) ?>" placeholder="Buscar por nombre, apellido o correo">
        <select name="rol">
            <option value="">Todos los roles</option>
            <?php foreach (ROLES as $valor => $texto): ?>
                <option value="<?= e($valor) ?>" <?= $rol === $valor ? 'selected' : '' ?>><?= e($texto) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secundario">Filtrar</button>
        <?php if ($buscar !== '' || $rol !== ''): ?>
            <a href="usuarios.php" class="btn btn-secundario">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="tabla-contenedor">
        <table>
            <thead>
                <tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= (int) $u['usuario_id'] ?></td>
                    <td><?= e($u['nombre'] . ' ' . $u['apellido']) ?></td>
                    <td><?= e($u['correo']) ?></td>
                    <td><span class="etiqueta rol-<?= e($u['rol']) ?>"><?= e(nombre_rol($u['rol'])) ?></span></td>
                    <td><div class="acciones">
                        <a class="btn btn-secundario btn-chico" href="editar_usuario.php?id=<?= (int) $u['usuario_id'] ?>">Editar</a>
                        <a class="btn btn-secundario btn-chico" href="cambiar_rol.php?id=<?= (int) $u['usuario_id'] ?>">Cambiar rol</a>
                        <?php if ((int) $u['usuario_id'] !== (int) $admin['usuario_id']): ?>
                            <a class="btn btn-peligro btn-chico" href="eliminar_usuario.php?id=<?= (int) $u['usuario_id'] ?>">Eliminar</a>
                        <?php endif; ?>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$usuarios): ?>
                <tr><td colspan="5" class="vacio">No se encontraron usuarios.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
