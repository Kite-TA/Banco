<?php
//Esta linea nomas la hice para verificar si esta funcionando el formulario con la base de datos, para recibir los datos y verlos en un archivo de texto, luego la borro
file_put_contents('debug_log.txt', print_r($_POST, true) . "\n" . file_get_contents('php://input') . "\n", FILE_APPEND);

// Configurar la respuesta como JSON y permitir peticiones desde cualquier origen
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// 1. CONEXIÓN A LA BASE DE DATOS (MySQL)
$host = 'localhost';
$dbname = 'banco_db';
$usuario_bd = 'root';
$password_bd = ''; //en este caso use XAMPP, por eso no tengo contraseña

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

// Extraer cada campo con valores por defecto vacíos
$nombre = trim($data['nombre'] ?? '');
$apellidos = trim($data['apellidos'] ?? '');
$email = trim($data['email'] ?? '');
$telefono = trim($data['telefono'] ?? '');
$direccion = trim($data['direccion'] ?? '');
$password = $data['password'] ?? '';

// 3. VALIDACIONES DEL LADO DEL SERVIDOR (obligatorias por seguridad)
if (empty($nombre) || empty($apellidos) || empty($email) || empty($telefono) || empty($direccion) || empty($password)) {
    echo json_encode(['error' => 'Todos los campos son obligatorios']);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(['error' => 'La contraseña debe tener al menos 6 caracteres']);
    exit;
}
if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/u', $nombre)) {
    echo json_encode(['error' => 'El nombre solo puede contener letras y espacios']);
    exit;
}
if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/u', $apellidos)) {
    echo json_encode(['error' => 'Los apellidos solo pueden contener letras y espacios']);
    exit;
}
if (!preg_match('/^[0-9]+$/', $telefono)) {
    echo json_encode(['error' => 'El teléfono solo puede contener números']);
    exit;
}
if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9 ,.\-#\/]+$/u', $direccion)) {
    echo json_encode(['error' => 'La dirección no puede contener caracteres especiales']);
    exit;
}

// 4. VERIFICAR SI EL EMAIL YA EXISTE (evitar duplicados)
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['error' => 'El correo electrónico ya está registrado']);
    exit;
}

// 5. ENCRIPTAR LA CONTRASEÑA
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// 6. INSERTAR EL NUEVO USUARIO EN LA TABLA
$sql = "INSERT INTO usuarios (nombre, apellidos, email, telefono, direccion, password_hash) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$resultado = $stmt->execute([$nombre, $apellidos, $email, $telefono, $direccion, $passwordHash]);

if ($resultado) {
    echo json_encode(['mensaje' => 'Usuario registrado exitosamente', 'usuarioId' => $pdo->lastInsertId()]);
} else {
    echo json_encode(['error' => 'No se pudo registrar el usuario']);
}