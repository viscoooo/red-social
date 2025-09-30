<?php
require_once 'includes/header.php';
require_once 'includes/comunidades.php';
redirigirSiNoAutenticado();
$accion=$_GET['accion']??'listar';
$comunidad_id=(int)($_GET['id']??0);
if($accion==='crear' && $_SERVER['REQUEST_METHOD']==='POST'){
    $nombre=trim($_POST['nombre']??'');
    $ubicacion=trim($_POST['ubicacion']??'');
    $descripcion=trim($_POST['descripcion']??'');
  if(crearComunidad($pdo,$nombre,$ubicacion,$descripcion,$usuario['id'])){ redirect_to('comunidades.php'); } else { $mensaje='Error al crear la comunidad'; $tipo_mensaje='error'; }
}
if($accion==='unir' && $comunidad_id>0){ if(unirComunidad($pdo,$comunidad_id,$usuario['id'])){ redirect_to('comunidad.php?id='.$comunidad_id); } }
$comunidades_cercanas=!empty($usuario['ubicacion'])? obtenerComunidadesCercanas($pdo,$usuario['ubicacion'],6):[];
$mis_comunidades=obtenerComunidadesUsuario($pdo,$usuario['id']);
?>
<div class="container">
<aside class="sidebar">
 <a href="index.php" class="sidebar-item"><i class="fas fa-home"></i><span>Inicio</span></a>
 <a href="comunidades.php" class="sidebar-item active"><i class="fas fa-users"></i><span>Comunidades</span></a>
</aside>
<main class="main-content">
 <div class="comunidades-header">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
   <h2 style="color:var(--verde-principal);"><i class="fas fa-users"></i> Comunidades Locales</h2>
   <button class="btn btn-primary" onclick="window.location.href='comunidades.php?accion=crear'"><i class="fas fa-plus"></i> Crear Comunidad</button>
  </div>
  <?php if(isset($mensaje)): ?><div class="alert alert-<?= $tipo_mensaje ?>"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
  <?php if($accion==='crear'): ?>
   <div class="crear-comunidad-form">
    <form method="POST">
     <div class="form-group"><label>Nombre *</label><input type="text" name="nombre" class="form-control" required></div>
     <div class="form-group"><label>Ubicación *</label><input type="text" name="ubicacion" class="form-control" required></div>
     <div class="form-group"><label>Descripción</label><textarea name="descripcion" class="form-control" rows="3"></textarea></div>
     <div style="display:flex;gap:1rem;margin-top:1.5rem;"><button type="submit" class="btn btn-primary">Crear</button><a href="comunidades.php" class="btn btn-secondary">Cancelar</a></div>
    </form>
   </div>
  <?php else: ?>
   <?php if($mis_comunidades): ?>
    <div class="mis-comunidades"><h3>Mis Comunidades (<?= count($mis_comunidades) ?>)</h3><div class="comunidades-grid">
     <?php foreach($mis_comunidades as $c): ?>
      <a href="comunidad.php?id=<?= $c['id'] ?>" class="comunidad-card">
        <div class="comunidad-info"><h4><?= htmlspecialchars($c['nombre']) ?></h4><p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($c['ubicacion']) ?></p><p><i class="fas fa-users"></i> <?= $c['miembros_count'] ?> miembros</p></div>
        <div class="comunidad-rol <?= $c['rol'] ?>"><?= ucfirst($c['rol']) ?></div>
      </a>
     <?php endforeach; ?>
    </div></div>
   <?php endif; ?>
   <?php if($comunidades_cercanas): ?>
    <div class="comunidades-cercanas"><h3>Comunidades Cercanas</h3><div class="comunidades-grid">
     <?php foreach($comunidades_cercanas as $c): $ya=false; foreach($mis_comunidades as $m){ if($m['id']==$c['id']){$ya=true;break;} } ?>
      <div class="comunidad-card <?= $ya?'miembro':'' ?>">
        <div class="comunidad-info"><h4><?= htmlspecialchars($c['nombre']) ?></h4><p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($c['ubicacion']) ?></p><p><i class="fas fa-users"></i> <?= $c['miembros_count'] ?> miembros</p><p class="comunidad-creador">por <?= htmlspecialchars($c['creador_nombre']) ?></p></div>
        <?php if(!$ya): ?><a href="comunidades.php?accion=unir&id=<?= $c['id'] ?>" class="btn btn-primary btn-unirse">Unirse</a><?php endif; ?>
      </div>
     <?php endforeach; ?>
    </div></div>
   <?php endif; ?>
   <?php if(!$mis_comunidades && !$comunidades_cercanas): ?>
    <div class="empty-state"><i class="fas fa-users" style="font-size:3rem;color:var(--gris-medio);margin-bottom:1rem;"></i><h3>No hay comunidades</h3><p>Crea la primera comunidad o actualiza tu ubicación.</p><button class="btn btn-primary" onclick="window.location.href='comunidades.php?accion=crear'">Crear Comunidad</button></div>
   <?php endif; ?>
  <?php endif; ?>
 </div>
</main>
</div>
<style>.comunidades-header{background:#fff;border-radius:var(--borde-radius);padding:2rem;box-shadow:var(--sombra);} .crear-comunidad-form{background:var(--gris-claro);padding:1.5rem;border-radius:var(--borde-radius);margin-top:1.5rem;} .comunidades-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.5rem;margin-top:1.5rem;} .comunidad-card{background:#fff;border-radius:var(--borde-radius);padding:1.5rem;box-shadow:var(--sombra);text-decoration:none;color:inherit;display:flex;flex-direction:column;justify-content:space-between;transition:var(--transicion);border:2px solid var(--gris-medio);} .comunidad-card.miembro{border-color:var(--verde-principal);background:rgba(46,204,113,.05);} .comunidad-card:hover{transform:translateY(-5px);box-shadow:0 8px 25px rgba(0,0,0,.15);} .comunidad-rol{display:inline-block;padding:.2rem .6rem;border-radius:15px;font-size:.8rem;font-weight:bold;margin-top:1rem;text-align:center;} .comunidad-rol.administrador{background:rgba(231,76,60,.2);color:var(--naranja-principal);} .comunidad-rol.moderador{background:rgba(52,152,219,.2);color:#3498db;} .comunidad-rol.miembro{background:rgba(46,204,113,.2);color:var(--verde-principal);} .btn-unirse{margin-top:auto;width:100%;text-align:center;}</style>
</body></html>