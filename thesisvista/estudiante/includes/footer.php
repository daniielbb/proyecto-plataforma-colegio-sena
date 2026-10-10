<?php $firma_inicial = firma_estudiante($id_estudiante, $grupo ? $id_grupo : null); ?>
        </main>
        <footer class="pie">THESISVISTA · Panel estudiante · Datos actualizados <?= e(fecha_hora(date('Y-m-d H:i:s'))) ?></footer>
    </div>
</div>

<script>

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
        f.addEventListener('change', function () { editando = true; });
    });
    var vivo = document.getElementById('indicador-vivo');
    var insignia = document.getElementById('insignia-avisos');
    var aviso = document.getElementById('aviso-cambios');
    var auto = document.body.dataset.autoRecargar === '1';

    function revisar() {
        fetch('api/novedades.php', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) {
                if (r.status === 401 || r.status === 403) { location.href = '../index.php'; throw new Error('sesion'); }
                if (!r.ok) throw new Error(r.status);
                return r.json();
            })
            .then(function (d) {
                vivo.classList.remove('desconectado');
                if (insignia) { insignia.textContent = d.avisos_nuevos; insignia.hidden = !d.avisos_nuevos; }
                if (d.firma !== firma) {
                    if (auto && !editando) { location.reload(); return; }
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
