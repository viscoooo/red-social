<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || !isset($_POST['publicacion_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$usuario_id = (int)$_SESSION['usuario_id'];
$publicacion_id = obtenerIdSeguro($_POST['publicacion_id']);

if ($publicacion_id === null) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit();
}

$existe = $pdo->prepare("SELECT id FROM publicaciones WHERE id = ?");
$existe->execute([$publicacion_id]);
if ($existe->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'La publicación no existe']);
    exit();
}

$guardado = toggleGuardado($pdo, $usuario_id, $publicacion_id);

echo json_encode([
    'success' => true,
    'guardado' => $guardado
]);
