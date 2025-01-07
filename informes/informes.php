<?php
// Conexión a la base de datos
require_once '../conexion.php';

// Array de meses y días de la semana en español
$mesesEspanol = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$diasSemanaEspanol = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

// Obtener los empleados para el select
$query = "SELECT id_empleado, nombre, apellidos FROM Empleado";
$empleados = $conexion->query($query)->fetchAll(PDO::FETCH_ASSOC);

function formatearHorasMinutos($minutos) {
    $horas = floor($minutos / 60);
    $min = $minutos % 60;
    return sprintf('%d horas y %02d minutos', $horas, $min); // Formato "X horas y YY minutos"
}

function generarTablaMes($conexion, $id_empleado, $mes, $anio, $mesesEspanol, $diasSemanaEspanol) {
    $primerDia = "$anio-" . str_pad($mes, 2, "0", STR_PAD_LEFT) . "-01"; // Asegurar formato YYYY-MM-DD
    $ultimoDia = date('Y-m-t', strtotime($primerDia)); // Último día del mes
    $horasTotales = 0; // Acumular horas trabajadas del mes

    // Obtener fichajes
    $queryFichajes = "
        SELECT 
        fecha, 
        DAYOFWEEK(fecha) AS dia_semana,
        entrada_manana, 
        salida_manana, 
        entrada_tarde, 
        salida_tarde,
        COALESCE(TIMESTAMPDIFF(MINUTE, entrada_manana, salida_manana), 0) + 
        COALESCE(TIMESTAMPDIFF(MINUTE, entrada_tarde, salida_tarde), 0) AS horas_trabajadas
    FROM Fichaje
    WHERE id_empleado = :id_empleado 
      AND fecha BETWEEN :primerDia AND :ultimoDia
    ";
    $stmtFichajes = $conexion->prepare($queryFichajes);
    $stmtFichajes->bindParam(':id_empleado', $id_empleado, PDO::PARAM_INT);
    $stmtFichajes->bindParam(':primerDia', $primerDia);
    $stmtFichajes->bindParam(':ultimoDia', $ultimoDia);
    $stmtFichajes->execute();
    $fichajes = $stmtFichajes->fetchAll(PDO::FETCH_ASSOC);

    // Obtener eventos especiales
    $queryEventos = "
        SELECT fecha_inicio, fecha_fin, tipo_evento
        FROM Eventos_especiales
        WHERE id_empleado = :id_empleado
          AND fecha_inicio <= :ultimoDia
          AND fecha_fin >= :primerDia
    ";
    $stmtEventos = $conexion->prepare($queryEventos);
    $stmtEventos->bindParam(':id_empleado', $id_empleado, PDO::PARAM_INT);
    $stmtEventos->bindParam(':primerDia', $primerDia);
    $stmtEventos->bindParam(':ultimoDia', $ultimoDia);
    $stmtEventos->execute();
    $eventos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);

    // Verificar si no hay registros de fichajes ni eventos
    if (empty($fichajes) && empty($eventos)) {
        return [
            'tabla' => "", // No genera tabla si no hay registros
            'horas_totales' => 0
        ];
    }

    ob_start();
    ?>
    <h3><?= $mesesEspanol[$mes - 1] ?> <?= $anio ?></h3>
        <table border="1" style="width: 100%; border-collapse: collapse; margin: 20px 0; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
        <thead>
            <tr>
                <th style="padding: 12px 15px; text-align: center; border-bottom: 1px solid #ddd; background-color: #4CAF50; color: white; font-weight: bold; text-transform: uppercase;">Fecha</th>
                <th style="padding: 12px 15px; text-align: center; border-bottom: 1px solid #ddd; background-color: #4CAF50; color: white; font-weight: bold; text-transform: uppercase;">Día</th>
                <th style="padding: 12px 15px; text-align: center; border-bottom: 1px solid #ddd; background-color: #4CAF50; color: white; font-weight: bold; text-transform: uppercase;">Ent. Mañana</th>
                <th style="padding: 12px 15px; text-align: center; border-bottom: 1px solid #ddd; background-color: #4CAF50; color: white; font-weight: bold; text-transform: uppercase;">Sal. Mañana</th>
                <th style="padding: 12px 15px; text-align: center; border-bottom: 1px solid #ddd; background-color: #4CAF50; color: white; font-weight: bold; text-transform: uppercase;">Ent. Tarde</th>
                <th style="padding: 12px 15px; text-align: center; border-bottom: 1px solid #ddd; background-color: #4CAF50; color: white; font-weight: bold; text-transform: uppercase;">Sal. Tarde</th>
                <th style="padding: 12px 15px; text-align: center; border-bottom: 1px solid #ddd; background-color: #4CAF50; color: white; font-weight: bold; text-transform: uppercase;">Horas Trabajadas</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $diaActual = $primerDia;
            while (strtotime($diaActual) <= strtotime($ultimoDia)):
                $diaSemana = $diasSemanaEspanol[date('w', strtotime($diaActual))];
                $esDomingo = ($diaSemana === 'Domingo');

                $diaActual = date('Y-m-d', strtotime($diaActual)); // Formato consistente

                // Buscar fichaje del día
                $fichaje = null;
                foreach ($fichajes as $registro) {
                    if ($registro['fecha'] === $diaActual) {
                        $fichaje = $registro;
                        break;
                    }
                }

                // Buscar evento especial del día
                $evento = null;
                foreach ($eventos as $e) {
                    if ($diaActual >= $e['fecha_inicio'] && $diaActual <= $e['fecha_fin']) {
                        $evento = $e;
                        break;
                    }
                }

                // Determinar tipo de evento o domingo
                $tipoEvento = null;
                $letraEvento = null;
                if ($esDomingo) {
                    $tipoEvento = 'DOMINGO';
                    $letraEvento = 'D';
                } elseif ($evento) {
                    switch (strtoupper($evento['tipo_evento'])) {
                        case 'LIBRANZA': $tipoEvento = 'LIBRANZA'; $letraEvento = 'L'; break;
                        case 'BAJA': $tipoEvento = 'BAJA'; $letraEvento = 'B'; break;
                        case 'VACACIONES': $tipoEvento = 'VACACIONES'; $letraEvento = 'V'; break;
                        case 'PERMISO': $tipoEvento = 'PERMISO'; $letraEvento = 'P'; break;
                    }
                }

                $entradasSalidas = $tipoEvento
                    ? [$letraEvento, $letraEvento, $letraEvento, $letraEvento] // Mostrar la letra del evento o domingo
                    : [
                        isset($fichaje['entrada_manana']) ? date('H:i', strtotime($fichaje['entrada_manana'])) : '-',
                        isset($fichaje['salida_manana']) ? date('H:i', strtotime($fichaje['salida_manana'])) : '-',
                        isset($fichaje['entrada_tarde']) ? date('H:i', strtotime($fichaje['entrada_tarde'])) : '-',
                        isset($fichaje['salida_tarde']) ? date('H:i', strtotime($fichaje['salida_tarde'])) : '-',
                    ];

                $horasTrabajadas = $tipoEvento
                    ? $tipoEvento // Muestra el nombre completo del evento especial en la columna "Horas Trabajadas"
                    : formatearHorasMinutos($fichaje['horas_trabajadas'] ?? 0);

                if (!$tipoEvento && isset($fichaje['horas_trabajadas'])) {
                    $horasTotales += $fichaje['horas_trabajadas'];
                }

                // Establecer fondo gris para domingos y días festivos
                $estiloFila = $esDomingo ? 'background-color:rgb(224, 224, 224);' : 'background-color: transparent;';
                ?>
                <tr style="<?= $estiloFila ?>">
                    <td><?= date('d/m/Y', strtotime($diaActual)) ?></td>
                    <td><?= $diaSemana ?></td>
                    <td><?= $entradasSalidas[0] ?></td>
                    <td><?= $entradasSalidas[1] ?></td>
                    <td><?= $entradasSalidas[2] ?></td>
                    <td><?= $entradasSalidas[3] ?></td>
                    <td><?= $horasTrabajadas ?></td>
                </tr>
                <?php
                $diaActual = date('Y-m-d', strtotime('+1 day', strtotime($diaActual))); // Incrementar al siguiente día
            endwhile;
            ?>
        </tbody>
    </table>
    <?php
    $contenidoMes = ob_get_clean();
    return [
        'tabla' => $contenidoMes,
        'horas_totales' => $horasTotales
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_empleado'], $_POST['tipo_resumen'])) {
    $id_empleado = (int)$_POST['id_empleado'];
    $tipo_resumen = $_POST['tipo_resumen'];

    $tituloInforme = ""; // Título del informe (mensual o anual)
    $nombreTrabajador = ""; // Nombre y apellidos del trabajador seleccionado

    // Obtener el nombre y apellidos del trabajador seleccionado
    foreach ($empleados as $empleado) {
        if ($empleado['id_empleado'] == $id_empleado) {
            $nombreTrabajador = $empleado['nombre'] . ' ' . $empleado['apellidos'];
            break;
        }
    }

    if ($tipo_resumen === 'mensual' && isset($_POST['mes'], $_POST['anio_mensual'])) {
        $mes = (int)$_POST['mes'];
        $anio = (int)$_POST['anio_mensual'];
        $resMes = generarTablaMes($conexion, $id_empleado, $mes, $anio, $mesesEspanol, $diasSemanaEspanol);
        if ($resMes['tabla'] === "") {
            $resultado = "<p>No se encontraron registros de fichajes ni eventos especiales para " . $mesesEspanol[$mes - 1] . " del $anio.</p>";
        } else {
            $resultado = "<h3>Total de horas trabajadas en " . $mesesEspanol[$mes - 1] . " del $anio por el empleado $nombreTrabajador: " . formatearHorasMinutos($resMes['horas_totales']) . "</h3>";
            $resultado .= $resMes['tabla'];
        }
        $tituloInforme = "Resultado del Informe Mensual";
    } elseif ($tipo_resumen === 'anual' && isset($_POST['anio_anual'])) {
        $anio = (int)$_POST['anio_anual'];
        $horasAnualesTotales = 0; // Inicializa acumulador
        $tablasMensuales = ""; // Almacena las tablas mensuales para agregarlas después
        for ($mes = 1; $mes <= 12; $mes++) {
            $resMes = generarTablaMes($conexion, $id_empleado, $mes, $anio, $mesesEspanol, $diasSemanaEspanol);
            if ($resMes['tabla'] !== "") { // Solo agrega tablas con datos
                $tablasMensuales .= $resMes['tabla'];
                $horasAnualesTotales += $resMes['horas_totales'];
            }
        }
        if ($tablasMensuales === "") {
            $resultado = "<p>No se encontraron registros de fichajes ni eventos especiales para el año $anio.</p>";
        } else {
            $resultado = "<h3>Total de horas trabajadas en $anio por el trabajador ($nombreTrabajador): " . formatearHorasMinutos($horasAnualesTotales) . "</h3>";
            $resultado .= $tablasMensuales;
        }
        $tituloInforme = "Resultado del Informe Anual";
    } else {
        $resultado = "Por favor, complete todos los campos requeridos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horas Trabajadas</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const tipoResumen = document.getElementById("tipo_resumen");
            const opcionesMensual = document.getElementById("opciones_mensual");
            const opcionesAnual = document.getElementById("opciones_anual");

            tipoResumen.addEventListener("change", function () {
                if (this.value === "mensual") {
                    opcionesMensual.style.display = "block";
                    opcionesAnual.style.display = "none";
                } else if (this.value === "anual") {
                    opcionesMensual.style.display = "none";
                    opcionesAnual.style.display = "block";
                } else {
                    opcionesMensual.style.display = "none";
                    opcionesAnual.style.display = "none";
                }
            });

            const mesActual = new Date().getMonth() + 1;
            const anioActual = new Date().getFullYear();

            document.getElementById("mes").value = mesActual;
            document.getElementById("anio_mensual").value = anioActual;
            document.getElementById("anio_anual").value = anioActual;
        });
    </script>
</head>
<body>
    <header>
        <h1>Consulta de Horas Trabajadas</h1>
    </header>
    <nav>
        <ul>
            <li><a href="../index.php">Inicio</a></li>
            <li><span>></span></li>
            <li><a href="">Informes</a></li>
        </ul>
    </nav>
    <main>
        <section class="container">
            <form action="informes.php" method="POST">
                <label for="id_empleado">Seleccione un Trabajador:</label>
                <select id="id_empleado" name="id_empleado" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($empleados as $empleado): ?>
                        <option value="<?= $empleado['id_empleado'] ?>">
                            <?= htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellidos']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="tipo_resumen">Seleccione el Tipo de Informe:</label>
                <select id="tipo_resumen" name="tipo_resumen" required>
                    <option value="">Seleccione</option>
                    <option value="mensual">Resumen Mensual</option>
                    <option value="anual">Resumen Anual</option>
                </select>

                <div id="opciones_mensual" style="display: none;">
                    <label for="mes">Seleccione el Mes:</label>
                    <select id="mes" name="mes">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>"><?= $mesesEspanol[$i - 1] ?></option>
                        <?php endfor; ?>
                    </select>

                    <label for="anio_mensual">Seleccione el Año:</label>
                    <input type="number" id="anio_mensual" name="anio_mensual" min="2024" max="<?= date('Y') ?>">
                </div>

                <div id="opciones_anual" style="display: none;">
                    <label for="anio_anual">Seleccione el Año:</label>
                    <input type="number" id="anio_anual" name="anio_anual" min="2024" max="<?= date('Y') ?>">
                </div>

                <button type="submit">Consultar</button>
                <button type="submit" name="descargar_excel" formaction="generar_excel.php">Descargar Excel</button>
            </form>
        </section>
        <!-- INICIO_INFORME -->                    
        <?php if (isset($resultado)): ?>
            <section class="container">
                <h2><?= $tituloInforme ?></h2>
                <?= $resultado ?>
            </section>
        <?php endif; ?>
        <!-- FIN_INFORME -->
    </main>
    <footer>
        <p>&copy; 2024 Gestión de Horas Trabajadas</p>
    </footer>
</body>
</html>
