-- =========================================================
-- ThesisVista / SENATV — Actualización de esquema
-- Ejecutar DESPUÉS de importar senatv.sql original
-- Agrega: materias, horarios, asignaciones y (para más
-- adelante) tareas/entregas de docente-estudiante.
-- =========================================================

USE `senatv`;

-- ---------------------------------------------------------
-- Materias: la unidad real que el administrador crea/borra.
-- `cursos` sigue existiendo como el programa de formación
-- (ej: "Análisis y Desarrollo de Software"); una materia
-- pertenece a un curso/programa.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `materias` (
  `id_materia` INT(11) NOT NULL AUTO_INCREMENT,
  `id_curso` INT(11) NOT NULL,
  `nombre_materia` VARCHAR(150) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_materia`),
  KEY `f_materias_curso` (`id_curso`),
  CONSTRAINT `f_materias_curso` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------
-- Horarios: franja de una materia para un grupo específico.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `horarios` (
  `id_horario` INT(11) NOT NULL AUTO_INCREMENT,
  `id_materia` INT(11) NOT NULL,
  `id_grupo` INT(11) NOT NULL,
  `dia_semana` ENUM('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado') NOT NULL,
  `hora_inicio` TIME NOT NULL,
  `hora_fin` TIME NOT NULL,
  `salon` VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (`id_horario`),
  KEY `f_horarios_materia` (`id_materia`),
  KEY `f_horarios_grupo` (`id_grupo`),
  CONSTRAINT `f_horarios_materia` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`) ON DELETE CASCADE,
  CONSTRAINT `f_horarios_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------
-- Asignación de un profesor a una materia dentro de un grupo.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `materia_profesor` (
  `id_materia_profesor` INT(11) NOT NULL AUTO_INCREMENT,
  `id_materia` INT(11) NOT NULL,
  `id_profesor` INT(11) NOT NULL,
  `id_grupo` INT(11) NOT NULL,
  `fecha_asignacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_materia_profesor`),
  UNIQUE KEY `uniq_materia_profesor_grupo` (`id_materia`,`id_profesor`,`id_grupo`),
  KEY `f_matprof_profesor` (`id_profesor`),
  KEY `f_matprof_grupo` (`id_grupo`),
  CONSTRAINT `f_matprof_materia` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`) ON DELETE CASCADE,
  CONSTRAINT `f_matprof_profesor` FOREIGN KEY (`id_profesor`) REFERENCES `usuarios` (`usuario_id`) ON DELETE CASCADE,
  CONSTRAINT `f_matprof_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------
-- Inscripción de un estudiante a una materia (esto es lo que
-- hace que, más adelante, al estudiante le "aparezcan" sus
-- materias y el profesor asignado).
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `estudiante_materia` (
  `id_estudiante_materia` INT(11) NOT NULL AUTO_INCREMENT,
  `id_estudiante` INT(11) NOT NULL,
  `id_materia` INT(11) NOT NULL,
  `fecha_asignacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_estudiante_materia`),
  UNIQUE KEY `uniq_estudiante_materia` (`id_estudiante`,`id_materia`),
  KEY `f_estmat_materia` (`id_materia`),
  CONSTRAINT `f_estmat_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `usuarios` (`usuario_id`) ON DELETE CASCADE,
  CONSTRAINT `f_estmat_materia` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------
-- Preparado para las fases futuras (docente asigna, estudiante
-- entrega). No lo usa todavía el panel de administrador, pero
-- queda listo para no migrar de nuevo.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tareas` (
  `id_tarea` INT(11) NOT NULL AUTO_INCREMENT,
  `id_materia` INT(11) NOT NULL,
  `id_profesor` INT(11) NOT NULL,
  `titulo` VARCHAR(150) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `fecha_entrega` DATETIME NOT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tarea`),
  KEY `f_tareas_materia` (`id_materia`),
  KEY `f_tareas_profesor` (`id_profesor`),
  CONSTRAINT `f_tareas_materia` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`),
  CONSTRAINT `f_tareas_profesor` FOREIGN KEY (`id_profesor`) REFERENCES `usuarios` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `entregas` (
  `id_entrega` INT(11) NOT NULL AUTO_INCREMENT,
  `id_tarea` INT(11) NOT NULL,
  `id_estudiante` INT(11) NOT NULL,
  `archivo_url` VARCHAR(255) DEFAULT NULL,
  `fecha_entrega` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `calificacion` DECIMAL(4,2) DEFAULT NULL,
  `comentario_profesor` TEXT DEFAULT NULL,
  PRIMARY KEY (`id_entrega`),
  UNIQUE KEY `uniq_entrega_tarea_estudiante` (`id_tarea`,`id_estudiante`),
  KEY `f_entregas_estudiante` (`id_estudiante`),
  CONSTRAINT `f_entregas_tarea` FOREIGN KEY (`id_tarea`) REFERENCES `tareas` (`id_tarea`),
  CONSTRAINT `f_entregas_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `usuarios` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
