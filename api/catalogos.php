<?php
require __DIR__ . '/bootstrap.php';
requireAdmin();

echo json_encode([
    'cursos' => $pdo->query('SELECT id_curso, nombre_curso FROM cursos ORDER BY nombre_curso')->fetchAll(),
    'grupos' => $pdo->query('SELECT id_grupo, nombre_grupo FROM grupos ORDER BY nombre_grupo')->fetchAll(),
    'profesores' => $pdo->query("SELECT usuario_id, nombre, apellido FROM usuarios WHERE rol = 'profesor' ORDER BY nombre")->fetchAll(),
    'estudiantes' => $pdo->query("SELECT usuario_id, nombre, apellido FROM usuarios WHERE rol = 'estudiante' ORDER BY nombre")->fetchAll(),
    'materias' => $pdo->query('SELECT id_materia, nombre_materia FROM materias ORDER BY nombre_materia')->fetchAll(),
]);
