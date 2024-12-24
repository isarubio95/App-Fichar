<?php
// Conexión a la base de datos
require_once 'conexion.php';

// Array de meses y días de la semana en español
$mesesEspanol = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$diasSemanaEspanol = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

// Obtener los empleados para el select
$query = "SELECT id_empleado, nombre, apellidos FROM Empleado";
$empleados = $conexion->query($query)->fetchAll(PDO::FETCH_ASSOC);

function formatearHorasMinutos($minutos) {
    $horas = floor($minutos / 60);
    $min = $minutos % 60;
    return sprintf('%d horas y %d minutos', $horas, $min);
}

function generarTablaMes($conexion, $id_empleado, $mes, $anio, $mesesEspanol, $diasSemanaEspanol) {
    $primerDia = "$anio-" . str_pad($mes, 2, "0", STR_PAD_LEFT) . "-01"; // Asegurar formato YYYY-MM-DD
    $ultimoDia = date('Y-m-t', strtotime($primerDia)); // Último día del mes

    // Obtener fichajes
    $queryFichajes = "
        SELECT 
            fecha, 
            DAYOFWEEK(fecha) AS dia_semana,
            entrada_manana, salida_manana, 
            entrada_tarde, salida_tarde,
            TIMESTAMPDIFF(MINUTE, entrada_manana, salida_manana) +
            TIMESTAMPDIFF(MINUTE, entrada_tarde, salida_tarde) AS horas_trabajadas
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

    ob_start();
    ?>
    <h3><?= $mesesEspanol[$mes - 1] ?> <?= $anio ?></h3>
    <table border="1" cellpadding="5" cellspacing="0" style="margin-bottom: 20px; width: 100%;">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Día</th>
                <th>Ent. Mañana</th>
                <th>Sal. Mañana</th>
                <th>Ent. Tarde</th>
                <th>Sal. Tarde</th>
                <th>Horas Trabajadas</th>
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
                if ($esDomingo) {
                    $tipoEvento = 'D';
                } elseif ($evento) {
                    switch (strtoupper($evento['tipo_evento'])) {
                        case 'LIBRANZA': $tipoEvento = 'L'; break;
                        case 'BAJA': $tipoEvento = 'BAJ.'; break;
                        case 'VACACIONES': $tipoEvento = 'V'; break;
                        case 'PERMISO': $tipoEvento = 'PER'; break;
                    }
                }

                $entradasSalidas = $tipoEvento
                    ? [$tipoEvento, $tipoEvento, $tipoEvento, $tipoEvento]
                    : [
                        $fichaje['entrada_manana'] ?? '-',
                        $fichaje['salida_manana'] ?? '-',
                        $fichaje['entrada_tarde'] ?? '-',
                        $fichaje['salida_tarde'] ?? '-',
                    ];

                $horasTrabajadas = $tipoEvento
                    ? $tipoEvento
                    : formatearHorasMinutos($fichaje['horas_trabajadas'] ?? 0);

                ?>
                <tr>
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
    return ob_get_clean();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_empleado'], $_POST['tipo_resumen'])) {
    $id_empleado = (int)$_POST['id_empleado'];
    $tipo_resumen = $_POST['tipo_resumen'];

    if ($tipo_resumen === 'mensual' && isset($_POST['mes'], $_POST['anio_mensual'])) {
        $mes = (int)$_POST['mes'];
        $anio = (int)$_POST['anio_mensual'];
        $resultado = generarTablaMes($conexion, $id_empleado, $mes, $anio, $mesesEspanol, $diasSemanaEspanol);
    } elseif ($tipo_resumen === 'anual' && isset($_POST['anio_anual'])) {
        $anio = (int)$_POST['anio_anual'];
        $resultado = "<h2>Resumen Anual</h2>";
        for ($mes = 1; $mes <= 12; $mes++) {
            $resultado .= generarTablaMes($conexion, $id_empleado, $mes, $anio, $mesesEspanol, $diasSemanaEspanol);
        }
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
    <link rel="stylesheet" href="styles.css">
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
            </form>
        </section>

        <?php if (isset($resultado)): ?>
            <section class="container">
                <h2>Resultado del Informe</h2>
                <?= $resultado ?>
            </section>
        <?php endif; ?>
    </main>
    <footer>
        <p>&copy; 2024 Gestión de Horas Trabajadas</p>
    </footer>
</body>
</html>
