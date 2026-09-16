<?php
require __DIR__ . '/bootstrap.php';
requireAdmin();

echo json_encode([
    'materias'    => (int)$pdo->query('SELECT COUNT(*) FROM materias')->fetchColumn(),
    'horarios'    => (int)$pdo->query('SELECT COUNT(*) FROM horarios')->fetchColumn(),
    'profesores'  => (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'profesor'")->fetchColumn(),
    'estudiantes' => (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'estudiante'")->fetchColumn(),
    'grupos'      => (int)$pdo->query('SELECT COUNT(*) FROM grupos')->fetchColumn(),
]);
