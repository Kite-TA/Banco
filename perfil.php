<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

$host        = 'localhost';
$dbname      = 'banco_db';
$usuario_bd  = 'root';
$password_bd = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $usuario_bd, $password_bd);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

// Obtener usuarioId desde GET o POST
$usuarioId = intval($_GET['usuarioId'] ?? $_POST['usuarioId'] ?? 0);

if ($usuarioId <= 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesión inválida. Por favor inicia sesión de nuevo.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            id,
            nombre,
            apellidos,
            email,
            telefono,
            direccion,
            DATE_FORMAT(fecha_registro, '%d/%m/%Y') AS fecha_registro
        FROM usuarios
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$usuarioId]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuario no encontrado.']);
        exit;
    }

    echo json_encode(['success' => true, 'usuario' => $usuario]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudieron obtener los datos del perfil.']);
}
?>
