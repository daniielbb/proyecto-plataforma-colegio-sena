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

    <div class="fila">
        <div>
            <label for="nombre">Nombre *</label>
            <input type="text" id="nombre" name="nombre" maxlength="100" required value="<?= e($datos['nombre']) ?>">
        </div>
        <div>
            <label for="apellido">Apellido *</label>
            <input type="text" id="apellido" name="apellido" maxlength="100" required value="<?= e($datos['apellido']) ?>">
        </div>
    </div>

    <div class="fila">
        <div>
            <label for="correo">Correo *</label>
            <input type="email" id="correo" name="correo" maxlength="150" required value="<?= e($datos['correo']) ?>">
        </div>
        <div>
            <label for="contrasena">Contraseña <?= $es_nuevo ? '*' : '' ?></label>
            <input type="password" id="contrasena" name="contrasena" minlength="4" <?= $es_nuevo ? 'required' : '' ?> autocomplete="new-password">
            <?php if (!$es_nuevo): ?>
                <p class="ayuda">Déjela vacía para conservar la contraseña actual.</p>
            <?php else: ?>
                <p class="ayuda">Se guarda cifrada con password_hash().</p>
            <?php endif; ?>
        </div>
    </div>

    <label for="rol">Rol *</label>
    <?php if (!empty($rol_bloqueado)): ?>
        <input type="hidden" name="rol" value="<?= e($datos['rol']) ?>">
        <input type="text" id="rol" value="<?= e(nombre_rol($datos['rol'])) ?>" disabled>
        <p class="ayuda">No puede cambiar su propio rol.</p>
    <?php else: ?>
        <select id="rol" name="rol" required>
            <option value="">Seleccione...</option>
            <?php foreach (ROLES as $valor => $texto): ?>
                <option value="<?= e($valor) ?>" <?= $datos['rol'] === $valor ? 'selected' : '' ?>><?= e($texto) ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>

    <div class="botones">
        <button type="submit" class="btn btn-primario"><?= $es_nuevo ? 'Guardar usuario' : 'Guardar cambios' ?></button>
        <a href="usuarios.php" class="btn btn-secundario">Cancelar</a>
    </div>
</form>
