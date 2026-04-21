<?php
/**
 * Lista de pendientes de siniestros con alarma vencida (pasó el plazo).
 * Reunión 21-abr-2026: widget en home.
 *
 * Regla: (fecha_creacion + dias_alarma * INTERVAL '1 day') < NOW() AND estado='Pendiente'.
 *
 * Retorno JSON: data = [ { id, id_siniestro, responsable, descripcion, dias_alarma,
 *   fecha_creacion, dias_atraso, numero_siniestro, numero_poliza, nombre_asegurado } ]
 */
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";

header('Content-Type: application/json; charset=utf-8');

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

$res = db_query($link, "SELECT p.id, p.id_siniestro, p.responsable, p.descripcion,
                               p.dias_alarma, p.fecha_creacion,
                               GREATEST(0, FLOOR(EXTRACT(EPOCH FROM (NOW() - (p.fecha_creacion + (p.dias_alarma * INTERVAL '1 day')))) / 86400))::int AS dias_atraso,
                               COALESCE(s.numero_siniestro,'') AS numero_siniestro,
                               s.numero_poliza, s.nombre_asegurado, COALESCE(s.ramo,'') AS ramo
                        FROM siniestros_pendientes p
                        LEFT JOIN siniestros s ON s.id = p.id_siniestro
                        WHERE p.estado = 'Pendiente'
                          AND (p.fecha_creacion + (p.dias_alarma * INTERVAL '1 day')) < NOW()
                          AND COALESCE(s.estado,'') <> 'Eliminado'
                        ORDER BY (p.fecha_creacion + (p.dias_alarma * INTERVAL '1 day')) ASC
                        LIMIT 50");
$data = array();
while ($row = db_fetch_object($res)) {
    $data[] = array(
        'id'                 => $row->id,
        'id_siniestro'       => $row->id_siniestro,
        'responsable'        => $row->responsable,
        'descripcion'        => $row->descripcion,
        'dias_alarma'        => (int)$row->dias_alarma,
        'dias_atraso'        => (int)$row->dias_atraso,
        'fecha_creacion'     => $row->fecha_creacion,
        'numero_siniestro'   => $row->numero_siniestro,
        'numero_poliza'      => $row->numero_poliza,
        'nombre_asegurado'   => $row->nombre_asegurado,
        'ramo'               => $row->ramo
    );
}
db_close($link);
echo json_encode(array('data' => $data));
?>
