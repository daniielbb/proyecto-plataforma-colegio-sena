# ThesisVista — Panel administrador

Panel para el perfil **administrador**: crear/quitar materias, definir
horarios, y asignar profesores y estudiantes.

**Arquitectura:**
- `*.html` + `css/style.css` → la interfaz visual. HTML/CSS puro, sin PHP.
- `js/*.js` → toda la interacción: llaman a la API con `fetch()` y pintan
  el resultado en el DOM.
- `api/*.php` → la única parte en PHP. No genera HTML, solo recibe
  peticiones y responde JSON (login, listar/crear/eliminar materias,
  horarios, asignaciones).

## 1. Requisitos
- XAMPP, Laragon o similar (Apache + PHP 8 + MariaDB).
- La base `senatv` ya importada desde tu `senatv.sql` original.

## 2. Instalación
1. Copia esta carpeta (`senatv-admin`) dentro de tu carpeta de servidor
   (ej. `C:\xampp\htdocs\senatv-admin` o `htdocs/senatv-admin`).
2. Importa primero tu `senatv.sql` original en phpMyAdmin (si no lo has
   hecho ya).
3. Importa después `sql/schema_update.sql` sobre la misma base `senatv`.
   Esto agrega: `materias`, `horarios`, `materia_profesor`,
   `estudiante_materia`, y dos tablas que quedan listas para cuando
   construyas los perfiles de docente/estudiante: `tareas` y `entregas`.
4. Revisa `config/db.php` y ajusta usuario/contraseña de MySQL si tu
   instalación no usa el típico `root` sin contraseña.
5. Abre `http://localhost/senatv-admin/login.html` (tiene que ser por
   `http://`, no abriendo el archivo directamente con doble clic — si no,
   el `fetch()` a la API no va a poder llamar a los `.php`).

## 3. Ingresar
Usa cualquier usuario de la tabla `usuarios` con `rol = 'administrador'`.
Con los datos del dump original:

- Correo: `admin@thesisvista.com`
- Contraseña: `admin123`

La primera vez que entres con esa contraseña (que en el dump está en
texto plano), el sistema la convierte automáticamente a un hash seguro
en la base de datos.

## 4. Qué hace cada archivo

**Interfaz (HTML/CSS):**
- `login.html`, `dashboard.html`, `materias.html`, `horarios.html`,
  `profesores.html`, `estudiantes.html`.

**Interacción (JS):**
- `js/common.js` — sesión, barra lateral, mensajes, logout. Lo cargan
  todas las páginas.
- `js/login.js`, `js/dashboard.js`, `js/materias.js`, `js/horarios.js`,
  `js/profesores.js`, `js/estudiantes.js` — uno por página.

**API (PHP, solo JSON):**
- `api/bootstrap.php` — sesión, conexión, helpers comunes.
- `api/login.php` / `api/logout.php` / `api/session.php`.
- `api/dashboard.php` — conteos del resumen.
- `api/catalogos.php` — cursos/grupos/profesores/estudiantes/materias
  para llenar los `<select>`.
- `api/materias.php`, `api/horarios.php`, `api/profesores.php`,
  `api/estudiantes.php` — `GET` lista, `POST` crea, `DELETE` elimina.

## 5. Siguientes fases (docente / estudiante)
Las tablas `tareas` y `entregas` ya están creadas para cuando construyas:
- El perfil **docente**: crear tareas por materia, calificar entregas.
- El perfil **estudiante**: ver sus materias (`estudiante_materia`) y su
  profesor (`materia_profesor`), y subir entregas.

La misma arquitectura (HTML/CSS + `js/` + `api/`) se puede extender
agregando, por ejemplo, `profesor-tareas.html` + `js/profesor-tareas.js`
+ `api/tareas.php`, sin tocar lo que ya existe.
