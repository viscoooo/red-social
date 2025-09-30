<?php
/**
 * Página de perfil de usuario - VERSIÓN OPTIMIZADA
 * Reordenada para realizar redirecciones ANTES de enviar salida (evitando "headers already sent").
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/funciones.php';
require_once 'includes/sesion.php';
redirigirSiNoAutenticado();

// Obtenemos usuario de sesión manualmente (header aún no incluido)
$usuario = obtenerUsuarioSesion();
if($usuario){ actualizarUltimoAcceso(); }

$usuario_id = isset($_GET['id']) ? (int)$_GET['id'] : (int)$usuario['id'];
$perfil = $usuario_id ? obtenerUsuarioPorId($pdo,$usuario_id) : null;
if(!$perfil){ redirect_to('index.php'); }

$tipo_filtro = $_GET['tipo'] ?? 'todos';
$pagina = max(1,(int)($_GET['pagina'] ?? 1));
$es_mismo_usuario = ($usuario_id === (int)$usuario['id']);
$tipos_validos = ['todos','general','mascota','evento','consejo'];
if(!in_array($tipo_filtro,$tipos_validos,true)){ $tipo_filtro='todos'; }

// Eliminación de publicación (antes de cargar datos pesados para poder redirigir)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_publicacion'])) {
    $publicacion_id = (int)($_POST['publicacion_id'] ?? 0);
    if ($publicacion_id && eliminarPublicacion($pdo,$publicacion_id,$usuario['id'])) {
        redirect_to('perfil.php?id='.$usuario_id.'&tipo='.$tipo_filtro.'&pagina='.$pagina);
    }
}

// Estadísticas (caché)
$estadisticas_base = obtenerEstadisticasUsuario($pdo,$usuario_id);
$seguidores = $estadisticas_base['seguidores'];
$siguiendo = $estadisticas_base['siguiendo'];
$total_publicaciones = $estadisticas_base['total_publicaciones'];
$esta_siguiendo = usuarioEstaSiguiendo($pdo,$usuario['id'],$usuario_id);
// Likes y comentarios recibidos (se podrían cachear adicionalmente si son costosos)
$stmt = $pdo->prepare('SELECT COUNT(*) FROM likes l JOIN publicaciones p ON l.publicacion_id = p.id WHERE p.usuario_id = ?');
$stmt->execute([$usuario_id]);
$total_likes_recibidos = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM comentarios c JOIN publicaciones p ON c.publicacion_id = p.id WHERE p.usuario_id = ?');
$stmt->execute([$usuario_id]);
$total_comentarios_recibidos = (int)$stmt->fetchColumn();

$por_pagina = 12;
$publicaciones_raw = obtenerPublicacionesPerfilOptimizadas($pdo,$usuario_id,$usuario['id'],$pagina,$por_pagina);
if($tipo_filtro !== 'todos') {
    $publicaciones = array_values(array_filter($publicaciones_raw, fn($p)=> $p['tipo'] === $tipo_filtro));
} else { $publicaciones = $publicaciones_raw; }
$total_publicaciones_db = contarPublicacionesUsuario($pdo,$usuario_id);
$total_paginas = max(1,(int)ceil($total_publicaciones_db / $por_pagina));
if($pagina > $total_paginas){ $pagina = $total_paginas; }

$fotos_mascotas = json_decode($perfil['fotos_mascotas'] ?? '[]', true) ?: [];

// Incluir cabecera SOLO después de que ya no habrá redirecciones.
require_once 'includes/header.php';
?>

<div class="container">
    <aside class="sidebar">
    <a href="<?= app_url('index.php') ?>" class="sidebar-item">
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
    <a href="<?= app_url('perfil.php?id='.$usuario['id']) ?>" class="sidebar-item active">
            <i class="fas fa-user"></i>
            <span>Perfil</span>
        </a>
    <a href="<?= app_url('ajustes.php') ?>" class="sidebar-item">
            <i class="fas fa-cog"></i>
            <span>Ajustes</span>
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

    <main class="main-content">
        <!-- Cabecera del perfil COMPACTA -->
        <div class="profile-header-compact">
            <div class="profile-banner-compact">
                <div class="banner-overlay-compact"></div>
                <div class="profile-avatar-compact">
                    <?php if ($perfil['foto_perfil'] && file_exists('uploads/perfiles/' . $perfil['foto_perfil'])): ?>
                        <img src="<?= app_url('uploads/perfiles/' . htmlspecialchars($perfil['foto_perfil'])) ?>" alt="Foto de perfil">
                    <?php else: ?>
                        <div class="avatar-placeholder-compact">
                            <?= substr($perfil['nombre'], 0, 2) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="profile-info-compact">
                <div class="profile-header-content-compact">
                    <div class="profile-basic-info-compact">
                        <h1><?= htmlspecialchars($perfil['nombre']) ?></h1>
                        <p class="profile-username-compact">@<?= htmlspecialchars($perfil['username']) ?></p>
                    </div>
                    
                    <?php if (!$es_mismo_usuario): ?>
                        <a href="#" class="follow-btn-compact <?= $esta_siguiendo ? 'following' : '' ?>" data-usuario-id="<?= $usuario_id ?>">
                            <?= $esta_siguiendo ? '<i class="fas fa-user-check"></i> Siguiendo' : '<i class="fas fa-user-plus"></i> Seguir' ?>
                        </a>
                    <?php else: ?>
                        <a href="ajustes.php" class="edit-profile-btn-compact">
                            <i class="fas fa-edit"></i> Editar perfil
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="profile-bio-compact">
                    <?= !empty($perfil['biografia']) ? nl2br(htmlspecialchars($perfil['biografia'])) : '<span class="placeholder-text">Este usuario aún no ha agregado una biografía.</span>' ?>
                </div>
                
                <?php if (!empty($perfil['ubicacion'])): ?>
                <div class="profile-location-compact">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?= htmlspecialchars($perfil['ubicacion']) ?></span>
                </div>
                <?php endif; ?>
                
                <div class="profile-stats-compact">
                    <div class="stat-item-compact">
                        <div class="stat-value-compact"><?= $total_publicaciones ?></div>
                        <div class="stat-label-compact">Publicaciones</div>
                    </div>
                    <div class="stat-item-compact">
                        <div class="stat-value-compact"><?= $seguidores ?></div>
                        <div class="stat-label-compact">Seguidores</div>
                    </div>
                    <div class="stat-item-compact">
                        <div class="stat-value-compact"><?= $siguiendo ?></div>
                        <div class="stat-label-compact">Siguiendo</div>
                    </div>
                    <div class="stat-item-compact">
                        <div class="stat-value-compact"><?= $total_likes_recibidos ?></div>
                        <div class="stat-label-compact">Likes</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fotos de mascotas -->
        <?php if (!empty($fotos_mascotas)): ?>
        <div class="pet-photos-section">
            <h3 style="margin: 1.5rem 0; color: var(--verde-principal); padding-left: 1rem;">Fotos de mis mascotas</h3>
            <div class="pet-photos-grid">
                <?php foreach ($fotos_mascotas as $foto): ?>
                <div class="pet-photo-item">
                    <img src="uploads/mascotas/<?= htmlspecialchars($foto) ?>" alt="Foto de mascota">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filtros de publicaciones -->
        <div class="profile-controls">
            <div class="profile-filters">
                <div class="filter-item <?= $tipo_filtro === 'todos' ? 'active' : '' ?>" data-tipo="todos"><i class="fas fa-th-large"></i> Todos</div>
                <div class="filter-item <?= $tipo_filtro === 'general' ? 'active' : '' ?>" data-tipo="general"><i class="fas fa-comment"></i> General</div>
                <div class="filter-item <?= $tipo_filtro === 'mascota' ? 'active' : '' ?>" data-tipo="mascota"><i class="fas fa-paw"></i> Mascotas</div>
                <div class="filter-item <?= $tipo_filtro === 'evento' ? 'active' : '' ?>" data-tipo="evento"><i class="fas fa-calendar"></i> Eventos</div>
                <div class="filter-item <?= $tipo_filtro === 'consejo' ? 'active' : '' ?>" data-tipo="consejo"><i class="fas fa-lightbulb"></i> Consejos</div>
            </div>
            <?php if($total_paginas>1): ?>
            <div class="pagination">
                <?php if($pagina>1): ?>
                    <a href="?id=<?= $usuario_id ?>&tipo=<?= $tipo_filtro ?>&pagina=<?= $pagina-1 ?>" class="pagination-btn"><i class="fas fa-chevron-left"></i></a>
                <?php endif; ?>
                <?php
                    $inicio = max(1,$pagina-2); $fin = min($total_paginas,$pagina+2);
                    if($inicio>1) echo '<span class="pagination-ellipsis">...</span>';
                    for($i=$inicio;$i<=$fin;$i++): ?>
                        <a href="?id=<?= $usuario_id ?>&tipo=<?= $tipo_filtro ?>&pagina=<?= $i ?>" class="pagination-btn <?= $i==$pagina?'active':'' ?>"><?= $i ?></a>
                <?php endfor; if($fin<$total_paginas) echo '<span class="pagination-ellipsis">...</span>'; ?>
                <?php if($pagina<$total_paginas): ?>
                    <a href="?id=<?= $usuario_id ?>&tipo=<?= $tipo_filtro ?>&pagina=<?= $pagina+1 ?>" class="pagination-btn"><i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Publicaciones -->
        <?php if (!empty($publicaciones)): ?>
            <div class="profile-posts-grid-compact">
                <?php foreach ($publicaciones as $post): ?>
                <div class="profile-post-card-compact" data-publicacion-id="<?= $post['publicacion_id'] ?>">
                    <div class="post-image-container-compact">
                        <?php if (!empty($post['imagen'])): ?>
                            <img src="uploads/<?= htmlspecialchars($post['imagen']) ?>" alt="Imagen de la publicación" class="post-image-compact" loading="lazy">
                        <?php else: ?>
                            <div class="post-text-preview-compact"><?= htmlspecialchars(substr($post['contenido'],0,100)) ?>...</div>
                        <?php endif; ?>
                        <?php if ($post['tipo'] !== 'general'): ?>
                            <div class="post-type-badge <?= $post['tipo'] ?>">
                                <?php $tipos_labels=[ 'mascota'=>'<i class="fas fa-paw"></i> Mascota','evento'=>'<i class="fas fa-calendar"></i> Evento','consejo'=>'<i class="fas fa-lightbulb"></i> Consejo']; echo $tipos_labels[$post['tipo']] ?? $post['tipo']; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($es_mismo_usuario): ?>
                            <div class="post-menu-dropdown">
                                <button class="menu-toggle-btn" onclick="togglePostMenu(<?= $post['publicacion_id'] ?>)"><i class="fas fa-ellipsis-h"></i></button>
                                <div class="post-menu-content" id="post-menu-<?= $post['publicacion_id'] ?>">
                                    <button class="menu-item" onclick="abrirModalEdicion(<?= $post['publicacion_id'] ?>,'<?= addslashes(htmlspecialchars($post['contenido'])) ?>','<?= $post['tipo'] ?>','<?= addslashes(htmlspecialchars($post['nombre_mascota'] ?? '')) ?>','<?= addslashes(htmlspecialchars($post['ubicacion'] ?? '')) ?>')"><i class="fas fa-edit"></i> Editar</button>
                                    <form method="POST" onsubmit="return confirm('¿Estás seguro de eliminar esta publicación?');">
                                        <input type="hidden" name="publicacion_id" value="<?= $post['publicacion_id'] ?>">
                                        <input type="hidden" name="eliminar_publicacion" value="1">
                                        <button type="submit" class="menu-item delete-item"><i class="fas fa-trash"></i> Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="post-content-compact">
                        <div class="post-header-compact">
                            <div class="avatar-compact">
                                <?php if ($post['autor_foto'] && file_exists('uploads/' . $post['autor_foto'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($post['autor_foto']) ?>" alt="Foto de perfil" loading="lazy">
                                <?php else: ?>
                                    <?= substr($post['autor_nombre'], 0, 1) ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="post-user-compact"><?= htmlspecialchars($post['autor_nombre']) ?></div>
                                <div class="post-time-compact">Hace <?= date('H:i', strtotime($post['fecha_publicacion'])) ?> · <?= htmlspecialchars($post['autor_username']) ?></div>
                            </div>
                        </div>
                        <div class="post-content-text-compact">
                            <?php if (!empty($post['nombre_mascota'])): ?>
                                <div style="font-weight: bold; color: var(--naranja-principal); margin-bottom: 0.5rem;">
                                    <i class="fas fa-paw"></i> <?= htmlspecialchars($post['nombre_mascota']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($post['ubicacion'])): ?>
                                <div style="font-size: 0.9rem; color: var(--gris-oscuro); margin-bottom: 0.5rem;">
                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($post['ubicacion']) ?>
                                </div>
                            <?php endif; ?>
                            <p><?= nl2br(htmlspecialchars($post['contenido'])) ?></p>
                        </div>
                    </div>
                    
                    <!-- Botones de interacción (solo uno, en la parte inferior) -->
                    <div class="post-actions-compact">
                        <div class="action-group">
                            <button class="like-btn-compact like-btn" data-publicacion-id="<?= $post['publicacion_id'] ?>">
                                <i class="far fa-heart <?= $post['usuario_dio_like'] ? 'fas liked' : '' ?>"></i>
                                <span>Me gusta</span>
                                <span class="like-count-compact"><?= $post['total_likes'] ?></span>
                            </button>
                            <button class="comment-btn-compact" onclick="toggleCommentSection(<?= $post['publicacion_id'] ?>)">
                                <i class="far fa-comment"></i>
                                <span>Comentar</span>
                                <span class="comment-count-compact"><?= $post['total_comentarios'] ?></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="comments-section-compact" id="comments-<?= $post['publicacion_id'] ?>" style="display:none;">
                        <div class="comments-loading" id="comments-loading-<?= $post['publicacion_id'] ?>" style="text-align:center; padding:1rem;">
                            <i class="fas fa-spinner fa-spin"></i> Cargando comentarios...
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if($total_paginas>1): ?>
            <div class="pagination-bottom">
                <div class="pagination">
                    <?php if($pagina>1): ?><a href="?id=<?= $usuario_id ?>&tipo=<?= $tipo_filtro ?>&pagina=<?= $pagina-1 ?>" class="pagination-btn"><i class="fas fa-chevron-left"></i> Anterior</a><?php endif; ?>
                    <span style="margin:0 1rem; color:var(--gris-oscuro);">Página <?= $pagina ?> de <?= $total_paginas ?></span>
                    <?php if($pagina<$total_paginas): ?><a href="?id=<?= $usuario_id ?>&tipo=<?= $tipo_filtro ?>&pagina=<?= $pagina+1 ?>" class="pagination-btn">Siguiente <i class="fas fa-chevron-right"></i></a><?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state-compact">
                <i class="fas fa-paw" style="font-size: 2.5rem; color: var(--gris-medio); margin-bottom: 1rem;"></i>
                <?php if ($tipo_filtro === 'todos'): ?>
                    <h3><?= $es_mismo_usuario ? 'Aún no has publicado nada' : 'Este usuario aún no ha publicado nada' ?></h3>
                    <?php if ($es_mismo_usuario): ?>
                        <a href="publicar.php" class="btn btn-primary" style="margin-top: 1rem; padding: 0.4rem 1rem; font-size: 0.9rem;">Crear publicación</a>
                    <?php endif; ?>
                <?php else: ?>
                    <h3>No hay publicaciones de tipo "<?= $tipo_filtro ?>"</h3>
                    <?php if ($es_mismo_usuario): ?>
                        <a href="publicar.php" class="btn btn-primary" style="margin-top: 1rem; padding: 0.4rem 1rem; font-size: 0.9rem;">Crear publicación</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- Modal de edición de publicación -->
<div id="editarPublicacionModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal" onclick="cerrarModalEdicion()">&times;</span>
        <h2>Editar Publicación</h2>
        <form id="formEditarPublicacion">
            <input type="hidden" id="publicacionIdEditar" name="publicacion_id">
            <div class="form-group">
                <label>Tipo de publicación:</label>
                <select id="tipoPublicacionEditar" name="tipo" class="form-control">
                    <option value="general">General</option>
                    <option value="mascota">Sobre mi mascota</option>
                    <option value="evento">Evento</option>
                    <option value="consejo">Consejo</option>
                </select>
            </div>
            <div class="form-group" id="nombreMascotaField" style="display: none;">
                <label>Nombre de la mascota:</label>
                <input type="text" id="nombreMascotaEditar" name="nombre_mascota" class="form-control" placeholder="Nombre de tu mascota">
            </div>
            <div class="form-group">
                <label>Ubicación:</label>
                <input type="text" id="ubicacionEditar" name="ubicacion" class="form-control" placeholder="Ubicación de la publicación" value="">
            </div>
            <div class="form-group">
                <label>Contenido:</label>
                <textarea id="contenidoEditar" name="contenido" class="form-control" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </form>
    </div>
</div>

<style>
/* Estilos adicionales para filtros y acciones */
.profile-filters {
    display: flex;
    gap: 0.5rem;
    margin: 1rem 0 1.5rem;
    padding: 0 1rem;
    overflow-x: auto;
    background-color: var(--blanco);
    border-radius: var(--borde-radius);
    box-shadow: var(--sombra);
    padding: 0.8rem;
}

.filter-item {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    cursor: pointer;
    transition: var(--transicion);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    white-space: nowrap;
    background-color: var(--gris-claro);
    color: var(--gris-oscuro);
}

.filter-item:hover {
    background-color: var(--verde-principal);
    color: var(--blanco);
}

.filter-item.active {
    background-color: var(--verde-principal);
    color: var(--blanco);
    font-weight: 600;
}

.post-type-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    color: white;
    backdrop-filter: blur(10px);
}

.post-type-badge.mascota {
    background-color: rgba(46, 204, 113, 0.9);
}

.post-type-badge.evento {
    background-color: rgba(231, 76, 60, 0.9);
}

.post-type-badge.consejo {
    background-color: rgba(52, 152, 219, 0.9);
}

/* Menú desplegable para publicaciones */
.post-menu-dropdown {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 10;
}

.menu-toggle-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transicion);
}

.menu-toggle-btn:hover {
    background-color: rgba(0, 0, 0, 0.9);
    transform: scale(1.1);
}

.post-menu-content {
    position: absolute;
    top: 40px;
    right: 0;
    background-color: var(--blanco);
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    min-width: 160px;
    z-index: 100;
    display: none;
    border: 1px solid var(--gris-medio);
}

.post-menu-content.show {
    display: block;
}

.menu-item {
    display: block;
    width: 100%;
    padding: 0.8rem 1rem;
    text-align: left;
    border: none;
    background: none;
    cursor: pointer;
    color: var(--negro-suave);
    font-size: 0.95rem;
    transition: var(--transicion);
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.menu-item:hover {
    background-color: var(--gris-claro);
}

.menu-item.delete-item {
    color: var(--naranja-principal);
}

.menu-item.delete-item:hover {
    background-color: rgba(231, 76, 60, 0.1);
}

/* Estilos del perfil compacto */
.profile-header-compact {
    background: linear-gradient(135deg, var(--verde-principal), var(--naranja-principal));
    color: var(--blanco);
    border-radius: var(--borde-radius);
    overflow: hidden;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}

.profile-banner-compact {
    position: relative;
    height: 120px;
    background: linear-gradient(45deg, #1abc9c, #3498db);
}

.banner-overlay-compact {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.2) 100%);
}

.profile-avatar-compact {
    position: absolute;
    bottom: -40px;
    left: 2rem;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 3px solid var(--blanco);
    overflow: hidden;
    background-color: var(--blanco);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.profile-avatar-compact img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder-compact {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    font-weight: bold;
    color: var(--verde-principal);
    background-color: var(--blanco);
}

.profile-info-compact {
    padding: 50px 2rem 1.5rem 120px;
}

.profile-header-content-compact {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.profile-basic-info-compact h1 {
    font-size: 1.5rem;
    margin-bottom: 0.2rem;
}

.profile-username-compact {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1rem;
    margin-bottom: 0;
}

.follow-btn-compact, .edit-profile-btn-compact {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 1.2rem;
    border-radius: 20px;
    font-weight: 600;
    text-decoration: none;
    font-size: 0.9rem;
}

.follow-btn-compact {
    background-color: var(--blanco);
    color: var(--verde-principal);
    border: 2px solid var(--blanco);
}

.follow-btn-compact:hover {
    background-color: transparent;
    color: var(--blanco);
}

.follow-btn-compact.following {
    background-color: transparent !important;
    color: var(--blanco) !important;
}

.edit-profile-btn-compact {
    background-color: rgba(255, 255, 255, 0.2);
    color: var(--blanco);
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.edit-profile-btn-compact:hover {
    background-color: rgba(255, 255, 255, 0.3);
}

.profile-bio-compact {
    margin: 1rem 0;
    line-height: 1.5;
    font-size: 1rem;
    max-width: 600px;
}

.profile-location-compact {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0.8rem 0;
    font-size: 1rem;
}

.profile-stats-compact {
    display: flex;
    gap: 1.2rem;
    margin: 1rem 0;
    flex-wrap: wrap;
}

.stat-item-compact {
    text-align: center;
    min-width: 80px;
}

.stat-value-compact {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 0.2rem;
}

.stat-label-compact {
    font-size: 0.8rem;
    opacity: 0.9;
}

/* Fotos de mascotas */
.pet-photos-section {
    background-color: var(--blanco);
    border-radius: var(--borde-radius);
    padding: 1rem;
    box-shadow: var(--sombra);
    margin-bottom: 1.5rem;
}

.pet-photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.8rem;
}

.pet-photo-item {
    border-radius: 8px;
    overflow: hidden;
    aspect-ratio: 1/1;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.pet-photo-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Grid de publicaciones compacto */
.profile-posts-grid-compact {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 0.8rem;
    padding: 0 1rem;
}

.profile-post-card-compact {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    background-color: var(--blanco);
    box-shadow: var(--sombra);
    margin-bottom: 1rem;
}

.post-image-container-compact {
    width: 100%;
    height: 100%;
    overflow: hidden;
    position: relative;
}

.post-image-compact {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.post-text-preview-compact {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.8rem;
    font-size: 0.85rem;
    color: var(--gris-oscuro);
    text-align: center;
    line-height: 1.3;
}

.post-content-compact {
    padding: 1rem;
}

.post-header-compact {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.avatar-compact {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--verde-principal);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--blanco);
    font-weight: bold;
    font-size: 1.2rem;
    overflow: hidden;
}

.avatar-compact img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.post-user-compact {
    font-weight: 700;
    color: var(--negro-suave);
}

.post-time-compact {
    color: var(--gris-oscuro);
    font-size: 0.9rem;
}

.post-content-text-compact {
    margin: 1rem 0;
    line-height: 1.7;
}

.post-actions-compact {
    padding: 1rem;
    border-top: 1px solid var(--gris-medio);
    background-color: var(--gris-claro);
}

.action-group {
    display: flex;
    gap: 1rem;
    justify-content: space-between;
    align-items: center;
}

.like-btn-compact, .comment-btn-compact {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 30px;
    cursor: pointer;
    transition: var(--transicion);
    color: var(--gris-oscuro);
    text-decoration: none;
    background-color: transparent;
    border: none;
}

.like-btn-compact:hover, .comment-btn-compact:hover {
    background-color: var(--gris-claro);
}

.like-btn-compact.liked, .like-btn-compact:hover {
    color: var(--naranja-principal);
}

.comment-btn-compact:hover {
    color: var(--verde-principal);
}

.like-btn-compact i {
    font-size: 1.2rem;
}

.comment-btn-compact i {
    font-size: 1.2rem;
}

.like-count-compact, .comment-count-compact {
    font-size: 0.9rem;
    margin-left: 0.5rem;
}

/* Sección de comentarios */
.comments-section-compact {
    padding: 1rem;
    border-top: 1px solid var(--gris-medio);
}

.comment-compact {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--gris-medio);
}

.comment-compact:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.comment-avatar-compact {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--verde-principal);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--blanco);
    font-size: 0.9rem;
    font-weight: bold;
}

.comment-avatar-compact img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.comment-content-compact {
    flex: 1;
}

.comment-user-compact {
    font-weight: 600;
    margin-bottom: 0.3rem;
}

.comment-text-compact {
    margin-bottom: 0.3rem;
}

.comment-time-compact {
    font-size: 0.8rem;
    color: var(--gris-oscuro);
}

.comment-form-compact {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.comment-input-compact {
    flex: 1;
    padding: 0.6rem;
    border: 2px solid var(--gris-medio);
    border-radius: 30px;
    font-size: 0.9rem;
}

.comment-input-compact:focus {
    outline: none;
    border-color: var(--verde-principal);
}

.comment-btn-compact {
    background-color: var(--verde-principal);
    color: var(--blanco);
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: 30px;
    font-weight: 600;
    cursor: pointer;
}

.comment-btn-compact:hover {
    background-color: var(--verde-oscuro);
}

.empty-state-compact {
    text-align: center;
    padding: 2rem 1rem;
    color: var(--gris-oscuro);
}

.empty-state-compact h3 {
    margin-bottom: 0.8rem;
    color: var(--negro-suave);
    font-size: 1.2rem;
}

/* Modal de edición */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: var(--blanco);
    margin: 10% auto;
    padding: 2rem;
    width: 90%;
    max-width: 500px;
    border-radius: var(--borde-radius);
    position: relative;
}

.close-modal {
    position: absolute;
    right: 1rem;
    top: 1rem;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--gris-oscuro);
}

/* Responsive */
@media (max-width: 768px) {
    .profile-info-compact {
        padding: 50px 1rem 1.5rem 100px;
    }
    
    .profile-basic-info-compact h1 {
        font-size: 1.3rem;
    }
    
    .profile-stats-compact {
        gap: 0.8rem;
    }
    
    .stat-item-compact {
        min-width: 70px;
    }
    
    .stat-value-compact {
        font-size: 1.1rem;
    }
    
    .profile-posts-grid-compact {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
    
    .profile-filters {
        padding: 0.6rem;
    }
    
    .filter-item {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
    }
    
    .post-actions-compact {
        padding: 0.8rem;
    }
    
    .action-group {
        flex-direction: column;
        gap: 0.5rem;
        align-items: stretch;
    }
    
    .like-btn-compact, .comment-btn-compact {
        justify-content: center;
        padding: 0.5rem;
        font-size: 0.9rem;
    }
    
    .like-btn-compact i, .comment-btn-compact i {
        font-size: 1rem;
    }
    
    .post-menu-dropdown {
        top: 5px;
        right: 5px;
    }
}

@media (max-width: 480px) {
    .profile-banner-compact {
        height: 100px;
    }
    
    .profile-avatar-compact {
        width: 70px;
        height: 70px;
        bottom: -35px;
        left: 1rem;
    }
    
    .profile-info-compact {
        padding: 45px 1rem 1.5rem 90px;
    }
    
    .profile-basic-info-compact h1 {
        font-size: 1.2rem;
    }
    
    .profile-stats-compact {
        gap: 0.6rem;
    }
    
    .stat-item-compact {
        min-width: 60px;
    }
    
    .stat-value-compact {
        font-size: 1rem;
    }
    
    .profile-posts-grid-compact {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    }
    
    .profile-filters {
        padding: 0.5rem;
    }
    
    .filter-item {
        padding: 0.3rem 0.6rem;
        font-size: 0.8rem;
    }
    
    .post-actions-compact {
        padding: 0.8rem;
    }
    
    .action-group {
        flex-direction: row;
        gap: 0.3rem;
    }
    
    .like-btn-compact, .comment-btn-compact {
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
    }
    
    .like-btn-compact i, .comment-btn-compact i {
        font-size: 0.9rem;
    }
    
    .post-menu-dropdown {
        top: 5px;
        right: 5px;
    }
    
    .menu-toggle-btn {
        width: 28px;
        height: 28px;
    }
}
</style>

<script>
document.querySelectorAll('.filter-item').forEach(f=>f.addEventListener('click',function(){const t=this.dataset.tipo;window.location.href=`perfil.php?id=<?= $usuario_id ?>&tipo=${t}&pagina=1`;}));

function togglePostMenu(id){const menu=document.getElementById('post-menu-'+id);document.querySelectorAll('.post-menu-content').forEach(m=>{if(m!==menu)m.classList.remove('show');});menu&&menu.classList.toggle('show');}
document.addEventListener('click',e=>{if(!e.target.closest('.post-menu-dropdown')){document.querySelectorAll('.post-menu-content').forEach(m=>m.classList.remove('show'));}});

function abrirModalEdicion(id,contenido,tipo,nombreMascota,ubicacion){document.getElementById('publicacionIdEditar').value=id;document.getElementById('contenidoEditar').value=contenido;document.getElementById('tipoPublicacionEditar').value=tipo;document.getElementById('nombreMascotaEditar').value=nombreMascota||'';document.getElementById('ubicacionEditar').value=ubicacion||'';toggleNombreMascotaField(tipo);document.getElementById('editarPublicacionModal').style.display='block';const m=document.getElementById('post-menu-'+id);if(m)m.classList.remove('show');}
function cerrarModalEdicion(){document.getElementById('editarPublicacionModal').style.display='none';}
function toggleNombreMascotaField(tipo){const f=document.getElementById('nombreMascotaField');f.style.display=(tipo==='mascota')?'block':'none';}
document.getElementById('tipoPublicacionEditar').addEventListener('change',function(){toggleNombreMascotaField(this.value);});
document.getElementById('formEditarPublicacion').addEventListener('submit',function(e){e.preventDefault();const fd=new FormData(this);fetch('editar_publicacion.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success){cerrarModalEdicion();location.reload();}else{alert('Error al editar la publicación: '+d.message);}}).catch(err=>{console.error(err);alert('Error al editar la publicación');});});

function toggleCommentSection(id){const c=document.getElementById('comments-'+id);if(!c)return;const visible=c.style.display==='block';c.style.display=visible?'none':'block';if(!visible && !c.dataset.cargado){cargarComentarios(id);} }
function cargarComentarios(id){const loading=document.getElementById('comments-loading-'+id);const cont=document.getElementById('comments-'+id);fetch('cargar_comentarios.php?publicacion_id='+id).then(r=>r.text()).then(html=>{if(loading)loading.style.display='none';cont.innerHTML=html;cont.dataset.cargado='true';}).catch(err=>{console.error(err);if(loading)loading.innerHTML='<p style="color:var(--naranja-principal);">Error al cargar comentarios</p>';});}

document.querySelectorAll('.like-btn-compact').forEach(btn=>btn.addEventListener('click',function(e){e.preventDefault();const pid=this.dataset.publicacionId;const icon=this.querySelector('i');const count=this.querySelector('.like-count-compact');fetch('like.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'publicacion_id='+pid}).then(r=>r.json()).then(d=>{if(d.success){if(d.liked){icon.classList.remove('far');icon.classList.add('fas');icon.style.color='#e67e22';}else{icon.classList.remove('fas');icon.classList.add('far');icon.style.color='';}count.textContent=d.likes;}}).catch(console.error);}));

window.addEventListener('click',e=>{const modal=document.getElementById('editarPublicacionModal');if(e.target===modal)cerrarModalEdicion();});

// Lazy enhancement (si hiciera falta para imágenes nuevas)
document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('img[loading="lazy"]').forEach(img=>{ /* placeholder hook */ });});
</script>

</body>
</html>
