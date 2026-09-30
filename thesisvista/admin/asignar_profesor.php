<?php
/**
 * THESISVISTA - Asignar docente a un proyecto / tesis
 * GET ?id= -> muestra el docente actual | POST -> UPDATE tesis SET id_profesor
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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_profesor = (int) ($_POST['id_profesor'] ?? 0);

    if (!csrf_valido()) {
        $error = 'El formulario expiró. Intente de nuevo.';
    } elseif (!es_usuario_con_rol($id_profesor, 'profesor')) {
        $error = 'Seleccione un docente válido.';
    } elseif ($id_profesor === (int) $tesis['id_profesor']) {
        $error = 'Ese docente ya está asignado a este proyecto.';
    } else {
        $pdo = conectar();
        try {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE tesis SET id_profesor = ? WHERE id_tesis = ?')->execute([$id_profesor, $id]);
            $grupo = $tesis['id_grupo'] === null ? null : (int) $tesis['id_grupo'];
            $notas = asegurar_relaciones((int) $tesis['id_estudiante'], $id_profesor, $grupo);
            $pdo->commit();

            $nuevo = buscar_usuario($id_profesor);
            mensaje('exito', 'Docente ' . $nuevo['nombre'] . ' ' . $nuevo['apellido'] . ' asignado al proyecto "'
                . $tesis['titulo'] . '"' . ($notas ? '; además ' . implode(', ', $notas) : '') . '.');
            redirigir('ver_proyecto.php?id=' . $id);
        } catch (PDOException $ex) {
            $pdo->rollBack();
            error_log($ex->getMessage());
            $error = 'No se pudo asignar el docente.';
        }
    }
}

$profesores = usuarios_por_rol('profesor');

$titulo  = 'Asignar docente';
$seccion = 'proyectos';
require __DIR__ . '/includes/header.php';
?>
<div class="tarjeta" style="max-width:620px">
    <h2><?= e($tesis['titulo']) ?></h2>
    <p>Estudiante: <strong><?= e($tesis['est_nombre'] . ' ' . $tesis['est_apellido']) ?></strong><br>
       Docente actual: <strong><?= e($tesis['prof_nombre'] . ' ' . $tesis['prof_apellido']) ?></strong></p>

    <?php if ($error): ?><div class="alerta alerta-error"><?= e($error) ?></div><?php endif; ?>

    <form method="post" action="asignar_profesor.php?id=<?= $id ?>" class="formulario">
        <?= campo_csrf() ?>
        <label for="id_profesor">Nuevo docente</label>
        <select id="id_profesor" name="id_profesor" required>
            <?php foreach ($profesores as $p): ?>
                <option value="<?= (int) $p['usuario_id'] ?>" <?= (int) $tesis['id_profesor'] === (int) $p['usuario_id'] ? 'selected' : '' ?>>
                    <?= e($p['nombre'] . ' ' . $p['apellido'] . ' (' . $p['correo'] . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="botones">
            <button type="submit" class="btn btn-primario">Asignar docente</button>
            <a href="ver_proyecto.php?id=<?= $id ?>" class="btn btn-secundario">Cancelar</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
