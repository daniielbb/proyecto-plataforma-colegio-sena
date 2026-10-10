<?php

$fases_menu = $grupo ? ($fases ?? fases_estudiante($id_grupo, $id_estudiante)) : [];
$avisos_nuevos = $grupo ? total_avisos_nuevos(avisos_estudiante($grupo, $id_estudiante, $fases_menu)) : 0;

$menu = [
    'Mi proyecto' => [
        'inicio'   => ['dashboard.php', 'Inicio', 'inicio'],
        'grupo'    => ['grupo.php', 'Mi grupo', 'grupo'],
        'fases'    => ['fases.php', 'Progreso del proyecto', 'fases'],
        'entregas' => ['entregas.php', 'Mis entregas', 'trabajos'],
    ],
    'Seguimiento' => [
        'comentarios' => ['comentarios.php', 'Comentarios del docente', 'comentario'],
        'incentivos'  => ['incentivos.php', 'Incentivos', 'incentivo'],
    ] + (ESTUDIANTE_VE_NOTAS ? ['notas' => ['calificaciones.php', 'Calificaciones', 'nota']] : []) + [
        'avisos'      => ['avisos.php', 'Avisos', 'campana'],
    ],
    'Cuenta' => [
        'perfil' => ['perfil.php', 'Mi perfil', 'perfil'],
    ],
];
$migas = $migas ?? [];
$iniciales = mb_strtoupper(mb_substr($estudiante['nombre'], 0, 1) . mb_substr($estudiante['apellido'], 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · Panel estudiante · THESISVISTA</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/docente.css">
    <link rel="stylesheet" href="../css/estudiante.css">
</head>
<body class="docente estudiante" data-auto-recargar="<?= !empty($auto_recargar) ? '1' : '0' ?>">
<div class="app">
    <aside class="d-lateral" id="menu-lateral">
        <a href="dashboard.php" class="d-marca">
            <span class="d-logo">TV</span>
            <span><strong>THESISVISTA</strong><small>Panel estudiante</small></span>
        </a>

        <?php if ($grupo): ?>
            <div class="e-grupo-actual">
                <small>Mi grupo</small>
                <?php if (count($grupos) > 1): ?>
                    <form method="get" action="dashboard.php">
                        <select name="grupo" onchange="this.form.submit()" aria-label="Cambiar de grupo">
                            <?php foreach ($grupos as $g): ?>
                                <option value="<?= (int) $g['id_grupo'] ?>" <?= (int) $g['id_grupo'] === $id_grupo ? 'selected' : '' ?>>
                                    <?= e($g['nombre_grupo'] . ' · Curso ' . $g['nombre_curso']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <noscript><button class="btn btn-chico btn-secundario">Ver</button></noscript>
                    </form>
                <?php else: ?>
                    <strong><?= e($grupo['nombre_grupo']) ?></strong><span>Curso <?= e($grupo['nombre_curso']) ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <nav class="d-menu" aria-label="Menú del estudiante">
            <?php foreach ($menu as $grupo_menu => $items): ?>
                <p class="d-menu-titulo"><?= e($grupo_menu) ?></p>
                <?php foreach ($items as $clave => [$url, $texto, $ico]): ?>
                    <a href="<?= $url ?>" class="<?= ($seccion ?? '') === $clave ? 'activo' : '' ?>">
                        <?= icono($ico) ?><span><?= e($texto) ?></span>
                        <?php if ($clave === 'avisos'): ?>
                            <span class="d-insignia" id="insignia-avisos" <?= $avisos_nuevos ? '' : 'hidden' ?>><?= $avisos_nuevos ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>

        <a href="../logout.php" class="d-salir"><?= icono('salir') ?><span>Cerrar sesión</span></a>
    </aside>

    <div class="contenido">
        <header class="d-encabezado">
            <div>
                <?php if ($migas): ?>
                    <nav class="d-migas" aria-label="Ubicación">
                        <?php foreach ($migas as $i => [$texto, $url]): ?>
                            <?php if ($i): ?><span aria-hidden="true">›</span><?php endif; ?>
                            <?php if ($url): ?><a href="<?= e($url) ?>"><?= e($texto) ?></a><?php else: ?><span><?= e($texto) ?></span><?php endif; ?>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
                <h1><?= e($titulo) ?></h1>
                <?php if (!empty($subtitulo)): ?><p class="d-subtitulo"><?= e($subtitulo) ?></p><?php endif; ?>
            </div>
            <div class="d-usuario">
                <span class="d-vivo" id="indicador-vivo" title="Conectado: los cambios del docente y del administrador se muestran automáticamente">
                    <span></span> En vivo
                </span>
                <a href="perfil.php" class="d-usuario-datos">
                    <span class="avatar"><?= e($iniciales) ?></span>
                    <span><strong><?= e($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></strong><small>Estudiante</small></span>
                </a>
            </div>
        </header>

        <div class="d-aviso-cambios" id="aviso-cambios" hidden>
            Hay información nueva de su grupo (fases, comentarios o entregas).
            <button type="button" class="btn btn-chico btn-primario" onclick="location.reload()">Actualizar ahora</button>
        </div>

        <main class="principal d-principal">
            <?php mostrar_mensajes(); ?>
