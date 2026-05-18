<?php
// Ejecuta esta ruta una vez para añadir la columna 'activo' a la tabla usuarios
header('Content-Type: application/json');
include __DIR__ . '/../backend_em/conexion.php';

try {
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1");
    echo json_encode(['success'=>true,'message'=>'Migración aplicada (columna activo creada si no existía)']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}

?>
