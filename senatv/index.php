<?php
/**
 * HOME público. Si ya hay sesión, se envía al panel según el rol.
 */
require_once __DIR__ . '/includes/sesion.php';
require_once __DIR__ . '/includes/iconos.php';

iniciar_sesion();
if (usuario_autenticado()) {
    redirigir('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(APP_NOMBRE) ?> · <?= e(APP_SUBTITULO) ?></title>
<link rel="stylesheet" href="<?= e(url('css/estilos.css')) ?>">
</head>
<body class="pagina-home">
  <header class="home-barra">
    <div class="home-marca"><?= icono('birrete') ?> <strong><?= e(APP_NOMBRE) ?></strong></div>
    <a class="boton boton-claro" href="<?= e(url('login.php')) ?>">Iniciar sesión</a>
  </header>

  <main class="home-hero">
    <h1>Sigue tu proyecto académico fase por fase</h1>
    <p>Consulta el estado de tus fases, tus documentos y las correcciones y comentarios de tu docente en un solo lugar.</p>
    <a class="boton" href="<?= e(url('login.php')) ?>">Ingresar a la plataforma</a>

    <div class="home-roles">
      <div class="tarjeta"><?= icono('birrete') ?><h3>Estudiante</h3><p>Consulta tu proyecto, fases, avances y retroalimentación.</p></div>
      <div class="tarjeta"><?= icono('correccion') ?><h3>Docente</h3><p>Revisa documentos, corrige, comenta y avanza los grupos.</p></div>
      <div class="tarjeta"><?= icono('grupo') ?><h3>Administrador</h3><p>Gestiona usuarios, cursos y grupos.</p></div>
    </div>
  </main>
</body>
</html>
