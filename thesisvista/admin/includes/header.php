<?php
/**
 * Plantilla del panel de administrador: encabezado + barra lateral.
 * Variables que debe definir cada página antes de incluirla:
 *   $titulo  -> título de la página
 *   $seccion -> 'inicio', 'usuarios' o 'proyectos' (resalta el menú)
 *   $admin   -> datos del administrador (devuelto por requerir_rol)
 */
$menu = [
    'inicio'    => ['dashboard.php', 'Inicio'],
    'usuarios'  => ['usuarios.php',  'Gestión de usuarios'],
    'proyectos' => ['proyectos.php', 'Gestión de proyectos / tesis'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · THESISVISTA</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<div class="app">
    <aside class="lateral">
        <a href="dashboard.php" class="marca">
            <span class="logo">TV</span>
            <span>THESISVISTA</span>
        </a>
        <p class="lateral-rol">Vista administrador</p>
        <nav class="menu">
            <?php foreach ($menu as $clave => [$url, $texto]): ?>
                <a href="<?= $url ?>" class="<?= ($seccion ?? '') === $clave ? 'activo' : '' ?>"><?= e($texto) ?></a>
            <?php endforeach; ?>
        </nav>
        <a href="../logout.php" class="menu-salir">Cerrar sesión</a>
    </aside>

    <div class="contenido">
        <header class="encabezado">
            <h1><?= e($titulo) ?></h1>
            <div class="encabezado-usuario">
                <span class="avatar"><?= e(mb_strtoupper(mb_substr($admin['nombre'], 0, 1))) ?></span>
                <div>
                    <strong><?= e($admin['nombre'] . ' ' . $admin['apellido']) ?></strong>
                    <small><?= e(nombre_rol($admin['rol'])) ?></small>
                </div>
            </div>
        </header>

        <main class="principal">
            <?php mostrar_mensajes(); ?>
