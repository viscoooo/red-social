<?php
/**
 * Formulario para crear un caso de emergencia (mascota perdida o adopción).
 */
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/funciones.php';
redirigirSiNoAutenticado();

$msg=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST') {
    $tipo = $_POST['tipo'] ?? 'perdida';
    $nombre = trim($_POST['nombre_mascota'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    if(!in_array($tipo,['perdida','adopcion'],true)) { $err='Tipo inválido'; }
    elseif(strlen($descripcion)<10){ $err='La descripción es muy corta'; }
    else {
        $imagen = null;
        if(!empty($_FILES['imagen']['name'])){
            $fn = time().'_'.preg_replace('/[^a-zA-Z0-9._-]/','_', $_FILES['imagen']['name']);
            $dest = __DIR__.'/uploads/'.$fn;
            if(move_uploaded_file($_FILES['imagen']['tmp_name'],$dest)) { $imagen = $fn; }
        }
        try {
            $stmt = $pdo->prepare("INSERT INTO mascotas_emergencia (usuario_id, tipo, nombre_mascota, descripcion, ubicacion, imagen) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['usuario_id'], $tipo, $nombre ?: null, $descripcion, $ubicacion ?: null, $imagen]);
            $msg='Caso registrado correctamente.';
        } catch(PDOException $e){ $err='Error al guardar el caso'; }
    }
}
require_once __DIR__.'/includes/header.php';
?>
<div class="container">
    <main class="main-content" style="max-width:650px;margin:0 auto;">
        <div class="post" style="padding:1.5rem;">
            <h2 style="margin-bottom:1rem;">Reportar Emergencia</h2>
            <?php if($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Tipo</label>
                    <select name="tipo" class="form-control" required>
                        <option value="perdida">Mascota Perdida</option>
                        <option value="adopcion">En Adopción</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nombre de la mascota (opcional)</label>
                    <input type="text" name="nombre_mascota" class="form-control" maxlength="100">
                </div>
                <div class="form-group">
                    <label>Ubicación (ciudad / zona) (opcional)</label>
                    <input type="text" name="ubicacion" class="form-control" maxlength="255">
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="5" required placeholder="Describe la situación, rasgos distintivos, contacto, etc."></textarea>
                </div>
                <div class="form-group">
                    <label>Imagen (opcional)</label>
                    <input type="file" name="imagen" accept="image/*" class="form-control">
                </div>
                <button class="btn btn-primary" type="submit">Guardar</button>
                <a href="emergencia.php" class="btn btn-secondary" style="text-decoration:none;">Volver</a>
            </form>
        </div>
    </main>
</div>
</body></html>