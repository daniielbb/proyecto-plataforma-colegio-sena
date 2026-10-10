<?php

$por_revisar = total_por_revisar($id_docente);
$menu = [
    'Mi trabajo' => [
        'inicio'     => ['dashboard.php', 'Inicio', 'inicio'],
        'proyectos'  => ['proyectos.php', 'Mis proyectos', 'proyecto'],
        'cursos'     => ['cursos.php', 'Cursos y grupos', 'cursos'],
        'fases'      => ['fases.php', 'Fases', 'fases'],
    ],
    'Seguimiento' => [
        'avances'        => ['avances.php', 'Avances', 'avance'],
        'trabajos'       => ['trabajos.php', 'Trabajos', 'trabajos'],
        'calificaciones' => ['calificaciones.php', 'Calificaciones', 'nota'],
        'comentarios'    => ['comentarios.php', 'Comentarios', 'comentario'],
        'incentivos'     => ['incentivos.php', 'Incentivos', 'incentivo'],
    ],
    'Cuenta' => [
        'perfil' => ['perfil.php', 'Mi perfil', 'perfil'],
    ],
];
$migas = $migas ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · Panel docente · THESISVISTA</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/docente.css">
</head>
<body class="docente" data-auto-recargar="<?= !empty($auto_recargar) ? '1' : '0' ?>">
<div class="app">
    <aside class="d-lateral" id="menu-lateral">
        <a href="dashboard.php" class="d-marca">
            <span class="d-logo">TV</span>
            <span><strong>THESISVISTA</strong><small>Panel docente</small></span>
        </a>

        <nav class="d-menu" aria-label="Menú del docente">
            <?php foreach ($menu as $grupo_menu => $items): ?>
                <p class="d-menu-titulo"><?= e($grupo_menu) ?></p>
                <?php foreach ($items as $clave => [$url, $texto, $ico]): ?>
                    <a href="<?= $url ?>" class="<?= ($seccion ?? '') === $clave ? 'activo' : '' ?>">
                        <?= icono($ico) ?><span><?= e($texto) ?></span>
                        <?php if ($clave === 'trabajos'): ?>
                            <span class="d-insignia" id="insignia-revisar" <?= $por_revisar ? '' : 'hidden' ?>><?= $por_revisar ?></span>
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
                <span class="d-vivo" id="indicador-vivo" title="Conectado: los cambios se muestran automáticamente">
                    <span></span> En vivo
                </span>
                <a href="perfil.php" class="d-usuario-datos">
                    <span class="avatar"><?= e(mb_strtoupper(mb_substr($docente['nombre'], 0, 1) . mb_substr($docente['apellido'], 0, 1))) ?></span>
                    <span><strong><?= e($docente['nombre'] . ' ' . $docente['apellido']) ?></strong><small>Docente</small></span>
                </a>
            </div>
        </header>

        <div class="d-aviso-cambios" id="aviso-cambios" hidden>
            Hay información nueva (entregas, avances o comentarios).
            <button type="button" class="btn btn-chico btn-primario" onclick="location.reload()">Actualizar ahora</button>
        </div>

        <main class="principal d-principal">
            <?php mostrar_mensajes(); ?>
