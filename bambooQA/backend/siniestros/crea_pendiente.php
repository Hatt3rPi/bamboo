<?php
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";

function estandariza_info($d) { return htmlspecialchars(stripslashes(trim($d))); }
function sqlesc($v) { return str_replace("'", "''", $v); }

header('Content-Type: application/json; charset=utf-8');

$id_siniestro = estandariza_info($_POST['id_siniestro'] ?? '');
$id_bien      = estandariza_info($_POST['id_bien']      ?? '');
$responsable  = estandariza_info($_POST['responsable']  ?? '');
$descripcion  = estandariza_info($_POST['descripcion']  ?? '');
$fecha_entrega = estandariza_info($_POST['fecha_entrega'] ?? '');
$notas        = estandariza_info($_POST['notas']        ?? '');
$usuario      = $_SESSION['username'] ?? '';

$ok = false; $mensaje = ''; $id_nuevo = null;

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

if (!ctype_digit($id_siniestro))                                 { $mensaje = 'id_siniestro inválido.'; }
elseif (!in_array($responsable, array('Cliente','Liquidador','Compañía'))) { $mensaje = 'responsable inválido.'; }
elseif ($descripcion === '')                                     { $mensaje = 'descripcion es obligatoria.'; }
else {
    $d  = sqlesc($descripcion);
    $n  = sqlesc($notas);
    $r  = sqlesc($responsable);
    $fe = ($fecha_entrega !== '') ? "NULLIF('$fecha_entrega','')::date" : "NULL";
    $ib = (ctype_digit($id_bien) && $id_bien !== '') ? "'$id_bien'" : "NULL";
    db_query($link, "INSERT INTO siniestros_pendientes
                        (id_siniestro, id_bien, responsable, descripcion, fecha_entrega, notas, usuario_creacion)
                     VALUES
                        ('$id_siniestro', $ib, '$r', '$d', $fe, '$n', '" . sqlesc($usuario) . "')");
    $rs = db_query($link, "SELECT currval('siniestros_pendientes_id_seq') AS id");
    while ($fila = db_fetch_object($rs)) { $id_nuevo = $fila->id; }
    if ($id_nuevo) {
        db_query($link, "INSERT INTO siniestros_pendientes_bitacora
                            (id_pendiente, accion, estado_anterior, estado_nuevo,
                             responsable_anterior, responsable_nuevo, usuario)
                         VALUES
                            ('$id_nuevo', 'Creación', NULL, 'Pendiente', NULL, '$r', '" . sqlesc($usuario) . "')");
    }
    db_query($link, "SELECT trazabilidad('" . sqlesc($usuario) . "', 'Creación pendiente siniestro',
                        'Siniestro: $id_siniestro, resp: $r', 'siniestros_pendientes',
                        '$id_siniestro', '{$_SERVER['PHP_SELF']}')");
    $ok = true; $mensaje = 'Pendiente creado.';
}

db_close($link);
echo json_encode(array('ok' => $ok, 'mensaje' => $mensaje, 'id' => $id_nuevo));
?>
