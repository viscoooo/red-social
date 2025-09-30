<?php
require_once 'includes/header.php';
require_once 'includes/consejos.php';
redirigirSiNoAutenticado();
$tipo_mascota = $_GET['tipo'] ?? '';
$problema = $_GET['problema'] ?? '';
$consejo = null;
if ($tipo_mascota && $problema) {
    $consejo = generarConsejoInteligente($tipo_mascota, $problema);
}
?>
<div class="container">
    <aside class="sidebar">
        <a href="index.php" class="sidebar-item"><i class="fas fa-home"></i><span>Inicio</span></a>
        <a href="consejos.php" class="sidebar-item active"><i class="fas fa-lightbulb"></i><span>Consejos</span></a>
    </aside>
    <main class="main-content">
        <div class="consejos-container">
            <h2 style="color: var(--verde-principal); margin-bottom: 1.5rem;"><i class="fas fa-lightbulb"></i> Consejos Inteligentes</h2>
            <div class="consejos-form">
                <form method="GET">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem;">
                        <div class="form-group">
                            <label>Tipo de mascota:</label>
                            <select name="tipo" class="form-control" required onchange="this.form.submit()">
                                <option value="">Selecciona...</option>
                                <option value="perro" <?= $tipo_mascota==='perro'?'selected':''; ?>>Perro</option>
                                <option value="gato" <?= $tipo_mascota==='gato'?'selected':''; ?>>Gato</option>
                                <option value="general" <?= $tipo_mascota==='general'?'selected':''; ?>>General</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Problema o tema:</label>
                            <select name="problema" class="form-control" required>
                                <option value="">Selecciona...</option>
                                <?php if ($tipo_mascota==='perro'): ?>
                                    <option value="ansiedad" <?= $problema==='ansiedad'?'selected':''; ?>>Ansiedad</option>
                                    <option value="entrenamiento" <?= $problema==='entrenamiento'?'selected':''; ?>>Entrenamiento</option>
                                    <option value="alimentacion" <?= $problema==='alimentacion'?'selected':''; ?>>Alimentación</option>
                                    <option value="salud" <?= $problema==='salud'?'selected':''; ?>>Salud</option>
                                <?php elseif ($tipo_mascota==='gato'): ?>
                                    <option value="estrés" <?= $problema==='estrés'?'selected':''; ?>>Estrés</option>
                                    <option value="alimentacion" <?= $problema==='alimentacion'?'selected':''; ?>>Alimentación</option>
                                    <option value="socializacion" <?= $problema==='socializacion'?'selected':''; ?>>Socialización</option>
                                <?php else: ?>
                                    <option value="adopcion" <?= $problema==='adopcion'?'selected':''; ?>>Adopción</option>
                                    <option value="emergencia" <?= $problema==='emergencia'?'selected':''; ?>>Emergencia</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fas fa-search"></i> Obtener Consejo</button>
                </form>
            </div>
            <?php if ($consejo): ?>
                <div class="consejo-card">
                    <div class="consejo-header"><h3>Consejo para <?= htmlspecialchars($tipo_mascota) ?> - <?= htmlspecialchars(ucfirst($problema)) ?></h3></div>
                    <div class="consejo-content">
                        <div class="consejo-main"><i class="fas fa-paw"></i><p><?= htmlspecialchars($consejo['consejo']) ?></p></div>
                        <div class="consejo-tips"><h4>Consejos adicionales:</h4><ul><?php foreach($consejo['tips'] as $tip): ?><li><?= htmlspecialchars($tip) ?></li><?php endforeach; ?></ul></div>
                        <div class="consejo-vet"><h4>⚠️ ¿Cuándo consultar a un veterinario?</h4><p><?= htmlspecialchars($consejo['cuando_consultar_vet']) ?></p></div>
                    </div>
                </div>
            <?php elseif ($tipo_mascota || $problema): ?>
                <div class="empty-state"><i class="fas fa-lightbulb" style="font-size:3rem;color:var(--gris-medio);margin-bottom:1rem;"></i><h3>No encontramos consejos para esa combinación</h3><p>Intenta con otras opciones o contacta un veterinario</p></div>
            <?php else: ?>
                <div class="consejos-intro"><p>Selecciona el tipo de mascota y el problema o tema para obtener recomendaciones basadas en conocimientos veterinarios y de comportamiento animal.</p></div>
            <?php endif; ?>
        </div>
    </main>
</div>
<style>
.consejos-container{background:#fff;border-radius:var(--borde-radius);padding:2rem;box-shadow:var(--sombra);} .consejos-form{background:var(--gris-claro);padding:1.5rem;border-radius:var(--borde-radius);margin-bottom:2rem;} .consejo-card{background:linear-gradient(135deg,rgba(46,204,113,.1),rgba(230,126,34,.1));border-radius:var(--borde-radius);overflow:hidden;margin-bottom:2rem;} .consejo-header{background:linear-gradient(135deg,var(--verde-principal),var(--naranja-principal));color:#fff;padding:1.5rem;} .consejo-content{padding:1.5rem;} .consejo-main{display:flex;gap:1rem;margin-bottom:1.5rem;padding:1rem;background:rgba(255,255,255,.7);border-radius:8px;} .consejo-main i{font-size:2rem;color:var(--verde-principal);} .consejo-tips{margin-bottom:1.5rem;padding:1rem;background:rgba(46,204,113,.1);border-left:4px solid var(--verde-principal);border-radius:8px;} .consejo-vet{padding:1rem;background:rgba(231,76,60,.1);border-left:4px solid var(--naranja-principal);border-radius:8px;} .empty-state{text-align:center;padding:2rem;}
</style>
</body></html>