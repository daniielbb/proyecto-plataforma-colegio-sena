<?php
// Punto de entrada de la interfaz del estudiante
require_once __DIR__ . '/../includes/sesion.php';
requiere_rol('estudiante');
redirigir('estudiante/inicio.php');
