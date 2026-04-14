<?php
if (!isset($_SESSION)) {
    session_start();
}
require_once "/home/gestio10/public_html/backend/config.php";

function estandariza_info($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

$id_poliza = estandariza_info($_REQUEST["id_poliza"] ?? '');

$data = array();
if ($id_poliza !== '') {
    $sql = "SELECT i.numero_item, i.materia_asegurada, i.patente_ubicacion
            FROM items i
            JOIN polizas_2 p ON p.numero_poliza = i.numero_poliza
            WHERE p.id = '$id_poliza'
            ORDER BY i.numero_item";
    $resultado = db_query($link, $sql);
    while ($row = db_fetch_object($resultado)) {
        $data[] = array(
            "numero_item"       => $row->numero_item,
            "materia_asegurada" => $row->materia_asegurada,
            "patente_ubicacion" => $row->patente_ubicacion
        );
    }
}
db_close($link);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array("data" => $data));
?>
