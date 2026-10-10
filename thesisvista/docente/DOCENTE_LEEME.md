# THESISVISTA · Módulo DOCENTE

Se integra al proyecto actual: mismo login, misma base `senatv`, misma sesión y mismas funciones de
`includes/seguridad.php` (`requerir_rol`, CSRF, `e()`, mensajes). No modifica el panel del administrador.

## Instalación (3 pasos)

1. Copie estas carpetas/archivos dentro de `C:\xampp\htdocs\thesisvista\` (se suman a los existentes):
   ```
   docente/                     (reemplaza el docente/index.php provisional)
   includes/academico.php       (nuevo)
   css/docente.css              (nuevo; styles.css no se toca)
   uploads/ (.htaccess, entregas/, guias/)
   sql/actualizacion_docente.sql (reemplaza la versión anterior; es compatible con ella)
   ```
2. phpMyAdmin → base `senatv` → **Importar** `sql/actualizacion_docente.sql`. No borra datos y se puede repetir.
3. Entre por el login de siempre con un usuario de rol `profesor` (ej. `arthur@gmail` / `2222`).
   El login ya envía ese rol a `docente/index.php`.

## Qué puede hacer el docente

| Menú | Función |
|---|---|
| Inicio | Bienvenida, proyectos activos, cursos, grupos, fases en revisión, trabajos por revisar, incentivos, progreso general, actividad reciente y próximas fechas |
| Mis proyectos | Proyectos (`tesis`) que dirige y los de sus cursos. Cada uno: información, fases, curso y grupo, avances, trabajos, calificaciones, comentarios, incentivos |
| Cursos y grupos | Curso → grupos (carpetas) → grupo (integrantes, proyecto, fase actual, línea de fases ✓ ● ○, trabajos, notas, comentarios, incentivos) |
| Fases | Carpetas por curso: crear, editar, ordenar (↑ ↓), cambiar estado, eliminar. Nombre, descripción, objetivo, instrucciones, fechas, duración, peso, evidencias, requisitos, formato, guía, criterios, grupos e incentivos |
| Grupo → Fase | La pantalla de revisión: versiones entregadas, vista previa del archivo, estado (En revisión / Requiere corrección / Aprobada / Completada / avance parcial), calificación por criterios o directa, retroalimentación, incentivos |
| Avances | Matriz grupos × fases con barras y progreso general |
| Trabajos | Por revisar · todos · pendientes por entregar, con filtros |
| Calificaciones / Comentarios / Incentivos / Perfil | Consultas, edición de lo propio, cambio de contraseña |

## Reglas

- **Progreso de una fase** = 100 % si está Aprobada/Completada; si no, el avance registrado en `fase_grupo.porcentaje_avance`.
  **Progreso general** = promedio de las fases del grupo (ponderado por `fases.peso` si todas tienen peso). Siempre se calcula desde la base.
- **Estados del grupo en la fase:** Pendiente · En progreso · Entregada · En revisión · Requiere corrección · Aprobada · Completada.
  Cada cambio actualiza también `tesis_fase`, así el administrador ve el mismo avance en "Consultar proyecto".
- **Notas** de 0.0 a 5.0 (aprobatoria 3.0, constantes en `includes/academico.php`). Por grupo o estudiante, por fase o por trabajo.
  Solo quien la asignó la edita; cada cambio queda en `calificaciones_historial`.
- **Fases:** las edita cualquier docente del curso; solo su creador las elimina y solo si no tienen entregas, notas ni comentarios.
  "Borrador" = los estudiantes no la ven.
- **Tiempo real:** cada página consulta `docente/api/novedades.php` cada 15 s. Si un estudiante o docente cambió algo en sus cursos,
  la página se actualiza sola (o avisa si usted está escribiendo en un formulario).

## Seguridad (no solo visual)

- Cada página llama `requerir_rol('profesor')`, que relee el rol en la base: un estudiante o un administrador que abra `docente/`
  es enviado a su propio panel; el docente que abra `admin/` es enviado a `docente/`.
- Cada id de la URL o del formulario se valida contra `docente_curso` (el docente solo ve sus cursos, grupos, fases, entregas y proyectos).
- CSRF en todos los POST, consultas preparadas, `htmlspecialchars` en toda salida, archivos solo por `docente/archivo.php`.
- **En la base de datos (triggers):** se rechaza cualquier comentario, nota, revisión, fase o incentivo cuyo autor no sea un docente
  asignado al curso del grupo, el cambio de autor de un comentario o nota, y notas fuera de 0–5.

## Para el módulo ESTUDIANTE (pendiente)

`includes/academico.php` ya tiene la lógica compartida. Para que el estudiante entregue:
`registrar_entrega($id_estudiante, $id_grupo, $id_fase, $_FILES['archivo'], $comentario)` → crea la versión, deja la fase
"Entregada" (o "Corregido" si se pidió corrección) y el docente la ve al instante. El estudiante solo debe **leer**
`retroalimentacion`, `calificaciones` e `incentivo_otorgado` de su grupo (los triggers le impiden escribir en ellas).

## Tablas nuevas o ampliadas

`fases` (+objetivo, instrucciones, evidencias, requisitos_siguiente, tipo_entrega, archivo guía, fecha_inicio, fecha_limite, peso, estado) ·
`criterios_fase` · `fase_grupo` (+estado, porcentaje_avance, fechas) · `entregas` · `revisiones_entrega` · `retroalimentacion` ·
`calificaciones` · `calificacion_criterio` · `calificaciones_historial` · `incentivos_grupales` (+id_tesis, icono) · `incentivo_otorgado` ·
`cursos.ficha` (la usaba ya el panel del administrador y no existía en el volcado).
