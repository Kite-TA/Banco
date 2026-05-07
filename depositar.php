<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// ── Constante de límite máximo por operación ──
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
$cuentaId  = intval($data['cuentaId'] ?? 0);
$monto     = floatval($data['monto']  ?? 0);

// ── VALIDACIONES ──────────────────────────────────────────
if ($cuentaId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Sesión inválida. Por favor inicia sesión de nuevo.']);
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

    // Bloquear fila para escritura segura
    $stmt = $pdo->prepare("SELECT saldo FROM cuentas WHERE id = ? FOR UPDATE");
    $stmt->execute([$cuentaId]);
    $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cuenta) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Cuenta no encontrada.']);
        exit;
    }

    $nuevoSaldo = $cuenta['saldo'] + $monto;

    // Actualizar saldo
    $upd = $pdo->prepare("UPDATE cuentas SET saldo = ? WHERE id = ?");
    $upd->execute([$nuevoSaldo, $cuentaId]);

    // Registrar transacción
    $ins = $pdo->prepare("
        INSERT INTO transacciones (cuenta_id, tipo, monto, descripcion, saldo_despues)
        VALUES (?, 'deposito', ?, 'Depósito en efectivo', ?)
    ");
    $ins->execute([$cuentaId, $monto, $nuevoSaldo]);

    $pdo->commit();

    echo json_encode([
        'mensaje'     => 'Depósito realizado con éxito.',
        'nuevoSaldo'  => $nuevoSaldo,
        'monto'       => $monto
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo completar el depósito. Intenta de nuevo.']);
}
