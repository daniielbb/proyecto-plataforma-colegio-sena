<?php

?>
<?php if (!$entregas): ?>
    <p class="vacio">No hay trabajos entregados con estos criterios.</p>
<?php else: ?>
    <div class="tabla-contenedor">
        <table>
            <thead><tr><th>Trabajo</th><th>Estudiante</th><th>Grupo / fase</th><th>Estado</th><th>Enviado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($entregas as $en): ?>
                <tr>
                    <td><a href="archivo.php?entrega=<?= (int) $en['id_entrega'] ?>" target="_blank" rel="noopener"><?= e($en['nombre_original']) ?></a><br>
                        <small><?= e(strtoupper($en['extension'])) ?> · <?= e(tamano_legible((int) $en['tamano'])) ?> · versión <?= (int) $en['version'] ?></small></td>
                    <td><?= e($en['nombre'] . ' ' . $en['apellido']) ?></td>
                    <td><strong><?= e($en['nombre_grupo']) ?></strong><br><small>Curso <?= e($en['nombre_curso']) ?> · Fase <?= (int) $en['orden'] ?> · <?= e($en['nombre_fase']) ?></small></td>
                    <td><?= etiqueta($en['estado']) ?></td>
                    <td><small><?= e(fecha_hora($en['fecha_subida'])) ?>
                        <?php if ($en['fecha_limite'] && substr($en['fecha_subida'], 0, 10) > $en['fecha_limite']): ?><br><span class="d-plazo vencido">fuera de plazo</span><?php endif; ?></small></td>
                    <td><a class="btn btn-primario btn-chico" href="grupo_fase.php?grupo=<?= (int) $en['id_grupo'] ?>&fase=<?= (int) $en['id_fase'] ?>&entrega=<?= (int) $en['id_entrega'] ?>">Revisar</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
