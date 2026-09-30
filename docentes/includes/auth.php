<?php
/**
 * Sesión, control de acceso del docente, CSRF y mensajes flash.
 * Incluir al inicio de TODAS las páginas de /docente.
 */
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/funciones.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/** Escapa texto para imprimirlo en HTML. */
function e($texto): string
{
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

/** URL absoluta dentro de la aplicación. */
function url(string $ruta): string
{
    return BASE_URL . '/' . ltrim($ruta, '/');
}

function redirigir(string $ruta): void
{
    header('Location: ' . url($ruta));
    exit;
}

/**
 * Exige que haya un usuario con rol "profesor" autenticado.
 * El rol se vuelve a leer de la BD en cada petición, así un cambio
 * hecho por el administrador se aplica de inmediato.
 */
function requerir_docente(PDO $pdo): array
{
    if (empty($_SESSION['usuario_id'])) {
        redirigir('login.php');
    }
    $st = $pdo->prepare('SELECT usuario_id, nombre, apellido, correo, rol, ultimo_acceso
                           FROM usuarios WHERE usuario_id = ?');
    $st->execute([$_SESSION['usuario_id']]);
    $u = $st->fetch();
    if (!$u) {
        session_destroy();
        redirigir('login.php');
    }
    if ($u['rol'] !== 'profesor') {
        http_response_code(403);
        exit('Acceso denegado: esta sección es exclusiva para docentes.');
    }
    return $u;
}

/** Detiene la ejecución con un 403 (recurso que no pertenece al docente). */
function denegar(string $mensaje = 'No tiene permiso para ver este recurso.'): void
{
    http_response_code(403);
    $docente = $GLOBALS['docente'] ?? null;
    $titulo_pagina = 'Acceso denegado';
    require __DIR__ . '/header.php';
    echo '<div class="card vacio"><h2>Acceso denegado</h2><p>' . e($mensaje) . '</p>'
       . '<a class="btn" href="' . url('docente/dashboard.php') . '">Volver al inicio</a></div>';
    require __DIR__ . '/footer.php';
    exit;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verificar_csrf(): void
{
    if (!isset($_POST['csrf']) || !hash_equals(csrf_token(), (string)$_POST['csrf'])) {
        http_response_code(400);
        exit('Solicitud inválida (token de seguridad). Recargue la página e intente de nuevo.');
    }
}

/* ---------- Mensajes flash ---------- */
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function mostrar_flash(): string
{
    $html = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $html .= '<div class="alerta alerta-' . e($f['tipo']) . '">' . e($f['mensaje']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $html;
}
