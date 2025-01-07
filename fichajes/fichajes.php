<?php
    // Conexión a la base de datos
    require_once '../conexion.php';

    // Obtener todos los empleados con sus IDs, nombres y apellidos
    $queryEmpleados = "
        SELECT id_empleado, nombre, apellidos
        FROM Empleado
    ";
    $empleados = $conexion->query($queryEmpleados)->fetchAll(PDO::FETCH_ASSOC);

    // Obtener los últimos 10 fichajes junto con el nombre y apellido del empleado
    $query = "SELECT Fichaje.fecha, Fichaje.entrada_manana, Fichaje.salida_manana, Fichaje.entrada_tarde, Fichaje.salida_tarde, 
                    Empleado.nombre, Empleado.apellidos
            FROM Fichaje
            INNER JOIN Empleado ON Fichaje.id_empleado = Empleado.id_empleado
            ORDER BY Fichaje.fecha DESC
            LIMIT 10";
    $fichajes = $conexion->query($query)->fetchAll(PDO::FETCH_ASSOC);

    // Obtener el último fichaje efectuado junto con el nombre y apellido del empleado
    $queryUltimoFichaje = "
        SELECT Fichaje.fecha, Fichaje.entrada_manana, Fichaje.salida_manana, Fichaje.entrada_tarde, Fichaje.salida_tarde, 
            Empleado.nombre, Empleado.apellidos, Fichaje.id_fichaje
        FROM Fichaje
        INNER JOIN Empleado ON Fichaje.id_empleado = Empleado.id_empleado
        ORDER BY Fichaje.fecha DESC, Fichaje.id_fichaje DESC
        LIMIT 1
    ";
    $ultimoFichaje = $conexion->query($queryUltimoFichaje)->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fichajes</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <script src="../script.js" defer></script>
</head>
<body>
    <header>
        <h1>Fichar Entrada/Salida</h1>
    </header>
    <nav>
        <ul>
            <li><a href="../index.php">Inicio</a></li>
            <li><span>></span></li>
            <li><a href="">Fichajes</a></li>
        </ul>
    </nav>
    <main> 
        <section class="container-row">
            <div class="container">
                <h2>Registros libranza, vacaciones, bajas o permisos</h2>
                <form action="procesar_dia_especial.php" method="POST">
                    <label for="id_empleado_especial">ID Empleado:</label>
                    <input type="number" id="id_empleado_especial" name="id_empleado" required>

                    <label for="tipo_evento">Tipo de Evento:</label>
                    <select id="tipo_evento" name="tipo_evento" required>
                        <option value="Vacaciones">Vacaciones</option>
                        <option value="Libranza">Libranza</option>
                        <option value="Baja">Baja</option>
                        <option value="Permiso">Permiso</option>
                    </select>

                    <label for="fecha_inicio">Fecha de Inicio:</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" required>

                    <label for="fecha_fin">Fecha de Fin:</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" required>

                    <label for="descripcion">Descripción (opcional):</label>
                    <textarea id="descripcion" name="descripcion" rows="3"></textarea>

                    <button type="submit" name="accion" value="registrar_especial">Registrar Día Especial</button>
                </form>
            </div>

            <div class="container">
                <h2>Registrar Fichaje</h2>
                <form action="procesar_fichaje.php" method="POST">
                    <label for="id_empleado">ID Empleado:</label>
                    <input type="number" id="id_empleado" name="id_empleado" autofocus required>

                    <label for="contrasena">Contraseña:</label>
                    <input type="password" id="contrasena" name="contrasena" required>

                    <button type="submit" name="accion" value="entrada">Fichar Entrada</button>
                    <button type="submit" name="accion" value="salida">Fichar Salida</button>
                </form>
                <div class="ultimo-fichaje">

                    <?php if ($ultimoFichaje): ?>
                        <p><strong>Último Fichaje:</strong></p>
                        <p>Empleado: <?= htmlspecialchars($ultimoFichaje['nombre'] . ' ' . $ultimoFichaje['apellidos']) ?></p>
                        <p>Fecha: <?= htmlspecialchars($ultimoFichaje['fecha']) ?></p>
                        <p>
                            Tipo: 
                            <?php
                                if ($ultimoFichaje['entrada_manana']) {
                                    echo 'Entrada Mañana a las ' . htmlspecialchars($ultimoFichaje['entrada_manana']);
                                } elseif ($ultimoFichaje['salida_manana']) {
                                    echo 'Salida Mañana a las ' . htmlspecialchars($ultimoFichaje['salida_manana']);
                                } elseif ($ultimoFichaje['entrada_tarde']) {
                                    echo 'Entrada Tarde a las ' . htmlspecialchars($ultimoFichaje['entrada_tarde']);
                                } elseif ($ultimoFichaje['salida_tarde']) {
                                    echo 'Salida Tarde a las ' . htmlspecialchars($ultimoFichaje['salida_tarde']);
                                }
                            ?>
                        </p>
                        <form action="eliminar_fichaje.php" method="POST">
                            <input type="hidden" name="id_fichaje" value="<?= htmlspecialchars($ultimoFichaje['id_fichaje']) ?>">
                            <a class="borrar" type="submit" onclick="return confirm('¿Estás seguro de que deseas eliminar este fichaje?')">Eliminar Fichaje</a>
                        </form>
                    <?php else: ?>
                        <p>No hay fichajes registrados.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="container">
                <h2>Lista de Empleados</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Apellidos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($empleados)): ?>
                            <?php foreach ($empleados as $empleado): ?>
                                <tr>
                                    <td><?= htmlspecialchars($empleado['id_empleado']) ?></td>
                                    <td><?= htmlspecialchars($empleado['nombre']) ?></td>
                                    <td><?= htmlspecialchars($empleado['apellidos']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">No hay empleados registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        
        <div class="container">
            <h2>Últimos 10 Fichajes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Fecha</th>
                        <th>Entrada Mañana</th>
                        <th>Salida Mañana</th>
                        <th>Entrada Tarde</th>
                        <th>Salida Tarde</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fichajes as $fichaje): ?>
                        <tr>
                            <td><?= htmlspecialchars($fichaje['nombre'] . ' ' . $fichaje['apellidos']) ?></td>
                            <td><?= htmlspecialchars($fichaje['fecha']) ?></td>
                            <td><?= htmlspecialchars($fichaje['entrada_manana'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($fichaje['salida_manana'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($fichaje['entrada_tarde'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($fichaje['salida_tarde'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    <footer>
        <p>&copy; 2024 Gestión de Fichajes</p>
    </footer>
</body>
</html>
