<?php
// Conexión a la base de datos
require_once '../conexion.php';
require_once 'festivos.php';
$festivos = obtenerFestivos($conexion);

// Manejo de acciones si se envían datos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['accion'])) {
            if ($_POST['accion'] === 'crear') {
                // Crear empleado
                if (!empty($_POST['nombre']) && !empty($_POST['apellidos']) && !empty($_POST['contrasena'])) {
                    $query = "INSERT INTO Empleado (nombre, apellidos, contrasena, horas_jornada) VALUES (:nombre, :apellidos, :contrasena, :horas_jornada)";
                    $stmt = $conexion->prepare($query);
                    $stmt->bindParam(':nombre', $_POST['nombre']);
                    $stmt->bindParam(':apellidos', $_POST['apellidos']);
                    $stmt->bindParam(':contrasena', $_POST['contrasena']);
                    $stmt->bindParam(':horas_jornada', $_POST['horas_jornada'], PDO::PARAM_STR);
                    $stmt->execute();
                    $mensaje = "Empleado creado exitosamente.";
                } else {
                    throw new Exception("Todos los campos son obligatorios para crear un empleado.");
                }
            } elseif ($_POST['accion'] === 'borrar') {
                // Borrar empleado
                if (!empty($_POST['id_empleado'])) {
                    $conexion->beginTransaction();
                    try {
                        // Eliminar registros relacionados en Fichaje
                        $queryFichaje = "DELETE FROM Fichaje WHERE id_empleado = :id_empleado";
                        $stmtFichaje = $conexion->prepare($queryFichaje);
                        $stmtFichaje->bindParam(':id_empleado', $_POST['id_empleado'], PDO::PARAM_INT);
                        $stmtFichaje->execute();

                        // Eliminar empleado
                        $queryEmpleado = "DELETE FROM Empleado WHERE id_empleado = :id_empleado";
                        $stmtEmpleado = $conexion->prepare($queryEmpleado);
                        $stmtEmpleado->bindParam(':id_empleado', $_POST['id_empleado'], PDO::PARAM_INT);
                        $stmtEmpleado->execute();

                        $conexion->commit();
                        $mensaje = "Empleado y registros relacionados eliminados exitosamente.";
                    } catch (Exception $e) {
                        $conexion->rollBack();
                        throw new Exception("Error al eliminar empleado: " . $e->getMessage());
                    }
                    $mensaje = "Empleado borrado exitosamente.";
                } else {
                    throw new Exception("Debe seleccionar un empleado para borrar.");
                }
            } elseif ($_POST['accion'] === 'modificar') {
                // Modificar empleado
                if (!empty($_POST['id_empleado']) && (!empty($_POST['nombre']) || !empty($_POST['apellidos']) || !empty($_POST['contrasena']) || !empty($_POST['horas_jornada']))) {
                    $query = "UPDATE Empleado SET ";
                    $params = [];

                    if (!empty($_POST['nombre'])) {
                        $query .= "nombre = :nombre, ";
                        $params[':nombre'] = $_POST['nombre'];
                    }
                    if (!empty($_POST['apellidos'])) {
                        $query .= "apellidos = :apellidos, ";
                        $params[':apellidos'] = $_POST['apellidos'];
                    }
                    if (!empty($_POST['contrasena'])) {
                        $query .= "contrasena = :contrasena, ";
                        $params[':contrasena'] = $_POST['contrasena'];
                    }

                    if (isset($_POST['horas_jornada'])) {
                        $query .= "horas_jornada = :horas_jornada, ";
                        $params[':horas_jornada'] = $_POST['horas_jornada'];
                    }

                    // Remover la última coma y agregar la condición WHERE
                    $query = rtrim($query, ', ') . " WHERE id_empleado = :id_empleado";
                    $params[':id_empleado'] = $_POST['id_empleado'];

                    $stmt = $conexion->prepare($query);
                    foreach ($params as $key => $value) {
                        $stmt->bindValue($key, $value);
                    }
                    $stmt->execute();
                    $mensaje = "Empleado modificado exitosamente.";
                } else {
                    throw new Exception("Debe seleccionar un empleado y al menos un campo para modificar.");
                }
            }
            else if ($_POST['accion'] === 'crear_festivo') {
                // Crear festivo
                if (!empty($_POST['fecha']) && !empty($_POST['descripcion'])) {
                    crearFestivo($conexion, $_POST['fecha'], $_POST['descripcion']);
                    $mensaje = "Festivo creado exitosamente.";
                    // Redirigir a la misma página para recargarla
                    header("Location: " . $_SERVER['PHP_SELF'] . "#tabla-festivos");
                    exit;
                } else {
                    throw new Exception("Todos los campos son obligatorios para crear un festivo.");
                }
            } 
            else if ($_POST['accion'] === 'borrar_festivo') {
                // Borrar festivo
                if (!empty($_POST['id_festivo'])) {
                    borrarFestivo($conexion, $_POST['id_festivo']);
                    $mensaje = "Festivo borrado exitosamente.";
                    // Redirigir a la misma página para recargarla
                    header("Location: " . $_SERVER['PHP_SELF'] . "#tabla-festivos");
                    exit;
                } else {
                    throw new Exception("Debe seleccionar un festivo para borrar.");
                }
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener todos los empleados
$queryEmpleados = "SELECT id_empleado, nombre, apellidos FROM Empleado";
$empleados = $conexion->query($queryEmpleados)->fetchAll(PDO::FETCH_ASSOC);

// Inicializar variables
$fichajes = [];
$trabajadorSeleccionado = null;
$fechaSeleccionada = null;

// Manejo del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_empleado'], $_POST['fecha'])) {
    $trabajadorSeleccionado = (int)$_POST['id_empleado'];
    $fechaSeleccionada = $_POST['fecha'];

    // Obtener los fichajes del trabajador para la fecha seleccionada
    $queryFichajes = "
        SELECT entrada_manana, salida_manana, entrada_tarde, salida_tarde
        FROM Fichaje
        WHERE id_empleado = :id_empleado AND fecha = :fecha
    ";
    $stmt = $conexion->prepare($queryFichajes);
    $stmt->bindParam(':id_empleado', $trabajadorSeleccionado, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fechaSeleccionada);
    $stmt->execute();
    $fichajes = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Actualizar fichajes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_fichajes'])) {
    $entradaManana = $_POST['entrada_manana'] ?: null;
    $salidaManana = $_POST['salida_manana'] ?: null;
    $entradaTarde = $_POST['entrada_tarde'] ?: null;
    $salidaTarde = $_POST['salida_tarde'] ?: null;

    if ($trabajadorSeleccionado && $fechaSeleccionada) {
        // Verificar si ya existen fichajes para ese día
        $queryVerificar = "SELECT COUNT(*) FROM Fichaje WHERE id_empleado = :id_empleado AND fecha = :fecha";
        $stmtVerificar = $conexion->prepare($queryVerificar);
        $stmtVerificar->bindParam(':id_empleado', $trabajadorSeleccionado, PDO::PARAM_INT);
        $stmtVerificar->bindParam(':fecha', $fechaSeleccionada);
        $stmtVerificar->execute();
        $existeFichaje = $stmtVerificar->fetchColumn();

        if ($existeFichaje) {
            // Actualizar los fichajes
            $queryActualizar = "
                UPDATE Fichaje
                SET entrada_manana = :entrada_manana, salida_manana = :salida_manana,
                    entrada_tarde = :entrada_tarde, salida_tarde = :salida_tarde
                WHERE id_empleado = :id_empleado AND fecha = :fecha
            ";
            $stmtActualizar = $conexion->prepare($queryActualizar);
        } else {
            // Insertar nuevos fichajes
            $queryActualizar = "
                INSERT INTO Fichaje (id_empleado, fecha, entrada_manana, salida_manana, entrada_tarde, salida_tarde)
                VALUES (:id_empleado, :fecha, :entrada_manana, :salida_manana, :entrada_tarde, :salida_tarde)
            ";
            $stmtActualizar = $conexion->prepare($queryActualizar);
        }

        $stmtActualizar->bindParam(':id_empleado', $trabajadorSeleccionado, PDO::PARAM_INT);
        $stmtActualizar->bindParam(':fecha', $fechaSeleccionada);
        $stmtActualizar->bindParam(':entrada_manana', $entradaManana);
        $stmtActualizar->bindParam(':salida_manana', $salidaManana);
        $stmtActualizar->bindParam(':entrada_tarde', $entradaTarde);
        $stmtActualizar->bindParam(':salida_tarde', $salidaTarde);
        $stmtActualizar->execute();

        
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Empleados</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <header>
        <h1>Menú de Administración</h1>
    </header>
    <nav>
        <ul>
            <li><a href="../index.php">Inicio</a></li>
            <li><span>></span></li>
            <li><a href="">Administración</a></li>
        </ul>
    </nav>
    <main>
        <?php if (!empty($mensaje)): ?>
            <p class="success"><?= htmlspecialchars($mensaje) ?></p>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <section class="container-row">     
            <div class="container">
                <h2>Borrar Empleado</h2>
                <form method="POST">
                    <input type="hidden" name="accion" value="borrar">
                    <label for="id_empleado_borrar">Seleccione un Empleado:</label>
                    <select id="id_empleado_borrar" name="id_empleado" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($empleados as $empleado): ?>
                            <option value="<?= $empleado['id_empleado'] ?>">
                                <?= htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellidos']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Borrar</button>
                </form>
            </div>

            <div class="container">
                <h2>Crear Empleado</h2>
                <form method="POST" id="crearEmpleadoForm">
                    <input type="hidden" name="accion" value="crear">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" required>

                    <label for="apellidos">Apellidos:</label>
                    <input type="text" id="apellidos" name="apellidos" required>

                    <label for="contrasena">Contraseña:</label>
                    <input type="password" id="contrasena" name="contrasena" required>

                    <label for="confirmar_contrasena">Confirmar Contraseña:</label>
                    <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required>

                    <label for="horas_jornada">Horas por jornada:</label>
                    <input type="number" id="horas_jornada" name="horas_jornada" step="0.1" required>

                    <button type="submit">Crear</button>
                </form>
            </div>

            <div class="container">
                <h2>Modificar Empleado</h2>
                <form method="POST" id="modificarEmpleadoForm">
                    <input type="hidden" name="accion" value="modificar">
                    <label for="id_empleado_modificar">Seleccione un Empleado:</label>
                    <select id="id_empleado_modificar" name="id_empleado" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($empleados as $empleado): ?>
                            <option value="<?= $empleado['id_empleado'] ?>">
                                <?= htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellidos']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="nombre_modificar">Nuevo Nombre:</label>
                    <input type="text" id="nombre_modificar" name="nombre">

                    <label for="apellidos_modificar">Nuevos Apellidos:</label>
                    <input type="text" id="apellidos_modificar" name="apellidos">

                    <label for="contrasena_modificar">Nueva Contraseña:</label>
                    <input type="password" id="contrasena_modificar" name="contrasena">

                    <label for="confirmar_contrasena_modificar">Confirmar Contraseña:</label>
                    <input type="password" id="confirmar_contrasena_modificar" name="confirmar_contrasena">

                    <label for="horas_jornada_modificar">Nuevas Horas por jornada:</label>
                    <input type="number" id="horas_jornada_modificar" name="horas_jornada" step="0.1">

                    <button type="submit">Modificar</button>
                </form>
            </div>
        </section>
        
        <section class="container-row" style="margin: 20px 0 30px 0">
            <div class="container">
                <h2>Insertar o Actualizar Fichajes</h2>
                <form method="POST" action="#fichajesForm" id="fichajesForm">
                    <label for="id_empleado">Seleccione un Trabajador:</label>
                    <select id="id_empleado" name="id_empleado" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($empleados as $empleado): ?>
                            <option value="<?= $empleado['id_empleado'] ?>" <?= ($trabajadorSeleccionado == $empleado['id_empleado']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellidos']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="fecha">Seleccione la Fecha:</label>
                    <input type="date" id="fecha" name="fecha" value="<?= htmlspecialchars($fechaSeleccionada) ?>" required>

                    <button type="submit">Cargar Fichajes</button>
                </form>

                <?php if ($trabajadorSeleccionado && $fechaSeleccionada): ?>
                    <h3>Fichajes para <?= htmlspecialchars($fechaSeleccionada) ?></h3>
                    <form method="POST">
                        <input type="hidden" name="id_empleado" value="<?= $trabajadorSeleccionado ?>">
                        <input type="hidden" name="fecha" value="<?= htmlspecialchars($fechaSeleccionada) ?>">
                        <input type="hidden" name="actualizar_fichajes" value="1">

                        <label for="entrada_manana">Entrada Mañana:</label>
                        <input type="time" id="entrada_manana" name="entrada_manana" value="<?= htmlspecialchars($fichajes['entrada_manana'] ?? '') ?>">

                        <label for="salida_manana">Salida Mañana:</label>
                        <input type="time" id="salida_manana" name="salida_manana" value="<?= htmlspecialchars($fichajes['salida_manana'] ?? '') ?>">

                        <label for="entrada_tarde">Entrada Tarde:</label>
                        <input type="time" id="entrada_tarde" name="entrada_tarde" value="<?= htmlspecialchars($fichajes['entrada_tarde'] ?? '') ?>">

                        <label for="salida_tarde">Salida Tarde:</label>
                        <input type="time" id="salida_tarde" name="salida_tarde" value="<?= htmlspecialchars($fichajes['salida_tarde'] ?? '') ?>">

                        <button type="submit">Guardar Cambios</button>
                    </form>
                <?php endif; ?>
            </div>
            
            <div class="container">           
                <h2>Crear Festivo</h2>
                <form method="POST">
                    <input type="hidden" name="accion" value="crear_festivo">      
                    <label for="fecha">Fecha:</label>
                    <input type="date" id="fecha" name="fecha" required>
                    <label for="descripcion">Descripción:</label>
                    <input type="text" id="descripcion" name="descripcion" required>
                    <button type="submit">Crear</button>                                              
                </form>               
                <div>
                    <h2>Lista de festivos</h2>
                    <table id="tabla-festivos">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Festivo</th>
                                <th>Borrar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($festivos as $festivo): ?>
                                <tr>                            
                                    <td><?= htmlspecialchars($festivo['fecha']) ?></td>
                                    <td><?= htmlspecialchars($festivo['descripcion']) ?></td>   
                                    <td>
                                        <form method="POST" onsubmit="return confirm('¿Está seguro de que desea borrar este festivo?');">
                                            <input type="hidden" name="accion" value="borrar_festivo">
                                            <input type="hidden" name="id_festivo" value="<?= htmlspecialchars($festivo['id_festivo']) ?>">
                                            <button type="submit" class="btn-danger">Borrar</button>
                                        </form>
                                    </td>                        
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>   
            </div>   
        </section>     
        <script>
            function validarContraseñas(formId, passwordId, confirmPasswordId) {
                const form = document.getElementById(formId);
                const password = document.getElementById(passwordId);
                const confirmPassword = document.getElementById(confirmPasswordId);

                form.addEventListener('submit', (event) => {
                    if (password.value !== confirmPassword.value) {
                        event.preventDefault(); // Evita el envío del formulario
                        alert('Las contraseñas no coinciden. Por favor, verifica.');
                    }
                });
            }
            // Validar formularios de creación y modificación
            validarContraseñas('crearEmpleadoForm', 'contrasena', 'confirmar_contrasena');
            validarContraseñas('modificarEmpleadoForm', 'contrasena_modificar', 'confirmar_contrasena_modificar');
        </script>           
    </main>
    <footer>
        <p>&copy; 2024 Administración de Empleados</p>
    </footer>
</body>
</html>
