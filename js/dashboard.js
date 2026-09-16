document.addEventListener('DOMContentLoaded', async () => {
  const sesion = await requireAdminSession();
  if (!sesion) return;

  try {
    const stats = await api('api/dashboard.php');
    const etiquetas = {
      materias: 'Materias',
      horarios: 'Horarios',
      profesores: 'Profesores',
      estudiantes: 'Estudiantes',
      grupos: 'Grupos',
    };

    const cont = document.getElementById('stat-row');
    cont.innerHTML = Object.entries(etiquetas).map(([clave, etiqueta]) => `
      <div class="stat">
        <div class="num">${stats[clave] ?? 0}</div>
        <div class="label">${etiqueta}</div>
      </div>
    `).join('');
  } catch (e) {
    mostrarFlash(e.message, true);
  }
});
