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

  document.getElementById('id_profesor').innerHTML = '<option value="">Selecciona...</option>' +
    cat.profesores.map(p => `<option value="${p.usuario_id}">${escapeHtml(p.nombre + ' ' + p.apellido)}</option>`).join('');

  if (!cat.materias.length || !cat.profesores.length) {
    mostrarFlash('Necesitas al menos una materia y un usuario con rol "profesor" para asignar.', true);
  }
}

async function cargarAsignaciones() {
  const asignaciones = await api('api/profesores.php');
  const tbody = document.getElementById('tbody-asignaciones');
  document.getElementById('total-asignaciones').textContent = asignaciones.length;

  if (!asignaciones.length) {
    tbody.innerHTML = '<tr><td colspan="4" style="color:var(--text-muted);">Todavía no hay profesores asignados.</td></tr>';
    return;
  }

  tbody.innerHTML = asignaciones.map(a => `
    <tr>
      <td>${escapeHtml(a.nombre_materia)}</td>
      <td>${escapeHtml(a.nombre_grupo)}</td>
      <td>${escapeHtml(a.nombre + ' ' + a.apellido)}</td>
      <td><button type="button" class="btn-danger" data-id="${a.id_materia_profesor}">Quitar</button></td>
    </tr>
  `).join('');
}

document.addEventListener('DOMContentLoaded', async () => {
  const sesion = await requireAdminSession();
  if (!sesion) return;

  try {
    await cargarCatalogos();
    await cargarAsignaciones();
  } catch (e) {
    mostrarFlash(e.message, true);
  }

  document.getElementById('form-asignacion').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    ocultarFlash();

    const payload = {
      id_materia: document.getElementById('id_materia').value,
      id_grupo: document.getElementById('id_grupo').value,
      id_profesor: document.getElementById('id_profesor').value,
    };

    try {
      await api('api/profesores.php', { method: 'POST', body: JSON.stringify(payload) });
      ev.target.reset();
      mostrarFlash('Profesor asignado.');
      await cargarAsignaciones();
    } catch (e) {
      mostrarFlash(e.message, true);
    }
  });

  document.getElementById('tbody-asignaciones').addEventListener('click', async (ev) => {
    const btn = ev.target.closest('button[data-id]');
    if (!btn) return;
    if (!confirm('¿Quitar esta asignación?')) return;

    try {
      await api('api/profesores.php?id=' + btn.dataset.id, { method: 'DELETE' });
      mostrarFlash('Asignación eliminada.');
      await cargarAsignaciones();
    } catch (e) {
      mostrarFlash(e.message, true);
    }
  });
});
