<?php

require_once __DIR__ . '/includes/inicio.php';

$id = get_int('id');
$p  = tesis_para_docente($id_docente, $id);
if (!$p) {
    mensaje('error', 'El proyecto no existe o no pertenece a sus cursos.');
    redirigir('proyectos.php');
}

$id_grupo = $p['id_grupo'] ? (int) $p['id_grupo'] : null;
$grupo    = $id_grupo ? grupo_para_docente($id_docente, $id_grupo) : null;   // null si el grupo no es de sus cursos
$fases    = $grupo ? fases_de_grupo($id_grupo) : [];
$progreso = progreso_grupo($id_grupo ?? 0, $fases);

$tabs = [
    'info'           => ['Información general', 'proyecto'],
    'fases'          => ['Fases', 'fases'],
    'grupo'          => ['Curso y grupo', 'grupo'],
    'avances'        => ['Avances', 'avance'],
    'trabajos'       => ['Trabajos entregados', 'trabajos'],
    'calificaciones' => ['Calificaciones', 'nota'],
    'comentarios'    => ['Comentarios', 'comentario'],
    'incentivos'     => ['Incentivos', 'incentivo'],
];
$tab = isset($tabs[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'info';


$url_proyecto = 'proyecto.php?id=' . $id;

$clase_tesis = ['Borrador' => 'etiqueta-gris', 'En revisión' => 'etiqueta-violeta', 'Aprobada' => 'etiqueta-verde', 'Rechazada' => 'etiqueta-roja'];

$titulo  = $p['titulo'];
$subtitulo = 'Proyecto de ' . $p['est_nombre'] . ' ' . $p['est_apellido'] . ($grupo ? ' · Curso ' . $grupo['nombre_curso'] . ' · ' . $grupo['nombre_grupo'] : '');
$seccion = 'proyectos';
$migas   = [['Mis proyectos', 'proyectos.php'], [$p['titulo'], null]];
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<nav class="d-pestanas">
    <?php foreach ($tabs as $k => [$texto, $ico]): ?>
        <a href="<?= $url_proyecto ?>&tab=<?= $k ?>" class="<?= $tab === $k ? 'activa' : '' ?>"><?= icono($ico, 16) ?> <?= e($texto) ?></a>
    <?php endforeach; ?>
</nav>

<?php if (!$grupo && $tab !== 'info'): ?>
    <div class="alerta alerta-aviso">
        <?= $id_grupo ? 'El grupo de este proyecto pertenece a un curso que usted no tiene asignado.' : 'Este proyecto aún no está vinculado a un grupo.' ?>
        El administrador puede vincularlo desde la gestión de proyectos; mientras tanto no hay fases ni avances que mostrar.
    </div>
<?php endif; ?>

<?php if ($tab === 'info'): ?>
    <div class="d-columnas">
        <section class="tarjeta">
            <h2>Información general</h2>
            <dl class="d-ficha">
                <div><dt>Estado del proyecto</dt><dd><span class="etiqueta <?= $clase_tesis[$p['estado']] ?? '' ?>"><?= e($p['estado']) ?></span></dd></div>
                <div><dt>Fecha de registro</dt><dd><?= e(fecha_corta($p['fecha_registro'])) ?></dd></div>
                <div><dt>Estudiante responsable</dt><dd><?= e($p['est_nombre'] . ' ' . $p['est_apellido']) ?><br><small class="d-tenue"><?= e($p['est_correo']) ?></small></dd></div>
                <div><dt>Docente director(a)</dt><dd><?= e($p['prof_nombre'] . ' ' . $p['prof_apellido']) ?><?= (int) $p['id_profesor'] === $id_docente ? ' (usted)' : '' ?></dd></div>
                <div><dt>Curso / grupo</dt><dd><?= $p['nombre_grupo'] ? e('Curso ' . $p['nombre_curso'] . ' · ' . $p['nombre_grupo']) : 'Sin grupo' ?></dd></div>
                <div><dt>Fase actual</dt><dd><?= $progreso['fase_actual'] ? 'Fase ' . (int) $progreso['fase_actual']['orden'] . ' · ' . e($progreso['fase_actual']['nombre_fase']) : ($progreso['total'] ? 'Todas terminadas' : '—') ?></dd></div>
            </dl>
            <div class="d-bloque" style="margin-top:18px">
                <h3>Resumen</h3>
                <p class="d-texto"><?= e($p['resumen']) ?></p>
            </div>
        </section>
        <section class="tarjeta">
            <h2>Progreso general</h2>
            <?= barra($progreso['porcentaje'], true, 'grande') ?>
            <p class="d-tenue"><?= $progreso['terminadas'] ?> de <?= $progreso['total'] ?> fases terminadas<?= $progreso['ponderado'] ? ' · calculado con el peso de cada fase' : '' ?></p>
            <?php if ($grupo): $nota = nota_acumulada_grupo($fases); ?>
                <p>Nota acumulada del grupo: <span class="d-nota <?= $nota !== null && $nota >= NOTA_APROBATORIA ? 'aprobada' : 'baja' ?>"><?= formato_nota($nota) ?></span></p>
                <a class="btn btn-primario" href="grupo.php?id=<?= $id_grupo ?>">Ver el grupo completo</a>
            <?php endif; ?>
        </section>
    </div>

<?php elseif ($grupo && $tab === 'fases'): ?>
    <section class="tarjeta">
        <div class="tarjeta-cabecera">
            <h2>Fases del proyecto</h2>
            <div class="d-barra-acciones">
                <a class="btn btn-secundario btn-chico" href="fases.php?curso=<?= (int) $grupo['id_curso'] ?>"><?= icono('carpeta', 15) ?> Organizar fases del curso</a>
                <a class="btn btn-primario btn-chico" href="fase_form.php?curso=<?= (int) $grupo['id_curso'] ?>&grupo=<?= $id_grupo ?>&tesis=<?= $id ?>"><?= icono('mas', 15) ?> Nueva fase</a>
            </div>
        </div>
        <p class="d-tenue" style="margin-top:0">Las fases pertenecen al curso <?= e($grupo['nombre_curso']) ?> y se asignan a uno o varios grupos. Aquí ve las del grupo <?= e($grupo['nombre_grupo']) ?>.</p>
        <div class="d-carpetas">
            <?php foreach ($fases as $f): ?>
                <article class="d-carpeta">
                    <span class="d-carpeta-num">Fase <?= (int) $f['orden'] ?></span>
                    <h3><a class="d-carpeta-enlace" href="fase.php?id=<?= (int) $f['id_fase'] ?>" style="color:inherit;text-decoration:none"><?= e($f['nombre_fase']) ?></a></h3>
                    <p><?= e(mb_strimwidth($f['descripcion'], 0, 100, '…')) ?></p>
                    <div class="d-carpeta-datos">
                        <span><?= icono('calendario', 14) ?><?= limite($f['fecha_limite'], fase_terminada($f['estado'])) ?></span>
                        <?php if ($f['peso'] !== null): ?><span>Peso <?= e(rtrim(rtrim($f['peso'], '0'), '.')) ?>%</span><?php endif; ?>
                    </div>
                    <?= barra($f['porcentaje']) ?>
                    <div class="d-carpeta-pie"><?= etiqueta($f['estado']) ?><a class="btn btn-secundario btn-chico" style="position:relative;z-index:2" href="grupo_fase.php?grupo=<?= $id_grupo ?>&fase=<?= (int) $f['id_fase'] ?>">Revisar</a></div>
                </article>
            <?php endforeach; ?>
            <a class="d-carpeta nueva" href="fase_form.php?curso=<?= (int) $grupo['id_curso'] ?>&grupo=<?= $id_grupo ?>&tesis=<?= $id ?>"><?= icono('mas', 28) ?><strong>Crear fase</strong><small>Se asignará a <?= e($grupo['nombre_grupo']) ?></small></a>
        </div>
    </section>

<?php elseif ($grupo && $tab === 'grupo'): $integrantes = integrantes_grupo($id_grupo); ?>
    <section class="tarjeta">
        <h2>Curso y grupo</h2>
        <ol class="d-linea">
            <li class="hecha"><a href="curso.php?id=<?= (int) $grupo['id_curso'] ?>"><span class="d-linea-punto"><?= icono('cursos') ?></span>
                <span class="d-linea-texto"><small>CURSO</small><strong><?= e($grupo['nombre_curso']) ?></strong><span>Ver todos los grupos del curso</span></span><span></span></a></li>
            <li class="actual"><a href="grupo.php?id=<?= $id_grupo ?>"><span class="d-linea-punto"><?= icono('grupo') ?></span>
                <span class="d-linea-texto"><small>GRUPO</small><strong><?= e($grupo['nombre_grupo']) ?></strong><span><?= count($integrantes) ?> integrante(s) · abrir vista completa del grupo</span></span>
                <span class="d-linea-barra"><?= barra($progreso['porcentaje']) ?></span></a></li>
        </ol>
        <h3 style="margin-top:18px">Integrantes</h3>
        <ul class="d-personas">
            <?php foreach ($integrantes as $u): ?>
                <li><span class="avatar chico"><?= e(mb_strtoupper(mb_substr($u['nombre'], 0, 1))) ?></span>
                    <span><strong><?= e($u['nombre'] . ' ' . $u['apellido']) ?></strong><?= (int) $u['usuario_id'] === (int) $p['id_estudiante'] ? ' <span class="etiqueta etiqueta-cafe">Responsable del proyecto</span>' : '' ?><small><?= e($u['correo']) ?></small></span></li>
            <?php endforeach; ?>
            <?php if (!$integrantes): ?><li class="vacio">El grupo no tiene estudiantes.</li><?php endif; ?>
        </ul>
    </section>

<?php elseif ($grupo && $tab === 'avances'): ?>
    <section class="tarjeta">
        <div class="tarjeta-cabecera"><h2>Avance por fase</h2><div style="min-width:260px"><?= barra($progreso['porcentaje'], true, 'grande') ?></div></div>
        <?= linea_fases($fases, $id_grupo) ?>
    </section>

<?php elseif ($grupo && $tab === 'trabajos'):
    $entregas = entregas_docente($id_docente, ['id_grupo' => $id_grupo]);
    $pend = trabajos_pendientes_docente($id_docente, ['id_grupo' => $id_grupo]); ?>
    <div class="d-columnas">
        <section class="tarjeta">
            <h2>Trabajos entregados (última versión)</h2>
            <?php require __DIR__ . '/includes/tabla_entregas.php'; ?>
        </section>
        <section class="tarjeta">
            <h2>Trabajos pendientes</h2>
            <ul class="lista-simple">
                <?php foreach ($pend as $t): ?>
                    <li><a href="grupo_fase.php?grupo=<?= $id_grupo ?>&fase=<?= (int) $t['id_fase'] ?>"><strong>Fase <?= (int) $t['orden'] ?> · <?= e($t['nombre_fase']) ?></strong></a> <?= etiqueta($t['estado']) ?><br>
                        <small><?= limite($t['fecha_limite']) ?></small></li>
                <?php endforeach; ?>
                <?php if (!$pend): ?><li class="vacio">No hay trabajos pendientes.</li><?php endif; ?>
            </ul>
        </section>
    </div>

<?php elseif ($grupo && $tab === 'calificaciones'): ?>
    <section class="tarjeta">
        <h2>Calificaciones del grupo</h2>
        <?= tabla_calificaciones(calificaciones(['id_grupo' => $id_grupo]), false) ?>
    </section>

<?php elseif ($grupo && $tab === 'comentarios'): ?>
    <section class="tarjeta">
        <h2>Retroalimentación del docente</h2>
        <?= lista_comentarios(comentarios(['id_grupo' => $id_grupo]), $id_docente, true, $url_proyecto . '&tab=comentarios') ?>
    </section>

<?php elseif ($grupo && $tab === 'incentivos'):
    $disponibles = array_filter(incentivos(['id_curso' => (int) $grupo['id_curso']]), fn($i) => !$i['id_tesis'] || (int) $i['id_tesis'] === $id); ?>
    <div class="d-columnas">
        <section class="tarjeta">
            <div class="tarjeta-cabecera"><h2>Incentivos disponibles</h2>
                <a class="btn btn-primario btn-chico" href="incentivo_form.php?curso=<?= (int) $grupo['id_curso'] ?>&tesis=<?= $id ?>"><?= icono('mas', 15) ?> Crear incentivo</a></div>
            <div class="d-incentivos">
                <?php foreach ($disponibles as $i): ?>
                    <a class="d-incentivo" href="incentivo.php?id=<?= (int) $i['id_incentivo_grupal'] ?>&grupo=<?= $id_grupo ?>">
                        <div style="display:flex;gap:12px;align-items:center"><span class="d-medalla"><?= e($i['icono'] ?: '🏆') ?></span>
                            <div><h3><?= e($i['nombre']) ?></h3><?= etiqueta($i['estado']) ?></div></div>
                        <p><strong>Criterio:</strong> <?= e(mb_strimwidth($i['criterio'], 0, 120, '…')) ?></p>
                    </a>
                <?php endforeach; ?>
                <?php if (!$disponibles): ?><p class="vacio">No hay incentivos para este curso.</p><?php endif; ?>
            </div>
        </section>
        <section class="tarjeta">
            <h2>Obtenidos por el grupo</h2>
            <?= lista_otorgados(incentivos_otorgados(['id_grupo' => $id_grupo]), false) ?>
        </section>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>