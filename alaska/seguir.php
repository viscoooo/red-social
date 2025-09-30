<?php
/**
 * Endpoint AJAX para seguir/dejar de seguir a un usuario.
 * Devuelve JSON con el estado final (following).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id']) || !isset($_POST['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$seguidor_id = (int)$_SESSION['usuario_id'];
$seguido_id = obtenerIdSeguro($_POST['usuario_id']);

if ($seguido_id === null) {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
    exit();
}

if ($seguidor_id === $seguido_id) {
    echo json_encode(['success' => false, 'message' => 'No puedes seguirte a ti mismo']);
    exit();
}

$existeUsuario = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
$existeUsuario->execute([$seguido_id]);
if ($existeUsuario->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'El usuario no existe']);
    exit();
}

$stmt = $pdo->prepare("SELECT id FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
$stmt->execute([$seguidor_id, $seguido_id]);

if ($stmt->rowCount() > 0) {
    $stmt = $pdo->prepare("DELETE FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
    $stmt->execute([$seguidor_id, $seguido_id]);
    $following = false;
} else {
    $stmt = $pdo->prepare("INSERT INTO seguidores (seguidor_id, seguido_id) VALUES (?, ?)");
    $stmt->execute([$seguidor_id, $seguido_id]);
    $following = true;
    // Notificar al usuario seguido
    crearNotificacion($pdo, $seguido_id, 'seguimiento', $seguidor_id, null, null);
}

echo json_encode([
    'success' => true,
    'following' => $following
]);
