<?php
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";
require_once __DIR__ . "/render_template.php";
header('Content-Type: application/json; charset=utf-8');

$codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
$vars_json = $_POST['vars'] ?? '';
$vars = is_string($vars_json) ? (json_decode(stripslashes($vars_json), true) ?: array()) : array();

if ($codigo === '') {
    echo json_encode(array('ok' => false, 'mensaje' => 'Falta código.'));
    exit;
}

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

$rendered = render_email_template($link, $codigo, $vars);
db_close($link);

if ($rendered === null) {
    echo json_encode(array('ok' => false, 'mensaje' => 'Plantilla no encontrada o inactiva.'));
    exit;
}
echo json_encode(array('ok' => true) + $rendered);
?>
