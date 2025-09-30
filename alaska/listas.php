<?php
/**
 * Listas personalizadas
 */
require_once __DIR__ . '/includes/header.php';
redirigirSiNoAutenticado();

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_lista'])) {
    $nombre = trim($_POST['nombre_lista'] ?? '');
    $descripcion = trim($_POST['descripcion_lista'] ?? '');
    $es_privada = isset($_POST['lista_privada']);
    if (empty($nombre)) {
        $mensaje = 'El nombre de la lista es obligatorio';
        $tipo_mensaje = 'error';
    } elseif (mb_strlen($nombre) > 50) {
        $mensaje = 'El nombre de la lista es demasiado largo';
        $tipo_mensaje = 'error';
    } else {
        if (crearLista($pdo, $usuario['id'], $nombre, $descripcion, $es_privada)) {
            $mensaje = 'Lista creada correctamente';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al crear la lista';
            $tipo_mensaje = 'error';
        }
    }
}

$listas = obtenerListas($pdo, $usuario['id']);
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
        <a href="<?= url('guardados.php') ?>" class="sidebar-item">
            <i class="fas fa-bookmark"></i>
            <span>Guardados</span>
        </a>
        <a href="<?= url('listas.php') ?>" class="sidebar-item active">
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
        <a href="<?= url('publicar.php') ?>" class="btn btn-primary" style="padding:.8rem; margin:0 1.2rem; text-align:center; text-decoration:none;">
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
        <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
        <?php endif; ?>

        <div class="post">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="color: var(--verde-principal);"><i class="fas fa-list-alt"></i> Tus Listas</h2>
                <button class="btn btn-primary" onclick="abrirCrearLista()" style="padding:.5rem 1rem; font-size:.9rem;"><i class="fas fa-plus"></i> Nueva lista</button>
            </div>

            <?php if (empty($listas)): ?>
                <div style="text-align:center; padding:2rem;">
                    <i class="fas fa-list" style="font-size:3rem; color: var(--gris-medio); margin-bottom:1rem;"></i>
                    <h3>No tienes listas creadas</h3>
                    <p>Crea listas para organizar a las personas que sigues</p>
                </div>
            <?php else: ?>
                <?php foreach ($listas as $lista): ?>
                <div class="post" style="margin-bottom:1rem; padding:1.5rem;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
                        <div>
                            <h3 style="color: var(--naranja-principal);"><?= htmlspecialchars($lista['nombre']) ?></h3>
                            <?php if (!empty($lista['descripcion'])): ?>
                                <p style="color: var(--gris-oscuro); margin-top:.5rem;"><?= htmlspecialchars($lista['descripcion']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ((int)$lista['es_privada'] === 1): ?>
                                <span style="background-color: var(--naranja-principal); color:white; padding:.2rem .5rem; border-radius:10px; font-size:.8rem;"><i class="fas fa-lock"></i> Privada</span>
                            <?php else: ?>
                                <span style="background-color: var(--verde-principal); color:white; padding:.2rem .5rem; border-radius:10px; font-size:.8rem;"><i class="fas fa-globe"></i> Pública</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display:flex; gap:1rem;">
                        <button class="btn btn-secondary" style="flex:1;"><i class="fas fa-users"></i> Ver miembros</button>
                        <button class="btn btn-secondary" style="flex:1;"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-secondary" style="flex:1;"><i class="fas fa-trash"></i> Eliminar</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<div id="crearListaModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,.5); z-index:1000;">
    <div style="background:white; margin:10% auto; padding:2rem; width:90%; max-width:500px; border-radius: var(--borde-radius);">
        <h3>Crear Nueva Lista</h3>
        <form method="POST" style="margin-top:1rem;">
            <input type="hidden" name="crear_lista" value="1">
            <div class="form-group">
                <label>Nombre de la lista *</label>
                <input type="text" name="nombre_lista" class="form-control" placeholder="Ej: Veterinarios, Refugios, etc." required maxlength="50">
            </div>
            <div class="form-group">
                <label>Descripción (opcional)</label>
                <textarea name="descripcion_lista" class="form-control" rows="3" placeholder="Describe el propósito de esta lista"></textarea>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="lista_privada"> Hacer esta lista privada
                </label>
                <p style="font-size:.9rem; color: var(--gris-oscuro); margin-top:.3rem;">Solo tú podrás ver esta lista</p>
            </div>
            <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalLista()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear lista</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirCrearLista(){document.getElementById('crearListaModal').style.display='block'}
function cerrarModalLista(){document.getElementById('crearListaModal').style.display='none'}
window.onclick=function(e){const m=document.getElementById('crearListaModal'); if(e.target===m){cerrarModalLista();}}
</script>

</body>
</html>
