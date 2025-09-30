<?php
/**
 * Página de exploración de contenido
 * Reordenada para evitar salida previa a redirects.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/includes/sesion.php';
redirigirSiNoAutenticado();
$usuario = obtenerUsuarioSesion();
if ($usuario) { actualizarUltimoAcceso(); }

// Publicaciones populares por likes
$stmt = $pdo->prepare("\n    SELECT p.*, u.nombre, u.username, u.foto_perfil, COUNT(l.id) as likes_count\n    FROM publicaciones p\n    JOIN usuarios u ON p.usuario_id = u.id\n    LEFT JOIN likes l ON p.id = l.publicacion_id\n    GROUP BY p.id\n    ORDER BY likes_count DESC, p.fecha_publicacion DESC\n    LIMIT 20\n");
$stmt->execute();
$publicaciones_populares = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Usuarios sugeridos que no sigues
$stmt = $pdo->prepare("\n    SELECT u.id, u.nombre, u.username, u.foto_perfil, u.biografia\n    FROM usuarios u\n    WHERE u.id != ? \n    AND u.id NOT IN (\n        SELECT seguido_id FROM seguidores WHERE seguidor_id = ?\n    )\n    ORDER BY u.fecha_registro DESC\n    LIMIT 5\n");
$stmt->execute([$usuario['id'], $usuario['id']]);
$usuarios_sugeridos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Incluir cabecera tras la lógica previa
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <aside class="sidebar">
        <a href="<?= url('index.php') ?>" class="sidebar-item">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
        </a>
        <a href="<?= url('explorar.php') ?>" class="sidebar-item active">
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
        <div class="post-form" style="text-align:center; padding:2rem;">
            <h2 style="color: var(--verde-principal); margin-bottom:1rem;">
                <i class="fas fa-fire"></i> Tendencias y Contenido Popular
            </h2>
            <p>Descubre las publicaciones más populares y personas interesantes</p>
        </div>

        <div class="post">
            <h3 style="margin-bottom:1.5rem; color: var(--naranja-principal);">
                <i class="fas fa-chart-line"></i> Publicaciones Populares
            </h3>
            <?php foreach ($publicaciones_populares as $post): ?>
            <div class="post" style="margin-bottom:1.5rem; padding:1rem;">
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
                        <div class="post-time"><?= (int)$post['likes_count'] ?> Me gusta · <?= date('d M', strtotime($post['fecha_publicacion'])) ?></div>
                    </div>
                </div>
                <div class="post-content">
                    <p><?= nl2br(htmlspecialchars(mb_strimwidth($post['contenido'], 0, 150, '...'))) ?></p>
                </div>
                <?php if (!empty($post['imagen'])): ?>
                    <img src="<?= url('uploads/' . htmlspecialchars($post['imagen'])) ?>" alt="Imagen de la publicación" class="post-image" style="max-height:200px;">
                <?php endif; ?>
                <div class="post-actions" style="justify-content:flex-end;">
                    <a href="<?= url('perfil.php?id=' . $post['usuario_id']) ?>" class="post-action">
                        <i class="fas fa-user"></i>
                        <span>Ver perfil</span>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="post">
            <h3 style="margin-bottom:1.5rem; color: var(--verde-principal);">
                <i class="fas fa-users"></i> Personas Sugeridas
            </h3>
            <?php foreach ($usuarios_sugeridos as $user): ?>
            <div class="post" style="display:flex; align-items:center; gap:1rem; margin-bottom:1rem; padding:1rem;">
                <div class="avatar" style="width:60px; height:60px;">
                    <?php if (!empty($user['foto_perfil']) && file_exists(__DIR__ . '/uploads/' . $user['foto_perfil'])): ?>
                        <img src="<?= url('uploads/' . htmlspecialchars($user['foto_perfil'])) ?>" alt="Foto de perfil">
                    <?php else: ?>
                        <?= obtenerIniciales($user['nombre']) ?>
                    <?php endif; ?>
                </div>
                <div style="flex:1;">
                    <div class="post-user"><?= htmlspecialchars($user['nombre']) ?></div>
                    <div class="post-time">@<?= htmlspecialchars($user['username']) ?></div>
                    <div style="font-size:.9rem; color: var(--gris-oscuro); margin-top:.3rem;">
                        <?= htmlspecialchars(mb_strimwidth((string)($user['biografia'] ?? ''), 0, 60, '...')) ?>
                    </div>
                </div>
                <a href="#" class="follow-btn" data-usuario-id="<?= $user['id'] ?>" style="padding:.4rem 1rem; font-size:.9rem;">
                    Seguir
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

</body>
</html>
