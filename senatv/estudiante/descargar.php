<?php
/**
 * Entrega un documento del proyecto del estudiante (solo lectura).
 * Los archivos están en /uploads/documentos (bloqueado por .htaccess);
 * solo se sirven si el documento pertenece a la tesis del estudiante en sesión.
 */
require __DIR__ . '/_base.php';

$id  = get_id('id');
$doc = ($tesis && $id) ? est_documento($pdo, (int)$tesis['id_tesis'], $id) : null;

$ruta = null;
if ($doc && $doc['ruta_archivo']) {
    $base = realpath(RUTA_DOCUMENTOS);
    $cand = $base ? realpath($base . DIRECTORY_SEPARATOR . $doc['ruta_archivo']) : false;
    // Evita salir de la carpeta de documentos (../../)
    if ($cand && strpos($cand, $base . DIRECTORY_SEPARATOR) === 0 && is_file($cand)) {
        $ruta = $cand;
    }
}

if (!$ruta) {
    http_response_code(404);
    exit('Documento no disponible.');
}

$tipos = ['pdf' => 'application/pdf', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'];
$ext   = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
$mime  = $tipos[$ext] ?? 'application/octet-stream';
$modo  = isset($tipos[$ext]) ? 'inline' : 'attachment';
$nombre = preg_replace('/[^\w\-. ]+/u', '_', $doc['nombre_documento']) . '.' . $ext;

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($ruta));
header('Content-Disposition: ' . $modo . '; filename="' . $nombre . '"');
header('X-Content-Type-Options: nosniff');
readfile($ruta);
