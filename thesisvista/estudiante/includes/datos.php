<?php

const ESTUDIANTE_VE_NOTAS = true;


const DIAS_AVISO_LIMITE = 7;


const ESTADOS_ESTUDIANTE = [
    'Bloqueada'              => 'etiqueta-gris',
    'Disponible'             => 'etiqueta-azul',
    'En progreso'            => 'etiqueta-ambar',
    'Pendiente de revisión'  => 'etiqueta-violeta',
    'Completada'             => 'etiqueta-verde',
];


function modulo_estudiante_instalado(): bool
{
    $stmt = conectar()->query("SELECT
        (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'fases' AND COLUMN_NAME = 'requiere_anterior')
      + (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'aviso_lectura')
      + (SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_NAME = 'tv_fase_bloqueada')");
    return (int) $stmt->fetchColumn() === 3;
}


function grupos_estudiante(int $id_estudiante): array
{
    $stmt = conectar()->prepare(
        'SELECT g.id_grupo, g.nombre_grupo, g.id_curso, g.fecha_creacion, c.nombre_curso, c.ficha, eg.fecha_asignacion
         FROM estudiante_grupo eg
         JOIN grupos g ON g.id_grupo = eg.id_grupo
         JOIN cursos c ON c.id_curso = g.id_curso
         WHERE eg.id_estudiante = ?
         ORDER BY eg.fecha_asignacion DESC, eg.id_estudiante_grupo DESC'
    );
    $stmt->execute([$id_estudiante]);
    return $stmt->fetchAll();
}


function docentes_del_curso(int $id_curso): array
{
    $stmt = conectar()->prepare(
        "SELECT u.usuario_id, u.nombre, u.apellido, u.correo
         FROM docente_curso dc JOIN usuarios u ON u.usuario_id = dc.id_profesor
         WHERE dc.id_curso = ? AND u.rol = 'profesor'
         ORDER BY u.nombre, u.apellido"
    );
    $stmt->execute([$id_curso]);
    return $stmt->fetchAll();
}


function proyectos_estudiante(int $id_grupo, int $id_estudiante): array
{
    $stmt = conectar()->prepare(
        'SELECT t.id_tesis, t.titulo, t.resumen, t.estado, t.fecha_registro, t.id_estudiante, t.id_profesor,
                e.nombre AS est_nombre, e.apellido AS est_apellido,
                p.nombre AS prof_nombre, p.apellido AS prof_apellido, p.correo AS prof_correo
         FROM tesis t
         JOIN usuarios e ON e.usuario_id = t.id_estudiante
         JOIN usuarios p ON p.usuario_id = t.id_profesor
         WHERE t.id_grupo = ?
         ORDER BY (t.id_estudiante = ?) DESC, t.fecha_registro DESC, t.id_tesis DESC'
    );
    $stmt->execute([$id_grupo, $id_estudiante]);
    return $stmt->fetchAll();
}


function integrantes_con_participacion(int $id_grupo, array $proyectos, array $fases): array
{
    $habilitadas = count(array_filter($fases, fn($f) => !$f['bloqueada']));
    $stmt = conectar()->prepare(
        "SELECT u.usuario_id, u.nombre, u.apellido, eg.fecha_asignacion,
                (SELECT COUNT(DISTINCT en.id_fase) FROM entregas en JOIN fases fa ON fa.id_fase = en.id_fase
                  WHERE en.id_grupo = eg.id_grupo AND en.id_estudiante = u.usuario_id AND fa.estado <> 'Borrador') AS fases_entregadas,
                (SELECT COUNT(*) FROM entregas en WHERE en.id_grupo = eg.id_grupo AND en.id_estudiante = u.usuario_id) AS total_entregas,
                (SELECT MAX(en.fecha_subida) FROM entregas en WHERE en.id_grupo = eg.id_grupo AND en.id_estudiante = u.usuario_id) AS ultima_entrega
         FROM estudiante_grupo eg JOIN usuarios u ON u.usuario_id = eg.id_estudiante
         WHERE eg.id_grupo = ? AND u.rol = 'estudiante'
         ORDER BY u.nombre, u.apellido"
    );
    $stmt->execute([$id_grupo]);
    $filas = $stmt->fetchAll();

    foreach ($filas as &$u) {
        $responsable = array_filter($proyectos, fn($p) => (int) $p['id_estudiante'] === (int) $u['usuario_id']);
        $u['rol_proyecto'] = $responsable
            ? 'Responsable del proyecto «' . reset($responsable)['titulo'] . '»'
            : 'Integrante del grupo';
        $u['habilitadas'] = $habilitadas;
        $n = (int) $u['fases_entregadas'];
        if (!$habilitadas)           $u['participacion'] = ['Sin fases habilitadas', 'etiqueta-gris'];
        elseif ($n >= $habilitadas)  $u['participacion'] = ['Al día', 'etiqueta-verde'];
        elseif ($n > 0)              $u['participacion'] = ['Participando', 'etiqueta-ambar'];
        else                         $u['participacion'] = ['Sin entregas aún', 'etiqueta-gris'];
    }
    return $filas;
}


function fases_estudiante(int $id_grupo, int $id_estudiante): array
{
    $stmt = conectar()->prepare(
        "SELECT f.id_fase, f.id_curso, f.nombre_fase, f.descripcion, f.objetivo, f.ejemplo, f.instrucciones, f.evidencias,
                f.requisitos_siguiente, f.tipo_entrega, f.archivo_guia, f.archivo_guia_nombre, f.orden, f.duracion_dias,
                f.fecha_inicio, f.fecha_limite, f.peso, f.requiere_anterior, f.estado AS estado_fase,
                fg.estado, fg.porcentaje_avance, fg.fecha_asignacion, fg.fecha_entrega, fg.fecha_aprobacion, fg.fecha_actualizacion,
                tv_fase_bloqueada(f.id_fase, fg.id_grupo) AS codigo_bloqueo,
                (SELECT COUNT(*) FROM entregas en WHERE en.id_fase = f.id_fase AND en.id_grupo = fg.id_grupo) AS total_entregas,
                (SELECT COUNT(*) FROM entregas en WHERE en.id_fase = f.id_fase AND en.id_grupo = fg.id_grupo AND en.id_estudiante = ?) AS mis_entregas,
                (SELECT COUNT(*) FROM retroalimentacion r WHERE r.id_fase = f.id_fase AND r.id_grupo = fg.id_grupo
                    AND (r.id_estudiante IS NULL OR r.id_estudiante = ?)) AS total_comentarios
         FROM fase_grupo fg JOIN fases f ON f.id_fase = fg.id_fase
         WHERE fg.id_grupo = ? AND f.estado <> 'Borrador'
         ORDER BY f.orden, f.id_fase"
    );
    $stmt->execute([$id_estudiante, $id_estudiante, $id_grupo]);
    $fases = $stmt->fetchAll();

    $previa_pendiente = null;   // primera fase publicada anterior que aún no está aprobada
    foreach ($fases as &$f) {
        $f['porcentaje'] = porcentaje_fase($f);
        $codigo = (int) $f['codigo_bloqueo'];
        $f['bloqueada'] = $codigo !== 0;
        $f['motivo_bloqueo'] = null;
        $f['fase_requerida'] = null;

        if ($codigo === 2) {
            $f['motivo_bloqueo'] = 'El docente cerró esta fase antes de que el grupo la empezara.';
        } elseif ($codigo === 3) {
            $f['motivo_bloqueo'] = 'Se habilita el ' . fecha_corta($f['fecha_inicio']) . '.';
        } elseif ($codigo === 4) {
            $f['fase_requerida'] = $previa_pendiente;
            $f['motivo_bloqueo'] = $previa_pendiente
                ? 'Se desbloquea cuando el docente apruebe la Fase ' . $previa_pendiente['orden'] . ' · ' . $previa_pendiente['nombre_fase'] . '.'
                : 'Se desbloquea cuando el docente apruebe la fase anterior.';
        } elseif ($codigo === 1) {
            $f['motivo_bloqueo'] = 'Esta fase no está disponible para su grupo.';
        }

        if ($f['bloqueada'])                                               $f['estado_visible'] = 'Bloqueada';
        elseif (fase_terminada($f['estado']))                               $f['estado_visible'] = 'Completada';
        elseif (in_array($f['estado'], ['Entregada', 'En revisión'], true)) $f['estado_visible'] = 'Pendiente de revisión';
        elseif (in_array($f['estado'], ['En progreso', 'Requiere corrección'], true)) $f['estado_visible'] = 'En progreso';
        else                                                                $f['estado_visible'] = 'Disponible';

        
        $f['puede_entregar'] = false;
        if ($f['bloqueada'])                         $f['motivo_no_entrega'] = $f['motivo_bloqueo'];
        elseif ($f['estado_fase'] !== 'Publicada')   $f['motivo_no_entrega'] = 'El docente cerró la fase: ya no recibe entregas.';
        elseif ($f['estado'] === 'En revisión')      $f['motivo_no_entrega'] = 'El docente está revisando el trabajo. Podrá subir otra versión si pide correcciones.';
        elseif (fase_terminada($f['estado']))        $f['motivo_no_entrega'] = 'La fase ya fue aprobada.';
        else { $f['puede_entregar'] = true; $f['motivo_no_entrega'] = null; }

        if ($previa_pendiente === null && $f['estado_fase'] === 'Publicada' && !fase_terminada($f['estado'])) {
            $previa_pendiente = ['id_fase' => (int) $f['id_fase'], 'orden' => $f['orden'], 'nombre_fase' => $f['nombre_fase'],
                                 'requisitos_siguiente' => $f['requisitos_siguiente']];
        }
    }
    unset($f);
    return $fases;
}


function buscar_fase(array $fases, int $id_fase): ?array
{
    foreach ($fases as $f) if ((int) $f['id_fase'] === $id_fase) return $f;
    return null;
}


function resumen_progreso(int $id_grupo, array $fases): array
{
    $r = progreso_grupo($id_grupo, $fases);           // misma fórmula que ve el docente
    $r['por_estado'] = array_fill_keys(array_keys(ESTADOS_ESTUDIANTE), 0);
    foreach ($fases as $f) $r['por_estado'][$f['estado_visible']]++;
    $r['pendientes'] = $r['por_estado']['Disponible'] + $r['por_estado']['En progreso'];
    $r['siguiente'] = null;
    foreach ($fases as $f) {
        if ($f['puede_entregar']) { $r['siguiente'] = $f; break; }
    }
    return $r;
}


function entregas_estudiante(int $id_grupo, ?int $solo_estudiante = null, ?int $id_fase = null): array
{
    $sql = "SELECT en.*, u.nombre, u.apellido, fa.nombre_fase, fa.orden, fa.fecha_limite,
                   r.nombre AS rev_nombre, r.apellido AS rev_apellido
            FROM entregas en
            JOIN usuarios u ON u.usuario_id = en.id_estudiante
            JOIN fases fa ON fa.id_fase = en.id_fase
            LEFT JOIN usuarios r ON r.usuario_id = en.id_profesor_revisor
            WHERE en.id_grupo = ? AND fa.estado <> 'Borrador'";
    $p = [$id_grupo];
    if ($solo_estudiante) { $sql .= ' AND en.id_estudiante = ?'; $p[] = $solo_estudiante; }
    if ($id_fase)         { $sql .= ' AND en.id_fase = ?';       $p[] = $id_fase; }
    $sql .= ' ORDER BY en.fecha_subida DESC, en.id_entrega DESC';
    $stmt = conectar()->prepare($sql);
    $stmt->execute($p);
    return $stmt->fetchAll();
}


function entrega_para_estudiante(int $id_estudiante, int $id_entrega): ?array
{
    $stmt = conectar()->prepare(
        "SELECT en.* FROM entregas en
         JOIN estudiante_grupo eg ON eg.id_grupo = en.id_grupo AND eg.id_estudiante = ?
         JOIN fase_grupo fg ON fg.id_fase = en.id_fase AND fg.id_grupo = en.id_grupo
         JOIN fases fa ON fa.id_fase = en.id_fase AND fa.estado <> 'Borrador'
         WHERE en.id_entrega = ?"
    );
    $stmt->execute([$id_estudiante, $id_entrega]);
    return $stmt->fetch() ?: null;
}


function comentarios_estudiante(int $id_grupo, int $id_estudiante, ?int $id_fase = null, int $limite = 200): array
{
    $sql = "SELECT r.*, u.nombre, u.apellido, fa.nombre_fase, fa.orden, en.version, en.nombre_original
            FROM retroalimentacion r
            JOIN usuarios u ON u.usuario_id = r.id_profesor
            LEFT JOIN fases fa ON fa.id_fase = r.id_fase
            LEFT JOIN entregas en ON en.id_entrega = r.id_entrega
            WHERE r.id_grupo = ?
              AND (r.id_estudiante IS NULL OR r.id_estudiante = ?)
              AND (r.id_fase IS NULL OR fa.estado <> 'Borrador')";
    $p = [$id_grupo, $id_estudiante];
    if ($id_fase) { $sql .= ' AND r.id_fase = ?'; $p[] = $id_fase; }
    $sql .= ' ORDER BY r.fecha DESC LIMIT ' . (int) $limite;
    $stmt = conectar()->prepare($sql);
    $stmt->execute($p);
    return $stmt->fetchAll();
}



function calificaciones_estudiante(int $id_grupo, int $id_estudiante): array
{
    if (!ESTUDIANTE_VE_NOTAS) return [];
    $stmt = conectar()->prepare(
        "SELECT ca.*, u.nombre, u.apellido, fa.nombre_fase, fa.orden, en.version, en.nombre_original
         FROM calificaciones ca
         JOIN usuarios u ON u.usuario_id = ca.id_profesor
         JOIN fases fa ON fa.id_fase = ca.id_fase
         LEFT JOIN entregas en ON en.id_entrega = ca.id_entrega
         WHERE ca.id_grupo = ? AND (ca.id_estudiante IS NULL OR ca.id_estudiante = ?) AND fa.estado <> 'Borrador'
         ORDER BY fa.orden, ca.id_estudiante IS NOT NULL, ca.fecha DESC"
    );
    $stmt->execute([$id_grupo, $id_estudiante]);
    return $stmt->fetchAll();
}


function incentivos_estudiante(array $grupo, int $id_estudiante): array
{
    $stmt = conectar()->prepare(
        "SELECT i.*, fa.nombre_fase, fa.orden AS orden_fase, t.titulo AS titulo_tesis,
                u.nombre AS creador_nombre, u.apellido AS creador_apellido
         FROM incentivos_grupales i
         JOIN usuarios u ON u.usuario_id = i.id_profesor
         LEFT JOIN fases fa ON fa.id_fase = i.id_fase
         LEFT JOIN tesis t ON t.id_tesis = i.id_tesis
         WHERE i.id_curso = ? AND i.estado IN ('Activo', 'Finalizado')
           AND (i.id_fase IS NULL OR (fa.estado <> 'Borrador'
                AND EXISTS (SELECT 1 FROM fase_grupo fg WHERE fg.id_fase = i.id_fase AND fg.id_grupo = ?)))
           AND (i.id_tesis IS NULL OR t.id_grupo = ?)
         ORDER BY FIELD(i.estado, 'Activo', 'Finalizado'), fa.orden, i.fecha_creacion DESC"
    );
    $stmt->execute([$grupo['id_curso'], $grupo['id_grupo'], $grupo['id_grupo']]);
    $incentivos = $stmt->fetchAll();

    $otorgados = otorgados_estudiante((int) $grupo['id_grupo'], $id_estudiante);
    foreach ($incentivos as &$i) {
        $i['otorgamientos'] = array_values(array_filter($otorgados, fn($o) => (int) $o['id_incentivo_grupal'] === (int) $i['id_incentivo_grupal']));
        if ($i['otorgamientos'])              $i['resultado'] = 'Obtenido';
        elseif ($i['estado'] === 'Finalizado') $i['resultado'] = 'No obtenido';
        else                                   $i['resultado'] = 'Pendiente';
    }
    return $incentivos;
}


function otorgados_estudiante(int $id_grupo, int $id_estudiante): array
{
    $stmt = conectar()->prepare(
        'SELECT o.*, i.nombre AS incentivo, i.icono, i.valor, i.id_fase, fa.nombre_fase, fa.orden,
                u.nombre, u.apellido
         FROM incentivo_otorgado o
         JOIN incentivos_grupales i ON i.id_incentivo_grupal = o.id_incentivo_grupal
         JOIN usuarios u ON u.usuario_id = o.id_profesor
         LEFT JOIN fases fa ON fa.id_fase = i.id_fase
         WHERE o.id_grupo = ? AND (o.id_estudiante IS NULL OR o.id_estudiante = ?)
         ORDER BY o.fecha DESC'
    );
    $stmt->execute([$id_grupo, $id_estudiante]);
    return $stmt->fetchAll();
}


function insignias_estudiante(int $id_estudiante): array
{
    $stmt = conectar()->prepare(
        'SELECT ei.fecha_obtenido, i.nombre, i.descripcion, i.icono, t.titulo
         FROM estudiante_incentivo ei
         JOIN incentivos i ON i.id_incentivo = ei.id_incentivo
         LEFT JOIN tesis t ON t.id_tesis = ei.id_tesis
         WHERE ei.id_estudiante = ? ORDER BY ei.fecha_obtenido DESC'
    );
    $stmt->execute([$id_estudiante]);
    return $stmt->fetchAll();
}



function fecha_avisos_vistos(int $id_estudiante): ?string
{
    $stmt = conectar()->prepare('SELECT fecha_visto FROM aviso_lectura WHERE id_usuario = ?');
    $stmt->execute([$id_estudiante]);
    return $stmt->fetchColumn() ?: null;
}

function marcar_avisos_vistos(int $id_estudiante): void
{
    conectar()->prepare('INSERT INTO aviso_lectura (id_usuario, fecha_visto) VALUES (?, NOW())
                         ON DUPLICATE KEY UPDATE fecha_visto = NOW()')->execute([$id_estudiante]);
}


function avisos_estudiante(array $grupo, int $id_estudiante, array $fases, int $limite = 60): array
{
    $pdo = conectar();
    $g = (int) $grupo['id_grupo'];
    $visto = fecha_avisos_vistos($id_estudiante);
    $avisos = [];
    $url_fase = fn($id) => 'fase.php?id=' . (int) $id;
    $nombre_fase = fn($orden, $nombre) => 'Fase ' . $orden . ' · ' . $nombre;


    foreach (comentarios_estudiante($g, $id_estudiante, null, 40) as $c) {
        $avisos[] = ['tipo' => 'comentario', 'fecha' => $c['fecha'],
            'titulo' => $c['nombre'] . ' ' . $c['apellido'] . ' publicó ' . mb_strtolower($c['tipo'] === 'Comentario general' ? 'un comentario' : 'una ' . $c['tipo']),
            'texto' => ($c['nombre_fase'] ? $nombre_fase($c['orden'], $c['nombre_fase']) . ': ' : '') . mb_strimwidth($c['comentario'], 0, 140, '…'),
            'url' => $c['id_fase'] ? $url_fase($c['id_fase']) . '#comentarios' : 'comentarios.php'];
    }


    $stmt = $pdo->prepare(
        "SELECT r.estado, r.fecha, r.comentario, u.nombre, u.apellido, en.id_estudiante, en.nombre_original, en.version,
                fa.id_fase, fa.nombre_fase, fa.orden
         FROM revisiones_entrega r JOIN entregas en ON en.id_entrega = r.id_entrega
         JOIN usuarios u ON u.usuario_id = r.id_profesor JOIN fases fa ON fa.id_fase = en.id_fase
         WHERE en.id_grupo = ? AND fa.estado <> 'Borrador' ORDER BY r.fecha DESC LIMIT 40");
    $stmt->execute([$g]);
    foreach ($stmt->fetchAll() as $r) {
        $mia = (int) $r['id_estudiante'] === $id_estudiante;
        $avisos[] = ['tipo' => 'revision', 'fecha' => $r['fecha'],
            'titulo' => ($mia ? 'Tu entrega' : 'Una entrega del grupo') . ' quedó «' . $r['estado'] . '»',
            'texto' => $nombre_fase($r['orden'], $r['nombre_fase']) . ' · ' . $r['nombre_original'] . ' (v' . (int) $r['version'] . ') · por ' . $r['nombre'] . ' ' . $r['apellido'],
            'url' => $url_fase($r['id_fase']) . '#entregas'];
    }

    $stmt = $pdo->prepare(
        "SELECT fg.estado, fg.porcentaje_avance, fg.fecha_actualizacion, u.nombre, u.apellido, fa.id_fase, fa.nombre_fase, fa.orden
         FROM fase_grupo fg JOIN fases fa ON fa.id_fase = fg.id_fase JOIN usuarios u ON u.usuario_id = fg.id_profesor_actualiza
         WHERE fg.id_grupo = ? AND fa.estado <> 'Borrador' AND fg.fecha_actualizacion IS NOT NULL");
    $stmt->execute([$g]);
    foreach ($stmt->fetchAll() as $a) {
        $avisos[] = ['tipo' => 'avance', 'fecha' => $a['fecha_actualizacion'],
            'titulo' => $nombre_fase($a['orden'], $a['nombre_fase']) . ': ' . $a['estado'],
            'texto' => 'Actualizado por ' . $a['nombre'] . ' ' . $a['apellido']
                . ($a['estado'] === 'En progreso' ? ' · avance registrado ' . (int) $a['porcentaje_avance'] . ' %' : ''),
            'url' => $url_fase($a['id_fase'])];
    }


    $previa_aprobada = null;
    foreach ($fases as $f) {
        if (!$f['bloqueada']) {
            $momentos = array_filter([$f['fecha_asignacion'], $f['fecha_inicio'] ? $f['fecha_inicio'] . ' 00:00:00' : null,
                                      (int) $f['requiere_anterior'] ? $previa_aprobada : null]);
            $cuando = $momentos ? max($momentos) : null;
            if ($cuando && $cuando <= date('Y-m-d H:i:s')) {
                $avisos[] = ['tipo' => 'desbloqueo', 'fecha' => $cuando,
                    'titulo' => $nombre_fase($f['orden'], $f['nombre_fase']) . ' está disponible',
                    'texto' => $f['puede_entregar'] ? 'Ya puedes revisar las instrucciones y subir tu entrega.' : 'Puedes consultar su información.',
                    'url' => $url_fase($f['id_fase'])];
            }
        }
        if ($f['estado_fase'] === 'Publicada') $previa_aprobada = fase_terminada($f['estado']) ? $f['fecha_aprobacion'] : $previa_aprobada;
    }


    $stmt = $pdo->prepare(
        "SELECT en.fecha_subida, en.version, en.nombre_original, u.nombre, u.apellido, fa.id_fase, fa.nombre_fase, fa.orden
         FROM entregas en JOIN usuarios u ON u.usuario_id = en.id_estudiante JOIN fases fa ON fa.id_fase = en.id_fase
         WHERE en.id_grupo = ? AND en.id_estudiante <> ? AND fa.estado <> 'Borrador' ORDER BY en.fecha_subida DESC LIMIT 20");
    $stmt->execute([$g, $id_estudiante]);
    foreach ($stmt->fetchAll() as $en) {
        $avisos[] = ['tipo' => 'entrega', 'fecha' => $en['fecha_subida'],
            'titulo' => $en['nombre'] . ' ' . $en['apellido'] . ((int) $en['version'] > 1 ? ' subió una nueva versión' : ' subió una entrega'),
            'texto' => $nombre_fase($en['orden'], $en['nombre_fase']) . ' · ' . $en['nombre_original'],
            'url' => $url_fase($en['id_fase']) . '#entregas'];
    }


    foreach (otorgados_estudiante($g, $id_estudiante) as $o) {
        $avisos[] = ['tipo' => 'incentivo', 'fecha' => $o['fecha'],
            'titulo' => ($o['id_estudiante'] ? 'Obtuviste' : 'Tu grupo obtuvo') . ' el incentivo «' . $o['incentivo'] . '»',
            'texto' => 'Otorgado por ' . $o['nombre'] . ' ' . $o['apellido'] . ($o['observacion'] ? ' · ' . mb_strimwidth($o['observacion'], 0, 100, '…') : ''),
            'url' => 'incentivos.php'];
    }


    if (!empty($grupo['fecha_asignacion'])) {
        $avisos[] = ['tipo' => 'grupo', 'fecha' => $grupo['fecha_asignacion'] . ' 00:00:00',
            'titulo' => 'Fuiste asignado(a) al grupo ' . $grupo['nombre_grupo'],
            'texto' => 'Curso ' . $grupo['nombre_curso'], 'url' => 'grupo.php'];
    }

    foreach ($avisos as &$a) {
        $a['fijo'] = false;
        $a['nuevo'] = $visto === null || $a['fecha'] > $visto;
    }
    unset($a);
    usort($avisos, fn($x, $y) => strcmp($y['fecha'], $x['fecha']));
    $avisos = array_slice($avisos, 0, $limite);

   
    $recordatorios = [];
    foreach (fechas_proximas($fases) as $f) {
        $d = dias_restantes($f['fecha_limite']);
        $recordatorios[] = ['tipo' => 'limite', 'fecha' => $f['fecha_limite'] . ' 23:59:59', 'fijo' => true, 'nuevo' => false,
            'titulo' => $d < 0 ? 'Fecha límite vencida: Fase ' . $f['orden'] : ($d === 0 ? 'Hoy vence la Fase ' . $f['orden'] : 'Faltan ' . $d . ' día(s) para la Fase ' . $f['orden']),
            'texto' => $f['nombre_fase'] . ' · límite ' . fecha_corta($f['fecha_limite']) . ((int) $f['total_entregas'] ? '' : ' · tu grupo aún no ha entregado'),
            'url' => $url_fase($f['id_fase'])];
    }
    return array_merge($recordatorios, $avisos);
}


function fechas_proximas(array $fases): array
{
    return array_values(array_filter($fases, function ($f) {
        if (!$f['puede_entregar'] || !$f['fecha_limite']) return false;
        if (in_array($f['estado'], ['Entregada'], true)) return false;
        return dias_restantes($f['fecha_limite']) <= DIAS_AVISO_LIMITE;
    }));
}

function total_avisos_nuevos(array $avisos): int
{
    return count(array_filter($avisos, fn($a) => $a['nuevo']));
}


function firma_estudiante(int $id_estudiante, ?int $id_grupo): string
{
    $pdo = conectar();
    $stmt = $pdo->prepare("SELECT CONCAT_WS('|', CURDATE(),
        (SELECT GROUP_CONCAT(CONCAT(id_grupo, '@', fecha_asignacion) ORDER BY id_grupo) FROM estudiante_grupo WHERE id_estudiante = ?),
        (SELECT rol FROM usuarios WHERE usuario_id = ?))");
    $stmt->execute([$id_estudiante, $id_estudiante]);
    $base = (string) $stmt->fetchColumn();
    if (!$id_grupo) return md5($base);

    $stmt = $pdo->prepare("SELECT CONCAT_WS('|',
        (SELECT GROUP_CONCAT(CONCAT(u.usuario_id, u.nombre, u.apellido) ORDER BY u.usuario_id) FROM estudiante_grupo eg
            JOIN usuarios u ON u.usuario_id = eg.id_estudiante WHERE eg.id_grupo = ?),
        (SELECT CONCAT(nombre_grupo, id_curso) FROM grupos WHERE id_grupo = ?),
        (SELECT GROUP_CONCAT(CONCAT_WS(',', id_tesis, titulo, estado, id_profesor, id_estudiante) ORDER BY id_tesis) FROM tesis WHERE id_grupo = ?),
        (SELECT GROUP_CONCAT(CONCAT_WS(',', fg.id_fase, fg.estado, fg.porcentaje_avance, fg.fecha_actualizacion, f.estado, f.orden,
                 f.requiere_anterior, f.fecha_inicio, f.fecha_limite, COALESCE(f.fecha_modificacion, f.fecha_creacion)) ORDER BY fg.id_fase)
            FROM fase_grupo fg JOIN fases f ON f.id_fase = fg.id_fase WHERE fg.id_grupo = ?),
        (SELECT CONCAT(COUNT(*), '-', COALESCE(MAX(fecha_subida), ''), '-', COALESCE(MAX(fecha_revision), '')) FROM entregas WHERE id_grupo = ?),
        (SELECT CONCAT(COUNT(*), '-', COALESCE(MAX(COALESCE(fecha_edicion, fecha)), '')) FROM retroalimentacion WHERE id_grupo = ?),
        (SELECT CONCAT(COUNT(*), '-', COALESCE(MAX(COALESCE(fecha_modificacion, fecha)), '')) FROM calificaciones WHERE id_grupo = ?),
        (SELECT CONCAT(COUNT(*), '-', COALESCE(MAX(fecha), '')) FROM incentivo_otorgado WHERE id_grupo = ?),
        (SELECT CONCAT(COUNT(*), '-', COALESCE(MAX(COALESCE(i.fecha_modificacion, i.fecha_creacion)), ''))
            FROM incentivos_grupales i JOIN grupos g ON g.id_curso = i.id_curso WHERE g.id_grupo = ?),
        (SELECT GROUP_CONCAT(dc.id_profesor ORDER BY dc.id_profesor) FROM docente_curso dc JOIN grupos g ON g.id_curso = dc.id_curso WHERE g.id_grupo = ?)
    )");
    $stmt->execute(array_fill(0, 10, $id_grupo));
    return md5($base . '#' . $stmt->fetchColumn());
}
