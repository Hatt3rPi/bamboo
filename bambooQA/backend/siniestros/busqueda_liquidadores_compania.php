<?php
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";

header('Content-Type: application/json; charset=utf-8');

// Recibe id_siniestro, resuelve la compañía vía polizas y devuelve los liquidadores
// previamente registrados para esa compañía. Útil para el dropdown del modal de
// "marcar Entregado" de la tarea compania_entrega_numero.
$id_siniestro = isset($_GET['id_siniestro']) ? preg_replace('/[^0-9]/', '', $_GET['id_siniestro']) : '';

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

$compania = '';
$data = array();

if ($id_siniestro !== '') {
    $rs = db_query($link, "SELECT COALESCE(p.compania, '') AS compania
                           FROM siniestros s
                           LEFT JOIN polizas_2 p ON p.id = s.id_poliza
                           WHERE s.id = '$id_siniestro'");
    while ($row = db_fetch_object($rs)) { $compania = trim($row->compania); }

    if ($compania !== '') {
        $c = str_replace("'", "''", $compania);
        $rs2 = db_query($link, "SELECT id, nombre, COALESCE(telefono,'') AS telefono,
                                       COALESCE(correo,'') AS correo
                                FROM liquidadores
                                WHERE compania = '$c'
                                ORDER BY nombre ASC");
        while ($row = db_fetch_object($rs2)) {
            $data[] = array(
                'id'       => (int)$row->id,
                'nombre'   => $row->nombre,
                'telefono' => $row->telefono,
                'correo'   => $row->correo
            );
        }
    }
}

db_close($link);
echo json_encode(array('compania' => $compania, 'data' => $data));
?>
