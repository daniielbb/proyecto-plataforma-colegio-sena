<?php
/**
 * Enrutador por rol: después de iniciar sesión cada usuario
 * se envía a su propia interfaz.
 *   estudiante    -> estudiante/
 *   profesor      -> docente/
 *   administrador -> admin/
 */
require_once __DIR__ . '/includes/sesion.php';

requiere_login();
redirigir(inicio_por_rol($_SESSION['rol']));
