function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

async function cargarCatalogos() {
  const cat = await api('api/catalogos.php');

  document.getElementById('id_materia').innerHTML = '<option value="">Selecciona...</option>' +
    cat.materias.map(m => `<option value="${m.id_materia}">${escapeHtml(m.nombre_materia)}</option>`).join('');

  document.getElementById('id_grupo').innerHTML = '<option value="">Selecciona...</option>' +
    cat.grupos.map(g => `<option value="${g.id_grupo}">${escapeHtml(g.nombre_grupo)}</option>`).join('');

  if (!cat.materias.length) {
    mostrarFlash('Primero crea al menos una materia en la sección "Materias".', true);
  }
}

async function cargarHorarios() {
  const horarios = await api('api/horarios.php');
  const tbody = document.getElementById('tbody-horarios');
  document.getElementById('total-horarios').textContent = horarios.length;

  if (!horarios.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="color:var(--text-muted);">Todavía no hay horarios.</td></tr>';
    return;
  }

  tbody.innerHTML = horarios.map(h => `
    <tr>
      <td>${escapeHtml(h.dia_semana)}</td>
      <td>${h.hora_inicio.slice(0,5)}–${h.hora_fin.slice(0,5)}</td>
      <td>${escapeHtml(h.nombre_materia)}</td>
      <td>${escapeHtml(h.nombre_grupo)}</td>
      <td>${escapeHtml(h.salon ?? '—')}</td>
      <td><button type="button" class="btn-danger" data-id="${h.id_horario}">Quitar</button></td>
    </tr>
  `).join('');
}

document.addEventListener('DOMContentLoaded', async () => {
  const sesion = await requireAdminSession();
  if (!sesion) return;

  try {
    await cargarCatalogos();
    await cargarHorarios();
  } catch (e) {
    mostrarFlash(e.message, true);
  }

  document.getElementById('form-horario').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    ocultarFlash();

    const payload = {
      id_materia: document.getElementById('id_materia').value,
      id_grupo: document.getElementById('id_grupo').value,
      dia_semana: document.getElementById('dia_semana').value,
      hora_inicio: document.getElementById('hora_inicio').value,
      hora_fin: document.getElementById('hora_fin').value,
      salon: document.getElementById('salon').value.trim(),
    };

    try {
      await api('api/horarios.php', { method: 'POST', body: JSON.stringify(payload) });
      ev.target.reset();
      mostrarFlash('Horario creado.');
      await cargarHorarios();
    } catch (e) {
      mostrarFlash(e.message, true);
    }
  });

  document.getElementById('tbody-horarios').addEventListener('click', async (ev) => {
    const btn = ev.target.closest('button[data-id]');
    if (!btn) return;
    if (!confirm('¿Eliminar este horario?')) return;

    try {
      await api('api/horarios.php?id=' + btn.dataset.id, { method: 'DELETE' });
      mostrarFlash('Horario eliminado.');
      await cargarHorarios();
    } catch (e) {
      mostrarFlash(e.message, true);
    }
  });
});
