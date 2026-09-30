# Thesis Vista · Interfaz del docente

PHP 8 + MySQL/MariaDB (PDO), HTML y CSS. Sin frameworks y sin JavaScript.

## Instalación (XAMPP)

1. Copie la carpeta `thesisvista` en `C:\xampp\htdocs\`.
2. En phpMyAdmin ejecute, en este orden:
   1. `senatv.sql` (base original)
   2. `senatv_extension_seguimiento.sql` (extensión v1)
   3. `sql/senatv_extension_v2_docente.sql` (esta entrega: estado *Corregido* y campo *Recomendación*)
3. Revise `config/conexion.php` (usuario, clave y `BASE_URL`).
4. Abra `http://localhost/thesisvista/` e ingrese con un usuario de rol `profesor`
   (ej: `arthur@gmail` / `2222`). La contraseña se convierte a `password_hash` en el primer ingreso.

## Estructura

```
config/conexion.php          Conexión PDO y constantes
includes/auth.php            Sesión, rol docente, CSRF, mensajes
includes/funciones.php       Permisos, progreso, consultas compartidas
includes/header.php/footer.php  Barra lateral y plantilla
login.php / logout.php
docente/dashboard.php        Inicio con indicadores
docente/grupos.php           Mis grupos (tarjetas)
docente/grupo.php            Detalle del grupo + participación individual
docente/proyectos.php        Lista de proyectos
docente/proyecto.php         Progreso, fases como carpetas, siguiente fase, estado
docente/fase.php             Detalle de fase, requisitos, documentos, historial
docente/revision.php         Registrar / editar revisión
docente/revisiones.php       Documentos por revisar
docente/correcciones.php     Correcciones pendientes
docente/historial.php        Historial de revisiones (tabla)
docente/perfil.php           Datos del docente y cambio de contraseña
docente/ver_documento.php    Descarga protegida de archivos
css/styles.css
uploads/documentos/          Archivos subidos (bloqueado con .htaccess)
```

## Cómo se usan las tablas

| Función | Tabla / campo |
|---|---|
| Grupos del docente | `docente_curso` → `grupos.id_curso` |
| Proyectos | `tesis` (docente asignado = `tesis.id_profesor`) |
| Fases y progreso | `fases` (activas del curso) + `tesis_fase`; progreso = completadas / total |
| Siguiente fase | `tesis_fase`: cierra la actual (Completada) y abre la nueva (En progreso, fecha límite = hoy + `duracion_dias`) |
| Documentos | `documento` (`id_fase`, `estado`, `ruta_archivo`, `fecha_modificacion`) |
| Revisión | `correcciones` (estado, corrección, recomendación, calificación 0–5) |
| Comentario | `comentarios` (misma fecha que la revisión, con `id_usuario` = docente) |
| Requisitos | `requisitos_fase` |
| Participación | `estudiante_grupo.rol_grupo/estado`, autoría de `tesis`, comentarios, `estudiante_incentivo` |

Estados de revisión: Entregado = *Pendiente de revisión*, En revisión, Requiere ajustes = *Requiere correcciones*, Corregido, Aprobado.

## Permisos

- Solo usuarios con rol `profesor` (se verifica en la BD en cada petición).
- Cada grupo, proyecto, fase, documento y revisión se valida contra los cursos del docente; si no corresponde, responde 403.
- Solo se pueden editar las revisiones y requisitos propios.
- No hay opciones de administración (usuarios, cursos, grupos, fases).
- Formularios protegidos con token CSRF; consultas preparadas.

## Para el módulo del estudiante

Debe **leer** (sin formularios de creación/edición): `comentarios` y `correcciones` del docente,
`documento.estado`, `tesis_fase` (fase actual, fechas, observaciones) y `requisitos_fase`.
Cuando el estudiante vuelva a subir un documento corregido debe poner `documento.estado = 'Corregido'`.
