<?php
/**
 * Chat individual con otro usuario
 */
require_once __DIR__ . '/includes/header.php';
redirigirSiNoAutenticado();

$otro_usuario_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!validarId($otro_usuario_id) || $otro_usuario_id === (int)$usuario['id']) {
    die('Usuario no válido');
}

$otro_usuario = obtenerUsuarioPorId($pdo, $otro_usuario_id);
if (!$otro_usuario) {
    die('Usuario no encontrado');
}

$mensajes = obtenerMensajesConversacion($pdo, $usuario['id'], $otro_usuario_id, 50);
?>
<div class="container">
    <aside class="sidebar">
        <a href="<?= url('index.php') ?>" class="sidebar-item">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
        </a>
        <a href="<?= url('mensajes.php') ?>" class="sidebar-item active">
            <i class="fas fa-envelope"></i>
            <span>Mensajes</span>
        </a>
        <a href="<?= url('perfil.php?id=' . $usuario['id']) ?>" class="sidebar-item">
            <i class="fas fa-user"></i>
            <span>Perfil</span>
        </a>
        <div class="divider"></div>
        <a href="<?= url('logout.php') ?>" class="sidebar-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar sesión</span>
        </a>
    </aside>

    <main class="main-content">
        <div class="post">
            <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1.5rem; padding-bottom:1rem; border-bottom:1px solid var(--gris-medio);">
                <div class="avatar" style="width:60px; height:60px;">
                    <?php if (!empty($otro_usuario['foto_perfil']) && file_exists(__DIR__ . '/uploads/' . $otro_usuario['foto_perfil'])): ?>
                        <img src="<?= url('uploads/' . htmlspecialchars($otro_usuario['foto_perfil'])) ?>" alt="Foto de perfil">
                    <?php else: ?>
                        <?= obtenerIniciales($otro_usuario['nombre']) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="post-user"><?= htmlspecialchars($otro_usuario['nombre']) ?></div>
                    <div class="post-time">@<?= htmlspecialchars($otro_usuario['username']) ?></div>
                </div>
                <a href="<?= url('perfil.php?id=' . $otro_usuario['id']) ?>" class="btn btn-secondary" style="margin-left:auto; padding:.4rem .8rem; font-size:.9rem;">
                    Ver perfil
                </a>
            </div>
            <div id="areaMensajes" style="height:400px; overflow-y:auto; padding:1rem 0; border-bottom:1px solid var(--gris-medio);">
                <?php foreach ($mensajes as $msg): ?>
                <div style="display:flex; gap:1rem; margin-bottom:1rem; <?= ($msg['remitente_id'] == $usuario['id']) ? 'flex-direction: row-reverse;' : '' ?>">
                    <div class="avatar" style="width:40px; height:40px; flex-shrink:0;">
                        <?php if (!empty($msg['remitente_foto']) && file_exists(__DIR__ . '/uploads/' . $msg['remitente_foto'])): ?>
                            <img src="<?= url('uploads/' . htmlspecialchars($msg['remitente_foto'])) ?>" alt="Foto de perfil">
                        <?php else: ?>
                            <?= obtenerIniciales($msg['remitente_nombre']) ?>
                        <?php endif; ?>
                    </div>
                    <div style="max-width:70%;">
                        <div style="background-color: <?= ($msg['remitente_id'] == $usuario['id']) ? 'var(--verde-principal)' : 'var(--gris-claro)' ?>; color: <?= ($msg['remitente_id'] == $usuario['id']) ? 'white' : 'inherit' ?>; padding:.8rem; border-radius:15px; <?= ($msg['remitente_id'] == $usuario['id']) ? 'border-bottom-right-radius:5px;' : 'border-bottom-left-radius:5px;' ?>">
                            <?= nl2br(htmlspecialchars($msg['contenido'])) ?>
                        </div>
                        <div style="font-size:.7rem; color: var(--gris-oscuro); margin-top:.3rem; text-align: <?= ($msg['remitente_id'] == $usuario['id']) ? 'right' : 'left' ?>;">
                            <?= date('H:i', strtotime($msg['fecha_envio'])) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <form id="formularioMensaje" style="display:flex; gap:1rem; margin-top:1rem;">
                <input type="text" id="contenidoMensaje" class="form-control" placeholder="Escribe un mensaje..." style="flex:1; padding:.8rem;">
                <button type="submit" class="btn btn-primary" style="padding:.8rem 1.5rem;">Enviar</button>
            </form>
        </div>
    </main>
</div>

<script>
document.getElementById('areaMensajes').scrollTop = document.getElementById('areaMensajes').scrollHeight;

document.getElementById('formularioMensaje').addEventListener('submit', function(e){
    e.preventDefault();
    const contenido = document.getElementById('contenidoMensaje').value.trim();
    if(!contenido) return;
    fetch('<?= url('enviar_mensaje.php') ?>', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`destinatario_id=<?= $otro_usuario_id ?>&contenido=${encodeURIComponent(contenido)}`})
    .then(r=>r.json())
    .then(data=>{
        if(data.success){
            const area = document.getElementById('areaMensajes');
            const nuevo = document.createElement('div');
            nuevo.innerHTML = `
                <div style="display:flex; gap:1rem; margin-bottom:1rem; flex-direction: row-reverse;">
                    <div class=\"avatar\" style=\"width:40px; height:40px; flex-shrink:0; background-color: var(--verde-principal); color:white; display:flex; align-items:center; justify-content:center; font-weight:bold;\">
                        <?= obtenerIniciales($usuario['nombre']) ?>
                    </div>
                    <div style=\"max-width:70%;\">
                        <div style=\"background-color: var(--verde-principal); color:white; padding:.8rem; border-radius:15px; border-bottom-right-radius:5px;\">${contenido}</div>
                        <div style=\"font-size:.7rem; color: var(--gris-oscuro); margin-top:.3rem; text-align:right;\">Ahora</div>
                    </div>
                </div>`;
            area.appendChild(nuevo);
            area.scrollTop = area.scrollHeight;
            document.getElementById('contenidoMensaje').value = '';
        }
    })
    .catch(()=>alert('Error al enviar el mensaje'))
});
</script>

</body>
</html>
