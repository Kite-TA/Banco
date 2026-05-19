<?php
// Ejecuta esta ruta una vez para añadir las columnas necesarias a la tabla usuarios
header('Content-Type: application/json');
include __DIR__ . '/../backend_em/conexion.php';

function ensureColumn(PDO $pdo, string $column, string $definition) {
    $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE '$column'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN $definition");
    }
}

try {
    ensureColumn($pdo, 'activo', "activo TINYINT(1) NOT NULL DEFAULT 1");
    ensureColumn($pdo, 'failed_attempts', "failed_attempts INT(3) NOT NULL DEFAULT 0");
    ensureColumn($pdo, 'lock_expires_at', "lock_expires_at DATETIME NULL DEFAULT NULL");
    echo json_encode(['success' => true, 'message' => 'Migración aplicada: columnas activo, failed_attempts y lock_expires_at creadas si no existían.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
