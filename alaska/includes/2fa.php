<?php
/**
 * Módulo helper de Autenticación de Dos Factores (2FA) con tolerancia a entornos
 * sin dependencias Composer. Si la librería pragmarx/google2fa no está presente:
 *  - Se generan secretos pseudo-aleatorios simples.
 *  - La verificación siempre falla (para no dar falsa seguridad) salvo que quieras permitir bypass.
 *  - Se expone una URL otpauth compatible básica para que el usuario pueda migrar cuando haya soporte.
 */

// ===================== Carga opcional de dependencia =====================
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
	require_once $autoload;
}

if (class_exists('PragmaRX\\Google2FA\\Google2FA')) {
	$cls = '\\PragmaRX\\Google2FA\\Google2FA';
	try {
		$google2fa = new $cls();
	} catch (Throwable $e) {
		// fallback silencioso: no instanciamos nada si falla
	}
}

// ===================== API Pública =====================

/**
 * Genera un secreto para 2FA. Si no hay librería, devuelve 20 hex chars (10 bytes).
 */
function generarSecreto2FA(): string
{
	if (!isset($GLOBALS['google2fa'])) {
		return bin2hex(random_bytes(10));
	}
	return $GLOBALS['google2fa']->generateSecretKey();
}

/**
 * Verifica un código TOTP. Devuelve false si no hay soporte.
 */
function verificarCodigo2FA(string $secret, string $codigo): bool
{
	if (!isset($GLOBALS['google2fa'])) {
		return false; // Sin librería no podemos validar contra tiempo real.
	}
	return (bool)$GLOBALS['google2fa']->verifyKey($secret, $codigo);
}

/**
 * Genera la URL otpauth:// para que aplicaciones de autenticación puedan registrar la cuenta.
 * Si no hay librería, construye manualmente una URL estándar.
 */
function generarQRCodeURL(string $secret, string $nombre_usuario, string $email): string
{
	if (!isset($GLOBALS['google2fa'])) {
		return 'otpauth://totp/ALASKA:' . rawurlencode($email) . '?secret=' . $secret . '&issuer=ALASKA';
	}
	return $GLOBALS['google2fa']->getQRCodeUrl('ALASKA', $email, $secret);
}

/**
 * Activa (o actualiza) 2FA para un usuario.
 */
function activar2FA($pdo, int $usuario_id, string $secret): bool
{
	$stmt = $pdo->prepare("INSERT INTO autenticacion_dos_factores (usuario_id, secret_key, activo, fecha_activacion)
			VALUES (?,?,TRUE,NOW())
			ON DUPLICATE KEY UPDATE secret_key = VALUES(secret_key), activo = VALUES(activo), fecha_activacion = VALUES(fecha_activacion)");
	return $stmt->execute([$usuario_id, $secret]);
}

/**
 * Desactiva 2FA.
 */
function desactivar2FA($pdo, int $usuario_id): bool
{
	$stmt = $pdo->prepare("UPDATE autenticacion_dos_factores SET activo = FALSE WHERE usuario_id = ?");
	return $stmt->execute([$usuario_id]);
}

/**
 * Obtiene estado (activo y secret) de 2FA del usuario.
 * @return array|null { activo: 0|1, secret_key: string }
 */
function obtenerEstado2FA($pdo, int $usuario_id): ?array
{
	$stmt = $pdo->prepare("SELECT activo, secret_key FROM autenticacion_dos_factores WHERE usuario_id = ?");
	$stmt->execute([$usuario_id]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	return $row ?: null;
}
