<?php
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";
header('Content-Type: application/json; charset=utf-8');

function sqlesc($v) { return str_replace("'", "''", $v); }

$id      = $_POST['id'] ?? '';
$usuario = $_SESSION['username'] ?? '';

if (!ctype_digit($id)) {
    echo json_encode(array('ok' => false, 'mensaje' => 'id inválido'));
    exit;
}

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);
db_query($link, "DELETE FROM email_templates WHERE id='$id'");
$u = sqlesc($usuario);
db_query($link, "SELECT trazabilidad('$u', 'Eliminar email template', 'id: $id',
                    'email_templates', '$id', '{$_SERVER['PHP_SELF']}')");
db_close($link);

echo json_encode(array('ok' => true, 'mensaje' => 'Eliminada.'));
?>
