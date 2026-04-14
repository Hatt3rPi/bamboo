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

$id_siniestro = estandariza_info($_REQUEST["id_siniestro"] ?? '');

$data = array();
if ($id_siniestro !== '') {
    $sql = "SELECT TO_CHAR(\"timestamp\", 'YYYY-MM-DD HH24:MI:SS') AS fecha,
                   usuario, estado_anterior, estado_nuevo, motivo
            FROM siniestros_bitacora
            WHERE id_siniestro = '$id_siniestro'
            ORDER BY \"timestamp\" DESC";
    $resultado = db_query($link, $sql);
    while ($row = db_fetch_object($resultado)) {
        $data[] = array(
            "fecha"           => $row->fecha,
            "usuario"         => $row->usuario,
            "estado_anterior" => $row->estado_anterior,
            "estado_nuevo"    => $row->estado_nuevo,
            "motivo"          => $row->motivo
        );
    }
}
db_close($link);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array("data" => $data));
?>
