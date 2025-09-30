<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/config.php';
require_once 'includes/2fa.php';
if(!isset($_SESSION['2fa_usuario_id'])){ redirect_to('login.php'); }
$mensaje='';$tipo_mensaje='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $codigo=$_POST['codigo']??''; $secret=$_SESSION['2fa_secret']??''; 
    if(verificarCodigo2FA($secret,$codigo)){
        $usuario_id=$_SESSION['2fa_usuario_id'];
        $stmt=$pdo->prepare('SELECT nombre, username FROM usuarios WHERE id=?');
        $stmt->execute([$usuario_id]);
        $u=$stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['usuario_id']=$usuario_id; $_SESSION['usuario_nombre']=$u['nombre']; $_SESSION['usuario_username']=$u['username'];
        unset($_SESSION['2fa_usuario_id'],$_SESSION['2fa_secret']);
    redirect_to('index.php');
    } else { $mensaje='Código incorrecto'; $tipo_mensaje='error'; }
}
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Verificar 2FA - ALASKA</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><link rel="stylesheet" href="css/estilo.css"></head><body><div class="auth-container"><h1 class="auth-title"><i class="fas fa-shield-alt" style="color:#2ecc71"></i> Verificación en Dos Pasos</h1><?php if($mensaje): ?><div class="alert alert-<?= $tipo_mensaje ?>"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?><p style="text-align:center;margin-bottom:1.5rem;">Ingresa el código de 6 dígitos de tu app de autenticación</p><form method="POST"><div class="form-group"><label for="codigo">Código</label><input type="text" id="codigo" name="codigo" class="form-control" maxlength="6" required oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div><button type="submit" class="btn btn-primary" style="width:100%">Verificar</button></form><div class="auth-links" style="margin-top:1.5rem;"><p><a href="login.php">Volver</a></p></div></div></body></html>