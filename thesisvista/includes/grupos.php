<?php

require_once __DIR__ . '/../config/database.php';


function estudiantes_de_grupo(int $id_grupo): array
{
    $stmt = conectar()->prepare(
        "SELECT u.usuario_id, u.nombre, u.apellido, u.correo, eg.fecha_asignacion
         FROM estudiante_grupo eg
         JOIN usuarios u ON u.usuario_id = eg.id_estudiante
         WHERE eg.id_grupo = ? AND u.rol = 'estudiante'
         ORDER BY u.nombre, u.apellido"
    );
    $stmt->execute([$id_grupo]);
    return $stmt->fetchAll();
}


function grupos_de_estudiante(int $id_estudiante): array
{
    $stmt = conectar()->prepare(
        'SELECT g.id_grupo, g.nombre_grupo, g.id_curso, c.nombre_curso, c.ficha, eg.fecha_asignacion,
                (SELECT COUNT(*) FROM estudiante_grupo x WHERE x.id_grupo = g.id_grupo) AS total_estudiantes
         FROM estudiante_grupo eg
         JOIN grupos g ON g.id_grupo = eg.id_grupo
         JOIN cursos c ON c.id_curso = g.id_curso
         WHERE eg.id_estudiante = ?
         ORDER BY c.nombre_curso, g.nombre_grupo'
    );
    $stmt->execute([$id_estudiante]);
    return $stmt->fetchAll();
}


function grupos_de_docente(int $id_profesor): array
{
    $stmt = conectar()->prepare(
        'SELECT g.id_grupo, g.nombre_grupo, g.id_curso, c.nombre_curso, c.ficha,
                (SELECT COUNT(*) FROM estudiante_grupo x WHERE x.id_grupo = g.id_grupo) AS total_estudiantes
         FROM docente_curso dc
         JOIN grupos g ON g.id_curso = dc.id_curso
         JOIN cursos c ON c.id_curso = g.id_curso
         WHERE dc.id_profesor = ?
         ORDER BY c.nombre_curso, g.nombre_grupo'
    );
    $stmt->execute([$id_profesor]);
    return $stmt->fetchAll();
}


function estudiante_en_grupo(int $id_estudiante, int $id_grupo): bool
{
    $stmt = conectar()->prepare('SELECT COUNT(*) FROM estudiante_grupo WHERE id_estudiante = ? AND id_grupo = ?');
    $stmt->execute([$id_estudiante, $id_grupo]);
    return $stmt->fetchColumn() > 0;
}
