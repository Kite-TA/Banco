<?php
header("Content-Type: application/json");

// --- CONEXIÓN ---
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

// --- RECIBIR DATOS ---
$data = json_decode(file_get_contents("php://input"), true);

define('DEFAULT_USUARIO_ID', null);

$tipo = $data['tipo'] ?? '';
$saldo = $data['saldo'] ?? 0;

// --- VALIDACIONES ---
if (!$tipo || $saldo < 0) {
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

// --- GENERAR NÚMERO DE CUENTA ---
function generarNumeroCuenta() {
    return '52' . rand(1000000000, 9999999999);
}

do {
    $numeroCuenta = generarNumeroCuenta();
    $stmt = $pdo->prepare("SELECT id FROM cuentas WHERE numero_cuenta = ?");
    $stmt->execute([$numeroCuenta]);
} while ($stmt->fetch());

// --- INSERTAR ---
$sql = "INSERT INTO cuentas (numero_cuenta, tipo, saldo)
        VALUES (?, ?, ?)";

$stmt = $pdo->prepare($sql);
$result = $stmt->execute([$numeroCuenta, $tipo, $saldo]);

if ($result) {
    echo json_encode([
        'mensaje' => 'Cuenta creada correctamente',
        'numero_cuenta' => $numeroCuenta
    ]);
} else {
    echo json_encode(['error' => 'No se pudo crear la cuenta']);
}