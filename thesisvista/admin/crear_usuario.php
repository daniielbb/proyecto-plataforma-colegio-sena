<?php
/**
 * THESISVISTA - Crear usuario
 * Formulario -> POST -> validar -> INSERT INTO usuarios -> mensaje -> lista de usuarios
 */
require_once __DIR__ . '/../includes/seguridad.php';
require_once __DIR__ . '/includes/funciones_admin.php';

$admin = requerir_rol('administrador');

$datos   = ['nombre' => '', 'apellido' => '', 'correo' => '', 'rol' => ''];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $errores[] = 'El formulario expiró. Intente de nuevo.';
    } else {
        [$datos, $errores] = validar_usuario($_POST, true);

        if (!$errores) {
            try {
                $stmt = conectar()->prepare(
                    'INSERT INTO usuarios (nombre, apellido, correo, contrasena, rol) VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $datos['nombre'],
                    $datos['apellido'],
                    $datos['correo'],
                    password_hash($datos['contrasena'], PASSWORD_DEFAULT),
                    $datos['rol'],
                ]);

                mensaje('exito', 'Usuario "' . $datos['nombre'] . ' ' . $datos['apellido'] . '" creado como '
                    . nombre_rol($datos['rol']) . ' (ID ' . conectar()->lastInsertId() . ').');
                redirigir('usuarios.php');
            } catch (PDOException $ex) {
                // 23000 = violación de clave única (correo repetido)
                $errores[] = $ex->getCode() === '23000'
                    ? 'Ya existe un usuario con ese correo.'
                    : 'No se pudo guardar el usuario en la base de datos.';
            }
        }
    }
}

$titulo   = 'Crear usuario';
$seccion  = 'usuarios';
$es_nuevo = true;
$accion   = 'crear_usuario.php';
require __DIR__ . '/includes/header.php';
?>
<div class="tarjeta" style="max-width:760px">
    <h2>Datos del nuevo usuario</h2>
    <?php require __DIR__ . '/includes/form_usuario.php'; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
