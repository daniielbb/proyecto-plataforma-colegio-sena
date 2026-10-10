<?php

require_once __DIR__ . '/grupos.php';

const ESTADOS_TESIS = ['Borrador', 'En revisión', 'Aprobada', 'Rechazada'];

function existe_tabla(string $tabla): bool
{
    $stmt = conectar()->prepare('SELECT COUNT(*) FROM information_schema.TABLES
                                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $stmt->execute([$tabla]);
    return (int) $stmt->fetchColumn() > 0;
}

function clase_estado(string $estado): string
{
    return [
        'Borrador'    => 'etiqueta-gris',
        'En revisión' => 'etiqueta-ambar',
        'Aprobada'    => 'etiqueta-verde',
        'Rechazada'   => 'etiqueta-roja',
    ][$estado] ?? 'etiqueta-gris';
}

function buscar_usuario(int $id): ?array
{
    $stmt = conectar()->prepare('SELECT usuario_id, nombre, apellido, correo, rol
                                 FROM usuarios WHERE usuario_id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function usuarios_por_rol(string $rol): array
{
    $stmt = conectar()->prepare('SELECT usuario_id, nombre, apellido, correo
                                 FROM usuarios WHERE rol = ? ORDER BY nombre, apellido');
    $stmt->execute([$rol]);
    return $stmt->fetchAll();
}

function validar_usuario(array $post, bool $es_nuevo, ?string $correo_actual = null, ?int $id = null): array
{
    $d = [
        'nombre'     => trim($post['nombre'] ?? ''),
        'apellido'   => trim($post['apellido'] ?? ''),
        'correo'     => trim($post['correo'] ?? ''),
        'contrasena' => $post['contrasena'] ?? '',
        'rol'        => $post['rol'] ?? '',
    ];
    $errores = [];

    if ($d['nombre'] === '' || mb_strlen($d['nombre']) > 100)     $errores[] = 'El nombre es obligatorio (máximo 100 caracteres).';
    if ($d['apellido'] === '' || mb_strlen($d['apellido']) > 100) $errores[] = 'El apellido es obligatorio (máximo 100 caracteres).';

    if ($d['correo'] === '' || mb_strlen($d['correo']) > 150) {
        $errores[] = 'El correo es obligatorio (máximo 150 caracteres).';
    } elseif ($d['correo'] !== $correo_actual) {
        if (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo no tiene un formato válido (ej: nombre@dominio.com).';
        } else {
            $stmt = conectar()->prepare('SELECT COUNT(*) FROM usuarios WHERE correo = ? AND usuario_id <> ?');
            $stmt->execute([$d['correo'], $id ?? 0]);
            if ($stmt->fetchColumn() > 0) $errores[] = 'Ya existe otro usuario con ese correo.';
        }
    }

    if ($es_nuevo || $d['contrasena'] !== '') {
        if (mb_strlen($d['contrasena']) < 4) $errores[] = 'La contraseña debe tener al menos 4 caracteres.';
    }

    if (!isset(ROLES[$d['rol']])) $errores[] = 'Seleccione un rol válido.';

    return [$d, $errores];
}

function dependencias_usuario(int $id): array
{
    $consultas = [
        ['Tesis donde es el estudiante',     'tesis',                'id_estudiante',       'estudiante'],
        ['Tesis donde es el docente',        'tesis',                'id_profesor',         'profesor'],
        ['Grupos (estudiante_grupo)',        'estudiante_grupo',     'id_estudiante',       'estudiante'],
        ['Incentivos obtenidos',             'estudiante_incentivo', 'id_estudiante',       'estudiante'],
        ['Cursos asignados (docente_curso)', 'docente_curso',        'id_profesor',         'profesor'],
        ['Fases creadas',                    'fases',                'id_profesor_creador', 'profesor'],
        ['Comentarios escritos',             'comentarios',          'id_usuario',          null],
        ['Revisiones / correcciones',        'correcciones',         'id_profesor',         'profesor'],
        ['Requisitos de fase',               'requisitos_fase',      'id_profesor',         'profesor'],
    ];

    $resultado = [];
    foreach ($consultas as [$texto, $tabla, $columna, $rol]) {
        if (!existe_tabla($tabla)) continue;
        $stmt = conectar()->prepare("SELECT COUNT(*) FROM `$tabla` WHERE `$columna` = ?");
        $stmt->execute([$id]);
        $cantidad = (int) $stmt->fetchColumn();
        if ($cantidad > 0) $resultado[] = [$texto, $cantidad, $rol];
    }
    return $resultado;
}

function error_cambio_rol(array $usuario, string $rol_nuevo, int $id_admin): ?string
{
    if ($usuario['rol'] === $rol_nuevo) return null;

    if ((int) $usuario['usuario_id'] === $id_admin) {
        return 'No puede cambiar su propio rol (perdería el acceso de administrador).';
    }

    $bloqueos = [];
    foreach (dependencias_usuario((int) $usuario['usuario_id']) as [$texto, $cantidad, $rol]) {
        if ($rol === $usuario['rol']) $bloqueos[] = "$texto: $cantidad";
    }
    if ($bloqueos) {
        return 'No se puede cambiar el rol porque el usuario tiene registros como '
            . nombre_rol($usuario['rol']) . ' (' . implode(', ', $bloqueos)
            . '). Reasigne o elimine esos registros primero.';
    }
    return null;
}

if (!function_exists('lista_cursos')) {
    function lista_cursos(): array
    {
        return conectar()->query('SELECT id_curso, nombre_curso FROM cursos ORDER BY nombre_curso')->fetchAll();
    }
}

if (!function_exists('buscar_grupo')) {
    function buscar_grupo(int $id): ?array
    {
        $stmt = conectar()->prepare(
            'SELECT g.id_grupo, g.nombre_grupo, g.id_curso, g.fecha_creacion, c.nombre_curso
             FROM grupos g JOIN cursos c ON c.id_curso = g.id_curso
             WHERE g.id_grupo = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}

if (!function_exists('ids_estudiantes_grupo')) {
    function ids_estudiantes_grupo(int $id_grupo): array
    {
        $stmt = conectar()->prepare('SELECT id_estudiante FROM estudiante_grupo WHERE id_grupo = ?');
        $stmt->execute([$id_grupo]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}

if (!function_exists('estudiantes_de_grupo')) {
    function estudiantes_de_grupo(int $id_grupo): array
    {
        $stmt = conectar()->prepare(
            'SELECT u.usuario_id, u.nombre, u.apellido, u.correo, eg.fecha_asignacion
             FROM estudiante_grupo eg JOIN usuarios u ON u.usuario_id = eg.id_estudiante
             WHERE eg.id_grupo = ? ORDER BY u.nombre, u.apellido'
        );
        $stmt->execute([$id_grupo]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('estudiantes_para_selector')) {
    function estudiantes_para_selector(?int $id_grupo_actual = null): array
    {
        $stmt = conectar()->prepare(
            "SELECT u.usuario_id, u.nombre, u.apellido, u.correo,
                    GROUP_CONCAT(g.nombre_grupo ORDER BY g.nombre_grupo SEPARATOR ', ') AS otros_grupos
             FROM usuarios u
             LEFT JOIN estudiante_grupo eg ON eg.id_estudiante = u.usuario_id AND eg.id_grupo <> ?
             LEFT JOIN grupos g ON g.id_grupo = eg.id_grupo
             WHERE u.rol = 'estudiante'
             GROUP BY u.usuario_id, u.nombre, u.apellido, u.correo
             ORDER BY u.nombre, u.apellido"
        );
        $stmt->execute([$id_grupo_actual ?? 0]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('estudiantes_bloqueados_grupo')) {
    function estudiantes_bloqueados_grupo(int $id_grupo): array
    {
        $stmt = conectar()->prepare(
            'SELECT DISTINCT u.usuario_id, u.nombre, u.apellido
             FROM tesis t JOIN usuarios u ON u.usuario_id = t.id_estudiante
             WHERE t.id_grupo = ?'
        );
        $stmt->execute([$id_grupo]);
        $resultado = [];
        foreach ($stmt->fetchAll() as $u) {
            $resultado[(int) $u['usuario_id']] = $u['nombre'] . ' ' . $u['apellido'];
        }
        return $resultado;
    }
}

if (!function_exists('proyectos_de_grupo')) {
    function proyectos_de_grupo(int $id_grupo): array
    {
        $stmt = conectar()->prepare(
            'SELECT t.id_tesis, t.titulo, t.estado, t.id_estudiante,
                    p.nombre AS prof_nombre, p.apellido AS prof_apellido
             FROM tesis t JOIN usuarios p ON p.usuario_id = t.id_profesor
             WHERE t.id_grupo = ? ORDER BY t.titulo'
        );
        $stmt->execute([$id_grupo]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('docentes_de_curso')) {
    function docentes_de_curso(int $id_curso): array
    {
        $stmt = conectar()->prepare(
            'SELECT u.usuario_id, u.nombre, u.apellido, u.correo
             FROM docente_curso dc JOIN usuarios u ON u.usuario_id = dc.id_profesor
             WHERE dc.id_curso = ? ORDER BY u.nombre, u.apellido'
        );
        $stmt->execute([$id_curso]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('validar_grupo')) {
    function validar_grupo(array $post, ?array $grupo = null): array
    {
        $ids = [];
        foreach ((array) ($post['estudiantes'] ?? []) as $valor) {
            $valor = (int) $valor;
            if ($valor > 0) $ids[$valor] = $valor;
        }

        $d = [
            'nombre_grupo' => trim($post['nombre_grupo'] ?? ''),
            'id_curso'     => (int) ($post['id_curso'] ?? 0),
            'estudiantes'  => array_values($ids),
        ];
        $errores  = [];
        $pdo      = conectar();
        $id_grupo = $grupo ? (int) $grupo['id_grupo'] : 0;

        if ($d['nombre_grupo'] === '' || mb_strlen($d['nombre_grupo']) > 100) {
            $errores[] = 'El nombre del grupo es obligatorio (máximo 100 caracteres).';
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM cursos WHERE id_curso = ?');
        $stmt->execute([$d['id_curso']]);
        if ($stmt->fetchColumn() == 0) {
            $errores[] = 'Seleccione un curso válido.';
        } elseif ($d['nombre_grupo'] !== '') {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM grupos WHERE nombre_grupo = ? AND id_curso = ? AND id_grupo <> ?');
            $stmt->execute([$d['nombre_grupo'], $d['id_curso'], $id_grupo]);
            if ($stmt->fetchColumn() > 0) $errores[] = 'Ya existe un grupo con ese nombre en el curso seleccionado.';
        }

        if ($d['estudiantes']) {
            $marcas = implode(',', array_fill(0, count($d['estudiantes']), '?'));
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = 'estudiante' AND usuario_id IN ($marcas)");
            $stmt->execute($d['estudiantes']);
            if ((int) $stmt->fetchColumn() !== count($d['estudiantes'])) {
                $errores[] = 'Uno o más de los estudiantes seleccionados ya no existen o no tienen rol de estudiante.';
            }
        }

        if ($grupo) {
            $faltan = array_diff_key(estudiantes_bloqueados_grupo($id_grupo), array_flip($d['estudiantes']));
            if ($faltan) {
                $errores[] = 'No se puede quitar a ' . implode(', ', $faltan)
                    . ' porque tiene un proyecto vinculado a este grupo. Cambie primero el grupo del proyecto.';
            }
            if ($d['id_curso'] !== (int) $grupo['id_curso'] && proyectos_de_grupo($id_grupo)) {
                $errores[] = 'No se puede cambiar el curso de un grupo que tiene proyectos vinculados.';
            }
        }

        return [$d, $errores];
    }
}

if (!function_exists('sincronizar_estudiantes_grupo')) {
    function sincronizar_estudiantes_grupo(int $id_grupo, array $ids_nuevos): array
    {
        $pdo      = conectar();
        $actuales = ids_estudiantes_grupo($id_grupo);

        $agregar = array_diff($ids_nuevos, $actuales);
        $quitar  = array_diff($actuales, $ids_nuevos);

        $insertar = $pdo->prepare('INSERT INTO estudiante_grupo (id_estudiante, id_grupo, fecha_asignacion) VALUES (?, ?, CURDATE())');
        foreach ($agregar as $id) $insertar->execute([$id, $id_grupo]);

        $borrar = $pdo->prepare('DELETE FROM estudiante_grupo WHERE id_estudiante = ? AND id_grupo = ?');
        foreach ($quitar as $id) $borrar->execute([$id, $id_grupo]);

        return [count($agregar), count($quitar)];
    }
}

if (!function_exists('lista_grupos')) {
    function lista_grupos(): array
    {
        return conectar()->query('SELECT g.id_grupo, g.nombre_grupo, g.id_curso, c.nombre_curso,
                                         (SELECT COUNT(*) FROM estudiante_grupo eg WHERE eg.id_grupo = g.id_grupo) AS total_estudiantes
                                  FROM grupos g JOIN cursos c ON c.id_curso = g.id_curso
                                  ORDER BY c.nombre_curso, g.nombre_grupo')->fetchAll();
    }
}

function buscar_tesis(int $id): ?array
{
    $stmt = conectar()->prepare(
        'SELECT t.*,
                e.nombre AS est_nombre, e.apellido AS est_apellido, e.correo AS est_correo,
                p.nombre AS prof_nombre, p.apellido AS prof_apellido, p.correo AS prof_correo,
                g.nombre_grupo, c.nombre_curso
         FROM tesis t
         JOIN usuarios e ON e.usuario_id = t.id_estudiante
         JOIN usuarios p ON p.usuario_id = t.id_profesor
         LEFT JOIN grupos g ON g.id_grupo = t.id_grupo
         LEFT JOIN cursos c ON c.id_curso = g.id_curso
         WHERE t.id_tesis = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function es_usuario_con_rol(int $id, string $rol): bool
{
    $stmt = conectar()->prepare('SELECT COUNT(*) FROM usuarios WHERE usuario_id = ? AND rol = ?');
    $stmt->execute([$id, $rol]);
    return $stmt->fetchColumn() > 0;
}

function validar_proyecto(array $post): array
{
    $d = [
        'titulo'         => trim($post['titulo'] ?? ''),
        'resumen'        => trim($post['resumen'] ?? ''),
        'estado'         => $post['estado'] ?? '',
        'fecha_registro' => $post['fecha_registro'] ?? '',
        'id_estudiante'  => (int) ($post['id_estudiante'] ?? 0),
        'id_profesor'    => (int) ($post['id_profesor'] ?? 0),
        'id_grupo'       => ($post['id_grupo'] ?? '') === '' ? null : (int) $post['id_grupo'],
    ];
    $errores = [];

    if ($d['titulo'] === '' || mb_strlen($d['titulo']) > 255) $errores[] = 'El título es obligatorio (máximo 255 caracteres).';
    if ($d['resumen'] === '') $errores[] = 'El resumen es obligatorio.';
    if (!in_array($d['estado'], ESTADOS_TESIS, true)) $errores[] = 'Seleccione un estado válido.';

    $fecha = DateTime::createFromFormat('Y-m-d', $d['fecha_registro']);
    if (!$fecha || $fecha->format('Y-m-d') !== $d['fecha_registro']) $errores[] = 'La fecha de registro no es válida.';

    if (!es_usuario_con_rol($d['id_estudiante'], 'estudiante')) $errores[] = 'Seleccione un estudiante válido.';
    if (!es_usuario_con_rol($d['id_profesor'], 'profesor'))     $errores[] = 'Seleccione un docente válido.';

    if ($d['id_grupo'] !== null) {
        $stmt = conectar()->prepare('SELECT COUNT(*) FROM grupos WHERE id_grupo = ?');
        $stmt->execute([$d['id_grupo']]);
        if ($stmt->fetchColumn() == 0) $errores[] = 'El grupo seleccionado no existe.';
    }

    return [$d, $errores];
}

function asegurar_relaciones(int $id_estudiante, int $id_profesor, ?int $id_grupo): array
{
    if ($id_grupo === null) return [];

    $pdo   = conectar();
    $notas = [];

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM estudiante_grupo WHERE id_estudiante = ? AND id_grupo = ?');
    $stmt->execute([$id_estudiante, $id_grupo]);
    if ($stmt->fetchColumn() == 0) {
        $pdo->prepare('INSERT INTO estudiante_grupo (id_estudiante, id_grupo, fecha_asignacion) VALUES (?, ?, CURDATE())')
            ->execute([$id_estudiante, $id_grupo]);
        $notas[] = 'el estudiante fue agregado al grupo';
    }

    $stmt = $pdo->prepare('SELECT id_curso FROM grupos WHERE id_grupo = ?');
    $stmt->execute([$id_grupo]);
    $id_curso = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM docente_curso WHERE id_profesor = ? AND id_curso = ?');
    $stmt->execute([$id_profesor, $id_curso]);
    if ($stmt->fetchColumn() == 0) {
        $pdo->prepare('INSERT INTO docente_curso (id_profesor, id_curso, fecha_asignacion) VALUES (?, ?, CURDATE())')
            ->execute([$id_profesor, $id_curso]);
        $notas[] = 'el docente fue asignado al curso del grupo';
    }

    return $notas;
}

function crear_fases_faltantes(int $id_tesis, ?int $id_grupo): int
{
    if ($id_grupo === null) return 0;

    $stmt = conectar()->prepare(
        "INSERT INTO tesis_fase (id_tesis, id_fase, estado)
         SELECT ?, f.id_fase, 'Pendiente'
         FROM fases f
         JOIN grupos g ON g.id_curso = f.id_curso
         WHERE g.id_grupo = ? AND f.activa = 1
           AND NOT EXISTS (SELECT 1 FROM tesis_fase tf WHERE tf.id_tesis = ? AND tf.id_fase = f.id_fase)
         ORDER BY f.orden"
    );
    $stmt->execute([$id_tesis, $id_grupo, $id_tesis]);
    return $stmt->rowCount();
}

function dependencias_tesis(int $id): array
{
    $tablas = [
        'Fases del proyecto (tesis_fase)' => 'tesis_fase',
        'Documentos'                      => 'documento',
        'Comentarios'                     => 'comentarios',
        'Incentivos vinculados'           => 'estudiante_incentivo',
        'Revisiones / correcciones'       => 'correcciones',
        'Requisitos específicos'          => 'requisitos_fase',
    ];
    $resultado = [];
    foreach ($tablas as $texto => $tabla) {
        if (!existe_tabla($tabla)) continue;
        $stmt = conectar()->prepare("SELECT COUNT(*) FROM `$tabla` WHERE id_tesis = ?");
        $stmt->execute([$id]);
        $resultado[$texto] = (int) $stmt->fetchColumn();
    }
    return $resultado;
}