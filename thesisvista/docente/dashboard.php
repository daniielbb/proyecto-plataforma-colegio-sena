<?php

require_once __DIR__ . '/includes/inicio.php';

$pdo     = conectar();
$cursos  = cursos_docente($id_docente);
$ids_cur = ids_cursos_docente($id_docente);
$grupos  = grupos_docente($id_docente);
$proyectos = proyectos_docente($id_docente);

$proyectos_activos = array_filter($proyectos, fn($p) => $p['estado'] !== 'Rechazada');
$dirigidos         = array_filter($proyectos_activos, fn($p) => (int) $p['es_director'] === 1);
$grupos_activos    = array_filter($grupos, fn($g) => (int) $g['total_estudiantes'] > 0);

$progresos = [];
foreach ($grupos as $g) {
    $fases_g = fases_de_grupo((int) $g['id_grupo']);
    $progresos[(int) $g['id_grupo']] = progreso_grupo((int) $g['id_grupo'], $fases_g) + ['grupo' => $g];
}
$con_fases = array_filter($progresos, fn($p) => $p['total'] > 0);
$progreso_general = $con_fases ? (int) round(array_sum(array_column($con_fases, 'porcentaje')) / count($con_fases)) : 0;

$fases_revision = 0;
$incentivos_otorgados = 0;
if ($ids_cur) {
    $in = marcas($ids_cur);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM fase_grupo fg JOIN grupos g ON g.id_grupo = fg.id_grupo
                           JOIN fases f ON f.id_fase = fg.id_fase
                           WHERE g.id_curso IN ($in) AND f.estado <> 'Borrador' AND fg.estado IN ('Entregada', 'En revisión')");
    $stmt->execute($ids_cur);
    $fases_revision = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM incentivo_otorgado o JOIN grupos g ON g.id_grupo = o.id_grupo WHERE g.id_curso IN ($in)");
    $stmt->execute($ids_cur);
    $incentivos_otorgados = (int) $stmt->fetchColumn();
}
$por_revisar  = total_por_revisar($id_docente);
$para_revisar = entregas_docente($id_docente, ['por_revisar' => 1], 6);
$pendientes   = array_slice(array_filter(trabajos_pendientes_docente($id_docente), fn($t) => $t['fecha_limite']), 0, 6);
$actividad    = actividad_reciente($id_docente, 12);

$hora = (int) date('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');

$titulo  = 'Inicio';
$seccion = 'inicio';
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<section class="d-bienvenida">
    <div>
        <h2><?= e($saludo) ?>, <?= e($docente['nombre']) ?></h2>
        <p>Bienvenido(a), <?= e($docente['nombre'] . ' ' . $docente['apellido']) ?>.
            <?php if ($por_revisar): ?>Tiene <strong><?= $por_revisar ?></strong> trabajo(s) esperando su revisión.
            <?php else: ?>No tiene trabajos pendientes por revisar.<?php endif; ?></p>
    </div>
    <div class="d-barra-acciones">
        <?php if ($por_revisar): ?><a href="trabajos.php" class="btn btn-secundario">Revisar trabajos</a><?php endif; ?>
        <a href="fase_form.php" class="btn btn-secundario"><?= icono('mas', 16) ?> Nueva fase</a>
    </div>
</section>

<?php if (!$cursos): ?>
    <div class="alerta alerta-aviso">Todavía no tiene cursos asignados. El administrador debe asignarle un curso
        (tabla <em>docente_curso</em>) para que vea sus grupos, fases y estudiantes.</div>
<?php endif; ?>

<section class="d-resumen">
    <a class="d-cifra" href="proyectos.php"><?= icono('proyecto') ?><strong><?= count($proyectos_activos) ?></strong>
        <span>Proyectos activos<?= $dirigidos ? ' · ' . count($dirigidos) . ' dirigidos' : '' ?></span></a>
    <a class="d-cifra" href="cursos.php"><?= icono('cursos') ?><strong><?= count($cursos) ?></strong><span>Cursos asignados</span></a>
    <a class="d-cifra" href="cursos.php"><?= icono('grupo') ?><strong><?= count($grupos_activos) ?></strong><span>Grupos activos</span></a>
    <a class="d-cifra" href="avances.php"><?= icono('fases') ?><strong><?= $fases_revision ?></strong><span>Fases en revisión</span></a>
    <a class="d-cifra <?= $por_revisar ? 'urgente' : '' ?>" href="trabajos.php"><?= icono('trabajos') ?><strong><?= $por_revisar ?></strong><span>Trabajos por revisar</span></a>
    <a class="d-cifra" href="incentivos.php"><?= icono('incentivo') ?><strong><?= $incentivos_otorgados ?></strong><span>Incentivos asignados</span></a>
    <a class="d-cifra" href="avances.php"><?= icono('avance') ?><strong><?= $progreso_general ?>%</strong><span>Progreso general</span></a>
</section>

<div class="d-columnas">
    <div>
        <section class="tarjeta">
            <div class="tarjeta-cabecera">
                <h2>Progreso de mis grupos</h2>
                <a href="avances.php" class="btn btn-secundario btn-chico">Ver avances</a>
            </div>
            <?php if (!$progresos): ?>
                <p class="vacio">No hay grupos en sus cursos.</p>
            <?php else: ?>
                <div class="tabla-contenedor">
                    <table>
                        <thead><tr><th>Grupo</th><th>Fase actual</th><th style="width:34%">Progreso</th></tr></thead>
                        <tbody>
                        <?php foreach ($progresos as $id_g => $p): ?>
                            <tr>
                                <td><a href="grupo.php?id=<?= $id_g ?>"><strong><?= e($p['grupo']['nombre_grupo']) ?></strong></a><br>
                                    <small>Curso <?= e($p['grupo']['nombre_curso']) ?> · <?= (int) $p['grupo']['total_estudiantes'] ?> estudiante(s)</small></td>
                                <td>
                                    <?php if (!$p['total']): ?><small class="d-tenue">Sin fases asignadas</small>
                                    <?php elseif ($p['fase_actual']): ?>
                                        Fase <?= (int) $p['fase_actual']['orden'] ?> · <?= e($p['fase_actual']['nombre_fase']) ?><br>
                                        <?= etiqueta($p['fase_actual']['estado']) ?>
                                    <?php else: ?><span class="etiqueta etiqueta-verde-fuerte">Todas las fases terminadas</span><?php endif; ?>
                                </td>
                                <td><?= barra($p['porcentaje']) ?><small><?= $p['terminadas'] ?> de <?= $p['total'] ?> fases terminadas</small></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="tarjeta">
            <div class="tarjeta-cabecera">
                <h2>Esperando su revisión</h2>
                <a href="trabajos.php" class="btn btn-secundario btn-chico">Todos los trabajos</a>
            </div>
            <?php if (!$para_revisar): ?>
                <p class="vacio">Al día: no hay trabajos nuevos por revisar.</p>
            <?php else: ?>
                <ul class="d-entregas">
                    <?php foreach ($para_revisar as $en): ?>
                        <li class="d-entrega">
                            <span class="d-entrega-ico"><?= e($en['extension']) ?></span>
                            <div class="d-entrega-cuerpo">
                                <strong><?= e($en['nombre_original']) ?></strong> <?= etiqueta($en['estado']) ?><br>
                                <small><?= e($en['nombre'] . ' ' . $en['apellido']) ?> · <?= e($en['nombre_grupo']) ?> · Fase <?= (int) $en['orden'] ?> · v<?= (int) $en['version'] ?> · <?= e(hace($en['fecha_subida'])) ?></small>
                            </div>
                            <a class="btn btn-primario btn-chico" href="grupo_fase.php?grupo=<?= (int) $en['id_grupo'] ?>&fase=<?= (int) $en['id_fase'] ?>">Revisar</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <div>
        <section class="tarjeta">
            <h2 class="d-seccion-titulo"><?= icono('reloj') ?> Actividad reciente</h2>
            <?= lista_actividad($actividad) ?>
        </section>

        <section class="tarjeta">
            <h2 class="d-seccion-titulo"><?= icono('calendario') ?> Próximas fechas límite</h2>
            <?php if (!$pendientes): ?>
                <p class="vacio">No hay fechas límite pendientes.</p>
            <?php else: ?>
                <ul class="lista-simple">
                    <?php foreach ($pendientes as $t): ?>
                        <li><a href="grupo_fase.php?grupo=<?= (int) $t['id_grupo'] ?>&fase=<?= (int) $t['id_fase'] ?>"><strong><?= e($t['nombre_grupo']) ?></strong></a>
                            · Fase <?= (int) $t['orden'] ?> <?= etiqueta($t['estado']) ?><br>
                            <small><?= e($t['nombre_fase']) ?> · <?= limite($t['fecha_limite']) ?></small></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
