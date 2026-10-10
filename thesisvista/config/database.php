<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'senatv');
define('DB_USER', 'root');
define('DB_PASS', '');          
define('DB_CHARSET', 'utf8mb4');


function conectar(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
            PDO::ATTR_EMULATE_PREPARES   => false,                 
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
           
            error_log('Error de conexión: ' . $e->getMessage());
            http_response_code(500);
            exit('No se pudo conectar con la base de datos. Revise config/database.php y que MySQL esté encendido.');
        }
    }

    return $pdo;
}
