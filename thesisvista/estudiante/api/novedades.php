<?php

require_once __DIR__ . '/../../includes/academico.php';
require_once __DIR__ . '/../includes/datos.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!usuario_logueado()) { http_response_code(401); echo json_encode(['error' => 'sesion']); exit; }
$stmt = conectar()->prepare('SELECT rol FROM usuarios WHERE usuario_id = ?');
$stmt->execute([$_SESSION['usuario_id']]);
if ($stmt->fetchColumn() !== 'estudiante' || !modulo_docente_instalado() || !modulo_estudiante_instalado()) {
    http_response_code(403); echo json_encode(['error' => 'permiso']); exit;
}
$id_estudiante = (int) $_SESSION['usuario_id'];

// Grupo activo: el de la sesión solo si sigue siendo suyo en estudiante_grupo.
$grupos = grupos_estudiante($id_estudiante);
$grupo = null;
foreach ($grupos as $g) if ((int) $g['id_grupo'] === (int) ($_SESSION['grupo_estudiante'] ?? 0)) $grupo = $g;
$grupo = $grupo ?? ($grupos[0] ?? null);
session_write_close();   // no bloquear otras peticiones de la misma sesión

$avisos = 0;
if ($grupo) {
    $fases = fases_estudiante((int) $grupo['id_grupo'], $id_estudiante);
    $avisos = total_avisos_nuevos(avisos_estudiante($grupo, $id_estudiante, $fases));
}
echo json_encode([
    'firma' => firma_estudiante($id_estudiante, $grupo ? (int) $grupo['id_grupo'] : null),
    'avisos_nuevos' => $avisos,
]);
