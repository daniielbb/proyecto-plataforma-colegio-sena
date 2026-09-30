<?php
require_once __DIR__ . '/includes/sesion.php';

iniciar_sesion();
// Se exige el token para evitar cierres de sesión forzados desde otros sitios
if (validar_csrf($_GET['t'] ?? null)) {
    cerrar_sesion();
    session_start();
    $_SESSION['aviso'] = 'Cerraste sesión correctamente.';
}
redirigir('login.php');
