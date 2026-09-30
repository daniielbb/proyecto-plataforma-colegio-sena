-- =====================================================================
--  SENATV · Extensión para el módulo de seguimiento (v1)
--  Ejecutar DESPUÉS de importar la base original `senatv`.
--
--  Principio: NO se borra ni renombra nada existente. Solo se agregan
--  columnas opcionales (NULL o con valor por defecto) y dos tablas nuevas
--  para funcionalidades que la base original no cubría:
--
--   1. correcciones      -> Correcciones formales del docente sobre un
--                           documento/fase (estado, observación, nota).
--   2. requisitos_fase   -> Puntos / requisitos que el docente establece
--                           para una fase (general o por proyecto).
--   3. documento         -> + id_fase, estado, ruta_archivo, fecha_modificacion
--   4. estudiante_grupo  -> + rol_grupo, estado
--   5. usuarios          -> + ultimo_acceso (para notificar novedades)
--   6. comentarios       -> + id_documento (documento relacionado, opcional)
--
--  Compatible con MariaDB 10.3+ (XAMPP). En MySQL 8 elimine los
--  "IF NOT EXISTS" de las sentencias ALTER TABLE ... ADD COLUMN.
-- =====================================================================

USE `senatv`;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. usuarios: último acceso (para marcar correcciones/comentarios nuevos)
-- ---------------------------------------------------------------------
ALTER TABLE `usuarios`
  ADD COLUMN IF NOT EXISTS `ultimo_acceso` DATETIME NULL DEFAULT NULL
      COMMENT 'Último inicio de sesión; se usa para notificar novedades';

-- Nota: la columna `contrasena` ya es varchar(255), suficiente para
-- password_hash(). El login migra automáticamente las contraseñas en
-- texto plano a hash la primera vez que el usuario inicia sesión.

-- ---------------------------------------------------------------------
-- 2. documento: fase asociada, estado, archivo físico y modificación
-- ---------------------------------------------------------------------
ALTER TABLE `documento`
  ADD COLUMN IF NOT EXISTS `id_fase` INT(11) NULL DEFAULT NULL AFTER `id_tesis`,
  ADD COLUMN IF NOT EXISTS `estado` ENUM('Entregado','En revisión','Requiere ajustes','Aprobado')
      NOT NULL DEFAULT 'Entregado' AFTER `tipo_documento`,
  ADD COLUMN IF NOT EXISTS `ruta_archivo` VARCHAR(255) NULL DEFAULT NULL
      COMMENT 'Ruta relativa dentro de /uploads/documentos/' AFTER `estado`,
  ADD COLUMN IF NOT EXISTS `fecha_modificacion` DATETIME NULL DEFAULT NULL
      ON UPDATE current_timestamp() AFTER `fecha_subida`;

ALTER TABLE `documento`
  ADD KEY IF NOT EXISTS `f_documentos_fase` (`id_fase`);

-- (se agrega la FK solo si no existe)
SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'documento'
              AND CONSTRAINT_NAME = 'f_documentos_fase');
SET @sql := IF(@fk = 0,
  'ALTER TABLE `documento` ADD CONSTRAINT `f_documentos_fase` FOREIGN KEY (`id_fase`) REFERENCES `fases` (`id_fase`) ON DELETE SET NULL',
  'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ---------------------------------------------------------------------
-- 2b. comentarios: documento relacionado (opcional)
-- ---------------------------------------------------------------------
ALTER TABLE `comentarios`
  ADD COLUMN IF NOT EXISTS `id_documento` INT(11) NULL DEFAULT NULL AFTER `id_fase`,
  ADD KEY IF NOT EXISTS `f_comentarios_documento` (`id_documento`);

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'comentarios'
              AND CONSTRAINT_NAME = 'f_comentarios_documento');
SET @sql := IF(@fk = 0,
  'ALTER TABLE `comentarios` ADD CONSTRAINT `f_comentarios_documento` FOREIGN KEY (`id_documento`) REFERENCES `documento` (`id_documento`) ON DELETE SET NULL',
  'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ---------------------------------------------------------------------
-- 3. estudiante_grupo: rol dentro del grupo y estado del integrante
-- ---------------------------------------------------------------------
ALTER TABLE `estudiante_grupo`
  ADD COLUMN IF NOT EXISTS `rol_grupo` VARCHAR(50) NULL DEFAULT NULL
      COMMENT 'Ej: Líder, Investigador, Documentador (lo asigna el docente)',
  ADD COLUMN IF NOT EXISTS `estado` ENUM('Activo','Inactivo') NOT NULL DEFAULT 'Activo';

-- ---------------------------------------------------------------------
-- 4. correcciones: revisiones formales del docente
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `correcciones` (
  `id_correccion` INT(11) NOT NULL AUTO_INCREMENT,
  `id_tesis`      INT(11) NOT NULL,
  `id_fase`       INT(11) NULL DEFAULT NULL,
  `id_documento`  INT(11) NULL DEFAULT NULL,
  `id_profesor`   INT(11) NOT NULL,
  `estado`        ENUM('Requiere ajustes','En revisión','Aprobada') NOT NULL DEFAULT 'Requiere ajustes',
  `observacion`   TEXT NOT NULL,
  `calificacion`  DECIMAL(3,1) NULL DEFAULT NULL COMMENT 'Nota/puntos opcionales (0.0 - 5.0)',
  `fecha`         DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_correccion`),
  KEY `f_correcciones_tesis` (`id_tesis`),
  KEY `f_correcciones_fase` (`id_fase`),
  KEY `f_correcciones_documento` (`id_documento`),
  KEY `f_correcciones_profesor` (`id_profesor`),
  CONSTRAINT `f_correcciones_tesis`     FOREIGN KEY (`id_tesis`)     REFERENCES `tesis` (`id_tesis`),
  CONSTRAINT `f_correcciones_fase`      FOREIGN KEY (`id_fase`)      REFERENCES `fases` (`id_fase`) ON DELETE SET NULL,
  CONSTRAINT `f_correcciones_documento` FOREIGN KEY (`id_documento`) REFERENCES `documento` (`id_documento`) ON DELETE SET NULL,
  CONSTRAINT `f_correcciones_profesor`  FOREIGN KEY (`id_profesor`)  REFERENCES `usuarios` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 5. requisitos_fase: puntos / requisitos que define el docente
--    id_tesis NULL  => requisito general de la fase (todos los proyectos)
--    id_tesis <> NULL => requisito específico para un proyecto
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `requisitos_fase` (
  `id_requisito`   INT(11) NOT NULL AUTO_INCREMENT,
  `id_fase`        INT(11) NOT NULL,
  `id_tesis`       INT(11) NULL DEFAULT NULL,
  `descripcion`    VARCHAR(500) NOT NULL,
  `obligatorio`    TINYINT(1) NOT NULL DEFAULT 1,
  `orden`          INT(11) NOT NULL DEFAULT 1,
  `id_profesor`    INT(11) NOT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_requisito`),
  KEY `f_requisitos_fase` (`id_fase`),
  KEY `f_requisitos_tesis` (`id_tesis`),
  KEY `f_requisitos_profesor` (`id_profesor`),
  CONSTRAINT `f_requisitos_fase`     FOREIGN KEY (`id_fase`)     REFERENCES `fases` (`id_fase`) ON DELETE CASCADE,
  CONSTRAINT `f_requisitos_tesis`    FOREIGN KEY (`id_tesis`)    REFERENCES `tesis` (`id_tesis`) ON DELETE CASCADE,
  CONSTRAINT `f_requisitos_profesor` FOREIGN KEY (`id_profesor`) REFERENCES `usuarios` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 6. (Recomendado) Usuario MySQL de SOLO LECTURA para el módulo estudiante.
--    Descomente y ajuste la contraseña si desea usarlo en config/config.php.
--    El único UPDATE que hace el login es sobre usuarios(ultimo_acceso,
--    contrasena) por eso se concede solo sobre esas columnas.
-- ---------------------------------------------------------------------
-- CREATE USER IF NOT EXISTS 'senatv_lectura'@'localhost' IDENTIFIED BY 'cambie_esta_clave';
-- GRANT SELECT ON `senatv`.* TO 'senatv_lectura'@'localhost';
-- GRANT UPDATE (`ultimo_acceso`, `contrasena`) ON `senatv`.`usuarios` TO 'senatv_lectura'@'localhost';
-- FLUSH PRIVILEGES;
