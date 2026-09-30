<?php
/**
 * Interfaz DOCENTE (próxima etapa).
 * Rol real en la BD: usuarios.rol = 'profesor'.
 * Aquí se implementarán: ver grupos, asignar estudiantes, establecer fases y
 * requisitos (tabla requisitos_fase), revisar documentos, registrar
 * correcciones (tabla correcciones), escribir comentarios (tabla comentarios),
 * asignar notas y avanzar fases (tabla tesis_fase).
 */
require_once __DIR__ . '/../includes/sesion.php';
require_once __DIR__ . '/../includes/iconos.php';
requiere_rol('profesor');
$titulo = 'Panel docente';
$descripcion = 'Esta interfaz se desarrollará en la siguiente etapa.';
$funciones = ['Ver grupos y estudiantes', 'Establecer fases y requisitos', 'Revisar documentos',
              'Registrar correcciones y notas', 'Escribir comentarios', 'Avanzar grupos a la siguiente fase'];
require __DIR__ . '/../includes/panel_en_construccion.php';
