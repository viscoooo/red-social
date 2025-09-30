<?php
/**
 * Configuración y helpers de rutas dinámicas.
 * Genera BASE_URL automáticamente (protocolo + host + path base) para entornos variables.
 * Si ya existe BASE_URL definida en config.php se respeta.
 */

if(!function_exists('getBaseUrl')) {
    function getBaseUrl(): string {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        $proto = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = rtrim(str_replace('\\','/',dirname($script)), '/');
        if($dir === '' || $dir === '.') $dir = '';
        return $proto . '://' . $host . ($dir ? $dir : '') . '/';
    }
}

if(!defined('AUTO_BASE_URL')) {
    define('AUTO_BASE_URL', getBaseUrl());
}

if(!function_exists('app_url')) {
    function app_url(string $path=''): string {
        $base = defined('BASE_URL') ? BASE_URL : AUTO_BASE_URL; // preferir BASE_URL fija si está definida
        return rtrim($base,'/') . '/' . ltrim($path,'/');
    }
}

if(!function_exists('redirect')) {
    function redirect(string $path): void {
        $target = app_url($path);
        if(!headers_sent()) {
            header('Location: ' . $target);
        } else {
            echo '<script>window.location.href=' . json_encode($target) . ';</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"></noscript>';
        }
        exit();
    }
}

// Helpers específicos
if(!function_exists('urlPerfil')) {
    function urlPerfil(int $usuario_id): string { return app_url('perfil.php?id=' . $usuario_id); }
}
if(!function_exists('urlPublicacion')) {
    function urlPublicacion(int $pub_id): string { return app_url('publicacion.php?id=' . $pub_id); }
}
if(!function_exists('urlSegura')) {
    function urlSegura(string $path): string {
        $path = preg_replace('/\.\.+/', '', $path); // quitar .. repetidos
        $path = ltrim($path, '/');
        return app_url($path);
    }
}
?>
