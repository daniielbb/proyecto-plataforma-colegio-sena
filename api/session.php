<?php
require __DIR__ . '/bootstrap.php';

if (!isset($_SESSION['usuario_id'])) {
    fail(401, 'No hay sesión activa.');
}

echo json_encode([
    'usuario_id' => $_SESSION['usuario_id'],
    'nombre'     => $_SESSION['nombre'],
    'apellido'   => $_SESSION['apellido'],
    'rol'        => $_SESSION['rol'],
]);
