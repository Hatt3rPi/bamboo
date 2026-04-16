<?php
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";

header('Content-Type: application/json; charset=utf-8');

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

// Filtros opcionales vía GET
$filtro_estado = isset($_GET['estado']) ? preg_replace("/[^A-Za-zÁÉÍÓÚáéíóúñÑ ]/u", '', $_GET['estado']) : '';
$solo_con_alarma_proxima = isset($_GET['alarma_proxima']) && $_GET['alarma_proxima'] == '1';
$filtro_id_siniestro = isset($_GET['id_siniestro']) ? preg_replace('/[^0-9]/', '', $_GET['id_siniestro']) : '';

$where = "WHERE 1=1";
if ($filtro_estado !== '') {
    $e = str_replace("'", "''", $filtro_estado);
    $where .= " AND b.estado = '$e'";
}
if ($solo_con_alarma_proxima) {
    $where .= " AND b.fecha_alarma IS NOT NULL AND b.fecha_alarma <= (CURRENT_DATE + INTERVAL '7 days')";
}
if ($filtro_id_siniestro !== '') {
    $where .= " AND b.id_siniestro = '$filtro_id_siniestro'";
}

$res = db_query($link, "SELECT b.id, b.id_siniestro, b.tipo, b.categoria, b.descripcion, b.estado,
                               b.observaciones, b.fecha_alarma,
                               b.patente, b.marca, b.modelo, b.anio_vehiculo,
                               s.numero_siniestro, s.numero_poliza, s.ramo,
                               COALESCE(d.total, 0) AS total_docs,
                               COALESCE(d.pendientes, 0) AS pendientes,
                               COALESCE(d.entregados, 0) AS entregados,
                               d.docs_json
                        FROM siniestros_bienes_afectados b
                        LEFT JOIN siniestros s ON s.id = b.id_siniestro
                        LEFT JOIN (
                            SELECT sbd.id_bien,
                                   COUNT(*) AS total,
                                   SUM(CASE WHEN sbd.estado = 'Pendiente' THEN 1 ELSE 0 END) AS pendientes,
                                   SUM(CASE WHEN sbd.estado = 'Entregado' THEN 1 ELSE 0 END) AS entregados,
                                   json_agg(json_build_object(
                                       'nombre', ds.nombre,
                                       'estado', sbd.estado,
                                       'fecha_entrega', sbd.fecha_entrega::text
                                   ) ORDER BY ds.orden, ds.nombre) AS docs_json
                            FROM siniestros_bienes_documentos sbd
                            LEFT JOIN documentos_siniestro ds ON ds.id = sbd.id_documento
                            GROUP BY sbd.id_bien
                        ) d ON d.id_bien = b.id
                        $where
                        ORDER BY b.fecha_alarma NULLS LAST, b.updated_at DESC");
$data = array();
while ($row = db_fetch_object($res)) {
    $data[] = array(
        'id'                => $row->id,
        'id_siniestro'      => $row->id_siniestro,
        'numero_siniestro'  => $row->numero_siniestro,
        'numero_poliza'     => $row->numero_poliza,
        'ramo'              => $row->ramo,
        'tipo'              => $row->tipo,
        'categoria'         => $row->categoria,
        'descripcion'       => $row->descripcion,
        'estado'            => $row->estado,
        'observaciones'     => $row->observaciones,
        'fecha_alarma'      => $row->fecha_alarma,
        'patente'           => $row->patente,
        'marca'             => $row->marca,
        'modelo'            => $row->modelo,
        'anio_vehiculo'     => $row->anio_vehiculo,
        'total_docs'        => (int)$row->total_docs,
        'pendientes'        => (int)$row->pendientes,
        'entregados'        => (int)$row->entregados,
        'docs'              => $row->docs_json ? json_decode($row->docs_json, true) : array()
    );
}
db_close($link);
echo json_encode(array('data' => $data));
?>
