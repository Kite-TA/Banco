<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

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

$cuentaId = intval($_GET['cuentaId'] ?? 0);

if ($cuentaId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro inválido']);
    exit;
}

// Saldo actual
$stmt = $pdo->prepare("SELECT saldo, numero_cuenta FROM cuentas WHERE id = ?");
$stmt->execute([$cuentaId]);
$cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cuenta) {
    http_response_code(404);
    echo json_encode(['error' => 'Cuenta no encontrada']);
    exit;
}

// Últimos 50 movimientos
$stmtTx = $pdo->prepare("
    SELECT tipo, monto, descripcion, cuenta_relacionada, saldo_despues, fecha
    FROM transacciones
    WHERE cuenta_id = ?
    ORDER BY fecha DESC
    LIMIT 50
");
$stmtTx->execute([$cuentaId]);
$transacciones = $stmtTx->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'saldo'          => $cuenta['saldo'],
    'numeroCuenta'   => $cuenta['numero_cuenta'],
    'transacciones'  => $transacciones
]);
