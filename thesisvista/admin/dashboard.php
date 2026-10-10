<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/seguridad.php';
require_once __DIR__ . '/../includes/funciones_admin.php';

$admin = requerir_rol('administrador');
$pdo   = conectar();


$por_rol = array_fill_keys(array_keys(ROLES), 0);
foreach ($pdo->query('SELECT rol, COUNT(*) AS total FROM usuarios GROUP BY rol') as $fila) {
    $por_rol[$fila['rol']] = (int) $fila['total'];
}


$por_estado = array_fill_keys(ESTADOS_TESIS, 0);
foreach ($pdo->query('SELECT estado, COUNT(*) AS total FROM tesis GROUP BY estado') as $fila) {
    $por_estado[$fila['estado']] = (int) $fila['total'];
}
$total_tesis = array_sum($por_estado);

$total_grupos = (int) $pdo->query('SELECT COUNT(*) FROM grupos')->fetchColumn();


$ultimos_usuarios = $pdo->query('SELECT usuario_id, nombre, apellido, correo, rol
                                 FROM usuarios ORDER BY usuario_id DESC LIMIT 5')->fetchAll();

$ultimas_tesis = $pdo->query('SELECT t.id_tesis, t.titulo, t.estado, t.fecha_registro,
                                     e.nombre AS est_nombre, e.apellido AS est_apellido
                              FROM tesis t JOIN usuarios e ON e.usuario_id = t.id_estudiante
                              ORDER BY t.fecha_registro DESC, t.id_tesis DESC LIMIT 5')->fetchAll();

$titulo  = 'Panel de administración';
$seccion = 'inicio';
require __DIR__ . '/../includes/header.php';
?>

<section class="rejilla">
    <div class="dato"><span>Administradores</span><strong><?= $por_rol['administrador'] ?></strong></div>
    <div class="dato"><span>Docentes</span><strong><?= $por_rol['profesor'] ?></strong></div>
    <div class="dato"><span>Estudiantes</span><strong><?= $por_rol['estudiante'] ?></strong></div>
    <div class="dato"><span>Grupos</span><strong><?= $total_grupos ?></strong></div>
    <div class="dato"><span>Proyectos / tesis</span><strong><?= $total_tesis ?></strong></div>
</section>

<section class="rejilla-2" style="margin-bottom:24px">
    <a class="acceso" href="usuarios.php">
        <h3>1. Gestión de usuarios</h3>
        <p>Ver, crear, editar, cambiar rol y eliminar usuarios.</p>
    </a>
    <a class="acceso" href="grupos.php">
        <h3>2. Gestión de grupos</h3>
        <p>Crear grupos y asignarles todos los estudiantes que necesiten.</p>
    </a>
    <a class="acceso" href="proyectos.php">
        <h3>3. Gestión de proyectos / tesis</h3>
        <p>Ver, crear, editar, consultar, asignar docente y eliminar proyectos.</p>
    </a>
</section>

<section class="rejilla-2">
    <div class="tarjeta">
        <div class="tarjeta-cabecera">
            <h2>Últimos usuarios</h2>
            <a href="crear_usuario.php" class="btn btn-primario btn-chico">+ Crear usuario</a>
        </div>
        <ul class="lista-simple">
            <?php foreach ($ultimos_usuarios as $u): ?>
                <li>
                    <strong><?= e($u['nombre'] . ' ' . $u['apellido']) ?></strong>
                    <span class="etiqueta rol-<?= e($u['rol']) ?>"><?= e(nombre_rol($u['rol'])) ?></span><br>
                    <small><?= e($u['correo']) ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="tarjeta">
        <div class="tarjeta-cabecera">
            <h2>Proyectos recientes</h2>
            <a href="crear_proyecto.php" class="btn btn-primario btn-chico">+ Crear proyecto</a>
        </div>
        <p>
            <?php foreach ($por_estado as $estado => $total): ?>
                <span class="etiqueta <?= clase_estado($estado) ?>"><?= e($estado) ?>: <?= $total ?></span>
            <?php endforeach; ?>
        </p>
        <ul class="lista-simple">
            <?php foreach ($ultimas_tesis as $t): ?>
                <li>
                    <a href="ver_proyecto.php?id=<?= (int) $t['id_tesis'] ?>"><strong><?= e($t['titulo']) ?></strong></a>
                    <span class="etiqueta <?= clase_estado($t['estado']) ?>"><?= e($t['estado']) ?></span><br>
                    <small><?= e($t['est_nombre'] . ' ' . $t['est_apellido']) ?> · <?= e($t['fecha_registro']) ?></small>
                </li>
            <?php endforeach; ?>
            <?php if (!$ultimas_tesis): ?><li class="vacio">Aún no hay proyectos registrados.</li><?php endif; ?>
        </ul>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>