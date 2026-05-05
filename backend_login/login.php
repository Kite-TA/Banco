<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

// 1. CONEXIÓN A LA BASE DE DATOS 
$host = 'localhost';
$dbname = 'banco_db';
$usuario_bd = 'root';
$password_bd = ''; // XAMPP por defecto no tiene contraseña

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $usuario_bd, $password_bd);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error de conexión al banco']);
    exit;
}

// 2. RECIBIR DATOS DEL FRONTEND
$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['error' => 'Por favor, completa todos los campos']);
    exit;
}

// 3. BUSCAR AL USUARIO POR EMAIL
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// 4. VERIFICAR CONTRASEÑA (HU-21: Validación de Seguridad)
// Usamos password_verify porque tu compañero usó password_hash en el registro
if ($usuario && password_verify($password, $usuario['password_hash'])) {
    echo json_encode([
        'mensaje' => '¡Bienvenido, ' . $usuario['nombre'] . '!',
        'usuarioId' => $usuario['id']
    ]);
} else {
    // Si no existe o la contraseña no coincide
    http_response_code(401);
    echo json_encode(['error' => 'Correo o contraseña incorrectos']);
}