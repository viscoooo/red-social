<?php
/**
 * Listado de casos de emergencia: mascotas perdidas y adopción.
 */
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/funciones.php';
redirigirSiNoAutenticado();

$tipo = $_GET['tipo'] ?? 'todas'; // todas | perdida | adopcion
$valid = ['todas','perdida','adopcion'];
if(!in_array($tipo,$valid,true)) $tipo='todas';

$params=[];
$sql = "SELECT m.*, u.nombre, u.username, u.foto_perfil FROM mascotas_emergencia m JOIN usuarios u ON m.usuario_id=u.id";
if($tipo!=='todas'){ $sql .= " WHERE m.tipo = ?"; $params[]=$tipo; }
$sql .= " ORDER BY m.fecha_creacion DESC LIMIT 200";
$stmt=$pdo->prepare($sql);$stmt->execute($params);$casos=$stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__.'/includes/header.php';
?>
<div class="container">
    <main class="main-content" style="max-width:1000px;margin:0 auto;">
        <div class="post" style="padding:1.2rem;">
            <h2 style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
                <span>Emergencias de Mascotas</span>
                <a href="reportar_emergencia.php" class="btn btn-primary" style="text-decoration:none;">Reportar caso</a>
            </h2>
            <div style="margin:1rem 0; display:flex; gap:.5rem; flex-wrap:wrap;">
                <?php foreach($valid as $t): $active = $t===$tipo? 'background:var(--verde-principal);color:#fff;font-weight:600;':''; ?>
                    <a href="?tipo=<?= $t ?>" style="text-decoration:none; padding:6px 14px; border-radius:20px; background:var(--gris-claro); font-size:0.85rem; <?= $active ?>"><?= ucfirst($t) ?></a>
                <?php endforeach; ?>
            </div>
            <?php if(empty($casos)): ?>
                <p style="color:var(--gris-oscuro);">No hay casos registrados.</p>
            <?php else: ?>
                <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:1rem;">
                    <?php foreach($casos as $c): ?>
                    <div style="background:#fff; border:1px solid var(--gris-medio); border-radius:12px; overflow:hidden; display:flex; flex-direction:column; position:relative;">
                        <div style="padding:8px 12px; display:flex; align-items:center; gap:8px; background:linear-gradient(135deg,var(--verde-principal),var(--naranja-principal)); color:#fff;">
                            <div style="width:34px;height:34px;border-radius:50%;overflow:hidden;background:rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-weight:600;">
                                <?php if($c['foto_perfil'] && file_exists('uploads/'. $c['foto_perfil'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($c['foto_perfil']) ?>" style="width:100%;height:100%;object-fit:cover;" alt="perfil">
                                <?php else: ?>
                                    <?= substr($c['nombre'],0,1) ?>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:0.75rem; line-height:1.2;">
                                <strong>@<?= htmlspecialchars($c['username']) ?></strong><br>
                                <span><?= tiempoTranscurrido($c['fecha_creacion']) ?></span>
                            </div>
                            <span style="margin-left:auto; font-size:0.65rem; background:#fff; color:var(--naranja-principal); padding:3px 8px; border-radius:14px; font-weight:700;">
                                <?= $c['tipo']==='perdida'? 'PERDIDA':'ADOPCIÓN' ?>
                            </span>
                        </div>
                        <?php if($c['imagen'] && file_exists('uploads/'. $c['imagen'])): ?>
                            <img data-src="uploads/<?= htmlspecialchars($c['imagen']) ?>" alt="Mascota" style="width:100%;height:180px;object-fit:cover;" class="lazy">
                        <?php else: ?>
                            <div style="height:180px;display:flex;align-items:center;justify-content:center;font-size:3rem;color:var(--gris-medio);"><i class="fas fa-paw"></i></div>
                        <?php endif; ?>
                        <div style="padding:12px; flex:1; display:flex; flex-direction:column;">
                            <?php if(!empty($c['nombre_mascota'])): ?>
                                <div style="font-weight:700; color:var(--naranja-principal); font-size:1rem; margin-bottom:4px;">
                                    <i class="fas fa-bone"></i> <?= htmlspecialchars($c['nombre_mascota']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if(!empty($c['ubicacion'])): ?>
                                <div style="font-size:0.75rem; color:var(--gris-oscuro); margin-bottom:6px;">
                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($c['ubicacion']) ?>
                                    <button class="map-toggle-btn" data-map-target="map-emergencia-<?= $c['id'] ?>" data-ubicacion="<?= htmlspecialchars($c['ubicacion']) ?>" style="margin-left:6px;">Mapa</button>
                                </div>
                                <div id="map-emergencia-<?= $c['id'] ?>" class="emergencia-map" style="display:none;"></div>
                            <?php endif; ?>
                            <div style="font-size:0.8rem; line-height:1.3; flex:1;"><?= nl2br(htmlspecialchars(substr($c['descripcion'],0,260))) ?><?= strlen($c['descripcion'])>260? '...':''; ?></div>
                        </div>
                        <div style="padding:10px 12px; border-top:1px solid var(--gris-medio); display:flex; gap:8px;">
                            <a href="reportar.php?tipo=usuario&id=<?= $c['usuario_id'] ?>" style="font-size:0.7rem; text-decoration:none; background:var(--gris-claro); padding:4px 10px; border-radius:16px;">Reportar usuario</a>
                            <a href="reportar.php?tipo=publicacion&id=<?= $c['id'] ?>" style="font-size:0.7rem; text-decoration:none; background:var(--gris-claro); padding:4px 10px; border-radius:16px;">Reportar caso</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body></html>