<?php
/**
 * Manejador AJAX para agregar comentarios a publicaciones
 * Recibe el ID de la publicación y el contenido del comentario
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación y datos recibidos
if (!isset($_SESSION['usuario_id']) || !isset($_POST['publicacion_id']) || !isset($_POST['contenido'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$usuario_id = (int)$_SESSION['usuario_id'];
$publicacion_id = obtenerIdSeguro($_POST['publicacion_id']);
$contenido = trim($_POST['contenido']);

if ($publicacion_id === null) {
    echo json_encode(['success' => false, 'message' => 'Publicación inválida']);
    exit();
}

// Validar contenido del comentario
if (empty($contenido)) {
    echo json_encode(['success' => false, 'message' => 'El comentario no puede estar vacío']);
    exit();
}

if (strlen($contenido) > 500) {
    echo json_encode(['success' => false, 'message' => 'El comentario es demasiado largo (máximo 500 caracteres)']);
    exit();
}

// Verificar que la publicación existe (evita comentar en IDs inválidos)
$stmt = $pdo->prepare("SELECT id FROM publicaciones WHERE id = ?");
$stmt->execute([$publicacion_id]);
if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Publicación no encontrada']);
    exit();
}

// Moderación IA
$analisis = analizarContenidoModeracion($contenido);
if ($analisis['is_toxic']) {
    echo json_encode(['success' => false, 'message' => 'El comentario contiene contenido inapropiado']);
    exit();
}

try {
    if (agregarComentario($pdo, $publicacion_id, $usuario_id, $contenido)) {
        // Notificar al autor de la publicación
        $autor = $pdo->prepare("SELECT usuario_id FROM publicaciones WHERE id = ?");
        $autor->execute([$publicacion_id]);
        $autorId = (int)($autor->fetchColumn() ?: 0);
        if ($autorId) {
            crearNotificacion($pdo, $autorId, 'comentario', $usuario_id, $publicacion_id, null);
        }
        // Obtener info del usuario para construir el comentario en el cliente
            $usuarioActual = obtenerUsuarioPorId($pdo, $usuario_id);
            $totalComentarios = (int)contarComentarios($pdo, $publicacion_id);

            echo json_encode([
                'success' => true,
                'comentario' => [
                    'nombre' => $usuarioActual['nombre'] ?? 'Usuario',
                    'foto_perfil' => $usuarioActual['foto_perfil'] ?? null,
                    'contenido' => $contenido,
                    'fecha' => date('Y-m-d H:i:s')
                ],
                'totalComentarios' => $totalComentarios
            ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el comentario']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
}
?>