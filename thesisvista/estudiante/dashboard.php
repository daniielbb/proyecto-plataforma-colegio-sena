<?php
/** THESISVISTA - Inicio del estudiante: todo enfocado en SU grupo y SU proyecto. */
require_once __DIR__ . '/includes/inicio.php';

$titulo  = 'Hola, ' . $estudiante['nombre'];
$seccion = 'inicio';
$auto_recargar = true;

if (!$grupo) {
    $subtitulo = 'Panel del estudiante';
    require __DIR__ . '/includes/header.php'; ?>
    <section class="d-bienvenida">
        <div><h2>Bienvenido(a), <?= e($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></h2>
            <p>Todavía no perteneces a ningún grupo. Cuando el administrador te asigne uno, aquí verás tu proyecto,
               tus fases y los comentarios de tu docente (esta página se actualiza sola).</p></div>
    </section>
    <?php require __DIR__ . '/includes/footer.php';
    exit;
}

$fases      = fases_estudiante($id_grupo, $id_estudiante);
$progreso   = resumen_progreso($id_grupo, $fases);
$proyectos  = proyectos_estudiante($id_grupo, $id_estudiante);
$docentes   = docentes_del_curso((int) $grupo['id_curso']);
$integrantes = integrantes_con_participacion($id_grupo, $proyectos, $fases);
$comentarios = comentarios_estudiante($id_grupo, $id_estudiante, null, 4);
$mis_entregas = array_slice(entregas_estudiante($id_grupo, $id_estudiante), 0, 4);
$incentivos = incentivos_estudiante($grupo, $id_estudiante);
$proximas   = fechas_proximas($fases);
$avisos     = array_slice(avisos_estudiante($grupo, $id_estudiante, $fases), 0, 6);
$proyecto   = $proyectos[0] ?? null;

// Docente encargado: el director del proyecto; si no hay proyecto, los docentes del curso.
$encargados = [];
foreach ($proyectos as $p) $encargados[(int) $p['id_profesor']] = $p['prof_nombre'] . ' ' . $p['prof_apellido'];
if (!$encargados) foreach ($docentes as $d) $encargados[(int) $d['usuario_id']] = $d['nombre'] . ' ' . $d['apellido'];

$subtitulo = 'Este es el resumen de tu grupo y tu proyecto.';
require __DIR__ . '/includes/header.php';
?>

<section class="d-bienvenida e-bienvenida">
    <dl class="e-identidad">
        <div><dt>Mi grupo</dt><dd><?= e($grupo['nombre_grupo']) ?> <small>· Curso <?= e($grupo['nombre_curso']) ?></small></dd></div>
        <div><dt>Proyecto</dt><dd><?= $proyecto ? e($proyecto['titulo']) : '<span class="e-suave">Sin proyecto registrado</span>' ?></dd></div>
        <div><dt>Docente</dt><dd><?= $encargados ? e(implode(', ', $encargados)) : '<span class="e-suave">Sin docente asignado</span>' ?></dd></div>
    </dl>
    <?php if ($progreso['siguiente']): ?>
        <a class="btn btn-secundario" href="fase.php?id=<?= (int) $progreso['siguiente']['id_fase'] ?>">
            <?= icono('subir', 16) ?> Ir a Fase <?= (int) $progreso['siguiente']['orden'] ?></a>
    <?php endif; ?>
</section>

<div class="d-resumen">
    <a class="d-cifra" href="fases.php"><?= icono('avance') ?><strong><?= $progreso['porcentaje'] ?>%</strong><span>Progreso del proyecto</span></a>
    <a class="d-cifra" href="fases.php"><?= icono('check') ?><strong><?= $progreso['por_estado']['Completada'] ?></strong><span>Fases completadas</span></a>
    <a class="d-cifra" href="fases.php"><?= icono('fases') ?><strong><?= $progreso['pendientes'] ?></strong><span>Fases pendientes</span></a>
    <a class="d-cifra" href="fases.php"><?= icono('reloj') ?><strong><?= $progreso['por_estado']['Pendiente de revisión'] ?></strong><span>En revisión</span></a>
    <a class="d-cifra" href="fases.php"><?= icono('candado') ?><strong><?= $progreso['por_estado']['Bloqueada'] ?></strong><span>Bloqueadas</span></a>
    <a class="d-cifra <?= $avisos_nuevos ? 'urgente' : '' ?>" href="avisos.php"><?= icono('campana') ?><strong><?= $avisos_nuevos ?></strong><span>Avisos nuevos</span></a>
</div>

<div class="d-columnas">
    <div>
        <section class="tarjeta">
            <div class="tarjeta-cabecera"><h2>Progreso</h2><a href="fases.php" class="btn btn-secundario btn-chico">Ver todas las fases</a></div>
            <?= barra($progreso['porcentaje'], true, 'grande') ?>
            <p class="d-tenue" style="margin:8px 0 16px"><small>
                <?= $progreso['terminadas'] ?> de <?= $progreso['total'] ?> fases completadas ·
                calculado con los datos del docente<?= $progreso['ponderado'] ? ' (ponderado por el peso de cada fase)' : '' ?>.</small></p>
            <?= linea_fases($fases) ?>
        </section>

        <section class="tarjeta">
            <div class="tarjeta-cabecera"><h2>Comentarios del docente</h2><a href="comentarios.php" class="btn btn-secundario btn-chico">Ver todos</a></div>
            <?= lista_comentarios($comentarios) ?>
        </section>

        <section class="tarjeta">
            <div class="tarjeta-cabecera"><h2>Mis últimas entregas</h2><a href="entregas.php" class="btn btn-secundario btn-chico">Ver entregas</a></div>
            <?= $mis_entregas ? lista_entregas($mis_entregas, $id_estudiante, true) : '<p class="vacio">Aún no has subido archivos.</p>' ?>
        </section>
    </div>

    <div>
        <section class="tarjeta">
            <div class="tarjeta-cabecera"><h2>Integrantes</h2><a href="grupo.php" class="btn btn-secundario btn-chico">Mi grupo</a></div>
            <ul class="d-personas">
                <?php foreach ($integrantes as $u): ?>
                    <li><span class="avatar chico"><?= e(mb_strtoupper(mb_substr($u['nombre'], 0, 1))) ?></span>
                        <div><strong><?= e($u['nombre'] . ' ' . $u['apellido']) ?></strong><?= (int) $u['usuario_id'] === $id_estudiante ? ' <span class="etiqueta etiqueta-cafe">Tú</span>' : '' ?>
                            <small><?= e($u['rol_proyecto']) ?></small></div></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="tarjeta">
            <h2 class="d-seccion-titulo"><?= icono('calendario') ?> Próximas fechas</h2>
            <?php if ($proximas): ?>
                <ul class="lista-simple">
                    <?php foreach ($proximas as $f): ?>
                        <li><a href="fase.php?id=<?= (int) $f['id_fase'] ?>"><strong>Fase <?= (int) $f['orden'] ?> · <?= e($f['nombre_fase']) ?></strong></a><br>
                            <small><?= limite($f['fecha_limite']) ?></small></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="d-tenue" style="margin:0">No hay fechas límite en los próximos <?= DIAS_AVISO_LIMITE ?> días.</p>
            <?php endif; ?>
        </section>

        <section class="tarjeta">
            <div class="tarjeta-cabecera"><h2>Incentivos</h2><a href="incentivos.php" class="btn btn-secundario btn-chico">Ver</a></div>
            <?php if ($incentivos): ?>
                <ul class="d-otorgados">
                    <?php foreach (array_slice($incentivos, 0, 4) as $i): ?>
                        <li><span class="d-medalla"><?= e($i['icono'] ?: '🏆') ?></span>
                            <div><strong><?= e($i['nombre']) ?></strong><br>
                                <span class="etiqueta <?= ['Obtenido' => 'etiqueta-verde', 'Pendiente' => 'etiqueta-ambar', 'No obtenido' => 'etiqueta-gris'][$i['resultado']] ?>"><?= e($i['resultado']) ?></span>
                                <small class="d-tenue"><?= $i['nombre_fase'] ? 'Fase ' . (int) $i['orden_fase'] : 'Proyecto' ?></small></div></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="d-tenue" style="margin:0">Tu docente aún no ha definido incentivos.</p>
            <?php endif; ?>
        </section>

        <section class="tarjeta">
            <div class="tarjeta-cabecera"><h2>Avisos</h2><a href="avisos.php" class="btn btn-secundario btn-chico">Ver todos</a></div>
            <?= lista_avisos($avisos) ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
