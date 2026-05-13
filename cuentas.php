<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$host       = 'localhost';
$dbname     = 'banco_db';
$usuario_bd = 'root';
$password_bd = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $usuario_bd, $password_bd);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión']);
    exit;
}

$usuarioId = intval($_GET['usuarioId'] ?? 0);

if ($usuarioId <= 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesión inválida.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, numero_cuenta, tipo, saldo, fecha_apertura
        FROM cuentas
        WHERE usuario_id = ?
        ORDER BY id DESC
    ");
    $stmt->execute([$usuarioId]);
    $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['cuentas' => $cuentas]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudieron obtener las cuentas']);
}
