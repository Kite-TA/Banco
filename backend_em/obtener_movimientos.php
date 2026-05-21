<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include 'conexion.php';

$id_bruto  = $_GET['cuentaId'] ?? $_GET['cuenta_id'] ?? 0;
$cuentaId  = intval($id_bruto);
$usuarioId = intval($_GET['usuarioId'] ?? 0);

if ($cuentaId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de cuenta no válido']);
    exit;
}

if ($usuarioId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesión inválida']);
    exit;
}

try {
    // Verificar propiedad: la cuenta debe pertenecer al usuario que consulta
    $stmt = $pdo->prepare("SELECT numero_cuenta, saldo FROM cuentas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$cuentaId, $usuarioId]);
    $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cuenta) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para consultar esta cuenta']);
        exit;
    }

    $stmtMovs = $pdo->prepare("
        SELECT
            fecha, tipo,
            COALESCE(descripcion, CASE LOWER(tipo)
                WHEN 'deposito' THEN 'Depósito en ventanilla'
                WHEN 'retiro'   THEN 'Retiro de efectivo'
                ELSE ''
            END) AS descripcion,
            cuenta_relacionada, monto, saldo_despues
        FROM transacciones
        WHERE cuenta_id = ?
        ORDER BY fecha DESC
    ");
    $stmtMovs->execute([$cuentaId]);
    $transacciones = $stmtMovs->fetchAll(PDO::FETCH_ASSOC);

    if (isset($_GET['export']) && $_GET['export'] === 'excel') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Movimientos_' . $cuenta['numero_cuenta'] . '.csv');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, ['Fecha y Hora', 'Tipo de Operación', 'Detalles', 'Monto (MXN)', 'Saldo Resultante']);
        foreach ($transacciones as $row) {
            fputcsv($out, [$row['fecha'], $row['tipo'], $row['descripcion'], $row['monto'], $row['saldo_despues']]);
        }
        fclose($out);
        exit;
    }

    echo json_encode([
        'success'       => true,
        'saldo'         => floatval($cuenta['saldo']),
        'numeroCuenta'  => $cuenta['numero_cuenta'],
        'transacciones' => $transacciones
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
