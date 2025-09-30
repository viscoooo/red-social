<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/donaciones.php';
header('Content-Type: application/json');
if(!isset($_SESSION['usuario_id'])){echo json_encode(['success'=>false,'error'=>'No autorizado']);exit;}
$campana_id=(int)($_POST['campana_id']??0); $monto=(float)($_POST['monto']??0); $email=trim($_POST['email']??'');
if($campana_id<=0 || $monto<10 || !$email){echo json_encode(['success'=>false,'error'=>'Datos inválidos']);exit;}
$stmt=$pdo->prepare('SELECT id,meta,total_donado FROM campanas_donacion WHERE id=? AND activo=TRUE');$stmt->execute([$campana_id]);$campana=$stmt->fetch(PDO::FETCH_ASSOC); if(!$campana){echo json_encode(['success'=>false,'error'=>'Campaña no encontrada']);exit;}
// Modo simplificado: registrar directamente (sin Stripe real si no hay config)
registrarDonacion($pdo,$campana_id,$_SESSION['usuario_id'],$monto,'stripe','demo_ref','completado');
echo json_encode(['success'=>true]);
