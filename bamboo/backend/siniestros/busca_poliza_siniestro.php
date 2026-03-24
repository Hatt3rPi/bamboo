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

$busqueda = estandariza_info($_POST["busqueda"] ?? '');

$sql = "SELECT p.id, p.numero_poliza, p.ramo, p.compania,
            p.vigencia_inicial, p.vigencia_final, p.estado,
            CONCAT_WS(' ', c.nombre_cliente, c.apellido_paterno, c.apellido_materno) AS nombre_cliente,
            c.rut_sin_dv, c.dv, c.telefono, c.correo
        FROM polizas_2 p
        LEFT JOIN clientes c ON p.rut_proponente = c.rut_sin_dv
        WHERE p.numero_poliza LIKE '%$busqueda%'
           OR c.rut_sin_dv LIKE '%$busqueda%'
        ORDER BY p.vigencia_final DESC
        LIMIT 20";

$resultado = db_query($link, $sql);
$data = array();
while ($row = db_fetch_object($resultado)) {
    $data[] = array(
        "id"             => $row->id,
        "numero_poliza"  => $row->numero_poliza,
        "ramo"           => $row->ramo,
        "compania"       => $row->compania,
        "vigencia_inicial" => $row->vigencia_inicial,
        "vigencia_final"   => $row->vigencia_final,
        "estado"           => $row->estado,
        "nombre_cliente"   => $row->nombre_cliente,
        "rut_sin_dv"       => $row->rut_sin_dv,
        "dv"               => $row->dv,
        "telefono"         => $row->telefono,
        "correo"           => $row->correo
    );
}
db_close($link);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array("data" => $data));
?>
