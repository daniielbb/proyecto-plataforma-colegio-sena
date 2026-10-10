# THESISVISTA · Módulo ESTUDIANTE

Se integra al proyecto actual: **mismo login** (`login.php` ya envía el rol `estudiante` a `estudiante/index.php`),
misma base `senatv`, misma sesión y mismas funciones (`requerir_rol`, CSRF, `e()`, `includes/academico.php`).
No modifica ningún archivo del administrador ni del docente.

## Instalación (3 pasos)

1. Copie dentro de `C:\xampp\htdocs\thesisvista\` (se suman a lo existente):
   ```
   estudiante/                        (reemplaza el estudiante/index.php provisional)
   css/estudiante.css                 (nuevo; styles.css y docente.css no se tocan)
   sql/actualizacion_estudiante.sql   (nuevo)
   ```
2. phpMyAdmin → base `senatv` → **Importar** `sql/actualizacion_estudiante.sql`
   (después de `sql/actualizacion_docente.sql`). No borra datos y se puede repetir.
3. Entre por el login de siempre con un estudiante (ej. `cami@gmail` / `4321`).

## Qué ve el estudiante (solo su grupo)

| Menú | Contenido |
|---|---|
| Inicio | «Hola, nombre», grupo, proyecto, docente, cifras de progreso, línea de fases ✓ ● ○ 🔒, comentarios, entregas, integrantes, próximas fechas, incentivos y avisos |
| Mi grupo | Grupo, curso, proyecto(s), tema (resumen), docente encargado, docentes del curso, integrantes con rol en el proyecto y participación real |
| Progreso del proyecto | Progreso general y por estado (Bloqueada · Disponible · En progreso · Pendiente de revisión · Completada) y una tarjeta por fase |
| Fase | Bloqueada: solo nombre, descripción y condición para desbloquearla. Disponible: instrucciones, guía, fechas, comentarios del docente, incentivos, nota, entregas y **Subir entrega** |
| Mis entregas | Archivos propios (y pestaña «Todo mi grupo»), con versiones y revisiones del docente |
| Comentarios / Incentivos / Calificaciones | Solo lectura, filtrados al grupo y a lo dirigido al estudiante |
| Avisos | Comentarios, revisiones, cambios de estado, fases desbloqueadas, entregas de compañeros, incentivos, fechas límite y cambio de grupo |

## Reglas

- **Estados que ve el estudiante** (calculados de `fase_grupo.estado`, que solo cambia el docente):
  Pendiente → *Disponible* · En progreso / Requiere corrección → *En progreso* · Entregada / En revisión → *Pendiente de revisión* ·
  Aprobada / Completada → *Completada* · y *Bloqueada* según la regla de abajo. Las fases en Borrador no se muestran.
- **Bloqueo** (función `tv_fase_bloqueada()` en la base, la usan PHP y los triggers): una fase que el grupo no ha empezado está
  bloqueada si (a) el docente la cerró, (b) su `fecha_inicio` es futura o (c) `fases.requiere_anterior = 1` (valor por defecto)
  y una fase publicada anterior del grupo aún no está Aprobada/Completada.
  **El docente la desbloquea** aprobando la fase anterior, cambiando la fecha de inicio, o registrando un avance
  («En progreso») del grupo en esa fase desde *Grupo → Fase*. Para que una fase no dependa de la anterior:
  `UPDATE fases SET requiere_anterior = 0 WHERE id_fase = …`.
- **Entregas:** se usa `registrar_entrega()` (misma lógica del docente): cada archivo queda asociado a estudiante, grupo,
  fase y fecha (el proyecto es `tesis.id_grupo`). Subir otro archivo crea una nueva versión que reemplaza la anterior
  (que queda en el historial) mientras la fase no esté En revisión, Aprobada o Cerrada. Máx. 10 MB; formatos según la fase.
  Nadie puede borrar ni cambiar el autor de una entrega.
- **Progreso** = misma fórmula del docente (`progreso_grupo`), siempre desde la base.
- **Notas:** visibles en solo lectura (grupo y propias). Para ocultarlas: `ESTUDIANTE_VE_NOTAS = false` en `estudiante/includes/datos.php`.
- **Tiempo real:** cada página consulta `estudiante/api/novedades.php` cada 15 s; si el docente o el administrador cambió algo
  del grupo (incluido cambiar al estudiante de grupo), la página se actualiza sola.

## Seguridad (no solo visual)

- Cada página llama `requerir_rol('estudiante')`, que relee el rol en la base: un estudiante que abra `admin/` o `docente/`
  es devuelto a su panel (esas páginas ya usan `requerir_rol('administrador'|'profesor')`), y las APIs responden 403.
- El grupo se lee de `estudiante_grupo` en cada petición; un `?grupo=` ajeno se ignora. Fases, entregas, archivos,
  comentarios e incentivos se filtran por ese grupo; los archivos solo se sirven por `estudiante/archivo.php` con esa validación.
- **En la base (triggers):** se rechaza una entrega de quien no sea estudiante del grupo, en una fase bloqueada, cerrada,
  en revisión o aprobada, y cualquier cambio de autor/fase/archivo de una entrega. Los triggers del módulo docente ya impiden
  que un estudiante escriba comentarios, notas, revisiones, fases o incentivos.
- CSRF en todos los POST, consultas preparadas, `htmlspecialchars` en toda salida, `estudiante/includes/` bloqueada con `.htaccess`.

## Base de datos agregada (mínima)

`fases.requiere_anterior` · `aviso_lectura` (hasta cuándo vio sus avisos) · función `tv_fase_bloqueada` ·
triggers `trg_entregas_bi`, `trg_entregas_bu`. Todo lo demás reutiliza las tablas existentes.
