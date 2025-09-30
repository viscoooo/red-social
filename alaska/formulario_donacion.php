<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/donaciones.php';
if(!isset($_SESSION['usuario_id'])) die('No autorizado');
$campana_id=(int)($_GET['campana']??0); if($campana_id<=0) die('Campaña inválida');
$stmt=$pdo->prepare("SELECT cd.*,me.nombre as mascota_nombre,me.tipo as mascota_tipo,u.nombre as creador_nombre FROM campanas_donacion cd JOIN mascotas_emergencia me ON cd.emergencia_id=me.id JOIN usuarios u ON me.usuario_id=u.id WHERE cd.id=? AND cd.activo=TRUE");
$stmt->execute([$campana_id]); $campana=$stmt->fetch(PDO::FETCH_ASSOC); if(!$campana) die('No encontrada');
?>
<h3><?= htmlspecialchars($campana['titulo']) ?></h3>
<p><strong>Mascota:</strong> <?= htmlspecialchars($campana['mascota_nombre']) ?></p>
<p><strong>Meta:</strong> $<?= number_format($campana['meta'],2) ?> <strong>Recaudado:</strong> $<?= number_format($campana['total_donado'],2) ?></p>
<form id="formDonacion" method="POST">
 <input type="hidden" name="campana_id" value="<?= $campana_id ?>">
 <div class="form-group"><label>Monto ($)</label><div class="donacion-montos"><button type="button" class="monto-btn" onclick="selMonto(50)">$50</button><button type="button" class="monto-btn" onclick="selMonto(100)">$100</button><button type="button" class="monto-btn" onclick="selMonto(200)">$200</button><button type="button" class="monto-btn" onclick="selMonto(500)">$500</button></div><input type="number" id="monto" name="monto" class="form-control" min="10" step="10" required></div>
 <div class="form-group"><label>Correo</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_SESSION['usuario_email']??'') ?>" required></div>
 <button type="submit" class="btn btn-primary"><i class="fas fa-heart"></i> Donar</button>
</form>
<style>.donacion-montos{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;} .monto-btn{padding:.5rem 1rem;border:2px solid var(--gris-medio);background:none;border-radius:30px;cursor:pointer;} .monto-btn.active, .monto-btn:hover{border-color:var(--verde-principal);background:var(--verde-principal);color:#fff;}</style>
<script>function selMonto(m){document.getElementById('monto').value=m;document.querySelectorAll('.monto-btn').forEach(b=>b.classList.remove('active'));event.target.classList.add('active');}
 document.getElementById('formDonacion').addEventListener('submit',e=>{e.preventDefault();const fd=new FormData(e.target);fetch('procesar_donacion.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success){alert('Donación registrada (modo test)');location.reload();}else alert('Error: '+d.error);});});</script>