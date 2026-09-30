<?php
/** THESISVISTA - Cerrar sesión */
require_once __DIR__ . '/includes/seguridad.php';

$_SESSION = [];
session_destroy();

session_start();                       // nueva sesión solo para el mensaje
mensaje('exito', 'Sesión cerrada correctamente.');
redirigir('login.php');
