<?php
require_once '../conexion.php';

function obtenerFestivos($conexion) {
    $query = "SELECT id_festivo, fecha, descripcion FROM Festivo ORDER BY fecha";
    return $conexion->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

function crearFestivo($conexion, $fecha, $descripcion) {
    $query = "INSERT INTO Festivo (fecha, descripcion, ano) VALUES (:fecha, :descripcion, YEAR(:fecha))";
    $stmt = $conexion->prepare($query);
    $stmt->bindParam(':fecha', $fecha);
    $stmt->bindParam(':descripcion', $descripcion);
    $stmt->execute();
}

function borrarFestivo($conexion, $idFestivo) {
    $query = "DELETE FROM Festivo WHERE id_festivo = :id_festivo";
    $stmt = $conexion->prepare($query);
    $stmt->bindParam(':id_festivo', $idFestivo, PDO::PARAM_INT);
    $stmt->execute();
}
