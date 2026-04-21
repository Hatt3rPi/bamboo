<?php
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";
header('Content-Type: application/json; charset=utf-8');

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

$modulo = isset($_GET['modulo']) ? preg_replace('/[^a-z_]/', '', strtolower($_GET['modulo'])) : '';
$where = '1=1';
if ($modulo !== '') {
    $where = "modulo = '$modulo'";
}

$res = db_query($link, "SELECT id, codigo, nombre, modulo, asunto, cuerpo_texto, cuerpo_html,
                               variables, activo, updated_at, updated_by
                        FROM email_templates
                        WHERE $where
                        ORDER BY modulo, nombre");
$data = array();
while ($row = db_fetch_object($res)) {
    $data[] = array(
        'id'           => $row->id,
        'codigo'       => $row->codigo,
        'nombre'       => $row->nombre,
        'modulo'       => $row->modulo,
        'asunto'       => $row->asunto,
        'cuerpo_texto' => $row->cuerpo_texto,
        'cuerpo_html'  => $row->cuerpo_html,
        'variables'    => json_decode($row->variables ?: '[]', true),
        'activo'       => ($row->activo === 't' || $row->activo === true || $row->activo === '1'),
        'updated_at'   => $row->updated_at,
        'updated_by'   => $row->updated_by
    );
}
db_close($link);
echo json_encode(array('data' => $data));
?>
