<?php
include 'conexion.php';

// 1. Obtener el ID desde la URL
$cuenta_id = $_GET['cuenta_id'] ?? null;

// 2. Validación de seguridad para el ID
if (!$cuenta_id || $cuenta_id === "null" || $cuenta_id === "undefined") {
    die("Acceso denegado: No se recibió un ID de cuenta válido por la URL.");
}


try {
    // 3. Consultar historial en la tabla 'transacciones'
    $stmt = $pdo->prepare("SELECT fecha, tipo, descripcion, monto, saldo_despues FROM transacciones WHERE cuenta_id = ? ORDER BY fecha DESC");
    $stmt->execute([$cuenta_id]);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Cabeceras para que el navegador entienda que es un archivo de Excel (CSV)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Estado_Cuenta_Lupita.csv');

    // 5. Crear el archivo
    $archivo = fopen('php://output', 'w');
    
    // Excel reconozca los caracteres especiales
    fprintf($archivo, chr(0xEF).chr(0xBB).chr(0xBF));

    // Encabezados de las columnas
    fputcsv($archivo, ['FECHA Y HORA', 'TIPO DE OPERACION', 'DESCRIPCION', 'MONTO ($)', 'SALDO']);

    // Meter los datos de la base de datos al archivo
    foreach ($datos as $fila) {
        fputcsv($archivo, $fila);
    }

    fclose($archivo);
    exit;

} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}