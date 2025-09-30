<?php
/**
 * Endpoint AJAX para dar/quitar like a una publicación.
 * Devuelve JSON con el estado final (liked) y el total de likes.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id']) || !isset($_POST['publicacion_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$usuario_id = (int)$_SESSION['usuario_id'];
$publicacion_id = obtenerIdSeguro($_POST['publicacion_id']);

if ($publicacion_id === null) {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
    exit();
}

$pubStmt = $pdo->prepare("SELECT usuario_id FROM publicaciones WHERE id = ?");
$pubStmt->execute([$publicacion_id]);
$autorId = (int)($pubStmt->fetchColumn() ?: 0);

if ($autorId === 0) {
    echo json_encode(['success' => false, 'message' => 'La publicación no existe']);
    exit();
}

$stmt = $pdo->prepare("SELECT id FROM likes WHERE usuario_id = ? AND publicacion_id = ?");
$stmt->execute([$usuario_id, $publicacion_id]);

if ($stmt->rowCount() > 0) {
    $stmt = $pdo->prepare("DELETE FROM likes WHERE usuario_id = ? AND publicacion_id = ?");
    $stmt->execute([$usuario_id, $publicacion_id]);
    $liked = false;
} else {
    $stmt = $pdo->prepare("INSERT INTO likes (usuario_id, publicacion_id) VALUES (?, ?)");
    $stmt->execute([$usuario_id, $publicacion_id]);
    $liked = true;
    if ($autorId) {
        crearNotificacion($pdo, $autorId, 'like', $usuario_id, $publicacion_id, null);
    }
}

$likes = contarLikes($pdo, $publicacion_id);

echo json_encode([
    'success' => true,
    'liked' => $liked,
    'likes' => (int)$likes
]);
