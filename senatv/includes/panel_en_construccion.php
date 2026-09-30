<?php /** Vista temporal compartida para las interfaces aún no desarrolladas. */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo) ?> · <?= e(APP_NOMBRE) ?></title>
<link rel="stylesheet" href="<?= e(url('css/estilos.css')) ?>">
</head>
<body class="pagina-centro">
  <main class="tarjeta tarjeta-mensaje">
    <div class="mensaje-icono"><?= icono('proyecto') ?></div>
    <h1><?= e($titulo) ?></h1>
    <p>Hola, <?= e($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?>. <?= e($descripcion) ?></p>
    <ul class="lista-simple">
      <?php foreach ($funciones as $f): ?><li><?= icono('check') ?> <?= e($f) ?></li><?php endforeach; ?>
    </ul>
    <a class="boton" href="<?= e(url('logout.php?t=' . token_csrf())) ?>">Cerrar sesión</a>
  </main>
</body>
</html>
