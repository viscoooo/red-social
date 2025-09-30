<?php
/**
 * Página principal del feed.
 * Requiere autenticación. Muestra el formulario de creación de publicaciones
 * (enlaza a publicar.php) y la lista inicial de posts. La carga adicional se
 * hace por AJAX con cargar_publicaciones.php.
 */
// Incluir dependencias SIN generar salida para poder redirigir si no hay sesión
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/funciones.php';
require_once 'includes/sesion.php';
redirigirSiNoAutenticado();
// Refrescar datos de usuario y último acceso
$usuario = obtenerUsuarioSesion();
if ($usuario) { actualizarUltimoAcceso(); }

// Lógica previa (consultas) antes de emitir HTML
// Paginación básica del feed
$pagina = max(1,(int)($_GET['pagina']??1));
$por_pagina = 10;
$offset = ($pagina-1)*$por_pagina;
// Publicaciones optimizadas (evita N+1)
$publicaciones = obtenerPublicacionesOptimizadas($pdo, $usuario['id'], $por_pagina, $offset);
// Total para paginación (se podría cachear)
$stmtTotal = $pdo->query('SELECT COUNT(*) FROM publicaciones');
$total_publicaciones = (int)$stmtTotal->fetchColumn();
$total_paginas = (int)ceil($total_publicaciones / $por_pagina);

// Ahora sí, emitir cabecera (HTML comienza aquí)
require_once 'includes/header.php';
?>

<div class="container">
    <!-- Barra lateral -->
    <aside class="sidebar">
    <a href="<?= app_url('index.php') ?>" class="sidebar-item active">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
        </a>
    <a href="<?= app_url('explorar.php') ?>" class="sidebar-item">
            <i class="fas fa-hashtag"></i>
            <span>Explorar</span>
        </a>
    <a href="<?= app_url('notificaciones.php') ?>" class="sidebar-item">
            <i class="fas fa-bell"></i>
            <span>Notificaciones</span>
        </a>
    <a href="<?= app_url('mensajes.php') ?>" class="sidebar-item">
            <i class="fas fa-envelope"></i>
            <span>Mensajes</span>
        </a>
    <a href="<?= app_url('guardados.php') ?>" class="sidebar-item">
            <i class="fas fa-bookmark"></i>
            <span>Guardados</span>
        </a>
    <a href="<?= app_url('listas.php') ?>" class="sidebar-item">
            <i class="fas fa-list-alt"></i>
            <span>Listas</span>
        </a>
    <a href="<?= app_url('perfil.php?id='.$usuario['id']) ?>" class="sidebar-item">
            <i class="fas fa-user"></i>
            <span>Perfil</span>
        </a>
        
        
        <div class="divider"></div>
        
    <a href="<?= app_url('publicar.php') ?>" class="btn btn-primary" style="padding: 0.8rem; margin: 0 1.2rem; text-align: center; text-decoration: none;">
            <i class="fas fa-feather-alt"></i>
            Publicar
        </a>
        
        <div class="divider"></div>
        
    <a href="<?= app_url('logout.php') ?>" class="sidebar-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar sesión</span>
        </a>
    </aside>

    <!-- Contenido principal -->
    <main class="main-content">
        <!-- Formulario de publicación -->
        <div class="post-form">
            <div class="post-header">
                <div class="avatar">
                    <?php if ($usuario['foto_perfil'] && file_exists('uploads/' . $usuario['foto_perfil'])): ?>
                        <img src="<?= app_url('uploads/' . htmlspecialchars($usuario['foto_perfil'])) ?>" alt="Foto de perfil">
                    <?php else: ?>
                        <?= obtenerIniciales($usuario['nombre']) ?>
                    <?php endif; ?>
                </div>
                <textarea class="post-input" placeholder="¿Qué está pasando con tus mascotas?" readonly onclick="window.location.href='<?= app_url('publicar.php') ?>'"></textarea>
            </div>
            <div class="post-actions">
                <div class="action-icons">
                    <div class="action-icon" title="Agregar imagen">
                        <i class="fas fa-image"></i>
                    </div>
                    <div class="action-icon" title="Agregar hashtag">
                        <i class="fas fa-paw"></i>
                    </div>
                    <div class="action-icon" title="Agregar ubicación">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="action-icon" title="Programar publicación">
                        <i class="fas fa-calendar"></i>
                    </div>
                </div>
                <a href="<?= url('publicar.php') ?>" class="post-btn" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">Publicar</a>
            </div>
        </div>

        <!-- Publicaciones -->
        <?php foreach ($publicaciones as $post): ?>
        <?php
            $fotoPerfil = $post['autor_foto'] ?? '';
            $avatarPath = '';
            if ($fotoPerfil) {
                if (file_exists('uploads/perfiles/' . $fotoPerfil)) {
                    $avatarPath = 'uploads/perfiles/' . $fotoPerfil;
                } elseif (file_exists('uploads/' . $fotoPerfil)) {
                    $avatarPath = 'uploads/' . $fotoPerfil;
                }
            }
        ?>
        <div class="post">
            <div class="post-header">
                <div class="avatar">
                    <?php if ($avatarPath): ?>
                        <img src="<?= app_url(htmlspecialchars($avatarPath)) ?>" alt="Foto de perfil">
                    <?php else: ?>
                        <?= substr($post['autor_nombre'], 0, 1) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="post-user">
                        <a href="<?= app_url('perfil.php?id='.$post['usuario_id']) ?>" style="text-decoration: none; color: inherit;">
                            <?= htmlspecialchars($post['autor_nombre']) ?>
                        </a>
                    </div>
                    <div class="post-time"><?= tiempoTranscurrido($post['fecha_publicacion']) ?> · @<?= htmlspecialchars($post['autor_username']) ?></div>
                </div>
            </div>
            <div class="post-content">
                <p><?= nl2br(htmlspecialchars($post['contenido'])) ?></p>
            </div>
            <?php if (!empty($post['imagen'])): ?>
                <img data-src="<?= app_url('uploads/' . htmlspecialchars($post['imagen'])) ?>" alt="Imagen de la publicación" class="post-image lazy">
            <?php endif; ?>
            <?php if(!empty($post['ubicacion'])): ?>
            <div style="margin-top:8px;">
                <button class="map-toggle-btn" data-map-target="map-post-<?= $post['id'] ?>" data-ubicacion="<?= htmlspecialchars($post['ubicacion']) ?>">Ver mapa</button>
                <div id="map-post-<?= $post['id'] ?>" class="post-map" style="display:none;"></div>
            </div>
            <?php endif; ?>
            <div class="post-stats">
                <div class="stat">
                    <i class="far fa-heart"></i>
                    <span class="like-count-text"><?= (int)$post['total_likes'] ?> Me gusta</span>
                </div>
                <div class="stat">
                    <i class="far fa-comment"></i>
                    <span class="comment-count-text"><?= (int)$post['total_comentarios'] ?> Comentarios</span>
                </div>
                <div class="stat">
                    <i class="fas fa-retweet"></i>
                    <span>0 Retweets</span>
                </div>
            </div>
            <div class="post-actions">
                <a href="#" class="post-action like like-btn" data-publicacion-id="<?= $post['id'] ?>">
                    <i class="<?= $post['usuario_dio_like'] ? 'fas liked' : 'far' ?> fa-heart"></i>
                    <span>Me gusta</span>
                    <span class="like-count"><?= (int)$post['total_likes'] ?></span>
                </a>
                <a href="#" class="post-action comment" onclick="toggleCommentForm(<?= $post['id'] ?>)">
                    <i class="far fa-comment"></i>
                    <span>Comentar</span>
                </a>
                <a href="#" class="post-action share">
                    <i class="fas fa-retweet"></i>
                    <span>Retweet</span>
                </a>
                <a href="#" class="post-action save save-btn" data-publicacion-id="<?= $post['id'] ?>">
                    <i class="<?= $post['usuario_guardado'] ? 'fas' : 'far' ?> fa-bookmark" style="<?= $post['usuario_guardado'] ? 'color: var(--naranja-principal);' : '' ?>"></i>
                    <span><?= $post['usuario_guardado'] ? 'Guardado' : 'Guardar' ?></span>
                </a>
            </div>

            <!-- Sección de comentarios -->
            <div class="comments-section" id="comments-<?= $post['id'] ?>" style="display: none;">
                <?php
                $comentarios = obtenerComentarios($pdo, $post['id'], 3);
                foreach ($comentarios as $comentario): ?>
                <div class="comment">
                    <div class="comment-avatar">
                        <?php if ($comentario['foto_perfil'] && file_exists('uploads/' . $comentario['foto_perfil'])): ?>
                            <img src="uploads/<?= htmlspecialchars($comentario['foto_perfil']) ?>" alt="Foto de perfil">
                        <?php else: ?>
                            <?= substr($comentario['nombre'], 0, 1) ?>
                        <?php endif; ?>
                    </div>
                    <div class="comment-content">
                        <div class="comment-user"><?= htmlspecialchars($comentario['nombre']) ?></div>
                        <div class="comment-text"><?= htmlspecialchars($comentario['contenido']) ?></div>
                        <div class="comment-time"><?= tiempoTranscurrido($comentario['fecha_comentario']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (count($comentarios) > 0): ?>
                <a href="#" class="ver-todos-comentarios" data-publicacion-id="<?= $post['id'] ?>" style="display: block; text-align: center; margin-top: 10px; color: var(--verde-principal);">Ver todos los comentarios</a>
                <?php endif; ?>

                <form class="comment-form" data-publicacion-id="<?= $post['id'] ?>">
                    <input type="text" class="comment-input" placeholder="Escribe un comentario...">
                    <button type="submit" class="comment-btn">Comentar</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </main>
</div>

</body>
</html>
