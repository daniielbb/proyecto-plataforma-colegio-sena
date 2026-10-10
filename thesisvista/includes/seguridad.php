<?php


require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const ROLES = [
    'administrador' => 'Administrador',
    'profesor'      => 'Docente',
    'estudiante'    => 'Estudiante',
];


const PANELES = [
    'administrador' => 'admin/dashboard.php',
    'profesor'      => 'docente/index.php',
    'estudiante'    => 'estudiante/index.php',
];


function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function nombre_rol(string $rol): string
{
    return ROLES[$rol] ?? $rol;
}


function redirigir(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function mensaje(string $tipo, string $texto): void
{
    $_SESSION['mensajes'][] = ['tipo' => $tipo, 'texto' => $texto];
}


function mostrar_mensajes(): void
{
    foreach ($_SESSION['mensajes'] ?? [] as $m) {
        echo '<div class="alerta alerta-' . e($m['tipo']) . '">' . e($m['texto']) . '</div>';
    }
    unset($_SESSION['mensajes']);
}

function campo_csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}


function csrf_valido(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}



function usuario_logueado(): bool
{
    return isset($_SESSION['usuario_id']);
}


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

    if (!$usuario) {                       
        session_unset();
        mensaje('error', 'Su usuario ya no existe. Inicie sesión nuevamente.');
        redirigir($raiz . 'login.php');
    }

    if ($usuario['rol'] !== $rol) {        
        $_SESSION['rol'] = $usuario['rol'];
        mensaje('error', 'No tiene permisos para acceder a esa sección.');
        redirigir($raiz . PANELES[$usuario['rol']]);
    }

    return $usuario;
}
