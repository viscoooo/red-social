<?php
/**
 * Archivo de configuración global.
 * 
 * Define BASE_URL y helpers de rutas / redirecciones. Aquí también se aplica un
 * buffer de salida temprano para evitar los avisos "Cannot modify header information"
 * cuando por error se genera salida antes de intentar redirigir. El buffer permite
 * seguir usando header() mientras no se haya hecho flush.
 */

// Configuración global de rutas/base
// Ajusta esta ruta si mueves el proyecto a otra carpeta o dominio.
// Debe terminar con '/'. Ejemplos:
//   '/red-social/alaska/'  (para http://localhost/red-social/alaska/)
//   '/'                         (si está en la raíz del dominio)
//   'https://midominio.com/app/'
if (!defined('BASE_URL')) {
    $base = '/red-social/alaska/'; // CAMBIA esto si mueves la app
    // Normalizamos para evitar barras dobles y garantizar barra final
    define('BASE_URL', rtrim($base, '/') . '/');
}

// Iniciar sesión temprano (algunos scripts dependen de $_SESSION) y activar buffering seguro.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Activa un buffer de salida si aún no existe para poder enviar headers más tarde.
if (!headers_sent() && ob_get_level() === 0) {
    ob_start();
}

// Definición de rutas canónicas (clave => path relativo) para centralizar mantenimiento
if(!defined('APP_ROUTES')) {
    define('APP_ROUTES', json_encode([
        'login' => 'login.php',
        'logout' => 'logout.php',
        'registro' => 'registro.php',
        'feed' => 'index.php',
        'perfil' => 'perfil.php',
        'publicar' => 'publicar.php',
        'explorar' => 'explorar.php',
        'notificaciones' => 'notificaciones.php',
        'mensajes' => 'mensajes.php',
        'guardados' => 'guardados.php',
        'listas' => 'listas.php'
    ]));
}

if(!function_exists('route_path')) {
    /** Obtiene el path relativo de una ruta nombrada */
    function route_path(string $name): ?string {
        $routes = json_decode(APP_ROUTES, true);
        return $routes[$name] ?? null;
    }
}

if(!function_exists('redirect_route')) {
    /** Redirige usando el nombre de ruta definido en APP_ROUTES */
    function redirect_route(string $name, array $query = []): void {
        $path = route_path($name);
        if($path === null) { redirect_to('index.php'); }
        if($query){ $path .= (strpos($path,'?')===false?'?':'&'). http_build_query($query); }
        redirect_to($path);
    }
}

// Helper para construir URLs absolutas a partir de rutas relativas
if (!function_exists('url')) {
    /**
     * Genera una URL absoluta basada en BASE_URL.
     * @param string $path Ruta relativa (por ej. 'css/estilo.css' o '/login.php')
    * @return string URL absoluta (por ej. '/red-social/alaska/css/estilo.css')
     */
    function url(string $path = ''): string {
        return BASE_URL . ltrim($path, '/');
    }
}

// Compatibilidad con llamadas existentes a app_url()
if (!function_exists('app_url')) {
    function app_url(string $path = ''): string {
        return url($path);
    }
}

// Helper para redirigir a una ruta específica construida con url()
if (!function_exists('redirect_to')) {
    /**
     * Redirige inmediatamente a una ruta de la aplicación.
     * @param string $path Ruta relativa destino
     * @return void
     */
    function redirect_to(string $path): void {
        $target = url($path);
        // Si aún no se enviaron headers, se usa header().
        if (!headers_sent()) {
            header('Location: ' . $target);
        } else {
            // Fallback silencioso (sin warning) si ya hubo salida:
            echo '<script>window.location.href=' . json_encode($target) . ';</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"></noscript>';
        }
        exit();
    }
}

// Compatibilidad con scripts que usaban redirect() del archivo de rutas eliminado
if (!function_exists('redirect')) {
    function redirect(string $path): void {
        redirect_to($path);
    }
}
?>
