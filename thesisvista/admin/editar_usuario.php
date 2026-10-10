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

$datos         = $usuario;
$errores       = [];
$rol_bloqueado = $id === (int) $admin['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $errores[] = 'El formulario expiró. Intente de nuevo.';
    } else {
        [$datos, $errores] = validar_usuario($_POST, false, $usuario['correo'], $id);

        if (!$errores) {
            $error_rol = error_cambio_rol($usuario, $datos['rol'], (int) $admin['usuario_id']);
            if ($error_rol) $errores[] = $error_rol;
        }

        if (!$errores) {
            try {
                $sql        = 'UPDATE usuarios SET nombre = ?, apellido = ?, correo = ?, rol = ?';
                $parametros = [$datos['nombre'], $datos['apellido'], $datos['correo'], $datos['rol']];

                if ($datos['contrasena'] !== '') {           // solo si escribió una nueva contraseña
                    $sql .= ', contrasena = ?';
                    $parametros[] = password_hash($datos['contrasena'], PASSWORD_DEFAULT);
                }
                $sql .= ' WHERE usuario_id = ?';
                $parametros[] = $id;

                conectar()->prepare($sql)->execute($parametros);

                if ($rol_bloqueado) {
                    $_SESSION['nombre'] = $datos['nombre'] . ' ' . $datos['apellido'];
                }
                mensaje('exito', 'Usuario "' . $datos['nombre'] . ' ' . $datos['apellido'] . '" actualizado correctamente.');
                redirigir('usuarios.php');
            } catch (PDOException $ex) {
                $errores[] = $ex->getCode() === '23000'
                    ? 'Ya existe otro usuario con ese correo.'
                    : 'No se pudieron guardar los cambios.';
            }
        }
    }
}

$titulo   = 'Editar usuario';
$seccion  = 'usuarios';
$es_nuevo = false;
$accion   = 'editar_usuario.php?id=' . $id;
require __DIR__ . '/../includes/header.php';
?>
<div class="tarjeta" style="max-width:760px">
    <h2>Editar: <?= e($usuario['nombre'] . ' ' . $usuario['apellido']) ?> <small>(ID <?= $id ?>)</small></h2>
    <?php require __DIR__ . '/../includes/form_usuario.php'; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>