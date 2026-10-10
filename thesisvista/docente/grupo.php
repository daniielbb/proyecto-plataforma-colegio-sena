<?php

require_once __DIR__ . '/includes/inicio.php';

$id    = get_int('id');
$grupo = grupo_para_docente($id_docente, $id);
if (!$grupo) {
    mensaje('error', 'El grupo no existe o no pertenece a sus cursos.');
    redirigir('cursos.php');
}
$integrantes = integrantes_grupo($id);
$proyectos   = proyectos_grupo($id);
$fases       = fases_de_grupo($id);
$progreso    = progreso_grupo($id, $fases);
$actual      = $progreso['fase_actual'];
$entregas    = entregas_docente($id_docente, ['id_grupo' => $id]);
$pendientes  = trabajos_pendientes_docente($id_docente, ['id_grupo' => $id]);
$notas       = calificaciones(['id_grupo' => $id]);
$comentarios = comentarios(['id_grupo' => $id], 8);
$otorgados   = incentivos_otorgados(['id_grupo' => $id]);
$actividad   = actividad_reciente($id_docente, 8, $id);
$nota_acum   = nota_acumulada_grupo($fases);
$curso_txt   = $grupo['nombre_curso'] . (is_numeric($grupo['nombre_curso']) ? '°' : '');

$titulo  = $grupo['nombre_grupo'];
$subtitulo = 'Curso ' . $curso_txt . ' · ' . count($integrantes) . ' integrante(s)';
$seccion = 'cursos';
$migas = [['Mis cursos', 'cursos.php'], [$curso_txt, 'curso.php?id=' . (int) $grupo['id_curso']], [$grupo['nombre_grupo'], null]];
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<section class="d-resumen">
    <div class="d-cifra"><?= icono('avance') ?><strong><?= $progreso['porcentaje'] ?>%</strong><span>Progreso general</span></div>
    <div class="d-cifra"><?= icono('fases') ?><strong><?= $progreso['terminadas'] ?>/<?= $progreso['total'] ?></strong><span>Fases terminadas</span></div>
    <div class="d-cifra"><?= icono('trabajos') ?><strong><?= count($entregas) ?></strong><span>Trabajos entregados</span></div>
    <div class="d-cifra <?= $pendientes ? 'urgente' : '' ?>"><?= icono('reloj') ?><strong><?= count($pendientes) ?></strong><span>Trabajos pendientes</span></div>
    <div class="d-cifra"><?= icono('nota') ?><strong><?= formato_nota($nota_acum) ?></strong><span>Nota acumulada</span></div>
    <div class="d-cifra"><?= icono('incentivo') ?><strong><?= count($otorgados) ?></strong><span>Incentivos obtenidos</span></div>
</section>

<div class="d-columnas">
    <div>
        <section class="tarjeta">
            <div class="tarjeta-cabecera">
                <h2>Fases del proyecto</h2>
                <div style="min-width:260px"><?= barra($progreso['porcentaje'], true, 'grande') ?></div>
            </div>
            <?php if ($actual): ?>
                <div class="alerta alerta-aviso" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center">
                    <span><strong>Fase actual:</strong> Fase <?= (int) $actual['orden'] ?> · <?= e($actual['nombre_fase']) ?> · <?= etiqueta($actual['estado']) ?>
                        · Fecha límite: <?= limite($actual['fecha_limite']) ?></span>
                    <a class="btn btn-primario btn-chico" href="grupo_fase.php?grupo=<?= $id ?>&fase=<?= (int) $actual['id_fase'] ?>">Revisar fase actual</a>
                </div>
            <?php endif; ?>
            <?= linea_fases($fases, $id) ?>
        </section>

        <section class="tarjeta">
            <h2>Trabajos entregados</h2>
            <?php require __DIR__ . '/includes/tabla_entregas.php'; ?>
            <?php if ($pendientes): ?>
                <h3 style="margin-top:20px">Trabajos pendientes</h3>
                <ul class="lista-simple">
                    <?php foreach ($pendientes as $t): ?>
                        <li><a href="grupo_fase.php?grupo=<?= $id ?>&fase=<?= (int) $t['id_fase'] ?>"><strong>Fase <?= (int) $t['orden'] ?> · <?= e($t['nombre_fase']) ?></strong></a>
                            <?= etiqueta($t['estado']) ?> · <small><?= limite($t['fecha_limite']) ?></small></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="tarjeta">
            <h2>Calificaciones</h2>
            <?= tabla_calificaciones($notas, false) ?>
        </section>
    </div>

    <div>
        <section class="tarjeta">
            <h2>Integrantes</h2>
            <ul class="d-personas">
                <?php foreach ($integrantes as $u): ?>
                    <li><span class="avatar chico"><?= e(mb_strtoupper(mb_substr($u['nombre'], 0, 1))) ?></span>
                        <span><strong><?= e($u['nombre'] . ' ' . $u['apellido']) ?></strong><small><?= e($u['correo']) ?></small></span></li>
                <?php endforeach; ?>
                <?php if (!$integrantes): ?><li class="vacio">Sin estudiantes.</li><?php endif; ?>
            </ul>
        </section>

        <section class="tarjeta">
            <h2>Proyecto</h2>
            <ul class="lista-simple">
                <?php foreach ($proyectos as $p): ?>
                    <li><a href="proyecto.php?id=<?= (int) $p['id_tesis'] ?>"><strong><?= e($p['titulo']) ?></strong></a> <?= etiqueta($p['estado']) ?><br>
                        <small><?= e($p['est_nombre'] . ' ' . $p['est_apellido']) ?> · Director(a): <?= e($p['prof_nombre'] . ' ' . $p['prof_apellido']) ?></small></li>
                <?php endforeach; ?>
                <?php if (!$proyectos): ?><li class="vacio">El grupo no tiene proyectos registrados.</li><?php endif; ?>
            </ul>
        </section>

        <section class="tarjeta">
            <div class="tarjeta-cabecera"><h2>Incentivos</h2><a class="btn btn-secundario btn-chico" href="incentivos.php?curso=<?= (int) $grupo['id_curso'] ?>">Otorgar</a></div>
            <?= lista_otorgados($otorgados, false) ?>
        </section>

        <section class="tarjeta">
            <div class="tarjeta-cabecera"><h2>Últimos comentarios</h2><a class="btn btn-secundario btn-chico" href="comentarios.php?grupo=<?= $id ?>">Ver todos</a></div>
            <?= lista_comentarios($comentarios, $id_docente, true) ?>
        </section>

        <section class="tarjeta">
            <h2>Actividad del grupo</h2>
            <?= lista_actividad($actividad) ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
