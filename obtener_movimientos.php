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

$cuentaId  = intval($_GET['cuentaId']  ?? 0);
$usuarioId = intval($_GET['usuarioId'] ?? 0);  // REQUERIDO para verificar propiedad

if ($cuentaId <= 0 || $usuarioId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros inválidos']);
    exit;
}

// ── VERIFICACIÓN DE PROPIEDAD ─────────────────────────
// La cuenta debe pertenecer al usuario que hace la petición
$stmt = $pdo->prepare("
    SELECT saldo, numero_cuenta
    FROM cuentas
    WHERE id = ? AND usuario_id = ?
");
$stmt->execute([$cuentaId, $usuarioId]);
$cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cuenta) {
    // 403 en lugar de 404: sabemos que el recurso existe pero el usuario no tiene acceso
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permiso para consultar esta cuenta']);
    exit;
}

// Últimos 50 movimientos — solo si la cuenta es del usuario
$stmtTx = $pdo->prepare("
    SELECT id, tipo, monto, descripcion, cuenta_relacionada, saldo_despues, fecha
    FROM transacciones
    WHERE cuenta_id = ?
    ORDER BY fecha DESC
    LIMIT 50
");
$stmtTx->execute([$cuentaId]);
$transacciones = $stmtTx->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'saldo'         => $cuenta['saldo'],
    'numeroCuenta'  => $cuenta['numero_cuenta'],
    'transacciones' => $transacciones
]);
