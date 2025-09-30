<?php
/**
 * Página de publicación - VERSIÓN MEJORADA
 * Incluye selección de mascotas, ubicación y elimina botón de calendario
 *
 * Reordenado para ejecutar autenticación y lógica antes de emitir HTML (evita warnings de headers).
 */
// Incluir dependencias sin emitir salida todavía
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/funciones.php';
require_once 'includes/sesion.php';
redirigirSiNoAutenticado();
$usuario = obtenerUsuarioSesion();
if ($usuario) { actualizarUltimoAcceso(); }

$mensaje = '';
$tipo_mensaje = '';

// Obtener mascotas guardadas del usuario (necesita $usuario)
if ($usuario) {
    $mascotas_guardadas = obtenerMascotasGuardadas($pdo, $usuario['id']);
} else {
    $mascotas_guardadas = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contenido = trim($_POST['contenido'] ?? '');
    $tipo = $_POST['tipo'] ?? 'general';
    $nombre_mascota = trim($_POST['nombre_mascota'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $imagen = null;
    
    // Validar tipo
    $tipos_validos = ['general', 'mascota', 'evento', 'consejo'];
    if (!in_array($tipo, $tipos_validos)) {
        $tipo = 'general';
    }
    
    if (empty($contenido)) {
        $mensaje = 'El contenido de la publicación es obligatorio';
        $tipo_mensaje = 'error';
    } else {
        $analisis = analizarContenidoModeracion($contenido);
        if ($analisis['is_toxic']) {
            $mensaje = 'El contenido fue detectado como potencialmente inapropiado. Revisa las normas de la comunidad.';
            $tipo_mensaje = 'error';
            crearReporteAutomatico($pdo, 0, 'publicacion', $usuario['id']);
        } else {
            // Manejar la subida de imagen
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024;
                if (in_array($_FILES['imagen']['type'], $allowed_types) && $_FILES['imagen']['size'] <= $max_size) {
                    $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
                    $imagen = uniqid() . '.' . $extension;
                    $upload_path = 'uploads/' . $imagen;
                    if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $upload_path)) {
                        $mensaje = 'Error al subir la imagen';
                        $tipo_mensaje = 'error';
                        $imagen = null;
                    }
                } else {
                    $mensaje = 'Formato de imagen no válido o archivo demasiado grande (máximo 5MB)';
                    $tipo_mensaje = 'error';
                }
            }
        }
        if ($tipo_mensaje !== 'error') {
            $stmt = $pdo->prepare("INSERT INTO publicaciones (usuario_id, contenido, imagen, tipo, nombre_mascota, ubicacion) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$usuario['id'], $contenido, $imagen, $tipo, $nombre_mascota, $ubicacion])) {
                redirect('index.php');
            } else {
                $mensaje = 'Error al crear la publicación';
                $tipo_mensaje = 'error';
            }
        }
    }
}

// Emitir cabecera después de toda la lógica y posibles redirects
require_once 'includes/header.php';
?>

<div class="container">
    <!-- Barra lateral con menú de navegación -->
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
    <a href="<?= app_url('perfil.php?id='.$usuario['id']) ?>" class="sidebar-item">
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

    <!-- Contenido principal con formulario de publicación -->
    <main class="main-content">
        <div class="post-form">
            <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipo_mensaje ?>">
                <?= htmlspecialchars($mensaje) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="post-header">
                    <div class="avatar">
                        <?php if ($usuario['foto_perfil'] && file_exists('uploads/perfiles/' . $usuario['foto_perfil'])): ?>
                            <img src="<?= app_url('uploads/perfiles/' . htmlspecialchars($usuario['foto_perfil'])) ?>" alt="Foto de perfil">
                        <?php else: ?>
                            <?= substr($usuario['nombre'], 0, 1) ?>
                        <?php endif; ?>
                    </div>
                    <textarea name="contenido" class="post-input" placeholder="¿Qué está pasando con tus mascotas?" required></textarea>
                </div>
                
                <!-- Tipo de publicación -->
                <div class="form-group" style="margin: 1rem 0;">
                    <label>Tipo de publicación:</label>
                    <select name="tipo" class="form-control" onchange="toggleNombreMascota(this.value)">
                        <option value="general">General</option>
                        <option value="mascota">Sobre mi mascota</option>
                        <option value="evento">Evento</option>
                        <option value="consejo">Consejo</option>
                    </select>
                </div>
                
                <!-- Nombre de la mascota (solo si es tipo mascota) -->
                <div class="form-group" id="nombreMascotaField" style="display: none; margin: 1rem 0;">
                    <label>Nombre de la mascota:</label>
                    <select name="nombre_mascota" class="form-control">
                        <option value="">Selecciona una mascota</option>
                        <?php foreach ($mascotas_guardadas as $mascota): ?>
                        <option value="<?= htmlspecialchars($mascota['nombre']) ?>"><?= htmlspecialchars($mascota['nombre']) ?></option>
                        <?php endforeach; ?>
                        <option value="otro">Otra mascota...</option>
                    </select>
                    <input type="text" name="nombre_mascota_otro" class="form-control" placeholder="Nombre de la mascota" style="margin-top: 0.5rem; display: none;" onkeyup="actualizarNombreMascota()">
                </div>
                
                <!-- Ubicación -->
                <div class="form-group" style="margin: 1rem 0;">
                    <label>Ubicación (opcional):</label>
                    <input type="text" name="ubicacion" class="form-control" placeholder="Ciudad, país, lugar específico..." value="">
                    <p style="font-size: 0.8rem; color: var(--gris-oscuro); margin-top: 0.3rem;">Ejemplo: Ciudad de México, Parque Central, Hospital Veterinario</p>
                </div>
                
                <!-- Acciones de publicación -->
                <div class="post-actions">
                    <div class="action-icons">
                        <label class="action-icon" style="cursor: pointer;">
                            <i class="fas fa-image"></i>
                            <input type="file" name="imagen" accept="image/*" style="display: none;">
                        </label>
                        <div class="action-icon" title="Seleccionar mascota">
                            <i class="fas fa-paw"></i>
                        </div>
                        <div class="action-icon" title="Agregar ubicación">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                    </div>
                    <button type="submit" class="post-btn">Publicar</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
function toggleNombreMascota(tipo) {
    const field = document.getElementById('nombreMascotaField');
    if (tipo === 'mascota') {
        field.style.display = 'block';
    } else {
        field.style.display = 'none';
    }
}

function actualizarNombreMascota() {
    const select = document.querySelector('select[name="nombre_mascota"]');
    const input = document.querySelector('input[name="nombre_mascota_otro"]');
    const valorSelect = select.value;
    
    if (valorSelect === 'otro') {
        input.style.display = 'block';
    } else {
        input.style.display = 'none';
    }
}

// Event listener para el select de mascotas
document.querySelector('select[name="nombre_mascota"]').addEventListener('change', function() {
    actualizarNombreMascota();
});

// Prevenir el envío del formulario si se selecciona "Otra mascota" y no se ingresa nombre
document.querySelector('form').addEventListener('submit', function(e) {
    const select = document.querySelector('select[name="nombre_mascota"]');
    const input = document.querySelector('input[name="nombre_mascota_otro"]');
    const valorSelect = select.value;
    
    if (valorSelect === 'otro' && input.style.display === 'block' && input.value.trim() === '') {
        e.preventDefault();
        alert('Por favor ingresa el nombre de la mascota');
    }
});
</script>

</body>
</html>
