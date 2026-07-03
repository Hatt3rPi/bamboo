<?php
// Exporta a Excel el historial (bitácora) de UN siniestro, ordenado por fecha (cronológico).
// Requerimiento Adriana (jun-2026): reconstituir la escena de un siniestro por fecha para
// enviar a la compañía. Fuente: tabla siniestros_bitacora (misma que busqueda_bitacora_siniestro.php).
if (!isset($_SESSION)) { session_start(); }
require "/home/gestio10/public_html/vendor/autoload.php";
require_once "/home/gestio10/public_html/backend/config.php";

use PhpOffice\PhpSpreadsheet\{Spreadsheet, IOFactory};

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

$id_siniestro = (int) ($_REQUEST["id_siniestro"] ?? 0);
if ($id_siniestro <= 0) {
    header("Location: /bamboo/listado_siniestros.php");
    exit;
}

// Encabezado del siniestro (defensivo: si el esquema difiere, se usa el id sin romper).
$num_siniestro = (string) $id_siniestro;
$numero_poliza = '';
$nombre_asegurado = '';
$rs = @db_query($link, "SELECT numero_siniestro, numero_poliza, nombre_asegurado
                        FROM siniestros WHERE id = '$id_siniestro'");
if ($rs && ($r = db_fetch_object($rs))) {
    if (!empty($r->numero_siniestro)) $num_siniestro = $r->numero_siniestro;
    $numero_poliza    = $r->numero_poliza ?? '';
    $nombre_asegurado = $r->nombre_asegurado ?? '';
}

// Bitácora del siniestro, en orden cronológico (por fecha).
$sql = "SELECT TO_CHAR(\"timestamp\", 'YYYY-MM-DD HH24:MI:SS') AS fecha,
               usuario, estado_anterior, estado_nuevo, motivo
        FROM siniestros_bitacora
        WHERE id_siniestro = '$id_siniestro'
        ORDER BY \"timestamp\" ASC";
$resultado = db_query($link, $sql);

$excel = new Spreadsheet();
$hoja = $excel->getActiveSheet();
$hoja->setTitle('Historial');

// Bloque de contexto.
$hoja->setCellValue('A1', 'Historial del siniestro');
$hoja->setCellValue('A2', 'N° Siniestro');   $hoja->setCellValue('B2', $num_siniestro);
$hoja->setCellValue('A3', 'N° Póliza');       $hoja->setCellValue('B3', $numero_poliza);
$hoja->setCellValue('A4', 'Asegurado');       $hoja->setCellValue('B4', $nombre_asegurado);

// Encabezados de la tabla.
$hoja->setCellValue('A6', 'Fecha');
$hoja->setCellValue('B6', 'Usuario');
$hoja->setCellValue('C6', 'Estado anterior');
$hoja->setCellValue('D6', 'Estado nuevo');
$hoja->setCellValue('E6', 'Motivo / detalle');

$fila = 7;
if ($resultado) {
    while ($row = db_fetch_object($resultado)) {
        $hoja->setCellValue('A'.$fila, $row->fecha);
        $hoja->setCellValue('B'.$fila, $row->usuario);
        $hoja->setCellValue('C'.$fila, $row->estado_anterior);
        $hoja->setCellValue('D'.$fila, $row->estado_nuevo);
        $hoja->setCellValue('E'.$fila, $row->motivo);
        $fila++;
    }
}
foreach (['A','B','C','D','E'] as $col) { $hoja->getColumnDimension($col)->setAutoSize(true); }
if ($fila > 7) { $hoja->setAutoFilter('A6:E'.($fila-1)); }

$fecha = new DateTime(date("Y-m-d H:i:sP"), new DateTimeZone('America/Santiago'));
$nombre_archivo = 'Historial_siniestro_'.preg_replace('/[^A-Za-z0-9_-]/', '', $num_siniestro)
                . '_' . date_format($fecha, 'd-m-Y_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$nombre_archivo.'"');
header('Cache-Control: max-age=0');
db_close($link);
$writer = IOFactory::createWriter($excel, 'Xlsx');
$writer->save('php://output');
exit;
?>
