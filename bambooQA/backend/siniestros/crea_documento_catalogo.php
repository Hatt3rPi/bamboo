<?php
if (!isset($_SESSION)) {
    session_start();
}
require_once "/home/gestio10/public_html/backend/config.php";

function estandariza_info($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
// Escape SQL literal (mitigación para comillas en texto libre)
function sqlesc($v) {
    return str_replace("'", "''", $v);
}

header('Content-Type: application/json; charset=utf-8');

$accion       = estandariza_info($_POST["accion"]        ?? '');
$id           = estandariza_info($_POST["id"]            ?? '');
$nombre       = estandariza_info($_POST["nombre"]        ?? '');
$descripcion  = estandariza_info($_POST["descripcion"]   ?? '');
$orden        = estandariza_info($_POST["orden"]         ?? '0');
$usuario      = $_SESSION['username'] ?? '';

$ok = false; $mensaje = '';

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

switch ($accion) {
    case 'crear_documento':
        if ($nombre === '') { $mensaje = 'El nombre es obligatorio.'; break; }
        $n = sqlesc($nombre); $d = sqlesc($descripcion);
        $o = ctype_digit($orden) ? (int)$orden : 0;
        db_query($link, "INSERT INTO documentos_siniestro (nombre, descripcion, orden) VALUES ('$n', '$d', $o)");
        db_query($link, "SELECT trazabilidad('$usuario', 'Creación documento catálogo', 'Nombre: $n', 'documentos_siniestro', '$n', '{$_SERVER['PHP_SELF']}')");
        $ok = true; $mensaje = 'Documento creado.';
        break;

    case 'actualizar_documento':
        if ($id === '' || !ctype_digit($id)) { $mensaje = 'ID inválido.'; break; }
        if ($nombre === '') { $mensaje = 'El nombre es obligatorio.'; break; }
        $n = sqlesc($nombre); $d = sqlesc($descripcion);
        $o = ctype_digit($orden) ? (int)$orden : 0;
        db_query($link, "UPDATE documentos_siniestro SET nombre = '$n', descripcion = '$d', orden = $o WHERE id = '$id'");
        db_query($link, "SELECT trazabilidad('$usuario', 'Actualización documento catálogo', 'ID: $id', 'documentos_siniestro', '$id', '{$_SERVER['PHP_SELF']}')");
        $ok = true; $mensaje = 'Documento actualizado.';
        break;

    case 'desactivar_documento':
        if ($id === '' || !ctype_digit($id)) { $mensaje = 'ID inválido.'; break; }
        db_query($link, "UPDATE documentos_siniestro SET activo = FALSE WHERE id = '$id'");
        db_query($link, "SELECT trazabilidad('$usuario', 'Desactivación documento catálogo', 'ID: $id', 'documentos_siniestro', '$id', '{$_SERVER['PHP_SELF']}')");
        $ok = true; $mensaje = 'Documento desactivado.';
        break;

    case 'activar_documento':
        if ($id === '' || !ctype_digit($id)) { $mensaje = 'ID inválido.'; break; }
        db_query($link, "UPDATE documentos_siniestro SET activo = TRUE WHERE id = '$id'");
        db_query($link, "SELECT trazabilidad('$usuario', 'Activación documento catálogo', 'ID: $id', 'documentos_siniestro', '$id', '{$_SERVER['PHP_SELF']}')");
        $ok = true; $mensaje = 'Documento activado.';
        break;

    default:
        $mensaje = 'Acción no reconocida.';
}

db_close($link);
echo json_encode(array("ok" => $ok, "mensaje" => $mensaje));
?>
