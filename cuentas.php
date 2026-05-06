<?php
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'banco_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error de conexión']);
    exit;
}

try {
    $stmt = $pdo->query('SELECT numero_cuenta, tipo, saldo, fecha_creacion FROM cuentas ORDER BY id DESC');
    $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['cuentas' => $cuentas]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'No se pudieron obtener las cuentas']);
}
