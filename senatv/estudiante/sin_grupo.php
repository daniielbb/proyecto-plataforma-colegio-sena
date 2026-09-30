<?php
/**
 * Pantalla informativa cuando el estudiante NO tiene grupo asignado.
 * El estudiante no puede asignarse a sí mismo: lo hace el docente o el administrador.
 */
require __DIR__ . '/_base.php';

layout_inicio('Sin grupo asignado', '', $ctx);
?>
<section class="tarjeta tarjeta-mensaje centrado">
  <div class="mensaje-icono"><?= icono('grupo') ?></div>
  <h1>Todavía no tienes un grupo asignado</h1>
  <p>Tu cuenta está activa, pero aún no perteneces a ningún grupo. Cuando tu docente o el administrador
     te asignen a un grupo podrás ver tu proyecto, sus fases, documentos, correcciones y comentarios.</p>
  <ul class="lista-simple">
    <li><?= icono('check') ?> Cuenta de estudiante verificada</li>
    <li><?= icono('reloj') ?> Pendiente: asignación de grupo por parte del docente</li>
  </ul>
  <p class="texto-suave">Si crees que es un error, comunícate con tu docente.</p>
</section>
<?php layout_fin(); ?>
