<?php
/**
 * THESISVISTA - Funciones comunes para los 3 módulos
 * (administrador, docente y estudiante).
 *
 * - Inicio de sesión PHP
 * - Control de acceso por rol
 * - Mensajes de éxito / error (flash)
 * - Token CSRF para los formularios
 * - Escape de datos para HTML
 */

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Roles tal como existen en la columna usuarios.rol (ENUM) y su nombre visible. */
const ROLES = [
    'administrador' => 'Administrador',
    'profesor'      => 'Docente',
    'estudiante'    => 'Estudiante',
];

/* Panel al que se envía cada rol después del login. */
const PANELES = [
    'administrador' => 'admin/dashboard.php',
    'profesor'      => 'docente/index.php',
    'estudiante'    => 'estudiante/index.php',
];

/** Escapa un texto para mostrarlo de forma segura en HTML. */
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/** Nombre visible de un rol (ej: 'profesor' => 'Docente'). */
function nombre_rol(string $rol): string
{
    return ROLES[$rol] ?? $rol;
}

/** Redirige a otra página y termina el script. */
function redirigir(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/* ---------------- Mensajes flash ---------------- */

/** Guarda un mensaje para mostrarlo en la siguiente página. $tipo: 'exito' o 'error'. */
function mensaje(string $tipo, string $texto): void
{
    $_SESSION['mensajes'][] = ['tipo' => $tipo, 'texto' => $texto];
}

/** Imprime (y borra) los mensajes guardados. */
function mostrar_mensajes(): void
{
    foreach ($_SESSION['mensajes'] ?? [] as $m) {
        echo '<div class="alerta alerta-' . e($m['tipo']) . '">' . e($m['texto']) . '</div>';
    }
    unset($_SESSION['mensajes']);
}

/* ---------------- CSRF ---------------- */

/** Campo oculto con el token que se agrega a cada formulario POST. */
function campo_csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

/** Comprueba el token recibido por POST. */
function csrf_valido(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

/* ---------------- Sesión y roles ---------------- */

function usuario_logueado(): bool
{
    return isset($_SESSION['usuario_id']);
}

/**
 * Protege una página: solo deja pasar usuarios con el rol indicado.
 * El rol se vuelve a leer de la base de datos para que un cambio de rol
 * o una eliminación hecha por el administrador tenga efecto inmediato.
 *
 * $raiz = ruta relativa hasta la carpeta principal (ej: '../' desde /admin).
 */
function requerir_rol(string $rol, string $raiz = '../'): array
{
    if (!usuario_logueado()) {
        mensaje('error', 'Debe iniciar sesión para continuar.');
        redirigir($raiz . 'login.php');
    }

    $stmt = conectar()->prepare(
        'SELECT usuario_id, nombre, apellido, correo, rol FROM usuarios WHERE usuario_id = ?'
    );
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();

    if (!$usuario) {                       // el usuario fue eliminado
        session_unset();
        mensaje('error', 'Su usuario ya no existe. Inicie sesión nuevamente.');
        redirigir($raiz . 'login.php');
    }

    if ($usuario['rol'] !== $rol) {        // no tiene permiso para este módulo
        $_SESSION['rol'] = $usuario['rol'];
        mensaje('error', 'No tiene permisos para acceder a esa sección.');
        redirigir($raiz . PANELES[$usuario['rol']]);
    }

    return $usuario;
}
