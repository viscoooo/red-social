<?php
/**
 * Formulario para reportar publicaciones, usuarios o comentarios.
 * Requiere autenticación.
 */
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/funciones.php';
redirigirSiNoAutenticado();

$mensaje = '';
$error = '';
$tipo_objeto = $_GET['tipo'] ?? 'publicacion'; // publicacion | usuario | comentario
$objeto_id = isset($_GET['id'])? (int)$_GET['id'] : 0;
$motivos = [
    'spam' => 'Spam / Publicidad',
    'odio' => 'Lenguaje de odio',
    'acoso' => 'Acoso',
    'contenido_inapropiado' => 'Contenido inapropiado',
    'otro' => 'Otro'
];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $tipo_objeto = $_POST['tipo_objeto'] ?? 'publicacion';
    $objeto_id = (int)($_POST['objeto_id'] ?? 0);
    $motivo = $_POST['motivo'] ?? '';
    $descripcion = trim($_POST['descripcion'] ?? '');
    if (!in_array($tipo_objeto,['publicacion','usuario','comentario'], true)) {
        $error = 'Tipo inválido';
    } elseif (!array_key_exists($motivo,$motivos)) {
        $error = 'Motivo inválido';
    } elseif ($objeto_id<=0) {
        $error = 'Identificador inválido';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO reportes (reportante_id, tipo_objeto, objeto_id, motivo, descripcion) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['usuario_id'], $tipo_objeto, $objeto_id, $motivo, $descripcion]);
            $mensaje = 'Reporte enviado. Gracias por ayudarnos a moderar.';
        } catch (PDOException $e) {
            $error = 'Error al guardar el reporte';
        }
    }
}

require_once __DIR__.'/includes/header.php';
?>
<div class="container">
    <main class="main-content" style="max-width:700px;margin:0 auto;">
        <div class="post" style="padding:1.5rem;">
            <h2 style="margin-bottom:1rem;">Reportar</h2>
            <?php if($mensaje): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
            <?php if($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="objeto_id" value="<?= $objeto_id ?>">
                <input type="hidden" name="tipo_objeto" value="<?= htmlspecialchars($tipo_objeto) ?>">
                <p style="font-size:0.9rem;color:var(--gris-oscuro);margin-bottom:1rem;">Estás reportando un elemento de tipo <strong><?= htmlspecialchars($tipo_objeto) ?></strong> (ID: <?= $objeto_id ?>)</p>
                <div class="form-group">
                    <label for="motivo">Motivo</label>
                    <select name="motivo" id="motivo" class="form-control" required>
                        <option value="" disabled selected>Selecciona un motivo</option>
                        <?php foreach($motivos as $k=>$label): ?>
                            <option value="<?= $k ?>" <?= isset($motivo)&&$motivo===$k? 'selected':''; ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción (opcional)</label>
                    <textarea name="descripcion" id="descripcion" rows="4" class="form-control" placeholder="Explica brevemente el problema..."></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Enviar reporte</button>
            </form>
        </div>
    </main>
</div>
</body></html>