<?php
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";

header('Content-Type: application/json; charset=utf-8');

$id_siniestro = isset($_GET['id_siniestro']) ? preg_replace('/[^0-9]/', '', $_GET['id_siniestro']) : '';

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

$data = array();
if ($id_siniestro !== '') {
    $res = db_query($link, "SELECT p.id, p.id_siniestro, p.id_bien, p.responsable, p.descripcion,
                                   p.estado, p.fecha_creacion, p.fecha_entrega, p.notas, p.usuario_creacion,
                                   b.descripcion AS bien_descripcion, b.tipo AS bien_tipo
                            FROM siniestros_pendientes p
                            LEFT JOIN siniestros_bienes_afectados b ON b.id = p.id_bien
                            WHERE p.id_siniestro = '$id_siniestro'
                            ORDER BY
                                CASE p.estado WHEN 'Pendiente' THEN 0 WHEN 'Entregado' THEN 1 ELSE 2 END,
                                CASE p.responsable WHEN 'Cliente' THEN 0 WHEN 'Liquidador' THEN 1 ELSE 2 END,
                                p.fecha_creacion DESC");
    while ($row = db_fetch_object($res)) {
        $data[] = array(
            'id'                => $row->id,
            'id_siniestro'      => $row->id_siniestro,
            'id_bien'           => $row->id_bien,
            'responsable'       => $row->responsable,
            'descripcion'       => $row->descripcion,
            'estado'            => $row->estado,
            'fecha_creacion'    => $row->fecha_creacion,
            'fecha_entrega'     => $row->fecha_entrega,
            'notas'             => $row->notas,
            'usuario_creacion'  => $row->usuario_creacion,
            'bien_descripcion'  => $row->bien_descripcion,
            'bien_tipo'         => $row->bien_tipo
        );
    }
}

db_close($link);
echo json_encode(array('data' => $data));
?>
