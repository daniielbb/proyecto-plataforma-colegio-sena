<?php
/** Pantalla 403: se muestra cuando un rol intenta entrar a otra interfaz. */
require_once __DIR__ . '/iconos.php';
$destino = isset($_SESSION['rol']) ? inicio_por_rol($_SESSION['rol']) : 'login.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Acceso denegado · <?= e(APP_NOMBRE) ?></title>
<link rel="stylesheet" href="<?= e(url('css/estilos.css')) ?>">
</head>
<body class="pagina-centro">
  <main class="tarjeta tarjeta-mensaje">
    <div class="mensaje-icono"><?= icono('candado') ?></div>
    <h1>Acceso denegado</h1>
    <p>Tu rol no tiene permiso para ver esta sección de la plataforma.</p>
    <a class="boton" href="<?= e(url($destino)) ?>">Volver a mi inicio</a>
  </main>
</body>
</html>
