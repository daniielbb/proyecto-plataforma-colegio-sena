<?php

require_once __DIR__ . '/../includes/seguridad.php';
require_once __DIR__ . '/../includes/funciones_admin.php';

$admin  = requerir_rol('administrador');
$cursos = lista_cursos();

$datos = [
    'nombre_grupo' => '',
    'id_curso'     => count($cursos) === 1 ? (int) $cursos[0]['id_curso'] : 0,
    'estudiantes'  => [],
];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $errores[] = 'El formulario expiró. Intente de nuevo.';
    } else {
        [$datos, $errores] = validar_grupo($_POST);

        if (!$errores) {
            $pdo = conectar();
            try {
                $pdo->beginTransaction();   // el grupo y sus estudiantes se guardan juntos, o nada

                $pdo->prepare('INSERT INTO grupos (id_curso, nombre_grupo, fecha_creacion) VALUES (?, ?, CURDATE())')
                    ->execute([$datos['id_curso'], $datos['nombre_grupo']]);
                $id_grupo = (int) $pdo->lastInsertId();

                [$agregados] = sincronizar_estudiantes_grupo($id_grupo, $datos['estudiantes']);

                $pdo->commit();

                mensaje('exito', 'Grupo "' . $datos['nombre_grupo'] . '" creado con ' . $agregados
                    . ($agregados === 1 ? ' estudiante.' : ' estudiantes.'));
                redirigir('ver_grupo.php?id=' . $id_grupo);
            } catch (PDOException $ex) {
                $pdo->rollBack();
                error_log($ex->getMessage());
                $errores[] = 'No se pudo guardar el grupo en la base de datos.';
            }
        }
    }
}

$estudiantes = estudiantes_para_selector();
$bloqueados  = [];

$titulo   = 'Crear grupo';
$seccion  = 'grupos';
$es_nuevo = true;
$accion   = 'crear_grupo.php';
$cancelar = 'grupos.php';
require __DIR__ . '/../includes/header.php';
?>
<div class="tarjeta" style="max-width:1000px">
    <h2>Datos del nuevo grupo</h2>
    <?php if (!$cursos): ?>
        <div class="alerta alerta-aviso">Para crear un grupo debe existir al menos un curso en la tabla <code>cursos</code>.</div>
    <?php endif; ?>
    <?php require __DIR__ . '/../includes/form_grupo.php'; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>