<?php
namespace Alaska\Servicios {
/**
 * Funciones utilitarias para la sección de servicios / profesionales.
 * Ahora namespaced con wrappers globales (definidos al final) para retrocompatibilidad.
 */

// Definir constantes globales (en espacio global) vía referencia a root namespace
if (!defined('SERVICIO_CALIFICACION_MIN')) \define('SERVICIO_CALIFICACION_MIN', 1);
if (!defined('SERVICIO_CALIFICACION_MAX')) \define('SERVICIO_CALIFICACION_MAX', 5);

use PDO; use Exception;

/** Obtiene las categorías de servicios activas. */
function obtenerCategoriasServicios(PDO $pdo): array {
	$stmt = $pdo->prepare("SELECT * FROM categorias_servicios WHERE activo=TRUE ORDER BY nombre");
	$stmt->execute();
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Lista servicios filtrados opcionalmente por categoría y/o ubicación. */
function obtenerServicios(PDO $pdo, $categoria_id = null, $ubicacion = null, int $limit = 20): array {
	$limit = max(1, (int)$limit);
	$sql = "SELECT s.*, c.nombre AS categoria_nombre, c.icono AS categoria_icono,
			CASE WHEN s.total_calificaciones = 0
				 THEN 'Sin calificaciones'
				 ELSE CONCAT(ROUND(s.calificacion,1), ' (', s.total_calificaciones, ' calificaciones)')
			END AS calificacion_texto
			FROM servicios s
			JOIN categorias_servicios c ON s.categoria_id = c.id
			WHERE s.activo = TRUE";
	$params = [];
	if ($categoria_id) { $sql .= " AND s.categoria_id = ?"; $params[] = $categoria_id; }
	if ($ubicacion) { $sql .= " AND s.ubicacion LIKE ?"; $params[] = '%'.$ubicacion.'%'; }
	$sql .= " ORDER BY s.calificacion DESC, s.total_calificaciones DESC LIMIT ?"; $params[] = $limit;
	$stmt = $pdo->prepare($sql); $stmt->execute($params);
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Inserta o actualiza calificación y recalcula agregados. */
function calificarServicio(PDO $pdo, int $servicio_id, int $usuario_id, int $calificacion, string $comentario = ''): bool {
	if ($calificacion < \SERVICIO_CALIFICACION_MIN || $calificacion > \SERVICIO_CALIFICACION_MAX) return false;
	try {
		$pdo->beginTransaction();
		$stmt = $pdo->prepare("INSERT INTO calificaciones_servicios (servicio_id,usuario_id,calificacion,comentario)
							   VALUES (?,?,?,?)
							   ON DUPLICATE KEY UPDATE
								   calificacion=VALUES(calificacion),
								   comentario=VALUES(comentario),
								   fecha_calificacion=NOW()");
		$stmt->execute([$servicio_id,$usuario_id,$calificacion,$comentario]);
		$stmt = $pdo->prepare("UPDATE servicios s SET
								calificacion=(SELECT AVG(calificacion) FROM calificaciones_servicios WHERE servicio_id=s.id),
								total_calificaciones=(SELECT COUNT(*) FROM calificaciones_servicios WHERE servicio_id=s.id)
							   WHERE s.id=?");
		$stmt->execute([$servicio_id]);
		$pdo->commit();
		return true;
	} catch (Exception $e) {
		if ($pdo->inTransaction()) $pdo->rollBack();
		error_log('Error calificarServicio: '.$e->getMessage());
		return false;
	}
}

/** Recupera calificación individual de un usuario. */
function obtenerCalificacionUsuario(PDO $pdo, int $servicio_id, int $usuario_id): ?array {
	$stmt = $pdo->prepare("SELECT calificacion, comentario FROM calificaciones_servicios WHERE servicio_id=? AND usuario_id=?");
	$stmt->execute([$servicio_id,$usuario_id]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	return $row ?: null;
}

// ===== FIN namespace Alaska\Servicios =====
}

// ===================== Wrappers Globales Retrocompatibles =====================
namespace {
	if (!function_exists('obtenerCategoriasServicios')) {
		function obtenerCategoriasServicios($pdo) { return \Alaska\Servicios\obtenerCategoriasServicios($pdo); }
	}
	if (!function_exists('obtenerServicios')) {
		function obtenerServicios($pdo, $categoria_id = null, $ubicacion = null, $limit = 20) { return \Alaska\Servicios\obtenerServicios($pdo,$categoria_id,$ubicacion,$limit); }
	}
	if (!function_exists('calificarServicio')) {
		function calificarServicio($pdo,$servicio_id,$usuario_id,$calificacion,$comentario='') { return \Alaska\Servicios\calificarServicio($pdo,$servicio_id,$usuario_id,$calificacion,$comentario); }
	}
	if (!function_exists('obtenerCalificacionUsuario')) {
		function obtenerCalificacionUsuario($pdo,$servicio_id,$usuario_id) { return \Alaska\Servicios\obtenerCalificacionUsuario($pdo,$servicio_id,$usuario_id); }
	}
}

// Wrappers globales ya presentes más abajo
