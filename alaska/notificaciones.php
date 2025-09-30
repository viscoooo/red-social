<?php
/**
 * Página de notificaciones
 */
require_once __DIR__ . '/includes/header.php';
redirigirSiNoAutenticado();

marcarNotificacionesLeidas($pdo, $usuario['id']);
$notificaciones = obtenerNotificaciones($pdo, $usuario['id'], 30);
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
        <a href="<?= url('notificaciones.php') ?>" class="sidebar-item active">
            <i class="fas fa-bell"></i>
            <span>Notificaciones</span>
        </a>
        <a href="<?= url('mensajes.php') ?>" class="sidebar-item">
            <i class="fas fa-envelope"></i>
            <span>Mensajes</span>
        </a>
        <a href="<?= url('guardados.php') ?>" class="sidebar-item">
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
                <i class="fas fa-bell"></i> Notificaciones
            </h2>
            <?php if (empty($notificaciones)): ?>
                <div style="text-align:center; padding:2rem;">
                    <i class="fas fa-bell-slash" style="font-size:3rem; color: var(--gris-medio); margin-bottom:1rem;"></i>
                    <h3>No tienes notificaciones</h3>
                    <p>Recibirás notificaciones cuando alguien interactúe con tu contenido</p>
                </div>
            <?php else: ?>
                <?php foreach ($notificaciones as $notif): ?>
                <div class="post" style="display:flex; gap:1rem; margin-bottom:1rem; padding:1rem; border-left:3px solid var(--verde-principal);">
                    <div class="avatar" style="width:50px; height:50px;">
                        <?php if (!empty($notif['actor_foto']) && file_exists(__DIR__ . '/uploads/' . $notif['actor_foto'])): ?>
                            <img src="<?= url('uploads/' . htmlspecialchars($notif['actor_foto'])) ?>" alt="Foto de perfil">
                        <?php else: ?>
                            <?= obtenerIniciales($notif['actor_nombre']) ?>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1;">
                        <?php
                        $mensaje = '';
                        $enlace = url('index.php');
                        switch ($notif['tipo']) {
                            case 'like':
                                $mensaje = '<strong>' . htmlspecialchars($notif['actor_nombre']) . '</strong> le gustó tu publicación';
                                break;
                            case 'comentario':
                                $mensaje = '<strong>' . htmlspecialchars($notif['actor_nombre']) . '</strong> comentó en tu publicación';
                                break;
                            case 'seguimiento':
                                $mensaje = '<strong>' . htmlspecialchars($notif['actor_nombre']) . '</strong> empezó a seguirte';
                                $enlace = url('perfil.php?id=' . $notif['actor_id']);
                                break;
                            case 'mencion':
                                $mensaje = '<strong>' . htmlspecialchars($notif['actor_nombre']) . '</strong> te mencionó en una publicación';
                                break;
                        }
                        ?>
                        <div class="post-content">
                            <p><?= $mensaje ?></p>
                        </div>
                        <div class="post-time" style="font-size:.8rem;">
                            <?= date('d M H:i', strtotime($notif['fecha_notificacion'])) ?>
                        </div>
                        <a href="<?= $enlace ?>" class="post-action" style="margin-top:.5rem; padding:.3rem .8rem; font-size:.9rem;">
                            Ver
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>
