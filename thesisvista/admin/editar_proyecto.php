<?php

require_once __DIR__ . '/../includes/seguridad.php';
require_once __DIR__ . '/../includes/funciones_admin.php';

$admin = requerir_rol('administrador');

$id    = (int) ($_GET['id'] ?? 0);
$tesis = buscar_tesis($id);
if (!$tesis) {
    mensaje('error', 'El proyecto solicitado no existe.');
    redirigir('proyectos.php');
}

$datos   = $tesis + ['crear_fases' => false];
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
                $pdo->beginTransaction();

                $stmt = $pdo->prepare('UPDATE tesis SET titulo = ?, resumen = ?, estado = ?, fecha_registro = ?,
                                              id_estudiante = ?, id_profesor = ?, id_grupo = ?
                                       WHERE id_tesis = ?');
                $stmt->execute([
                    $datos['titulo'], $datos['resumen'], $datos['estado'], $datos['fecha_registro'],
                    $datos['id_estudiante'], $datos['id_profesor'], $datos['id_grupo'], $id,
                ]);

                $notas = asegurar_relaciones($datos['id_estudiante'], $datos['id_profesor'], $datos['id_grupo']);
                if ($datos['crear_fases']) {
                    $fases = crear_fases_faltantes($id, $datos['id_grupo']);
                    if ($fases > 0) $notas[] = "se crearon $fases fases del curso";
                }

                $pdo->commit();

                mensaje('exito', 'Proyecto "' . $datos['titulo'] . '" actualizado'
                    . ($notas ? '; además ' . implode(', ', $notas) : '') . '.');
                redirigir('ver_proyecto.php?id=' . $id);
            } catch (PDOException $ex) {
                $pdo->rollBack();
                error_log($ex->getMessage());
                $errores[] = 'No se pudieron guardar los cambios.';
            }
        }
    }
}

$estudiantes = usuarios_por_rol('estudiante');
$profesores  = usuarios_por_rol('profesor');
$grupos      = lista_grupos();

$titulo   = 'Editar proyecto / tesis';
$seccion  = 'proyectos';
$es_nuevo = false;
$accion   = 'editar_proyecto.php?id=' . $id;
require __DIR__ . '/../includes/header.php';
?>
<div class="tarjeta" style="max-width:900px">
    <h2>Editar: <?= e($tesis['titulo']) ?> <small>(ID <?= $id ?>)</small></h2>
    <?php require __DIR__ . '/../includes/form_proyecto.php'; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>