<?php
// Conexión a la base de datos
require_once '../conexion.php';

// Verificar que los datos fueron enviados correctamente
if (!isset($_POST['id_empleado'], $_POST['tipo_evento'], $_POST['fecha_inicio'], $_POST['fecha_fin']) || 
    empty($_POST['id_empleado']) || 
    empty($_POST['tipo_evento']) || 
    empty($_POST['fecha_inicio']) || 
    empty($_POST['fecha_fin'])) {
    echo "<script>alert('Error: Datos incompletos.'); window.location.href='fichajes.php';</script>";
    exit;
}

$id_empleado = (int)$_POST['id_empleado'];
$tipo_evento = $_POST['tipo_evento'];
$fecha_inicio = $_POST['fecha_inicio'];
$fecha_fin = $_POST['fecha_fin'];
$descripcion = !empty($_POST['descripcion']) ? $_POST['descripcion'] : null;

try {
    // Verificar si el empleado ya tiene un evento especial en el rango de fechas solicitado
    $queryConflicto = "
    SELECT * 
    FROM Eventos_especiales 
    WHERE id_empleado = :id_empleado 
    AND (
        (fecha_inicio <= :fecha_fin AND fecha_fin >= :fecha_inicio) -- Solapamiento
    )
    ";
    $stmtConflicto = $conexion->prepare($queryConflicto);
    $stmtConflicto->bindParam(':id_empleado', $id_empleado, PDO::PARAM_INT);
    $stmtConflicto->bindParam(':fecha_inicio', $fecha_inicio);
    $stmtConflicto->bindParam(':fecha_fin', $fecha_fin);
    $stmtConflicto->execute();
    $conflicto = $stmtConflicto->fetch(PDO::FETCH_ASSOC);

    if ($conflicto) {
    echo "<script>alert('Error: Ya existe un evento especial registrado que se solapa con este rango de fechas.'); window.location.href='fichajes.php';</script>";
    exit;
    }

    // Verificar si el empleado ya tiene fichajes en las fechas solicitadas
    $queryFichaje = "SELECT * FROM Fichaje WHERE id_empleado = :id_empleado AND fecha BETWEEN :fecha_inicio AND :fecha_fin";
    $stmtFichaje = $conexion->prepare($queryFichaje);
    $stmtFichaje->bindParam(':id_empleado', $id_empleado, PDO::PARAM_INT);
    $stmtFichaje->bindParam(':fecha_inicio', $fecha_inicio);
    $stmtFichaje->bindParam(':fecha_fin', $fecha_fin);
    $stmtFichaje->execute();
    $fichajeExistente = $stmtFichaje->fetch(PDO::FETCH_ASSOC);

    if ($fichajeExistente) {
    echo "<script>alert('Error: No se puede registrar un día especial porque ya hay fichajes registrados en el rango de fechas.'); window.location.href='fichajes.php';</script>";
    exit;
    }

    // Validar que la fecha de fin no sea anterior a la fecha de inicio
    if (strtotime($fecha_fin) < strtotime($fecha_inicio)) {
        echo "<script>alert('Error: La fecha de fin no puede ser anterior a la fecha de inicio.'); window.location.href='fichajes.php';</script>";
        exit;
    }

    // Verificar si el empleado existe
    $query = "SELECT id_empleado FROM Empleado WHERE id_empleado = :id_empleado";
    $stmt = $conexion->prepare($query);
    $stmt->bindParam(':id_empleado', $id_empleado, PDO::PARAM_INT);
    $stmt->execute();
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$empleado) {
        echo "<script>alert('Error: El empleado no existe.'); window.location.href='fichajes.php';</script>";
        exit;
    }

    // Insertar el evento especial en la base de datos
    $query = "INSERT INTO Eventos_especiales (id_empleado, tipo_evento, fecha_inicio, fecha_fin, descripcion)
              VALUES (:id_empleado, :tipo_evento, :fecha_inicio, :fecha_fin, :descripcion)";
    $stmt = $conexion->prepare($query);
    $stmt->bindParam(':id_empleado', $id_empleado, PDO::PARAM_INT);
    $stmt->bindParam(':tipo_evento', $tipo_evento, PDO::PARAM_STR);
    $stmt->bindParam(':fecha_inicio', $fecha_inicio, PDO::PARAM_STR);
    $stmt->bindParam(':fecha_fin', $fecha_fin, PDO::PARAM_STR);
    $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);

    $stmt->execute();

    echo "<script>alert('El evento especial se ha registrado correctamente.'); window.location.href='fichajes.php';</script>";
} catch (Exception $e) {
    echo "<script>alert('Error al registrar el evento especial: " . $e->getMessage() . "'); window.location.href='fichajes.php';</script>";
    exit;
}
?>
