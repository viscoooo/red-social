<?php
// AJAX: editar publicación
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];
$publicacion_id = obtenerIdSeguro($_POST['publicacion_id'] ?? null);
$contenido = trim($_POST['contenido'] ?? '');
$tipo = $_POST['tipo'] ?? 'general';
$nombre_mascota = trim($_POST['nombre_mascota'] ?? '');
$ubicacion = trim($_POST['ubicacion'] ?? '');

$tipos_validos = ['general','mascota','evento','consejo'];
if (!in_array($tipo, $tipos_validos, true)) { $tipo = 'general'; }
if ($tipo !== 'mascota') { $nombre_mascota = null; }

if ($publicacion_id === null) {
    echo json_encode(['success' => false, 'message' => 'Publicación inválida']);
    exit;
}

if ($contenido === '') {
    echo json_encode(['success' => false, 'message' => 'Contenido vacío']);
    exit;
}

$ok = editarPublicacion($pdo, $publicacion_id, $usuario_id, $contenido, $tipo, $nombre_mascota ?: null, $ubicacion ?: null);
echo json_encode(['success' => (bool)$ok]);
?>