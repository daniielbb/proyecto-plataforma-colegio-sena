<?php
/**
 * Entrega el archivo físico de un documento solo si el docente tiene acceso.
 * Los archivos viven en /uploads/documentos/ (protegido con .htaccess).
 */
require_once __DIR__ . '/../includes/docente_init.php';

$st = $pdo->prepare('SELECT id_tesis, nombre_documento, ruta_archivo FROM documento WHERE id_documento = ?');
$st->execute([entero($_GET['id'] ?? 0)]);
$d = $st->fetch();

if (!$d || !puede_ver_tesis($pdo, $doc, (int)$d['id_tesis'])) {
    denegar('Este documento no pertenece a tus grupos asignados.');
}
if (!$d['ruta_archivo']) {
    denegar('El documento no tiene un archivo adjunto.');
}

$base = realpath(UPLOADS_DIR);
$ruta = realpath(UPLOADS_DIR . $d['ruta_archivo']);
if (!$base || !$ruta || !str_starts_with($ruta, $base . DIRECTORY_SEPARATOR) || !is_file($ruta)) {
    denegar('No se encontró el archivo en el servidor.');
}

$tipos = ['pdf' => 'application/pdf', 'doc' => 'application/msword',
          'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'txt' => 'text/plain'];
$ext  = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
$tipo = $tipos[$ext] ?? 'application/octet-stream';
$en_linea = in_array($ext, ['pdf', 'png', 'jpg', 'jpeg', 'txt'], true);

header('Content-Type: ' . $tipo);
header('Content-Length: ' . filesize($ruta));
header('Content-Disposition: ' . ($en_linea ? 'inline' : 'attachment') . '; filename="' . rawurlencode(basename($ruta)) . '"');
header('X-Content-Type-Options: nosniff');
readfile($ruta);
