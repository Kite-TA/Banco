<?php
header('Content-Type: application/json');
include __DIR__ . '/../backend_em/conexion.php';

$q = trim($_GET['q'] ?? '');
$limit = intval($_GET['limit'] ?? 100);
$offset = intval($_GET['offset'] ?? 0);
$estado = isset($_GET['estado']) ? $_GET['estado'] : null;

$colActive = false;
$colFail = false;
$colLock = false;
try {
    $stmtC = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'activo'");
    if ($stmtC->rowCount() > 0) $colActive = true;
    $stmtF = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'failed_attempts'");
    if ($stmtF->rowCount() > 0) $colFail = true;
    $stmtL = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'lock_expires_at'");
    if ($stmtL->rowCount() > 0) $colLock = true;
} catch (Exception $e) { }

$sql = "SELECT id, nombre, apellidos, email, telefono, direccion, fecha_registro";
if ($colActive) $sql .= ", activo";
if ($colFail) $sql .= ", failed_attempts";
if ($colLock) $sql .= ", lock_expires_at";
$sql .= " FROM usuarios";

$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(nombre LIKE ? OR apellidos LIKE ? OR email LIKE ?)";
    $like = "%$q%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($colActive && $estado !== null) {
    if ($estado === 'active') { $where[] = "activo = 1"; }
    elseif ($estado === 'inactive') { $where[] = "activo = 0"; }
}

if (count($where) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY fecha_registro DESC LIMIT ? OFFSET ?';
$params[] = $limit; $params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'count' => count($rows), 'users' => $rows]);
?>
