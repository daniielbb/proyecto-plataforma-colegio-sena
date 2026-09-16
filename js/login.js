document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('form-login');

  form.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    ocultarFlash();

    const correo = document.getElementById('correo').value.trim();
    const contrasena = document.getElementById('contrasena').value;

    try {
      const resp = await api('api/login.php', {
        method: 'POST',
        body: JSON.stringify({ correo, contrasena }),
      });

      if (resp.rol === 'administrador') {
        window.location.href = 'dashboard.html';
      } else {
        mostrarFlash('Tu perfil (' + resp.rol + ') todavía no tiene un panel en esta versión.', true);
      }
    } catch (e) {
      mostrarFlash(e.message, true);
    }
  });
});
