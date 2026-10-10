<?php

require_once __DIR__ . '/includes/inicio.php';

$cursos  = cursos_docente($id_docente);
$ids_cur = ids_cursos_docente($id_docente);
$id_curso = get_int('curso');
if ($id_curso && !docente_en_curso($id_docente, $id_curso)) $id_curso = 0;
$filtro = $id_curso ? ['id_curso' => $id_curso] : ['cursos' => $ids_cur];

$lista     = incentivos($filtro);
$otorgados = incentivos_otorgados($id_curso ? ['cursos' => [$id_curso]] : ['cursos' => $ids_cur], 30);

$titulo  = 'Incentivos';
$seccion = 'incentivos';
$auto_recargar = true;
require __DIR__ . '/includes/header.php';
?>

<div class="tarjeta-cabecera">
    <form class="filtros" method="get" style="margin:0">
        <select name="curso" onchange="this.form.submit()">
            <option value="">Todos mis cursos</option>
            <?php foreach ($cursos as $c): ?><option value="<?= (int) $c['id_curso'] ?>" <?= $id_curso === (int) $c['id_curso'] ? 'selected' : '' ?>>Curso <?= e($c['nombre_curso']) ?></option><?php endforeach; ?>
        </select>
        <noscript><button class="btn btn-secundario">Filtrar</button></noscript>
    </form>
    <a class="btn btn-primario" href="incentivo_form.php<?= $id_curso ? '?curso=' . $id_curso : '' ?>"><?= icono('mas', 16) ?> Crear incentivo</a>
</div>

<div class="d-columnas">
    <section>
        <?php if (!$lista): ?>
            <div class="tarjeta"><p class="vacio">Aún no hay incentivos. Cree el primero, por ejemplo: <em>🏆 Equipo destacado — completar la fase antes de la fecha límite cumpliendo todos los criterios.</em></p></div>
        <?php else: ?>
            <div class="d-incentivos">
                <?php foreach ($lista as $i): ?>
                    <a class="d-incentivo" href="incentivo.php?id=<?= (int) $i['id_incentivo_grupal'] ?>">
                        <div style="display:flex;gap:12px;align-items:center">
                            <span class="d-medalla"><?= e($i['icono'] ?: '🏆') ?></span>
                            <div><h3><?= e($i['nombre']) ?></h3><?= etiqueta($i['estado']) ?> <?= $i['valor'] ? '<span class="etiqueta etiqueta-cafe">' . e($i['valor']) . '</span>' : '' ?></div>
                        </div>
                        <?php if ($i['descripcion']): ?><p><?= e(mb_strimwidth($i['descripcion'], 0, 120, '…')) ?></p><?php endif; ?>
                        <p><strong>Criterio:</strong> <?= e(mb_strimwidth($i['criterio'], 0, 140, '…')) ?></p>
                        <div class="d-carpeta-datos">
                            <span>Curso <?= e($i['nombre_curso']) ?></span>
                            <?php if ($i['nombre_fase']): ?><span>Fase <?= (int) $i['orden_fase'] ?> · <?= e($i['nombre_fase']) ?></span><?php endif; ?>
                            <?php if ($i['titulo_tesis']): ?><span>Proyecto: <?= e($i['titulo_tesis']) ?></span><?php endif; ?>
                            <span><?= icono('check', 14) ?>Otorgado <?= (int) $i['total_otorgados'] ?> vez/veces</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <section class="tarjeta">
        <h2>Otorgados recientemente</h2>
        <?= lista_otorgados($otorgados) ?>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
