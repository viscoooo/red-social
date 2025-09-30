<?php
/**
 * Módulo de Donaciones y Campañas de Recaudación.
 *
 * Funcionalidad:
 *  - crearCampanaDonacion(): Crea una nueva campaña asociada a una emergencia de mascota.
 *  - procesarDonacionStripe(): Genera un PaymentIntent (Stripe) de forma dinámica si la librería está instalada.
 *  - registrarDonacion(): Registra una donación (ya confirmada) y actualiza total acumulado.
 *  - obtenerCampanasDonacion(): Lista campañas activas (con progreso %) ordenadas por fecha.
 *  - obtenerDonacionesUsuario(): Historial de donaciones de un usuario.
 *
 * Diseño:
 *  - Dependencia externa Stripe se carga de forma tolerante (class_exists + call_user_func) para evitar fatales.
 *  - Se evita exponer directamente claves; se asume configuración en config/stripe.php.
 *  - Todas las operaciones usan sentencias preparadas.
 */

// ===================== Carga opcional de dependencias =====================
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
	require_once $autoload; // Composer autoload si existe
}
if (file_exists(__DIR__ . '/../config/stripe.php')) {
	require_once __DIR__ . '/../config/stripe.php';
}
// Placeholder para evitar notices si no se definió la constante.
if (!defined('STRIPE_SECRET_KEY')) {
	define('STRIPE_SECRET_KEY', '');
}

// ===================== Campañas =====================

/**
 * Crea una campaña de donación.
 * @param PDO $pdo
 * @return int|false ID de la campaña o false si falla.
 */
function crearCampanaDonacion($pdo, $emergencia_id, $titulo, $descripcion, $meta)
{
	$stmt = $pdo->prepare("INSERT INTO campanas_donacion (emergencia_id, titulo, descripcion, meta) VALUES (?,?,?,?)");
	if ($stmt->execute([$emergencia_id, trim($titulo), trim($descripcion), $meta])) {
		return (int)$pdo->lastInsertId();
	}
	return false;
}

// ===================== Pagos (Stripe) =====================

/**
 * Crea un PaymentIntent en Stripe (si la librería está disponible).
 * Devuelve estructura estándar para la capa superior.
 * @param float  $monto        Monto (en moneda base, se multiplica *100 para enviar a Stripe).
 * @param string $descripcion  Descripción del pago.
 * @param string $email        Email para recibo.
 * @return array { success: bool, client_secret?: string, payment_intent_id?: string, error?: string }
 */
function procesarDonacionStripe($monto, $descripcion, $email)
{
	if (!defined('STRIPE_SECRET_KEY') || STRIPE_SECRET_KEY === '') {
		return ['success' => false, 'error' => 'Stripe no configurado'];
	}
	if (!class_exists('Stripe\\Stripe') || !class_exists('Stripe\\PaymentIntent')) {
		return ['success' => false, 'error' => 'Librería Stripe no instalada'];
	}

	try {
		call_user_func(['Stripe\\Stripe', 'setApiKey'], STRIPE_SECRET_KEY);
		$paymentIntentClass = 'Stripe\\PaymentIntent';
		$pi = $paymentIntentClass::create([
			'amount' => (int)round($monto * 100),
			'currency' => 'mxn',
			'description' => $descripcion,
			'receipt_email' => $email
		]);
		return [
			'success' => true,
			'client_secret' => $pi->client_secret,
			'payment_intent_id' => $pi->id
		];
	} catch (Throwable $e) {
		error_log('Stripe: ' . $e->getMessage());
		return ['success' => false, 'error' => $e->getMessage()];
	}
}

// ===================== Donaciones Registradas =====================

/**
 * Registra una donación (post-confirmación de pago) y actualiza el total de la campaña.
 * @param PDO    $pdo
 * @param int    $campana_id
 * @param int    $donante_id
 * @param float  $monto
 * @param string $metodo  (stripe|transferencia|otro)
 * @param string $ref     Referencia de pago o transacción.
 * @param string $estado  Estado lógico (completado|pendiente|fallido)
 * @return bool
 */
function registrarDonacion($pdo, $campana_id, $donante_id, $monto, $metodo, $ref, $estado = 'completado')
{
	$stmt = $pdo->prepare("INSERT INTO donaciones (campana_id, donante_id, monto, metodo_pago, referencia_pago, estado) VALUES (?,?,?,?,?,?)");
	if ($stmt->execute([$campana_id, $donante_id, $monto, $metodo, $ref, $estado])) {
		$pdo->prepare("UPDATE campanas_donacion SET total_donado = total_donado + ? WHERE id = ?")
			->execute([$monto, $campana_id]);
		return true;
	}
	return false;
}

// ===================== Consultas =====================

/**
 * Lista campañas activas ordenadas por fecha (recientes primero) e incluye porcentaje.
 * @param PDO $pdo
 * @param int $limit
 * @return array<int,array<string,mixed>>
 */
function obtenerCampanasDonacion($pdo, $limit = 10)
{
	$limit = max(1, (int)$limit);
	$stmt = $pdo->prepare("SELECT cd.*, me.nombre AS mascota_nombre, me.tipo AS mascota_tipo, u.nombre AS creador_nombre, u.foto_perfil AS creador_foto,
			CASE WHEN cd.meta > 0 THEN ROUND((cd.total_donado / cd.meta) * 100, 2) ELSE 0 END AS porcentaje_completado
		FROM campanas_donacion cd
		JOIN mascotas_emergencia me ON cd.emergencia_id = me.id
		JOIN usuarios u ON me.usuario_id = u.id
		WHERE cd.activo = TRUE
		  AND (cd.fecha_fin IS NULL OR cd.fecha_fin > NOW())
		ORDER BY cd.fecha_inicio DESC
		LIMIT ?");
	$stmt->execute([$limit]);
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Historial de donaciones de un usuario.
 * @param PDO $pdo
 * @param int $usuario_id
 * @param int $limit
 * @return array<int,array<string,mixed>>
 */
function obtenerDonacionesUsuario($pdo, $usuario_id, $limit = 20)
{
	$limit = max(1, (int)$limit);
	$stmt = $pdo->prepare("SELECT d.*, cd.titulo AS campana_titulo, me.nombre AS mascota_nombre
		FROM donaciones d
		JOIN campanas_donacion cd ON d.campana_id = cd.id
		JOIN mascotas_emergencia me ON cd.emergencia_id = me.id
		WHERE d.donante_id = ?
		ORDER BY d.fecha_donacion DESC
		LIMIT ?");
	$stmt->execute([$usuario_id, $limit]);
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
