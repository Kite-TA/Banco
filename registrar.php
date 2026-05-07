<?php
// Configurar la respuesta como JSON y permitir peticiones desde cualquier origen
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// 1. CONEXIÓN A LA BASE DE DATOS (MySQL)
$host       = 'localhost';
$dbname     = 'banco_db';
$usuario_bd = 'root';
$password_bd = ''; // XAMPP por defecto no tiene contraseña

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $usuario_bd, $password_bd);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

// 2. OBTENER LOS DATOS ENVIADOS DESDE JS
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['error' => 'No se recibieron datos']);
    exit;
}

$nombre    = trim($data['nombre']    ?? '');
$apellidos = trim($data['apellidos'] ?? '');
$email     = trim($data['email']     ?? '');
$telefono  = trim($data['telefono']  ?? '');
$direccion = trim($data['direccion'] ?? '');
$password  = $data['password']       ?? '';

// 3. VALIDACIONES DEL LADO DEL SERVIDOR
if (empty($nombre) || empty($apellidos) || empty($email) || empty($telefono) || empty($direccion) || empty($password)) {
    echo json_encode(['error' => 'Todos los campos son obligatorios']);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(['error' => 'La contraseña debe tener al menos 6 caracteres']);
    exit;
}

// 4. VERIFICAR SI EL EMAIL YA EXISTE
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['error' => 'El correo electrónico ya está registrado']);
    exit;
}

// 5. ENCRIPTAR LA CONTRASEÑA
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// 6. USAR TRANSACCIÓN para insertar usuario Y crear su cuenta bancaria
try {
    $pdo->beginTransaction();

    // Insertar usuario
    $sql  = "INSERT INTO usuarios (nombre, apellidos, email, telefono, direccion, password_hash)
             VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre, $apellidos, $email, $telefono, $direccion, $passwordHash]);
    $nuevoUsuarioId = $pdo->lastInsertId();

    // Generar número de cuenta único (10 dígitos)
    do {
        $numeroCuenta = str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
        $check = $pdo->prepare("SELECT id FROM cuentas WHERE numero_cuenta = ?");
        $check->execute([$numeroCuenta]);
    } while ($check->fetch());

    // Crear cuenta bancaria de ahorro con saldo inicial de $0
    $sqlCuenta = "INSERT INTO cuentas (usuario_id, tipo, numero_cuenta, saldo) VALUES (?, 'ahorro', ?, 0.00)";
    $stmtCuenta = $pdo->prepare($sqlCuenta);
    $stmtCuenta->execute([$nuevoUsuarioId, $numeroCuenta]);

    $pdo->commit();

    echo json_encode([
        'mensaje'      => 'Usuario registrado exitosamente',
        'usuarioId'    => $nuevoUsuarioId,
        'numeroCuenta' => $numeroCuenta
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => 'No se pudo completar el registro']);
}
