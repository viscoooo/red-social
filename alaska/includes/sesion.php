<?php
/**
 * Gestión segura de sesiones
 * Archivo: includes/sesion.php
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
    ini_set('session.use_only_cookies', 1);
    session_start();
}

function iniciarSesionUsuario($usuario_id, $nombre, $username, $email) {
    $_SESSION['usuario_id'] = (int)$usuario_id;
    $_SESSION['usuario_nombre'] = $nombre;
    $_SESSION['usuario_username'] = $username;
    $_SESSION['usuario_email'] = $email;
    $_SESSION['login_time'] = time();
    $_SESSION['ultimo_acceso'] = time();
    session_regenerate_id(true); // Prevención de fijación de sesión
}

function estaAutenticado() {
    return isset($_SESSION['usuario_id'], $_SESSION['usuario_nombre'], $_SESSION['usuario_username'])
        && (time() - ($_SESSION['ultimo_acceso'] ?? 0) < 3600);
}

function actualizarUltimoAcceso() {
    if (estaAutenticado()) {
        $_SESSION['ultimo_acceso'] = time();
    }
}

function cerrarSesion() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}

function obtenerUsuarioSesion() {
    if (!estaAutenticado()) {
        return null;
    }
    return [
        'id' => $_SESSION['usuario_id'],
        'nombre' => $_SESSION['usuario_nombre'],
        'username' => $_SESSION['usuario_username'],
        'email' => $_SESSION['usuario_email']
    ];
}
