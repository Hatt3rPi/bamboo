<?php
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";

header('Content-Type: application/json; charset=utf-8');

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

// Filtros opcionales vía GET
$filtro_estado = isset($_GET['estado']) ? preg_replace("/[^A-Za-zÁÉÍÓÚáéíóúñÑ ]/u", '', $_GET['estado']) : '';
$solo_con_alarma_proxima = isset($_GET['alarma_proxima']) && $_GET['alarma_proxima'] == '1';

$where = "WHERE 1=1";
if ($filtro_estado !== '') {
    $e = str_replace("'", "''", $filtro_estado);
    $where .= " AND b.estado = '$e'";
}
if ($solo_con_alarma_proxima) {
    $where .= " AND b.fecha_alarma IS NOT NULL AND b.fecha_alarma <= (CURRENT_DATE + INTERVAL '7 days')";
}

$res = db_query($link, "SELECT b.id, b.id_siniestro, b.tipo, b.descripcion, b.estado,
                               b.observaciones, b.fecha_alarma,
                               s.numero_siniestro, s.numero_poliza, s.ramo,
                               COALESCE(d.total, 0) AS total_docs,
                               COALESCE(d.pendientes, 0) AS pendientes,
                               COALESCE(d.entregados, 0) AS entregados
                        FROM siniestros_bienes_afectados b
                        LEFT JOIN siniestros s ON s.id = b.id_siniestro
                        LEFT JOIN (
                            SELECT sbd.id_bien,
                                   COUNT(*) AS total,
                                   SUM(CASE WHEN sbd.estado = 'Pendiente' THEN 1 ELSE 0 END) AS pendientes,
                                   SUM(CASE WHEN sbd.estado = 'Entregado' THEN 1 ELSE 0 END) AS entregados
                            FROM siniestros_bienes_documentos sbd
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
        'descripcion'       => $row->descripcion,
        'estado'            => $row->estado,
        'observaciones'     => $row->observaciones,
        'fecha_alarma'      => $row->fecha_alarma,
        'total_docs'        => (int)$row->total_docs,
        'pendientes'        => (int)$row->pendientes,
        'entregados'        => (int)$row->entregados
    );
}
db_close($link);
echo json_encode(array('data' => $data));
?>
