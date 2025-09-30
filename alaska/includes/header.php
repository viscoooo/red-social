<?php
/**
 * Cabecera HTML reutilizable con barra de navegación sencilla.
 * Carga configuración, conexión y helpers básicos sin dependencias extra.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/funciones.php';

$usuario = obtenerUsuarioSesion();
if ($usuario) {
    actualizarUltimoAcceso();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALASKA - Red Social para Cuidado de Animales</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= url('css/estilo.css') ?>">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="logo">
                <i class="fas fa-paw"></i>
                <span>ALASKA</span>
            </div>

            <?php if ($usuario): ?>
            <form class="search-bar" method="GET" action="<?= url('buscar.php') ?>">
                <i class="fas fa-search"></i>
                <input type="text" name="q" placeholder="Buscar personas o publicaciones..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </form>
            <div class="nav-icons">
                <a href="<?= url('index.php') ?>" class="nav-icon <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                </a>
                <a href="<?= url('buscar.php') ?>" class="nav-icon <?= basename($_SERVER['PHP_SELF']) === 'buscar.php' ? 'active' : '' ?>">
                    <i class="fas fa-search"></i>
                </a>
                <a href="<?= url('notificaciones.php') ?>" class="nav-icon <?= basename($_SERVER['PHP_SELF']) === 'notificaciones.php' ? 'active' : '' ?>">
                    <i class="fas fa-bell"></i>
                </a>
                <a href="<?= url('mensajes.php') ?>" class="nav-icon <?= basename($_SERVER['PHP_SELF']) === 'mensajes.php' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i>
                </a>
                <a href="<?= url('perfil.php?id=' . ($usuario['id'] ?? 0)) ?>" class="nav-icon <?= basename($_SERVER['PHP_SELF']) === 'perfil.php' ? 'active' : '' ?>">
                    <i class="fas fa-user"></i>
                </a>
            </div>
            <?php else: ?>
            <div class="nav-auth">
                <a href="<?= url('login.php') ?>" class="btn-login">Iniciar Sesión</a>
                <a href="<?= url('registro.php') ?>" class="btn-registro">Registrarse</a>
            </div>
            <?php endif; ?>
        </div>
    </nav>
