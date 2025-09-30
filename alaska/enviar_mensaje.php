<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || !isset($_POST['destinatario_id']) || !isset($_POST['contenido'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$remitente_id = (int)$_SESSION['usuario_id'];
$destinatario_id = (int)$_POST['destinatario_id'];
$contenido = trim((string)$_POST['contenido']);

if (!validarId($destinatario_id) || $destinatario_id === $remitente_id) {
    echo json_encode(['success' => false, 'message' => 'Destinatario inválido']);
    exit();
}

if ($contenido === '') {
    echo json_encode(['success' => false, 'message' => 'El mensaje no puede estar vacío']);
    exit();
}

if (enviarMensaje($pdo, $remitente_id, $destinatario_id, $contenido)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al enviar el mensaje']);
}
