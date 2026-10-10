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

$estudiantes = estudiantes_de_grupo($id);
$docentes    = docentes_de_curso((int) $grupo['id_curso']);
$proyectos   = proyectos_de_grupo($id);

$proyecto_de = [];
foreach ($proyectos as $p) $proyecto_de[(int) $p['id_estudiante']][] = $p;

$titulo  = 'Consultar grupo';
$seccion = 'grupos';
require __DIR__ . '/../includes/header.php';
?>

<div class="tarjeta">
    <div class="tarjeta-cabecera">
        <h2>Grupo <?= e($grupo['nombre_grupo']) ?></h2>
        <div class="acciones">
            <a class="btn btn-secundario" href="editar_grupo.php?id=<?= $id ?>">Editar / agregar estudiantes</a>
            <a class="btn btn-peligro" href="eliminar_grupo.php?id=<?= $id ?>">Eliminar</a>
        </div>
    </div>

    <dl class="detalle">
        <dt>ID</dt>                <dd><?= $id ?></dd>
        <dt>Curso</dt>             <dd><?= e($grupo['nombre_curso']) ?></dd>
        <dt>Fecha de creación</dt> <dd><?= e($grupo['fecha_creacion']) ?></dd>
        <dt>Estudiantes</dt>       <dd><strong><?= count($estudiantes) ?></strong></dd>
    </dl>
</div>

<div class="rejilla-2">
    <div class="tarjeta">
        <div class="tarjeta-cabecera">
            <h2>Estudiantes del grupo (<?= count($estudiantes) ?>)</h2>
            <a class="btn btn-primario btn-chico" href="editar_grupo.php?id=<?= $id ?>">+ Agregar estudiantes</a>
        </div>
        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>Estudiante</th><th>Correo</th><th>En el grupo desde</th><th>Proyecto</th></tr></thead>
                <tbody>
                <?php foreach ($estudiantes as $u): ?>
                    <tr>
                        <td><strong><?= e($u['nombre'] . ' ' . $u['apellido']) ?></strong></td>
                        <td><small><?= e($u['correo']) ?></small></td>
                        <td><?= e($u['fecha_asignacion']) ?></td>
                        <td>
                            <?php foreach ($proyectos as $p): ?>
                                <a href="ver_proyecto.php?id=<?= (int) $p['id_tesis'] ?>"><?= e($p['titulo']) ?></a>
                                <span class="etiqueta <?= clase_estado($p['estado']) ?>"><?= e($p['estado']) ?></span>
                                <?php if ((int) $p['id_estudiante'] === (int) $u['usuario_id']): ?><small>(responsable)</small><?php endif; ?><br>
                            <?php endforeach; ?>
                            <?php if (!$proyectos): ?><small>Sin proyecto en este grupo</small><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$estudiantes): ?>
                    <tr><td colspan="4" class="vacio">Este grupo aún no tiene estudiantes. <a href="editar_grupo.php?id=<?= $id ?>">Agregar estudiantes</a></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="tarjeta">
            <h2>Docentes del curso (<?= count($docentes) ?>)</h2>
            <p style="margin-top:0"><small>Los docentes asignados al curso ven este grupo y sus estudiantes en su módulo.</small></p>
            <ul class="lista-simple">
                <?php foreach ($docentes as $d): ?>
                    <li><strong><?= e($d['nombre'] . ' ' . $d['apellido']) ?></strong><br><small><?= e($d['correo']) ?></small></li>
                <?php endforeach; ?>
                <?php if (!$docentes): ?><li class="vacio">Ningún docente asignado a este curso.</li><?php endif; ?>
            </ul>
        </div>

        <div class="tarjeta">
            <h2>Proyectos del grupo (<?= count($proyectos) ?>)</h2>
            <ul class="lista-simple">
                <?php foreach ($proyectos as $p): ?>
                    <li><a href="ver_proyecto.php?id=<?= (int) $p['id_tesis'] ?>"><strong><?= e($p['titulo']) ?></strong></a>
                        <span class="etiqueta <?= clase_estado($p['estado']) ?>"><?= e($p['estado']) ?></span><br>
                        <small>Docente: <?= e($p['prof_nombre'] . ' ' . $p['prof_apellido']) ?></small></li>
                <?php endforeach; ?>
                <?php if (!$proyectos): ?><li class="vacio">Sin proyectos vinculados.</li><?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<a href="grupos.php" class="btn btn-secundario">← Volver a grupos</a>

<?php require __DIR__ . '/../includes/footer.php'; ?>