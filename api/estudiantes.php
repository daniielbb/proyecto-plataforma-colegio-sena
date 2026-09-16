<?php
require __DIR__ . '/bootstrap.php';
requireAdmin();

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $inscripciones = $pdo->query('
        SELECT em.id_estudiante_materia, m.nombre_materia, u.nombre, u.apellido
        FROM estudiante_materia em
        JOIN materias m ON m.id_materia = em.id_materia
        JOIN usuarios u ON u.usuario_id = em.id_estudiante
        ORDER BY m.nombre_materia, u.nombre
    ')->fetchAll();
    echo json_encode($inscripciones);
    exit;
}

if ($metodo === 'POST') {
    $data = bodyJson();
    $idEstudiante = (int)($data['id_estudiante'] ?? 0);
    $idMateria    = (int)($data['id_materia'] ?? 0);

    if (!$idEstudiante || !$idMateria) {
        fail(400, 'Selecciona estudiante y materia.');
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO estudiante_materia (id_estudiante, id_materia) VALUES (?, ?)');
        $stmt->execute([$idEstudiante, $idMateria]);
        echo json_encode(['ok' => true, 'id_estudiante_materia' => (int)$pdo->lastInsertId()]);
    } catch (PDOException $e) {
        fail(409, 'Ese estudiante ya está inscrito en esa materia.');
    }
    exit;
}

if ($metodo === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        fail(400, 'Falta el id de la inscripción.');
    }
    $stmt = $pdo->prepare('DELETE FROM estudiante_materia WHERE id_estudiante_materia = ?');
    $stmt->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

fail(405, 'Método no permitido.');
