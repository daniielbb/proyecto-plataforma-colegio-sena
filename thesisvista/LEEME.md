# THESISVISTA · Interfaz del administrador

PHP 8 + MySQL/MariaDB (PDO), HTML y CSS. Sin frameworks. El único JavaScript es un script pequeño
y opcional en el formulario de grupos (búsqueda de estudiantes); el formulario funciona igual sin él.

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
includes/grupos.php           Consultas de grupos compartidas por admin, docente y estudiante
includes/pendiente.php        Página temporal de módulos en construcción
css/styles.css                Estilos (marrón, beige, crema, blanco)

admin/
  dashboard.php               Panel: totales, accesos y últimos registros
  usuarios.php                Lista + búsqueda + filtro por rol
  crear_usuario.php           INSERT INTO usuarios
  editar_usuario.php          UPDATE usuarios (contraseña opcional, rol)
  cambiar_rol.php             UPDATE usuarios SET rol
  eliminar_usuario.php        Confirmación + DELETE (solo si no tiene registros relacionados)
  grupos.php                  Lista de grupos + búsqueda (por grupo o estudiante) + filtro por curso
  crear_grupo.php             INSERT INTO grupos + un registro en estudiante_grupo por estudiante
  ver_grupo.php               Consulta: estudiantes del grupo, docentes del curso y proyectos
  editar_grupo.php            Cambiar nombre/curso y agregar o quitar estudiantes
  eliminar_grupo.php          Confirmación + borrado en transacción (no borra a los estudiantes)
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
| Grupos | `grupos(id_grupo, id_curso, nombre_grupo, fecha_creacion)` |
| Estudiantes de cada grupo | `estudiante_grupo(id_estudiante, id_grupo, fecha_asignacion)` — **un grupo → muchos estudiantes** |
| Proyectos | `tesis(id_tesis, titulo, resumen, estado, fecha_registro, id_estudiante, id_profesor, id_grupo)` |
| Curso y docentes | `cursos`, `docente_curso` |
| Fases y avance | `fases`, `tesis_fase`, vista `vista_progreso_tesis` |
| Consulta | `documento`, `comentarios` |

## Gestión de grupos

- **Un grupo tiene muchos estudiantes.** Al crear o editar un grupo, el administrador escribe el nombre,
  elige el curso y marca en una lista con casillas **todos** los estudiantes registrados que pertenecen a él.
  La lista es dinámica (sale de `usuarios` con rol `estudiante`), tiene buscador por nombre o correo,
  botones *Seleccionar visibles* / *Quitar todos* y un panel con los estudiantes seleccionados (con × para quitar)
  que se actualiza antes de guardar.
- Cada estudiante seleccionado es una fila en `estudiante_grupo`. Al editar, el sistema compara la selección
  con lo guardado y solo agrega las filas nuevas y borra las que se desmarcaron (todo en una transacción).
- Un estudiante puede estar en más de un grupo; la lista muestra *También en: …* para que el administrador lo vea.
- No se puede quitar del grupo a un estudiante cuyo proyecto (`tesis.id_grupo`) está en ese grupo: su casilla
  aparece bloqueada. Primero hay que cambiar el grupo del proyecto.
- No se puede cambiar el curso ni eliminar un grupo que tiene proyectos vinculados.
- Eliminar un grupo borra sus filas de `estudiante_grupo` y el grupo; las cuentas de los estudiantes se conservan.
- No hay límite de estudiantes por grupo y no se crea un grupo por estudiante.

## Reglas importantes

- **Eliminar usuario:** las claves foráneas no tienen `ON DELETE`, así que solo se puede borrar un usuario
  sin tesis, grupos, cursos, fases, comentarios ni incentivos. La página de confirmación muestra qué lo bloquea.
  (Para borrar un estudiante que está en grupos, primero quítelo de ellos en *Gestión de grupos*.)
- **Cambiar rol:** está bloqueado si el usuario tiene registros con su rol actual
  (por ejemplo, un docente con tesis asignadas). El administrador no puede cambiar su propio rol ni eliminarse.
- **Eliminar proyecto:** en una sola transacción se borran `tesis_fase`, `documento`, `comentarios`
  (y `correcciones`/`requisitos_fase` si existen). Los incentivos del estudiante se conservan con `id_tesis = NULL`.
  Los archivos físicos subidos por el módulo docente no se borran del disco.
- **Grupo de la tesis:** si se elige un grupo, el estudiante se agrega a `estudiante_grupo` (sin tocar a los demás
  integrantes) y el docente a `docente_curso` si aún no están. Así el módulo docente puede ver el proyecto.
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
- **Grupos:** use las funciones de `includes/grupos.php` para que ambos módulos muestren exactamente
  lo que definió el administrador:
  ```php
  require_once __DIR__ . '/../includes/grupos.php';

  // Docente: grupos de sus cursos (docente_curso) y los estudiantes de cada uno
  foreach (grupos_de_docente($usuario['usuario_id']) as $g) {
      $integrantes = estudiantes_de_grupo($g['id_grupo']);
  }

  // Estudiante: sus grupos y sus compañeros
  $mis_grupos = grupos_de_estudiante($usuario['usuario_id']);
  $companeros = estudiantes_de_grupo($mis_grupos[0]['id_grupo']);

  // Validar permisos: ¿este estudiante pertenece a este grupo?
  estudiante_en_grupo($id_estudiante, $id_grupo);
  ```
- Al crear `docente/dashboard.php` o `estudiante/dashboard.php`, el `index.php` de esa carpeta redirige allí solo.
- **Módulo docente de la entrega anterior:** usaba su propio `login.php`, `config/conexion.php` e `includes/auth.php`.
  No copie su `login.php` encima de este. Para unificarlo, sus páginas deben usar `requerir_rol('profesor')`
  y `$_SESSION['usuario_id']` de este login, y su conexión debe usar `conectar()` de `config/database.php`.
