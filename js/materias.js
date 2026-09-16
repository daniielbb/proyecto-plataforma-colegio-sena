let materiaEnBorrado = null;

async function cargarCursos() {
  const cat = await api('api/catalogos.php');
  const select = document.getElementById('id_curso');
  select.innerHTML = '<option value="">Selecciona...</option>' +
    cat.cursos.map(c => `<option value="${c.id_curso}">${escapeHtml(c.nombre_curso)}</option>`).join('');
}

async function cargarMaterias() {
  const materias = await api('api/materias.php');
  const tbody = document.getElementById('tbody-materias');
  document.getElementById('total-materias').textContent = materias.length;

  if (!materias.length) {
    tbody.innerHTML = '<tr><td colspan="4" style="color:var(--text-muted);">Todavía no hay materias creadas.</td></tr>';
    return;
  }

  tbody.innerHTML = materias.map(m => `
    <tr>
      <td>${escapeHtml(m.nombre_materia)}</td>
      <td>${escapeHtml(m.nombre_curso)}</td>
      <td>${escapeHtml(m.descripcion ?? '—')}</td>
      <td><button type="button" class="btn-danger" data-id="${m.id_materia}">Quitar</button></td>
    </tr>
  `).join('');
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', async () => {
  const sesion = await requireAdminSession();
  if (!sesion) return;

  try {
    await Promise.all([cargarCursos(), cargarMaterias()]);
  } catch (e) {
    mostrarFlash(e.message, true);
  }

  document.getElementById('form-materia').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    ocultarFlash();

    const payload = {
      nombre_materia: document.getElementById('nombre_materia').value.trim(),
      id_curso: document.getElementById('id_curso').value,
      descripcion: document.getElementById('descripcion').value.trim(),
    };

    try {
      await api('api/materias.php', { method: 'POST', body: JSON.stringify(payload) });
      ev.target.reset();
      mostrarFlash('Materia creada.');
      await cargarMaterias();
    } catch (e) {
      mostrarFlash(e.message, true);
    }
  });

  document.getElementById('tbody-materias').addEventListener('click', async (ev) => {
    const btn = ev.target.closest('button[data-id]');
    if (!btn) return;
    if (!confirm('¿Eliminar esta materia?')) return;

    try {
      await api('api/materias.php?id=' + btn.dataset.id, { method: 'DELETE' });
      mostrarFlash('Materia eliminada.');
      await cargarMaterias();
    } catch (e) {
      mostrarFlash(e.message, true);
    }
  });
});
