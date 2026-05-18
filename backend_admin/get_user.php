<?php
header('Content-Type: application/json');
include __DIR__ . '/../backend_em/conexion.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'ID inválido']); exit; }

$stmt = $pdo->prepare("SELECT id, nombre, apellidos, email, telefono, direccion, fecha_registro FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Usuario no encontrado']); exit; }

// Intentar añadir campo activo si existe
try {
    $stmtA = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'activo'");
    if ($stmtA->rowCount() > 0) {
        $stmt2 = $pdo->prepare("SELECT activo FROM usuarios WHERE id = ?");
        $stmt2->execute([$id]);
        $user['activo'] = intval($stmt2->fetchColumn());
    } else {
        $user['activo'] = 1;
    }
} catch (Exception $e) {
    $user['activo'] = 1;
}

echo json_encode(['success'=>true,'user'=>$user]);
?>
