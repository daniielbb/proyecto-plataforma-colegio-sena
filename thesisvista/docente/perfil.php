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
    $stmt->execute([$id_docente]);
    $guardada = (string) $stmt->fetchColumn();
    $ok = password_get_info($guardada)['algoName'] !== 'unknown' ? password_verify($actual, $guardada) : hash_equals($guardada, $actual);

    if (!$ok) $errores[] = 'La contraseña actual no es correcta.';
    if (mb_strlen($nueva) < 4) $errores[] = 'La nueva contraseña debe tener al menos 4 caracteres.';
    if ($nueva !== $repite) $errores[] = 'Las contraseñas nuevas no coinciden.';
    if (!$errores) {
        $pdo->prepare('UPDATE usuarios SET contrasena = ? WHERE usuario_id = ?')->execute([password_hash($nueva, PASSWORD_DEFAULT), $id_docente]);
        mensaje('exito', 'Contraseña actualizada.');
        redirigir('perfil.php');
    }
}

$cursos = cursos_docente($id_docente);
$dirigidos = array_filter(proyectos_docente($id_docente), fn($p) => (int) $p['es_director'] === 1);
$stmt = $pdo->prepare('SELECT
    (SELECT COUNT(*) FROM fases WHERE id_profesor_creador = ?) AS fases,
    (SELECT COUNT(*) FROM retroalimentacion WHERE id_profesor = ?) AS comentarios,
    (SELECT COUNT(*) FROM calificaciones WHERE id_profesor = ?) AS notas,
    (SELECT COUNT(*) FROM incentivo_otorgado WHERE id_profesor = ?) AS incentivos');
$stmt->execute([$id_docente, $id_docente, $id_docente, $id_docente]);
$tot = $stmt->fetch();

$titulo  = 'Mi perfil';
$seccion = 'perfil';
require __DIR__ . '/includes/header.php';
?>

<div class="d-columnas-iguales">
    <section class="tarjeta">
        <div style="display:flex;gap:16px;align-items:center;margin-bottom:16px">
            <span class="avatar" style="width:64px;height:64px;font-size:24px"><?= e(mb_strtoupper(mb_substr($docente['nombre'], 0, 1) . mb_substr($docente['apellido'], 0, 1))) ?></span>
            <div><h2 style="margin:0"><?= e($docente['nombre'] . ' ' . $docente['apellido']) ?></h2>
                <span class="etiqueta rol-profesor">Docente</span> <small class="d-tenue"><?= e($docente['correo']) ?></small></div>
        </div>
        <dl class="d-ficha">
            <div><dt>Cursos</dt><dd><?= $cursos ? e(implode(', ', array_map(fn($c) => $c['nombre_curso'], $cursos))) : '—' ?></dd></div>
            <div><dt>Proyectos que dirige</dt><dd><?= count($dirigidos) ?></dd></div>
            <div><dt>Fases creadas</dt><dd><?= (int) $tot['fases'] ?></dd></div>
            <div><dt>Comentarios escritos</dt><dd><?= (int) $tot['comentarios'] ?></dd></div>
            <div><dt>Notas asignadas</dt><dd><?= (int) $tot['notas'] ?></dd></div>
            <div><dt>Incentivos otorgados</dt><dd><?= (int) $tot['incentivos'] ?></dd></div>
        </dl>
        <p class="d-tenue" style="margin-bottom:0"><small>Sus datos personales y sus cursos los administra el administrador de la plataforma.</small></p>
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
