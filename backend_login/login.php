<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$host       = 'localhost';
$dbname     = 'banco_db';
$usuario_bd = 'root';
$password_bd = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $usuario_bd, $password_bd);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error de conexión al banco']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$email    = trim($data['email']    ?? '');
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['error' => 'Por favor, completa todos los campos']);
    exit;
}

// Buscar usuario + su cuenta en una sola consulta
$stmt = $pdo->prepare("
    SELECT u.id, u.nombre, u.password_hash, c.numero_cuenta, c.saldo, c.id as cuenta_id
    FROM usuarios u
    LEFT JOIN cuentas c ON c.usuario_id = u.id
    WHERE u.email = ?
    LIMIT 1
");
$stmt->execute([$email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario && password_verify($password, $usuario['password_hash'])) {
    echo json_encode([
        'mensaje'      => '¡Bienvenido, ' . $usuario['nombre'] . '!',
        'usuarioId'    => $usuario['id'],
        'nombre'       => $usuario['nombre'],
        'numeroCuenta' => $usuario['numero_cuenta'],
        'saldo'        => $usuario['saldo'],
        'cuentaId'     => $usuario['cuenta_id']
    ]);
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Correo o contraseña incorrectos']);
}
