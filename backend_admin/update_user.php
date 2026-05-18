<?php
header('Content-Type: application/json');
include __DIR__ . '/../backend_em/conexion.php';

$raw = file_get_contents('php://input');
if (php_sapi_name() === 'cli' && trim($raw) === '') {
    $raw = stream_get_contents(STDIN);
}
$data = json_decode($raw, true);
$id = intval($data['id'] ?? 0);
$activo = isset($data['activo']) ? intval($data['activo']) : null;

if ($id <= 0 || $activo === null) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Datos inválidos']); exit; }

// Comprobar si la columna existe
try {
    $stmtC = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'activo'");
    if ($stmtC->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>"La columna 'activo' no existe. Ejecuta la migración desde backend_admin/migrate.php o crea la columna manualmente."]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['success'=>false,'error'=>'Error al verificar esquema']); exit;
}

$stmt = $pdo->prepare("UPDATE usuarios SET activo = ? WHERE id = ?");
$stmt->execute([$activo, $id]);

echo json_encode(['success'=>true,'message'=>'Estado actualizado']);
?>
