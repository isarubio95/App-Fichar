<?php
require_once '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_fichaje'])) {
    $id_fichaje = (int)$_POST['id_fichaje'];

    try {
        // Verificar si el fichaje existe
        $queryVerificar = "SELECT * FROM Fichaje WHERE id_fichaje = :id_fichaje";
        $stmtVerificar = $conexion->prepare($queryVerificar);
        $stmtVerificar->bindParam(':id_fichaje', $id_fichaje, PDO::PARAM_INT);
        $stmtVerificar->execute();
        $fichaje = $stmtVerificar->fetch(PDO::FETCH_ASSOC);

        if (!$fichaje) {
            echo "<script>alert('Error: El fichaje no existe.'); window.location.href='fichajes.php';</script>";
            exit;
        }

        // Eliminar el fichaje
        $queryEliminar = "DELETE FROM Fichaje WHERE id_fichaje = :id_fichaje";
        $stmtEliminar = $conexion->prepare($queryEliminar);
        $stmtEliminar->bindParam(':id_fichaje', $id_fichaje, PDO::PARAM_INT);
        $stmtEliminar->execute();

        echo "<script>alert('Fichaje eliminado correctamente.'); window.location.href='fichajes.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Error al eliminar el fichaje: " . $e->getMessage() . "'); window.location.href='fichajes.php';</script>";
    }
} else {
    echo "<script>alert('Error: Solicitud no válida.'); window.location.href='fichajes.php';</script>";
    exit;
}
?>
