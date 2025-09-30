<?php
/**
 * Publicaciones guardadas
 */
require_once __DIR__ . '/includes/header.php';
redirigirSiNoAutenticado();

$publicaciones_guardadas = obtenerPublicacionesGuardadas($pdo, $usuario['id'], 20);
?>
<div class="container">
    <aside class="sidebar">
        <a href="<?= url('index.php') ?>" class="sidebar-item">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
        </a>
        <a href="<?= url('explorar.php') ?>" class="sidebar-item">
            <i class="fas fa-hashtag"></i>
            <span>Explorar</span>
        </a>
        <a href="<?= url('notificaciones.php') ?>" class="sidebar-item">
            <i class="fas fa-bell"></i>
            <span>Notificaciones</span>
        </a>
        <a href="<?= url('mensajes.php') ?>" class="sidebar-item">
            <i class="fas fa-envelope"></i>
            <span>Mensajes</span>
        </a>
        <a href="<?= url('guardados.php') ?>" class="sidebar-item active">
            <i class="fas fa-bookmark"></i>
            <span>Guardados</span>
        </a>
        <a href="<?= url('listas.php') ?>" class="sidebar-item">
            <i class="fas fa-list-alt"></i>
            <span>Listas</span>
        </a>
        <a href="<?= url('perfil.php?id=' . $usuario['id']) ?>" class="sidebar-item">
            <i class="fas fa-user"></i>
            <span>Perfil</span>
        </a>
        <a href="<?= url('ajustes.php') ?>" class="sidebar-item">
            <i class="fas fa-cog"></i>
            <span>Ajustes</span>
        </a>
        <div class="divider"></div>
        <a href="<?= url('publicar.php') ?>" class="btn btn-primary" style="padding: .8rem; margin: 0 1.2rem; text-align:center; text-decoration:none;">
            <i class="fas fa-feather-alt"></i>
            Publicar
        </a>
        <div class="divider"></div>
        <a href="<?= url('logout.php') ?>" class="sidebar-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar sesión</span>
        </a>
    </aside>

    <main class="main-content">
        <div class="post">
            <h2 style="color: var(--naranja-principal); margin-bottom:1.5rem;">
                <i class="fas fa-bookmark"></i> Publicaciones Guardadas
            </h2>
            <?php if (empty($publicaciones_guardadas)): ?>
                <div style="text-align:center; padding:2rem;">
                    <i class="far fa-bookmark" style="font-size:3rem; color: var(--gris-medio); margin-bottom:1rem;"></i>
                    <h3>No tienes publicaciones guardadas</h3>
                    <p>Guarda publicaciones interesantes con el ícono de bookmark</p>
                </div>
            <?php else: ?>
                <?php foreach ($publicaciones_guardadas as $post): ?>
                <div class="post">
                    <div class="post-header">
                        <div class="avatar">
                            <?php if (!empty($post['foto_perfil']) && file_exists(__DIR__ . '/uploads/' . $post['foto_perfil'])): ?>
                                <img src="<?= url('uploads/' . htmlspecialchars($post['foto_perfil'])) ?>" alt="Foto de perfil">
                            <?php else: ?>
                                <?= obtenerIniciales($post['nombre']) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="post-user"><?= htmlspecialchars($post['nombre']) ?></div>
                            <div class="post-time">Guardado · <?= date('d M', strtotime($post['fecha_publicacion'])) ?> · @<?= htmlspecialchars($post['username']) ?></div>
                        </div>
                    </div>
                    <div class="post-content">
                        <p><?= nl2br(htmlspecialchars($post['contenido'])) ?></p>
                    </div>
                    <?php if (!empty($post['imagen'])): ?>
                        <img src="<?= url('uploads/' . htmlspecialchars($post['imagen'])) ?>" alt="Imagen de la publicación" class="post-image">
                    <?php endif; ?>
                    <div class="post-stats">
                        <div class="stat">
                            <i class="far fa-heart"></i>
                            <span><?= contarLikes($pdo, $post['id']) ?> Me gusta</span>
                        </div>
                        <div class="stat">
                            <i class="far fa-comment"></i>
                            <span><?= contarComentarios($pdo, $post['id']) ?> Comentarios</span>
                        </div>
                    </div>
                    <div class="post-actions">
                        <a href="#" class="post-action like like-btn" data-publicacion-id="<?= $post['id'] ?>">
                            <i class="far fa-heart <?= usuarioYaDioLike($pdo, $usuario['id'], $post['id']) ? 'fas liked' : '' ?>"></i>
                            <span>Me gusta</span>
                            <span class="like-count"><?= contarLikes($pdo, $post['id']) ?></span>
                        </a>
                        <a href="<?= url('perfil.php?id=' . $post['usuario_id']) ?>" class="post-action">
                            <i class="fas fa-user"></i>
                            <span>Ver perfil</span>
                        </a>
                        <a href="#" class="post-action" onclick="toggleGuardado(<?= $post['id'] ?>)">
                            <i class="fas fa-bookmark" style="color: var(--naranja-principal);"></i>
                            <span>Quitar de guardados</span>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
function toggleGuardado(publicacionId){
    fetch('<?= url('guardar.php') ?>',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`publicacion_id=${publicacionId}`})
    .then(r=>r.json())
    .then(d=>{ if(d.success){ if(d.guardado){alert('Publicación guardada')} else { location.reload(); } } })
    .catch(e=>console.error('Error:',e));
}
</script>

</body>
</html>
