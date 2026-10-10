<?php

require_once __DIR__ . '/../includes/seguridad.php';
require_once __DIR__ . '/includes/funciones_admin.php';

$admin = requerir_rol('administrador');

$id    = (int) ($_GET['id'] ?? 0);
$grupo = buscar_grupo($id);
if (!$grupo) {
    mensaje('error', 'El grupo solicitado no existe.');
    redirigir('grupos.php');
}

$total_estudiantes = count(ids_estudiantes_grupo($id));
$proyectos         = proyectos_de_grupo($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        mensaje('error', 'El formulario expiró. Intente de nuevo.');
    } elseif ($proyectos) {
        mensaje('error', 'No se puede eliminar: el grupo tiene proyectos vinculados.');
    } else {
        $pdo = conectar();
        try {
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM estudiante_grupo WHERE id_grupo = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM grupos WHERE id_grupo = ?')->execute([$id]);
            $pdo->commit();
            mensaje('exito', 'Grupo "' . $grupo['nombre_grupo'] . '" eliminado. Sus estudiantes siguen registrados en la plataforma.');
        } catch (PDOException $ex) {
            $pdo->rollBack();
            error_log($ex->getMessage());
            mensaje('error', 'No se pudo eliminar el grupo. No se borró ningún dato.');
        }
    }
    redirigir('grupos.php');
}

$titulo  = 'Eliminar grupo';
$seccion = 'grupos';
require __DIR__ . '/includes/header.php';
?>
<div class="tarjeta" style="max-width:640px">
    <h2>¿Eliminar el grupo "<?= e($grupo['nombre_grupo']) ?>"?</h2>
    <p>Curso: <?= e($grupo['nombre_curso']) ?> · Estudiantes: <?= $total_estudiantes ?></p>

    <?php if ($proyectos): ?>
        <div class="alerta alerta-error">
            Este grupo no se puede eliminar porque tiene proyectos vinculados:
            <ul>
                <?php foreach ($proyectos as $p): ?>
                    <li><a href="editar_proyecto.php?id=<?= (int) $p['id_tesis'] ?>"><?= e($p['titulo']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <p>Cambie primero el grupo de esos proyectos (o déjelos sin grupo) desde <em>Editar proyecto</em>.</p>
        <a href="ver_grupo.php?id=<?= $id ?>" class="btn btn-secundario">Volver al grupo</a>
    <?php else: ?>
        <div class="alerta alerta-aviso">
            Esta acción no se puede deshacer. Los <?= $total_estudiantes ?> estudiantes dejarán de pertenecer a este grupo,
            pero sus cuentas no se eliminan.
        </div>
        <form method="post" action="eliminar_grupo.php?id=<?= $id ?>" class="formulario">
            <?= campo_csrf() ?>
            <div class="botones">
                <button type="submit" class="btn btn-peligro">Sí, eliminar grupo</button>
                <a href="ver_grupo.php?id=<?= $id ?>" class="btn btn-secundario">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
