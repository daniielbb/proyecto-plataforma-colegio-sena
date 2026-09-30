<?php
require_once __DIR__ . '/../includes/docente_init.php';

$id_grupo = entero($_GET['id'] ?? $_POST['id_grupo'] ?? 0);
if (!puede_ver_grupo($pdo, $doc, $id_grupo)) {
    denegar('Este grupo no está asignado a tu usuario.');
}

$st = $pdo->prepare('SELECT g.*, c.nombre_curso, c.ficha FROM grupos g JOIN cursos c ON c.id_curso = g.id_curso WHERE g.id_grupo = ?');
$st->execute([$id_grupo]);
$grupo = $st->fetch();

$proyectos = proyectos_de_grupo($pdo, $doc, $id_grupo, (int)$grupo['id_curso']);
$ids_tesis_grupo = array_map(fn($p) => (int)$p['id_tesis'], $proyectos);

/* ------------------------------------------------------------------
   Acciones: participación individual (solo datos que existen en la BD)
   ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'actualizar_integrante') {
        $id_eg  = entero($_POST['id_estudiante_grupo'] ?? 0);
        $rol    = trim($_POST['rol_grupo'] ?? '');
        $estado = in_array($_POST['estado'] ?? '', ['Activo', 'Inactivo'], true) ? $_POST['estado'] : 'Activo';
        $up = $pdo->prepare('UPDATE estudiante_grupo SET rol_grupo = ?, estado = ?
                              WHERE id_estudiante_grupo = ? AND id_grupo = ?');
        $up->execute([$rol === '' ? null : mb_substr($rol, 0, 50), $estado, $id_eg, $id_grupo]);
        flash('ok', 'Integrante actualizado.');
    }

    if ($accion === 'otorgar_incentivo') {
        $id_est  = entero($_POST['id_estudiante'] ?? 0);
        $id_inc  = entero($_POST['id_incentivo'] ?? 0);
        $id_tes  = entero($_POST['id_tesis'] ?? 0) ?: null;

        $es_miembro = $pdo->prepare('SELECT 1 FROM estudiante_grupo WHERE id_estudiante = ? AND id_grupo = ?');
        $es_miembro->execute([$id_est, $id_grupo]);
        $existe_inc = $pdo->prepare('SELECT 1 FROM incentivos WHERE id_incentivo = ?');
        $existe_inc->execute([$id_inc]);

        if (!$es_miembro->fetch() || !$existe_inc->fetch() || ($id_tes && !in_array($id_tes, $ids_tesis_grupo, true))) {
            flash('error', 'Datos no válidos para otorgar el incentivo.');
        } else {
            $dup = $pdo->prepare('SELECT 1 FROM estudiante_incentivo WHERE id_estudiante = ? AND id_incentivo = ? AND id_tesis <=> ?');
            $dup->execute([$id_est, $id_inc, $id_tes]);
            if ($dup->fetch()) {
                flash('info', 'El estudiante ya tiene ese incentivo para este proyecto.');
            } else {
                $pdo->prepare('INSERT INTO estudiante_incentivo (id_estudiante, id_incentivo, id_tesis, fecha_obtenido) VALUES (?, ?, ?, ?)')
                    ->execute([$id_est, $id_inc, $id_tes, date('Y-m-d H:i:s')]);
                flash('ok', 'Incentivo otorgado.');
            }
        }
    }
    redirigir('docente/grupo.php?id=' . $id_grupo . '#participacion');
}

/* ------------------------------------------------------------------
   Consultas
   ------------------------------------------------------------------ */
// Integrantes y su participación registrada
$in = marcadores($ids_tesis_grupo);
$st = $pdo->prepare(
    "SELECT eg.id_estudiante_grupo, eg.rol_grupo, eg.estado, eg.fecha_asignacion,
            u.usuario_id, u.nombre, u.apellido, u.correo,
            (SELECT GROUP_CONCAT(t.titulo SEPARATOR ', ') FROM tesis t
              WHERE t.id_estudiante = u.usuario_id AND t.id_grupo = eg.id_grupo) AS autor_de,
            (SELECT COUNT(*) FROM comentarios c
              WHERE c.id_usuario = u.usuario_id AND c.id_tesis IN ($in)) AS total_comentarios
       FROM estudiante_grupo eg
       JOIN usuarios u ON u.usuario_id = eg.id_estudiante
      WHERE eg.id_grupo = ?
      ORDER BY eg.estado, u.nombre");
$st->execute(array_merge($ids_tesis_grupo, [$id_grupo]));
$integrantes = $st->fetchAll();

$st = $pdo->prepare(
    'SELECT ei.id_estudiante, i.nombre, t.titulo
       FROM estudiante_incentivo ei
       JOIN incentivos i ON i.id_incentivo = ei.id_incentivo
       LEFT JOIN tesis t ON t.id_tesis = ei.id_tesis
       JOIN estudiante_grupo eg ON eg.id_estudiante = ei.id_estudiante AND eg.id_grupo = ?
      ORDER BY ei.fecha_obtenido');
$st->execute([$id_grupo]);
$incentivos_por_est = [];
foreach ($st->fetchAll() as $row) {
    $incentivos_por_est[$row['id_estudiante']][] = $row;
}
$catalogo_incentivos = $pdo->query('SELECT id_incentivo, nombre FROM incentivos ORDER BY id_incentivo')->fetchAll();

// Docentes del curso
$st = $pdo->prepare('SELECT u.nombre, u.apellido FROM docente_curso dc JOIN usuarios u ON u.usuario_id = dc.id_profesor
                      WHERE dc.id_curso = ? ORDER BY u.nombre');
$st->execute([$grupo['id_curso']]);
$docentes_curso = $st->fetchAll();

// Documentos del grupo
$st = $pdo->prepare(
    "SELECT d.*, t.titulo, f.nombre_fase FROM documento d
       JOIN tesis t ON t.id_tesis = d.id_tesis
       LEFT JOIN fases f ON f.id_fase = d.id_fase
      WHERE d.id_tesis IN ($in)
      ORDER BY COALESCE(d.fecha_modificacion, d.fecha_subida) DESC");
$st->execute($ids_tesis_grupo);
$documentos = $st->fetchAll();

$historial   = historial_revisiones($pdo, $ids_tesis_grupo);
$revisiones  = array_slice(array_values(array_filter($historial, fn($h) => $h['tipo'] === 'revision')), 0, 5);
$comentarios = array_slice(array_values(array_filter($historial, fn($h) => $h['comentario'] !== null && $h['comentario'] !== '')), 0, 6);
$actividad   = ultimas_actividades($pdo, $ids_tesis_grupo, 8);

$titulo_pagina = $grupo['nombre_grupo'];
$pagina_activa = 'grupos';
require __DIR__ . '/../includes/header.php';
?>

<div class="migas"><a href="grupos.php">Mis grupos</a> / <?= e($grupo['nombre_grupo']) ?></div>

<section class="card">
    <div class="cabecera-detalle">
        <div>
            <div class="etiqueta-superior">Grupo</div>
            <h2><?= e($grupo['nombre_grupo']) ?></h2>
            <p class="texto-suave"><?= e($grupo['nombre_curso']) ?><?= $grupo['ficha'] ? ' · Ficha ' . e($grupo['ficha']) : '' ?></p>
        </div>
        <dl class="ficha">
            <dt>Integrantes</dt><dd><?= count(array_filter($integrantes, fn($i) => $i['estado'] === 'Activo')) ?> activos</dd>
            <dt>Proyectos</dt><dd><?= count($proyectos) ?></dd>
            <dt>Docentes del curso</dt>
            <dd><?= e(implode(', ', array_map(fn($d) => $d['nombre'] . ' ' . $d['apellido'], $docentes_curso))) ?></dd>
        </dl>
    </div>
</section>

<?php if (!$proyectos): ?>
    <div class="card vacio">Este grupo todavía no tiene proyectos registrados.</div>
<?php endif; ?>

<?php foreach ($proyectos as $p): $pr = $p['progreso']; ?>
<section class="card">
    <div class="card-titulo">
        <div>
            <div class="etiqueta-superior">Proyecto</div>
            <h2><?= e($p['titulo']) ?></h2>
            <span class="texto-suave chico">Docente asignado: <?= e($p['prof_nombre'] . ' ' . $p['prof_apellido']) ?></span>
        </div>
        <div class="botones">
            <?= badge($p['estado']) ?>
            <a class="btn" href="proyecto.php?id=<?= (int)$p['id_tesis'] ?>">Ver proyecto</a>
        </div>
    </div>

    <div class="mitades">
        <div>
            <div class="etiqueta-superior">Progreso del proyecto</div>
            <div class="progreso-fila">
                <?= barra_progreso($pr['porcentaje'], true) ?>
                <span class="porcentaje-grande"><?= $pr['porcentaje'] ?>%</span>
            </div>
            <p class="texto-suave chico" style="margin-top:.5rem">
                <?= $pr['completadas'] ?> fase(s) completada(s) · <?= $pr['pendientes'] ?> pendiente(s) ·
                Fase actual: <strong><?= $pr['actual'] ? e($pr['actual']['nombre_fase']) : 'Todas completadas' ?></strong>
            </p>
        </div>
        <ul class="pasos" style="margin-top:0">
            <?php foreach ($p['fases'] as $f): $cls = strtolower(str_replace(' ', '-', $f['estado'])); ?>
            <li class="<?= e($cls) ?>">
                <span class="simbolo"><?= simbolo_fase($f['estado']) ?></span>
                <a class="nombre" href="fase.php?tesis=<?= (int)$p['id_tesis'] ?>&fase=<?= (int)$f['id_fase'] ?>">
                    Fase <?= (int)$f['orden'] ?> — <?= e($f['nombre_fase']) ?></a>
                <span class="fecha"><?= e($f['estado']) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php endforeach; ?>

<section class="card" id="participacion">
    <div class="card-titulo">
        <h2>Integrantes y participación individual</h2>
    </div>
    <p class="texto-suave chico">Se muestra la información de participación que registra la base de datos: rol dentro del grupo,
        estado, proyecto del que es autor, comentarios realizados e incentivos obtenidos.</p>
    <?php if (!$integrantes): ?>
        <p class="texto-suave">El grupo no tiene integrantes.</p>
    <?php else: ?>
    <div class="tabla-contenedor">
    <table>
        <thead><tr><th>Integrante</th><th>Rol y estado</th><th>Autor de</th><th>Comentarios</th><th>Incentivos</th></tr></thead>
        <tbody>
        <?php foreach ($integrantes as $i): ?>
        <tr>
            <td>
                <div class="integrante">
                    <span class="avatar"><?= e(mb_substr($i['nombre'], 0, 1) . mb_substr($i['apellido'], 0, 1)) ?></span>
                    <div><strong><?= e($i['nombre'] . ' ' . $i['apellido']) ?></strong><br>
                        <span class="texto-suave chico"><?= e($i['correo']) ?></span></div>
                </div>
            </td>
            <td>
                <form method="post" class="form-linea">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="accion" value="actualizar_integrante">
                    <input type="hidden" name="id_grupo" value="<?= $id_grupo ?>">
                    <input type="hidden" name="id_estudiante_grupo" value="<?= (int)$i['id_estudiante_grupo'] ?>">
                    <input type="text" name="rol_grupo" maxlength="50" placeholder="Ej: Líder" value="<?= e($i['rol_grupo']) ?>">
                    <select name="estado">
                        <?php foreach (['Activo', 'Inactivo'] as $es): ?>
                            <option <?= $i['estado'] === $es ? 'selected' : '' ?>><?= $es ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-secundario btn-chico">Guardar</button>
                </form>
            </td>
            <td><?= $i['autor_de'] ? e($i['autor_de']) : '<span class="texto-suave">—</span>' ?></td>
            <td><?= (int)$i['total_comentarios'] ?></td>
            <td>
                <?php foreach ($incentivos_por_est[$i['usuario_id']] ?? [] as $inc): ?>
                    <span class="badge b-ok" title="<?= e($inc['titulo'] ?? '') ?>"><?= e($inc['nombre']) ?></span>
                <?php endforeach; ?>
                <form method="post" class="form-linea" style="margin-top:.4rem">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="accion" value="otorgar_incentivo">
                    <input type="hidden" name="id_grupo" value="<?= $id_grupo ?>">
                    <input type="hidden" name="id_estudiante" value="<?= (int)$i['usuario_id'] ?>">
                    <select name="id_incentivo">
                        <?php foreach ($catalogo_incentivos as $ci): ?>
                            <option value="<?= (int)$ci['id_incentivo'] ?>"><?= e($ci['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="id_tesis">
                        <?php foreach ($proyectos as $p): ?>
                            <option value="<?= (int)$p['id_tesis'] ?>"><?= e($p['titulo']) ?></option>
                        <?php endforeach; ?>
                        <option value="0">Sin proyecto</option>
                    </select>
                    <button class="btn btn-secundario btn-chico">Otorgar</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</section>

<div class="mitades">
    <section class="card">
        <h2>Documentos</h2>
        <?php if (!$documentos): ?>
            <p class="texto-suave">No hay documentos entregados.</p>
        <?php else: ?>
        <ul class="lista-actividad">
            <?php foreach ($documentos as $d): ?>
            <li>
                <?= icono('documento') ?>
                <div style="flex:1">
                    <strong><?= e($d['nombre_documento']) ?></strong> <?= badge($d['estado']) ?><br>
                    <span class="texto-suave chico"><?= e($d['titulo']) ?> · <?= e($d['nombre_fase'] ?? 'Sin fase') ?> · <?= fecha($d['fecha_subida']) ?></span>
                </div>
                <a class="btn btn-chico" href="revision.php?doc=<?= (int)$d['id_documento'] ?>">Revisar</a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>Últimas actividades</h2>
        <?php if (!$actividad): ?>
            <p class="texto-suave">Sin actividad registrada.</p>
        <?php else: ?>
        <ul class="lista-actividad">
            <?php foreach ($actividad as $a): ?>
            <li><span class="punto <?= e($a['tipo']) ?>"></span>
                <div><strong><?= e($a['texto']) ?></strong><?= $a['nombre_fase'] ? ' · ' . e($a['nombre_fase']) : '' ?><br>
                    <span class="texto-suave chico"><?= e($a['titulo']) ?> · <?= fecha_hora($a['fecha']) ?></span></div></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>
</div>

<div class="mitades">
    <section class="card">
        <div class="card-titulo"><h2>Correcciones</h2></div>
        <?php if (!$revisiones): ?>
            <p class="texto-suave">No se han registrado revisiones.</p>
        <?php else: foreach ($revisiones as $r): ?>
            <div class="revision-item">
                <div class="cabecera">
                    <strong><?= e($r['nombre_fase'] ?? 'Sin fase') ?></strong><?= badge($r['estado']) ?>
                </div>
                <?php if ($r['observacion'] !== ''): ?><p><?= e($r['observacion']) ?></p><?php endif; ?>
                <span class="texto-suave chico"><?= e($r['titulo']) ?> · <?= e($r['nombre'] . ' ' . $r['apellido']) ?> · <?= fecha_hora($r['fecha']) ?></span>
            </div>
        <?php endforeach; endif; ?>
    </section>

    <section class="card">
        <h2>Comentarios</h2>
        <?php if (!$comentarios): ?>
            <p class="texto-suave">No hay comentarios.</p>
        <?php else: foreach ($comentarios as $c): ?>
            <div class="comentario-item <?= $c['rol'] === 'estudiante' ? 'de-estudiante' : '' ?>">
                <div class="meta"><?= e($c['nombre'] . ' ' . $c['apellido']) ?> (<?= e($c['rol'] === 'profesor' ? 'docente' : $c['rol']) ?>)
                    · <?= e($c['nombre_fase'] ?? 'General') ?> · <?= fecha_hora($c['fecha']) ?></div>
                <p><?= e($c['comentario']) ?></p>
            </div>
        <?php endforeach; endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
