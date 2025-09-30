<?php
/**
 * Búsqueda avanzada con filtros: texto, tipo de publicación, rango de fechas y ubicación parcial.
 */
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/funciones.php';
redirigirSiNoAutenticado();

$q = trim($_GET['q'] ?? '');
$tipo = $_GET['tipo'] ?? 'todos';
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$ubicacion = trim($_GET['ubicacion'] ?? '');

$tiposValidos = ['todos','general','mascota','evento','consejo'];
if(!in_array($tipo,$tiposValidos,true)) $tipo='todos';

$params = [];
$sql = "SELECT p.*, u.nombre, u.username, u.foto_perfil FROM publicaciones p JOIN usuarios u ON p.usuario_id=u.id WHERE 1=1";
if($q!==''){ $sql .= " AND (p.contenido LIKE ? OR u.nombre LIKE ? OR u.username LIKE ?)"; $like = '%'.$q.'%'; $params[]=$like; $params[]=$like; $params[]=$like; }
if($tipo!=='todos'){ $sql .= " AND p.tipo = ?"; $params[] = $tipo; }
if($ubicacion!==''){ $sql .= " AND p.ubicacion LIKE ?"; $params[] = '%'.$ubicacion.'%'; }
if($desde){ $sql .= " AND p.fecha_publicacion >= ?"; $params[] = $desde.' 00:00:00'; }
if($hasta){ $sql .= " AND p.fecha_publicacion <= ?"; $params[] = $hasta.' 23:59:59'; }
$sql .= " ORDER BY p.fecha_publicacion DESC LIMIT 100";
$stmt = $pdo->prepare($sql); $stmt->execute($params); $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__.'/includes/header.php';
?>
<div class="container">
    <main class="main-content" style="max-width:900px;margin:0 auto;">
        <div class="post" style="padding:1.2rem;">
            <h2 style="margin-bottom:1rem;">Búsqueda Avanzada</h2>
            <form method="GET" style="display:grid; gap:1rem; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); align-items:end;">
                <div style="grid-column:1/-1;">
                    <label style="font-size:0.8rem;font-weight:600;">Texto</label>
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="Palabras clave...">
                </div>
                <div>
                    <label style="font-size:0.8rem;font-weight:600;">Tipo</label>
                    <select name="tipo" class="form-control">
                        <?php foreach($tiposValidos as $t): ?>
                        <option value="<?= $t ?>" <?= $tipo===$t? 'selected':''; ?>><?= ucfirst($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.8rem;font-weight:600;">Desde</label>
                    <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>" class="form-control">
                </div>
                <div>
                    <label style="font-size:0.8rem;font-weight:600;">Hasta</label>
                    <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>" class="form-control">
                </div>
                <div>
                    <label style="font-size:0.8rem;font-weight:600;">Ubicación</label>
                    <input type="text" name="ubicacion" value="<?= htmlspecialchars($ubicacion) ?>" class="form-control" placeholder="Ciudad, zona...">
                </div>
                <div>
                    <button class="btn btn-primary" type="submit" style="width:100%;">Buscar</button>
                </div>
            </form>
        </div>
        <div class="post" style="padding:1.2rem;">
            <h3 style="margin-bottom:1rem;">Resultados (<?= count($resultados) ?>)</h3>
            <?php if(empty($resultados)): ?>
                <p style="color:var(--gris-oscuro);">No se encontraron resultados.</p>
            <?php else: ?>
                <?php foreach($resultados as $r): ?>
                <div style="border-bottom:1px solid var(--gris-medio); padding:0.8rem 0; display:flex; gap:10px;">
                    <div style="width:46px; height:46px; border-radius:50%; overflow:hidden; background:var(--verde-principal); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:600;">
                        <?php if($r['foto_perfil'] && file_exists('uploads/'. $r['foto_perfil'])): ?>
                            <img src="uploads/<?= htmlspecialchars($r['foto_perfil']) ?>" style="width:100%;height:100%;object-fit:cover;" alt="perfil">
                        <?php else: ?>
                            <?= substr($r['nombre'],0,1) ?>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1;">
                        <div style="font-weight:600;">
                            <a href="perfil.php?id=<?= $r['usuario_id'] ?>" style="text-decoration:none;color:var(--negro-suave);">
                                <?= htmlspecialchars($r['nombre']) ?> @<?= htmlspecialchars($r['username']) ?>
                            </a>
                            <span style="font-size:0.75rem; background:var(--gris-claro); padding:2px 6px; border-radius:12px; margin-left:6px;"><?= $r['tipo'] ?></span>
                        </div>
                        <?php if(!empty($r['ubicacion'])): ?>
                            <div style="font-size:0.7rem; color:var(--gris-oscuro);"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($r['ubicacion']) ?></div>
                        <?php endif; ?>
                        <div style="font-size:0.85rem; margin-top:4px;">
                            <?= nl2br(htmlspecialchars(substr($r['contenido'],0,160))) ?><?= strlen($r['contenido'])>160? '...':''; ?>
                        </div>
                        <div style="font-size:0.7rem; color:var(--gris-oscuro); margin-top:4px;">Publicado: <?= tiempoTranscurrido($r['fecha_publicacion']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
</body></html>