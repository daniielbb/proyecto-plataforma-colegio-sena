# THESISVISTA · Interfaz del administrador

PHP 8 + MySQL/MariaDB (PDO), HTML y CSS. Sin frameworks y sin JavaScript.

## Instalación (XAMPP)

1. Copie la carpeta `thesisvista` en `C:\xampp\htdocs\`.
2. Importe `senatv.sql` en phpMyAdmin (base `senatv`). **No se requiere ningún cambio de estructura.**
   Las extensiones del módulo docente son opcionales; si están instaladas, el panel también las tiene en cuenta.
3. Revise `config/database.php` (host, base, usuario, contraseña).
4. Abra `http://localhost/thesisvista/` e ingrese con el administrador de la base:
   `admin@thesisvista.com` / `admin123`.

> Las contraseñas de la base original están en texto plano. En el primer inicio de sesión
> de cada usuario, el sistema las convierte automáticamente a `password_hash()`.
> Los usuarios nuevos se guardan cifrados desde el principio.

## Estructura

```
index.php                     Envía al login o al panel del rol
login.php / logout.php        Inicio y cierre de sesión (común a los 3 roles)
config/database.php           Conexión PDO (único archivo con credenciales)
includes/seguridad.php        Sesión, control de rol, CSRF, mensajes, e()
includes/pendiente.php        Página temporal de módulos en construcción
css/styles.css                Estilos (marrón, beige, crema, blanco)

admin/
  dashboard.php               Panel: totales, accesos y últimos registros
  usuarios.php                Lista + búsqueda + filtro por rol
  crear_usuario.php           INSERT INTO usuarios
  editar_usuario.php          UPDATE usuarios (contraseña opcional, rol)
  cambiar_rol.php             UPDATE usuarios SET rol
  eliminar_usuario.php        Confirmación + DELETE (solo si no tiene registros relacionados)
  proyectos.php               Lista + filtros (título/estudiante, estado, docente) + avance
  crear_proyecto.php          INSERT INTO tesis (+ relaciones y fases)
  ver_proyecto.php            Consulta: datos, avance (vista_progreso_tesis), fases, documentos, comentarios
  editar_proyecto.php         UPDATE tesis
  asignar_profesor.php        UPDATE tesis SET id_profesor
  eliminar_proyecto.php       Confirmación + borrado en transacción
  includes/                   Plantilla (header/footer), formularios y funciones del admin

docente/index.php             Entrada del módulo docente (redirige a dashboard.php cuando exista)
estudiante/index.php          Entrada del módulo estudiante (redirige a dashboard.php cuando exista)
```

## Tablas y campos usados

| Función | Tabla / campos |
|---|---|
| Login y usuarios | `usuarios(usuario_id, nombre, apellido, correo, contrasena, rol)` |
| Roles | `usuarios.rol` ENUM: `administrador`, `profesor` (se muestra como *Docente*), `estudiante` |
| Proyectos | `tesis(id_tesis, titulo, resumen, estado, fecha_registro, id_estudiante, id_profesor, id_grupo)` |
| Grupo / curso | `grupos`, `cursos` |
| Relaciones que se mantienen | `estudiante_grupo`, `docente_curso` |
| Fases y avance | `fases`, `tesis_fase`, vista `vista_progreso_tesis` |
| Consulta | `documento`, `comentarios` |

## Reglas importantes

- **Eliminar usuario:** las claves foráneas no tienen `ON DELETE`, así que solo se puede borrar un usuario
  sin tesis, grupos, cursos, fases, comentarios ni incentivos. La página de confirmación muestra qué lo bloquea.
- **Cambiar rol:** está bloqueado si el usuario tiene registros con su rol actual
  (por ejemplo, un docente con tesis asignadas). El administrador no puede cambiar su propio rol ni eliminarse.
- **Eliminar proyecto:** en una sola transacción se borran `tesis_fase`, `documento`, `comentarios`
  (y `correcciones`/`requisitos_fase` si existen). Los incentivos del estudiante se conservan con `id_tesis = NULL`.
  Los archivos físicos subidos por el módulo docente no se borran del disco.
- **Grupo de la tesis:** si se elige un grupo, el estudiante se agrega a `estudiante_grupo` y el docente a
  `docente_curso` si aún no están. Así el módulo docente puede ver el proyecto.
  La casilla *Crear el plan de fases* inserta en `tesis_fase` las fases activas del curso que falten.

## Seguridad

PDO con consultas preparadas · `password_hash()`/`password_verify()` · sesiones con
`session_regenerate_id()` · el rol se vuelve a comprobar en la base de datos en cada página
· token CSRF en todos los formularios POST · `htmlspecialchars()` en todo lo que se muestra
· las carpetas `config/`, `includes/` y `admin/includes/` están bloqueadas con `.htaccess`.

## Para integrar los módulos docente y estudiante

- El login ya envía a cada rol a su carpeta (`PANELES` en `includes/seguridad.php`).
- En cada página nueva:
  ```php
  require_once __DIR__ . '/../includes/seguridad.php';
  $usuario = requerir_rol('profesor');   // o 'estudiante'
  ```
- Al crear `docente/dashboard.php` o `estudiante/dashboard.php`, el `index.php` de esa carpeta redirige allí solo.
- **Módulo docente de la entrega anterior:** usaba su propio `login.php`, `config/conexion.php` e `includes/auth.php`.
  No copie su `login.php` encima de este. Para unificarlo, sus páginas deben usar `requerir_rol('profesor')`
  y `$_SESSION['usuario_id']` de este login, y su conexión debe usar `conectar()` de `config/database.php`.
