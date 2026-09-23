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
// Pendiente anterior a cerrar al crear este (Adriana 22-sep: al pasar al siguiente
// pendiente, el anterior debe quedar cerrado). Se cierra sin promover la cadena
// automática: quien agrega el pendiente manual está conduciendo el flujo a mano.
$cerrar_anterior_id = estandariza_info($_POST['cerrar_anterior_id'] ?? '');
$usuario      = $_SESSION['username'] ?? '';

$ok = false; $mensaje = ''; $id_nuevo = null;

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

if (!ctype_digit($id_siniestro))                                 { $mensaje = 'id_siniestro inválido.'; }
elseif (!in_array($responsable, array('Cliente','Liquidador','Compañía','Taller','Usuario'))) { $mensaje = 'responsable inválido.'; }
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
    if ($id_nuevo && ctype_digit($cerrar_anterior_id)) {
        $rs_ant = db_query($link, "UPDATE siniestros_pendientes
                                   SET estado='Entregado', fecha_entrega=NOW(), updated_at=NOW()
                                   WHERE id='$cerrar_anterior_id' AND id_siniestro='$id_siniestro'
                                     AND estado='Pendiente'
                                   RETURNING id");
        if ($rs_ant && db_fetch_object($rs_ant)) {
            db_query($link, "INSERT INTO siniestros_pendientes_bitacora
                                (id_pendiente, accion, estado_anterior, estado_nuevo, usuario)
                             VALUES
                                ('$cerrar_anterior_id', 'Cerrado al crear pendiente siguiente', 'Pendiente', 'Entregado', '" . sqlesc($usuario) . "')");
        }
    }
    $ok = true; $mensaje = 'Pendiente creado.';
}

db_close($link);
echo json_encode(array('ok' => $ok, 'mensaje' => $mensaje, 'id' => $id_nuevo));
?>
