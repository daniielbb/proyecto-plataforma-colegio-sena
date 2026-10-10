<?php



$__tv_seguridad = null;

foreach ([__DIR__ . '/seguridad.php', dirname(__DIR__) . '/includes/seguridad.php', dirname(__DIR__) . '/seguridad.php'] as $__tv_ruta) {

    if (is_file($__tv_ruta)) { $__tv_seguridad = $__tv_ruta; break; }

}

if ($__tv_seguridad === null) {

    http_response_code(500);

    exit('THESISVISTA: no se encontró includes/seguridad.php. El archivo academico.php debe estar en la carpeta '

       . 'includes del proyecto, junto a seguridad.php (ruta actual: ' . htmlspecialchars(__DIR__) . ').');

}

require_once $__tv_seguridad;

 


const ESTADOS_AVANCE = ['Pendiente', 'En progreso', 'Entregada', 'En revisión', 'Requiere corrección', 'Aprobada', 'Completada'];

 



const ESTADOS_FASE = [

    'Borrador'  => 'Borrador · no la ven los estudiantes',

    'Publicada' => 'Publicada · visible para los grupos asignados',

    'Cerrada'   => 'Cerrada · ya no recibe entregas',

];

 



const ESTADOS_ENTREGA = ['Entregado', 'En revisión', 'Requiere corrección', 'Corregido', 'Aprobado'];

 

const TIPOS_COMENTARIO = ['Comentario general', 'Observación', 'Corrección', 'Recomendación'];

 

const NOTA_MIN = 0.0;

const NOTA_MAX = 5.0;

const NOTA_APROBATORIA = 3.0;

 

const TAMANO_MAXIMO = 10 * 1024 * 1024;   // 10 MB

 


const TIPOS_ENTREGA = [

    'cualquiera'   => ['Cualquier formato permitido', ['pdf', 'doc', 'docx', 'odt', 'ppt', 'pptx', 'odp', 'xls', 'xlsx', 'ods', 'csv', 'txt', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'zip', 'rar', '7z']],

    'pdf'          => ['PDF', ['pdf']],

    'documento'    => ['Documento (Word / PDF)', ['pdf', 'doc', 'docx', 'odt', 'txt']],

    'presentacion' => ['Presentación', ['pdf', 'ppt', 'pptx', 'odp']],

    'hoja'         => ['Hoja de cálculo', ['xls', 'xlsx', 'ods', 'csv']],

    'imagen'       => ['Imagen', ['png', 'jpg', 'jpeg', 'gif', 'webp']],

    'comprimido'   => ['Comprimido (ZIP / RAR)', ['zip', 'rar', '7z']],

];

 



const TIPOS_MIME_VISIBLES = [

    'pdf' => 'application/pdf', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',

    'gif' => 'image/gif', 'webp' => 'image/webp', 'txt' => 'text/plain; charset=utf-8',

];

 

const ICONOS_INCENTIVO = ['🏆', '⭐', '🥇', '🎖️', '🚀', '💡', '📚', '🤝', '⏱️', '🎯'];

 

define('CARPETA_UPLOADS', dirname(__DIR__) . '/uploads');

 



if (!ini_get('date.timezone') || date_default_timezone_get() === 'UTC') {

    date_default_timezone_set('America/Bogota');

}

conectar()->exec("SET time_zone = '" . date('P') . "'");

 



function modulo_docente_instalado(): bool

{

    static $ok = null;

    if ($ok === null) {

        $stmt = conectar()->query("SELECT COUNT(*) FROM information_schema.COLUMNS

                                   WHERE TABLE_SCHEMA = DATABASE()

                                     AND ((TABLE_NAME = 'fase_grupo' AND COLUMN_NAME = 'estado')

                                       OR (TABLE_NAME = 'retroalimentacion' AND COLUMN_NAME = 'id_retro')

                                       OR (TABLE_NAME = 'calificaciones' AND COLUMN_NAME = 'nota')

                                       OR (TABLE_NAME = 'incentivo_otorgado' AND COLUMN_NAME = 'id_otorgado')

                                       OR (TABLE_NAME = 'fases' AND COLUMN_NAME = 'peso'))");

        $ok = (int) $stmt->fetchColumn() === 5;

    }

    return $ok;

}

 



function marcas(array $ids): string

{

    return $ids ? implode(',', array_fill(0, count($ids), '?')) : 'NULL';

}

 

function nombre_completo(array $fila, string $prefijo = ''): string

{

    return trim(($fila[$prefijo . 'nombre'] ?? '') . ' ' . ($fila[$prefijo . 'apellido'] ?? ''));

}

 

function fecha_corta(?string $fecha): string

{

    if (!$fecha) return '—';

    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    $t = strtotime($fecha);

    return date('j', $t) . ' ' . $meses[(int) date('n', $t) - 1] . ' ' . date('Y', $t);

}

 

function fecha_hora(?string $fecha): string

{

    if (!$fecha) return '—';

    return fecha_corta($fecha) . ' · ' . date('g:i a', strtotime($fecha));

}

 



function hace(?string $fecha): string

{

    if (!$fecha) return '';

    $s = time() - strtotime($fecha);

    if ($s < 60)      return 'hace un momento';

    if ($s < 3600)    return 'hace ' . intdiv($s, 60) . ' min';

    if ($s < 86400)   return 'hace ' . intdiv($s, 3600) . ' h';

    if ($s < 2592000) return 'hace ' . intdiv($s, 86400) . ' día' . (intdiv($s, 86400) > 1 ? 's' : '');

    return fecha_corta($fecha);

}

 



function dias_restantes(?string $fecha): ?int

{

    if (!$fecha) return null;

    $hoy = new DateTime('today');

    $limite = new DateTime($fecha);

    return (int) $hoy->diff($limite)->format('%r%a');

}

 

function tamano_legible(int $bytes): string

{

    if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';

    if ($bytes >= 1024)    return number_format($bytes / 1024, 0) . ' KB';

    return $bytes . ' B';

}

 

function formato_nota($nota): string

{

    return $nota === null ? '—' : number_format((float) $nota, 1);

}

 

/** Clase de color para cada estado (fase, entrega, incentivo). */

function clase_estado_avance(string $estado): string

{

    return [

        'Pendiente'           => 'etiqueta-gris',

        'En progreso'         => 'etiqueta-ambar',

        'Entregada'           => 'etiqueta-azul',

        'Entregado'           => 'etiqueta-azul',

        'Corregido'           => 'etiqueta-azul',

        'En revisión'         => 'etiqueta-violeta',

        'Requiere corrección' => 'etiqueta-roja',

        'Aprobada'            => 'etiqueta-verde',

        'Aprobado'            => 'etiqueta-verde',

        'Completada'          => 'etiqueta-verde-fuerte',

        'Borrador'            => 'etiqueta-gris',

        'Publicada'           => 'etiqueta-verde',

        'Cerrada'             => 'etiqueta-cafe',

        'Activo'              => 'etiqueta-verde',

        'Inactivo'            => 'etiqueta-gris',

        'Finalizado'          => 'etiqueta-cafe',

    ][$estado] ?? 'etiqueta-gris';

}

 


function simbolo_estado(string $estado): string

{

    if (in_array($estado, ['Aprobada', 'Completada'], true)) return '✓';

    if ($estado === 'Pendiente') return '○';

    if ($estado === 'Requiere corrección') return '!';

    return '●';

}

 

function fase_terminada(string $estado): bool

{

    return in_array($estado, ['Aprobada', 'Completada'], true);

}

 



function ids_cursos_docente(int $id_profesor): array

{

    static $cache = [];

    if (!isset($cache[$id_profesor])) {

        $stmt = conectar()->prepare('SELECT id_curso FROM docente_curso WHERE id_profesor = ?');

        $stmt->execute([$id_profesor]);

        $cache[$id_profesor] = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    }

    return $cache[$id_profesor];

}

 

function docente_en_curso(int $id_profesor, int $id_curso): bool

{

    return in_array($id_curso, ids_cursos_docente($id_profesor), true);

}

 

function curso_para_docente(int $id_profesor, int $id_curso): ?array

{

    if (!docente_en_curso($id_profesor, $id_curso)) return null;

    $stmt = conectar()->prepare('SELECT * FROM cursos WHERE id_curso = ?');

    $stmt->execute([$id_curso]);

    return $stmt->fetch() ?: null;

}

 



function grupo_para_docente(int $id_profesor, int $id_grupo): ?array

{

    $stmt = conectar()->prepare(

        'SELECT g.*, c.nombre_curso, c.ficha

         FROM grupos g

         JOIN cursos c ON c.id_curso = g.id_curso

         JOIN docente_curso dc ON dc.id_curso = g.id_curso AND dc.id_profesor = ?

         WHERE g.id_grupo = ?'

    );

    $stmt->execute([$id_profesor, $id_grupo]);

    return $stmt->fetch() ?: null;

}



function fase_para_docente(int $id_profesor, int $id_fase): ?array

{

    $stmt = conectar()->prepare(

        'SELECT f.*, c.nombre_curso, u.nombre AS creador_nombre, u.apellido AS creador_apellido

         FROM fases f

         JOIN cursos c ON c.id_curso = f.id_curso

         JOIN usuarios u ON u.usuario_id = f.id_profesor_creador

         JOIN docente_curso dc ON dc.id_curso = f.id_curso AND dc.id_profesor = ?

         WHERE f.id_fase = ?'

    );

    $stmt->execute([$id_profesor, $id_fase]);

    return $stmt->fetch() ?: null;

}

 

function tesis_para_docente(int $id_profesor, int $id_tesis): ?array

{

    $stmt = conectar()->prepare(

        'SELECT t.*, e.nombre AS est_nombre, e.apellido AS est_apellido, e.correo AS est_correo,

                p.nombre AS prof_nombre, p.apellido AS prof_apellido,

                g.nombre_grupo, g.id_curso, c.nombre_curso

         FROM tesis t

         JOIN usuarios e ON e.usuario_id = t.id_estudiante

         JOIN usuarios p ON p.usuario_id = t.id_profesor

         LEFT JOIN grupos g ON g.id_grupo = t.id_grupo

         LEFT JOIN cursos c ON c.id_curso = g.id_curso

         WHERE t.id_tesis = ?

           AND (t.id_profesor = ?

                OR g.id_curso IN (SELECT id_curso FROM docente_curso WHERE id_profesor = ?))'

    );

    $stmt->execute([$id_tesis, $id_profesor, $id_profesor]);

    return $stmt->fetch() ?: null;

}

 


function entrega_para_docente(int $id_profesor, int $id_entrega): ?array

{

    $stmt = conectar()->prepare(

        'SELECT en.*, u.nombre, u.apellido, f.nombre_fase, f.orden, g.nombre_grupo, g.id_curso

         FROM entregas en

         JOIN usuarios u ON u.usuario_id = en.id_estudiante

         JOIN fases f ON f.id_fase = en.id_fase

         JOIN grupos g ON g.id_grupo = en.id_grupo

         JOIN docente_curso dc ON dc.id_curso = g.id_curso AND dc.id_profesor = ?

         WHERE en.id_entrega = ?'

    );

    $stmt->execute([$id_profesor, $id_entrega]);

    return $stmt->fetch() ?: null;

}

 


function motivo_no_eliminar_fase(int $id_profesor, array $fase): ?string

{

    if ((int) $fase['id_profesor_creador'] !== $id_profesor) {

        return 'Solo el docente que creó la fase puede eliminarla.';

    }

    $pdo = conectar();

    foreach ([

        'entregas'          => 'entregas de estudiantes',

        'calificaciones'    => 'calificaciones',

        'retroalimentacion' => 'comentarios',

        'comentarios'       => 'comentarios del administrador',

    ] as $tabla => $texto) {

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$tabla` WHERE id_fase = ?");

        $stmt->execute([$fase['id_fase']]);

        if ((int) $stmt->fetchColumn() > 0) {

            return "La fase tiene $texto registrados. Puede cerrarla o pasarla a borrador en lugar de eliminarla.";

        }

    }

    return null;

}

 



function cursos_docente(int $id_profesor): array

{

    $stmt = conectar()->prepare(

        "SELECT c.id_curso, c.nombre_curso, c.ficha,

                (SELECT COUNT(*) FROM grupos g WHERE g.id_curso = c.id_curso) AS total_grupos,

                (SELECT COUNT(DISTINCT eg.id_estudiante) FROM estudiante_grupo eg

                   JOIN grupos g ON g.id_grupo = eg.id_grupo WHERE g.id_curso = c.id_curso) AS total_estudiantes,

                (SELECT COUNT(*) FROM fases f WHERE f.id_curso = c.id_curso AND f.estado <> 'Borrador') AS total_fases

         FROM docente_curso dc JOIN cursos c ON c.id_curso = dc.id_curso

         WHERE dc.id_profesor = ?

         ORDER BY CAST(c.nombre_curso AS UNSIGNED) DESC, c.nombre_curso"

    );

    $stmt->execute([$id_profesor]);

    return $stmt->fetchAll();

}

 



function grupos_de_curso(int $id_curso): array

{

    $stmt = conectar()->prepare(

        'SELECT g.*, (SELECT COUNT(*) FROM estudiante_grupo eg WHERE eg.id_grupo = g.id_grupo) AS total_estudiantes

         FROM grupos g WHERE g.id_curso = ? ORDER BY g.nombre_grupo'

    );

    $stmt->execute([$id_curso]);

    return $stmt->fetchAll();

}

 


function grupos_docente(int $id_profesor): array

{

    $stmt = conectar()->prepare(

        'SELECT g.*, c.nombre_curso,

                (SELECT COUNT(*) FROM estudiante_grupo eg WHERE eg.id_grupo = g.id_grupo) AS total_estudiantes

         FROM grupos g

         JOIN cursos c ON c.id_curso = g.id_curso

         JOIN docente_curso dc ON dc.id_curso = g.id_curso AND dc.id_profesor = ?

         ORDER BY c.nombre_curso, g.nombre_grupo'

    );

    $stmt->execute([$id_profesor]);

    return $stmt->fetchAll();

}

 

function integrantes_grupo(int $id_grupo): array

{

    $stmt = conectar()->prepare(

        "SELECT u.usuario_id, u.nombre, u.apellido, u.correo, eg.fecha_asignacion

         FROM estudiante_grupo eg JOIN usuarios u ON u.usuario_id = eg.id_estudiante

         WHERE eg.id_grupo = ? AND u.rol = 'estudiante'

         ORDER BY u.nombre, u.apellido"

    );

    $stmt->execute([$id_grupo]);

    return $stmt->fetchAll();

}


function proyectos_grupo(int $id_grupo): array

{

    $stmt = conectar()->prepare(

        'SELECT t.id_tesis, t.titulo, t.estado, t.id_profesor, t.id_estudiante, e.nombre AS est_nombre, e.apellido AS est_apellido,

                p.nombre AS prof_nombre, p.apellido AS prof_apellido

         FROM tesis t

         JOIN usuarios e ON e.usuario_id = t.id_estudiante

         JOIN usuarios p ON p.usuario_id = t.id_profesor

         WHERE t.id_grupo = ? ORDER BY t.titulo'

    );

    $stmt->execute([$id_grupo]);

    return $stmt->fetchAll();

}

 



function proyectos_docente(int $id_profesor): array

{

    $stmt = conectar()->prepare(

        'SELECT t.id_tesis, t.titulo, t.resumen, t.estado, t.fecha_registro, t.id_grupo, t.id_profesor,

                (t.id_profesor = ?) AS es_director,

                e.nombre AS est_nombre, e.apellido AS est_apellido,

                p.nombre AS prof_nombre, p.apellido AS prof_apellido,

                g.nombre_grupo, g.id_curso, c.nombre_curso

         FROM tesis t

         JOIN usuarios e ON e.usuario_id = t.id_estudiante

         JOIN usuarios p ON p.usuario_id = t.id_profesor

         LEFT JOIN grupos g ON g.id_grupo = t.id_grupo

         LEFT JOIN cursos c ON c.id_curso = g.id_curso

         WHERE t.id_profesor = ?

            OR g.id_curso IN (SELECT id_curso FROM docente_curso WHERE id_profesor = ?)

         ORDER BY es_director DESC, t.fecha_registro DESC, t.id_tesis DESC'

    );

    $stmt->execute([$id_profesor, $id_profesor, $id_profesor]);

    return $stmt->fetchAll();

}

 


function fases_de_curso(int $id_curso): array

{

    $stmt = conectar()->prepare(

        "SELECT f.*, u.nombre AS creador_nombre, u.apellido AS creador_apellido,

                (SELECT COUNT(*) FROM fase_grupo fg WHERE fg.id_fase = f.id_fase) AS total_grupos,

                (SELECT COUNT(*) FROM fase_grupo fg WHERE fg.id_fase = f.id_fase AND fg.estado IN ('Aprobada','Completada')) AS grupos_terminados,

                (SELECT COUNT(*) FROM fase_grupo fg WHERE fg.id_fase = f.id_fase AND fg.estado IN ('Entregada','En revisión')) AS grupos_por_revisar,

                (SELECT COUNT(*) FROM criterios_fase cf WHERE cf.id_fase = f.id_fase) AS total_criterios

         FROM fases f JOIN usuarios u ON u.usuario_id = f.id_profesor_creador

         WHERE f.id_curso = ?

         ORDER BY f.orden, f.id_fase"

    );

    $stmt->execute([$id_curso]);

    return $stmt->fetchAll();

}

 

function criterios_de_fase(int $id_fase): array

{

    $stmt = conectar()->prepare('SELECT * FROM criterios_fase WHERE id_fase = ? ORDER BY orden, id_criterio');

    $stmt->execute([$id_fase]);

    return $stmt->fetchAll();

}

 


function ids_grupos_de_fase(int $id_fase): array

{

    $stmt = conectar()->prepare('SELECT id_grupo FROM fase_grupo WHERE id_fase = ?');

    $stmt->execute([$id_fase]);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

}



function avance_grupos_en_fase(int $id_fase): array

{

    $stmt = conectar()->prepare(

        "SELECT fg.*, g.nombre_grupo,

                (SELECT COUNT(*) FROM estudiante_grupo eg WHERE eg.id_grupo = g.id_grupo) AS total_estudiantes,

                (SELECT COUNT(*) FROM entregas en WHERE en.id_fase = fg.id_fase AND en.id_grupo = fg.id_grupo) AS total_entregas,

                (SELECT MAX(en.fecha_subida) FROM entregas en WHERE en.id_fase = fg.id_fase AND en.id_grupo = fg.id_grupo) AS ultima_entrega,

                (SELECT c.nota FROM calificaciones c WHERE c.id_fase = fg.id_fase AND c.id_grupo = fg.id_grupo

                    AND c.id_estudiante IS NULL ORDER BY c.fecha DESC LIMIT 1) AS nota_grupo

         FROM fase_grupo fg JOIN grupos g ON g.id_grupo = fg.id_grupo

         WHERE fg.id_fase = ?

         ORDER BY g.nombre_grupo"

    );

    $stmt->execute([$id_fase]);

    return $stmt->fetchAll();

}

 

function porcentaje_fase(array $fila): int

{

    return fase_terminada($fila['estado']) ? 100 : max(0, min(100, (int) $fila['porcentaje_avance']));

}

 


function fases_de_grupo(int $id_grupo, bool $incluir_borradores = false): array

{

    $sql = "SELECT f.*, fg.id_fase_grupo, fg.estado AS estado_grupo, fg.porcentaje_avance, fg.fecha_entrega,

                   fg.fecha_aprobacion, fg.fecha_actualizacion,

                   (SELECT COUNT(*) FROM entregas en WHERE en.id_fase = f.id_fase AND en.id_grupo = fg.id_grupo) AS total_entregas,

                   (SELECT c.nota FROM calificaciones c WHERE c.id_fase = f.id_fase AND c.id_grupo = fg.id_grupo

                       AND c.id_estudiante IS NULL ORDER BY c.fecha DESC LIMIT 1) AS nota_grupo

            FROM fase_grupo fg JOIN fases f ON f.id_fase = fg.id_fase

            WHERE fg.id_grupo = ?" . ($incluir_borradores ? '' : " AND f.estado <> 'Borrador'") . "

            ORDER BY f.orden, f.id_fase";

    $stmt = conectar()->prepare($sql);

    $stmt->execute([$id_grupo]);

    $filas = $stmt->fetchAll();

    foreach ($filas as &$f) {

        $f['estado'] = $f['estado_grupo'];           

        $f['porcentaje'] = porcentaje_fase($f);

    }

    return $filas;

}

 



function progreso_grupo(int $id_grupo, ?array $fases = null): array

{

    $fases = $fases ?? fases_de_grupo($id_grupo);

    $total = count($fases);

    $resultado = ['porcentaje' => 0, 'terminadas' => 0, 'total' => $total, 'fase_actual' => null, 'ponderado' => false];

    if (!$total) return $resultado;

 

    $con_peso = array_filter($fases, fn($f) => $f['peso'] !== null && (float) $f['peso'] > 0);

    $suma = 0;

    if (count($con_peso) === $total) {

        $pesos = array_sum(array_map(fn($f) => (float) $f['peso'], $fases));

        foreach ($fases as $f) $suma += $f['porcentaje'] * (float) $f['peso'];

        $resultado['porcentaje'] = (int) round($suma / $pesos);

        $resultado['ponderado'] = true;

    } else {

        foreach ($fases as $f) $suma += $f['porcentaje'];

        $resultado['porcentaje'] = (int) round($suma / $total);

    }

 

    foreach ($fases as $f) {

        if (fase_terminada($f['estado'])) {

            $resultado['terminadas']++;

        } elseif ($resultado['fase_actual'] === null) {

            $resultado['fase_actual'] = $f;

        }

    }

    return $resultado;

}

 

function asignar_fase_a_grupos(array $fase, array $ids_grupos): array

{

    $pdo = conectar();

    $id_fase = (int) $fase['id_fase'];

    $validos = array_map('intval', array_column(grupos_de_curso((int) $fase['id_curso']), 'id_grupo'));

    $ids_grupos = array_values(array_intersect(array_map('intval', $ids_grupos), $validos));

    $actuales = ids_grupos_de_fase($id_fase);

 

    $insertar = $pdo->prepare('INSERT IGNORE INTO fase_grupo (id_fase, id_grupo) VALUES (?, ?)');

    foreach (array_diff($ids_grupos, $actuales) as $g) $insertar->execute([$id_fase, $g]);

 

    $no_quitados = [];

    foreach (array_diff($actuales, $ids_grupos) as $g) {

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM entregas WHERE id_fase = ? AND id_grupo = ?');

        $stmt->execute([$id_fase, $g]);

        if ((int) $stmt->fetchColumn() > 0) {

            $n = $pdo->prepare('SELECT nombre_grupo FROM grupos WHERE id_grupo = ?');

            $n->execute([$g]);

            $no_quitados[] = $n->fetchColumn();

            continue;

        }

        $pdo->prepare('DELETE FROM fase_grupo WHERE id_fase = ? AND id_grupo = ?')->execute([$id_fase, $g]);

        // Quita la fila de avance del administrador solo si nunca avanzó.

        $pdo->prepare("DELETE tf FROM tesis_fase tf JOIN tesis t ON t.id_tesis = tf.id_tesis

                       WHERE tf.id_fase = ? AND t.id_grupo = ? AND tf.estado = 'Pendiente'")->execute([$id_fase, $g]);

    }

 

    foreach (ids_grupos_de_fase($id_fase) as $g) sincronizar_tesis_fase($id_fase, $g);

    return $no_quitados;

}

 



function sincronizar_tesis_fase(int $id_fase, int $id_grupo): void

{

    $pdo = conectar();

    $stmt = $pdo->prepare('SELECT f.estado AS estado_fase, f.fecha_inicio, f.fecha_limite, fg.estado, fg.fecha_aprobacion

                           FROM fase_grupo fg JOIN fases f ON f.id_fase = fg.id_fase

                           WHERE fg.id_fase = ? AND fg.id_grupo = ?');

    $stmt->execute([$id_fase, $id_grupo]);

    $fila = $stmt->fetch();

    if (!$fila || $fila['estado_fase'] === 'Borrador') return;

 

    if (fase_terminada($fila['estado'])) {

        $estado = 'Completada';

    } elseif ($fila['estado'] === 'Pendiente') {

        $vencida = $fila['fecha_limite'] && $fila['fecha_limite'] < date('Y-m-d');

        $estado = $vencida ? 'Atrasada' : 'Pendiente';

    } else {

        $estado = 'En progreso';

    }

    $completada = $estado === 'Completada' ? substr($fila['fecha_aprobacion'] ?? date('Y-m-d'), 0, 10) : null;

 

    $pdo->prepare(

        'INSERT INTO tesis_fase (id_tesis, id_fase, estado, fecha_inicio, fecha_limite, fecha_completada)

         SELECT t.id_tesis, ?, ?, ?, ?, ? FROM tesis t WHERE t.id_grupo = ?

         ON DUPLICATE KEY UPDATE estado = VALUES(estado),

             fecha_inicio = COALESCE(VALUES(fecha_inicio), tesis_fase.fecha_inicio),

             fecha_limite = COALESCE(VALUES(fecha_limite), tesis_fase.fecha_limite),

             fecha_completada = VALUES(fecha_completada)'

    )->execute([$id_fase, $estado, $fila['fecha_inicio'], $fila['fecha_limite'], $completada, $id_grupo]);

}

 



function actualizar_avance(int $id_fase, int $id_grupo, string $estado, ?int $porcentaje = null, ?int $id_profesor = null): void

{

    if (!in_array($estado, ESTADOS_AVANCE, true)) {

        throw new InvalidArgumentException('Estado no válido');

    }

    if (fase_terminada($estado)) $porcentaje = 100;

 

    $pdo = conectar();

    $pdo->prepare(

        'UPDATE fase_grupo

         SET estado = ?, porcentaje_avance = COALESCE(?, porcentaje_avance),

             fecha_aprobacion = IF(? IN (\'Aprobada\', \'Completada\'), COALESCE(fecha_aprobacion, NOW()), NULL),

             fecha_entrega = IF(? = \'Entregada\', NOW(), fecha_entrega),

             id_profesor_actualiza = ?, fecha_actualizacion = NOW()

         WHERE id_fase = ? AND id_grupo = ?'

    )->execute([$estado, $porcentaje, $estado, $estado, $id_profesor, $id_fase, $id_grupo]);

 

    sincronizar_tesis_fase($id_fase, $id_grupo);

}

 



function entregas_de_grupo_fase(int $id_grupo, int $id_fase): array

{

    $stmt = conectar()->prepare(

        'SELECT en.*, u.nombre, u.apellido, r.nombre AS rev_nombre, r.apellido AS rev_apellido

         FROM entregas en

         JOIN usuarios u ON u.usuario_id = en.id_estudiante

         LEFT JOIN usuarios r ON r.usuario_id = en.id_profesor_revisor

         WHERE en.id_grupo = ? AND en.id_fase = ?

         ORDER BY en.fecha_subida DESC, en.id_entrega DESC'

    );

    $stmt->execute([$id_grupo, $id_fase]);

    return $stmt->fetchAll();

}

 


function ultimas_entregas(int $id_grupo, int $id_fase): array

{

    $stmt = conectar()->prepare(

        'SELECT en.* FROM entregas en

         WHERE en.id_grupo = ? AND en.id_fase = ?

           AND en.version = (SELECT MAX(x.version) FROM entregas x

                             WHERE x.id_grupo = en.id_grupo AND x.id_fase = en.id_fase AND x.id_estudiante = en.id_estudiante)'

    );

    $stmt->execute([$id_grupo, $id_fase]);

    return $stmt->fetchAll();

}

 



function entregas_docente(int $id_profesor, array $f = [], int $limite = 200): array

{

    $sql = "SELECT en.*, u.nombre, u.apellido, fa.nombre_fase, fa.orden, fa.fecha_limite, g.nombre_grupo, g.id_curso, c.nombre_curso

            FROM entregas en

            JOIN usuarios u ON u.usuario_id = en.id_estudiante

            JOIN fases fa ON fa.id_fase = en.id_fase

            JOIN grupos g ON g.id_grupo = en.id_grupo

            JOIN cursos c ON c.id_curso = g.id_curso

            JOIN docente_curso dc ON dc.id_curso = g.id_curso AND dc.id_profesor = ?

            WHERE en.version = (SELECT MAX(x.version) FROM entregas x

                                WHERE x.id_grupo = en.id_grupo AND x.id_fase = en.id_fase AND x.id_estudiante = en.id_estudiante)";

    $p = [$id_profesor];

    foreach (['id_curso' => 'g.id_curso', 'id_grupo' => 'en.id_grupo', 'id_fase' => 'en.id_fase'] as $k => $col) {

        if (!empty($f[$k])) { $sql .= " AND $col = ?"; $p[] = (int) $f[$k]; }

    }

    if (!empty($f['estado']) && in_array($f['estado'], ESTADOS_ENTREGA, true)) { $sql .= ' AND en.estado = ?'; $p[] = $f['estado']; }

    if (!empty($f['por_revisar'])) $sql .= " AND en.estado IN ('Entregado', 'Corregido', 'En revisión')";

    $sql .= ' ORDER BY en.fecha_subida DESC LIMIT ' . (int) $limite;

    $stmt = conectar()->prepare($sql);

    $stmt->execute($p);

    return $stmt->fetchAll();

}

 


function trabajos_pendientes_docente(int $id_profesor, array $f = []): array

{

    $sql = "SELECT fg.*, fa.nombre_fase, fa.orden, fa.fecha_limite, g.nombre_grupo, g.id_curso, c.nombre_curso,

                   (SELECT COUNT(*) FROM entregas en WHERE en.id_fase = fg.id_fase AND en.id_grupo = fg.id_grupo) AS total_entregas

            FROM fase_grupo fg

            JOIN fases fa ON fa.id_fase = fg.id_fase

            JOIN grupos g ON g.id_grupo = fg.id_grupo

            JOIN cursos c ON c.id_curso = g.id_curso

            JOIN docente_curso dc ON dc.id_curso = g.id_curso AND dc.id_profesor = ?

            WHERE fa.estado = 'Publicada' AND fg.estado IN ('Pendiente', 'En progreso', 'Requiere corrección')";

    $p = [$id_profesor];

    foreach (['id_curso' => 'g.id_curso', 'id_grupo' => 'fg.id_grupo', 'id_fase' => 'fg.id_fase'] as $k => $col) {

        if (!empty($f[$k])) { $sql .= " AND $col = ?"; $p[] = (int) $f[$k]; }

    }

    $sql .= ' ORDER BY fa.fecha_limite IS NULL, fa.fecha_limite, fa.orden, g.nombre_grupo';

    $stmt = conectar()->prepare($sql);

    $stmt->execute($p);

    return $stmt->fetchAll();

}

 



function revisiones_de_entrega(int $id_entrega): array

{

    $stmt = conectar()->prepare(

        'SELECT r.*, u.nombre, u.apellido FROM revisiones_entrega r JOIN usuarios u ON u.usuario_id = r.id_profesor

         WHERE r.id_entrega = ? ORDER BY r.fecha DESC'

    );

    $stmt->execute([$id_entrega]);

    return $stmt->fetchAll();

}

 



function revisar_ultimas_entregas(int $id_profesor, int $id_grupo, int $id_fase, string $estado_entrega, ?string $comentario): int

{

    $pdo = conectar();

    $n = 0;

    $upd = $pdo->prepare('UPDATE entregas SET estado = ?, id_profesor_revisor = ?, fecha_revision = NOW() WHERE id_entrega = ?');

    $rev = $pdo->prepare('INSERT INTO revisiones_entrega (id_entrega, id_profesor, estado, comentario) VALUES (?, ?, ?, ?)');

    foreach (ultimas_entregas($id_grupo, $id_fase) as $en) {

        if ($en['estado'] === $estado_entrega) continue;

        $upd->execute([$estado_entrega, $id_profesor, $en['id_entrega']]);

        $rev->execute([$en['id_entrega'], $id_profesor, $estado_entrega, $comentario]);

        $n++;

    }

    return $n;

}

 


function registrar_entrega(int $id_estudiante, int $id_grupo, int $id_fase, array $archivo, ?string $comentario = null): array

{

    $pdo = conectar();

    $stmt = $pdo->prepare("SELECT f.*, fg.estado AS estado_grupo

                           FROM fase_grupo fg JOIN fases f ON f.id_fase = fg.id_fase

                           JOIN estudiante_grupo eg ON eg.id_grupo = fg.id_grupo AND eg.id_estudiante = ?

                           WHERE fg.id_fase = ? AND fg.id_grupo = ?");

    $stmt->execute([$id_estudiante, $id_fase, $id_grupo]);

    $fase = $stmt->fetch();

    if (!$fase)                                   return [null, 'La fase no está asignada a su grupo.'];

    if ($fase['estado'] !== 'Publicada')          return [null, 'La fase no está recibiendo entregas.'];

    if (in_array($fase['estado_grupo'], ['En revisión', 'Aprobada', 'Completada'], true)) {

        return [null, 'La fase está en revisión o ya fue aprobada.'];

    }

 

    [$guardado, $error] = guardar_archivo($archivo, 'entregas', TIPOS_ENTREGA[$fase['tipo_entrega']][1] ?? TIPOS_ENTREGA['cualquiera'][1]);

    if ($error) return [null, $error];

 

    $stmt = $pdo->prepare('SELECT COALESCE(MAX(version), 0) + 1 FROM entregas WHERE id_fase = ? AND id_grupo = ? AND id_estudiante = ?');

    $stmt->execute([$id_fase, $id_grupo, $id_estudiante]);

    $version = (int) $stmt->fetchColumn();

    $estado = $fase['estado_grupo'] === 'Requiere corrección' ? 'Corregido' : 'Entregado';

 

    $pdo->prepare('INSERT INTO entregas (id_fase, id_grupo, id_estudiante, version, archivo_ruta, nombre_original, extension, tamano, comentario_estudiante, estado)

                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')

        ->execute([$id_fase, $id_grupo, $id_estudiante, $version, $guardado['ruta'], $guardado['nombre'], $guardado['ext'], $guardado['tamano'], $comentario, $estado]);

    $id = (int) $pdo->lastInsertId();

    actualizar_avance($id_fase, $id_grupo, 'Entregada');

    return [$id, null];

}

 



function guardar_archivo(array $archivo, string $carpeta, array $extensiones): array

{

    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {

        return [null, ($archivo['error'] ?? 0) === UPLOAD_ERR_INI_SIZE || ($archivo['error'] ?? 0) === UPLOAD_ERR_FORM_SIZE

            ? 'El archivo supera el tamaño permitido.' : 'No se recibió el archivo.'];

    }

    if ($archivo['size'] > TAMANO_MAXIMO) return [null, 'El archivo supera los 10 MB.'];

    $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $extensiones, true)) {

        return [null, 'Formato no permitido. Use: ' . implode(', ', $extensiones) . '.'];

    }

    $dir = CARPETA_UPLOADS . '/' . $carpeta;

    if (!is_dir($dir) && !mkdir($dir, 0775, true)) return [null, 'No se pudo crear la carpeta de archivos.'];

    $ruta = bin2hex(random_bytes(16)) . '.' . $ext;

    $ok = is_uploaded_file($archivo['tmp_name'])

        ? move_uploaded_file($archivo['tmp_name'], "$dir/$ruta")

        : rename($archivo['tmp_name'], "$dir/$ruta");

    if (!$ok) return [null, 'No se pudo guardar el archivo.'];

    $nombre = mb_substr(preg_replace('/[^\p{L}\p{N} ._()-]/u', '_', basename($archivo['name'])), 0, 200);

    return [['ruta' => $ruta, 'nombre' => $nombre, 'ext' => $ext, 'tamano' => (int) $archivo['size']], null];

}

 


function enviar_archivo(string $carpeta, string $ruta, string $nombre, bool $descargar = false): void

{

    $archivo = CARPETA_UPLOADS . '/' . $carpeta . '/' . basename($ruta);

    if (!is_file($archivo)) {

        http_response_code(404);

        exit('El archivo ya no existe en el servidor.');

    }

    $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));

    $en_linea = !$descargar && isset(TIPOS_MIME_VISIBLES[$ext]);

    header('Content-Type: ' . ($en_linea ? TIPOS_MIME_VISIBLES[$ext] : 'application/octet-stream'));

    header('Content-Length: ' . filesize($archivo));

    header('X-Content-Type-Options: nosniff');

    header('X-Frame-Options: SAMEORIGIN');

    if ($ext !== 'pdf') {   

        header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");

    }

    header('Content-Disposition: ' . ($en_linea ? 'inline' : 'attachment') . "; filename*=UTF-8''" . rawurlencode($nombre));

    readfile($archivo);

    exit;

}

 


function comentarios(array $f, int $limite = 200): array

{

    $sql = 'SELECT r.*, u.nombre, u.apellido, e.nombre AS est_nombre, e.apellido AS est_apellido,

                   fa.nombre_fase, fa.orden, g.nombre_grupo, c.nombre_curso, t.titulo, en.version, en.nombre_original

            FROM retroalimentacion r

            JOIN usuarios u ON u.usuario_id = r.id_profesor

            JOIN grupos g ON g.id_grupo = r.id_grupo

            JOIN cursos c ON c.id_curso = r.id_curso

            LEFT JOIN usuarios e ON e.usuario_id = r.id_estudiante

            LEFT JOIN fases fa ON fa.id_fase = r.id_fase

            LEFT JOIN tesis t ON t.id_tesis = r.id_tesis

            LEFT JOIN entregas en ON en.id_entrega = r.id_entrega

            WHERE 1 = 1';

    $p = [];

    foreach (['id_grupo', 'id_fase', 'id_tesis', 'id_entrega', 'id_profesor', 'id_estudiante'] as $k) {

        if (!empty($f[$k])) { $sql .= " AND r.$k = ?"; $p[] = (int) $f[$k]; }

    }

    if (isset($f['cursos'])) {

        $sql .= ' AND r.id_curso IN (' . marcas($f['cursos']) . ')';

        $p = array_merge($p, $f['cursos']);

    }

    if (!empty($f['tipo']) && in_array($f['tipo'], TIPOS_COMENTARIO, true)) { $sql .= ' AND r.tipo = ?'; $p[] = $f['tipo']; }

    $sql .= ' ORDER BY r.fecha DESC LIMIT ' . (int) $limite;

    $stmt = conectar()->prepare($sql);

    $stmt->execute($p);

    return $stmt->fetchAll();

}

 


function guardar_comentario(int $id_profesor, array $grupo, ?int $id_fase, string $tipo, string $texto,

                            ?int $id_estudiante = null, ?int $id_entrega = null): int

{

    $pdo = conectar();



    $proyectos = proyectos_grupo((int) $grupo['id_grupo']);

    $id_tesis = null;

    foreach ($proyectos as $p) {

        if ($id_estudiante === null || (int) ($p['id_estudiante'] ?? 0) === $id_estudiante) { $id_tesis = (int) $p['id_tesis']; break; }

    }

    $pdo->prepare('INSERT INTO retroalimentacion (id_profesor, id_curso, id_grupo, id_fase, id_tesis, id_estudiante, id_entrega, tipo, comentario)

                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')

        ->execute([$id_profesor, $grupo['id_curso'], $grupo['id_grupo'], $id_fase, $id_tesis, $id_estudiante, $id_entrega, $tipo, $texto]);

    return (int) $pdo->lastInsertId();

}

 



function leer_nota($valor): ?float

{

    $valor = str_replace(',', '.', trim((string) $valor));

    if ($valor === '' || !is_numeric($valor)) return null;

    $n = round((float) $valor, 1);

    return ($n < NOTA_MIN || $n > NOTA_MAX) ? null : $n;

}

 


function calificaciones(array $f, int $limite = 300): array

{

    $sql = 'SELECT ca.*, u.nombre, u.apellido, e.nombre AS est_nombre, e.apellido AS est_apellido,

                   fa.nombre_fase, fa.orden, fa.peso, g.nombre_grupo, c.nombre_curso, en.version, en.nombre_original

            FROM calificaciones ca

            JOIN usuarios u ON u.usuario_id = ca.id_profesor

            JOIN fases fa ON fa.id_fase = ca.id_fase

            JOIN grupos g ON g.id_grupo = ca.id_grupo

            JOIN cursos c ON c.id_curso = ca.id_curso

            LEFT JOIN usuarios e ON e.usuario_id = ca.id_estudiante

            LEFT JOIN entregas en ON en.id_entrega = ca.id_entrega

            WHERE 1 = 1';

    $p = [];

    foreach (['id_grupo', 'id_fase', 'id_tesis', 'id_profesor'] as $k) {

        if (!empty($f[$k])) { $sql .= " AND ca.$k = ?"; $p[] = (int) $f[$k]; }

    }

    if (isset($f['cursos'])) {

        $sql .= ' AND ca.id_curso IN (' . marcas($f['cursos']) . ')';

        $p = array_merge($p, $f['cursos']);

    }

    $sql .= ' ORDER BY fa.orden, g.nombre_grupo, ca.id_estudiante IS NOT NULL, e.nombre, ca.fecha DESC LIMIT ' . (int) $limite;

    $stmt = conectar()->prepare($sql);

    $stmt->execute($p);

    return $stmt->fetchAll();

}

 

function notas_por_criterio(int $id_calificacion): array

{

    $stmt = conectar()->prepare('SELECT cc.id_criterio, cc.nota, cf.nombre, cf.peso

                                 FROM calificacion_criterio cc JOIN criterios_fase cf ON cf.id_criterio = cc.id_criterio

                                 WHERE cc.id_calificacion = ? ORDER BY cf.orden');

    $stmt->execute([$id_calificacion]);

    return $stmt->fetchAll();

}

 

function historial_calificacion(int $id_calificacion): array

{

    $stmt = conectar()->prepare('SELECT h.*, u.nombre, u.apellido FROM calificaciones_historial h

                                 JOIN usuarios u ON u.usuario_id = h.id_profesor

                                 WHERE h.id_calificacion = ? ORDER BY h.fecha DESC');

    $stmt->execute([$id_calificacion]);

    return $stmt->fetchAll();

}

 



function nota_desde_criterios(array $criterios, array $notas): ?float

{

    $suma = 0; $pesos = 0; $n = 0;

    $todos_con_peso = $criterios && count(array_filter($criterios, fn($c) => (float) $c['peso'] > 0)) === count($criterios);

    foreach ($criterios as $c) {

        if (!isset($notas[$c['id_criterio']])) return null;

        $peso = $todos_con_peso ? (float) $c['peso'] : 1;

        $suma += $notas[$c['id_criterio']] * $peso;

        $pesos += $peso; $n++;

    }

    return $n ? round($suma / $pesos, 1) : null;

}

 



function guardar_calificacion(int $id_profesor, array $grupo, int $id_fase, float $nota, ?string $observacion,

                              ?int $id_estudiante, ?int $id_entrega, array $notas_criterio = []): int

{

    $pdo = conectar();

    $stmt = $pdo->prepare('SELECT * FROM calificaciones

                           WHERE id_fase = ? AND id_grupo = ? AND id_profesor = ?

                             AND id_estudiante <=> ? AND id_entrega <=> ?');

    $stmt->execute([$id_fase, $grupo['id_grupo'], $id_profesor, $id_estudiante, $id_entrega]);

    $actual = $stmt->fetch();

 

    $id_tesis = null;

    foreach (proyectos_grupo((int) $grupo['id_grupo']) as $p) { $id_tesis = (int) $p['id_tesis']; break; }

 

    if ($actual) {

        $id = (int) $actual['id_calificacion'];

        $pdo->prepare('UPDATE calificaciones SET nota = ?, observacion = ?, fecha_modificacion = NOW() WHERE id_calificacion = ?')

            ->execute([$nota, $observacion, $id]);

        $anterior = (float) $actual['nota'];

    } else {

        $pdo->prepare('INSERT INTO calificaciones (id_profesor, id_curso, id_grupo, id_fase, id_tesis, id_estudiante, id_entrega, nota, observacion)

                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')

            ->execute([$id_profesor, $grupo['id_curso'], $grupo['id_grupo'], $id_fase, $id_tesis, $id_estudiante, $id_entrega, $nota, $observacion]);

        $id = (int) $pdo->lastInsertId();

        $anterior = null;

    }

 

    $pdo->prepare('DELETE FROM calificacion_criterio WHERE id_calificacion = ?')->execute([$id]);

    $ins = $pdo->prepare('INSERT INTO calificacion_criterio (id_calificacion, id_criterio, nota) VALUES (?, ?, ?)');

    foreach ($notas_criterio as $id_criterio => $n) $ins->execute([$id, $id_criterio, $n]);

 

    if ($anterior === null || $anterior !== $nota) {

        $pdo->prepare('INSERT INTO calificaciones_historial (id_calificacion, nota_anterior, nota_nueva, observacion, id_profesor)

                       VALUES (?, ?, ?, ?, ?)')->execute([$id, $anterior, $nota, $observacion, $id_profesor]);

    }

    return $id;

}

 


function nota_acumulada_grupo(array $fases_grupo): ?float

{

    $con_nota = array_filter($fases_grupo, fn($f) => $f['nota_grupo'] !== null);

    if (!$con_nota) return null;

    $ponderar = count(array_filter($con_nota, fn($f) => (float) $f['peso'] > 0)) === count($con_nota);

    $suma = 0; $pesos = 0;

    foreach ($con_nota as $f) {

        $w = $ponderar ? (float) $f['peso'] : 1;

        $suma += (float) $f['nota_grupo'] * $w; $pesos += $w;

    }

    return round($suma / $pesos, 1);

}

 



function incentivos(array $f): array

{

    $sql = 'SELECT i.*, c.nombre_curso, fa.nombre_fase, fa.orden AS orden_fase, t.titulo AS titulo_tesis,

                   u.nombre AS creador_nombre, u.apellido AS creador_apellido,

                   (SELECT COUNT(*) FROM incentivo_otorgado o WHERE o.id_incentivo_grupal = i.id_incentivo_grupal) AS total_otorgados

            FROM incentivos_grupales i

            JOIN cursos c ON c.id_curso = i.id_curso

            JOIN usuarios u ON u.usuario_id = i.id_profesor

            LEFT JOIN fases fa ON fa.id_fase = i.id_fase

            LEFT JOIN tesis t ON t.id_tesis = i.id_tesis

            WHERE 1 = 1';

    $p = [];

    foreach (['id_curso', 'id_fase', 'id_tesis', 'id_profesor'] as $k) {

        if (!empty($f[$k])) { $sql .= " AND i.$k = ?"; $p[] = (int) $f[$k]; }

    }

    if (isset($f['cursos'])) {

        $sql .= ' AND i.id_curso IN (' . marcas($f['cursos']) . ')';

        $p = array_merge($p, $f['cursos']);

    }

    if (!empty($f['estado'])) { $sql .= ' AND i.estado = ?'; $p[] = $f['estado']; }

    $sql .= " ORDER BY FIELD(i.estado, 'Activo', 'Inactivo', 'Finalizado'), i.fecha_creacion DESC";

    $stmt = conectar()->prepare($sql);

    $stmt->execute($p);

    return $stmt->fetchAll();

}

 

function incentivo_para_docente(int $id_profesor, int $id): ?array

{

    $stmt = conectar()->prepare(

        'SELECT i.*, c.nombre_curso, fa.nombre_fase, t.titulo AS titulo_tesis, u.nombre AS creador_nombre, u.apellido AS creador_apellido

         FROM incentivos_grupales i

         JOIN cursos c ON c.id_curso = i.id_curso

         JOIN usuarios u ON u.usuario_id = i.id_profesor

         JOIN docente_curso dc ON dc.id_curso = i.id_curso AND dc.id_profesor = ?

         LEFT JOIN fases fa ON fa.id_fase = i.id_fase

         LEFT JOIN tesis t ON t.id_tesis = i.id_tesis

         WHERE i.id_incentivo_grupal = ?'

    );

    $stmt->execute([$id_profesor, $id]);

    return $stmt->fetch() ?: null;

}

 


function incentivos_otorgados(array $f, int $limite = 200): array

{

    $sql = 'SELECT o.*, i.nombre AS incentivo, i.icono, i.valor, i.id_fase, fa.nombre_fase, g.nombre_grupo, c.nombre_curso,

                   e.nombre AS est_nombre, e.apellido AS est_apellido, u.nombre, u.apellido

            FROM incentivo_otorgado o

            JOIN incentivos_grupales i ON i.id_incentivo_grupal = o.id_incentivo_grupal

            JOIN grupos g ON g.id_grupo = o.id_grupo

            JOIN cursos c ON c.id_curso = g.id_curso

            JOIN usuarios u ON u.usuario_id = o.id_profesor

            LEFT JOIN usuarios e ON e.usuario_id = o.id_estudiante

            LEFT JOIN fases fa ON fa.id_fase = i.id_fase

            WHERE 1 = 1';

    $p = [];

    foreach (['id_incentivo_grupal' => 'o.id_incentivo_grupal', 'id_grupo' => 'o.id_grupo', 'id_tesis' => 'i.id_tesis'] as $k => $col) {

        if (!empty($f[$k])) { $sql .= " AND $col = ?"; $p[] = (int) $f[$k]; }

    }

    if (isset($f['cursos'])) {

        $sql .= ' AND g.id_curso IN (' . marcas($f['cursos']) . ')';

        $p = array_merge($p, $f['cursos']);

    }

    $sql .= ' ORDER BY o.fecha DESC LIMIT ' . (int) $limite;

    $stmt = conectar()->prepare($sql);

    $stmt->execute($p);

    return $stmt->fetchAll();

}

 


function actividad_reciente(int $id_profesor, int $limite = 12, ?int $id_grupo = null): array

{

    $cursos = ids_cursos_docente($id_profesor);

    if (!$cursos) return [];

    $in = marcas($cursos);

    $fg = $id_grupo ? ' AND g.id_grupo = ' . (int) $id_grupo : '';

 

    $sql = "

      (SELECT 'entrega' AS tipo, en.fecha_subida AS fecha, CONCAT(u.nombre, ' ', u.apellido) AS actor,

              g.nombre_grupo, fa.nombre_fase, fa.orden, en.version AS dato, en.estado AS extra, en.id_grupo, en.id_fase

         FROM entregas en JOIN usuarios u ON u.usuario_id = en.id_estudiante

         JOIN grupos g ON g.id_grupo = en.id_grupo JOIN fases fa ON fa.id_fase = en.id_fase

        WHERE g.id_curso IN ($in) $fg)

      UNION ALL

      (SELECT 'revision', r.fecha, CONCAT(u.nombre, ' ', u.apellido), g.nombre_grupo, fa.nombre_fase, fa.orden, r.estado, NULL, en.id_grupo, en.id_fase

         FROM revisiones_entrega r JOIN entregas en ON en.id_entrega = r.id_entrega

         JOIN usuarios u ON u.usuario_id = r.id_profesor

         JOIN grupos g ON g.id_grupo = en.id_grupo JOIN fases fa ON fa.id_fase = en.id_fase

        WHERE g.id_curso IN ($in) $fg)

      UNION ALL

      (SELECT 'comentario', re.fecha, CONCAT(u.nombre, ' ', u.apellido), g.nombre_grupo, fa.nombre_fase, fa.orden, re.tipo, NULL, re.id_grupo, re.id_fase

         FROM retroalimentacion re JOIN usuarios u ON u.usuario_id = re.id_profesor

         JOIN grupos g ON g.id_grupo = re.id_grupo LEFT JOIN fases fa ON fa.id_fase = re.id_fase

        WHERE re.id_curso IN ($in) $fg)

      UNION ALL

      (SELECT 'nota', COALESCE(ca.fecha_modificacion, ca.fecha), CONCAT(u.nombre, ' ', u.apellido), g.nombre_grupo, fa.nombre_fase, fa.orden, ca.nota,

              IF(ca.id_estudiante IS NULL, 'grupo', 'estudiante'), ca.id_grupo, ca.id_fase

         FROM calificaciones ca JOIN usuarios u ON u.usuario_id = ca.id_profesor

         JOIN grupos g ON g.id_grupo = ca.id_grupo JOIN fases fa ON fa.id_fase = ca.id_fase

        WHERE ca.id_curso IN ($in) $fg)

      UNION ALL

      (SELECT 'incentivo', o.fecha, CONCAT(u.nombre, ' ', u.apellido), g.nombre_grupo, i.nombre, NULL,

              CONCAT(COALESCE(i.icono, ''), ' ', i.nombre), CONCAT(COALESCE(e.nombre, ''), ' ', COALESCE(e.apellido, '')), o.id_grupo, i.id_fase

         FROM incentivo_otorgado o JOIN incentivos_grupales i ON i.id_incentivo_grupal = o.id_incentivo_grupal

         JOIN usuarios u ON u.usuario_id = o.id_profesor JOIN grupos g ON g.id_grupo = o.id_grupo

         LEFT JOIN usuarios e ON e.usuario_id = o.id_estudiante

        WHERE g.id_curso IN ($in) $fg)

      UNION ALL

      (SELECT 'avance', fgr.fecha_actualizacion, COALESCE(CONCAT(u.nombre, ' ', u.apellido), 'Estudiantes del grupo'), g.nombre_grupo,

              fa.nombre_fase, fa.orden, fgr.estado, fgr.porcentaje_avance, fgr.id_grupo, fgr.id_fase

         FROM fase_grupo fgr JOIN grupos g ON g.id_grupo = fgr.id_grupo JOIN fases fa ON fa.id_fase = fgr.id_fase

         LEFT JOIN usuarios u ON u.usuario_id = fgr.id_profesor_actualiza

        WHERE fgr.fecha_actualizacion IS NOT NULL AND g.id_curso IN ($in) $fg)

      " . ($id_grupo ? '' : "

      UNION ALL

      (SELECT 'fase', COALESCE(fa.fecha_modificacion, fa.fecha_creacion), CONCAT(u.nombre, ' ', u.apellido), c.nombre_curso,

              fa.nombre_fase, fa.orden, IF(fa.fecha_modificacion IS NULL, 'creó', 'actualizó'), NULL, NULL, fa.id_fase

         FROM fases fa JOIN usuarios u ON u.usuario_id = fa.id_profesor_creador JOIN cursos c ON c.id_curso = fa.id_curso

        WHERE fa.id_curso IN ($in))") . "

      ORDER BY fecha DESC LIMIT " . (int) $limite;

 

    $p = $id_grupo ? array_merge($cursos, $cursos, $cursos, $cursos, $cursos, $cursos)

                   : array_merge($cursos, $cursos, $cursos, $cursos, $cursos, $cursos, $cursos);

    $stmt = conectar()->prepare($sql);

    $stmt->execute($p);

    return $stmt->fetchAll();

}

 


function texto_actividad(array $a): string

{

    $fase = $a['orden'] !== null ? 'Fase ' . $a['orden'] . ' · ' . $a['nombre_fase'] : (string) $a['nombre_fase'];

    switch ($a['tipo']) {

        case 'entrega':

            return '<strong>' . e($a['actor']) . '</strong> (' . e($a['nombre_grupo']) . ') '

                . ((int) $a['dato'] > 1 ? 'envió la versión ' . (int) $a['dato'] . ' de ' : 'entregó un trabajo en ')

                . '<em>' . e($fase) . '</em>';

        case 'revision':

            return '<strong>' . e($a['actor']) . '</strong> marcó como <em>' . e($a['dato']) . '</em> el trabajo de '

                . e($a['nombre_grupo']) . ' en ' . e($fase);

        case 'comentario':

            return '<strong>' . e($a['actor']) . '</strong> agregó un comentario (' . e(mb_strtolower($a['dato'])) . ') a '

                . e($a['nombre_grupo']) . ($a['nombre_fase'] ? ' en ' . e($fase) : '');

        case 'nota':

            return '<strong>' . e($a['actor']) . '</strong> asignó nota <strong>' . e(formato_nota($a['dato'])) . '</strong> '

                . ($a['extra'] === 'grupo' ? 'al grupo ' : 'a un estudiante de ') . e($a['nombre_grupo']) . ' en ' . e($fase);

        case 'incentivo':

            return '<strong>' . e($a['actor']) . '</strong> otorgó «' . e(trim($a['dato'])) . '» '

                . (trim((string) $a['extra']) !== '' ? 'a ' . e(trim($a['extra'])) . ' (' . e($a['nombre_grupo']) . ')' : 'al grupo ' . e($a['nombre_grupo']));

        case 'avance':

            return e($a['nombre_grupo']) . ' quedó en <em>' . e($a['dato']) . '</em> en ' . e($fase)

                . ((int) $a['extra'] > 0 && !fase_terminada($a['dato']) ? ' (' . (int) $a['extra'] . ' %)' : '')

                . ' <small>· ' . e($a['actor']) . '</small>';

        case 'fase':

            return $a['dato'] === 'creó'

                ? '<strong>' . e($a['actor']) . '</strong> creó la fase <em>' . e($fase) . '</em> del curso ' . e($a['nombre_grupo'])

                : 'Se actualizó la fase <em>' . e($fase) . '</em> del curso ' . e($a['nombre_grupo']);

    }

    return '';

}

 



function firma_cambios(int $id_profesor): array

{

    $cursos = ids_cursos_docente($id_profesor);

    if (!$cursos) return ['firma' => 'vacio', 'por_revisar' => 0];

    $in = marcas($cursos);

    $sql = "SELECT CONCAT_WS('|',

              (SELECT CONCAT(COUNT(*), '-', COALESCE(MAX(en.fecha_subida), ''), '-', COALESCE(MAX(en.fecha_revision), ''))

                 FROM entregas en JOIN grupos g ON g.id_grupo = en.id_grupo WHERE g.id_curso IN ($in)),

              (SELECT CONCAT(COUNT(*), '-', COALESCE(MAX(fg.fecha_actualizacion), ''))

                 FROM fase_grupo fg JOIN grupos g ON g.id_grupo = fg.id_grupo WHERE g.id_curso IN ($in)),

              (SELECT CONCAT(COUNT(*), '-', COALESCE(MAX(COALESCE(fecha_modificacion, fecha_creacion)), ''))

                 FROM fases WHERE id_curso IN ($in)),

              (SELECT CONCAT(COUNT(*), '-', COALESCE(MAX(COALESCE(fecha_edicion, fecha)), '')) FROM retroalimentacion WHERE id_curso IN ($in)),

              (SELECT CONCAT(COUNT(*), '-', COALESCE(MAX(COALESCE(fecha_modificacion, fecha)), '')) FROM calificaciones WHERE id_curso IN ($in)),

              (SELECT CONCAT(COUNT(*), '-', COALESCE(MAX(o.fecha), '')) FROM incentivo_otorgado o

                 JOIN grupos g ON g.id_grupo = o.id_grupo WHERE g.id_curso IN ($in)),

              (SELECT COUNT(*) FROM estudiante_grupo eg JOIN grupos g ON g.id_grupo = eg.id_grupo WHERE g.id_curso IN ($in))

            ) AS firma";

    $stmt = conectar()->prepare($sql);

    $stmt->execute(array_merge($cursos, $cursos, $cursos, $cursos, $cursos, $cursos, $cursos));

    return ['firma' => md5((string) $stmt->fetchColumn()), 'por_revisar' => total_por_revisar($id_profesor)];

}

 



function total_por_revisar(int $id_profesor): int

{

    $stmt = conectar()->prepare(

        "SELECT COUNT(*) FROM entregas en

         JOIN grupos g ON g.id_grupo = en.id_grupo

         JOIN docente_curso dc ON dc.id_curso = g.id_curso AND dc.id_profesor = ?

         WHERE en.estado IN ('Entregado', 'Corregido', 'En revisión')

           AND en.version = (SELECT MAX(x.version) FROM entregas x

                             WHERE x.id_grupo = en.id_grupo AND x.id_fase = en.id_fase AND x.id_estudiante = en.id_estudiante)"

    );

    $stmt->execute([$id_profesor]);

    return (int) $stmt->fetchColumn();

}