<?php
// 1. Iniciamos la "memoria" del servidor
session_start();

// 2. Cabeceras para que el navegador entienda que enviamos JSON
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// 3. Configuración de tu base de datos
$host       = 'localhost';
$dbname     = 'banco_db';
$usuario_bd = 'root';
$password_bd = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $usuario_bd, $password_bd);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión al banco']);
    exit;
}

// 4. Recibimos los datos del Login (Email y Password)
$data     = json_decode(file_get_contents('php://input'), true);
$email    = trim($data['email']    ?? '');
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Por favor, completa todos los campos']);
    exit;
}

// 5. Buscamos al usuario y su cuenta vinculada (usando el usuario_id que creamos)
$stmt = $pdo->prepare("
    SELECT u.id, u.nombre, u.password_hash, c.numero_cuenta, c.saldo, c.id_cuenta as cuenta_id
    FROM usuarios u
    LEFT JOIN cuentas c ON c.usuario_id = u.id
    WHERE u.email = ?
    LIMIT 1
");
$stmt->execute([$email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// 6. Verificamos si la contraseña es correcta
if ($usuario && password_verify($password, $usuario['password_hash'])) {
    
    // ¡AQUÍ ESTÁ LA MAGIA! Guardamos el ID en la sesión
    $_SESSION['id_usuario'] = $usuario['id'];
    $_SESSION['nombre']     = $usuario['nombre'];

    echo json_encode([
        'success'      => true,
        'mensaje'      => '¡Bienvenido, ' . $usuario['nombre'] . '!',
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
?>