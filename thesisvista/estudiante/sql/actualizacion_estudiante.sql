-- =====================================================================
--  THESISVISTA - Actualización de la base `senatv` para el MÓDULO ESTUDIANTE
--
--  Cómo usarla: phpMyAdmin > base senatv > Importar este archivo
--  DESPUÉS de sql/actualizacion_docente.sql.
--  - No borra ni cambia datos existentes.
--  - Se puede ejecutar varias veces.
--
--  Se REUTILIZA todo lo existente (no hay tablas duplicadas):
--    usuarios.rol ................ rol del estudiante
--    estudiante_grupo ............ grupo(s) del estudiante (el admin lo cambia y el estudiante lo ve al instante)
--    tesis (id_grupo) ............ proyecto del grupo · tesis.id_profesor = docente encargado
--    fases + fase_grupo .......... fases del grupo, su estado y avance (las configura el docente)
--    entregas .................... archivos del estudiante (fase, grupo, estudiante, versión, fecha)
--    revisiones_entrega .......... revisiones del docente sobre cada entrega
--    retroalimentacion ........... comentarios del docente
--    incentivos_grupales / incentivo_otorgado / estudiante_incentivo ... incentivos
--
--  Se AGREGA solo lo indispensable:
--    fases.requiere_anterior ..... 1 = la fase se desbloquea cuando la fase anterior del grupo
--                                  está Aprobada/Completada (valor por defecto)
--    aviso_lectura ............... hasta qué momento el estudiante ya vio sus avisos
--    tv_fase_bloqueada() ......... regla ÚNICA de bloqueo (la usan PHP y los triggers)
--    TRIGGERS en `entregas` ...... la base rechaza entregas de quien no sea estudiante del grupo,
--                                  en fases bloqueadas, cerradas, en revisión o aprobadas,
--                                  y cualquier cambio del autor/archivo de una entrega.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. Regla de desbloqueo secuencial (por fase, la define el docente; por defecto activa)
-- ---------------------------------------------------------------------
ALTER TABLE `fases`
  ADD COLUMN IF NOT EXISTS `requiere_anterior` TINYINT(1) NOT NULL DEFAULT 1
      COMMENT '1 = se desbloquea cuando la fase anterior del grupo está aprobada' AFTER `estado`;

-- ---------------------------------------------------------------------
-- 2. Avisos vistos por el estudiante
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `aviso_lectura` (
  `id_usuario` int(11) NOT NULL,
  `fecha_visto` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  CONSTRAINT `f_avisolect_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`usuario_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DELIMITER $$

-- ---------------------------------------------------------------------
-- 3. Regla de bloqueo (una sola fuente de verdad)
--    Devuelve: 0 = disponible
--              1 = la fase no está asignada al grupo o está en borrador
--              2 = la fase fue cerrada por el docente y el grupo no la había empezado
--              3 = todavía no llega la fecha de inicio
--              4 = falta que el docente apruebe la fase anterior
--    Si el docente ya movió el estado del grupo en la fase (deja de estar 'Pendiente'),
--    la fase queda desbloqueada para ese grupo aunque se cumplan 3 o 4 (desbloqueo manual).
-- ---------------------------------------------------------------------
DROP FUNCTION IF EXISTS `tv_fase_bloqueada`$$
CREATE FUNCTION `tv_fase_bloqueada`(p_fase INT, p_grupo INT) RETURNS TINYINT
READS SQL DATA
BEGIN
    DECLARE v_estado_fase VARCHAR(20) DEFAULT NULL;
    DECLARE v_estado_grupo VARCHAR(30) DEFAULT NULL;
    DECLARE v_inicio DATE DEFAULT NULL;
    DECLARE v_requiere TINYINT DEFAULT 1;
    DECLARE v_orden INT DEFAULT 0;
    DECLARE v_previas INT DEFAULT 0;

    SELECT f.estado, fg.estado, f.fecha_inicio, f.requiere_anterior, f.orden
      INTO v_estado_fase, v_estado_grupo, v_inicio, v_requiere, v_orden
      FROM fase_grupo fg JOIN fases f ON f.id_fase = fg.id_fase
     WHERE fg.id_fase = p_fase AND fg.id_grupo = p_grupo
     LIMIT 1;

    IF v_estado_fase IS NULL OR v_estado_fase = 'Borrador' THEN RETURN 1; END IF;
    IF v_estado_grupo <> 'Pendiente' THEN RETURN 0; END IF;
    IF v_estado_fase = 'Cerrada' THEN RETURN 2; END IF;
    IF v_inicio IS NOT NULL AND v_inicio > CURDATE() THEN RETURN 3; END IF;

    IF v_requiere = 1 THEN
        SELECT COUNT(*) INTO v_previas
          FROM fase_grupo fg2 JOIN fases f2 ON f2.id_fase = fg2.id_fase
         WHERE fg2.id_grupo = p_grupo
           AND f2.estado = 'Publicada'
           AND (f2.orden < v_orden OR (f2.orden = v_orden AND f2.id_fase < p_fase))
           AND fg2.estado NOT IN ('Aprobada', 'Completada');
        IF v_previas > 0 THEN RETURN 4; END IF;
    END IF;
    RETURN 0;
END$$

-- ---------------------------------------------------------------------
-- 4. Entregas: solo el estudiante del grupo, en una fase disponible
-- ---------------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_entregas_bi`$$
CREATE TRIGGER `trg_entregas_bi` BEFORE INSERT ON `entregas` FOR EACH ROW
BEGIN
    DECLARE v_ok INT DEFAULT 0;
    DECLARE v_estado_fase VARCHAR(20) DEFAULT NULL;
    DECLARE v_estado_grupo VARCHAR(30) DEFAULT NULL;

    SELECT COUNT(*) INTO v_ok
      FROM usuarios u JOIN estudiante_grupo eg ON eg.id_estudiante = u.usuario_id
     WHERE u.usuario_id = NEW.id_estudiante AND u.rol = 'estudiante' AND eg.id_grupo = NEW.id_grupo;
    IF v_ok = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo un estudiante del grupo puede entregar en esta fase.';
    END IF;

    IF tv_fase_bloqueada(NEW.id_fase, NEW.id_grupo) <> 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fase está bloqueada para su grupo.';
    END IF;

    SELECT f.estado, fg.estado INTO v_estado_fase, v_estado_grupo
      FROM fase_grupo fg JOIN fases f ON f.id_fase = fg.id_fase
     WHERE fg.id_fase = NEW.id_fase AND fg.id_grupo = NEW.id_grupo LIMIT 1;
    IF v_estado_fase <> 'Publicada' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fase no está recibiendo entregas.';
    END IF;
    IF v_estado_grupo IN ('En revisión', 'Aprobada', 'Completada') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fase está en revisión o ya fue aprobada.';
    END IF;
END$$

-- Nadie puede cambiar a quién pertenece una entrega ni su archivo (el docente solo cambia el estado).
DROP TRIGGER IF EXISTS `trg_entregas_bu`$$
CREATE TRIGGER `trg_entregas_bu` BEFORE UPDATE ON `entregas` FOR EACH ROW
BEGIN
    IF NEW.id_estudiante <> OLD.id_estudiante OR NEW.id_grupo <> OLD.id_grupo OR NEW.id_fase <> OLD.id_fase
       OR NEW.version <> OLD.version OR NEW.archivo_ruta <> OLD.archivo_ruta THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede cambiar el autor, la fase ni el archivo de una entrega.';
    END IF;
END$$

DELIMITER ;
