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

$data            = json_decode(file_get_contents('php://input'), true);
$cuentaId        = intval($data['cuentaId']        ?? 0);
$cuentaDestino   = trim($data['cuentaDestino']     ?? '');
$monto           = floatval($data['monto']         ?? 0);

// ── VALIDACIONES ──────────────────────────────────────────
if ($cuentaId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Sesión inválida. Por favor inicia sesión de nuevo.']);
    exit;
}
if (empty($cuentaDestino)) {
    http_response_code(400);
    echo json_encode(['error' => 'Debes ingresar el número de cuenta destino.']);
    exit;
}
if ($monto <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'El monto debe ser mayor a cero.']);
    exit;
}
if ($monto > LIMITE_MAXIMO) {
    http_response_code(400);
    echo json_encode(['error' => 'El monto supera el límite máximo por operación ($' . number_format(LIMITE_MAXIMO, 2) . ').']);
    exit;
}

// ── OPERACIÓN ────────────────────────────────────────────
try {
    $pdo->beginTransaction();

    // Obtener y bloquear cuenta origen
    $stmtOrigen = $pdo->prepare("SELECT id, saldo, numero_cuenta FROM cuentas WHERE id = ? FOR UPDATE");
    $stmtOrigen->execute([$cuentaId]);
    $origen = $stmtOrigen->fetch(PDO::FETCH_ASSOC);

    if (!$origen) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Cuenta de origen no encontrada.']);
        exit;
    }

    // No se puede transferir a la misma cuenta
    if ($origen['numero_cuenta'] === $cuentaDestino) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'No puedes transferir a tu propia cuenta.']);
        exit;
    }

    // Validar cuenta destino existe
    $stmtDestino = $pdo->prepare("SELECT id, numero_cuenta FROM cuentas WHERE numero_cuenta = ? FOR UPDATE");
    $stmtDestino->execute([$cuentaDestino]);
    $destino = $stmtDestino->fetch(PDO::FETCH_ASSOC);

    if (!$destino) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'La cuenta destino no existe. Verifica el número e intenta de nuevo.']);
        exit;
    }

    // Validar saldo suficiente
    if ($origen['saldo'] < $monto) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'Saldo insuficiente. Tu saldo actual es $' . number_format($origen['saldo'], 2) . '.']);
        exit;
    }

    $nuevoSaldoOrigen  = $origen['saldo'] - $monto;

    // Actualizar saldo origen
    $pdo->prepare("UPDATE cuentas SET saldo = ? WHERE id = ?")
        ->execute([$nuevoSaldoOrigen, $origen['id']]);

    // Actualizar saldo destino
    $pdo->prepare("UPDATE cuentas SET saldo = saldo + ? WHERE id = ?")
        ->execute([$monto, $destino['id']]);

    // Obtener nuevo saldo destino para el registro
    $nuevoSaldoDestino = $pdo->query("SELECT saldo FROM cuentas WHERE id = " . $destino['id'])->fetchColumn();

    // Registrar en ambas cuentas
    $insOrigen = $pdo->prepare("
        INSERT INTO transacciones (cuenta_id, tipo, monto, descripcion, cuenta_relacionada, saldo_despues)
        VALUES (?, 'transferencia_enviada', ?, ?, ?, ?)
    ");
    $insOrigen->execute([$origen['id'], $monto, 'Transferencia enviada', $cuentaDestino, $nuevoSaldoOrigen]);

    $insDestino = $pdo->prepare("
        INSERT INTO transacciones (cuenta_id, tipo, monto, descripcion, cuenta_relacionada, saldo_despues)
        VALUES (?, 'transferencia_recibida', ?, ?, ?, ?)
    ");
    $insDestino->execute([$destino['id'], $monto, 'Transferencia recibida', $origen['numero_cuenta'], $nuevoSaldoDestino]);

    $pdo->commit();

    echo json_encode([
        'mensaje'    => 'Transferencia realizada con éxito.',
        'nuevoSaldo' => $nuevoSaldoOrigen,
        'monto'      => $monto,
        'destino'    => $cuentaDestino
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo completar la transferencia. Intenta de nuevo.']);
}
