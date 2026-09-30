<?php
require_once __DIR__ . '/../includes/docente_init.php';

$pendientes = correcciones_pendientes($pdo, $doc);

$titulo_pagina = 'Correcciones pendientes';
$pagina_activa = 'correcciones';
require __DIR__ . '/../includes/header.php';
?>

<p class="texto-suave">Grupos con documentos o fases que requieren correcciones. Cuando el grupo corrija, podrás revisar nuevamente.</p>

<?php if (!$pendientes): ?>
    <div class="card vacio">
        <h2>Sin correcciones pendientes</h2>
        <p>Ningún grupo tiene correcciones por resolver en este momento.</p>
    </div>
<?php else: ?>
<div class="rejilla">
    <?php foreach ($pendientes as $p): ?>
    <article class="card grupo-card">
        <div class="grupo-nombre"><?= e($p['nombre_grupo'] ?? 'Sin grupo') ?></div>
        <h3><?= e($p['titulo']) ?></h3>
        <ul class="datos">
            <li><span>Fase</span><strong><?= e($p['nombre_fase'] ?? 'Sin fase') ?></strong></li>
            <li><span><?= $p['origen'] === 'documento' ? 'Documento' : 'Tipo' ?></span>
                <strong><?= $p['origen'] === 'documento' ? e($p['nombre_documento']) : 'Revisión de la fase' ?></strong></li>
            <li><span>Estado</span><strong><?= badge($p['estado']) ?></strong></li>
            <li><span>Última revisión</span><strong><?= fecha($p['ultima_revision']) ?></strong></li>
        </ul>
        <div class="acciones botones">
            <?php if ($p['origen'] === 'documento'): ?>
                <a class="btn" href="revision.php?doc=<?= (int)$p['id_documento'] ?>">Revisar</a>
            <?php elseif ($p['id_fase']): ?>
                <a class="btn" href="revision.php?tesis=<?= (int)$p['id_tesis'] ?>&fase=<?= (int)$p['id_fase'] ?>">Revisar</a>
            <?php endif; ?>
            <?php if ($p['id_fase']): ?>
                <a class="btn btn-secundario" href="fase.php?tesis=<?= (int)$p['id_tesis'] ?>&fase=<?= (int)$p['id_fase'] ?>">Ver fase</a>
            <?php endif; ?>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
