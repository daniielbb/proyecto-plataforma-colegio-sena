<?php

require_once __DIR__ . '/includes/inicio.php';
exigir_grupo($grupo);

$fases = fases_estudiante($id_grupo, $id_estudiante);
$ver_grupo = ($_GET['ver'] ?? '') === 'grupo';
$entregas = entregas_estudiante($id_grupo, $ver_grupo ? null : $id_estudiante);
$abiertas = array_values(array_filter($fases, fn($f) => $f['puede_entregar']));

$titulo    = 'Mis entregas';
$subtitulo = 'Archivos asociados a ti, a tu grupo (' . $grupo['nombre_grupo'] . '), al proyecto y a cada fase';
$seccion   = 'entregas';
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<?php if ($abiertas): ?>
    <section class="tarjeta">
        <h2 class="d-seccion-titulo"><?= icono('subir') ?> Fases que reciben entregas</h2>
        <div class="d-barra-acciones">
            <?php foreach ($abiertas as $f): ?>
                <a class="btn btn-secundario" href="fase.php?id=<?= (int) $f['id_fase'] ?>#subir">Fase <?= (int) $f['orden'] ?> · <?= e($f['nombre_fase']) ?></a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<nav class="d-pestanas">
    <a href="entregas.php" class="<?= $ver_grupo ? '' : 'activa' ?>">Enviadas por mí</a>
    <a href="entregas.php?ver=grupo" class="<?= $ver_grupo ? 'activa' : '' ?>">Todo mi grupo</a>
</nav>

<section class="tarjeta">
    <?= $entregas ? lista_entregas($entregas, $id_estudiante, true)
                  : '<p class="vacio">' . ($ver_grupo ? 'Tu grupo aún no ha subido archivos.' : 'Aún no has subido archivos. Entra a una fase disponible y usa «Subir entrega».') . '</p>' ?>
    <p class="ayuda d-tenue"><small>Para reemplazar una entrega sube una nueva versión desde la fase (mientras no esté en revisión ni aprobada). Las entregas no se pueden borrar: el docente conserva el historial.</small></p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
