<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// 1. Conexión a la base de datos
include 'conexion.php';

/**
 * CAPTURA DE ID
 * Buscamos el ID en cualquier formato posible para mantener compatibilidad con tu login.
 */
$id_bruto = $_GET['cuentaId'] ?? $_GET['cuenta_id'] ?? 0;
$cuentaId = intval($id_bruto);

if ($cuentaId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'ID de cuenta no válido',
        'detalles' => ['recibido' => $id_bruto]
    ]);
    exit;
}

try {
    // 2. Información de la cuenta
    $stmt = $pdo->prepare("SELECT numero_cuenta, saldo FROM cuentas WHERE id = ?");
    $stmt->execute([$cuentaId]);
    $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cuenta) {
        throw new Exception("No existe la cuenta con ID: " . $cuentaId);
    }

    // 3. Consulta de movimientos
    $stmtMovs = $pdo->prepare("
        SELECT 
            fecha,
            tipo,
            COALESCE(descripcion, CASE LOWER(tipo)
                WHEN 'deposito' THEN 'Depósito en ventanilla'
                WHEN 'retiro' THEN 'Retiro de efectivo'
                ELSE ''
            END) AS descripcion,
            cuenta_relacionada,
            monto,
            saldo_despues
        FROM transacciones 
        WHERE cuenta_id = ? 
        ORDER BY fecha DESC
    ");
    $stmtMovs->execute([$cuentaId]);
    $transacciones = $stmtMovs->fetchAll(PDO::FETCH_ASSOC);

    /**
     * MODO EXPORTACIÓN A EXCEL (CSV)
     * Si en la URL agregas &export=excel, el PHP descargará el archivo en lugar de mostrar JSON.
     */
    if (isset($_GET['export']) && $_GET['export'] === 'excel') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Movimientos_Cuenta_' . $cuenta['numero_cuenta'] . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Añadimos el BOM para que Excel reconozca los acentos correctamente (UTF-8)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados de las columnas
        fputcsv($output, ['Fecha y Hora', 'Tipo de Operación', 'Detalles', 'Monto (MXN)', 'Saldo Resultante']);
        
        foreach ($transacciones as $row) {
            fputcsv($output, [
                $row['fecha'],
                $row['tipo'],
                $row['descripcion'],
                $row['monto'],
                $row['saldo_despues']
            ]);
        }
        
        fclose($output);
        exit;
    }

    // 4. Respuesta normal en JSON para la tabla del Dashboard
    echo json_encode([
        'success' => true,
        'saldo' => floatval($cuenta['saldo']),
        'numeroCuenta' => $cuenta['numero_cuenta'],
        'transacciones' => $transacciones
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>