<?php
/**
 * Interfaz ADMINISTRADOR (próxima etapa).
 * Rol real en la BD: usuarios.rol = 'administrador'.
 * Gestionará usuarios, cursos, grupos, docente_curso y estudiante_grupo.
 */
require_once __DIR__ . '/../includes/sesion.php';
require_once __DIR__ . '/../includes/iconos.php';
requiere_rol('administrador');
$titulo = 'Panel de administración';
$descripcion = 'Esta interfaz se desarrollará en la siguiente etapa.';
$funciones = ['Gestionar usuarios y roles', 'Gestionar cursos y fichas', 'Crear grupos',
              'Asignar docentes a cursos', 'Asignar estudiantes a grupos'];
require __DIR__ . '/../includes/panel_en_construccion.php';
