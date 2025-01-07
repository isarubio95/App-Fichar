<?php
require_once '../conexion.php';
require_once '../vendor/autoload.php'; // Incluye PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Verifica que los datos se pasen correctamente por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_empleado'], $_POST['tipo_resumen'])) {
    $id_empleado = (int)$_POST['id_empleado'];
    $tipo_resumen = $_POST['tipo_resumen'];

    // Inicia el buffer de salida para capturar el contenido generado
    ob_start();

    // Incluye el archivo de informes y genera el contenido HTML
    include 'informes.php';

    // Captura el contenido HTML generado
    $contenido_html_completo = ob_get_clean();

    // Filtrar el contenido relevante usando delimitadores únicos
    $inicio_informe = strpos($contenido_html_completo, '<!-- INICIO_INFORME -->');
    $fin_informe = strpos($contenido_html_completo, '<!-- FIN_INFORME -->') + strlen('<!-- FIN_INFORME -->');

    if ($inicio_informe !== false && $fin_informe !== false) {
        $contenido_html = substr($contenido_html_completo, $inicio_informe, $fin_informe - $inicio_informe);
    } else {
        die('No se pudo encontrar el contenido del informe.');
    }

    // Crear una instancia de PhpSpreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Informe de Horas Trabajadas');

    // Configurar la hoja para tamaño DIN A4
    $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
    $sheet->getPageSetup()->setFitToWidth(1); // Ajustar ancho a una página
    $sheet->getPageSetup()->setFitToHeight(0); // Sin límite de altura

    // Estilo global para todas las celdas
    $spreadsheet->getDefaultStyle()->applyFromArray([
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ]);

    // Convertir contenido HTML a filas y celdas en Excel
    $dom = new DOMDocument('1.0', 'UTF-8');
    @$dom->loadHTML(mb_convert_encoding($contenido_html, 'HTML-ENTITIES', 'UTF-8'));
    $tables = $dom->getElementsByTagName('table');
    $headings = $dom->getElementsByTagName('h2'); // Obtener los títulos principales
    $subheadings = $dom->getElementsByTagName('h3'); // Subtítulos (meses)

    // Ajustar la altura de todas las filas
    foreach ($sheet->getRowIterator() as $row) {
    $sheet->getRowDimension($row->getRowIndex())->setRowHeight(30); 
    }

    // Agregar el título del informe
    $tituloPrincipal = $headings->length > 0 ? trim($headings->item(0)->nodeValue) : "Informe de Horas Trabajadas";
    $sheet->setCellValue("A1", $tituloPrincipal);
    $sheet->mergeCells("A1:G1");
    $sheet->getStyle("A1")->applyFromArray([
        'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '000000']],
    ]);

    // Agregar el primer subtítulo (<h3>) en la fila 2
    if ($subheadings->length > 0) {
        $primerSubheading = trim($subheadings->item(0)->nodeValue);
        $sheet->setCellValue("A2", $primerSubheading);
        $sheet->mergeCells("A2:G2");
        $sheet->getStyle("A2")->applyFromArray([
            'font' => ['size' => 14, 'color' => ['rgb' => '000000']], // Color negro
        ]);
        $sheet->getRowDimension(2)->setRowHeight(30); // Ajustar altura de la fila 2
    }

    $rowIndex = 3; // Inicia en la fila 3 para dejar espacio debajo del título principal
    $subheadingIndex = 1;  // Iterador para los subtítulos

    foreach ($tables as $table) {
        $startRow = $rowIndex; // Guarda la fila inicial de la tabla para aplicar bordes externos
        $startColumn = 'A';   // Comienza en la primera columna

        // Insertar el subtítulo correspondiente antes de cada tabla
        if ($subheadingIndex < $subheadings->length) {
            $subheadingText = trim($subheadings->item($subheadingIndex)->nodeValue);
            $sheet->setCellValue("A$rowIndex", $subheadingText);
            $sheet->mergeCells("A$rowIndex:G$rowIndex");
            $sheet->getStyle("A$rowIndex")->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '4CAF50']],
            ]);
            $sheet->getRowDimension($rowIndex)->setRowHeight(30); // Ajustar altura de la fila de los subtítulos
            $rowIndex++;
            $subheadingIndex++; // Avanzar al siguiente subtítulo
        }
        
        // Recorrer las filas de la tabla
        $firstRow = true; // Indicador para la primera fila
        foreach ($table->getElementsByTagName('tr') as $row) {      
            $colIndex = 'A'; // Comienza en la primera columna de Excel
            // Determina si es la primera fila
        if ($firstRow) {
            $sheet->getRowDimension($rowIndex)->setRowHeight(30); // Altura mayor para la primera fila
            $firstRow = false; // Ya no es la primera fila
        } else {
            $sheet->getRowDimension($rowIndex)->setRowHeight(24); // Altura estándar para el resto
        }
            foreach ($row->getElementsByTagName('th') as $cell) {
                $sheet->setCellValue($colIndex . $rowIndex, trim($cell->nodeValue));
                $sheet->getStyle($colIndex . $rowIndex)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4CAF50'],
                    ]
                ]);
                $colIndex++;
            }
            foreach ($row->getElementsByTagName('td') as $cell) {
                $text = mb_convert_encoding(trim($cell->nodeValue), 'UTF-8', 'auto');
                $sheet->setCellValue($colIndex . $rowIndex, $text);
                $sheet->getStyle($colIndex . $rowIndex)->applyFromArray([
                    'font' => ['color' => ['rgb' => '000000']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F9F9F9'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'DDDDDD'],
                        ],
                    ],
                ]);
                $colIndex++;
            }
            $rowIndex++;
        }
        // Añadir un salto de página después de cada tabla
        $sheet->setBreak("A$rowIndex", \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::BREAK_ROW);
        $rowIndex += 2; // Dejar dos filas vacías entre tablas
    }

    // Ajustar el ancho de las columnas manualmente para hacerlas más estrechas
    $columnWidths = [
        'A' => 15, // Fecha
        'B' => 10, // Día
        'C' => 15, // Ent. Mañana
        'D' => 15, // Sal. Mañana
        'E' => 15, // Ent. Tarde
        'F' => 15, // Sal. Tarde
        'G' => 20, // Horas Trabajadas
    ];
    foreach ($columnWidths as $col => $width) {
        $sheet->getColumnDimension($col)->setWidth($width);
    }

    // Salida del Excel
    $writer = new Xlsx($spreadsheet);
    $nombreArchivo = "Informe_Horas_Trabajadas.xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"$nombreArchivo\"");
    header('Cache-Control: max-age=0');

    $writer->save('php://output');
    exit;
} else {
    die('Datos insuficientes para generar el informe.');
}
