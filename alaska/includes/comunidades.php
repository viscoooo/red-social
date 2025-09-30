<?php
/**
 * Módulo de Comunidades (grupos locales / temáticos).
 *
 * Funciones principales:
 *  - crearComunidad(): Crea comunidad y agrega al creador como administrador.
 *  - obtenerComunidadesCercanas(): Busca comunidades por coincidencia en ubicación.
 *  - obtenerComunidadesUsuario(): Comunidades donde el usuario es miembro (con rol).
 *  - unirComunidad(): Añade miembro (idempotente con INSERT IGNORE).
 *  - obtenerPublicacionesComunidad(): Lista publicaciones de una comunidad.
 */

/**
 * Crea una comunidad nueva.
 * @return int|false ID de la comunidad o false.
 */
function crearComunidad($pdo, $nombre, $ubicacion, $descripcion, $creador_id)
{
    $nombre = trim($nombre);
    $ubicacion = trim($ubicacion);
    $descripcion = trim($descripcion);
    if ($nombre === '' || $ubicacion === '') return false;

    $stmt = $pdo->prepare("INSERT INTO comunidades (nombre, ubicacion, descripcion, creador_id) VALUES (?,?,?,?)");
    if ($stmt->execute([$nombre, $ubicacion, $descripcion, $creador_id])) {
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO miembros_comunidad (comunidad_id, usuario_id, rol) VALUES (?,?,'administrador')")
            ->execute([$id, $creador_id]);
        return $id;
    }
    return false;
}

/**
 * Obtiene comunidades cuya ubicación coincide (LIKE) con la ubicación de usuario.
 * Incluye número de miembros activos.
 */
function obtenerComunidadesCercanas($pdo, $usuario_ubicacion, $limit = 10)
{
    $limit = max(1, (int)$limit);
    $like = '%' . $usuario_ubicacion . '%';
    $stmt = $pdo->prepare("SELECT c.*, u.nombre AS creador_nombre, COUNT(mc.usuario_id) AS miembros_count
        FROM comunidades c
        JOIN usuarios u ON c.creador_id = u.id
        LEFT JOIN miembros_comunidad mc ON c.id = mc.comunidad_id AND mc.activo = TRUE
        WHERE c.activo = TRUE AND (c.ubicacion LIKE ? OR u.ubicacion LIKE ?)
        GROUP BY c.id
        ORDER BY c.fecha_creacion DESC
        LIMIT ?");
    $stmt->execute([$like, $like, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Comunidades donde el usuario es miembro (con su rol y conteo de miembros).
 */
function obtenerComunidadesUsuario($pdo, $usuario_id)
{
    $stmt = $pdo->prepare("SELECT c.*, mc.rol, COUNT(mc2.usuario_id) AS miembros_count
        FROM miembros_comunidad mc
        JOIN comunidades c ON mc.comunidad_id = c.id
        LEFT JOIN miembros_comunidad mc2 ON c.id = mc2.comunidad_id AND mc2.activo = TRUE
        WHERE mc.usuario_id = ? AND mc.activo = TRUE AND c.activo = TRUE
        GROUP BY c.id
        ORDER BY mc.fecha_union DESC");
    $stmt->execute([$usuario_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Une a un usuario a una comunidad (ignora si ya existe).
 */
function unirComunidad($pdo, $comunidad_id, $usuario_id)
{
    $stmt = $pdo->prepare("INSERT IGNORE INTO miembros_comunidad (comunidad_id, usuario_id) VALUES (?,?)");
    return $stmt->execute([$comunidad_id, $usuario_id]);
}

/**
 * Publicaciones de una comunidad (más recientes primero).
 */
function obtenerPublicacionesComunidad($pdo, $comunidad_id, $limit = 20)
{
    $limit = max(1, (int)$limit);
    $stmt = $pdo->prepare("SELECT pc.*, u.nombre, u.username, u.foto_perfil
        FROM publicaciones_comunidad pc
        JOIN usuarios u ON pc.usuario_id = u.id
        WHERE pc.comunidad_id = ?
        ORDER BY pc.fecha_publicacion DESC
        LIMIT ?");
    $stmt->execute([$comunidad_id, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
