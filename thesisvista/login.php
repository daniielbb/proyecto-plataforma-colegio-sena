<?php

require_once __DIR__ . '/includes/seguridad.php';

if (usuario_logueado() && isset(PANELES[$_SESSION['rol'] ?? ''])) {
    redirigir(PANELES[$_SESSION['rol']]);
}

$error  = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo     = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if (!csrf_valido()) {
        $error = 'La sesión del formulario expiró. Intente de nuevo.';
    } elseif ($correo === '' || $contrasena === '') {
        $error = 'Escriba su correo y su contraseña.';
    } else {
        $pdo  = conectar();
        $stmt = $pdo->prepare('SELECT usuario_id, nombre, apellido, correo, contrasena, rol
                               FROM usuarios WHERE correo = ?');
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        $valido = false;
        if ($usuario) {
            $guardada = $usuario['contrasena'];

            if (password_get_info($guardada)['algoName'] !== 'unknown') {
                
                $valido = password_verify($contrasena, $guardada);
            } elseif (hash_equals($guardada, $contrasena)) {
                
                $valido = true;
                $pdo->prepare('UPDATE usuarios SET contrasena = ? WHERE usuario_id = ?')
                    ->execute([password_hash($contrasena, PASSWORD_DEFAULT), $usuario['usuario_id']]);
            }
        }

        if ($valido) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = (int) $usuario['usuario_id'];
            $_SESSION['nombre']     = $usuario['nombre'] . ' ' . $usuario['apellido'];
            $_SESSION['rol']        = $usuario['rol'];

           
            try {
                $pdo->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE usuario_id = ?')
                    ->execute([$usuario['usuario_id']]);
            } catch (PDOException $ex) {
                // La columna no existe: no pasa nada.
            }

            mensaje('exito', 'Bienvenido(a), ' . $usuario['nombre'] . '.');
            redirigir(PANELES[$usuario['rol']]);
        }

        $error = 'Correo o contraseña incorrectos. Intente nuevamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · THESIS VISTA</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="pagina-login">
    <main class="login-caja">
        <div class="login-marca">
            <span class="logo">TV</span>
            <h1>THESIS VISTA</h1>
            <p>Plataforma de gestión de proyectos y tesis</p>
        </div>

        <?php mostrar_mensajes(); ?>
        <?php if ($error): ?>
            <div class="alerta alerta-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" class="formulario">
            <?= campo_csrf() ?>
            <label for="correo">Correo electrónico</label>
            <input type="text" id="correo" name="correo" value="<?= e($correo) ?>" required autofocus maxlength="150">

            <label for="contrasena">Contraseña</label>
            <input type="password" id="contrasena" name="contrasena" required>

            <button type="submit" class="btn btn-primario btn-bloque">Iniciar sesión</button>
        </form>
    </main>
</body>
</html>
