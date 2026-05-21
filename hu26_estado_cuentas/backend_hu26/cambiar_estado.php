<?php

header('Content-Type: application/json');

include '../../backend_em/conexion.php';

try {

    // Obtener datos JSON
    $datos = json_decode(file_get_contents("php://input"), true);

    if (!$datos) {
        throw new Exception("No llegaron datos");
    }

    $cuentaId = $datos['cuentaId'];
    $estado = $datos['estado'];

    // Consulta SQL
    $sql = "UPDATE cuentas SET estado = :estado WHERE id = :id";

    // Preparar consulta
    $stmt = $pdo->prepare($sql);

    // Ejecutar
    $ok = $stmt->execute([
        ':estado' => $estado,
        ':id' => $cuentaId
    ]);

    // Respuesta JSON
    echo json_encode([
        "success" => $ok
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}