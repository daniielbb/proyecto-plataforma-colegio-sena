<?php
/**
 * THESISVISTA - Crear proyecto / tesis
 * Formulario -> POST -> validar -> INSERT INTO tesis (+ relaciones) -> mensaje -> lista
 */
require_once __DIR__ . '/../includes/seguridad.php';
require_once __DIR__ . '/includes/funciones_admin.php';

$admin = requerir_rol('administrador');

$datos = [
    'titulo' => '', 'resumen' => '', 'estado' => 'Borrador', 'fecha_registro' => date('Y-m-d'),
    'id_estudiante' => 0, 'id_profesor' => 0, 'id_grupo' => null, 'crear_fases' => true,
];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $errores[] = 'El formulario expiró. Intente de nuevo.';
    } else {
        [$datos, $errores] = validar_proyecto($_POST);
        $datos['crear_fases'] = isset($_POST['crear_fases']);

        if (!$errores) {
            $pdo = conectar();
            try {
                $pdo->beginTransaction();   // todo se guarda junto, o nada

                $stmt = $pdo->prepare('INSERT INTO tesis (titulo, resumen, estado, fecha_registro, id_estudiante, id_profesor, id_grupo)
                                       VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $datos['titulo'], $datos['resumen'], $datos['estado'], $datos['fecha_registro'],
                    $datos['id_estudiante'], $datos['id_profesor'], $datos['id_grupo'],
                ]);
                $id_tesis = (int) $pdo->lastInsertId();

                $notas = asegurar_relaciones($datos['id_estudiante'], $datos['id_profesor'], $datos['id_grupo']);
                if ($datos['crear_fases']) {
                    $fases = crear_fases_faltantes($id_tesis, $datos['id_grupo']);
                    if ($fases > 0) $notas[] = "se crearon $fases fases del curso";
                }

                $pdo->commit();

                mensaje('exito', 'Proyecto "' . $datos['titulo'] . '" creado (ID ' . $id_tesis . ')'
                    . ($notas ? '; además ' . implode(', ', $notas) : '') . '.');
                redirigir('ver_proyecto.php?id=' . $id_tesis);
            } catch (PDOException $ex) {
                $pdo->rollBack();
                error_log($ex->getMessage());
                $errores[] = 'No se pudo guardar el proyecto en la base de datos.';
            }
        }
    }
}

$estudiantes = usuarios_por_rol('estudiante');
$profesores  = usuarios_por_rol('profesor');
$grupos      = lista_grupos();

$titulo   = 'Crear proyecto / tesis';
$seccion  = 'proyectos';
$es_nuevo = true;
$accion   = 'crear_proyecto.php';
require __DIR__ . '/includes/header.php';
?>
<div class="tarjeta" style="max-width:900px">
    <h2>Datos del nuevo proyecto</h2>
    <?php if (!$estudiantes || !$profesores): ?>
        <div class="alerta alerta-aviso">Para crear un proyecto debe existir al menos un estudiante y un docente.
            <a href="crear_usuario.php">Crear usuario</a></div>
    <?php endif; ?>
    <?php require __DIR__ . '/includes/form_proyecto.php'; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
