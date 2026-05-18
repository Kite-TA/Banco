<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

$raw = file_get_contents('php://input');
if (php_sapi_name() === 'cli' && trim($raw) === '') {
    $raw = stream_get_contents(STDIN);
}
$data = json_decode($raw, true);
$user = trim($data['user'] ?? '');
$pass = trim($data['password'] ?? '');

if ($user === $ADMIN_USER && $pass === $ADMIN_PASS) {
    echo json_encode(['success' => true, 'message' => 'Autenticado']);
    exit;
}

http_response_code(401);
echo json_encode(['success' => false, 'error' => 'Credenciales inválidas']);
exit;
?>
