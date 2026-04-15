<?php
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";

header('Content-Type: application/json; charset=utf-8');

$id_bien = isset($_GET['id_bien']) ? preg_replace('/[^0-9]/', '', $_GET['id_bien']) : '';
if ($id_bien === '') { echo json_encode(array('data' => array())); exit; }

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

// LEFT JOIN permite que docs nuevos del catálogo aparezcan automáticamente
// como 'Pendiente' en bienes existentes (sin pre-materializar).
$res = db_query($link, "SELECT d.id AS id_documento, d.nombre, d.descripcion, d.orden,
                               COALESCE(sbd.estado, 'Pendiente') AS estado,
                               sbd.fecha_entrega, sbd.notas, sbd.updated_at
                        FROM documentos_siniestro d
                        LEFT JOIN siniestros_bienes_documentos sbd
                               ON sbd.id_documento = d.id AND sbd.id_bien = '$id_bien'
                        WHERE d.activo = TRUE
                        ORDER BY d.orden, d.id");
$data = array();
while ($row = db_fetch_object($res)) {
    $data[] = array(
        'id_documento'  => $row->id_documento,
        'nombre'        => $row->nombre,
        'descripcion'   => $row->descripcion,
        'estado'        => $row->estado,
        'fecha_entrega' => $row->fecha_entrega,
        'notas'         => $row->notas,
        'updated_at'    => $row->updated_at
    );
}
db_close($link);
echo json_encode(array('data' => $data));
?>
