<?php
/**
 * HU-04 — Cambio de contraseña con token
 * POST: { token, password, passwordConfirm }
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/backend_em/conexion.php';

$data            = json_decode(file_get_contents('php://input'), true);
$token           = trim($data['token']           ?? '');
$password        = $data['password']             ?? '';
$passwordConfirm = $data['passwordConfirm']      ?? '';

// ── Validaciones ─────────────────────────────────────────
if (strlen($token) !== 64 || !ctype_alnum($token)) {
    http_response_code(400);
    echo json_encode(['error' => 'Token inválido']);
    exit;
}
if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'La contraseña debe tener al menos 8 caracteres']);
    exit;
}
if ($password !== $passwordConfirm) {
    http_response_code(400);
    echo json_encode(['error' => 'Las contraseñas no coinciden']);
    exit;
}

// ── Verificar token ───────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT id, usuario_id FROM password_reset_tokens
    WHERE token = ? AND usado = 0 AND expires_at > NOW()
");
$stmt->execute([$token]);
$resetRow = $stmt->fetch();

if (!$resetRow) {
    http_response_code(400);
    echo json_encode(['error' => 'El enlace no es válido o ya expiró. Solicita uno nuevo.']);
    exit;
}

// ── Actualizar contraseña y marcar token como usado ───────
try {
    $pdo->beginTransaction();

    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Resetear también el contador de intentos fallidos
    $pdo->prepare("
        UPDATE usuarios
        SET password_hash = ?, failed_attempts = 0, lock_expires_at = NULL
        WHERE id = ?
    ")->execute([$hash, $resetRow['usuario_id']]);

    // Invalidar el token
    $pdo->prepare("
        UPDATE password_reset_tokens SET usado = 1 WHERE id = ?
    ")->execute([$resetRow['id']]);

    $pdo->commit();

    echo json_encode(['mensaje' => 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo actualizar la contraseña. Intenta de nuevo.']);
}
