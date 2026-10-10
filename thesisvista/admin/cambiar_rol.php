<?php

require_once __DIR__ . '/../includes/seguridad.php';
require_once __DIR__ . '/../includes/funciones_admin.php';

$admin = requerir_rol('administrador');

$id      = (int) ($_GET['id'] ?? 0);
$usuario = buscar_usuario($id);
if (!$usuario) {
    mensaje('error', 'El usuario solicitado no existe.');
    redirigir('usuarios.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rol_nuevo = $_POST['rol'] ?? '';

    if (!csrf_valido()) {
        $error = 'El formulario expiró. Intente de nuevo.';
    } elseif (!isset(ROLES[$rol_nuevo])) {
        $error = 'Seleccione un rol válido.';
    } elseif ($rol_nuevo === $usuario['rol']) {
        $error = 'El usuario ya tiene el rol ' . nombre_rol($rol_nuevo) . '.';
    } else {
        $error = error_cambio_rol($usuario, $rol_nuevo, (int) $admin['usuario_id']) ?? '';
    }

    if ($error === '') {
        conectar()->prepare('UPDATE usuarios SET rol = ? WHERE usuario_id = ?')->execute([$rol_nuevo, $id]);
        mensaje('exito', 'El rol de ' . $usuario['nombre'] . ' ' . $usuario['apellido'] . ' cambió de '
            . nombre_rol($usuario['rol']) . ' a ' . nombre_rol($rol_nuevo) . '.');
        redirigir('usuarios.php');
    }
}

$titulo  = 'Cambiar rol';
$seccion = 'usuarios';
require __DIR__ . '/../includes/header.php';
?>
<div class="tarjeta" style="max-width:560px">
    <h2><?= e($usuario['nombre'] . ' ' . $usuario['apellido']) ?></h2>
    <p><?= e($usuario['correo']) ?> · Rol actual:
        <span class="etiqueta rol-<?= e($usuario['rol']) ?>"><?= e(nombre_rol($usuario['rol'])) ?></span></p>

    <?php if ($error): ?><div class="alerta alerta-error"><?= e($error) ?></div><?php endif; ?>

    <?php if ($id === (int) $admin['usuario_id']): ?>
        <div class="alerta alerta-aviso">No puede cambiar su propio rol.</div>
        <a href="usuarios.php" class="btn btn-secundario">Volver</a>
    <?php else: ?>
        <form method="post" action="cambiar_rol.php?id=<?= $id ?>" class="formulario">
            <?= campo_csrf() ?>
            <label for="rol">Nuevo rol</label>
            <select id="rol" name="rol" required>
                <?php foreach (ROLES as $valor => $texto): ?>
                    <option value="<?= e($valor) ?>" <?= $usuario['rol'] === $valor ? 'selected' : '' ?>><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="ayuda">Si el usuario tiene tesis, grupos o cursos con su rol actual, primero deben reasignarse.</p>
            <div class="botones">
                <button type="submit" class="btn btn-primario">Cambiar rol</button>
                <a href="usuarios.php" class="btn btn-secundario">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>