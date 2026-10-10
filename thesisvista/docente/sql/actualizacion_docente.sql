-- =====================================================================
--  THESISVISTA - Actualización de la base `senatv` para el MÓDULO DOCENTE
--
--  Cómo usarla: phpMyAdmin > base senatv > Importar este archivo.
--  - No borra ni cambia datos existentes.
--  - Se puede ejecutar varias veces (todo usa IF NOT EXISTS / DROP TRIGGER IF EXISTS).
--  - Requiere MariaDB 10.3+ (la de XAMPP).
--
--  Se REUTILIZA de la base original:
--    usuarios (rol 'profesor' = docente), cursos, grupos, estudiante_grupo,
--    docente_curso, tesis (= proyecto), fases, tesis_fase (avance que ve el admin).
--
--  Se AGREGA (compatible con lo anterior):
--    fases ............... columnas: objetivo, instrucciones, fechas, peso, estado, evidencias...
--    criterios_fase ...... criterios de evaluación de cada fase
--    fase_grupo .......... qué grupos tienen cada fase + estado y avance del grupo en la fase
--    entregas ............ trabajos / evidencias que sube el estudiante (con versiones)
--    revisiones_entrega .. cambios de estado que hace el docente sobre una entrega
--    retroalimentacion ... comentarios del docente (proyecto, curso, grupo, estudiante, fase, trabajo)
--    calificaciones ...... notas (por fase, trabajo, estudiante o grupo) + detalle por criterio + historial
--    incentivos_grupales . incentivos que define el docente
--    incentivo_otorgado .. qué grupo o estudiante obtuvo cada incentivo
--    TRIGGERS ............ la base rechaza comentarios, notas, revisiones e incentivos
--                          que no vengan de un docente asignado al curso.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 0. `cursos.ficha` (la usa el panel del administrador; si ya existe no pasa nada)
-- ---------------------------------------------------------------------
ALTER TABLE `cursos`
  ADD COLUMN IF NOT EXISTS `ficha` VARCHAR(50) NULL AFTER `nombre_curso`;

-- ---------------------------------------------------------------------
-- 1. Columnas nuevas en `fases`
-- ---------------------------------------------------------------------
ALTER TABLE `fases`
  ADD COLUMN IF NOT EXISTS `objetivo`             TEXT NULL COMMENT 'Objetivo de la fase' AFTER `descripcion`,
  ADD COLUMN IF NOT EXISTS `instrucciones`        TEXT NULL COMMENT 'Instrucciones para el estudiante' AFTER `ejemplo`,
  ADD COLUMN IF NOT EXISTS `evidencias`           TEXT NULL COMMENT 'Trabajos / evidencias requeridas (una por línea)' AFTER `instrucciones`,
  ADD COLUMN IF NOT EXISTS `requisitos_siguiente` TEXT NULL COMMENT 'Requisitos para pasar a la siguiente fase' AFTER `evidencias`,
  ADD COLUMN IF NOT EXISTS `tipo_entrega`         VARCHAR(30) NOT NULL DEFAULT 'cualquiera' COMMENT 'pdf, documento, presentacion, hoja, imagen, comprimido, cualquiera' AFTER `requisitos_siguiente`,
  ADD COLUMN IF NOT EXISTS `archivo_guia`         VARCHAR(255) NULL COMMENT 'Nombre interno del archivo guía (uploads/guias)' AFTER `tipo_entrega`,
  ADD COLUMN IF NOT EXISTS `archivo_guia_nombre`  VARCHAR(255) NULL COMMENT 'Nombre original del archivo guía' AFTER `archivo_guia`,
  ADD COLUMN IF NOT EXISTS `fecha_inicio`         DATE NULL AFTER `duracion_dias`,
  ADD COLUMN IF NOT EXISTS `fecha_limite`         DATE NULL AFTER `fecha_inicio`,
  ADD COLUMN IF NOT EXISTS `peso`                 DECIMAL(5,2) NULL COMMENT 'Porcentaje de la fase dentro del proyecto' AFTER `fecha_limite`,
  ADD COLUMN IF NOT EXISTS `estado`               ENUM('Borrador','Publicada','Cerrada') NOT NULL DEFAULT 'Publicada' COMMENT 'Borrador: no la ven los estudiantes' AFTER `activa`;

-- ---------------------------------------------------------------------
-- 2. Criterios de evaluación de cada fase
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `criterios_fase` (
  `id_criterio` int(11) NOT NULL AUTO_INCREMENT,
  `id_fase` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL COMMENT 'Porcentaje dentro de la nota de la fase',
  `orden` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_criterio`),
  KEY `f_criterio_fase` (`id_fase`),
  CONSTRAINT `f_criterio_fase` FOREIGN KEY (`id_fase`) REFERENCES `fases` (`id_fase`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 3. Fase <-> grupo, con el estado y el avance del grupo en esa fase
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fase_grupo` (
  `id_fase_grupo` int(11) NOT NULL AUTO_INCREMENT,
  `id_fase` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `fecha_asignacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_fase_grupo`),
  UNIQUE KEY `uniq_fase_grupo` (`id_fase`, `id_grupo`),
  KEY `f_fasegrupo_grupo` (`id_grupo`),
  CONSTRAINT `f_fasegrupo_fase`  FOREIGN KEY (`id_fase`)  REFERENCES `fases` (`id_fase`),
  CONSTRAINT `f_fasegrupo_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `fase_grupo`
  ADD COLUMN IF NOT EXISTS `estado` ENUM('Pendiente','En progreso','Entregada','En revisión','Requiere corrección','Aprobada','Completada')
      NOT NULL DEFAULT 'Pendiente' AFTER `id_grupo`,
  ADD COLUMN IF NOT EXISTS `porcentaje_avance` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0-100, avance registrado del grupo en la fase' AFTER `estado`,
  ADD COLUMN IF NOT EXISTS `fecha_entrega` datetime NULL AFTER `fecha_asignacion`,
  ADD COLUMN IF NOT EXISTS `fecha_aprobacion` datetime NULL AFTER `fecha_entrega`,
  ADD COLUMN IF NOT EXISTS `id_profesor_actualiza` int(11) NULL AFTER `fecha_aprobacion`,
  ADD COLUMN IF NOT EXISTS `fecha_actualizacion` datetime NULL AFTER `id_profesor_actualiza`;

-- Las fases que ya existían se asignan a todos los grupos de su curso
-- (solo las que aún no tienen ningún grupo).
INSERT INTO `fase_grupo` (`id_fase`, `id_grupo`)
SELECT f.`id_fase`, g.`id_grupo`
FROM `fases` f
JOIN `grupos` g ON g.`id_curso` = f.`id_curso`
WHERE NOT EXISTS (SELECT 1 FROM `fase_grupo` x WHERE x.`id_fase` = f.`id_fase`);

-- Estado inicial tomado de los datos reales de `tesis_fase` (solo la primera vez).
UPDATE `fase_grupo` fg
JOIN (
    SELECT t.`id_grupo`, tf.`id_fase`,
           MAX(CASE tf.`estado` WHEN 'Completada' THEN 2 WHEN 'En progreso' THEN 1 ELSE 0 END) AS nivel,
           MAX(tf.`fecha_completada`) AS completada
    FROM `tesis_fase` tf JOIN `tesis` t ON t.`id_tesis` = tf.`id_tesis`
    WHERE t.`id_grupo` IS NOT NULL
    GROUP BY t.`id_grupo`, tf.`id_fase`
) x ON x.`id_grupo` = fg.`id_grupo` AND x.`id_fase` = fg.`id_fase`
SET fg.`estado`            = IF(x.nivel = 2, 'Completada', 'En progreso'),
    fg.`porcentaje_avance` = IF(x.nivel = 2, 100, 0),
    fg.`fecha_aprobacion`  = IF(x.nivel = 2, x.completada, NULL),
    fg.`fecha_actualizacion` = NOW()
WHERE x.nivel > 0 AND fg.`fecha_actualizacion` IS NULL AND fg.`estado` = 'Pendiente';

-- ---------------------------------------------------------------------
-- 4. Entregas del estudiante (cada subida es una versión; las anteriores se conservan)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `entregas` (
  `id_entrega` int(11) NOT NULL AUTO_INCREMENT,
  `id_fase` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `archivo_ruta` varchar(255) NOT NULL COMMENT 'Nombre interno en uploads/entregas',
  `nombre_original` varchar(255) NOT NULL,
  `extension` varchar(10) NOT NULL,
  `tamano` int(11) NOT NULL DEFAULT 0 COMMENT 'Bytes',
  `comentario_estudiante` text DEFAULT NULL,
  `estado` enum('Entregado','En revisión','Requiere corrección','Corregido','Aprobado') NOT NULL DEFAULT 'Entregado',
  `fecha_subida` datetime NOT NULL DEFAULT current_timestamp(),
  `id_profesor_revisor` int(11) DEFAULT NULL,
  `fecha_revision` datetime DEFAULT NULL,
  PRIMARY KEY (`id_entrega`),
  UNIQUE KEY `uniq_entrega_version` (`id_fase`, `id_grupo`, `id_estudiante`, `version`),
  KEY `f_entregas_grupo` (`id_grupo`),
  KEY `f_entregas_estudiante` (`id_estudiante`),
  KEY `f_entregas_revisor` (`id_profesor_revisor`),
  CONSTRAINT `f_entregas_fase`       FOREIGN KEY (`id_fase`)             REFERENCES `fases` (`id_fase`),
  CONSTRAINT `f_entregas_grupo`      FOREIGN KEY (`id_grupo`)            REFERENCES `grupos` (`id_grupo`),
  CONSTRAINT `f_entregas_estudiante` FOREIGN KEY (`id_estudiante`)       REFERENCES `usuarios` (`usuario_id`),
  CONSTRAINT `f_entregas_revisor`    FOREIGN KEY (`id_profesor_revisor`) REFERENCES `usuarios` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 5. Revisiones: cambio de estado que el docente asigna a una entrega
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `revisiones_entrega` (
  `id_revision` int(11) NOT NULL AUTO_INCREMENT,
  `id_entrega` int(11) NOT NULL,
  `id_profesor` int(11) NOT NULL,
  `estado` enum('Entregado','En revisión','Requiere corrección','Corregido','Aprobado') NOT NULL,
  `comentario` text DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_revision`),
  KEY `f_revisiones_entrega` (`id_entrega`),
  KEY `f_revisiones_profesor` (`id_profesor`),
  CONSTRAINT `f_revisiones_entrega`  FOREIGN KEY (`id_entrega`)  REFERENCES `entregas` (`id_entrega`),
  CONSTRAINT `f_revisiones_profesor` FOREIGN KEY (`id_profesor`) REFERENCES `usuarios` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 6. Retroalimentación del docente (SOLO docentes pueden escribir aquí)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `retroalimentacion` (
  `id_retro` int(11) NOT NULL AUTO_INCREMENT,
  `id_profesor` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `id_fase` int(11) DEFAULT NULL,
  `id_tesis` int(11) DEFAULT NULL COMMENT 'Proyecto',
  `id_estudiante` int(11) DEFAULT NULL COMMENT 'Si el comentario es para un estudiante',
  `id_entrega` int(11) DEFAULT NULL COMMENT 'Trabajo comentado',
  `tipo` enum('Comentario general','Observación','Corrección','Recomendación') NOT NULL DEFAULT 'Comentario general',
  `comentario` text NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_edicion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_retro`),
  KEY `f_retro_profesor` (`id_profesor`),
  KEY `f_retro_grupo_fase` (`id_grupo`, `id_fase`),
  KEY `f_retro_curso` (`id_curso`),
  KEY `f_retro_fase` (`id_fase`),
  KEY `f_retro_tesis` (`id_tesis`),
  KEY `f_retro_estudiante` (`id_estudiante`),
  KEY `f_retro_entrega` (`id_entrega`),
  CONSTRAINT `f_retro_profesor`   FOREIGN KEY (`id_profesor`)   REFERENCES `usuarios` (`usuario_id`),
  CONSTRAINT `f_retro_curso`      FOREIGN KEY (`id_curso`)      REFERENCES `cursos` (`id_curso`),
  CONSTRAINT `f_retro_grupo`      FOREIGN KEY (`id_grupo`)      REFERENCES `grupos` (`id_grupo`),
  CONSTRAINT `f_retro_fase`       FOREIGN KEY (`id_fase`)       REFERENCES `fases` (`id_fase`),
  CONSTRAINT `f_retro_tesis`      FOREIGN KEY (`id_tesis`)      REFERENCES `tesis` (`id_tesis`),
  CONSTRAINT `f_retro_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `usuarios` (`usuario_id`),
  CONSTRAINT `f_retro_entrega`    FOREIGN KEY (`id_entrega`)    REFERENCES `entregas` (`id_entrega`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 7. Calificaciones (escala 0.0 - 5.0)
--    id_estudiante NULL = nota del grupo · id_entrega NULL = nota de la fase
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `calificaciones` (
  `id_calificacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_profesor` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `id_fase` int(11) NOT NULL,
  `id_tesis` int(11) DEFAULT NULL,
  `id_estudiante` int(11) DEFAULT NULL,
  `id_entrega` int(11) DEFAULT NULL,
  `nota` decimal(3,1) NOT NULL,
  `observacion` text DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_calificacion`),
  KEY `f_calif_grupo_fase` (`id_grupo`, `id_fase`),
  KEY `f_calif_profesor` (`id_profesor`),
  KEY `f_calif_curso` (`id_curso`),
  KEY `f_calif_fase` (`id_fase`),
  KEY `f_calif_tesis` (`id_tesis`),
  KEY `f_calif_estudiante` (`id_estudiante`),
  KEY `f_calif_entrega` (`id_entrega`),
  CONSTRAINT `f_calif_profesor`   FOREIGN KEY (`id_profesor`)   REFERENCES `usuarios` (`usuario_id`),
  CONSTRAINT `f_calif_curso`      FOREIGN KEY (`id_curso`)      REFERENCES `cursos` (`id_curso`),
  CONSTRAINT `f_calif_grupo`      FOREIGN KEY (`id_grupo`)      REFERENCES `grupos` (`id_grupo`),
  CONSTRAINT `f_calif_fase`       FOREIGN KEY (`id_fase`)       REFERENCES `fases` (`id_fase`),
  CONSTRAINT `f_calif_tesis`      FOREIGN KEY (`id_tesis`)      REFERENCES `tesis` (`id_tesis`),
  CONSTRAINT `f_calif_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `usuarios` (`usuario_id`),
  CONSTRAINT `f_calif_entrega`    FOREIGN KEY (`id_entrega`)    REFERENCES `entregas` (`id_entrega`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `calificacion_criterio` (
  `id_calificacion_criterio` int(11) NOT NULL AUTO_INCREMENT,
  `id_calificacion` int(11) NOT NULL,
  `id_criterio` int(11) NOT NULL,
  `nota` decimal(3,1) NOT NULL,
  PRIMARY KEY (`id_calificacion_criterio`),
  UNIQUE KEY `uniq_calif_criterio` (`id_calificacion`, `id_criterio`),
  KEY `f_califcrit_criterio` (`id_criterio`),
  CONSTRAINT `f_califcrit_calif`    FOREIGN KEY (`id_calificacion`) REFERENCES `calificaciones` (`id_calificacion`) ON DELETE CASCADE,
  CONSTRAINT `f_califcrit_criterio` FOREIGN KEY (`id_criterio`)     REFERENCES `criterios_fase` (`id_criterio`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `calificaciones_historial` (
  `id_historial` int(11) NOT NULL AUTO_INCREMENT,
  `id_calificacion` int(11) NOT NULL,
  `nota_anterior` decimal(3,1) DEFAULT NULL,
  `nota_nueva` decimal(3,1) NOT NULL,
  `observacion` text DEFAULT NULL,
  `id_profesor` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_historial`),
  KEY `f_califhist_calif` (`id_calificacion`),
  KEY `f_califhist_profesor` (`id_profesor`),
  CONSTRAINT `f_califhist_calif`    FOREIGN KEY (`id_calificacion`) REFERENCES `calificaciones` (`id_calificacion`) ON DELETE CASCADE,
  CONSTRAINT `f_califhist_profesor` FOREIGN KEY (`id_profesor`)     REFERENCES `usuarios` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 8. Incentivos del docente (la tabla `incentivos` original —insignias— no se toca)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `incentivos_grupales` (
  `id_incentivo_grupal` int(11) NOT NULL AUTO_INCREMENT,
  `id_profesor` int(11) NOT NULL COMMENT 'Docente que lo creó',
  `id_curso` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo_criterio` varchar(30) NOT NULL DEFAULT 'personalizado',
  `criterio` text NOT NULL COMMENT 'Lo que hay que cumplir para obtenerlo',
  `id_fase` int(11) DEFAULT NULL,
  `meta_porcentaje` decimal(5,1) DEFAULT NULL,
  `valor` varchar(100) DEFAULT NULL COMMENT 'Puntos, nivel o premio',
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('Activo','Inactivo','Finalizado') NOT NULL DEFAULT 'Activo',
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_incentivo_grupal`),
  KEY `f_incgrupal_profesor` (`id_profesor`),
  KEY `f_incgrupal_curso` (`id_curso`),
  KEY `f_incgrupal_fase` (`id_fase`),
  CONSTRAINT `f_incgrupal_profesor` FOREIGN KEY (`id_profesor`) REFERENCES `usuarios` (`usuario_id`),
  CONSTRAINT `f_incgrupal_curso`    FOREIGN KEY (`id_curso`)    REFERENCES `cursos` (`id_curso`),
  CONSTRAINT `f_incgrupal_fase`     FOREIGN KEY (`id_fase`)     REFERENCES `fases` (`id_fase`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `incentivos_grupales`
  ADD COLUMN IF NOT EXISTS `id_tesis` int(11) NULL COMMENT 'Proyecto asociado (opcional)' AFTER `id_fase`,
  ADD COLUMN IF NOT EXISTS `icono` varchar(20) NULL AFTER `nombre`;

CREATE TABLE IF NOT EXISTS `incentivo_otorgado` (
  `id_otorgado` int(11) NOT NULL AUTO_INCREMENT,
  `id_incentivo_grupal` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `id_estudiante` int(11) DEFAULT NULL COMMENT 'NULL = lo obtuvo todo el grupo',
  `id_profesor` int(11) NOT NULL,
  `observacion` text DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_otorgado`),
  KEY `f_incotor_incentivo` (`id_incentivo_grupal`),
  KEY `f_incotor_grupo` (`id_grupo`),
  KEY `f_incotor_estudiante` (`id_estudiante`),
  KEY `f_incotor_profesor` (`id_profesor`),
  CONSTRAINT `f_incotor_incentivo`  FOREIGN KEY (`id_incentivo_grupal`) REFERENCES `incentivos_grupales` (`id_incentivo_grupal`),
  CONSTRAINT `f_incotor_grupo`      FOREIGN KEY (`id_grupo`)            REFERENCES `grupos` (`id_grupo`),
  CONSTRAINT `f_incotor_estudiante` FOREIGN KEY (`id_estudiante`)       REFERENCES `usuarios` (`usuario_id`),
  CONSTRAINT `f_incotor_profesor`   FOREIGN KEY (`id_profesor`)         REFERENCES `usuarios` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 9. PERMISOS EN LA BASE DE DATOS (triggers)
--    Aunque alguien salte la aplicación, la base rechaza:
--    - comentarios, notas, revisiones e incentivos de un usuario que no sea docente
--    - que el docente no esté asignado al curso del grupo (docente_curso)
--    - cambiar el autor de un comentario o de una nota
--    - notas fuera de 0.0 - 5.0
-- =====================================================================

DELIMITER $$

DROP FUNCTION IF EXISTS `tv_docente_del_grupo`$$
CREATE FUNCTION `tv_docente_del_grupo`(p_profesor INT, p_grupo INT) RETURNS TINYINT(1)
READS SQL DATA
BEGIN
    DECLARE v INT DEFAULT 0;
    SELECT COUNT(*) INTO v
    FROM usuarios u
    JOIN docente_curso dc ON dc.id_profesor = u.usuario_id
    JOIN grupos g ON g.id_curso = dc.id_curso
    WHERE u.usuario_id = p_profesor AND u.rol = 'profesor' AND g.id_grupo = p_grupo;
    RETURN v > 0;
END$$

DROP FUNCTION IF EXISTS `tv_docente_del_curso`$$
CREATE FUNCTION `tv_docente_del_curso`(p_profesor INT, p_curso INT) RETURNS TINYINT(1)
READS SQL DATA
BEGIN
    DECLARE v INT DEFAULT 0;
    SELECT COUNT(*) INTO v
    FROM usuarios u JOIN docente_curso dc ON dc.id_profesor = u.usuario_id
    WHERE u.usuario_id = p_profesor AND u.rol = 'profesor' AND dc.id_curso = p_curso;
    RETURN v > 0;
END$$

-- Comentarios del docente
DROP TRIGGER IF EXISTS `trg_retro_bi`$$
CREATE TRIGGER `trg_retro_bi` BEFORE INSERT ON `retroalimentacion` FOR EACH ROW
BEGIN
    IF NOT tv_docente_del_grupo(NEW.id_profesor, NEW.id_grupo) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo un docente asignado al curso del grupo puede comentar.';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_retro_bu`$$
CREATE TRIGGER `trg_retro_bu` BEFORE UPDATE ON `retroalimentacion` FOR EACH ROW
BEGIN
    IF NEW.id_profesor <> OLD.id_profesor THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede cambiar el autor de un comentario.';
    END IF;
END$$

-- Calificaciones
DROP TRIGGER IF EXISTS `trg_calif_bi`$$
CREATE TRIGGER `trg_calif_bi` BEFORE INSERT ON `calificaciones` FOR EACH ROW
BEGIN
    IF NOT tv_docente_del_grupo(NEW.id_profesor, NEW.id_grupo) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo un docente asignado al curso del grupo puede calificar.';
    END IF;
    IF NEW.nota < 0 OR NEW.nota > 5 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La nota debe estar entre 0.0 y 5.0.';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_calif_bu`$$
CREATE TRIGGER `trg_calif_bu` BEFORE UPDATE ON `calificaciones` FOR EACH ROW
BEGIN
    IF NEW.id_profesor <> OLD.id_profesor THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo el docente que asignó la nota puede modificarla.';
    END IF;
    IF NEW.nota < 0 OR NEW.nota > 5 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La nota debe estar entre 0.0 y 5.0.';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_califcrit_bi`$$
CREATE TRIGGER `trg_califcrit_bi` BEFORE INSERT ON `calificacion_criterio` FOR EACH ROW
BEGIN
    IF NEW.nota < 0 OR NEW.nota > 5 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La nota debe estar entre 0.0 y 5.0.';
    END IF;
END$$

-- Revisiones de entregas
DROP TRIGGER IF EXISTS `trg_revision_bi`$$
CREATE TRIGGER `trg_revision_bi` BEFORE INSERT ON `revisiones_entrega` FOR EACH ROW
BEGIN
    DECLARE v_grupo INT;
    SELECT id_grupo INTO v_grupo FROM entregas WHERE id_entrega = NEW.id_entrega;
    IF NOT tv_docente_del_grupo(NEW.id_profesor, v_grupo) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo un docente asignado al curso puede revisar entregas.';
    END IF;
END$$

-- Fases: solo las crea un docente del curso
DROP TRIGGER IF EXISTS `trg_fases_bi`$$
CREATE TRIGGER `trg_fases_bi` BEFORE INSERT ON `fases` FOR EACH ROW
BEGIN
    IF NOT tv_docente_del_curso(NEW.id_profesor_creador, NEW.id_curso) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo un docente asignado al curso puede crear fases.';
    END IF;
END$$

-- Incentivos
DROP TRIGGER IF EXISTS `trg_incgrupal_bi`$$
CREATE TRIGGER `trg_incgrupal_bi` BEFORE INSERT ON `incentivos_grupales` FOR EACH ROW
BEGIN
    IF NOT tv_docente_del_curso(NEW.id_profesor, NEW.id_curso) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo un docente asignado al curso puede crear incentivos.';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_incotor_bi`$$
CREATE TRIGGER `trg_incotor_bi` BEFORE INSERT ON `incentivo_otorgado` FOR EACH ROW
BEGIN
    IF NOT tv_docente_del_grupo(NEW.id_profesor, NEW.id_grupo) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo un docente asignado al curso del grupo puede otorgar incentivos.';
    END IF;
END$$

DELIMITER ;
