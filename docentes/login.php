<?php
/**
 * Inicio de sesión de Thesis Vista.
 * - Acepta contraseñas en texto plano (datos originales) y las migra a
 *   password_hash() en el primer inicio de sesión correcto.
 * - Registra usuarios.ultimo_acceso (extensión v1).
 */
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['usuario_id']) && ($_SESSION['rol'] ?? '') === 'profesor') {
    redirigir('docente/dashboard.php');
}

$error = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $correo = trim($_POST['correo'] ?? '');
    $clave  = (string)($_POST['contrasena'] ?? '');

    $st = $pdo->prepare('SELECT usuario_id, nombre, apellido, contrasena, rol FROM usuarios WHERE correo = ?');
    $st->execute([$correo]);
    $u = $st->fetch();

    $valida = false;
    if ($u) {
        $info = password_get_info($u['contrasena']);
        if ($info['algo'] !== null && $info['algo'] !== 0) {
            $valida = password_verify($clave, $u['contrasena']);
        } else {
            // Contraseña antigua en texto plano: se compara y se migra a hash
            $valida = hash_equals($u['contrasena'], $clave);
            if ($valida) {
                $pdo->prepare('UPDATE usuarios SET contrasena = ? WHERE usuario_id = ?')
                    ->execute([password_hash($clave, PASSWORD_DEFAULT), $u['usuario_id']]);
            }
        }
    }

    if (!$valida) {
        $error = 'Correo o contraseña incorrectos.';
    } elseif ($u['rol'] !== 'profesor') {
        $error = 'Esta interfaz es exclusiva para docentes.';
    } else {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int)$u['usuario_id'];
        $_SESSION['rol']        = $u['rol'];
        $_SESSION['nombre']     = $u['nombre'] . ' ' . $u['apellido'];
        $pdo->prepare('UPDATE usuarios SET ultimo_acceso = ? WHERE usuario_id = ?')
            ->execute([date('Y-m-d H:i:s'), $u['usuario_id']]);
        redirigir('docente/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · Thesis Vista</title>
    <link rel="stylesheet" href="<?= url('css/styles.css') ?>">
</head>
<body class="pagina-login">
    <div class="login-caja">
        <div class="login-marca">
            <span class="marca-logo">TV</span>
            <h1>Thesis Vista</h1>
            <p>Seguimiento de proyectos académicos</p>
        </div>
        <?php if ($error): ?><div class="alerta alerta-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="formulario">
            <?= csrf_campo() ?>
            <label>Correo
                <input type="text" name="correo" value="<?= e($correo) ?>" required autofocus>
            </label>
            <label>Contraseña
                <input type="password" name="contrasena" required>
            </label>
            <button class="btn btn-bloque" type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>
