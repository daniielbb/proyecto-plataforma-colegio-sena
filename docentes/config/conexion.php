<?php
/**
 * Thesis Vista · Conexión a MySQL/MariaDB (PDO)
 * Ajuste estos valores según su servidor (XAMPP por defecto: root sin clave).
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'senatv');
define('DB_USER', 'root');
define('DB_PASS', '');

// Ruta base de la aplicación dentro del servidor (ej: '/thesisvista').
// Déjela vacía ('') si la carpeta es la raíz del sitio.
define('BASE_URL', '/thesisvista');

// Carpeta física donde se guardan los documentos subidos por los estudiantes
define('UPLOADS_DIR', __DIR__ . '/../uploads/documentos/');

date_default_timezone_set('America/Bogota');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('No fue posible conectar con la base de datos. Revise config/conexion.php.');
}
