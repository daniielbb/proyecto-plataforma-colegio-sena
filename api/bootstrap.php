<?php
/**
 * Todos los endpoints en /api incluyen este archivo primero.
 * No genera HTML: solo JSON.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../config/db.php';

/** Corta la petición con un error JSON. */
function fail(int $status, string $mensaje): void {
    http_response_code($status);
    echo json_encode(['error' => $mensaje]);
    exit;
}

/** Exige que haya un administrador en sesión. */
function requireAdmin(): void {
    if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
        fail(401, 'No autorizado. Inicia sesión como administrador.');
    }
}

/** Lee y decodifica el body JSON de la petición. */
function bodyJson(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
