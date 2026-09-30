<?php
/**
 * Configuración general de la plataforma SENATV.
 * Ajuste estos valores a su servidor (XAMPP por defecto).
 */

// --- Base de datos -------------------------------------------------------
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'senatv');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// --- Aplicación ----------------------------------------------------------
define('APP_NOMBRE', 'SENATV');
define('APP_SUBTITULO', 'Seguimiento de proyectos académicos');
date_default_timezone_set('America/Bogota');

/**
 * URL base del proyecto. Se detecta sola (p. ej. "/senatv").
 * Si su servidor la detecta mal, escríbala manualmente: define('BASE_URL', '/senatv');
 */
if (!defined('BASE_URL')) {
    $raiz = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $doc  = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
    $base = ($doc !== '' && stripos($raiz, $doc) === 0) ? substr($raiz, strlen($doc)) : '';
    define('BASE_URL', rtrim($base, '/'));
}

// Carpeta física de archivos subidos (no accesible directamente por URL)
define('RUTA_DOCUMENTOS', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documentos');

// --- Seguridad -----------------------------------------------------------
define('SESION_NOMBRE', 'SENATVSESID');
define('SESION_INACTIVIDAD', 1800);   // segundos (30 min)
define('LOGIN_MAX_INTENTOS', 5);      // intentos fallidos antes de bloquear
define('LOGIN_BLOQUEO_SEG', 300);     // 5 min de bloqueo
// Convierte a hash (password_hash) las contraseñas en texto plano del dump original
define('MIGRAR_CLAVES_PLANAS', true);

/**
 * Si es true y el estudiante no tiene una tesis propia, se muestra la tesis
 * registrada para su grupo (modelo "un proyecto por grupo").
 * En la base actual cada tesis pertenece a un estudiante, por eso es false.
 */
define('PROYECTO_COMPARTIDO_POR_GRUPO', false);

/** Días hacia atrás que se consideran "novedad" si el usuario nunca había ingresado. */
define('DIAS_NOVEDAD_POR_DEFECTO', 7);

// Mapeo rol en BD -> carpeta de la interfaz
const ROLES_INTERFAZ = [
    'estudiante'    => 'estudiante',
    'profesor'      => 'docente',
    'administrador' => 'admin',
];
