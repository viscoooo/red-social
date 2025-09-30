<?php
/** Cierre de sesión mejorado */
require_once 'includes/sesion.php';
require_once 'includes/config.php';
cerrarSesion();
redirect('login.php');
exit;
?>
