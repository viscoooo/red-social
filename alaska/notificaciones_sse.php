<?php
/**
 * Endpoint Server-Sent Events para notificaciones en tiempo real.
 * Mantiene la conexión abierta y envía nuevas notificaciones no leídas.
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit();
}

$usuario_id = (int)$_SESSION['usuario_id'];

// Cabeceras SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // nginx

// Último ID conocido recibido desde EventSource (Last-Event-ID)
$lastId = 0;
if (!empty($_SERVER['HTTP_LAST_EVENT_ID'])) {
    $lastId = (int)$_SERVER['HTTP_LAST_EVENT_ID'];
}

// Bucle simple (duración máxima ~30 iteraciones * 2s = 60s) luego el cliente reconecta
$iteraciones = 0;
while ($iteraciones < 30) {
    $iteraciones++;
    // Obtener notificaciones nuevas (no leídas y con id > lastId)
    $nuevas = obtenerNotificacionesNoLeidas($pdo, $usuario_id, $lastId, 50);
    if (!empty($nuevas)) {
        foreach ($nuevas as $n) {
            $lastId = (int)$n['id'];
            // Construir mensaje amigable
            $mensaje = '';
            switch ($n['tipo']) {
                case 'like': $mensaje = $n['actor_nombre'] . ' le gustó tu publicación'; break;
                case 'comentario': $mensaje = $n['actor_nombre'] . ' comentó tu publicación'; break;
                case 'seguimiento': $mensaje = $n['actor_nombre'] . ' comenzó a seguirte'; break;
                case 'mencion': $mensaje = $n['actor_nombre'] . ' te mencionó'; break;
                default: $mensaje = 'Nueva notificación';
            }
            $payload = [
                'id' => (int)$n['id'],
                'tipo' => $n['tipo'],
                'actor' => [
                    'nombre' => $n['actor_nombre'],
                    'username' => $n['actor_username'],
                    'foto' => $n['actor_foto']
                ],
                'publicacion_id' => $n['publicacion_id'] ?? null,
                'comentario_id' => $n['comentario_id'] ?? null,
                'mensaje' => $mensaje,
                'timestamp' => $n['fecha_notificacion']
            ];
            echo 'id: ' . $lastId . "\n";
            echo 'event: notificacion' . "\n";
            echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\n";
        }
        // También enviar contador actualizado
        $contador = contarNotificacionesNoLeidas($pdo, $usuario_id);
        echo 'event: contador' . "\n";
        echo 'data: ' . json_encode(['total' => $contador]) . "\n\n";
        @ob_flush();
        @flush();
    }
    // Pequeña espera antes de la siguiente iteración
    sleep(2);
}
// Cerrar conexión (el navegador reconectará automáticamente)
exit();
