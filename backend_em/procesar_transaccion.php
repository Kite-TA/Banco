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

    $stmtCheck = $pdo->prepare("SELECT saldo FROM cuentas WHERE id = ?");
    $stmtCheck->execute([$cuenta_id]);
    $saldoActual = $stmtCheck->fetchColumn();

    if ($saldoActual === false) {
        throw new Exception('Cuenta no encontrada.');
    }

    if ($tipo === 'deposito') {
        $nuevoSaldo = $saldoActual + $monto;
        $stmt = $pdo->prepare("UPDATE cuentas SET saldo = ? WHERE id = ?");
        $stmt->execute([$nuevoSaldo, $cuenta_id]);

        $stmtMov = $pdo->prepare("INSERT INTO transacciones (cuenta_id, tipo, monto, descripcion, saldo_despues, fecha) VALUES (?, 'deposito', ?, 'Depósito en ventanilla', ?, NOW())");
        $stmtMov->execute([$cuenta_id, $monto, $nuevoSaldo]);

    } elseif ($tipo === 'retiro') {
        if ($saldoActual < $monto) {
            throw new Exception("Saldo insuficiente para retirar.");
        }

        $nuevoSaldo = $saldoActual - $monto;
        $stmt = $pdo->prepare("UPDATE cuentas SET saldo = ? WHERE id = ?");
        $stmt->execute([$nuevoSaldo, $cuenta_id]);

        $stmtMov = $pdo->prepare("INSERT INTO transacciones (cuenta_id, tipo, monto, descripcion, saldo_despues, fecha) VALUES (?, 'retiro', ?, 'Retiro de efectivo', ?, NOW())");
        $stmtMov->execute([$cuenta_id, $monto, $nuevoSaldo]);

    } else {
        throw new Exception('Tipo de transacción inválido.');
    }

    $pdo->commit(); // Guardar cambios de forma permanente

    $nuevoSaldo = $nuevoSaldo ?? $saldoActual;

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