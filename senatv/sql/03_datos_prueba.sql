-- =====================================================================
--  SENATV · Datos de prueba OPCIONALES para el módulo estudiante
--  Ejecutar después de 02_extension_seguimiento.sql.
--  Usa los usuarios, tesis y fases que YA existen en la base original.
--  Puede omitirse en producción.
-- =====================================================================
USE `senatv`;
SET NAMES utf8mb4;

-- Roles dentro del grupo (normalmente los asigna el docente)
UPDATE `estudiante_grupo` SET `rol_grupo` = 'Líder'          WHERE `id_estudiante` = 1 AND `id_grupo` = 1;
UPDATE `estudiante_grupo` SET `rol_grupo` = 'Investigadora'  WHERE `id_estudiante` = 2 AND `id_grupo` = 1;
UPDATE `estudiante_grupo` SET `rol_grupo` = 'Documentador'   WHERE `id_estudiante` = 5 AND `id_grupo` = 1;
UPDATE `estudiante_grupo` SET `rol_grupo` = 'Líder'          WHERE `id_estudiante` = 4 AND `id_grupo` = 2;

-- Ajuste de fecha para que la fase en curso de Mariana (tesis 2) tenga entrega próxima
UPDATE `tesis_fase` SET `fecha_limite` = '2026-10-02',
       `observaciones` = 'Entregar la justificación con impacto social, económico y tecnológico.'
 WHERE `id_tesis` = 2 AND `id_fase` = 2;

-- Documentos existentes: se asocian a su fase
UPDATE `documento` SET `id_fase` = 1, `estado` = 'Aprobado'         WHERE `id_documento` = 2;
UPDATE `documento` SET `id_fase` = 2, `estado` = 'Aprobado'         WHERE `id_documento` = 3;
UPDATE `documento` SET `id_fase` = 1, `estado` = 'Aprobado'         WHERE `id_documento` = 4;

-- Nuevos documentos de ejemplo (tesis 2 = Mariana, tesis 3 = Camila)
INSERT INTO `documento` (`id_documento`,`id_tesis`,`id_fase`,`nombre_documento`,`tipo_documento`,`estado`,`fecha_subida`) VALUES
(10, 2, 1, 'Planteamiento del problema', 'Documento PDF', 'Aprobado',         '2026-08-12'),
(11, 2, 2, 'Justificación v1',           'Documento Word', 'Requiere ajustes', '2026-09-20'),
(12, 2, 2, 'Justificación v2',           'Documento Word', 'En revisión',      '2026-09-26'),
(13, 3, 3, 'Marco teórico',              'Documento PDF', 'Requiere ajustes', '2026-09-18')
ON DUPLICATE KEY UPDATE `id_documento` = `id_documento`;

-- Correcciones formales del docente
INSERT INTO `correcciones` (`id_correccion`,`id_tesis`,`id_fase`,`id_documento`,`id_profesor`,`estado`,`observacion`,`calificacion`,`fecha`) VALUES
(1, 2, 1, 10, 9, 'Aprobada',         'El planteamiento es claro y delimita bien la población objetivo.', 4.5, '2026-08-17 10:15:00'),
(2, 2, 2, 11, 9, 'Requiere ajustes', 'Falta sustentar el impacto económico con datos. Agregue al menos dos fuentes.', 3.2, '2026-09-22 16:40:00'),
(3, 2, 2, 12, 9, 'En revisión',      'Recibida la versión 2. Se revisará antes de la fecha límite.', NULL, '2026-09-27 09:05:00'),
(4, 3, 3, 13, 6, 'Requiere ajustes', 'Amplíe el estado del arte con proyectos de los últimos 5 años y cite en formato APA.', 3.5, '2026-09-25 11:30:00')
ON DUPLICATE KEY UPDATE `id_correccion` = `id_correccion`;

-- Comentarios del docente (id_usuario = profesor). Los comentarios originales
-- con autor estudiante NO se muestran en la interfaz del estudiante.
INSERT INTO `comentarios` (`id_comentario`,`id_tesis`,`id_fase`,`id_documento`,`id_usuario`,`comentario`,`fecha`) VALUES
(10, 2, 1, 10,   9, 'Buen trabajo en la formulación. Mantenga este nivel de detalle en las siguientes fases.', '2026-08-18 08:30:00'),
(11, 2, 2, 11,   9, 'Se debe ampliar la justificación e incluir las fuentes utilizadas.', '2026-09-22 16:45:00'),
(12, 2, NULL, NULL, 9, 'Recuerde revisar la plataforma cada semana para ver las observaciones.', '2026-09-28 07:50:00'),
(13, 3, 3, 13,   6, 'Se debe ampliar la información del marco teórico y agregar las fuentes utilizadas.', '2026-09-25 11:35:00')
ON DUPLICATE KEY UPDATE `id_comentario` = `id_comentario`;

-- Requisitos generales por fase (id_tesis NULL = aplican a todos)
INSERT INTO `requisitos_fase` (`id_requisito`,`id_fase`,`id_tesis`,`descripcion`,`obligatorio`,`orden`,`id_profesor`) VALUES
(1, 1, NULL, 'Describir la problemática en máximo dos páginas.', 1, 1, 6),
(2, 1, NULL, 'Formular la pregunta de investigación.', 1, 2, 6),
(3, 2, NULL, 'Explicar el impacto social, económico y tecnológico.', 1, 1, 6),
(4, 2, NULL, 'Citar al menos dos fuentes confiables.', 1, 2, 6),
(5, 3, NULL, 'Incluir mínimo cinco antecedentes.', 1, 1, 6),
(6, 3, NULL, 'Usar normas APA 7.ª edición.', 1, 2, 6),
(7, 4, NULL, 'Entregar enlace al repositorio con avances documentados.', 1, 1, 6),
(8, 5, NULL, 'Presentación de 15 minutos y demo funcional.', 1, 1, 6),
(9, 2, 2,    'Agregar una tabla con costos estimados del proyecto.', 0, 3, 9)
ON DUPLICATE KEY UPDATE `id_requisito` = `id_requisito`;

-- Estudiante SIN grupo, para probar la pantalla informativa
INSERT IGNORE INTO `usuarios` (`usuario_id`,`nombre`,`apellido`,`correo`,`contrasena`,`rol`) VALUES
(12, 'Valentina', 'Rojas', 'vale@gmail', '1212', 'estudiante');
