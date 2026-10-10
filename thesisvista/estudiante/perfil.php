<?php

require_once __DIR__ . '/includes/inicio.php';

$pdo = conectar();
$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf('perfil.php');
    $actual = $_POST['actual'] ?? '';
    $nueva  = $_POST['nueva'] ?? '';
    $repite = $_POST['repite'] ?? '';

    $stmt = $pdo->prepare('SELECT contrasena FROM usuarios WHERE usuario_id = ?');
    $stmt->execute([$id_estudiante]);
    $guardada = (string) $stmt->fetchColumn();
    $ok = password_get_info($guardada)['algoName'] !== 'unknown' ? password_verify($actual, $guardada) : hash_equals($guardada, $actual);

    if (!$ok) $errores[] = 'La contraseña actual no es correcta.';
    if (mb_strlen($nueva) < 4) $errores[] = 'La nueva contraseña debe tener al menos 4 caracteres.';
    if ($nueva !== $repite) $errores[] = 'Las contraseñas nuevas no coinciden.';
    if (!$errores) {
        $pdo->prepare('UPDATE usuarios SET contrasena = ? WHERE usuario_id = ?')->execute([password_hash($nueva, PASSWORD_DEFAULT), $id_estudiante]);
        mensaje('exito', 'Contraseña actualizada.');
        redirigir('perfil.php');
    }
}

$stmt = $pdo->prepare('SELECT
    (SELECT COUNT(*) FROM entregas WHERE id_estudiante = ?) AS entregas,
    (SELECT COUNT(DISTINCT id_fase) FROM entregas WHERE id_estudiante = ?) AS fases');
$stmt->execute([$id_estudiante, $id_estudiante]);
$tot = $stmt->fetch();

$titulo  = 'Mi perfil';
$seccion = 'perfil';
require __DIR__ . '/includes/header.php';
?>

<div class="d-columnas-iguales">
    <section class="tarjeta">
        <div style="display:flex;gap:16px;align-items:center;margin-bottom:16px">
            <span class="avatar" style="width:64px;height:64px;font-size:24px"><?= e(mb_strtoupper(mb_substr($estudiante['nombre'], 0, 1) . mb_substr($estudiante['apellido'], 0, 1))) ?></span>
            <div><h2 style="margin:0"><?= e($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></h2>
                <span class="etiqueta rol-estudiante">Estudiante</span> <small class="d-tenue"><?= e($estudiante['correo']) ?></small></div>
        </div>
        <dl class="d-ficha">
            <div><dt>Grupo<?= count($grupos) > 1 ? 's' : '' ?></dt><dd><?= $grupos ? e(implode(', ', array_map(fn($g) => $g['nombre_grupo'] . ' (curso ' . $g['nombre_curso'] . ')', $grupos))) : 'Sin grupo' ?></dd></div>
            <div><dt>Archivos enviados</dt><dd><?= (int) $tot['entregas'] ?></dd></div>
            <div><dt>Fases con entrega</dt><dd><?= (int) $tot['fases'] ?></dd></div>
        </dl>
        <p class="d-tenue" style="margin-bottom:0"><small>Tus datos personales y tu grupo los administra el administrador de la plataforma.</small></p>
    </section>

    <section class="tarjeta">
        <h2>Cambiar contraseña</h2>
        <?php if ($errores): ?><div class="alerta alerta-error"><ul><?php foreach ($errores as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="post" class="formulario">
            <?= campo_csrf() ?>
            <label for="actual">Contraseña actual</label><input type="password" id="actual" name="actual" required autocomplete="current-password">
            <label for="nueva">Nueva contraseña</label><input type="password" id="nueva" name="nueva" required minlength="4" autocomplete="new-password">
            <label for="repite">Repita la nueva contraseña</label><input type="password" id="repite" name="repite" required minlength="4" autocomplete="new-password">
            <button class="btn btn-primario btn-bloque">Guardar contraseña</button>
        </form>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
