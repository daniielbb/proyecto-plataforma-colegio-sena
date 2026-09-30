<?php
/**
 * Conexión PDO a MySQL/MariaDB.
 * Uso:  $pdo = db();
 * Todas las consultas del sistema usan sentencias preparadas.
 */
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '-05:00'");
    } catch (PDOException $e) {
        error_log('[SENATV] Error de conexión: ' . $e->getMessage());
        http_response_code(500);
        exit('<p style="font-family:sans-serif;padding:2rem">No fue posible conectar con la base de datos. '
           . 'Verifique <code>config/config.php</code> y que MySQL esté en ejecución.</p>');
    }
    return $pdo;
}
