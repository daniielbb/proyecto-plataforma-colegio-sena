<?php
/**
 * THESISVISTA - Eliminar usuario
 * GET ?id= -> página de confirmación (sin JavaScript)
 * POST     -> DELETE FROM usuarios (solo si ninguna otra tabla lo referencia)
 *
 * Las claves foráneas de la base (tesis, comentarios, grupos, cursos...) no tienen
 * ON DELETE, por eso un usuario con registros relacionados no se puede borrar.
 */
require_once __DIR__ . '/../includes/seguridad.php';
require_once __DIR__ . '/includes/funciones_admin.php';

$admin = requerir_rol('administrador');

$id      = (int) ($_GET['id'] ?? 0);
$usuario = buscar_usuario($id);
if (!$usuario) {
    mensaje('error', 'El usuario solicitado no existe.');
    redirigir('usuarios.php');
}
if ($id === (int) $admin['usuario_id']) {
    mensaje('error', 'No puede eliminar su propio usuario.');
    redirigir('usuarios.php');
}

$dependencias = dependencias_usuario($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        mensaje('error', 'El formulario expiró. Intente de nuevo.');
    } elseif ($dependencias) {
        mensaje('error', 'No se puede eliminar: el usuario tiene registros relacionados.');
    } else {
        try {
            conectar()->prepare('DELETE FROM usuarios WHERE usuario_id = ?')->execute([$id]);
            mensaje('exito', 'Usuario "' . $usuario['nombre'] . ' ' . $usuario['apellido'] . '" eliminado.');
        } catch (PDOException $ex) {
            mensaje('error', 'MySQL no permitió eliminar el usuario porque tiene registros relacionados.');
        }
    }
    redirigir('usuarios.php');
}

$titulo  = 'Eliminar usuario';
$seccion = 'usuarios';
require __DIR__ . '/includes/header.php';
?>
<div class="tarjeta" style="max-width:640px">
    <h2>¿Eliminar a <?= e($usuario['nombre'] . ' ' . $usuario['apellido']) ?>?</h2>
    <p><?= e($usuario['correo']) ?> ·
        <span class="etiqueta rol-<?= e($usuario['rol']) ?>"><?= e(nombre_rol($usuario['rol'])) ?></span></p>

    <?php if ($dependencias): ?>
        <div class="alerta alerta-error">
            Este usuario no se puede eliminar porque está relacionado con:
            <ul>
                <?php foreach ($dependencias as [$texto, $cantidad]): ?>
                    <li><?= e($texto) ?>: <?= $cantidad ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <p>Reasigne o elimine primero esos registros (por ejemplo, asigne otro docente a sus proyectos).</p>
        <a href="usuarios.php" class="btn btn-secundario">Volver a usuarios</a>
    <?php else: ?>
        <div class="alerta alerta-aviso">Esta acción no se puede deshacer.</div>
        <form method="post" action="eliminar_usuario.php?id=<?= $id ?>" class="formulario">
            <?= campo_csrf() ?>
            <div class="botones">
                <button type="submit" class="btn btn-peligro">Sí, eliminar usuario</button>
                <a href="usuarios.php" class="btn btn-secundario">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
