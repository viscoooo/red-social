<?php
/**
 * Carga comentarios de una publicación (HTML compacto) + formulario si logueado.
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/funciones.php';

$publicacion_id = (int)($_GET['publicacion_id'] ?? 0);
if($publicacion_id<=0){ http_response_code(400); exit; }

$comentarios = obtenerComentariosPublicacion($pdo,$publicacion_id,5);

if(empty($comentarios)){
    echo '<p style="text-align:center; padding:1rem; color:var(--gris-oscuro);">Aún no hay comentarios</p>';
} else {
    foreach($comentarios as $c){
        $inicial = substr($c['autor_nombre'],0,1);
        $fotoPath = $c['autor_foto'] && file_exists(__DIR__.'/uploads/'.$c['autor_foto']) ? 'uploads/'.htmlspecialchars($c['autor_foto']) : null;
        echo '<div class="comment-compact">';
        echo '<div class="comment-avatar-compact">';
        if($fotoPath){ echo '<img src="'.$fotoPath.'" alt="Foto de perfil" loading="lazy">'; } else { echo htmlspecialchars($inicial); }
        echo '</div>';
        echo '<div class="comment-content-compact">';
        echo '<div class="comment-user-compact">'.htmlspecialchars($c['autor_nombre']).'</div>';
        echo '<div class="comment-text-compact">'.htmlspecialchars($c['contenido']).'</div>';
        $hora = date('H:i', strtotime($c['fecha_comentario']));
        echo '<div class="comment-time-compact">Hace '.$hora.'</div>';
        echo '</div>';
        echo '</div>';
    }
}

if(isset($_SESSION['usuario_id'])){
    echo '<form class="comment-form-compact" data-publicacion-id="'.$publicacion_id.'">';
    echo '<input type="text" class="comment-input-compact" placeholder="Escribe un comentario...">';
    echo '<button type="submit" class="comment-btn-compact">Comentar</button>';
    echo '</form>';
}