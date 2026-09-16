<?php
require __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(405, 'Método no permitido.');
}

$data = bodyJson();
$correo = trim($data['correo'] ?? '');
$contrasena = $data['contrasena'] ?? '';

if ($correo === '' || $contrasena === '') {
    fail(400, 'Correo y contraseña son obligatorios.');
}

$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE correo = ? LIMIT 1');
$stmt->execute([$correo]);
$usuario = $stmt->fetch();

$valido = false;

if ($usuario) {
    // Soporta contraseñas ya hasheadas y las que aún están en texto
    // plano en el dump original; si entra con la clave en texto
    // plano, la migramos a hash en ese mismo momento.
    if (password_verify($contrasena, $usuario['contrasena'])) {
        $valido = true;
    } elseif (hash_equals($usuario['contrasena'], $contrasena)) {
        $valido = true;
        $nuevoHash = password_hash($contrasena, PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE usuarios SET contrasena = ? WHERE usuario_id = ?');
        $upd->execute([$nuevoHash, $usuario['usuario_id']]);
    }
}

if (!$valido) {
    fail(401, 'Correo o contraseña incorrectos.');
}

$_SESSION['usuario_id'] = $usuario['usuario_id'];
$_SESSION['nombre']     = $usuario['nombre'];
$_SESSION['apellido']   = $usuario['apellido'];
$_SESSION['rol']        = $usuario['rol'];

echo json_encode([
    'ok'       => true,
    'rol'      => $usuario['rol'],
    'nombre'   => $usuario['nombre'],
    'apellido' => $usuario['apellido'],
]);
