<?php
require __DIR__ . '/bootstrap.php';
requireAdmin();

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $horarios = $pdo->query('
        SELECT h.id_horario, h.dia_semana, h.hora_inicio, h.hora_fin, h.salon,
               m.nombre_materia, g.nombre_grupo
        FROM horarios h
        JOIN materias m ON m.id_materia = h.id_materia
        JOIN grupos g ON g.id_grupo = h.id_grupo
        ORDER BY FIELD(h.dia_semana,"Lunes","Martes","Miércoles","Jueves","Viernes","Sábado"), h.hora_inicio
    ')->fetchAll();
    echo json_encode($horarios);
    exit;
}

if ($metodo === 'POST') {
    $data = bodyJson();
    $idMateria = (int)($data['id_materia'] ?? 0);
    $idGrupo   = (int)($data['id_grupo'] ?? 0);
    $dia       = $data['dia_semana'] ?? '';
    $inicio    = $data['hora_inicio'] ?? '';
    $fin       = $data['hora_fin'] ?? '';
    $salon     = trim($data['salon'] ?? '');

    $diasValidos = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];

    if (!$idMateria || !$idGrupo || !in_array($dia, $diasValidos, true) || !$inicio || !$fin) {
        fail(400, 'Completa materia, grupo, día y horas.');
    }
    if ($fin <= $inicio) {
        fail(400, 'La hora final debe ser posterior a la hora de inicio.');
    }

    $stmt = $pdo->prepare('INSERT INTO horarios (id_materia, id_grupo, dia_semana, hora_inicio, hora_fin, salon) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$idMateria, $idGrupo, $dia, $inicio, $fin, $salon !== '' ? $salon : null]);

    echo json_encode(['ok' => true, 'id_horario' => (int)$pdo->lastInsertId()]);
    exit;
}

if ($metodo === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        fail(400, 'Falta el id del horario.');
    }
    $stmt = $pdo->prepare('DELETE FROM horarios WHERE id_horario = ?');
    $stmt->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

fail(405, 'Método no permitido.');
