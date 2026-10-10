<?php

require_once __DIR__ . '/includes/inicio.php';
exigir_grupo($grupo);

$fases       = fases_estudiante($id_grupo, $id_estudiante);
$proyectos   = proyectos_estudiante($id_grupo, $id_estudiante);
$docentes    = docentes_del_curso((int) $grupo['id_curso']);
$integrantes = integrantes_con_participacion($id_grupo, $proyectos, $fases);

$titulo    = 'Mi grupo';
$subtitulo = $grupo['nombre_grupo'] . ' · Curso ' . $grupo['nombre_curso'];
$seccion   = 'grupo';
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<section class="tarjeta" style="padding:18px 22px">
    <dl class="d-ficha">
        <div><dt>Grupo</dt><dd><?= e($grupo['nombre_grupo']) ?></dd></div>
        <div><dt>Curso</dt><dd><?= e($grupo['nombre_curso']) ?><?= $grupo['ficha'] ? ' · Ficha ' . e($grupo['ficha']) : '' ?></dd></div>
        <div><dt>Integrantes</dt><dd><?= count($integrantes) ?></dd></div>
        <div><dt>En el grupo desde</dt><dd><?= e(fecha_corta($grupo['fecha_asignacion'])) ?></dd></div>
    </dl>
</section>

<div class="d-columnas">
    <div>
        <section class="tarjeta">
            <h2 class="d-seccion-titulo"><?= icono('proyecto') ?> Proyecto asignado</h2>
            <?php if (!$proyectos): ?>
                <p class="vacio">Tu grupo todavía no tiene un proyecto registrado. El administrador lo registra en la plataforma.</p>
            <?php endif; ?>
            <?php foreach ($proyectos as $p): ?>
                <article class="e-proyecto">
                    <h3><?= e($p['titulo']) ?> <?= etiqueta($p['estado']) ?></h3>
                    <div class="d-bloque"><h3>Tema del proyecto</h3><p class="d-texto"><?= e($p['resumen']) ?></p></div>
                    <dl class="d-ficha">
                        <div><dt>Docente encargado</dt><dd><?= e($p['prof_nombre'] . ' ' . $p['prof_apellido']) ?></dd></div>
                        <div><dt>Estudiante responsable</dt><dd><?= e($p['est_nombre'] . ' ' . $p['est_apellido']) ?><?= (int) $p['id_estudiante'] === $id_estudiante ? ' (tú)' : '' ?></dd></div>
                        <div><dt>Registrado</dt><dd><?= e(fecha_corta($p['fecha_registro'])) ?></dd></div>
                    </dl>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="tarjeta">
            <h2 class="d-seccion-titulo"><?= icono('grupo') ?> Integrantes</h2>
            <div class="tabla-contenedor">
                <table>
                    <thead><tr><th>Nombre</th><th>Apellido</th><th>Rol en el proyecto</th><th>Participación</th></tr></thead>
                    <tbody>
                    <?php foreach ($integrantes as $u): ?>
                        <tr>
                            <td><strong><?= e($u['nombre']) ?></strong><?= (int) $u['usuario_id'] === $id_estudiante ? ' <span class="etiqueta etiqueta-cafe">Tú</span>' : '' ?></td>
                            <td><?= e($u['apellido']) ?></td>
                            <td><small><?= e($u['rol_proyecto']) ?></small></td>
                            <td><span class="etiqueta <?= $u['participacion'][1] ?>"><?= e($u['participacion'][0]) ?></span><br>
                                <small>Entregó en <?= (int) $u['fases_entregadas'] ?> de <?= (int) $u['habilitadas'] ?> fase(s) habilitada(s)
                                    <?= $u['ultima_entrega'] ? '· última ' . e(hace($u['ultima_entrega'])) : '' ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="ayuda d-tenue"><small>La información personal de cada integrante la administra el administrador; aquí solo se consulta.</small></p>
        </section>
    </div>

    <div>
        <section class="tarjeta">
            <h2 class="d-seccion-titulo"><?= icono('docente') ?> Docentes del curso</h2>
            <?php if ($docentes): ?>
                <ul class="d-personas">
                    <?php foreach ($docentes as $d): ?>
                        <li><span class="avatar chico"><?= e(mb_strtoupper(mb_substr($d['nombre'], 0, 1))) ?></span>
                            <div><strong><?= e($d['nombre'] . ' ' . $d['apellido']) ?></strong>
                                <small><?= in_array((int) $d['usuario_id'], array_map('intval', array_column($proyectos, 'id_profesor')), true) ? 'Encargado del proyecto' : 'Docente del curso' ?></small></div></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="d-tenue" style="margin:0">El curso aún no tiene docentes asignados.</p>
            <?php endif; ?>
        </section>

        <section class="tarjeta">
            <h2>Progreso del grupo</h2>
            <?php $prog = resumen_progreso($id_grupo, $fases); ?>
            <?= barra($prog['porcentaje'], true, 'grande') ?>
            <p class="d-tenue"><small><?= $prog['terminadas'] ?> de <?= $prog['total'] ?> fases completadas.</small></p>
            <a class="btn btn-secundario btn-bloque" href="fases.php">Ver fases</a>
        </section>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
