<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include 'conexion.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$cuenta_id = intval($data['cuentaId'] ?? 0);
$monto     = floatval($data['monto'] ?? 0);
$tipo      = $data['tipo'] ?? ''; // 'deposito' o 'retiro'

if ($cuenta_id <= 0 || $monto <= 0 || empty($tipo)) {
    echo json_encode(['success' => false, 'error' => 'Datos de transacción incompletos.']);
    exit;
}

try {
    $pdo->beginTransaction(); // Modo seguro activo

    if ($tipo === 'deposito') {
        // 1. Sumar al saldo
        $stmt = $pdo->prepare("UPDATE cuentas SET saldo = saldo + ? WHERE id = ?");
        $stmt->execute([$monto, $cuenta_id]);

        // 2. Anotar movimiento
        $stmtMov = $pdo->prepare("INSERT INTO movimientos (cuenta_id, tipo, monto, fecha) VALUES (?, 'Deposito', ?, NOW())");
        $stmtMov->execute([$cuenta_id, $monto]);

    } elseif ($tipo === 'retiro') {
        // 1. Validar fondos antes de restar
        $stmtCheck = $pdo->prepare("SELECT saldo FROM cuentas WHERE id = ?");
        $stmtCheck->execute([$cuenta_id]);
        $saldoActual = $stmtCheck->fetchColumn();

        if ($saldoActual < $monto) {
            throw new Exception("Saldo insuficiente para retirar.");
        }

        // 2. Restar al saldo
        $stmt = $pdo->prepare("UPDATE cuentas SET saldo = saldo - ? WHERE id = ?");
        $stmt->execute([$monto, $cuenta_id]);

        // 3. Anotar movimiento
        $stmtMov = $pdo->prepare("INSERT INTO movimientos (cuenta_id, tipo, monto, fecha) VALUES (?, 'Retiro', ?, NOW())");
        $stmtMov->execute([$cuenta_id, $monto]);
    }

    $pdo->commit(); // Guardar cambios de forma permanente

    // Consultar el saldo final para actualizar tu tarjeta del Dashboard
    $stmtSaldo = $pdo->prepare("SELECT saldo FROM cuentas WHERE id = ?");
    $stmtSaldo->execute([$cuenta_id]);
    $nuevoSaldo = $stmtSaldo->fetchColumn();

    echo json_encode([
        'success' => true,
        'nuevoSaldo' => $nuevoSaldo,
        'mensaje' => '¡Transacción registrada con éxito!'
    ]);

} catch (Exception $e) {
    $pdo->rollBack(); // Si algo falla, el dinero no se toca
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>