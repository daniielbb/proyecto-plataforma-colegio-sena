<?php
/** THESISVISTA - Punto de entrada: envía al login o al panel del rol. */
require_once __DIR__ . '/includes/seguridad.php';

if (usuario_logueado() && isset(PANELES[$_SESSION['rol'] ?? ''])) {
    redirigir(PANELES[$_SESSION['rol']]);
}
redirigir('login.php');
