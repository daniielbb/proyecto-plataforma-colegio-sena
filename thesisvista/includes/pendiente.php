<?php /** Página temporal para los módulos que aún no se han desarrollado. */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Módulo <?= e($titulo_modulo) ?> · THESISVISTA</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="pagina-login">
    <main class="login-caja">
        <div class="login-marca">
            <span class="logo">TV</span>
            <h1>Módulo <?= e($titulo_modulo) ?></h1>
            <p>Hola, <?= e($usuario['nombre'] . ' ' . $usuario['apellido']) ?>. Este módulo está en construcción.</p>
        </div>
        <?php mostrar_mensajes(); ?>
        <a href="../logout.php" class="btn btn-primario btn-bloque">Cerrar sesión</a>
    </main>
</body>
</html>
