<?php
/**
 * HU-04 — Recuperación de contraseña
 * POST: { email }
 *   → Genera token de 64 chars, lo guarda con expiración 1h
 *   → Devuelve el enlace de reset (en producción se enviaría por email)
 * GET:  ?token=xxx  → Valida que el token existe, no está usado y no expiró
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/backend_em/conexion.php'; // proporciona $pdo

// ── GET: validar token antes de mostrar el formulario ────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = trim($_GET['token'] ?? '');

    if (strlen($token) !== 64 || !ctype_alnum($token)) {
        http_response_code(400);
        echo json_encode(['valido' => false, 'error' => 'Token con formato inválido']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id FROM password_reset_tokens
        WHERE token = ? AND usado = 0 AND expires_at > NOW()
    ");
    $stmt->execute([$token]);

    if ($stmt->fetch()) {
        echo json_encode(['valido' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['valido' => false, 'error' => 'El enlace no es válido o ya expiró']);
    }
    exit;
}

// ── POST: solicitar recuperación ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data  = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Ingresa un correo electrónico válido']);
        exit;
    }

    // Buscar usuario — SIEMPRE responder igual para no revelar si el email existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // Invalidar tokens anteriores pendientes del mismo usuario
        $pdo->prepare("
            UPDATE password_reset_tokens SET usado = 1
            WHERE usuario_id = ? AND usado = 0
        ")->execute([$usuario['id']]);

        // Generar token seguro de 64 caracteres hexadecimales
        $token     = bin2hex(random_bytes(32));
        $expiresAt = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

        $pdo->prepare("
            INSERT INTO password_reset_tokens (usuario_id, token, expires_at)
            VALUES (?, ?, ?)
        ")->execute([$usuario['id'], $token, $expiresAt]);

        // En producción aquí iría el envío de email (PHPMailer, etc.)
        // En desarrollo devolvemos el enlace directamente para poder probarlo
        $resetLink = "reset_password.html?token=$token";

        echo json_encode([
            'mensaje'   => 'Si el correo está registrado recibirás las instrucciones en breve.',
            'dev_link'  => $resetLink,   // ⚠️ Solo para desarrollo — quitar en producción
            'dev_token' => $token        // ⚠️ Solo para desarrollo — quitar en producción
        ]);
    } else {
        // Respuesta idéntica para no filtrar si el email existe
        echo json_encode([
            'mensaje'  => 'Si el correo está registrado recibirás las instrucciones en breve.'
        ]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
