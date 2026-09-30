<?php
/**
 * Capa de datos del ESTUDIANTE.
 * Todas las consultas están limitadas al estudiante en sesión: nunca se
 * recibe un id_estudiante o id_tesis desde la URL sin validarlo contra él.
 *
 * Tablas usadas: usuarios, estudiante_grupo, grupos, cursos, docente_curso,
 * tesis, fases, tesis_fase, documento, correcciones, comentarios,
 * requisitos_fase, estudiante_incentivo, incentivos.
 */

// ---------------------------------------------------------------------
// Contexto principal: usuario, grupo, curso, proyecto, fases y progreso
// ---------------------------------------------------------------------
function est_contexto(PDO $pdo, int $idEst): array
{
    $ctx = [
        'usuario' => est_usuario($pdo, $idEst),
        'grupos'  => est_grupos($pdo, $idEst),
        'grupo'   => null,
        'tesis'   => null,
        'fases'   => [],
        'progreso'=> ['porcentaje' => 0, 'completadas' => 0, 'total' => 0],
        'fase_actual' => null,
        'novedades'   => ['total' => 0, 'items' => []],
    ];

    if (!$ctx['grupos']) {
        return $ctx; // Sin grupo asignado
    }

    $ctx['tesis'] = est_tesis($pdo, $idEst, array_column($ctx['grupos'], 'id_grupo'));

    // Grupo principal: el de la tesis (si el estudiante pertenece a él) o el más reciente
    $ctx['grupo'] = $ctx['grupos'][0];
    if ($ctx['tesis'] && $ctx['tesis']['id_grupo']) {
        foreach ($ctx['grupos'] as $g) {
            if ((int)$g['id_grupo'] === (int)$ctx['tesis']['id_grupo']) {
                $ctx['grupo'] = $g;
                break;
            }
        }
    }

    if ($ctx['tesis']) {
        $ctx['fases']       = est_fases($pdo, (int)$ctx['tesis']['id_tesis'], (int)$ctx['grupo']['id_curso']);
        $ctx['progreso']    = est_progreso($ctx['fases']);
        $ctx['fase_actual'] = est_fase_actual($ctx['fases']);
        $ctx['novedades']   = est_novedades($pdo, (int)$ctx['tesis']['id_tesis'], $_SESSION['acceso_anterior'] ?? null);
    }
    return $ctx;
}

function est_usuario(PDO $pdo, int $idEst): array
{
    $st = $pdo->prepare("SELECT usuario_id, nombre, apellido, correo, rol, ultimo_acceso
                           FROM usuarios WHERE usuario_id = ? AND rol = 'estudiante'");
    $st->execute([$idEst]);
    return $st->fetch() ?: [];
}

/** Grupos activos del estudiante con su curso (más reciente primero). */
function est_grupos(PDO $pdo, int $idEst): array
{
    $st = $pdo->prepare("SELECT g.id_grupo, g.nombre_grupo, g.id_curso, c.nombre_curso, c.ficha,
                                eg.rol_grupo, eg.estado, eg.fecha_asignacion
                           FROM estudiante_grupo eg
                           JOIN grupos g ON g.id_grupo = eg.id_grupo
                           JOIN cursos c ON c.id_curso = g.id_curso
                          WHERE eg.id_estudiante = ? AND eg.estado = 'Activo'
                          ORDER BY eg.fecha_asignacion DESC, eg.id_estudiante_grupo DESC");
    $st->execute([$idEst]);
    return $st->fetchAll();
}

/** Proyecto (tesis) del estudiante, con su docente director. */
function est_tesis(PDO $pdo, int $idEst, array $idsGrupos): ?array
{
    $sql = "SELECT t.*, p.nombre AS prof_nombre, p.apellido AS prof_apellido, p.correo AS prof_correo
              FROM tesis t
              JOIN usuarios p ON p.usuario_id = t.id_profesor
             WHERE t.id_estudiante = ?
             ORDER BY t.fecha_registro DESC, t.id_tesis DESC LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([$idEst]);
    $t = $st->fetch();

    if (!$t && PROYECTO_COMPARTIDO_POR_GRUPO && $idsGrupos) {
        $in = implode(',', array_fill(0, count($idsGrupos), '?'));
        $st = $pdo->prepare("SELECT t.*, p.nombre AS prof_nombre, p.apellido AS prof_apellido, p.correo AS prof_correo
                               FROM tesis t JOIN usuarios p ON p.usuario_id = t.id_profesor
                              WHERE t.id_grupo IN ($in)
                              ORDER BY t.fecha_registro DESC LIMIT 1");
        $st->execute(array_map('intval', $idsGrupos));
        $t = $st->fetch();
    }
    return $t ?: null;
}

/**
 * Fases del curso con el estado del proyecto en cada una (tesis_fase)
 * y los contadores de documentos, correcciones y comentarios.
 */
function est_fases(PDO $pdo, int $idTesis, int $idCurso): array
{
    $sql = "SELECT f.id_fase, f.nombre_fase, f.descripcion, f.ejemplo, f.orden, f.duracion_dias,
                   tf.id_tesis_fase, COALESCE(tf.estado, 'Pendiente') AS estado_bd,
                   tf.fecha_inicio, tf.fecha_limite, tf.fecha_completada, tf.observaciones,
                   (SELECT COUNT(*) FROM documento d WHERE d.id_tesis = ? AND d.id_fase = f.id_fase) AS n_documentos,
                   (SELECT COUNT(*) FROM correcciones c WHERE c.id_tesis = ? AND c.id_fase = f.id_fase) AS n_correcciones,
                   (SELECT COUNT(*) FROM comentarios cm JOIN usuarios u ON u.usuario_id = cm.id_usuario
                     WHERE cm.id_tesis = ? AND cm.id_fase = f.id_fase AND u.rol = 'profesor') AS n_comentarios,
                   (SELECT c2.estado FROM correcciones c2 WHERE c2.id_tesis = ? AND c2.id_fase = f.id_fase
                     ORDER BY c2.fecha DESC, c2.id_correccion DESC LIMIT 1) AS ultima_correccion_estado
              FROM fases f
              LEFT JOIN tesis_fase tf ON tf.id_fase = f.id_fase AND tf.id_tesis = ?
             WHERE f.id_curso = ? AND f.activa = 1
             ORDER BY f.orden, f.id_fase";
    $st = $pdo->prepare($sql);
    $st->execute([$idTesis, $idTesis, $idTesis, $idTesis, $idTesis, $idCurso]);
    $fases = $st->fetchAll();

    foreach ($fases as &$f) {
        $f['estado'] = estado_fase_efectivo($f);
        // Fecha límite: la registrada por el docente o, si no existe, inicio + duración sugerida
        $f['fecha_estimada'] = false;
        $f['fecha_entrega']  = $f['fecha_limite'];
        if (!$f['fecha_entrega'] && $f['fecha_inicio'] && $f['duracion_dias']) {
            $f['fecha_entrega']  = date('Y-m-d', strtotime($f['fecha_inicio'] . ' +' . (int)$f['duracion_dias'] . ' days'));
            $f['fecha_estimada'] = true;
        }
    }
    return $fases;
}

/**
 * Estado que se muestra al estudiante. Se basa en tesis_fase.estado y agrega
 * "Requiere corrección" cuando la última corrección de la fase lo indica.
 */
function estado_fase_efectivo(array $f): string
{
    if ($f['estado_bd'] !== 'Completada' && ($f['ultima_correccion_estado'] ?? null) === 'Requiere ajustes') {
        return 'Requiere corrección';
    }
    return $f['estado_bd'];
}

/** Progreso = fases completadas / fases activas del curso (misma fórmula de vista_progreso_tesis). */
function est_progreso(array $fases): array
{
    $total = count($fases);
    $comp  = count(array_filter($fases, fn($f) => $f['estado_bd'] === 'Completada'));
    return [
        'total'       => $total,
        'completadas' => $comp,
        'porcentaje'  => $total ? (int)round($comp / $total * 100) : 0,
    ];
}

/** Fase actual: la primera en curso; si no hay, la primera pendiente; si todo está completo, la última. */
function est_fase_actual(array $fases): ?array
{
    foreach ($fases as $f) {
        if (in_array($f['estado_bd'], ['En progreso', 'Atrasada'], true)) return $f + ['todas_completas' => false];
    }
    foreach ($fases as $f) {
        if ($f['estado_bd'] === 'Pendiente') return $f + ['todas_completas' => false];
    }
    return $fases ? end($fases) + ['todas_completas' => true] : null;
}

/** Devuelve una fase del curso SOLO si pertenece al proyecto del estudiante. */
function est_buscar_fase(array $fases, int $idFase): ?array
{
    foreach ($fases as $i => $f) {
        if ((int)$f['id_fase'] === $idFase) {
            $f['anterior'] = $fases[$i - 1] ?? null;
            $f['siguiente'] = $fases[$i + 1] ?? null;
            return $f;
        }
    }
    return null;
}

// ---------------------------------------------------------------------
// Documentos
// ---------------------------------------------------------------------
function est_documentos(PDO $pdo, int $idTesis, ?int $idFase = null): array
{
    $sql = "SELECT d.id_documento, d.id_fase, d.nombre_documento, d.tipo_documento, d.estado,
                   d.ruta_archivo, d.fecha_subida, d.fecha_modificacion,
                   f.nombre_fase, f.orden,
                   c.id_correccion, c.estado AS corr_estado, c.fecha AS corr_fecha,
                   p.nombre AS revisor_nombre, p.apellido AS revisor_apellido
              FROM documento d
              LEFT JOIN fases f ON f.id_fase = d.id_fase
              LEFT JOIN correcciones c ON c.id_correccion = (
                     SELECT c2.id_correccion FROM correcciones c2
                      WHERE c2.id_documento = d.id_documento
                      ORDER BY c2.fecha DESC, c2.id_correccion DESC LIMIT 1)
              LEFT JOIN usuarios p ON p.usuario_id = c.id_profesor
             WHERE d.id_tesis = ?";
    $params = [$idTesis];
    if ($idFase !== null) {
        $sql .= ' AND d.id_fase = ?';
        $params[] = $idFase;
    }
    $sql .= ' ORDER BY (f.orden IS NULL), f.orden, d.fecha_subida DESC, d.id_documento DESC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Un documento, validando que pertenezca al proyecto del estudiante. */
function est_documento(PDO $pdo, int $idTesis, int $idDoc): ?array
{
    $st = $pdo->prepare('SELECT * FROM documento WHERE id_documento = ? AND id_tesis = ?');
    $st->execute([$idDoc, $idTesis]);
    return $st->fetch() ?: null;
}

// ---------------------------------------------------------------------
// Correcciones (tabla nueva `correcciones`)
// ---------------------------------------------------------------------
function est_correcciones(PDO $pdo, int $idTesis, ?int $idFase = null, ?int $limite = null): array
{
    $sql = "SELECT c.id_correccion, c.id_fase, c.id_documento, c.estado, c.observacion, c.calificacion, c.fecha,
                   p.nombre AS prof_nombre, p.apellido AS prof_apellido,
                   f.nombre_fase, f.orden, d.nombre_documento
              FROM correcciones c
              JOIN usuarios p ON p.usuario_id = c.id_profesor
              LEFT JOIN fases f ON f.id_fase = c.id_fase
              LEFT JOIN documento d ON d.id_documento = c.id_documento
             WHERE c.id_tesis = ?";
    $params = [$idTesis];
    if ($idFase !== null) {
        $sql .= ' AND c.id_fase = ?';
        $params[] = $idFase;
    }
    $sql .= ' ORDER BY c.fecha DESC, c.id_correccion DESC';
    if ($limite) {
        $sql .= ' LIMIT ' . (int)$limite;
    }
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function est_correccion(PDO $pdo, int $idTesis, int $idCorr): ?array
{
    $st = $pdo->prepare("SELECT c.*, p.nombre AS prof_nombre, p.apellido AS prof_apellido,
                                f.nombre_fase, f.orden, d.nombre_documento, d.tipo_documento, d.fecha_subida
                           FROM correcciones c
                           JOIN usuarios p ON p.usuario_id = c.id_profesor
                           LEFT JOIN fases f ON f.id_fase = c.id_fase
                           LEFT JOIN documento d ON d.id_documento = c.id_documento
                          WHERE c.id_correccion = ? AND c.id_tesis = ?");
    $st->execute([$idCorr, $idTesis]);
    return $st->fetch() ?: null;
}

// ---------------------------------------------------------------------
// Comentarios del docente (solo autores con rol 'profesor')
// ---------------------------------------------------------------------
/** $idFase: null = todos, 0 = solo generales (sin fase), >0 = de esa fase */
function est_comentarios(PDO $pdo, int $idTesis, ?int $idFase = null, ?int $limite = null): array
{
    $sql = "SELECT cm.id_comentario, cm.comentario, cm.fecha, cm.id_fase, cm.id_documento,
                   u.nombre AS prof_nombre, u.apellido AS prof_apellido,
                   f.nombre_fase, f.orden, d.nombre_documento
              FROM comentarios cm
              JOIN usuarios u ON u.usuario_id = cm.id_usuario AND u.rol = 'profesor'
              LEFT JOIN fases f ON f.id_fase = cm.id_fase
              LEFT JOIN documento d ON d.id_documento = cm.id_documento
             WHERE cm.id_tesis = ?";
    $params = [$idTesis];
    if ($idFase === 0) {
        $sql .= ' AND cm.id_fase IS NULL';
    } elseif ($idFase !== null) {
        $sql .= ' AND cm.id_fase = ?';
        $params[] = $idFase;
    }
    $sql .= ' ORDER BY cm.fecha DESC, cm.id_comentario DESC';
    if ($limite) {
        $sql .= ' LIMIT ' . (int)$limite;
    }
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

// ---------------------------------------------------------------------
// Requisitos de la fase (tabla nueva `requisitos_fase`)
// ---------------------------------------------------------------------
function est_requisitos(PDO $pdo, int $idFase, int $idTesis): array
{
    $st = $pdo->prepare("SELECT r.id_requisito, r.descripcion, r.obligatorio, r.id_tesis,
                                u.nombre AS prof_nombre, u.apellido AS prof_apellido
                           FROM requisitos_fase r
                           JOIN usuarios u ON u.usuario_id = r.id_profesor
                          WHERE r.id_fase = ? AND (r.id_tesis IS NULL OR r.id_tesis = ?)
                          ORDER BY r.orden, r.id_requisito");
    $st->execute([$idFase, $idTesis]);
    return $st->fetchAll();
}

// ---------------------------------------------------------------------
// Grupo
// ---------------------------------------------------------------------
function est_integrantes(PDO $pdo, int $idGrupo): array
{
    $st = $pdo->prepare("SELECT u.usuario_id, u.nombre, u.apellido, eg.rol_grupo, eg.estado, eg.fecha_asignacion,
                                c.nombre_curso, c.ficha
                           FROM estudiante_grupo eg
                           JOIN usuarios u ON u.usuario_id = eg.id_estudiante AND u.rol = 'estudiante'
                           JOIN grupos g   ON g.id_grupo = eg.id_grupo
                           JOIN cursos c   ON c.id_curso = g.id_curso
                          WHERE eg.id_grupo = ?
                          ORDER BY (eg.rol_grupo IS NULL), u.nombre, u.apellido");
    $st->execute([$idGrupo]);
    return $st->fetchAll();
}

/** Docentes asignados al curso (docente_curso). */
function est_docentes_curso(PDO $pdo, int $idCurso): array
{
    $st = $pdo->prepare("SELECT u.usuario_id, u.nombre, u.apellido, u.correo
                           FROM docente_curso dc JOIN usuarios u ON u.usuario_id = dc.id_profesor
                          WHERE dc.id_curso = ? AND u.rol = 'profesor'
                          ORDER BY u.nombre, u.apellido");
    $st->execute([$idCurso]);
    return $st->fetchAll();
}

// ---------------------------------------------------------------------
// Incentivos / logros
// ---------------------------------------------------------------------
function est_incentivos(PDO $pdo, int $idEst): array
{
    $st = $pdo->prepare("SELECT i.nombre, i.descripcion, i.icono, ei.fecha_obtenido
                           FROM estudiante_incentivo ei JOIN incentivos i ON i.id_incentivo = ei.id_incentivo
                          WHERE ei.id_estudiante = ?
                          ORDER BY ei.fecha_obtenido DESC");
    $st->execute([$idEst]);
    return $st->fetchAll();
}

// ---------------------------------------------------------------------
// Novedades (notificaciones): correcciones y comentarios desde el acceso anterior
// ---------------------------------------------------------------------
function est_novedades(PDO $pdo, int $idTesis, ?string $desde): array
{
    $desde = $desde ?: date('Y-m-d H:i:s', strtotime('-' . DIAS_NOVEDAD_POR_DEFECTO . ' days'));
    $sql = "(SELECT 'correccion' AS tipo, c.id_correccion AS id, c.estado AS detalle, c.fecha,
                    COALESCE(d.nombre_documento, f.nombre_fase, 'Proyecto') AS referencia
               FROM correcciones c
               LEFT JOIN documento d ON d.id_documento = c.id_documento
               LEFT JOIN fases f ON f.id_fase = c.id_fase
              WHERE c.id_tesis = ? AND c.fecha > ?)
            UNION ALL
            (SELECT 'comentario', cm.id_comentario, LEFT(cm.comentario, 80), cm.fecha,
                    COALESCE(f.nombre_fase, 'Comentario general')
               FROM comentarios cm
               JOIN usuarios u ON u.usuario_id = cm.id_usuario AND u.rol = 'profesor'
               LEFT JOIN fases f ON f.id_fase = cm.id_fase
              WHERE cm.id_tesis = ? AND cm.fecha > ?)
            ORDER BY fecha DESC";
    $st = $pdo->prepare($sql);
    $st->execute([$idTesis, $desde, $idTesis, $desde]);
    $items = $st->fetchAll();
    return ['total' => count($items), 'items' => array_slice($items, 0, 6)];
}

/**
 * Historial del proyecto: combina inicio/cierre de fases, documentos,
 * correcciones y comentarios en una línea de tiempo (más reciente primero).
 */
function est_historial(PDO $pdo, int $idTesis, array $fases): array
{
    $ev = [];
    foreach ($fases as $f) {
        if ($f['fecha_inicio'])     $ev[] = ['fecha' => $f['fecha_inicio'], 'tipo' => 'fase', 'titulo' => 'Inicio de fase ' . $f['orden'], 'texto' => $f['nombre_fase']];
        if ($f['fecha_completada']) $ev[] = ['fecha' => $f['fecha_completada'], 'tipo' => 'completada', 'titulo' => 'Fase ' . $f['orden'] . ' completada', 'texto' => $f['nombre_fase']];
    }
    foreach (est_documentos($pdo, $idTesis) as $d) {
        $ev[] = ['fecha' => $d['fecha_subida'], 'tipo' => 'documento', 'titulo' => 'Documento registrado', 'texto' => $d['nombre_documento']];
    }
    foreach (est_correcciones($pdo, $idTesis) as $c) {
        $ev[] = ['fecha' => $c['fecha'], 'tipo' => 'correccion', 'titulo' => 'Corrección: ' . $c['estado'],
                 'texto' => ($c['nombre_documento'] ?: $c['nombre_fase'] ?: 'Proyecto') . ' · ' . $c['prof_nombre'] . ' ' . $c['prof_apellido'],
                 'id' => $c['id_correccion']];
    }
    foreach (est_comentarios($pdo, $idTesis) as $c) {
        $ev[] = ['fecha' => $c['fecha'], 'tipo' => 'comentario', 'titulo' => 'Comentario del docente',
                 'texto' => $c['prof_nombre'] . ' ' . $c['prof_apellido'] . ($c['nombre_fase'] ? ' · ' . $c['nombre_fase'] : '')];
    }
    usort($ev, fn($a, $b) => strcmp($b['fecha'], $a['fecha']));
    return $ev;
}
