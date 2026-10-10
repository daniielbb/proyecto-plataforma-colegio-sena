<?php

require_once __DIR__ . '/../../includes/academico.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!usuario_logueado()) { http_response_code(401); echo json_encode(['error' => 'sesion']); exit; }
$stmt = conectar()->prepare('SELECT rol FROM usuarios WHERE usuario_id = ?');
$stmt->execute([$_SESSION['usuario_id']]);
if ($stmt->fetchColumn() !== 'profesor' || !modulo_docente_instalado()) {
    http_response_code(403); echo json_encode(['error' => 'permiso']); exit;
}
session_write_close();   // no bloquear otras peticiones de la misma sesión

echo json_encode(firma_cambios((int) $_SESSION['usuario_id']));
