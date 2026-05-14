<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// 1. Conexión a la base de datos
include 'conexion.php'; 

$cuentaId = intval($_GET['cuentaId'] ?? 0);

if ($cuentaId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de cuenta no válido']);
    exit;
}

try {
    // 2. BUSCAR SALDO (En la tabla 'cuentas')
    $stmt = $pdo->prepare("SELECT saldo, numero_cuenta FROM cuentas WHERE id = ?");
    $stmt->execute([$cuentaId]);
    $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cuenta) {
        http_response_code(404);
        echo json_encode(['error' => 'La cuenta no existe en la tabla cuentas']);
        exit;
    }

    // 3. BUSCAR HISTORIAL (En la tabla 'movimientos')
    // Nota: Solo incluimos columnas básicas para asegurar que funcione
    $stmtTx = $pdo->prepare("
        SELECT id, tipo, monto, fecha
        FROM movimientos 
        WHERE cuenta_id = ?
        ORDER BY fecha DESC
        LIMIT 50
    ");
    $stmtTx->execute([$cuentaId]);
    $transacciones = $stmtTx->fetchAll(PDO::FETCH_ASSOC);

    // 4. RESPUESTA AL DASHBOARD
    echo json_encode([
        'ok' => true,
        'saldo'          => $cuenta['saldo'],
        'numeroCuenta'   => $cuenta['numero_cuenta'],
        'transacciones'  => $transacciones
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de SQL: ' . $e->getMessage()]);
    exit;
}