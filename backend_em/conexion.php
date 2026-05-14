<?php
// Configuración de la base de datos
$host = 'localhost';
$db   = 'banco_db'; 
$user = 'root';      // Usuario por defecto de XAMPP
$pass = '';          // Contraseña por defecto de XAMPP (vacía)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Intentamos conectar
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Si falla, mostramos el error
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>