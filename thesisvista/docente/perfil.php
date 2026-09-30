<?php
require_once __DIR__ . '/../includes/docente_init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $actual = (string)($_POST['actual'] ?? '');
    $nueva  = (string)($_POST['nueva'] ?? '');
    $conf   = (string)($_POST['confirmar'] ?? '');

    $st = $pdo->prepare('SELECT contrasena FROM usuarios WHERE usuario_id = ?');
    $st->execute([$doc]);
    $hash = (string)$st->fetchColumn();
    $info = password_get_info($hash);
    $ok_actual = ($info['algo'] !== null && $info['algo'] !== 0) ? password_verify($actual, $hash) : hash_equals($hash, $actual);

    if (!$ok_actual) {
        flash('error', 'La contraseña actual no es correcta.');
    } elseif (mb_strlen($nueva) < 6) {
        flash('error', 'La nueva contraseña debe tener al menos 6 caracteres.');
    } elseif ($nueva !== $conf) {
        flash('error', 'La confirmación no coincide con la nueva contraseña.');
    } else {
        $pdo->prepare('UPDATE usuarios SET contrasena = ? WHERE usuario_id = ?')
            ->execute([password_hash($nueva, PASSWORD_DEFAULT), $doc]);
        flash('ok', 'Contraseña actualizada.');
    }
    redirigir('docente/perfil.php');
}

$st = $pdo->prepare('SELECT c.nombre_curso, c.ficha, dc.fecha_asignacion,
                            (SELECT COUNT(*) FROM grupos g WHERE g.id_curso = c.id_curso) AS total_grupos
                       FROM docente_curso dc JOIN cursos c ON c.id_curso = dc.id_curso
                      WHERE dc.id_profesor = ? ORDER BY c.nombre_curso');
$st->execute([$doc]);
$cursos = $st->fetchAll();

$st = $pdo->prepare('SELECT COUNT(*) FROM tesis WHERE id_profesor = ?');
$st->execute([$doc]);
$como_asignado = (int)$st->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*) FROM correcciones WHERE id_profesor = ?');
$st->execute([$doc]);
$mis_revisiones = (int)$st->fetchColumn();

$titulo_pagina = 'Perfil';
$pagina_activa = 'perfil';
require __DIR__ . '/../includes/header.php';
?>

<div class="mitades">
    <section class="card">
        <div class="integrante" style="margin-bottom:1.25rem">
            <span class="avatar avatar-grande"><?= e(mb_substr($docente['nombre'], 0, 1) . mb_substr($docente['apellido'], 0, 1)) ?></span>
            <div>
                <h2 style="margin:0"><?= e($docente['nombre'] . ' ' . $docente['apellido']) ?></h2>
                <span class="texto-suave">Docente</span>
            </div>
        </div>
        <dl class="ficha">
            <dt>Correo</dt><dd><?= e($docente['correo']) ?></dd>
            <dt>Último acceso</dt><dd><?= fecha_hora($docente['ultimo_acceso']) ?></dd>
            <dt>Proyectos a cargo</dt><dd><?= $como_asignado ?></dd>
            <dt>Revisiones realizadas</dt><dd><?= $mis_revisiones ?></dd>
        </dl>
        <p class="texto-suave chico" style="margin-top:1rem">Los datos personales y las asignaciones los administra el administrador de la plataforma.</p>
    </section>

    <section class="card">
        <h2>Cambiar contraseña</h2>
        <form method="post" class="formulario">
            <?= csrf_campo() ?>
            <label>Contraseña actual <input type="password" name="actual" required></label>
            <label>Nueva contraseña <input type="password" name="nueva" minlength="6" required></label>
            <label>Confirmar nueva contraseña <input type="password" name="confirmar" minlength="6" required></label>
            <button class="btn">Actualizar contraseña</button>
        </form>
    </section>
</div>

<section class="card">
    <h2>Cursos asignados</h2>
    <?php if (!$cursos): ?>
        <p class="texto-suave">No tienes cursos asignados.</p>
    <?php else: ?>
    <table>
        <thead><tr><th>Curso</th><th>Ficha</th><th>Grupos</th><th>Asignado desde</th></tr></thead>
        <tbody>
        <?php foreach ($cursos as $c): ?>
            <tr><td><?= e($c['nombre_curso']) ?></td><td><?= e($c['ficha'] ?? '—') ?></td>
                <td><?= (int)$c['total_grupos'] ?></td><td><?= fecha($c['fecha_asignacion']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
