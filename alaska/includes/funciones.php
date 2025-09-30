<?php
/**
 * Funciones comunes para la red social ALASKA
 *
 * Contiene utilidades de sesión, autenticación, consultas a la base de datos
 * con PDO (siempre usando consultas preparadas para evitar inyecciones SQL),
 * cálculos de métricas (likes, comentarios, seguidores), formateos (tiempo),
 * y helpers para limpiar texto y generar iniciales.
 */

// Inicia la sesión si aún no está activa. Es importante para manejar login,
// persistencia de usuario y validaciones de acceso a páginas privadas.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Obtiene un usuario por su ID.
 * @param PDO   $pdo Conexión a la BD
 * @param int   $id  ID del usuario
 * @return array|false Registro del usuario o false si no existe
 */
function obtenerUsuarioPorId($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Obtiene publicaciones para el feed, paginadas por limit/offset.
 * Importante: LIMIT y OFFSET se bindean con PDO::PARAM_INT para evitar el
 * error SQL 1064 de MySQL al pasar strings.
 * @param PDO $pdo
 * @param int $limit  Número máximo de publicaciones a devolver
 * @param int $offset Desplazamiento para paginación
 * @return array Lista de publicaciones con datos del autor
 */
function obtenerPublicaciones($pdo, $limit = 10, $offset = 0) {
    $limit = (int)$limit;
    $offset = (int)$offset;
    
    $stmt = $pdo->prepare("
        SELECT p.*, u.nombre, u.username, u.foto_perfil 
        FROM publicaciones p 
        JOIN usuarios u ON p.usuario_id = u.id 
        ORDER BY p.fecha_publicacion DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->bindParam(1, $limit, PDO::PARAM_INT);
    $stmt->bindParam(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene publicaciones optimizadas (feed) evitando N+1 combinando agregados.
 * Incluye conteo de likes/comentarios y flag si el usuario actual dio like.
 */
function obtenerPublicacionesOptimizadas($pdo, $usuario_actual_id, $limit = 10, $offset = 0){
    $limit = (int)$limit;
    $offset = (int)$offset;
    $uid = (int)$usuario_actual_id;
    $sql = "SELECT
                p.id,
                p.usuario_id,
                p.contenido,
                p.imagen,
                p.tipo,
                p.nombre_mascota,
                p.ubicacion,
                p.fecha_publicacion,
                u.nombre AS autor_nombre,
                u.username AS autor_username,
                u.foto_perfil AS autor_foto,
                COALESCE(lc.likes_count, 0) AS total_likes,
                COALESCE(cc.comments_count, 0) AS total_comentarios,
                CASE WHEN ul.publicacion_id IS NOT NULL THEN 1 ELSE 0 END AS usuario_dio_like,
                CASE WHEN g.publicacion_id IS NOT NULL THEN 1 ELSE 0 END AS usuario_guardado
            FROM publicaciones p
            INNER JOIN usuarios u ON p.usuario_id = u.id
            LEFT JOIN (
                SELECT publicacion_id, COUNT(*) AS likes_count FROM likes GROUP BY publicacion_id
            ) lc ON p.id = lc.publicacion_id
            LEFT JOIN (
                SELECT publicacion_id, COUNT(*) AS comments_count FROM comentarios GROUP BY publicacion_id
            ) cc ON p.id = cc.publicacion_id
            LEFT JOIN likes ul ON p.id = ul.publicacion_id AND ul.usuario_id = :uid
            LEFT JOIN guardados g ON p.id = g.publicacion_id AND g.usuario_id = :uid
            ORDER BY p.fecha_publicacion DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene todas las publicaciones de un usuario específico.
 * @param PDO $pdo
 * @param int $usuario_id
 * @return array
 */
function obtenerPublicacionesUsuario($pdo, $usuario_id) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.nombre, u.username, u.foto_perfil 
        FROM publicaciones p 
        JOIN usuarios u ON p.usuario_id = u.id 
        WHERE p.usuario_id = ? 
        ORDER BY p.fecha_publicacion DESC
    ");
    $stmt->execute([$usuario_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Cuenta cuántos likes tiene una publicación.
 * @param PDO $pdo
 * @param int $publicacion_id
 * @return int
 */
function contarLikes($pdo, $publicacion_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE publicacion_id = ?");
    $stmt->execute([$publicacion_id]);
    return $stmt->fetchColumn();
}

/**
 * Cuenta cuántos comentarios tiene una publicación.
 * @param PDO $pdo
 * @param int $publicacion_id
 * @return int
 */
function contarComentarios($pdo, $publicacion_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comentarios WHERE publicacion_id = ?");
    $stmt->execute([$publicacion_id]);
    return $stmt->fetchColumn();
}

/**
 * Verifica si un usuario ya dio like a una publicación.
 * @param PDO $pdo
 * @param int $usuario_id
 * @param int $publicacion_id
 * @return bool
 */
function usuarioYaDioLike($pdo, $usuario_id, $publicacion_id) {
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE usuario_id = ? AND publicacion_id = ?");
    $stmt->execute([$usuario_id, $publicacion_id]);
    return $stmt->rowCount() > 0;
}

/**
 * Devuelve el número de seguidores de un usuario.
 * @param PDO $pdo
 * @param int $usuario_id
 * @return int
 */
function obtenerSeguidores($pdo, $usuario_id) {
    static $cache = [];
    $usuario_id=(int)$usuario_id;
    if(!isset($cache[$usuario_id])){
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM seguidores WHERE seguido_id = ?");
        $stmt->execute([$usuario_id]);
        $cache[$usuario_id] = (int)$stmt->fetchColumn();
    }
    return $cache[$usuario_id];
}

/**
 * Devuelve el número de usuarios que sigue un usuario.
 * @param PDO $pdo
 * @param int $usuario_id
 * @return int
 */
function obtenerSiguiendo($pdo, $usuario_id) {
    static $cache = [];
    $usuario_id=(int)$usuario_id;
    if(!isset($cache[$usuario_id])){
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM seguidores WHERE seguidor_id = ?");
        $stmt->execute([$usuario_id]);
        $cache[$usuario_id] = (int)$stmt->fetchColumn();
    }
    return $cache[$usuario_id];
}

/**
 * Cuenta la cantidad total de publicaciones de un usuario.
 * @param PDO $pdo
 * @param int $usuario_id
 * @return int
 */
function contarPublicaciones($pdo, $usuario_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM publicaciones WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    return $stmt->fetchColumn();
}

/**
 * Verifica si un usuario sigue a otro usuario.
 * @param PDO $pdo
 * @param int $seguidor_id ID del usuario que sigue
 * @param int $seguido_id  ID del usuario seguido
 * @return bool
 */
function usuarioEstaSiguiendo($pdo, $seguidor_id, $seguido_id) {
    $stmt = $pdo->prepare("SELECT id FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
    $stmt->execute([$seguidor_id, $seguido_id]);
    return $stmt->rowCount() > 0;
}

// Compatibilidad: algunos archivos siguen llamando redirigirSiNoAutenticado() antes de incluir auth.php.
// Reintroducimos un wrapper seguro que delega a redirect()/redirect_to() si la sesión no está iniciada.
if(!function_exists('redirigirSiNoAutenticado')) {
    function redirigirSiNoAutenticado(): void {
        if (!isset($_SESSION['usuario_id'])) {
            if(function_exists('redirect')) { redirect('login.php'); }
            elseif(function_exists('redirect_to')) { redirect_to('login.php'); }
            else { header('Location: login.php'); exit; }
        }
    }
}

/**
 * Estadísticas de usuario con caché (archivo) reutilizable.
 */
function obtenerEstadisticasUsuario($pdo, $usuario_id){
    $usuario_id=(int)$usuario_id;
    if(!function_exists('getCachéEstadisticas')){
        // Fallback simple si la función de caché aún no existe
        return [
            'seguidores'=>obtenerSeguidores($pdo,$usuario_id),
            'siguiendo'=>obtenerSiguiendo($pdo,$usuario_id),
            'total_publicaciones'=>contarPublicaciones($pdo,$usuario_id)
        ];
    }
    return getCachéEstadisticas('estadisticas_usuario_'.$usuario_id, function() use ($pdo,$usuario_id){
        return [
            'seguidores'=>obtenerSeguidores($pdo,$usuario_id),
            'siguiendo'=>obtenerSiguiendo($pdo,$usuario_id),
            'total_publicaciones'=>contarPublicaciones($pdo,$usuario_id)
        ];
    }, 300);
}

/**
 * Actualiza estadísticas en sesión cada >5 min para evitar recomputar.
 */
function actualizarEstadisticasSesion($pdo){
    if(!isset($_SESSION['usuario_id'])) return;
    $now=time();
    $last = $_SESSION['usuario_estadisticas']['ultima_actualizacion'] ?? 0;
    if(($now - $last) > 300){
        $est = obtenerEstadisticasUsuario($pdo, $_SESSION['usuario_id']);
        $_SESSION['usuario_estadisticas'] = $est + ['ultima_actualizacion'=>$now];
    }
}

/**
 * Obtiene los comentarios de una publicación
 * @param PDO $pdo Conexión a la base de datos
 * @param int $publicacion_id ID de la publicación
 * @param int $limit Número máximo de comentarios
 * @return array Lista de comentarios
 */
/**
 * Obtiene comentarios de una publicación, ordenados por fecha ascendente.
 * @param PDO $pdo
 * @param int $publicacion_id
 * @param int $limit Máximo de comentarios a traer (paginación simple)
 * @return array
 */
function obtenerComentarios($pdo, $publicacion_id, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT c.*, u.nombre, u.username, u.foto_perfil 
        FROM comentarios c 
        JOIN usuarios u ON c.usuario_id = u.id 
        WHERE c.publicacion_id = ? 
        ORDER BY c.fecha_comentario ASC 
        LIMIT ?
    ");
    $stmt->bindParam(1, $publicacion_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Agrega un comentario a una publicación
 * @param PDO $pdo Conexión a la base de datos
 * @param int $publicacion_id ID de la publicación
 * @param int $usuario_id ID del usuario
 * @param string $contenido Contenido del comentario
 * @return bool True si se agregó correctamente, false si no
 */
/**
 * Inserta un comentario nuevo a una publicación.
 * Realiza validación básica (no vacío) y quita espacios.
 * @return bool true si se inserta exitosamente
 */
function agregarComentario($pdo, $publicacion_id, $usuario_id, $contenido) {
    if (empty(trim($contenido))) {
        return false;
    }
    
    $stmt = $pdo->prepare("INSERT INTO comentarios (publicacion_id, usuario_id, contenido) VALUES (?, ?, ?)");
    return $stmt->execute([$publicacion_id, $usuario_id, trim($contenido)]);
}

/**
 * Formatea el tiempo transcurrido desde una fecha
 * @param string $fecha Fecha en formato MySQL
 * @return string Tiempo formateado (ej: "hace 2 horas")
 */
/**
 * Convierte una fecha a un string tipo “hace X tiempo”.
 * @param string $fecha Fecha en formato MySQL (Y-m-d H:i:s)
 * @return string
 */
function tiempoTranscurrido($fecha) {
    $tiempo = time() - strtotime($fecha);
    
    if ($tiempo < 60) {
        return 'hace ' . $tiempo . ' segundos';
    } elseif ($tiempo < 3600) {
        $minutos = floor($tiempo / 60);
        return 'hace ' . $minutos . ' minuto' . ($minutos != 1 ? 's' : '');
    } elseif ($tiempo < 86400) {
        $horas = floor($tiempo / 3600);
        return 'hace ' . $horas . ' hora' . ($horas != 1 ? 's' : '');
    } elseif ($tiempo < 2592000) {
        $dias = floor($tiempo / 86400);
        return 'hace ' . $dias . ' día' . ($dias != 1 ? 's' : '');
    } else {
        return date('d/m/Y', strtotime($fecha));
    }
}

/**
 * Valida y limpia el contenido de texto
 * @param string $texto Texto a limpiar
 * @param int $max_length Longitud máxima
 * @return string Texto limpio
 */
/**
 * Limpia texto para salida segura en HTML y corta opcionalmente por longitud.
 * @param string   $texto
 * @param int|null $max_length
 * @return string
 */
function limpiarTexto($texto, $max_length = null) {
    $texto = trim($texto);
    $texto = htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    
    if ($max_length && strlen($texto) > $max_length) {
        $texto = substr($texto, 0, $max_length) . '...';
    }
    
    return $texto;
}

/**
 * Genera las iniciales de un nombre para mostrar en avatar
 * @param string $nombre Nombre completo
 * @return string Iniciales
 */
/**
 * Genera iniciales (1-2 letras) a partir de un nombre para usar como avatar.
 * @param string $nombre
 * @return string
 */
function obtenerIniciales($nombre) {
    $palabras = explode(' ', $nombre);
    $iniciales = '';
    
    foreach ($palabras as $palabra) {
        if (!empty($palabra)) {
            $iniciales .= strtoupper(substr($palabra, 0, 1));
            if (strlen($iniciales) >= 2) break;
        }
    }
    
    return $iniciales ?: substr(strtoupper($nombre), 0, 1);
}

/**
 * Valida que un ID sea entero positivo
 */
function validarId($id): bool {
    return filter_var($id, FILTER_VALIDATE_INT) !== false && (int)$id > 0;
}

/**
 * Normaliza un valor arbitrario (string/int) y devuelve un ID entero positivo.
 * @param mixed $valor
 * @return int|null
 */
function obtenerIdSeguro($valor): ?int {
    if (is_int($valor)) {
        return $valor > 0 ? $valor : null;
    }
    if (is_string($valor) || is_float($valor)) {
        $filtrado = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $filtrado === false ? null : (int)$filtrado;
    }
    return null;
}

/**
 * Crea una notificación para un usuario
 * @param int $usuario_id Usuario que recibirá la notificación
 * @param string $tipo    'like' | 'comentario' | 'seguimiento' | 'mencion'
 * @param int $actor_id   Usuario que realizó la acción
 * @param int|null $publicacion_id
 * @param int|null $comentario_id
 */
function crearNotificacion($pdo, int $usuario_id, string $tipo, int $actor_id, ?int $publicacion_id = null, ?int $comentario_id = null): bool {
    $tiposValidos = ['like','comentario','seguimiento','mencion'];
    if (!in_array($tipo, $tiposValidos, true) || $usuario_id === $actor_id) {
        return false; // no notificar acciones sobre uno mismo
    }
    $stmt = $pdo->prepare("INSERT INTO notificaciones (usuario_id, tipo, actor_id, publicacion_id, comentario_id) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$usuario_id, $tipo, $actor_id, $publicacion_id, $comentario_id]);
}

// ===================== GUARDADOS =====================

/** Verifica si una publicación está guardada por un usuario */
function publicacionGuardada($pdo, $usuario_id, $publicacion_id) {
    $stmt = $pdo->prepare("SELECT id FROM guardados WHERE usuario_id = ? AND publicacion_id = ?");
    $stmt->execute([$usuario_id, $publicacion_id]);
    return $stmt->rowCount() > 0;
}

/** Obtiene publicaciones guardadas por un usuario */
function obtenerPublicacionesGuardadas($pdo, $usuario_id, $limit = 20) {
    $limit = (int)$limit;
    $stmt = $pdo->prepare("
        SELECT p.*, u.nombre, u.username, u.foto_perfil 
        FROM guardados g
        JOIN publicaciones p ON g.publicacion_id = p.id
        JOIN usuarios u ON p.usuario_id = u.id
        WHERE g.usuario_id = ?
        ORDER BY g.fecha_guardado DESC
        LIMIT ?
    ");
    $stmt->bindParam(1, $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Guarda o quita una publicación de guardados */
function toggleGuardado($pdo, $usuario_id, $publicacion_id) {
    if (publicacionGuardada($pdo, $usuario_id, $publicacion_id)) {
        $stmt = $pdo->prepare("DELETE FROM guardados WHERE usuario_id = ? AND publicacion_id = ?");
        $stmt->execute([$usuario_id, $publicacion_id]);
        return false;
    } else {
        $stmt = $pdo->prepare("INSERT INTO guardados (usuario_id, publicacion_id) VALUES (?, ?)");
        $stmt->execute([$usuario_id, $publicacion_id]);
        return true;
    }
}

// ===================== LISTAS =====================

/** Obtiene las listas de un usuario */
function obtenerListas($pdo, $usuario_id) {
    $stmt = $pdo->prepare("
        SELECT * FROM listas 
        WHERE usuario_id = ? 
        ORDER BY fecha_creacion DESC
    ");
    $stmt->execute([$usuario_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Crea una nueva lista */
function crearLista($pdo, $usuario_id, $nombre, $descripcion = '', $es_privada = false) {
    if (empty(trim($nombre))) {
        return false;
    }
    $stmt = $pdo->prepare("
        INSERT INTO listas (usuario_id, nombre, descripcion, es_privada) 
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$usuario_id, trim($nombre), $descripcion, $es_privada ? 1 : 0]);
}

// ===================== NOTIFICACIONES =====================

/** Obtiene notificaciones de un usuario */
function obtenerNotificaciones($pdo, $usuario_id, $limit = 20) {
    $limit = (int)$limit;
    $stmt = $pdo->prepare("
        SELECT n.*, u.nombre as actor_nombre, u.username as actor_username, u.foto_perfil as actor_foto
        FROM notificaciones n
        JOIN usuarios u ON n.actor_id = u.id
        WHERE n.usuario_id = ?
        ORDER BY n.fecha_notificacion DESC
        LIMIT ?
    ");
    $stmt->bindParam(1, $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Marca notificaciones como leídas */
function marcarNotificacionesLeidas($pdo, $usuario_id) {
    $stmt = $pdo->prepare("UPDATE notificaciones SET leida = TRUE WHERE usuario_id = ? AND leida = FALSE");
    $stmt->execute([$usuario_id]);
}

/**
 * Obtiene notificaciones NO leídas (opcionalmente desde un ID mayor dado para streaming SSE)
 * @param PDO $pdo
 * @param int $usuario_id
 * @param int $desde_id Solo traer notificaciones con id > $desde_id
 * @param int $limit Límite de resultados
 */
function obtenerNotificacionesNoLeidas($pdo, $usuario_id, $desde_id = 0, $limit = 50) {
    $limit = (int)$limit;
    $desde_id = (int)$desde_id;
    $stmt = $pdo->prepare("SELECT n.*, u.nombre as actor_nombre, u.username as actor_username, u.foto_perfil as actor_foto
        FROM notificaciones n
        JOIN usuarios u ON n.actor_id = u.id
        WHERE n.usuario_id = ? AND n.leida = FALSE AND n.id > ?
        ORDER BY n.id ASC
        LIMIT ?");
    $stmt->bindParam(1, $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $desde_id, PDO::PARAM_INT);
    $stmt->bindParam(3, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Devuelve el total de notificaciones no leídas para un usuario.
 */
function contarNotificacionesNoLeidas($pdo, $usuario_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida = FALSE");
    $stmt->execute([$usuario_id]);
    return (int)$stmt->fetchColumn();
}

// ===================== MENSAJES =====================

/** Lista conversaciones del usuario (usa tabla conversaciones) con último mensaje */
function obtenerConversaciones($pdo, $usuario_id) {
    // Obtenemos las conversaciones del usuario
    $stmt = $pdo->prepare("SELECT * FROM conversaciones WHERE usuario1_id = ? OR usuario2_id = ? ORDER BY fecha_creacion DESC");
    $stmt->execute([$usuario_id, $usuario_id]);
    $convs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $resultado = [];
    foreach ($convs as $c) {
        $otroId = ($c['usuario1_id'] == $usuario_id) ? $c['usuario2_id'] : $c['usuario1_id'];
        $otro = obtenerUsuarioPorId($pdo, $otroId);
        // Último mensaje entre ambos
        $stmt2 = $pdo->prepare("SELECT * FROM mensajes WHERE (remitente_id = ? AND destinatario_id = ?) OR (remitente_id = ? AND destinatario_id = ?) ORDER BY fecha_envio DESC LIMIT 1");
        $stmt2->execute([$usuario_id, $otroId, $otroId, $usuario_id]);
        $ultimo = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [];
        $resultado[] = [
            'usuario1_id' => $c['usuario1_id'],
            'usuario2_id' => $c['usuario2_id'],
            'otro_usuario_id' => $otroId,
            'otro_usuario_nombre' => $otro['nombre'] ?? 'Usuario',
            'otro_usuario_username' => $otro['username'] ?? 'usuario',
            'otro_usuario_foto' => $otro['foto_perfil'] ?? null,
            'ultimo_mensaje' => $ultimo['contenido'] ?? null,
            'fecha_ultimo_mensaje' => $ultimo['fecha_envio'] ?? null,
            'ultimo_mensaje_leido' => (int)($ultimo['leido'] ?? 1)
        ];
    }
    return $resultado;
}

/** Obtiene mensajes de una conversación con otro usuario */
function obtenerMensajesConversacion($pdo, $usuario_id, $otro_usuario_id, $limit = 50) {
    $limit = (int)$limit;
    $stmt = $pdo->prepare("
        SELECT m.*, u.nombre as remitente_nombre, u.foto_perfil as remitente_foto
        FROM mensajes m
        JOIN usuarios u ON m.remitente_id = u.id
        WHERE (m.remitente_id = ? AND m.destinatario_id = ?) 
           OR (m.remitente_id = ? AND m.destinatario_id = ?)
        ORDER BY m.fecha_envio DESC
        LIMIT ?
    ");
    $stmt->bindParam(1, $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $otro_usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(3, $otro_usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(4, $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(5, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
}

/** Envía un mensaje; crea la conversación si no existe */
function enviarMensaje($pdo, $remitente_id, $destinatario_id, $contenido) {
    if (empty(trim($contenido))) {
        return false;
    }
    $min_id = min($remitente_id, $destinatario_id);
    $max_id = max($remitente_id, $destinatario_id);
    // Crear conversación si no existe (UNIQUE par)
    $stmt = $pdo->prepare("INSERT IGNORE INTO conversaciones (usuario1_id, usuario2_id) VALUES (?, ?)");
    $stmt->execute([$min_id, $max_id]);
    // Insertar mensaje
    $stmt2 = $pdo->prepare("INSERT INTO mensajes (remitente_id, destinatario_id, contenido) VALUES (?, ?, ?)");
    return $stmt2->execute([$remitente_id, $destinatario_id, trim($contenido)]);
}

// ===================== PUBLICACIONES (TIPOS / CRUD BÁSICO) =====================

/** Obtiene publicaciones de un usuario filtradas por tipo */
function obtenerPublicacionesPorTipo($pdo, $usuario_id, $tipo = 'todos') {
    if ($tipo === 'todos') {
        return obtenerPublicacionesUsuario($pdo, $usuario_id);
    }
    $stmt = $pdo->prepare("SELECT p.*, u.nombre, u.username, u.foto_perfil FROM publicaciones p JOIN usuarios u ON p.usuario_id = u.id WHERE p.usuario_id = ? AND p.tipo = ? ORDER BY p.fecha_publicacion DESC");
    $stmt->execute([$usuario_id, $tipo]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Elimina una publicación si pertenece al usuario */
function eliminarPublicacion($pdo, $publicacion_id, $usuario_id) {
    $stmt = $pdo->prepare("SELECT usuario_id, imagen FROM publicaciones WHERE id = ?");
    $stmt->execute([$publicacion_id]);
    $pub = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$pub || (int)$pub['usuario_id'] !== (int)$usuario_id) {
        return false;
    }
    // Borrar imagen
    if (!empty($pub['imagen']) && file_exists(__DIR__ . '/../uploads/' . $pub['imagen'])) {
        @unlink(__DIR__ . '/../uploads/' . $pub['imagen']);
    }
    // Likes y comentarios
    $pdo->prepare("DELETE FROM likes WHERE publicacion_id = ?")->execute([$publicacion_id]);
    $pdo->prepare("DELETE FROM comentarios WHERE publicacion_id = ?")->execute([$publicacion_id]);
    // Publicación
    $stmtDel = $pdo->prepare("DELETE FROM publicaciones WHERE id = ?");
    return $stmtDel->execute([$publicacion_id]);
}

/** Edita una publicación si pertenece al usuario */
function editarPublicacion($pdo, $publicacion_id, $usuario_id, $contenido, $tipo = 'general', $nombre_mascota = null, $ubicacion = null) {
    $stmt = $pdo->prepare("SELECT usuario_id FROM publicaciones WHERE id = ?");
    $stmt->execute([$publicacion_id]);
    $pub = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$pub || (int)$pub['usuario_id'] !== (int)$usuario_id) {
        return false;
    }
    // Normalizar parámetros
    $contenido = trim($contenido);
    if ($tipo !== 'mascota') {
        $nombre_mascota = null; // Sólo conservar nombre de mascota si el tipo es mascota
    }
    if ($contenido === '') { return false; }
    $stmtUpd = $pdo->prepare("UPDATE publicaciones SET contenido = ?, tipo = ?, nombre_mascota = ?, ubicacion = ? WHERE id = ?");
    return $stmtUpd->execute([$contenido, $tipo, $nombre_mascota, $ubicacion, $publicacion_id]);
}

/**
 * Obtiene las mascotas guardadas de un usuario
 */
function obtenerMascotasGuardadas($pdo, $usuario_id) {
    $stmt = $pdo->prepare("SELECT mascotas_guardadas FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $mascotas_json = $stmt->fetchColumn();
    return json_decode($mascotas_json ?? '[]', true) ?: [];
}

/**
 * Guarda las mascotas guardadas de un usuario
 */
function guardarMascotasGuardadas($pdo, $usuario_id, $mascotas) {
    $mascotas_json = json_encode($mascotas);
    $stmt = $pdo->prepare("UPDATE usuarios SET mascotas_guardadas = ? WHERE id = ?");
    return $stmt->execute([$mascotas_json, $usuario_id]);
}

/**
 * Actualiza la ubicación de una publicación (helper granular)
 */
function actualizarUbicacionPublicacion($pdo, $publicacion_id, $ubicacion) {
    $stmt = $pdo->prepare("UPDATE publicaciones SET ubicacion = ? WHERE id = ?");
    return $stmt->execute([$ubicacion, $publicacion_id]);
}

// ===================== MODERACIÓN IA (Perspective API) =====================

/**
 * Analiza contenido usando Perspective API para detectar contenido inapropiado.
 * Si no existe config/api.php o no hay clave válida, devuelve no tóxico.
 * @param string $texto
 * @return array { is_toxic: bool, scores: array, raw_response?: mixed }
 */
function analizarContenidoModeracion($texto) {
    // Evitar llamadas innecesarias en texto vacío
    $texto = trim($texto);
    if ($texto === '') {
        return ['is_toxic' => false, 'scores' => []];
    }
    // Ruta relativa desde páginas raíz (publicar.php, comentario.php). Intentar ambas.
    $configPath = __DIR__ . '/../config/api.php';
    if (!file_exists($configPath)) {
        return ['is_toxic' => false, 'scores' => []];
    }
    require_once $configPath;
    if (!defined('PERSPECTIVE_API_KEY') || constant('PERSPECTIVE_API_KEY') === '' || constant('PERSPECTIVE_API_KEY') === 'TU_CLAVE_API_AQUI') {
        return ['is_toxic' => false, 'scores' => []];
    }
    $url = 'https://commentanalyzer.googleapis.com/v1alpha1/comments:analyze';
    $payload = [
        'comment' => ['text' => $texto],
        'languages' => ['es'],
        'requestedAttributes' => [
            'TOXICITY' => ['scoreType' => 'PROBABILITY'],
            'PROFANITY' => ['scoreType' => 'PROBABILITY'],
            'THREAT' => ['scoreType' => 'PROBABILITY'],
            'IDENTITY_ATTACK' => ['scoreType' => 'PROBABILITY']
        ],
        'doNotStore' => true
    ];
    $ch = curl_init();
    curl_setopt_array($ch, [
    CURLOPT_URL => $url . '?key=' . constant('PERSPECTIVE_API_KEY'),
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 5
    ]);
    $response = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response === false) {
        error_log('Error cURL Perspective: ' . curl_error($ch));
    }
    curl_close($ch);
    if ($http !== 200) {
        error_log("Error Perspective API HTTP $http Response: $response");
        return ['is_toxic' => false, 'scores' => []];
    }
    $data = json_decode($response, true);
    if (!isset($data['attributeScores'])) {
        return ['is_toxic' => false, 'scores' => [], 'raw_response' => $data];
    }
    $scores = [];
    $is_toxic = false;
    // Definir umbrales seguros por defecto si no están definidos
    if (!defined('TOXICITY_THRESHOLD')) define('TOXICITY_THRESHOLD', 0.7);
    if (!defined('PROFANITY_THRESHOLD')) define('PROFANITY_THRESHOLD', 0.6);
    if (!defined('THREAT_THRESHOLD')) define('THREAT_THRESHOLD', 0.5);
    foreach ($data['attributeScores'] as $attr => $info) {
        $val = $info['summaryScore']['value'] ?? 0.0;
        $scores[strtolower($attr)] = $val;
        if (($attr === 'TOXICITY' && $val >= TOXICITY_THRESHOLD) ||
            ($attr === 'PROFANITY' && $val >= PROFANITY_THRESHOLD) ||
            ($attr === 'THREAT' && $val >= THREAT_THRESHOLD)) {
            $is_toxic = true;
        }
    }
    return [
        'is_toxic' => $is_toxic,
        'scores' => $scores,
        'raw_response' => $data
    ];
}

/**
 * Crea un reporte automático y advertencia si se detecta contenido inapropiado.
 * Ajustado al esquema existente (reportes: reportante_id, tipo_objeto, objeto_id, motivo, descripcion, estado)
 */
function crearReporteAutomatico($pdo, $objeto_id, $tipo_objeto, $usuario_id, $motivo = 'contenido_inapropiado') {
    try {
        $stmt = $pdo->prepare("INSERT INTO reportes (reportante_id, tipo_objeto, objeto_id, motivo, descripcion, estado) VALUES (0, ?, ?, ?, 'Reporte automático por IA de moderación', 'revisado')");
        $stmt->execute([$tipo_objeto, $objeto_id, $motivo]);
        $stmt2 = $pdo->prepare("INSERT INTO advertencias (usuario_id, motivo, descripcion) VALUES (?, ?, 'Contenido inapropiado detectado automáticamente')");
        $stmt2->execute([$usuario_id, 'Contenido inapropiado']);
    } catch (PDOException $e) {
        error_log('Error crearReporteAutomatico: ' . $e->getMessage());
    }
}

// ===================== OPTIMIZACIÓN PERFIL (Paginación + Caché ligero) =====================

/**
 * Obtiene publicaciones de un perfil con datos agregados (likes/comentarios + like del usuario actual) usando subconsultas agregadas.
 * Se limita el número de resultados para paginación.
 */
function obtenerPublicacionesPerfilOptimizadas(PDO $pdo, int $perfil_usuario_id, int $usuario_actual_id, int $pagina = 1, int $por_pagina = 12): array {
    $pagina = max(1, $pagina);
    $por_pagina = max(1, min(100, $por_pagina));
    $offset = ($pagina - 1) * $por_pagina;

    $sql = "
        SELECT 
            p.id AS publicacion_id,
            p.usuario_id,
            p.contenido,
            p.imagen,
            p.tipo,
            p.nombre_mascota,
            p.ubicacion,
            p.fecha_publicacion,
            u.nombre AS autor_nombre,
            u.username AS autor_username,
            u.foto_perfil AS autor_foto,
            COALESCE(lc.likes_total, 0) AS total_likes,
            COALESCE(cc.comentarios_total, 0) AS total_comentarios,
            CASE WHEN ul.publicacion_id IS NOT NULL THEN 1 ELSE 0 END AS usuario_dio_like
        FROM publicaciones p
        JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN (
            SELECT publicacion_id, COUNT(*) AS likes_total FROM likes GROUP BY publicacion_id
        ) lc ON p.id = lc.publicacion_id
        LEFT JOIN (
            SELECT publicacion_id, COUNT(*) AS comentarios_total FROM comentarios GROUP BY publicacion_id
        ) cc ON p.id = cc.publicacion_id
        LEFT JOIN likes ul ON p.id = ul.publicacion_id AND ul.usuario_id = :usuario_actual
        WHERE p.usuario_id = :perfil
        ORDER BY p.fecha_publicacion DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_actual', $usuario_actual_id, PDO::PARAM_INT);
    $stmt->bindValue(':perfil', $perfil_usuario_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Cuenta el total de publicaciones de un usuario (para paginación). */
function contarPublicacionesUsuario(PDO $pdo, int $usuario_id): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM publicaciones WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    return (int)$stmt->fetchColumn();
}

/** Obtiene algunos comentarios (limit) de una publicación con datos de autor. */
function obtenerComentariosPublicacion(PDO $pdo, int $publicacion_id, int $limit = 3): array {
    $limit = max(1, min(50, $limit));
    $stmt = $pdo->prepare("SELECT c.*, u.nombre AS autor_nombre, u.foto_perfil AS autor_foto FROM comentarios c JOIN usuarios u ON c.usuario_id = u.id WHERE c.publicacion_id = ? ORDER BY c.fecha_comentario ASC LIMIT ?");
    $stmt->bindValue(1, $publicacion_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Caché simple basado en archivos (JSON) para valores calculados costosos. */
function getCachéEstadisticas(string $clave, callable $funcion, int $tiempo_expiracion = 60) {
    $dir = __DIR__ . '/../cache';
    if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
    $archivo_cache = $dir . '/' . md5($clave) . '.cache';

    if (file_exists($archivo_cache)) {
        $json = @file_get_contents($archivo_cache);
        if ($json) {
            $datos = json_decode($json, true);
            if (isset($datos['timestamp'], $datos['valor']) && (time() - $datos['timestamp'] < $tiempo_expiracion)) {
                return $datos['valor'];
            }
        }
    }
    $valor = $funcion();
    @file_put_contents($archivo_cache, json_encode(['valor' => $valor, 'timestamp' => time()]));
    return $valor;
}

?>