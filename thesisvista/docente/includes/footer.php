<?php $firma_inicial = firma_cambios($id_docente)['firma']; ?>
        </main>
        <footer class="pie">THESISVISTA · Panel docente · Datos actualizados <?= e(fecha_hora(date('Y-m-d H:i:s'))) ?></footer>
    </div>
</div>

<script>

document.querySelectorAll('[data-abrir]').forEach(function (b) {
    b.addEventListener('click', function () {
        var d = document.getElementById(b.dataset.abrir);
        if (d && d.showModal) d.showModal();
    });
});
document.querySelectorAll('dialog [data-cerrar]').forEach(function (b) {
    b.addEventListener('click', function () { b.closest('dialog').close(); });
});


document.querySelectorAll('form[data-confirmar]').forEach(function (f) {
    f.addEventListener('submit', function (ev) {
        if (!window.confirm(f.dataset.confirmar)) ev.preventDefault();
    });
});


(function () {
    var firma = <?= json_encode($firma_inicial) ?>;
    var editando = false;
    document.querySelectorAll('main form').forEach(function (f) {
        f.addEventListener('input', function () { editando = true; });
    });
    var vivo = document.getElementById('indicador-vivo');
    var insignia = document.getElementById('insignia-revisar');
    var aviso = document.getElementById('aviso-cambios');
    var auto = document.body.dataset.autoRecargar === '1';

    function revisar() {
        fetch('api/novedades.php', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (d) {
                vivo.classList.remove('desconectado');
                if (insignia) { insignia.textContent = d.por_revisar; insignia.hidden = !d.por_revisar; }
                if (d.firma !== firma) {
                    var modalAbierto = document.querySelector('dialog[open]');
                    if (auto && !editando && !modalAbierto) { location.reload(); return; }
                    aviso.hidden = false;
                }
            })
            .catch(function () { vivo.classList.add('desconectado'); });
    }
    setInterval(revisar, 15000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) revisar(); });
})();
</script>
</body>
</html>
