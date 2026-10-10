<?php

?>
<?php if ($errores): ?>
    <div class="alerta alerta-error">
        Revise los siguientes datos:
        <ul><?php foreach ($errores as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= e($accion) ?>" class="formulario">
    <?= campo_csrf() ?>

    <label for="titulo">Título *</label>
    <input type="text" id="titulo" name="titulo" maxlength="255" required value="<?= e($datos['titulo']) ?>">

    <label for="resumen">Resumen *</label>
    <textarea id="resumen" name="resumen" required><?= e($datos['resumen']) ?></textarea>

    <div class="fila">
        <div>
            <label for="id_estudiante">Estudiante *</label>
            <select id="id_estudiante" name="id_estudiante" required>
                <option value="">Seleccione...</option>
                <?php foreach ($estudiantes as $u): ?>
                    <option value="<?= (int) $u['usuario_id'] ?>" <?= (int) $datos['id_estudiante'] === (int) $u['usuario_id'] ? 'selected' : '' ?>>
                        <?= e($u['nombre'] . ' ' . $u['apellido'] . ' (' . $u['correo'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="id_profesor">Docente asignado *</label>
            <select id="id_profesor" name="id_profesor" required>
                <option value="">Seleccione...</option>
                <?php foreach ($profesores as $u): ?>
                    <option value="<?= (int) $u['usuario_id'] ?>" <?= (int) $datos['id_profesor'] === (int) $u['usuario_id'] ? 'selected' : '' ?>>
                        <?= e($u['nombre'] . ' ' . $u['apellido']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="fila">
        <div>
            <label for="id_grupo">Grupo</label>
            <select id="id_grupo" name="id_grupo">
                <option value="">Sin grupo</option>
                <?php foreach ($grupos as $g): ?>
                    <option value="<?= (int) $g['id_grupo'] ?>" <?= (string) $datos['id_grupo'] === (string) $g['id_grupo'] ? 'selected' : '' ?>>
                        <?= e($g['nombre_grupo'] . ' · ' . $g['nombre_curso'] . ' (' . $g['total_estudiantes'] . ' estudiantes)') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="estado">Estado *</label>
            <select id="estado" name="estado" required>
                <?php foreach (ESTADOS_TESIS as $valor): ?>
                    <option value="<?= e($valor) ?>" <?= $datos['estado'] === $valor ? 'selected' : '' ?>><?= e($valor) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="fecha_registro">Fecha de registro *</label>
            <input type="date" id="fecha_registro" name="fecha_registro" required value="<?= e($datos['fecha_registro']) ?>">
        </div>
    </div>

    <label class="casilla">
        <input type="checkbox" name="crear_fases" value="1" <?= !empty($datos['crear_fases']) ? 'checked' : '' ?>>
        <span>Crear el plan de fases del curso del grupo (tabla <code>tesis_fase</code>, estado Pendiente) para las fases que falten.</span>
    </label>
    <p class="ayuda">Si elige un grupo, el estudiante se agrega a ese grupo (sin quitar a los demás integrantes) y el docente al curso cuando aún no lo estén.
        Los integrantes de cada grupo se administran en <a href="grupos.php">Gestión de grupos</a>.</p>

    <div class="botones">
        <button type="submit" class="btn btn-primario"><?= $es_nuevo ? 'Guardar proyecto' : 'Guardar cambios' ?></button>
        <a href="proyectos.php" class="btn btn-secundario">Cancelar</a>
    </div>
</form>
