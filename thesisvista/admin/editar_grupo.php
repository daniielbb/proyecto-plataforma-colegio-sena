<?php

require_once __DIR__ . '/../includes/seguridad.php';
require_once __DIR__ . '/../includes/funciones_admin.php';

$admin = requerir_rol('administrador');

$id    = (int) ($_GET['id'] ?? 0);
$grupo = buscar_grupo($id);
if (!$grupo) {
    mensaje('error', 'El grupo solicitado no existe.');
    redirigir('grupos.php');
}

$datos = [
    'nombre_grupo' => $grupo['nombre_grupo'],
    'id_curso'     => (int) $grupo['id_curso'],
    'estudiantes'  => ids_estudiantes_grupo($id),
];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $errores[] = 'El formulario expiró. Intente de nuevo.';
    } else {
        [$datos, $errores] = validar_grupo($_POST, $grupo);

        if (!$errores) {
            $pdo = conectar();
            try {
                $pdo->beginTransaction();

                $pdo->prepare('UPDATE grupos SET nombre_grupo = ?, id_curso = ? WHERE id_grupo = ?')
                    ->execute([$datos['nombre_grupo'], $datos['id_curso'], $id]);

                [$agregados, $quitados] = sincronizar_estudiantes_grupo($id, $datos['estudiantes']);

                $pdo->commit();

                $detalle = [];
                if ($agregados) $detalle[] = $agregados . ($agregados === 1 ? ' estudiante agregado' : ' estudiantes agregados');
                if ($quitados)  $detalle[] = $quitados . ($quitados === 1 ? ' estudiante quitado' : ' estudiantes quitados');
                mensaje('exito', 'Grupo "' . $datos['nombre_grupo'] . '" actualizado'
                    . ($detalle ? ' (' . implode(', ', $detalle) . ')' : '') . '.');
                redirigir('ver_grupo.php?id=' . $id);
            } catch (PDOException $ex) {
                $pdo->rollBack();
                error_log($ex->getMessage());
                $errores[] = 'No se pudieron guardar los cambios.';
            }
        }
    }
}

$cursos      = lista_cursos();
$estudiantes = estudiantes_para_selector($id);
$bloqueados  = estudiantes_bloqueados_grupo($id);

$titulo   = 'Editar grupo';
$seccion  = 'grupos';
$es_nuevo = false;
$accion   = 'editar_grupo.php?id=' . $id;
$cancelar = 'ver_grupo.php?id=' . $id;
require __DIR__ . '/../includes/header.php';
?>
<div class="tarjeta" style="max-width:1000px">
    <h2>Editar: <?= e($grupo['nombre_grupo']) ?> <small>(ID <?= $id ?>)</small></h2>
    <?php require __DIR__ . '/../includes/form_grupo.php'; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>