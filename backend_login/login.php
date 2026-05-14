<?php
// 1. Iniciamos la sesión para que el banco "recuerde" quién eres
session_start();

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
} 
catch (PDOException $e) {
    // Esto nos dirá el error exacto (ej. "Base de datos no encontrada")
    echo json_encode(['success' => false, 'error' => 'Error real: ' . $e->getMessage()]);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$email    = trim($data['email']    ?? '');
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Por favor, completa todos los campos']);
    exit;
}

try {
    $stmt = $pdo->prepare("
    SELECT 
        u.id, 
        u.nombre, 
        u.password_hash, 
        c.numero_cuenta, 
        c.saldo, 
        c.id as cuenta_id  
    FROM usuarios u
    LEFT JOIN cuentas c ON c.usuario_id = u.id
    WHERE u.email = ?
    LIMIT 1
");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

error_log("Password enviado: " . $password);
error_log("Hash en BD: " . $usuario['password_hash']);

    if ($usuario && password_verify($password, $usuario['password_hash'])) {
        
        // Guardamos en la "mochila" del servidor
        $_SESSION['id_usuario'] = $usuario['id'];
        $_SESSION['nombre']     = $usuario['nombre'];

        echo json_encode([
            'success'      => true,
            'mensaje'      => '¡Bienvenido, ' . $usuario['nombre'],
            'usuarioId'    => $usuario['id'],
            'nombre'       => $usuario['nombre'],
            'numeroCuenta' => $usuario['numero_cuenta'],
            'saldo'        => $usuario['saldo'],
            'cuentaId'     => $usuario['cuenta_id']
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Correo o contraseña incorrectos']);
    }
} 
catch (PDOException $e) {
    http_response_code(500); 
    echo json_encode(['success' => false, 'error' => 'Error de conexión al banco']);
    exit;
}
?>