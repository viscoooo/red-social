<?php
/**
 * Sistema de autenticación mejorado
 * Archivo: includes/auth.php
 */
require_once __DIR__.'/db.php';
require_once __DIR__.'/sesion.php';

function verificarCredenciales($pdo, $email, $password) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Email inválido'];
    }
    $stmt = $pdo->prepare("SELECT id, nombre, username, email, password FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario || !password_verify($password, $usuario['password'])) {
        return ['success' => false, 'message' => 'Credenciales incorrectas'];
    }
    return ['success'=>true,'usuario'=>[
        'id'=>$usuario['id'],
        'nombre'=>$usuario['nombre'],
        'username'=>$usuario['username'],
        'email'=>$usuario['email']
    ]];
}

function registrarUsuario($pdo, $nombre, $username, $email, $password) {
    $errores = [];
    if (empty(trim($nombre)) || strlen($nombre) < 2 || strlen($nombre) > 100) $errores[]='El nombre debe tener entre 2 y 100 caracteres';
    if (empty(trim($username)) || strlen($username) < 3 || strlen($username) > 30) $errores[]='El nombre de usuario debe tener entre 3 y 30 caracteres';
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) $errores[]='El nombre de usuario solo puede contener letras, números y guiones bajos';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[]='Email inválido';
    if (strlen($password) < 6) $errores[]='La contraseña debe tener al menos 6 caracteres';
    if ($errores) return ['success'=>false,'errores'=>$errores];
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? OR username = ?");
    $stmt->execute([$email,$username]);
    if ($stmt->rowCount()>0) return ['success'=>false,'errores'=>['El email o nombre de usuario ya están en uso']];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, username, email, password, biografia, ubicacion, activo) VALUES (?,?,?,?,?,?,TRUE)");
    $stmt->execute([trim($nombre),trim($username),trim($email),$hash,'Nuevo en ALASKA','']);
        return ['success'=>true,'usuario_id'=>$pdo->lastInsertId()];
    } catch (PDOException $e) {
        error_log('Error registrarUsuario: '.$e->getMessage());
        return ['success'=>false,'errores'=>['Error al crear la cuenta. Intenta más tarde']];
    }
}

/**
 * Redirigir si no está autenticado (optimizado: no reconsulta BD aquí)
 * Solo asegura que la sesión existe y expone $usuario desde sesión.
 */
function redirigirSiNoAutenticado() {
    if (!estaAutenticado()) { redirect_to('login.php'); }
    // Cargar datos mínimos desde la sesión (ya establecidos en iniciarSesionUsuario)
    global $usuario;
    if (!isset($usuario)) {
        $usuario = obtenerUsuarioSesion(); // No provoca consulta si la sesión ya guarda los datos
    }
}
function redirigirSiAutenticado() {
    if (estaAutenticado()) { redirect_to('index.php'); }
}
