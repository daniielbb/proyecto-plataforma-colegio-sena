# SENATV · Plataforma de seguimiento de proyectos académicos

Solo usa **HTML, PHP, CSS y SQL**. No tiene frameworks ni JavaScript: el menú móvil, las notificaciones y las carpetas desplegables funcionan con CSS puro (`checkbox` y `<details>`).
Etapa 1: **interfaz del estudiante** completa. Las carpetas `docente/` y `admin/` ya tienen la validación de rol lista.

## Instalación (XAMPP)

1. Copia la carpeta `senatv/` en `C:\xampp\htdocs\`.
2. En phpMyAdmin crea la base `senatv` (utf8mb4) e importa, **en este orden**:
   1. `sql/01_senatv_original.sql`: tu dump, sin cambios.
   2. `sql/02_extension_seguimiento.sql`: agrega columnas y tablas; no borra nada.
   3. `sql/03_datos_prueba.sql`: datos de prueba (opcional).
3. Revisa las credenciales en `config/config.php` (por defecto `root` sin contraseña).
4. Abre `http://localhost/senatv/`.

| Usuario | Correo | Clave | Qué muestra |
|---|---|---|---|
| Mariana (estudiante) | mari@gmail | 1234 | Proyecto con fases, documentos, correcciones y comentarios |
| Camila (estudiante) | cami@gmail | 4321 | Proyecto con la fase 3 en corrección |
| Alejandro (estudiante) | alejo@gmail | 5678 | Tiene grupo pero todavía no tiene proyecto |
| Valentina (estudiante) | vale@gmail | 1212 | No tiene grupo (pantalla informativa; creada por el archivo 03) |
| Arthur (profesor) | arthur@gmail | 2222 | Panel docente (en construcción) |
| Laura (admin) | admin@thesisvista.com | admin123 | Panel admin (en construcción) |

> La primera vez que cada usuario inicia sesión, su contraseña en texto plano se convierte automáticamente a `password_hash`. La clave con la que ingresa sigue siendo la misma.

## Estructura

```
senatv/
├── index.php            Página de inicio pública
├── login.php / logout.php
├── dashboard.php        Envía a cada usuario a la interfaz de su rol
├── config/              config.php (ajustes) · conexion.php (PDO)
├── includes/            sesion.php (roles/CSRF) · funciones.php · iconos.php
│                        modelo_estudiante.php (todas las consultas) · layout_estudiante.php
├── estudiante/          inicio, proyecto, fases, fase, documentos, correcciones,
│                        correccion, comentarios, grupo, perfil, sin_grupo, descargar
├── docente/  admin/     Accesos preparados, con requiere_rol()
├── css/estilos.css
├── uploads/documentos/  Archivos de documentos (acceso por URL bloqueado)
└── sql/
```

## Seguridad aplicada

- Se exige sesión y rol en cada página: `requiere_rol('estudiante' | 'profesor' | 'administrador')`. Si no corresponde, responde 403.
- El módulo del estudiante es **solo lectura**: `solo_lectura()` rechaza cualquier POST, PUT o DELETE con 405. No existe ningún formulario para comentar, responder, editar ni corregir.
- Cada ID que llega por la URL (fase, corrección, documento) se valida contra la tesis del estudiante en sesión. Si no le pertenece, responde 404.
- Se usa PDO con sentencias preparadas en todas las consultas y `htmlspecialchars` en todo lo que se imprime.
- Protección de la sesión: se regenera el ID al iniciar sesión, la cookie es HttpOnly y SameSite, la sesión expira tras 30 min de inactividad, el login tiene token CSRF y se bloquea temporalmente tras 5 intentos fallidos.
- Solo se muestran los comentarios cuyo autor tiene `rol = 'profesor'`.
- Los archivos `.htaccess` bloquean el acceso por URL a `config/`, `includes/`, `sql/` y `uploads/`.
