<?php
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";

header('Content-Type: application/json; charset=utf-8');

$incluir_inactivos = isset($_GET['incluir_inactivos']) && $_GET['incluir_inactivos'] == '1';

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

$where = $incluir_inactivos ? '' : 'WHERE activo = TRUE';
$res = db_query($link, "SELECT id, nombre, descripcion, orden, activo, created_at
                        FROM documentos_siniestro
                        $where
                        ORDER BY orden, id");
$data = array();
while ($row = db_fetch_object($res)) {
    $data[] = array(
        'id'          => $row->id,
        'nombre'      => $row->nombre,
        'descripcion' => $row->descripcion,
        'orden'       => $row->orden,
        'activo'      => ($row->activo === true || $row->activo === 't' || $row->activo == 1) ? 1 : 0,
        'created_at'  => $row->created_at
    );
}
db_close($link);
echo json_encode(array('data' => $data));
?>
