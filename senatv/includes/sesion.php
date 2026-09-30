<?php
/**
 * Manejo de sesiones y control de acceso por rol.
 * Se incluye en TODAS las páginas.
 *
 *   requiere_login();                 -> exige sesión válida
 *   requiere_rol('estudiante');       -> exige sesión + rol exacto
 *   solo_lectura();                   -> bloquea POST/PUT/DELETE en módulos de consulta
 */
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/funciones.php';

function iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name(SESION_NOMBRE);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL === '' ? '/' : BASE_URL . '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();

    // Expiración por inactividad
    if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad']) > SESION_INACTIVIDAD) {
        cerrar_sesion();
        session_start();
        $_SESSION['aviso'] = 'Tu sesión expiró por inactividad. Ingresa de nuevo.';
    }
    $_SESSION['ultima_actividad'] = time();
}

function usuario_autenticado(): bool
{
    return isset($_SESSION['usuario_id'], $_SESSION['rol'], $_SESSION['huella'])
        && hash_equals($_SESSION['huella'], huella_cliente());
}

/** Huella simple del navegador para dificultar el robo de sesión. */
function huella_cliente(): string
{
    return hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . SESION_NOMBRE);
}

function requiere_login(): void
{
    iniciar_sesion();
    if (!usuario_autenticado()) {
        redirigir('login.php');
    }
    // Páginas privadas: no se guardan en caché ni se pueden incrustar en otros sitios
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

/**
 * Verifica que el usuario tenga el rol indicado (valores reales de usuarios.rol:
 * 'estudiante', 'profesor', 'administrador'). Si no, responde 403.
 */
function requiere_rol(string $rol): void
{
    requiere_login();
    if ($_SESSION['rol'] !== $rol) {
        http_response_code(403);
        require __DIR__ . '/acceso_denegado.php';
        exit;
    }
}

/**
 * Los módulos de consulta (estudiante) solo aceptan GET/HEAD.
 * Cualquier intento de enviar formularios o modificar datos se rechaza.
 */
function solo_lectura(): void
{
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($metodo, ['GET', 'HEAD'], true)) {
        http_response_code(405);
        header('Allow: GET, HEAD');
        exit('Operación no permitida: este módulo es solo de consulta.');
    }
}

/** Ruta de inicio según el rol. */
function inicio_por_rol(string $rol): string
{
    $carpeta = ROLES_INTERFAZ[$rol] ?? null;
    return $carpeta ? $carpeta . '/index.php' : 'login.php';
}

function cerrar_sesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// --- CSRF (usado en el formulario de login) ------------------------------
function token_csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function validar_csrf(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}
