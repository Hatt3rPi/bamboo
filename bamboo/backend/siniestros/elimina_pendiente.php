<?php
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";

function estandariza_info($d) { return htmlspecialchars(stripslashes(trim($d))); }
function sqlesc($v) { return str_replace("'", "''", $v); }

header('Content-Type: application/json; charset=utf-8');

$id      = estandariza_info($_POST['id'] ?? '');
$usuario = $_SESSION['username'] ?? '';

$ok = false; $mensaje = '';

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

if (!ctype_digit($id)) {
    $mensaje = 'id inválido.';
} else {
    db_query($link, "DELETE FROM siniestros_pendientes WHERE id='$id'");
    db_query($link, "SELECT trazabilidad('" . sqlesc($usuario) . "', 'Eliminación pendiente',
                        'ID: $id', 'siniestros_pendientes', '$id', '{$_SERVER['PHP_SELF']}')");
    $ok = true; $mensaje = 'Pendiente eliminado.';
}

db_close($link);
echo json_encode(array('ok' => $ok, 'mensaje' => $mensaje));
?>
