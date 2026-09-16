function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

async function cargarCatalogos() {
  const cat = await api('api/catalogos.php');

  document.getElementById('id_estudiante').innerHTML = '<option value="">Selecciona...</option>' +
    cat.estudiantes.map(e => `<option value="${e.usuario_id}">${escapeHtml(e.nombre + ' ' + e.apellido)}</option>`).join('');

  document.getElementById('id_materia').innerHTML = '<option value="">Selecciona...</option>' +
    cat.materias.map(m => `<option value="${m.id_materia}">${escapeHtml(m.nombre_materia)}</option>`).join('');

  if (!cat.materias.length || !cat.estudiantes.length) {
    mostrarFlash('Necesitas al menos una materia y un usuario con rol "estudiante" para inscribir.', true);
  }
}

async function cargarInscripciones() {
  const inscripciones = await api('api/estudiantes.php');
  const tbody = document.getElementById('tbody-inscripciones');
  document.getElementById('total-inscripciones').textContent = inscripciones.length;

  if (!inscripciones.length) {
    tbody.innerHTML = '<tr><td colspan="3" style="color:var(--text-muted);">Todavía no hay estudiantes inscritos.</td></tr>';
    return;
  }

  tbody.innerHTML = inscripciones.map(i => `
    <tr>
      <td>${escapeHtml(i.nombre + ' ' + i.apellido)}</td>
      <td>${escapeHtml(i.nombre_materia)}</td>
      <td><button type="button" class="btn-danger" data-id="${i.id_estudiante_materia}">Quitar</button></td>
    </tr>
  `).join('');
}

document.addEventListener('DOMContentLoaded', async () => {
  const sesion = await requireAdminSession();
  if (!sesion) return;

  try {
    await cargarCatalogos();
    await cargarInscripciones();
  } catch (e) {
    mostrarFlash(e.message, true);
  }

  document.getElementById('form-inscripcion').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    ocultarFlash();

    const payload = {
      id_estudiante: document.getElementById('id_estudiante').value,
      id_materia: document.getElementById('id_materia').value,
    };

    try {
      await api('api/estudiantes.php', { method: 'POST', body: JSON.stringify(payload) });
      ev.target.reset();
      mostrarFlash('Estudiante inscrito.');
      await cargarInscripciones();
    } catch (e) {
      mostrarFlash(e.message, true);
    }
  });

  document.getElementById('tbody-inscripciones').addEventListener('click', async (ev) => {
    const btn = ev.target.closest('button[data-id]');
    if (!btn) return;
    if (!confirm('¿Quitar esta inscripción?')) return;

    try {
      await api('api/estudiantes.php?id=' + btn.dataset.id, { method: 'DELETE' });
      mostrarFlash('Inscripción eliminada.');
      await cargarInscripciones();
    } catch (e) {
      mostrarFlash(e.message, true);
    }
  });
});
