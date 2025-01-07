<?php
// Conexión a la base de datos
require_once '../conexion.php';
require_once 'fichajes.php';

// Verificar que los datos fueron enviados correctamente
if (!isset($_POST['id_empleado'], $_POST['accion'], $_POST['contrasena']) || empty($_POST['id_empleado']) || empty($_POST['accion']) || empty($_POST['contrasena'])) {
    echo "<script>alert('Error: Datos incompletos.'); window.location.href='fichajes.php';</script>";
    exit;
}

$id_empleado = (int)$_POST['id_empleado'];
$contrasena = $_POST['contrasena'];
$accion = $_POST['accion']; // 'entrada' o 'salida'
$hora_actual = date('H:i:s');
$fecha = date('Y-m-d');
$periodo = (date('H') < 15) ? 'manana' : 'tarde';

// Verificar si el empleado tiene un evento especial registrado para hoy
$queryEventoEspecial = "SELECT * FROM Eventos_especiales WHERE id_empleado = :id_empleado AND :fecha BETWEEN fecha_inicio AND fecha_fin";
$stmtEventoEspecial = $conexion->prepare($queryEventoEspecial);
$stmtEventoEspecial->bindParam(':id_empleado', $id_empleado, PDO::PARAM_INT);
$stmtEventoEspecial->bindParam(':fecha', $fecha);
$stmtEventoEspecial->execute();
$eventoEspecial = $stmtEventoEspecial->fetch(PDO::FETCH_ASSOC);

if ($eventoEspecial) {
    echo "<script>alert('Error: No se puede fichar porque ya hay un evento especial registrado para esta fecha.'); window.location.href='fichajes.php';</script>";
    exit;
}

try {
    // Verificar si el empleado existe y la contraseña es correcta
    $query = "SELECT contrasena FROM Empleado WHERE id_empleado = :id_empleado";
    $stmt = $conexion->prepare($query);
    $stmt->bindParam(':id_empleado', $id_empleado, PDO::PARAM_INT);
    $stmt->execute();
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$empleado || $empleado['contrasena'] !== $contrasena) {
        echo "<script>alert('Error: ID o contraseña incorrectos.'); window.location.href='fichajes.php';</script>";
        exit;
    }

    // Verificar si el empleado ya tiene un fichaje para la fecha y el periodo actual
    $query = "SELECT * FROM Fichaje WHERE id_empleado = :id_empleado AND fecha = :fecha";
    $stmt = $conexion->prepare($query);
    $stmt->bindParam(':id_empleado', $id_empleado, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha);
    $stmt->execute();
    $fichajeExistente = $stmt->fetch(PDO::FETCH_ASSOC);

    // Determinar el campo a actualizar
    $campo_fichaje = null;
    if ($accion === 'entrada') {
        $campo_fichaje = ($periodo === 'manana') ? 'entrada_manana' : 'entrada_tarde';
    } elseif ($accion === 'salida') {
        $campo_fichaje = ($periodo === 'manana') ? 'salida_manana' : 'salida_tarde';
    }

    if (!$campo_fichaje) {
        echo "<script>alert('Error: Acción no válida.'); window.location.href='fichajes.php';</script>";
        exit;
    }

    // Verificar conflictos de fichajes
    if ($fichajeExistente && !empty($fichajeExistente[$campo_fichaje])) {
        echo "<script>alert('Error: Ya se ha registrado este fichaje.'); window.location.href='fichajes.php';</script>";
        exit;
    }

    if (!$fichajeExistente) {
        // Insertar un nuevo registro si no existe
        $insertQuery = "INSERT INTO Fichaje (id_empleado, fecha, $campo_fichaje)
                        VALUES (:id_empleado, :fecha, :hora_actual)";
        $insertStmt = $conexion->prepare($insertQuery);
        $insertStmt->bindParam(':id_empleado', $id_empleado, PDO::PARAM_INT);
        $insertStmt->bindParam(':fecha', $fecha);
        $insertStmt->bindParam(':hora_actual', $hora_actual);
        $insertStmt->execute();
    } else {
        // Actualizar el campo correspondiente
        $updateQuery = "UPDATE Fichaje SET $campo_fichaje = :hora_actual WHERE id_empleado = :id_empleado AND fecha = :fecha";
        $updateStmt = $conexion->prepare($updateQuery);
        $updateStmt->bindParam(':hora_actual', $hora_actual);
        $updateStmt->bindParam(':id_empleado', $id_empleado, PDO::PARAM_INT);
        $updateStmt->bindParam(':fecha', $fecha);
        $updateStmt->execute();
    }
    echo '<p id="confirmationMessage">Fichaje realizado correctamente<p>';
        
} catch (Exception $e) {
    error_log("Error al procesar el fichaje: " . $e->getMessage(), 3, "C:/xampp/php_errors.log");
    exit;
}
?>
