<?php
// Configuración de la conexión a la base de datos
$host = "localhost";    // El servidor (en XAMPP siempre es localhost)
$user = "root";         // El usuario por defecto de XAMPP
$pass = "";             // En XAMPP, por defecto no hay contraseña
$db   = "banco_db";     // El nombre exacto de la base de datos que creamos

// Crear la conexión
$conexion = mysqli_connect($host, $user, $pass, $db);

// Verificar si la conexión falló
if (!$conexion) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}

// Opcional: Configurar el conjunto de caracteres a UTF-8 para evitar problemas con tildes o la letra ñ
mysqli_set_charset($conexion, "utf8");
?>