/**
 * Utilidades compartidas por todas las páginas del panel.
 * No depende de nada más: solo fetch() contra /api/*.php
 */

/** Llama a un endpoint de la API y devuelve el JSON ya parseado.
 *  Lanza un Error con el mensaje del servidor si la respuesta no es ok. */
async function api(path, options = {}) {
  const res = await fetch(path, {
    credentials: 'same-origin',
    headers: options.body ? { 'Content-Type': 'application/json' } : {},
    ...options,
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(data.error || 'Ocurrió un error inesperado.');
  }
  return data;
}

/** Exige sesión de administrador; si no hay, redirige al login.
 *  Si la hay, pinta el nombre en la barra lateral. */
async function requireAdminSession() {
  try {
    const sesion = await api('api/session.php');
    if (sesion.rol !== 'administrador') {
      window.location.href = 'login.html';
      return null;
    }
    const who = document.getElementById('who-name');
    if (who) who.textContent = `${sesion.nombre} ${sesion.apellido}`;
    return sesion;
  } catch (e) {
    window.location.href = 'login.html';
    return null;
  }
}

/** Inyecta el HTML de la barra lateral en #sidebar-placeholder, si existe.
 *  Así el menú vive en un solo lugar en vez de repetirse en cada página. */
function renderSidebar() {
  const placeholder = document.getElementById('sidebar-placeholder');
  if (!placeholder) return;
  placeholder.outerHTML = `
    <aside class="sidebar">
      <div class="brand">ThesisVista<span>Panel administrador</span></div>
      <nav class="nav">
        <a href="dashboard.html">Resumen</a>
        <a href="materias.html">Materias</a>
        <a href="horarios.html">Horarios</a>
        <a href="profesores.html">Profesores</a>
        <a href="estudiantes.html">Estudiantes</a>
      </nav>
      <div class="who">
        Conectado como<br>
        <strong id="who-name">—</strong>
        <button type="button" id="btn-logout">Cerrar sesión</button>
      </div>
    </aside>`;
}

/** Marca el link del menú que corresponde a la página actual. */
function marcarNavActiva() {
  const actual = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav a').forEach(a => {
    if (a.getAttribute('href') === actual) a.classList.add('active');
  });
}

/** Muestra un mensaje en el contenedor #flash de la página. */
function mostrarFlash(mensaje, esError = false) {
  const cont = document.getElementById('flash');
  if (!cont) return;
  cont.textContent = mensaje;
  cont.className = 'flash' + (esError ? ' error' : '');
  cont.style.display = 'block';
}

function ocultarFlash() {
  const cont = document.getElementById('flash');
  if (cont) cont.style.display = 'none';
}

function wireLogout() {
  const btn = document.getElementById('btn-logout');
  if (!btn) return;
  btn.addEventListener('click', async () => {
    try { await api('api/logout.php', { method: 'POST' }); } catch (e) {}
    window.location.href = 'login.html';
  });
}

document.addEventListener('DOMContentLoaded', () => {
  renderSidebar();
  marcarNavActiva();
  wireLogout();
});
