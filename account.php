<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

define('LIMITE_MAXIMO', 50000.00);

$host       = 'localhost';
$dbname     = 'banco_db';
$usuario_bd = 'root';
$password_bd = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $usuario_bd, $password_bd);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

$data      = json_decode(file_get_contents('php://input'), true);
$usuarioId = intval($data['usuarioId'] ?? 0);
$tipo      = trim($data['tipo']        ?? '');
$saldo     = floatval($data['saldo']   ?? 0);

// ── VALIDACIONES ──────────────────────────────────────────
if ($usuarioId <= 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesión inválida. Por favor inicia sesión de nuevo.']);
    exit;
}
if (!in_array($tipo, ['ahorro', 'corriente'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de cuenta inválido.']);
    exit;
}
if ($saldo < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'El saldo inicial no puede ser negativo.']);
    exit;
}
if ($saldo > LIMITE_MAXIMO) {
    http_response_code(400);
    echo json_encode(['error' => 'El saldo inicial supera el límite máximo ($' . number_format(LIMITE_MAXIMO, 2) . ').']);
    exit;
}

// Verificar que el usuario existe
$chkUser = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
$chkUser->execute([$usuarioId]);
if (!$chkUser->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Usuario no encontrado.']);
    exit;
}

// ── CREAR CUENTA ─────────────────────────────────────────
try {
    $pdo->beginTransaction();

    // Generar número de cuenta único (prefijo 52 + 10 dígitos)
    do {
        $numeroCuenta = '52' . str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
        $chk = $pdo->prepare("SELECT id FROM cuentas WHERE numero_cuenta = ?");
        $chk->execute([$numeroCuenta]);
    } while ($chk->fetch());

    $ins = $pdo->prepare("
        INSERT INTO cuentas (usuario_id, tipo, numero_cuenta, saldo)
        VALUES (?, ?, ?, ?)
    ");
    $ins->execute([$usuarioId, $tipo, $numeroCuenta, $saldo]);
    $nuevaCuentaId = $pdo->lastInsertId();

    // Si el saldo inicial > 0, registrar como depósito inicial
    if ($saldo > 0) {
        $pdo->prepare("
            INSERT INTO transacciones (cuenta_id, tipo, monto, descripcion, saldo_despues)
            VALUES (?, 'deposito', ?, 'Depósito inicial de apertura', ?)
        ")->execute([$nuevaCuentaId, $saldo, $saldo]);
    }

    $pdo->commit();

    echo json_encode([
        'mensaje'      => 'Cuenta creada correctamente',
        'numero_cuenta' => $numeroCuenta,
        'cuentaId'     => $nuevaCuentaId,
        'tipo'         => $tipo,
        'saldo'        => $saldo
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo crear la cuenta. Intenta de nuevo.']);
}
