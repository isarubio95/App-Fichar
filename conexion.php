<?php
// Archivo de conexión a la base de datos
$host = 'localhost';
$db = 'fichajes';
$user = 'root';
$password = '';

try {
    $conexion = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>
