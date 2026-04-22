<?php
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";

header('Content-Type: application/json; charset=utf-8');

$id_siniestro = isset($_GET['id_siniestro']) ? preg_replace('/[^0-9]/', '', $_GET['id_siniestro']) : '';
if ($id_siniestro === '') { echo json_encode(array('data' => array())); exit; }

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

$res = db_query($link, "SELECT b.id, b.id_siniestro, b.tipo, b.categoria, b.descripcion, b.estado,
                               b.observaciones, b.fecha_alarma,
                               COALESCE(b.direccion,'')     AS direccion,
                               COALESCE(b.item_afectado,'') AS item_afectado,
                               b.patente, b.marca, b.modelo, b.anio_vehiculo,
                               b.taller_nombre, b.taller_telefono,
                               b.created_at, b.updated_at,
                               COALESCE(d.total, 0) AS total_docs,
                               COALESCE(d.pendientes, 0) AS pendientes,
                               COALESCE(d.entregados, 0) AS entregados
                        FROM siniestros_bienes_afectados b
                        LEFT JOIN (
                            SELECT sbd.id_bien,
                                   COUNT(*) AS total,
                                   SUM(CASE WHEN sbd.estado = 'Pendiente' THEN 1 ELSE 0 END) AS pendientes,
                                   SUM(CASE WHEN sbd.estado = 'Entregado' THEN 1 ELSE 0 END) AS entregados
                            FROM siniestros_bienes_documentos sbd
                            GROUP BY sbd.id_bien
                        ) d ON d.id_bien = b.id
                        WHERE b.id_siniestro = '$id_siniestro'
                        ORDER BY b.tipo, b.id");
$data = array();
while ($row = db_fetch_object($res)) {
    $data[] = array(
        'id'              => $row->id,
        'id_siniestro'    => $row->id_siniestro,
        'tipo'            => $row->tipo,
        'categoria'       => $row->categoria,
        'descripcion'     => $row->descripcion,
        'direccion'       => $row->direccion,
        'item_afectado'   => $row->item_afectado,
        'estado'          => $row->estado,
        'observaciones'   => $row->observaciones,
        'fecha_alarma'    => $row->fecha_alarma,
        'patente'         => $row->patente,
        'marca'           => $row->marca,
        'modelo'          => $row->modelo,
        'anio_vehiculo'   => $row->anio_vehiculo,
        'taller_nombre'   => $row->taller_nombre,
        'taller_telefono' => $row->taller_telefono,
        'total_docs'      => (int)$row->total_docs,
        'pendientes'      => (int)$row->pendientes,
        'entregados'      => (int)$row->entregados,
        'created_at'      => $row->created_at,
        'updated_at'      => $row->updated_at
    );
}
db_close($link);
echo json_encode(array('data' => $data));
?>
