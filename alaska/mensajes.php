<?php
/**
 * Página principal de mensajes
 */
require_once __DIR__ . '/includes/header.php';
redirigirSiNoAutenticado();

$conversaciones = obtenerConversaciones($pdo, $usuario['id']);
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
        <a href="<?= url('mensajes.php') ?>" class="sidebar-item active">
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
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="color: var(--verde-principal);">
                    <i class="fas fa-envelope"></i> Mensajes
                </h2>
                <button class="btn btn-primary" onclick="abrirNuevoMensaje()" style="padding:.5rem 1rem; font-size:.9rem;">
                    <i class="fas fa-plus"></i> Nuevo mensaje
                </button>
            </div>

            <?php if (empty($conversaciones)): ?>
                <div style="text-align:center; padding:2rem;">
                    <i class="fas fa-envelope-open" style="font-size:3rem; color: var(--gris-medio); margin-bottom:1rem;"></i>
                    <h3>No tienes mensajes</h3>
                    <p>Empieza una conversación con alguien de la comunidad</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversaciones as $conv): ?>
                <a href="<?= url('chat.php?id=' . ($conv['usuario1_id'] == $usuario['id'] ? $conv['usuario2_id'] : $conv['usuario1_id'])) ?>" 
                   class="post" style="display:flex; gap:1rem; margin-bottom:1rem; padding:1rem; text-decoration:none; color:inherit;">
                    <div class="avatar" style="width:60px; height:60px;">
                        <?php if (!empty($conv['otro_usuario_foto']) && file_exists(__DIR__ . '/uploads/' . $conv['otro_usuario_foto'])): ?>
                            <img src="<?= url('uploads/' . htmlspecialchars($conv['otro_usuario_foto'])) ?>" alt="Foto de perfil">
                        <?php else: ?>
                            <?= obtenerIniciales($conv['otro_usuario_nombre']) ?>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.3rem;">
                            <div class="post-user"><?= htmlspecialchars($conv['otro_usuario_nombre']) ?></div>
                            <?php if (!empty($conv['fecha_ultimo_mensaje'])): ?>
                                <div class="post-time" style="font-size:.8rem;">
                                    <?= date('d M', strtotime($conv['fecha_ultimo_mensaje'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($conv['ultimo_mensaje'])): ?>
                            <div style="color: var(--gris-oscuro); font-size:.9rem; margin-bottom:.3rem;">
                                <?= htmlspecialchars(mb_strimwidth($conv['ultimo_mensaje'], 0, 50, '...')) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal para nuevo mensaje -->
<div id="nuevoMensajeModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,.5); z-index:1000;">
    <div style="background:white; margin:10% auto; padding:2rem; width:90%; max-width:500px; border-radius: var(--borde-radius);">
        <h3>Nuevo Mensaje</h3>
        <div class="form-group" style="margin-top:1rem;">
            <label>Buscar persona:</label>
            <input type="text" id="buscarPersona" class="form-control" placeholder="Nombre o username...">
            <div id="resultadosBusqueda" style="margin-top:.5rem; max-height:200px; overflow-y:auto;"></div>
        </div>
        <div style="display:flex; gap:1rem; margin-top:1.5rem;">
            <button class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="iniciarConversacion()">Enviar</button>
        </div>
    </div>
</div>

<script>
function abrirNuevoMensaje(){document.getElementById('nuevoMensajeModal').style.display='block'}
function cerrarModal(){document.getElementById('nuevoMensajeModal').style.display='none';document.getElementById('buscarPersona').value='';document.getElementById('resultadosBusqueda').innerHTML=''}
function iniciarConversacion(){const i=document.getElementById('buscarPersona');const v=i.value.trim();if(v){alert('Busca usuarios reales en versión completa');cerrarModal();}}

document.getElementById('buscarPersona').addEventListener('input',function(){const q=this.value.trim();const r=document.getElementById('resultadosBusqueda');if(q.length<2){r.innerHTML='';return;}r.innerHTML=`
        <div style="padding:.5rem; cursor:pointer; border-bottom:1px solid #eee;" onclick="seleccionarUsuario('1','María Vargas')">
            <div style="font-weight:bold;">María Vargas</div>
            <div style="font-size:.9rem; color:#666;">@mariavargas</div>
        </div>
        <div style="padding:.5rem; cursor:pointer; border-bottom:1px solid #eee;" onclick="seleccionarUsuario('2','Carlos Rodríguez')">
            <div style="font-weight:bold;">Carlos Rodríguez</div>
            <div style="font-size:.9rem; color:#666;">@carlosrodriguez</div>
        </div>`;});
function seleccionarUsuario(id,nombre){document.getElementById('buscarPersona').value=nombre}
</script>

</body>
</html>
