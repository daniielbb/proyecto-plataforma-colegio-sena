<?php

$seleccionados = array_flip(array_map('intval', $datos['estudiantes']));
$total_sel     = 0;
foreach ($estudiantes as $u) if (isset($seleccionados[(int) $u['usuario_id']])) $total_sel++;
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
            <label for="nombre_grupo">Nombre del grupo *</label>
            <input type="text" id="nombre_grupo" name="nombre_grupo" maxlength="100" required
                   placeholder="Ej: 11-01" value="<?= e($datos['nombre_grupo']) ?>">
        </div>
        <div>
            <label for="id_curso">Curso *</label>
            <select id="id_curso" name="id_curso" required>
                <option value="">Seleccione...</option>
                <?php foreach ($cursos as $c): ?>
                    <option value="<?= (int) $c['id_curso'] ?>" <?= (int) $datos['id_curso'] === (int) $c['id_curso'] ? 'selected' : '' ?>>
                        <?= e($c['nombre_curso']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <label for="buscar-estudiante">Seleccionar estudiantes</label>
    <p class="ayuda">Marque todos los estudiantes que pertenecen a este grupo. La lista sale de los estudiantes registrados en la plataforma.</p>

    <div class="selector-estudiantes" data-selector-estudiantes>
        <div class="selector-panel">
            <input type="search" id="buscar-estudiante" class="selector-buscar" placeholder="Buscar estudiante por nombre o correo..."
                   autocomplete="off" data-solo-js hidden>
            <div class="selector-herramientas" data-solo-js hidden>
                <button type="button" class="btn btn-secundario btn-chico" data-marcar-visibles>Seleccionar visibles</button>
                <button type="button" class="btn btn-secundario btn-chico" data-quitar-todos>Quitar todos</button>
                <a href="crear_usuario.php" target="_blank" rel="noopener" class="btn btn-secundario btn-chico">+ Registrar estudiante</a>
            </div>

            <?php if (!$estudiantes): ?>
                <p class="vacio">No hay estudiantes registrados. <a href="crear_usuario.php">Crear usuario</a></p>
            <?php else: ?>
                <ul class="selector-lista">
                    <?php foreach ($estudiantes as $u):
                        $uid       = (int) $u['usuario_id'];
                        $marcado   = isset($seleccionados[$uid]);
                        $bloqueado = isset($bloqueados[$uid]);
                        $nombre    = $u['nombre'] . ' ' . $u['apellido']; ?>
                        <li class="selector-item<?= $marcado ? ' marcado' : '' ?>"
                            data-texto="<?= e($nombre . ' ' . $u['correo']) ?>" data-nombre="<?= e($nombre) ?>"
                            <?= $bloqueado ? 'data-bloqueado' : '' ?>>
                            <label>
                                <?php if ($bloqueado): ?>
                                    <input type="checkbox" checked disabled>
                                    <input type="hidden" name="estudiantes[]" value="<?= $uid ?>">
                                <?php else: ?>
                                    <input type="checkbox" name="estudiantes[]" value="<?= $uid ?>" <?= $marcado ? 'checked' : '' ?>>
                                <?php endif; ?>
                                <span>
                                    <strong><?= e($nombre) ?></strong>
                                    <small><?= e($u['correo']) ?>
                                        <?php if ($u['otros_grupos']): ?> · También en: <?= e($u['otros_grupos']) ?><?php endif; ?></small>
                                    <?php if ($bloqueado): ?>
                                        <small class="selector-nota">Tiene un proyecto en este grupo: no se puede quitar.</small>
                                    <?php endif; ?>
                                </span>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="vacio selector-sin-resultados" hidden>Ningún estudiante coincide con la búsqueda.</p>
            <?php endif; ?>
        </div>

        <div class="selector-panel">
            <h3 class="selector-titulo">Estudiantes seleccionados (<span data-contador><?= $total_sel ?></span>)</h3>
            <ul class="seleccionados-lista">
                <?php foreach ($estudiantes as $u): if (!isset($seleccionados[(int) $u['usuario_id']])) continue; ?>
                    <li><span><?= e($u['nombre'] . ' ' . $u['apellido']) ?></span></li>
                <?php endforeach; ?>
            </ul>
            <p class="seleccionados-vacio" <?= $total_sel > 0 ? 'hidden' : '' ?>>Aún no hay estudiantes seleccionados.</p>
        </div>
    </div>

    <div class="botones">
        <a href="<?= e($cancelar) ?>" class="btn btn-secundario">Cancelar</a>
        <button type="submit" class="btn btn-primario"><?= $es_nuevo ? 'Crear grupo' : 'Guardar cambios' ?></button>
    </div>
</form>

<script>
(function () {
    var caja = document.querySelector('[data-selector-estudiantes]');
    if (!caja) return;

    var buscar   = caja.querySelector('.selector-buscar');
    var items    = Array.prototype.slice.call(caja.querySelectorAll('.selector-item'));
    var lista    = caja.querySelector('.seleccionados-lista');
    var contador = caja.querySelector('[data-contador]');
    var sinRes   = caja.querySelector('.selector-sin-resultados');
    var vacio    = caja.querySelector('.seleccionados-vacio');

    caja.querySelectorAll('[data-solo-js]').forEach(function (el) { el.hidden = false; });

    function casilla(item) { return item.querySelector('input[type=checkbox]'); }
    function normalizar(t) { return t.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase(); }

    function filtrar() {
        var q = normalizar(buscar.value.trim()), visibles = 0;
        items.forEach(function (it) {
            var ok = q === '' || normalizar(it.dataset.texto).indexOf(q) !== -1;
            it.hidden = !ok;
            if (ok) visibles++;
        });
        if (sinRes) sinRes.hidden = visibles > 0;
    }

    function pintar() {
        lista.innerHTML = '';
        var total = 0;
        items.forEach(function (it) {
            var marcado = casilla(it).checked;
            it.classList.toggle('marcado', marcado);
            if (!marcado) return;
            total++;
            var li = document.createElement('li');
            var span = document.createElement('span');
            span.textContent = it.dataset.nombre;
            li.appendChild(span);
            if (!it.hasAttribute('data-bloqueado')) {
                var quitar = document.createElement('button');
                quitar.type = 'button';
                quitar.className = 'btn-quitar';
                quitar.textContent = '×';
                quitar.title = 'Quitar del grupo';
                quitar.setAttribute('aria-label', 'Quitar a ' + it.dataset.nombre);
                quitar.addEventListener('click', function () { casilla(it).checked = false; pintar(); });
                li.appendChild(quitar);
            }
            lista.appendChild(li);
        });
        contador.textContent = total;
        vacio.hidden = total > 0;
    }

    buscar.addEventListener('input', filtrar);
    buscar.addEventListener('keydown', function (ev) { if (ev.key === 'Enter') ev.preventDefault(); });
    items.forEach(function (it) { casilla(it).addEventListener('change', pintar); });

    caja.querySelector('[data-marcar-visibles]').addEventListener('click', function () {
        items.forEach(function (it) { if (!it.hidden) casilla(it).checked = true; });
        pintar();
    });
    caja.querySelector('[data-quitar-todos]').addEventListener('click', function () {
        items.forEach(function (it) { if (!it.hasAttribute('data-bloqueado')) casilla(it).checked = false; });
        pintar();
    });

    pintar();
})();
</script>