<?php
/**
 * THESISVISTA - Eliminar proyecto / tesis
 * GET ?id= -> página de confirmación con lo que se borrará (sin JavaScript)
 * POST     -> borra en una transacción los registros dependientes y luego la tesis.
 *
 * Las claves foráneas no tienen ON DELETE CASCADE, por eso se borran
 * primero las filas hijas en el orden correcto.
 */
require_once __DIR__ . '/../includes/seguridad.php';
require_once __DIR__ . '/includes/funciones_admin.php';

$admin = requerir_rol('administrador');

$id    = (int) ($_GET['id'] ?? 0);
$tesis = buscar_tesis($id);
if (!$tesis) {
    mensaje('error', 'El proyecto solicitado no existe.');
    redirigir('proyectos.php');
}

$dependencias = dependencias_tesis($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        mensaje('error', 'El formulario expiró. Intente de nuevo.');
        redirigir('proyectos.php');
    }

    $pdo = conectar();
    try {
        $pdo->beginTransaction();

        // Tablas de la extensión del módulo docente (si existen)
        if (existe_tabla('correcciones'))    $pdo->prepare('DELETE FROM correcciones WHERE id_tesis = ?')->execute([$id]);
        if (existe_tabla('requisitos_fase')) $pdo->prepare('DELETE FROM requisitos_fase WHERE id_tesis = ?')->execute([$id]);

        // Tablas de la base original
        $pdo->prepare('DELETE FROM comentarios WHERE id_tesis = ?')->execute([$id]);
        // Los incentivos son del estudiante: se conservan, solo se quita el vínculo con la tesis (id_tesis admite NULL).
        $pdo->prepare('UPDATE estudiante_incentivo SET id_tesis = NULL WHERE id_tesis = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM documento WHERE id_tesis = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM tesis_fase WHERE id_tesis = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM tesis WHERE id_tesis = ?')->execute([$id]);

        $pdo->commit();
        mensaje('exito', 'Proyecto "' . $tesis['titulo'] . '" eliminado junto con sus fases, documentos y comentarios.');
    } catch (PDOException $ex) {
        $pdo->rollBack();
        error_log($ex->getMessage());
        mensaje('error', 'No se pudo eliminar el proyecto. No se borró ningún dato.');
    }
    redirigir('proyectos.php');
}

$titulo  = 'Eliminar proyecto';
$seccion = 'proyectos';
require __DIR__ . '/includes/header.php';
?>
<div class="tarjeta" style="max-width:640px">
    <h2>¿Eliminar el proyecto "<?= e($tesis['titulo']) ?>"?</h2>
    <p>Estudiante: <?= e($tesis['est_nombre'] . ' ' . $tesis['est_apellido']) ?> ·
       Docente: <?= e($tesis['prof_nombre'] . ' ' . $tesis['prof_apellido']) ?></p>

    <div class="alerta alerta-aviso">
        Esta acción no se puede deshacer. También se eliminarán:
        <ul>
            <?php foreach ($dependencias as $texto => $cantidad): ?>
                <?php if ($texto === 'Incentivos vinculados'): ?>
                    <li><?= e($texto) ?>: <?= $cantidad ?> <small>(se conservan para el estudiante, sin tesis)</small></li>
                <?php else: ?>
                    <li><?= e($texto) ?>: <?= $cantidad ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>

    <form method="post" action="eliminar_proyecto.php?id=<?= $id ?>" class="formulario">
        <?= campo_csrf() ?>
        <div class="botones">
            <button type="submit" class="btn btn-peligro">Sí, eliminar proyecto</button>
            <a href="ver_proyecto.php?id=<?= $id ?>" class="btn btn-secundario">Cancelar</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
