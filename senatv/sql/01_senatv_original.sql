-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-09-2026 a las 00:23:26
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12
--
-- ARCHIVO ORIGINAL SIN MODIFICAR (copia de referencia).

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `senatv`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios`
--

CREATE TABLE `comentarios` (
  `id_comentario` int(11) NOT NULL,
  `id_tesis` int(11) NOT NULL,
  `id_fase` int(11) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `comentario` text NOT NULL,
  `fecha` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comentarios`
--

INSERT INTO `comentarios` (`id_comentario`, `id_tesis`, `id_fase`, `id_usuario`, `comentario`, `fecha`) VALUES
(2, 2, 1, 3, 'buen servicio', '2026-08-05 20:49:06'),
(3, 4, NULL, 5, 'buen servicio', '2026-08-05 20:49:06'),
(4, 5, NULL, 2, 'mal servicio', '2026-08-05 20:49:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id_curso` int(11) NOT NULL,
  `nombre_curso` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ficha` varchar(50) DEFAULT NULL COMMENT 'Ej: número de ficha SENA',
  `fecha_creacion` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id_curso`, `nombre_curso`, `descripcion`, `ficha`, `fecha_creacion`) VALUES
(1, 'Análisis y Desarrollo de Software', 'Formación técnica en desarrollo de software', '2758901', '2026-09-14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docente_curso`
--

CREATE TABLE `docente_curso` (
  `id_docente_curso` int(11) NOT NULL,
  `id_profesor` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `fecha_asignacion` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `docente_curso`
--

INSERT INTO `docente_curso` (`id_docente_curso`, `id_profesor`, `id_curso`, `fecha_asignacion`) VALUES
(1, 6, 1, '2026-09-14'),
(2, 7, 1, '2026-09-14'),
(3, 9, 1, '2026-09-14'),
(4, 10, 1, '2026-09-14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documento`
--

CREATE TABLE `documento` (
  `id_documento` int(11) NOT NULL,
  `id_tesis` int(11) NOT NULL,
  `nombre_documento` varchar(255) NOT NULL,
  `tipo_documento` varchar(50) NOT NULL,
  `fecha_subida` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `documento`
--

INSERT INTO `documento` (`id_documento`, `id_tesis`, `nombre_documento`, `tipo_documento`, `fecha_subida`) VALUES
(2, 2, 'correccion', 'copia de la tesis', '2026-08-17'),
(3, 3, 'correccion', 'copia de la justificacion', '2026-08-17'),
(4, 4, 'correccion', 'copia de la problematica', '2026-08-17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiante_grupo`
--

CREATE TABLE `estudiante_grupo` (
  `id_estudiante_grupo` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `fecha_asignacion` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estudiante_grupo`
--

INSERT INTO `estudiante_grupo` (`id_estudiante_grupo`, `id_estudiante`, `id_grupo`, `fecha_asignacion`) VALUES
(1, 1, 1, '2026-09-14'),
(2, 2, 1, '2026-09-14'),
(3, 3, 2, '2026-09-14'),
(4, 4, 2, '2026-09-14'),
(5, 5, 1, '2026-09-14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiante_incentivo`
--

CREATE TABLE `estudiante_incentivo` (
  `id_estudiante_incentivo` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `id_incentivo` int(11) NOT NULL,
  `id_tesis` int(11) DEFAULT NULL,
  `fecha_obtenido` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estudiante_incentivo`
--

INSERT INTO `estudiante_incentivo` (`id_estudiante_incentivo`, `id_estudiante`, `id_incentivo`, `id_tesis`, `fecha_obtenido`) VALUES
(1, 1, 1, 2, '2026-09-14 22:19:58'),
(2, 2, 1, 3, '2026-09-14 22:19:58'),
(3, 2, 2, 3, '2026-09-14 22:19:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fases`
--

CREATE TABLE `fases` (
  `id_fase` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `nombre_fase` varchar(150) NOT NULL,
  `descripcion` text NOT NULL COMMENT 'Explicación breve de la fase',
  `ejemplo` text DEFAULT NULL COMMENT 'Texto, enlace o referencia de ejemplo',
  `orden` int(11) NOT NULL DEFAULT 1 COMMENT 'Orden de la fase dentro del proceso',
  `duracion_dias` int(11) DEFAULT NULL COMMENT 'Duración sugerida en días para calcular fecha límite',
  `id_profesor_creador` int(11) NOT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `fases`
--

INSERT INTO `fases` (`id_fase`, `id_curso`, `nombre_fase`, `descripcion`, `ejemplo`, `orden`, `duracion_dias`, `id_profesor_creador`, `activa`, `fecha_creacion`, `fecha_modificacion`) VALUES
(1, 1, 'Formulación del problema', 'El estudiante identifica y describe la problemática a resolver.', 'Ejemplo: \"Las pymes del sector textil no cuentan con un sistema de inventario digital...\"', 1, 15, 6, 1, '2026-09-14 22:19:58', NULL),
(2, 1, 'Justificación', 'Se explica por qué es importante desarrollar el proyecto.', 'Ejemplo: incluir impacto social, económico y tecnológico esperado.', 2, 10, 6, 1, '2026-09-14 22:19:58', NULL),
(3, 1, 'Marco teórico y estado del arte', 'Investigación de antecedentes y bases conceptuales del proyecto.', 'Ejemplo: citar proyectos similares y tecnologías relacionadas.', 3, 20, 6, 1, '2026-09-14 22:19:58', NULL),
(4, 1, 'Desarrollo / Implementación', 'Construcción del producto o solución planteada.', 'Ejemplo: repositorio de código con avances documentados.', 4, 45, 6, 1, '2026-09-14 22:19:58', NULL),
(5, 1, 'Sustentación final', 'Presentación y defensa del proyecto terminado.', 'Ejemplo: presentación de 15 minutos + demo funcional.', 5, 10, 6, 1, '2026-09-14 22:19:58', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos`
--

CREATE TABLE `grupos` (
  `id_grupo` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `nombre_grupo` varchar(100) NOT NULL,
  `fecha_creacion` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `grupos`
--

INSERT INTO `grupos` (`id_grupo`, `id_curso`, `nombre_grupo`, `fecha_creacion`) VALUES
(1, 1, 'Grupo A', '2026-09-14'),
(2, 1, 'Grupo B', '2026-09-14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incentivos`
--

CREATE TABLE `incentivos` (
  `id_incentivo` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `icono` varchar(100) DEFAULT NULL COMMENT 'Nombre/clase de ícono o emoji, ej: ?'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `incentivos`
--

INSERT INTO `incentivos` (`id_incentivo`, `nombre`, `descripcion`, `icono`) VALUES
(1, 'Fase a tiempo', 'Completaste una fase antes de la fecha límite', ''),
(2, 'Racha perfecta', 'Completaste 3 fases seguidas a tiempo', ''),
(3, 'Proyecto aprobado', 'Tu proyecto fue aprobado por el docente', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tesis`
--

CREATE TABLE `tesis` (
  `id_tesis` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `resumen` text NOT NULL,
  `estado` enum('Borrador','En revisión','Aprobada','Rechazada') NOT NULL,
  `fecha_registro` date NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `id_profesor` int(11) NOT NULL,
  `id_grupo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tesis`
--

INSERT INTO `tesis` (`id_tesis`, `titulo`, `resumen`, `estado`, `fecha_registro`, `id_estudiante`, `id_profesor`, `id_grupo`) VALUES
(2, 'yyyy', 'djnsanjnsa', 'Borrador', '2026-08-05', 1, 9, 1),
(3, 'bienbien', 'increible', 'En revisión', '2026-08-20', 2, 6, 1),
(4, 'maravilloso', 'lo mejor de los mejor', 'Aprobada', '2026-08-07', 4, 10, 2),
(5, 'pesimo', 'horroroso', 'Rechazada', '2026-08-25', 5, 7, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tesis_fase`
--

CREATE TABLE `tesis_fase` (
  `id_tesis_fase` int(11) NOT NULL,
  `id_tesis` int(11) NOT NULL,
  `id_fase` int(11) NOT NULL,
  `estado` enum('Pendiente','En progreso','Completada','Atrasada') NOT NULL DEFAULT 'Pendiente',
  `fecha_inicio` date DEFAULT NULL,
  `fecha_limite` date DEFAULT NULL,
  `fecha_completada` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tesis_fase`
--

INSERT INTO `tesis_fase` (`id_tesis_fase`, `id_tesis`, `id_fase`, `estado`, `fecha_inicio`, `fecha_limite`, `fecha_completada`, `observaciones`) VALUES
(1, 2, 1, 'Completada', '2026-08-05', '2026-08-20', '2026-08-18', NULL),
(2, 2, 2, 'En progreso', '2026-08-19', '2026-08-29', NULL, NULL),
(3, 2, 3, 'Pendiente', NULL, NULL, NULL, NULL),
(4, 2, 4, 'Pendiente', NULL, NULL, NULL, NULL),
(5, 2, 5, 'Pendiente', NULL, NULL, NULL, NULL),
(6, 3, 1, 'Completada', '2026-08-20', '2026-09-04', '2026-08-30', NULL),
(7, 3, 2, 'Completada', '2026-08-31', '2026-09-10', '2026-09-05', NULL),
(8, 3, 3, 'En progreso', '2026-09-06', '2026-09-26', NULL, NULL),
(9, 3, 4, 'Pendiente', NULL, NULL, NULL, NULL),
(10, 3, 5, 'Pendiente', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `usuario_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('administrador','profesor','estudiante') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`usuario_id`, `nombre`, `apellido`, `correo`, `contrasena`, `rol`) VALUES
(1, 'Mariana', 'Parra', 'mari@gmail', '1234', 'estudiante'),
(2, 'Camila', 'Arevalo', 'cami@gmail', '4321', 'estudiante'),
(3, 'Alejandro', 'Garzon', 'alejo@gmail', '5678', 'estudiante'),
(4, 'Esteban', 'Peña', 'esteban@gmail', '8765', 'estudiante'),
(5, 'Angel', 'Cardenas', 'Angel@gmail', '1122', 'estudiante'),
(6, 'Arthur', 'Calderon', 'arthur@gmail', '2222', 'profesor'),
(7, 'Carmen', 'Carranza', 'carmen@gmail', '5252', 'profesor'),
(8, 'Martha', 'mendoza', 'martha@gmail', '7878', 'profesor'),
(9, 'Carlos', 'Perez', 'carlos@gmail', '5555', 'profesor'),
(10, 'Sara', 'soposs', 'sara@gmail', '4444', 'profesor'),
(11, 'Laura', 'Gómez', 'admin@thesisvista.com', 'admin123', 'administrador');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_progreso_tesis`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_progreso_tesis` (
`id_tesis` int(11)
,`titulo` varchar(255)
,`nombre_estudiante` varchar(100)
,`apellido_estudiante` varchar(100)
,`total_fases` bigint(21)
,`fases_completadas` decimal(22,0)
,`porcentaje_avance` decimal(27,1)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_progreso_tesis`
--
DROP TABLE IF EXISTS `vista_progreso_tesis`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_progreso_tesis`  AS SELECT `t`.`id_tesis` AS `id_tesis`, `t`.`titulo` AS `titulo`, `u`.`nombre` AS `nombre_estudiante`, `u`.`apellido` AS `apellido_estudiante`, count(`tf`.`id_tesis_fase`) AS `total_fases`, sum(case when `tf`.`estado` = 'Completada' then 1 else 0 end) AS `fases_completadas`, round(sum(case when `tf`.`estado` = 'Completada' then 1 else 0 end) / count(`tf`.`id_tesis_fase`) * 100,1) AS `porcentaje_avance` FROM ((`tesis` `t` join `usuarios` `u` on(`u`.`usuario_id` = `t`.`id_estudiante`)) left join `tesis_fase` `tf` on(`tf`.`id_tesis` = `t`.`id_tesis`)) GROUP BY `t`.`id_tesis`, `t`.`titulo`, `u`.`nombre`, `u`.`apellido` ;

--
-- Índices para tablas volcadas
--

ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id_comentario`),
  ADD KEY `f_comentarios_tesis` (`id_tesis`),
  ADD KEY `f_comentarios_fase` (`id_fase`),
  ADD KEY `f_comentarios_usuario` (`id_usuario`);

ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id_curso`);

ALTER TABLE `docente_curso`
  ADD PRIMARY KEY (`id_docente_curso`),
  ADD UNIQUE KEY `uniq_docente_curso` (`id_profesor`,`id_curso`),
  ADD KEY `f_docentecurso_curso` (`id_curso`);

ALTER TABLE `documento`
  ADD PRIMARY KEY (`id_documento`),
  ADD KEY `f_documentos_tesis` (`id_tesis`);

ALTER TABLE `estudiante_grupo`
  ADD PRIMARY KEY (`id_estudiante_grupo`),
  ADD UNIQUE KEY `uniq_estudiante_grupo` (`id_estudiante`,`id_grupo`),
  ADD KEY `f_estgrupo_grupo` (`id_grupo`);

ALTER TABLE `estudiante_incentivo`
  ADD PRIMARY KEY (`id_estudiante_incentivo`),
  ADD KEY `f_estinc_estudiante` (`id_estudiante`),
  ADD KEY `f_estinc_incentivo` (`id_incentivo`),
  ADD KEY `f_estinc_tesis` (`id_tesis`);

ALTER TABLE `fases`
  ADD PRIMARY KEY (`id_fase`),
  ADD KEY `f_fases_curso` (`id_curso`),
  ADD KEY `f_fases_profesor` (`id_profesor_creador`);

ALTER TABLE `grupos`
  ADD PRIMARY KEY (`id_grupo`),
  ADD KEY `f_grupos_curso` (`id_curso`);

ALTER TABLE `incentivos`
  ADD PRIMARY KEY (`id_incentivo`);

ALTER TABLE `tesis`
  ADD PRIMARY KEY (`id_tesis`),
  ADD KEY `f_tesis_estudiante` (`id_estudiante`),
  ADD KEY `f_tesis_profesor` (`id_profesor`),
  ADD KEY `f_tesis_grupo` (`id_grupo`);

ALTER TABLE `tesis_fase`
  ADD PRIMARY KEY (`id_tesis_fase`),
  ADD UNIQUE KEY `uniq_tesis_fase` (`id_tesis`,`id_fase`),
  ADD KEY `f_tesisfase_fase` (`id_fase`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`usuario_id`),
  ADD UNIQUE KEY `correo` (`correo`);

ALTER TABLE `comentarios` MODIFY `id_comentario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
ALTER TABLE `cursos` MODIFY `id_curso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `docente_curso` MODIFY `id_docente_curso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
ALTER TABLE `documento` MODIFY `id_documento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
ALTER TABLE `estudiante_grupo` MODIFY `id_estudiante_grupo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `estudiante_incentivo` MODIFY `id_estudiante_incentivo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `fases` MODIFY `id_fase` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `grupos` MODIFY `id_grupo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `incentivos` MODIFY `id_incentivo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `tesis` MODIFY `id_tesis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `tesis_fase` MODIFY `id_tesis_fase` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
ALTER TABLE `usuarios` MODIFY `usuario_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

ALTER TABLE `comentarios`
  ADD CONSTRAINT `f_comentarios_fase` FOREIGN KEY (`id_fase`) REFERENCES `fases` (`id_fase`),
  ADD CONSTRAINT `f_comentarios_tesis` FOREIGN KEY (`id_tesis`) REFERENCES `tesis` (`id_tesis`),
  ADD CONSTRAINT `f_comentarios_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`usuario_id`);

ALTER TABLE `docente_curso`
  ADD CONSTRAINT `f_docentecurso_curso` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`),
  ADD CONSTRAINT `f_docentecurso_profesor` FOREIGN KEY (`id_profesor`) REFERENCES `usuarios` (`usuario_id`);

ALTER TABLE `documento`
  ADD CONSTRAINT `f_documentos_tesis` FOREIGN KEY (`id_tesis`) REFERENCES `tesis` (`id_tesis`);

ALTER TABLE `estudiante_grupo`
  ADD CONSTRAINT `f_estgrupo_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `usuarios` (`usuario_id`),
  ADD CONSTRAINT `f_estgrupo_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`);

ALTER TABLE `estudiante_incentivo`
  ADD CONSTRAINT `f_estinc_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `usuarios` (`usuario_id`),
  ADD CONSTRAINT `f_estinc_incentivo` FOREIGN KEY (`id_incentivo`) REFERENCES `incentivos` (`id_incentivo`),
  ADD CONSTRAINT `f_estinc_tesis` FOREIGN KEY (`id_tesis`) REFERENCES `tesis` (`id_tesis`);

ALTER TABLE `fases`
  ADD CONSTRAINT `f_fases_curso` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`),
  ADD CONSTRAINT `f_fases_profesor` FOREIGN KEY (`id_profesor_creador`) REFERENCES `usuarios` (`usuario_id`);

ALTER TABLE `grupos`
  ADD CONSTRAINT `f_grupos_curso` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`);

ALTER TABLE `tesis`
  ADD CONSTRAINT `f_tesis_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `usuarios` (`usuario_id`),
  ADD CONSTRAINT `f_tesis_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`),
  ADD CONSTRAINT `f_tesis_profesor` FOREIGN KEY (`id_profesor`) REFERENCES `usuarios` (`usuario_id`);

ALTER TABLE `tesis_fase`
  ADD CONSTRAINT `f_tesisfase_fase` FOREIGN KEY (`id_fase`) REFERENCES `fases` (`id_fase`),
  ADD CONSTRAINT `f_tesisfase_tesis` FOREIGN KEY (`id_tesis`) REFERENCES `tesis` (`id_tesis`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
