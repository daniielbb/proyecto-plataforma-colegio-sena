<?php

require_once __DIR__ . '/includes/seguridad.php';

$_SESSION = [];
session_destroy();

session_start();                       
mensaje('exito', 'Sesión cerrada correctamente.');
redirigir('login.php');
