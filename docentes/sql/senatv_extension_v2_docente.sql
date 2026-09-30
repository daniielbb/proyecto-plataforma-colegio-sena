-- =====================================================================
--  SENATV · Extensión v2 para el módulo DOCENTE
--  Ejecutar DESPUÉS de:
--    1) senatv.sql                          (base original)
--    2) senatv_extension_seguimiento.sql    (extensión v1)
--
--  Solo agrega lo que la interfaz docente necesita y no existía.
--  NO borra ni renombra nada; los valores existentes se conservan.
--
--   1. documento.estado     -> + 'Corregido'
--   2. correcciones.estado  -> + 'Corregido'
--   3. correcciones         -> + recomendacion (TEXT, opcional)
-- =====================================================================

USE `senatv`;
SET NAMES utf8mb4;

-- 1. Estado "Corregido" en documentos
--    Entregado        = Pendiente de revisión
--    En revisión      = En revisión
--    Requiere ajustes = Requiere correcciones
--    Corregido        = El estudiante ya corrigió (nuevo)
--    Aprobado         = Aprobado
ALTER TABLE `documento`
  MODIFY `estado` ENUM('Entregado','En revisión','Requiere ajustes','Corregido','Aprobado')
  NOT NULL DEFAULT 'Entregado';

-- 2. Estado "Corregido" en las revisiones del docente
ALTER TABLE `correcciones`
  MODIFY `estado` ENUM('Requiere ajustes','En revisión','Corregido','Aprobada')
  NOT NULL DEFAULT 'Requiere ajustes';

-- 3. Campo "Recomendación" de la revisión
ALTER TABLE `correcciones`
  ADD COLUMN IF NOT EXISTS `recomendacion` TEXT NULL DEFAULT NULL AFTER `observacion`;
