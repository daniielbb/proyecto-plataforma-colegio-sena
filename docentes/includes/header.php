<?php
/**
 * Encabezado + barra lateral del docente.
 * Variables esperadas: $docente (array), $titulo_pagina, $pagina_activa.
 */
$titulo_pagina = $titulo_pagina ?? 'Thesis Vista';
$pagina_activa = $pagina_activa ?? '';
$menu = [
    'inicio'       => ['Inicio',       'docente/dashboard.php'],
    'grupos'       => ['Mis grupos',   'docente/grupos.php'],
    'proyectos'    => ['Proyectos',    'docente/proyectos.php'],
    'revisiones'   => ['Revisiones',   'docente/revisiones.php'],
    'correcciones' => ['Correcciones', 'docente/correcciones.php'],
    'perfil'       => ['Perfil',       'docente/perfil.php'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo_pagina) ?> · Thesis Vista</title>
    <link rel="stylesheet" href="<?= url('css/styles.css') ?>">
</head>
<body>
<input type="checkbox" id="menu-toggle" class="menu-toggle" aria-label="Abrir menú">
<div class="app">
    <aside class="sidebar">
        <div class="marca">
            <span class="marca-logo">TV</span>
            <div>
                <strong>Thesis Vista</strong>
                <small>Panel docente</small>
            </div>
        </div>
        <nav class="menu">
            <?php foreach ($menu as $clave => [$texto, $ruta]): ?>
                <a href="<?= url($ruta) ?>" class="<?= $pagina_activa === $clave ? 'activo' : '' ?>">
                    <?= icono($clave) ?><span><?= e($texto) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php if (!empty($docente)): ?>
        <div class="sidebar-usuario">
            <div class="avatar"><?= e(mb_substr($docente['nombre'], 0, 1) . mb_substr($docente['apellido'], 0, 1)) ?></div>
            <div class="sidebar-usuario-datos">
                <strong><?= e($docente['nombre'] . ' ' . $docente['apellido']) ?></strong>
                <small>Docente</small>
            </div>
            <a class="salir" href="<?= url('logout.php') ?>" title="Cerrar sesión"><?= icono('salir') ?></a>
        </div>
        <?php endif; ?>
    </aside>

    <main class="contenido">
        <header class="barra-superior">
            <label for="menu-toggle" class="btn-menu" aria-hidden="true">☰</label>
            <h1><?= e($titulo_pagina) ?></h1>
        </header>
        <?= mostrar_flash() ?>
