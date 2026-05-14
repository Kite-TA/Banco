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
$usuarioId = intval($_GET['usuarioId'] ?? 0);

if ($cuentaId <= 0 || $usuarioId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros inválidos']);
    exit;
}

// ── Cuenta (verificando que pertenece al usuario) ─────
$stmtC = $pdo->prepare("
    SELECT c.id, c.numero_cuenta, c.tipo, c.saldo, c.fecha_apertura,
           u.nombre, u.apellidos, u.email
    FROM cuentas c
    JOIN usuarios u ON u.id = c.usuario_id
    WHERE c.id = ? AND c.usuario_id = ?
");
$stmtC->execute([$cuentaId, $usuarioId]);
$cuenta = $stmtC->fetch(PDO::FETCH_ASSOC);

if (!$cuenta) {
    http_response_code(404);
    echo json_encode(['error' => 'Cuenta no encontrada o no tienes permiso para verla']);
    exit;
}

// ── Todas las transacciones (sin límite, orden DESC) ──
$stmtTx = $pdo->prepare("
    SELECT id, tipo, monto, descripcion, cuenta_relacionada, saldo_despues, fecha
    FROM transacciones
    WHERE cuenta_id = ?
    ORDER BY fecha DESC
");
$stmtTx->execute([$cuentaId]);
$transacciones = $stmtTx->fetchAll(PDO::FETCH_ASSOC);

// ── Estadísticas ──────────────────────────────────────
$stmtStats = $pdo->prepare("
    SELECT
        COUNT(*)                                                AS total_operaciones,
        COALESCE(SUM(CASE WHEN tipo = 'deposito'              THEN monto ELSE 0 END), 0) AS total_depositado,
        COALESCE(SUM(CASE WHEN tipo = 'retiro'                THEN monto ELSE 0 END), 0) AS total_retirado,
        COALESCE(SUM(CASE WHEN tipo = 'transferencia_enviada' THEN monto ELSE 0 END), 0) AS total_transferido,
        COALESCE(SUM(CASE WHEN tipo = 'transferencia_recibida' THEN monto ELSE 0 END), 0) AS total_recibido,
        MIN(fecha)                                              AS primera_operacion,
        MAX(fecha)                                              AS ultima_operacion
    FROM transacciones
    WHERE cuenta_id = ?
");
$stmtStats->execute([$cuentaId]);
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'cuenta'       => $cuenta,
    'transacciones'=> $transacciones,
    'stats'        => $stats
]);
