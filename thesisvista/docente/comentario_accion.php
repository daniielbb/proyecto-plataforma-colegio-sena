<?php

require_once __DIR__ . '/includes/inicio.php';

$volver = (string) ($_POST['volver'] ?? 'comentarios.php');
if (!preg_match('/^[a-z_]+\.php(\?[^\s]*)?(#[a-z]+)?$/i', $volver)) $volver = 'comentarios.php';   // solo páginas del módulo
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirigir($volver);
exigir_csrf($volver);

$id   = (int) ($_POST['id'] ?? 0);
$tipo = $_POST['tipo'] ?? '';
$texto = mb_substr(trim((string) ($_POST['comentario'] ?? '')), 0, 5000);

if (!in_array($tipo, TIPOS_COMENTARIO, true) || $texto === '') {
    mensaje('error', 'Escriba el comentario y elija un tipo válido.');
    redirigir($volver);
}
$stmt = conectar()->prepare('UPDATE retroalimentacion SET tipo = ?, comentario = ?, fecha_edicion = NOW()
                             WHERE id_retro = ? AND id_profesor = ?');
$stmt->execute([$tipo, $texto, $id, $id_docente]);
mensaje($stmt->rowCount() ? 'exito' : 'error', $stmt->rowCount() ? 'Comentario actualizado.' : 'Solo puede editar sus propios comentarios.');
redirigir($volver);
