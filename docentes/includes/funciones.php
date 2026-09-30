<?php
/**
 * Consultas y utilidades compartidas del módulo docente.
 *
 * REGLA DE ACCESO (docente_curso):
 *   Un grupo está asignado al docente si el curso del grupo aparece en
 *   docente_curso para ese docente. Un proyecto (tesis) es accesible si su
 *   grupo es accesible o si el docente es el profesor asignado (tesis.id_profesor).
 */

/* =====================================================================
   Etiquetas y estados
   ===================================================================== */

// documento.estado  => etiqueta visible
const ESTADOS_DOCUMENTO = [
    'Entregado'        => 'Pendiente de revisión',
    'En revisión'      => 'En revisión',
    'Requiere ajustes' => 'Requiere correcciones',
    'Corregido'        => 'Corregido',
    'Aprobado'         => 'Aprobado',
];

// correcciones.estado => etiqueta visible
const ESTADOS_REVISION = [
    'En revisión'      => 'En revisión',
    'Requiere ajustes' => 'Requiere correcciones',
    'Corregido'        => 'Corregido',
    'Aprobada'         => 'Aprobado',
];

// tesis_fase.estado
const ESTADOS_FASE = ['Pendiente', 'En progreso', 'Completada', 'Atrasada'];

// tesis.estado
const ESTADOS_TESIS = ['Borrador', 'En revisión', 'Aprobada', 'Rechazada'];

/** Estado de revisión (correcciones) => estado del documento. */
function estado_rev_a_doc(string $estado): string
{
    return $estado === 'Aprobada' ? 'Aprobado' : $estado;
}

/** Etiqueta con color para cualquier estado. */
function badge(?string $estado): string
{
    if ($estado === null || $estado === '') {
        return '<span class="badge b-neutro">Sin estado</span>';
    }
    $etiqueta = ESTADOS_DOCUMENTO[$estado] ?? ESTADOS_REVISION[$estado] ?? $estado;
    $clase = match ($estado) {
        'Entregado', 'Pendiente', 'Borrador'           => 'b-pendiente',
        'En revisión', 'En progreso'                   => 'b-proceso',
        'Requiere ajustes', 'Atrasada', 'Rechazada'    => 'b-alerta',
        'Corregido'                                    => 'b-corregido',
        'Aprobado', 'Aprobada', 'Completada'           => 'b-ok',
        default                                        => 'b-neutro',
    };
    return '<span class="badge ' . $clase . '">' . e($etiqueta) . '</span>';
}

/** Símbolo del estado de una fase. */
function simbolo_fase(string $estado): string
{
    return match ($estado) {
        'Completada'  => '✓',
        'En progreso' => '◐',
        'Atrasada'    => '!',
        default       => '○',
    };
}

function fecha(?string $f): string
{
    return $f ? date('d/m/Y', strtotime($f)) : '—';
}

function fecha_hora(?string $f): string
{
    if (!$f) {
        return '—';
    }
    // Campos DATE (sin hora) se muestran solo con la fecha
    return (strlen($f) === 10 || str_ends_with($f, '00:00:00')) ? fecha($f) : date('d/m/Y H:i', strtotime($f));
}

/** Placeholders "?,?,?" para un IN (...). Nunca devuelve un IN vacío. */
function marcadores(array &$ids): string
{
    if (!$ids) {
        $ids = [0];
    }
    return implode(',', array_fill(0, count($ids), '?'));
}

function entero($valor): int
{
    return max(0, (int)$valor);
}

/* =====================================================================
   Permisos
   ===================================================================== */

/** IDs de los grupos asignados al docente (por docente_curso). */
function ids_grupos_docente(PDO $pdo, int $doc): array
{
    static $cache = [];
    if (!isset($cache[$doc])) {
        $st = $pdo->prepare('SELECT g.id_grupo
                               FROM grupos g
                               JOIN docente_curso dc ON dc.id_curso = g.id_curso
                              WHERE dc.id_profesor = ?');
        $st->execute([$doc]);
        $cache[$doc] = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }
    return $cache[$doc];
}

/** IDs de los proyectos (tesis) que el docente puede consultar. */
function ids_tesis_docente(PDO $pdo, int $doc): array
{
    static $cache = [];
    if (!isset($cache[$doc])) {
        $st = $pdo->prepare('SELECT t.id_tesis
                               FROM tesis t
                               LEFT JOIN grupos g ON g.id_grupo = t.id_grupo
                              WHERE t.id_profesor = ?
                                 OR g.id_curso IN (SELECT id_curso FROM docente_curso WHERE id_profesor = ?)');
        $st->execute([$doc, $doc]);
        $cache[$doc] = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }
    return $cache[$doc];
}

function puede_ver_grupo(PDO $pdo, int $doc, int $id_grupo): bool
{
    return in_array($id_grupo, ids_grupos_docente($pdo, $doc), true);
}

function puede_ver_tesis(PDO $pdo, int $doc, int $id_tesis): bool
{
    return in_array($id_tesis, ids_tesis_docente($pdo, $doc), true);
}

/* =====================================================================
   Proyectos (tesis) y fases
   ===================================================================== */

/** Datos completos de un proyecto o null si el docente no tiene acceso. */
function obtener_tesis(PDO $pdo, int $doc, int $id_tesis): ?array
{
    if (!puede_ver_tesis($pdo, $doc, $id_tesis)) {
        return null;
    }
    $st = $pdo->prepare(
        "SELECT t.*,
                ue.nombre AS est_nombre, ue.apellido AS est_apellido, ue.correo AS est_correo,
                up.nombre AS prof_nombre, up.apellido AS prof_apellido,
                g.nombre_grupo, g.id_curso AS curso_grupo,
                COALESCE(g.id_curso, (SELECT dc.id_curso FROM docente_curso dc
                                       WHERE dc.id_profesor = t.id_profesor
                                       ORDER BY dc.id_curso LIMIT 1)) AS id_curso
           FROM tesis t
           JOIN usuarios ue ON ue.usuario_id = t.id_estudiante
           JOIN usuarios up ON up.usuario_id = t.id_profesor
           LEFT JOIN grupos g ON g.id_grupo = t.id_grupo
          WHERE t.id_tesis = ?"
    );
    $st->execute([$id_tesis]);
    $t = $st->fetch();
    if ($t) {
        $c = $pdo->prepare('SELECT nombre_curso, ficha FROM cursos WHERE id_curso = ?');
        $c->execute([$t['id_curso']]);
        $curso = $c->fetch() ?: ['nombre_curso' => null, 'ficha' => null];
        $t += $curso;
    }
    return $t ?: null;
}

/**
 * Fases activas del curso con el estado que tienen para el proyecto.
 * Si una fase aún no tiene registro en tesis_fase se considera "Pendiente".
 */
function fases_de_tesis(PDO $pdo, int $id_tesis, ?int $id_curso): array
{
    if (!$id_curso) {
        return [];
    }
    $st = $pdo->prepare(
        "SELECT f.id_fase, f.nombre_fase, f.descripcion, f.ejemplo, f.orden, f.duracion_dias,
                tf.id_tesis_fase,
                COALESCE(tf.estado, 'Pendiente') AS estado,
                tf.fecha_inicio, tf.fecha_limite, tf.fecha_completada, tf.observaciones,
                (SELECT COUNT(*) FROM documento d
                  WHERE d.id_tesis = ? AND d.id_fase = f.id_fase) AS total_documentos
           FROM fases f
           LEFT JOIN tesis_fase tf ON tf.id_fase = f.id_fase AND tf.id_tesis = ?
          WHERE f.id_curso = ? AND f.activa = 1
          ORDER BY f.orden, f.id_fase"
    );
    $st->execute([$id_tesis, $id_tesis, $id_curso]);
    return $st->fetchAll();
}

/**
 * Progreso calculado dinámicamente: fases completadas / fases activas del curso.
 * "Fase actual" = primera fase En progreso o Atrasada; si no hay, la primera
 * que no esté completada.
 */
function resumen_progreso(array $fases): array
{
    $total = count($fases);
    $completadas = 0;
    $actual = null;
    $primera_pendiente = null;
    foreach ($fases as $f) {
        if ($f['estado'] === 'Completada') {
            $completadas++;
        } elseif (in_array($f['estado'], ['En progreso', 'Atrasada'], true)) {
            $actual ??= $f;
        } else {
            $primera_pendiente ??= $f;
        }
    }
    return [
        'total'       => $total,
        'completadas' => $completadas,
        'pendientes'  => $total - $completadas,
        'porcentaje'  => $total ? (int)round($completadas / $total * 100) : 0,
        'actual'      => $actual ?? $primera_pendiente,
        'en_curso'    => $actual !== null,
    ];
}

/** Barra de progreso HTML. */
function barra_progreso(int $porcentaje, bool $grande = false): string
{
    $p = max(0, min(100, $porcentaje));
    return '<div class="progreso' . ($grande ? ' progreso-grande' : '') . '" role="progressbar" aria-valuenow="' . $p
         . '" aria-valuemin="0" aria-valuemax="100"><div class="progreso-relleno" style="width:' . $p . '%"></div></div>';
}

/** Proyectos (tesis) de un grupo que el docente puede ver, con su progreso. */
function proyectos_de_grupo(PDO $pdo, int $doc, int $id_grupo, int $id_curso): array
{
    $ids = ids_tesis_docente($pdo, $doc);
    $in  = marcadores($ids);
    $st = $pdo->prepare(
        "SELECT t.id_tesis, t.titulo, t.estado, t.id_profesor,
                up.nombre AS prof_nombre, up.apellido AS prof_apellido
           FROM tesis t
           JOIN usuarios up ON up.usuario_id = t.id_profesor
          WHERE t.id_grupo = ? AND t.id_tesis IN ($in)
          ORDER BY t.id_tesis"
    );
    $st->execute(array_merge([$id_grupo], $ids));
    $proyectos = $st->fetchAll();
    foreach ($proyectos as &$p) {
        $p['fases']    = fases_de_tesis($pdo, (int)$p['id_tesis'], $id_curso);
        $p['progreso'] = resumen_progreso($p['fases']);
    }
    return $proyectos;
}

/* =====================================================================
   Actividad y correcciones
   ===================================================================== */

/** Últimas actividades de un conjunto de proyectos. */
function ultimas_actividades(PDO $pdo, array $ids_tesis, int $limite = 8): array
{
    if (!$ids_tesis) {
        return [];
    }
    $in = marcadores($ids_tesis);
    $sql = "
        SELECT a.*, t.titulo FROM (
            SELECT 'documento' AS tipo, d.id_tesis, COALESCE(d.fecha_modificacion, d.fecha_subida) AS fecha,
                   CONCAT('Documento entregado: ', d.nombre_documento) AS texto, f.nombre_fase
              FROM documento d LEFT JOIN fases f ON f.id_fase = d.id_fase
             WHERE d.id_tesis IN ($in)
            UNION ALL
            SELECT 'comentario', c.id_tesis, c.fecha,
                   CONCAT(u.nombre, ' ', u.apellido, ' comentó'), f.nombre_fase
              FROM comentarios c JOIN usuarios u ON u.usuario_id = c.id_usuario
              LEFT JOIN fases f ON f.id_fase = c.id_fase
             WHERE c.id_tesis IN ($in)
            UNION ALL
            SELECT 'revision', r.id_tesis, r.fecha,
                   CONCAT('Revisión registrada: ', r.estado), f.nombre_fase
              FROM correcciones r LEFT JOIN fases f ON f.id_fase = r.id_fase
             WHERE r.id_tesis IN ($in)
            UNION ALL
            SELECT 'fase', tf.id_tesis, tf.fecha_inicio, 'Inició la fase', f.nombre_fase
              FROM tesis_fase tf JOIN fases f ON f.id_fase = tf.id_fase
             WHERE tf.fecha_inicio IS NOT NULL AND tf.id_tesis IN ($in)
            UNION ALL
            SELECT 'fase', tf.id_tesis, tf.fecha_completada, 'Completó la fase', f.nombre_fase
              FROM tesis_fase tf JOIN fases f ON f.id_fase = tf.id_fase
             WHERE tf.fecha_completada IS NOT NULL AND tf.id_tesis IN ($in)
        ) a
        JOIN tesis t ON t.id_tesis = a.id_tesis
        ORDER BY a.fecha DESC
        LIMIT " . (int)$limite;
    $st = $pdo->prepare($sql);
    $st->execute(array_merge($ids_tesis, $ids_tesis, $ids_tesis, $ids_tesis, $ids_tesis));
    return $st->fetchAll();
}

/**
 * Correcciones pendientes (esperando que el estudiante corrija):
 *  A) Documentos con estado "Requiere ajustes".
 *  B) Revisiones sin documento cuyo último estado para esa fase es "Requiere ajustes".
 */
function correcciones_pendientes(PDO $pdo, int $doc): array
{
    $ids = ids_tesis_docente($pdo, $doc);
    $in  = marcadores($ids);
    $sql = "
        SELECT 'documento' AS origen, d.id_documento, d.id_tesis, d.id_fase, d.nombre_documento,
               d.estado, t.titulo, g.id_grupo, g.nombre_grupo, f.nombre_fase,
               (SELECT MAX(r.fecha) FROM correcciones r WHERE r.id_documento = d.id_documento) AS ultima_revision
          FROM documento d
          JOIN tesis t ON t.id_tesis = d.id_tesis
          LEFT JOIN grupos g ON g.id_grupo = t.id_grupo
          LEFT JOIN fases f ON f.id_fase = d.id_fase
         WHERE d.estado = 'Requiere ajustes' AND d.id_tesis IN ($in)
        UNION ALL
        SELECT 'fase', NULL, r.id_tesis, r.id_fase, NULL,
               r.estado, t.titulo, g.id_grupo, g.nombre_grupo, f.nombre_fase, r.fecha
          FROM correcciones r
          JOIN tesis t ON t.id_tesis = r.id_tesis
          LEFT JOIN grupos g ON g.id_grupo = t.id_grupo
          LEFT JOIN fases f ON f.id_fase = r.id_fase
         WHERE r.id_documento IS NULL AND r.estado = 'Requiere ajustes' AND r.id_tesis IN ($in)
           AND NOT EXISTS (SELECT 1 FROM correcciones r2
                            WHERE r2.id_tesis = r.id_tesis AND r2.id_fase <=> r.id_fase
                              AND (r2.fecha > r.fecha OR (r2.fecha = r.fecha AND r2.id_correccion > r.id_correccion)))
        ORDER BY ultima_revision DESC";
    $st = $pdo->prepare($sql);
    $st->execute(array_merge($ids, $ids));
    return $st->fetchAll();
}

/**
 * Historial de revisiones. Cada revisión (correcciones) se une con el
 * comentario que el docente guardó en el mismo formulario (misma fecha).
 * Los comentarios sueltos también aparecen como filas propias.
 */
function historial_revisiones(PDO $pdo, array $ids_tesis, ?int $id_fase = null): array
{
    if (!$ids_tesis) {
        return [];
    }
    $in = marcadores($ids_tesis);
    $filtro_r = $id_fase ? ' AND r.id_fase = ?' : '';
    $filtro_c = $id_fase ? ' AND c.id_fase = ?' : '';
    $sql = "
        SELECT 'revision' AS tipo, r.id_correccion, r.id_tesis, r.id_fase, r.id_documento, r.fecha,
               r.estado, r.observacion, r.recomendacion, r.calificacion, r.id_profesor AS id_autor,
               c.comentario, u.nombre, u.apellido, u.rol, f.nombre_fase, f.orden, d.nombre_documento, t.titulo
          FROM correcciones r
          JOIN usuarios u ON u.usuario_id = r.id_profesor
          JOIN tesis t ON t.id_tesis = r.id_tesis
          LEFT JOIN fases f ON f.id_fase = r.id_fase
          LEFT JOIN documento d ON d.id_documento = r.id_documento
          LEFT JOIN comentarios c ON c.id_tesis = r.id_tesis AND c.id_fase <=> r.id_fase
                                 AND c.id_documento <=> r.id_documento AND c.id_usuario = r.id_profesor
                                 AND c.fecha = r.fecha
         WHERE r.id_tesis IN ($in) $filtro_r
        UNION ALL
        SELECT 'comentario', NULL, c.id_tesis, c.id_fase, c.id_documento, c.fecha,
               NULL, NULL, NULL, NULL, c.id_usuario,
               c.comentario, u.nombre, u.apellido, u.rol, f.nombre_fase, f.orden, d.nombre_documento, t.titulo
          FROM comentarios c
          JOIN usuarios u ON u.usuario_id = c.id_usuario
          JOIN tesis t ON t.id_tesis = c.id_tesis
          LEFT JOIN fases f ON f.id_fase = c.id_fase
          LEFT JOIN documento d ON d.id_documento = c.id_documento
         WHERE c.id_tesis IN ($in) $filtro_c
           AND NOT EXISTS (SELECT 1 FROM correcciones r
                            WHERE r.id_tesis = c.id_tesis AND r.id_fase <=> c.id_fase
                              AND r.id_documento <=> c.id_documento AND r.id_profesor = c.id_usuario
                              AND r.fecha = c.fecha)
        ORDER BY fecha DESC";
    $params = $ids_tesis;
    if ($id_fase) { $params[] = $id_fase; }
    $params = array_merge($params, $ids_tesis);
    if ($id_fase) { $params[] = $id_fase; }
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Documentos de un proyecto (opcionalmente de una fase). */
function documentos_de_tesis(PDO $pdo, int $id_tesis, ?int $id_fase = null): array
{
    $sql = 'SELECT d.*, f.nombre_fase, f.orden,
                   (SELECT MAX(r.fecha) FROM correcciones r WHERE r.id_documento = d.id_documento) AS ultima_revision
              FROM documento d
              LEFT JOIN fases f ON f.id_fase = d.id_fase
             WHERE d.id_tesis = ?';
    $params = [$id_tesis];
    if ($id_fase) {
        $sql .= ' AND d.id_fase = ?';
        $params[] = $id_fase;
    }
    $sql .= ' ORDER BY COALESCE(d.fecha_modificacion, d.fecha_subida) DESC, d.id_documento DESC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/* =====================================================================
   Iconos (SVG en línea, sin librerías externas)
   ===================================================================== */
function icono(string $nombre): string
{
    $p = [
        'inicio'       => '<path d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'grupos'       => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14.6c2.8.2 5 2.2 5 5.4"/>',
        'proyectos'    => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'revisiones'   => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/><path d="M8.5 15l2.2 2.2L15.5 12.5"/>',
        'correcciones' => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M13.5 6.5l4 4"/>',
        'perfil'       => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
        'salir'        => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 17l5-5-5-5"/><path d="M15 12H4"/>',
        'documento'    => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/><path d="M8 13h8M8 17h5"/>',
        'reloj'        => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'historial'    => '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/><path d="M12 8v4l3 2"/>',
        'flecha'       => '<path d="M15 18l-6-6 6-6"/>',
    ][$nombre] ?? '';
    return '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}
