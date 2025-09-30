<?php
/**
 * Página de ajustes de cuenta - VERSIÓN MEJORADA
 * Incluye gestión de mascotas guardadas y mejora del diseño
 *
 * Reordenado para evitar salida antes de posibles redirects.
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/funciones.php';
require_once 'includes/sesion.php';
redirigirSiNoAutenticado();
$usuario = obtenerUsuarioSesion();
if ($usuario) { actualizarUltimoAcceso(); }

$mensaje = '';
$tipo_mensaje = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Distinguimos operaciones antes de volver a cargar datos al final
    if (isset($_POST['agregar_mascota']) || isset($_POST['eliminar_mascota'])) {
        // Operaciones de mascotas guardadas se gestionan más abajo para mantener orden lógico
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $biografia = trim($_POST['biografia'] ?? '');
        $ubicacion = trim($_POST['ubicacion'] ?? '');
        $es_publico = isset($_POST['es_publico']);
        $foto_perfil = $usuario['foto_perfil'];
        $fotos_mascotas = json_decode($usuario['fotos_mascotas'] ?? '[]', true) ?: [];
        if (empty($nombre) || empty($username)) {
            $mensaje = 'El nombre y el nombre de usuario son obligatorios';
            $tipo_mensaje = 'error';
        } elseif (strlen($username) < 3) {
            $mensaje = 'El nombre de usuario debe tener al menos 3 caracteres';
            $tipo_mensaje = 'error';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
            $stmt->execute([$username, $usuario['id']]);
            if ($stmt->rowCount() > 0) {
                $mensaje = 'El nombre de usuario ya está en uso';
                $tipo_mensaje = 'error';
            } else {
                if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024;
                    if (in_array($_FILES['foto_perfil']['type'], $allowed_types) && $_FILES['foto_perfil']['size'] <= $max_size) {
                        if ($foto_perfil !== 'default.jpg' && file_exists('uploads/perfiles/' . $foto_perfil)) {
                            unlink('uploads/perfiles/' . $foto_perfil);
                        }
                        $extension = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
                        $foto_perfil = 'perfil_' . $usuario['id'] . '_' . time() . '.' . $extension;
                        $upload_path = 'uploads/perfiles/' . $foto_perfil;
                        if (!move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $upload_path)) {
                            $mensaje = 'Error al subir la foto de perfil';
                            $tipo_mensaje = 'error';
                            $foto_perfil = $usuario['foto_perfil'];
                        }
                    } else {
                        $mensaje = 'Formato de imagen no válido o archivo demasiado grande (máximo 5MB)';
                        $tipo_mensaje = 'error';
                    }
                }
                if (isset($_POST['eliminar_foto_perfil'])) {
                    if ($usuario['foto_perfil'] !== 'default.jpg' && file_exists('uploads/perfiles/' . $usuario['foto_perfil'])) {
                        unlink('uploads/perfiles/' . $usuario['foto_perfil']);
                    }
                    $foto_perfil = 'default.jpg';
                }
                $nuevas_fotos_mascotas = [];
                for ($i = 0; $i < 5; $i++) {
                    if (isset($_FILES['foto_mascota_' . $i]) && $_FILES['foto_mascota_' . $i]['error'] === UPLOAD_ERR_OK) {
                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                        $max_size = 5 * 1024 * 1024;
                        if (in_array($_FILES['foto_mascota_' . $i]['type'], $allowed_types) && $_FILES['foto_mascota_' . $i]['size'] <= $max_size) {
                            $extension = pathinfo($_FILES['foto_mascota_' . $i]['name'], PATHINFO_EXTENSION);
                            $nombre_foto = 'mascota_' . $usuario['id'] . '_' . time() . '_' . $i . '.' . $extension;
                            $upload_path = 'uploads/mascotas/' . $nombre_foto;
                            if (move_uploaded_file($_FILES['foto_mascota_' . $i]['tmp_name'], $upload_path)) {
                                $nuevas_fotos_mascotas[] = $nombre_foto;
                            }
                        }
                    }
                }
                $fotos_mascotas = json_decode($usuario['fotos_mascotas'] ?? '[]', true) ?: [];
                $eliminar_fotos = $_POST['eliminar_foto_mascota'] ?? [];
                foreach ($fotos_mascotas as $foto) {
                    if (!in_array($foto, $eliminar_fotos)) {
                        $nuevas_fotos_mascotas[] = $foto;
                    } else {
                        if (file_exists('uploads/mascotas/' . $foto)) {
                            unlink('uploads/mascotas/' . $foto);
                        }
                    }
                }
                $nuevas_fotos_mascotas = array_slice($nuevas_fotos_mascotas, 0, 5);
                if ($tipo_mensaje !== 'error') {
                    $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, username = ?, biografia = ?, ubicacion = ?, es_publico = ?, foto_perfil = ?, fotos_mascotas = ? WHERE id = ?");
                    $fotos_json = json_encode($nuevas_fotos_mascotas);
                    $stmt->execute([$nombre, $username, $biografia, $ubicacion, $es_publico, $foto_perfil, $fotos_json, $usuario['id']]);
                    $_SESSION['usuario_nombre'] = $nombre;
                    $_SESSION['usuario_username'] = $username;
                    $mensaje = 'Perfil actualizado correctamente en ALASKA';
                    $tipo_mensaje = 'success';
                    $usuario = obtenerUsuarioPorId($pdo, $usuario['id']);
                }
            }
        }
    }
}

// Crear carpetas si no existen
if (!file_exists('uploads/perfiles')) { mkdir('uploads/perfiles', 0755, true); }
if (!file_exists('uploads/mascotas')) { mkdir('uploads/mascotas', 0755, true); }

$fotos_mascotas = json_decode($usuario['fotos_mascotas'] ?? '[]', true) ?: [];
$mascotas_guardadas = obtenerMascotasGuardadas($pdo, $usuario['id']);

// Agregar mascota guardada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_mascota'])) {
    $nombre_mascota = trim($_POST['nombre_mascota'] ?? '');
    $descripcion = trim($_POST['descripcion_mascota'] ?? '');
    if (!empty($nombre_mascota)) {
        $nueva_mascota = [ 'nombre' => $nombre_mascota, 'descripcion' => $descripcion, 'fecha_agregado' => date('Y-m-d H:i:s') ];
        $mascotas_guardadas[] = $nueva_mascota;
        guardarMascotasGuardadas($pdo, $usuario['id'], $mascotas_guardadas);
        $mensaje = 'Mascota agregada a tu lista';
        $tipo_mensaje = 'success';
        $mascotas_guardadas = obtenerMascotasGuardadas($pdo, $usuario['id']);
    } else {
        $mensaje = 'El nombre de la mascota es obligatorio';
        $tipo_mensaje = 'error';
    }
}

// Eliminar mascota guardada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_mascota'])) {
    $indice = (int)$_POST['indice_mascota'];
    if (isset($mascotas_guardadas[$indice])) {
        unset($mascotas_guardadas[$indice]);
        $mascotas_guardadas = array_values($mascotas_guardadas);
        guardarMascotasGuardadas($pdo, $usuario['id'], $mascotas_guardadas);
        $mensaje = 'Mascota eliminada de tu lista';
        $tipo_mensaje = 'success';
        $mascotas_guardadas = obtenerMascotasGuardadas($pdo, $usuario['id']);
    }
}

// Emitir cabecera tras toda la lógica
require_once 'includes/header.php';
?>

<div class="container">
    <!-- Barra lateral con menú de navegación -->
    <aside class="sidebar">
        <a href="index.php" class="sidebar-item">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
        </a>
        <a href="explorar.php" class="sidebar-item">
            <i class="fas fa-hashtag"></i>
            <span>Explorar</span>
        </a>
        <a href="notificaciones.php" class="sidebar-item">
            <i class="fas fa-bell"></i>
            <span>Notificaciones</span>
        </a>
        <a href="mensajes.php" class="sidebar-item">
            <i class="fas fa-envelope"></i>
            <span>Mensajes</span>
        </a>
        <a href="guardados.php" class="sidebar-item">
            <i class="fas fa-bookmark"></i>
            <span>Guardados</span>
        </a>
        <a href="listas.php" class="sidebar-item">
            <i class="fas fa-list-alt"></i>
            <span>Listas</span>
        </a>
        <a href="perfil.php?id=<?= $usuario['id'] ?>" class="sidebar-item">
            <i class="fas fa-user"></i>
            <span>Perfil</span>
        </a>
        <a href="ajustes.php" class="sidebar-item active">
            <i class="fas fa-cog"></i>
            <span>Ajustes</span>
        </a>
        
        <div class="divider"></div>
        
        <a href="publicar.php" class="btn btn-primary" style="padding: 0.8rem; margin: 0 1.2rem; text-align: center; text-decoration: none;">
            <i class="fas fa-feather-alt"></i>
            Publicar
        </a>
        
        <div class="divider"></div>
        
        <a href="logout.php" class="sidebar-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar sesión</span>
        </a>
    </aside>

    <!-- Contenido principal con formulario de ajustes -->
    <main class="main-content">
        <div class="settings-container">
            <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipo_mensaje ?>">
                <?= htmlspecialchars($mensaje) ?>
            </div>
            <?php endif; ?>
            
            <h2 class="settings-title">Ajustes de ALASKA</h2>
            
            <!-- Información personal -->
            <form method="POST" enctype="multipart/form-data">
            <div class="settings-section">
                <h3>Información Personal</h3>
                <div class="form-group">
                    <label for="name">Nombre completo</label>
                    <input type="text" id="name" name="nombre" class="form-control" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="username">Nombre de usuario</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($usuario['username']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="bio">Biografía</label>
                    <textarea id="bio" name="biografia" class="form-control" rows="3"><?= htmlspecialchars($usuario['biografia']) ?></textarea>
                </div>
                <div class="form-group">
                    <label for="location">Ubicación</label>
                    <input type="text" id="location" name="ubicacion" class="form-control" value="<?= htmlspecialchars($usuario['ubicacion']) ?>">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="es_publico" <?= $usuario['es_publico'] ? 'checked' : '' ?>>
                        Hacer mi perfil público
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
            
            <!-- Foto de Perfil -->
            <div class="settings-section">
                <h3>Foto de Perfil</h3>
                <div class="form-group" style="text-align: center; margin-bottom: 1.5rem;">
                    <div class="avatar-preview" style="width: 120px; height: 120px; border-radius: 50%; margin: 0 auto 1rem; overflow: hidden; border: 3px solid var(--gris-medio);">
                        <?php if ($usuario['foto_perfil'] && file_exists('uploads/perfiles/' . $usuario['foto_perfil'])): ?>
                            <img src="uploads/perfiles/<?= htmlspecialchars($usuario['foto_perfil']) ?>" alt="Foto de perfil actual" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; background-color: var(--verde-principal); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold;">
                                <?= substr($usuario['nombre'], 0, 1) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <label class="btn btn-secondary" style="cursor: pointer;">
                            <input type="file" name="foto_perfil" accept="image/*" style="display: none;">
                            Cambiar foto
                        </label>
                        <?php if ($usuario['foto_perfil'] !== 'default.jpg'): ?>
                        <button type="submit" name="eliminar_foto_perfil" class="btn btn-secondary" style="background-color: var(--naranja-principal); border-color: var(--naranja-principal);">
                            Eliminar foto
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Fotos de Mascotas -->
            <div class="settings-section">
                <h3>Fotos de Mis Mascotas</h3>
                <p style="margin-bottom: 1.5rem; color: var(--gris-oscuro);">Sube hasta 5 fotos de tus mascotas para mostrar en tu perfil</p>
                
                <div class="form-group">
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                        <?php foreach ($fotos_mascotas as $index => $foto): ?>
                        <div style="position: relative;">
                            <img src="uploads/mascotas/<?= htmlspecialchars($foto) ?>" alt="Foto de mascota" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px;">
                            <label style="position: absolute; top: 5px; right: 5px; background: rgba(231, 76, 60, 0.8); color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                                <input type="checkbox" name="eliminar_foto_mascota[]" value="<?= htmlspecialchars($foto) ?>" style="display: none;">
                                <i class="fas fa-times"></i>
                            </label>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php for ($i = count($fotos_mascotas); $i < 5; $i++): ?>
                        <div style="border: 2px dashed var(--gris-medio); border-radius: 8px; display: flex; align-items: center; justify-content: center; height: 120px;">
                            <label style="cursor: pointer; text-align: center; color: var(--gris-oscuro);">
                                <i class="fas fa-camera" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i><br>
                                <small>Subir foto</small>
                                <input type="file" name="foto_mascota_<?= $i ?>" accept="image/*" style="display: none;">
                            </label>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            
            <!-- Mascotas Guardadas -->
            <div class="settings-section">
                <h3>Mascotas Guardadas</h3>
                <p style="margin-bottom: 1.5rem; color: var(--gris-oscuro);">Gestiona las mascotas que deseas seguir o recordar</p>
                
                <!-- Formulario para agregar nueva mascota -->
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <h4>Agregar nueva mascota</h4>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 200px;">
                            <label>Nombre de la mascota:</label>
                            <input type="text" name="nombre_mascota" class="form-control" placeholder="Nombre de la mascota" required>
                        </div>
                        <div style="flex: 1; min-width: 200px;">
                            <label>Descripción (opcional):</label>
                            <textarea name="descripcion_mascota" class="form-control" rows="2" placeholder="Características, raza, etc."></textarea>
                        </div>
                        <div style="align-self: flex-end;">
                            <button type="submit" name="agregar_mascota" class="btn btn-primary" style="height: 100%;">Agregar</button>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de mascotas guardadas -->
                <?php if (!empty($mascotas_guardadas)): ?>
                <div style="background-color: var(--gris-claro); padding: 1rem; border-radius: var(--borde-radius); margin-bottom: 1.5rem;">
                    <h4>Mascotas en tu lista (<?= count($mascotas_guardadas) ?>)</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem; margin-top: 1rem;">
                        <?php foreach ($mascotas_guardadas as $index => $mascota): ?>
                        <div style="background: white; padding: 1rem; border-radius: 8px; box-shadow: var(--sombra); position: relative;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                <div>
                                    <h5 style="margin: 0; color: var(--naranja-principal);"><?= htmlspecialchars($mascota['nombre']) ?></h5>
                                    <div style="font-size: 0.9rem; color: var(--gris-oscuro);">Agregada: <?= date('d M Y', strtotime($mascota['fecha_agregado'])) ?></div>
                                </div>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta mascota?');">
                                    <input type="hidden" name="indice_mascota" value="<?= $index ?>">
                                    <input type="hidden" name="eliminar_mascota" value="1">
                                    <button type="submit" style="background: none; border: none; cursor: pointer; color: var(--naranja-principal); font-size: 1.2rem;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                            <?php if (!empty($mascota['descripcion'])): ?>
                            <p style="margin: 0.5rem 0 0; font-size: 0.9rem; line-height: 1.4;"><?= htmlspecialchars($mascota['descripcion']) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 2rem; background-color: var(--gris-claro); border-radius: var(--borde-radius);">
                    <i class="fas fa-paw" style="font-size: 3rem; color: var(--gris-medio); margin-bottom: 1rem;"></i>
                    <h3>No tienes mascotas guardadas</h3>
                    <p>Agrega mascotas que quieras seguir o recordar</p>
                </div>
                <?php endif; ?>
            </div>
            </form>
        </div>
    </main>
</div>

<script>
// Manejar eliminación de fotos de mascotas
document.querySelectorAll('input[name="eliminar_foto_mascota[]"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const icon = this.nextElementSibling;
        if (this.checked) {
            icon.style.background = 'rgba(46, 204, 113, 0.8)';
            icon.innerHTML = '<i class="fas fa-check"></i>';
        } else {
            icon.style.background = 'rgba(231, 76, 60, 0.8)';
            icon.innerHTML = '<i class="fas fa-times"></i>';
        }
    });
});

// Manejar preview de nuevas fotos (opcional)
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                console.log('Archivo seleccionado:', e.target.result);
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
});
</script>

</body>
</html>
