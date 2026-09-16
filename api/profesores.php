<?php
require __DIR__ . '/bootstrap.php';
requireAdmin();

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $asignaciones = $pdo->query('
        SELECT mp.id_materia_profesor, m.nombre_materia, g.nombre_grupo,
               u.nombre, u.apellido
        FROM materia_profesor mp
        JOIN materias m ON m.id_materia = mp.id_materia
        JOIN grupos g ON g.id_grupo = mp.id_grupo
        JOIN usuarios u ON u.usuario_id = mp.id_profesor
        ORDER BY m.nombre_materia, g.nombre_grupo
    ')->fetchAll();
    echo json_encode($asignaciones);
    exit;
}

if ($metodo === 'POST') {
    $data = bodyJson();
    $idMateria  = (int)($data['id_materia'] ?? 0);
    $idProfesor = (int)($data['id_profesor'] ?? 0);
    $idGrupo    = (int)($data['id_grupo'] ?? 0);

    if (!$idMateria || !$idProfesor || !$idGrupo) {
        fail(400, 'Selecciona materia, profesor y grupo.');
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO materia_profesor (id_materia, id_profesor, id_grupo) VALUES (?, ?, ?)');
        $stmt->execute([$idMateria, $idProfesor, $idGrupo]);
        echo json_encode(['ok' => true, 'id_materia_profesor' => (int)$pdo->lastInsertId()]);
    } catch (PDOException $e) {
        fail(409, 'Ese profesor ya está asignado a esa materia y grupo.');
    }
    exit;
}

if ($metodo === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        fail(400, 'Falta el id de la asignación.');
    }
    $stmt = $pdo->prepare('DELETE FROM materia_profesor WHERE id_materia_profesor = ?');
    $stmt->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

fail(405, 'Método no permitido.');
