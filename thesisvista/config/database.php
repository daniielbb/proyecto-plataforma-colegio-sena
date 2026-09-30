<?php
/**
 * THESISVISTA - Conexión a la base de datos (PDO)
 *
 * Este es el ÚNICO archivo con las credenciales de MySQL.
 * Todas las páginas lo incluyen y usan la función conectar().
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'senatv');
define('DB_USER', 'root');
define('DB_PASS', '');          // En XAMPP el usuario root no tiene contraseña por defecto
define('DB_CHARSET', 'utf8mb4');

/**
 * Devuelve siempre la misma conexión PDO (se crea solo una vez por petición).
 */
function conectar(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // los errores lanzan excepciones
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // resultados como arreglo asociativo
            PDO::ATTR_EMULATE_PREPARES   => false,                  // consultas preparadas reales
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            // No mostramos el detalle técnico al usuario, solo un mensaje claro.
            error_log('Error de conexión: ' . $e->getMessage());
            http_response_code(500);
            exit('No se pudo conectar con la base de datos. Revise config/database.php y que MySQL esté encendido.');
        }
    }

    return $pdo;
}
