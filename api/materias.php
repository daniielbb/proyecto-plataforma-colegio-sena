<?php
require __DIR__ . '/bootstrap.php';
requireAdmin();

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $materias = $pdo->query('
        SELECT m.id_materia, m.nombre_materia, m.descripcion, c.nombre_curso
        FROM materias m
        JOIN cursos c ON c.id_curso = m.id_curso
        ORDER BY m.nombre_materia
    ')->fetchAll();
    echo json_encode($materias);
    exit;
}

if ($metodo === 'POST') {
    $data = bodyJson();
    $nombre = trim($data['nombre_materia'] ?? '');
    $idCurso = (int)($data['id_curso'] ?? 0);
    $descripcion = trim($data['descripcion'] ?? '');

    if ($nombre === '' || $idCurso <= 0) {
        fail(400, 'El nombre de la materia y el programa son obligatorios.');
    }

    $stmt = $pdo->prepare('INSERT INTO materias (id_curso, nombre_materia, descripcion) VALUES (?, ?, ?)');
    $stmt->execute([$idCurso, $nombre, $descripcion !== '' ? $descripcion : null]);

    echo json_encode(['ok' => true, 'id_materia' => (int)$pdo->lastInsertId()]);
    exit;
}

if ($metodo === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        fail(400, 'Falta el id de la materia.');
    }
    try {
        $stmt = $pdo->prepare('DELETE FROM materias WHERE id_materia = ?');
        $stmt->execute([$id]);
        echo json_encode(['ok' => true]);
    } catch (PDOException $e) {
        fail(409, 'No se pudo eliminar: la materia tiene horarios, asignaciones o tareas asociadas.');
    }
    exit;
}

fail(405, 'Método no permitido.');
