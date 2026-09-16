<?php
/**
 * Conexión PDO a la base de datos senatv.
 * Ajusta $host / $user / $pass según tu instalación
 * (XAMPP/Laragon suelen usar user=root, pass='').
 */

$host    = 'localhost';
$db      = 'senatv';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Error de conexión a la base de datos: ' . $e->getMessage());
}
