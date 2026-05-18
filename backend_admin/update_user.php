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
$unlock = isset($data['unlock']) ? boolval($data['unlock']) : false;

if ($id <= 0 || ($activo === null && !$unlock)) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Datos inválidos']); exit; }

$needsActive = $activo !== null;
$needsLock   = $unlock;

try {
    if ($needsActive) {
        $stmtC = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'activo'");
        if ($stmtC->rowCount() === 0) {
            http_response_code(400);
            echo json_encode(['success'=>false,'error'=>"La columna 'activo' no existe. Ejecuta la migración desde backend_admin/migrate.php o crea la columna manualmente."]);
            exit;
        }
    }
    if ($needsLock) {
        $stmtF = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'failed_attempts'");
        $stmtL = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'lock_expires_at'");
        if ($stmtF->rowCount() === 0 || $stmtL->rowCount() === 0) {
            http_response_code(400);
            echo json_encode(['success'=>false,'error'=>'Las columnas de bloqueo no existen. Ejecuta la migración desde backend_admin/migrate.php.']);
            exit;
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Error al verificar esquema']);
    exit;
}

$actions = [];
$params = [];
if ($unlock) {
    $actions[] = 'failed_attempts = 0';
    $actions[] = 'lock_expires_at = NULL';
}
if ($activo !== null) {
    $actions[] = 'activo = ?';
    $params[] = $activo;
}

$sql = 'UPDATE usuarios SET ' . implode(', ', $actions) . ' WHERE id = ?';
$params[] = $id;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode(['success'=>true,'message'=>'Usuario actualizado']);
?>
