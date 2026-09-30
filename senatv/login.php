<?php
/**
 * Inicio de sesión para los tres roles (estudiante, profesor, administrador).
 * Flujo: ¿tiene cuenta? -> iniciar sesión -> validar -> redirigir según rol.
 */
require_once __DIR__ . '/includes/sesion.php';
require_once __DIR__ . '/includes/iconos.php';

iniciar_sesion();
if (usuario_autenticado()) {
    redirigir('dashboard.php');
}

$error  = '';
$correo = '';
$aviso  = $_SESSION['aviso'] ?? '';
unset($_SESSION['aviso']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim((string)($_POST['correo'] ?? ''));
    $clave  = (string)($_POST['contrasena'] ?? '');

    $bloqueadoHasta = $_SESSION['login_bloqueo'] ?? 0;

    if (!validar_csrf($_POST['csrf'] ?? null)) {
        $error = 'La sesión del formulario expiró. Intenta de nuevo.';
    } elseif ($bloqueadoHasta > time()) {
        $error = 'Demasiados intentos fallidos. Espera ' . ceil(($bloqueadoHasta - time()) / 60) . ' min e intenta de nuevo.';
    } elseif ($correo === '' || $clave === '') {
        $error = 'Ingresa tu correo y tu contraseña.';
    } else {
        $pdo  = db();
        $stmt = $pdo->prepare('SELECT usuario_id, nombre, apellido, correo, contrasena, rol, ultimo_acceso
                                 FROM usuarios WHERE correo = ? LIMIT 1');
        $stmt->execute([$correo]);
        $u = $stmt->fetch();

        $valida = false;
        if ($u) {
            $info = password_get_info($u['contrasena']);
            if ($info['algo'] !== null && $info['algo'] !== 0) {
                $valida = password_verify($clave, $u['contrasena']);
                if ($valida && password_needs_rehash($u['contrasena'], PASSWORD_DEFAULT)) {
                    $pdo->prepare('UPDATE usuarios SET contrasena = ? WHERE usuario_id = ?')
                        ->execute([password_hash($clave, PASSWORD_DEFAULT), $u['usuario_id']]);
                }
            } else {
                // Contraseña en texto plano (dump original): se compara y se migra a hash
                $valida = hash_equals($u['contrasena'], $clave);
                if ($valida && MIGRAR_CLAVES_PLANAS) {
                    $pdo->prepare('UPDATE usuarios SET contrasena = ? WHERE usuario_id = ?')
                        ->execute([password_hash($clave, PASSWORD_DEFAULT), $u['usuario_id']]);
                }
            }
        }

        if ($valida && isset(ROLES_INTERFAZ[$u['rol']])) {
            session_regenerate_id(true);
            unset($_SESSION['login_intentos'], $_SESSION['login_bloqueo']);
            $_SESSION['usuario_id']      = (int)$u['usuario_id'];
            $_SESSION['rol']             = $u['rol'];
            $_SESSION['nombre']          = $u['nombre'];
            $_SESSION['apellido']        = $u['apellido'];
            $_SESSION['huella']          = huella_cliente();
            // Guardamos el acceso anterior para detectar correcciones/comentarios nuevos
            $_SESSION['acceso_anterior'] = $u['ultimo_acceso']
                ?: date('Y-m-d H:i:s', strtotime('-' . DIAS_NOVEDAD_POR_DEFECTO . ' days'));
            $pdo->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE usuario_id = ?')
                ->execute([$u['usuario_id']]);
            redirigir('dashboard.php');
        }

        $_SESSION['login_intentos'] = ($_SESSION['login_intentos'] ?? 0) + 1;
        if ($_SESSION['login_intentos'] >= LOGIN_MAX_INTENTOS) {
            $_SESSION['login_bloqueo']  = time() + LOGIN_BLOQUEO_SEG;
            $_SESSION['login_intentos'] = 0;
        }
        $error = 'Correo o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Iniciar sesión · <?= e(APP_NOMBRE) ?></title>
<link rel="stylesheet" href="<?= e(url('css/estilos.css')) ?>">
</head>
<body class="pagina-login">
  <main class="login-caja">
    <section class="login-marca">
      <div class="marca-logo"><?= icono('birrete') ?></div>
      <h1><?= e(APP_NOMBRE) ?></h1>
      <p><?= e(APP_SUBTITULO) ?></p>
      <ul class="login-lista">
        <li><?= icono('carpeta') ?> Fases de tu proyecto organizadas como carpetas</li>
        <li><?= icono('correccion') ?> Correcciones y comentarios de tu docente</li>
        <li><?= icono('progreso') ?> Progreso actualizado en tiempo real</li>
      </ul>
    </section>

    <section class="login-form">
      <h2>Iniciar sesión</h2>
      <p class="texto-suave">Ingresa con el correo registrado en la institución.</p>

      <?php if ($aviso): ?><div class="alerta alerta-info"><?= e($aviso) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alerta alerta-error" role="alert"><?= e($error) ?></div><?php endif; ?>

      <form method="post" action="<?= e(url('login.php')) ?>" autocomplete="on">
        <input type="hidden" name="csrf" value="<?= e(token_csrf()) ?>">
        <label for="correo">Correo</label>
        <input id="correo" name="correo" type="text" inputmode="email" required maxlength="150"
               value="<?= e($correo) ?>" autofocus>

        <label for="contrasena">Contraseña</label>
        <input id="contrasena" name="contrasena" type="password" required maxlength="255">

        <button class="boton boton-bloque" type="submit">Ingresar</button>
      </form>

      <p class="login-nota">¿No tienes cuenta? Las cuentas las crea el administrador de la plataforma.
        Solicítala a tu docente o a la coordinación.</p>
    </section>
  </main>
</body>
</html>
