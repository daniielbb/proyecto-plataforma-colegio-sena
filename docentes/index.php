<?php
require_once __DIR__ . '/includes/auth.php';
redirigir(!empty($_SESSION['usuario_id']) ? 'docente/dashboard.php' : 'login.php');
