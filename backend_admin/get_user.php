<?php
header('Content-Type: application/json');
include __DIR__ . '/../backend_em/conexion.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'ID inválido']); exit; }

$colActive = false;
$colFail = false;
$colLock = false;
try {
    $stmtA = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'activo'");
    if ($stmtA->rowCount() > 0) $colActive = true;
    $stmtF = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'failed_attempts'");
    if ($stmtF->rowCount() > 0) $colFail = true;
    $stmtL = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'lock_expires_at'");
    if ($stmtL->rowCount() > 0) $colLock = true;
} catch (Exception $e) { }

$sql = "SELECT id, nombre, apellidos, email, telefono, direccion, fecha_registro";
if ($colActive) $sql .= ", activo";
if ($colFail) $sql .= ", failed_attempts";
if ($colLock) $sql .= ", lock_expires_at";
$sql .= " FROM usuarios WHERE id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Usuario no encontrado']); exit; }

if (!$colActive) $user['activo'] = 1;
if (!$colFail) $user['failed_attempts'] = 0;
if (!$colLock) $user['lock_expires_at'] = null;

echo json_encode(['success'=>true,'user'=>$user]);
?>
