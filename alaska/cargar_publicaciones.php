<?php
/**
 * Endpoint que devuelve HTML de tarjetas de publicaciones para la “carga infinita”.
 * Es invocado por JS (fetch) desde el cliente al hacer scroll.
 */

// Asegura sesión activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dependencias necesarias: config (URLs), db (PDO), funciones (consultas y helpers)
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/funciones.php';

// Solo permitir acceso si el usuario está autenticado; si no, no devolvemos nada
if (!isset($_SESSION['usuario_id'])) {
    exit();
}

// Paginación básica: page inicia en 1; limit define cantidad por página
$usuario_id = (int)$_SESSION['usuario_id'];
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Obtenemos el lote de publicaciones
$publicaciones = obtenerPublicacionesOptimizadas($pdo, $usuario_id, $limit, $offset);

// Si no hay más resultados, devolvemos vacío para que el frontend detenga la carga
if (empty($publicaciones)) {
    exit();
}

// Render: cada publicación replica la misma estructura que en index/perfil
// para que los estilos y JS (initializeButtons) funcionen igual.
foreach ($publicaciones as $post): ?>
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
                <img src="<?= url(htmlspecialchars($avatarPath)) ?>" alt="Foto de perfil">
            <?php else: ?>
                <?= substr($post['autor_nombre'], 0, 1) ?>
            <?php endif; ?>
        </div>
        <div>
            <div class="post-user">
                <a href="<?= url('perfil.php') ?>?id=<?= $post['usuario_id'] ?>" style="text-decoration:none;color:inherit;">
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
    <img src="<?= url('uploads/' . htmlspecialchars($post['imagen'])) ?>" alt="Imagen de la publicación" class="post-image">
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

    <div class="comments-section" id="comments-<?= $post['id'] ?>" style="display:none;">
        <?php $comentarios = obtenerComentarios($pdo, $post['id'], 3); foreach ($comentarios as $comentario): ?>
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
    <a href="#" class="ver-todos-comentarios" data-publicacion-id="<?= $post['id'] ?>" style="display:block;text-align:center;margin-top:10px;color:var(--verde-principal);">Ver todos los comentarios</a>
    <?php endif; ?>

        <form class="comment-form" data-publicacion-id="<?= $post['id'] ?>">
            <input type="text" class="comment-input" placeholder="Escribe un comentario...">
            <button type="submit" class="comment-btn">Comentar</button>
        </form>
    </div>
</div>
<?php endforeach; ?>
