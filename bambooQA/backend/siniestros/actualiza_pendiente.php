<?php
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";

function estandariza_info($d) { return htmlspecialchars(stripslashes(trim($d))); }
function sqlesc($v) { return str_replace("'", "''", $v); }

header('Content-Type: application/json; charset=utf-8');

$id            = estandariza_info($_POST['id']            ?? '');
$responsable   = estandariza_info($_POST['responsable']   ?? '');
$descripcion   = estandariza_info($_POST['descripcion']   ?? '');
$estado        = estandariza_info($_POST['estado']        ?? '');
$fecha_entrega = estandariza_info($_POST['fecha_entrega'] ?? '');
$notas         = estandariza_info($_POST['notas']         ?? '');
$usuario       = $_SESSION['username'] ?? '';

$ok = false; $mensaje = '';
$cliente_completo = false;
$liquidador = array('nombre' => '', 'correo' => '', 'numero_siniestro' => '', 'numero_poliza' => '', 'nombre_asegurado' => '');

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

if (!ctype_digit($id))                                                      { $mensaje = 'id inválido.'; }
elseif (!in_array($responsable, array('Cliente','Liquidador','Compañía'))) { $mensaje = 'responsable inválido.'; }
elseif ($descripcion === '')                                                { $mensaje = 'descripcion es obligatoria.'; }
elseif (!in_array($estado, array('Pendiente','Entregado','No aplica')))     { $mensaje = 'estado inválido.'; }
else {
    $resp_anterior = ''; $est_anterior = ''; $id_siniestro = '';
    $rs = db_query($link, "SELECT responsable, estado, id_siniestro FROM siniestros_pendientes WHERE id='$id'");
    while ($row = db_fetch_object($rs)) {
        $resp_anterior = $row->responsable;
        $est_anterior  = $row->estado;
        $id_siniestro  = $row->id_siniestro;
    }

    if ($id_siniestro === '') {
        $mensaje = 'Pendiente no encontrado.';
    } else {
        $d  = sqlesc($descripcion);
        $n  = sqlesc($notas);
        $r  = sqlesc($responsable);
        $e  = sqlesc($estado);
        $fe = ($fecha_entrega !== '') ? "NULLIF('$fecha_entrega','')::date" : "NULL";
        db_query($link, "UPDATE siniestros_pendientes SET
                            responsable   = '$r',
                            descripcion   = '$d',
                            estado        = '$e',
                            fecha_entrega = $fe,
                            notas         = '$n',
                            updated_at    = NOW()
                         WHERE id = '$id'");

        if ($est_anterior !== $estado || $resp_anterior !== $responsable) {
            $accion = ($est_anterior !== $estado) ? 'Cambio estado' : 'Cambio responsable';
            db_query($link, "INSERT INTO siniestros_pendientes_bitacora
                                (id_pendiente, accion, estado_anterior, estado_nuevo,
                                 responsable_anterior, responsable_nuevo, usuario)
                             VALUES
                                ('$id', '" . sqlesc($accion) . "',
                                 " . ($est_anterior !== $estado ? "'" . sqlesc($est_anterior) . "'" : "NULL") . ",
                                 " . ($est_anterior !== $estado ? "'$e'" : "NULL") . ",
                                 " . ($resp_anterior !== $responsable ? "'" . sqlesc($resp_anterior) . "'" : "NULL") . ",
                                 " . ($resp_anterior !== $responsable ? "'$r'" : "NULL") . ",
                                 '" . sqlesc($usuario) . "')");
        }

        // ¿Se acaba de cerrar el último pendiente del Cliente?
        if ($est_anterior === 'Pendiente' && $estado === 'Entregado' && $resp_anterior === 'Cliente') {
            $restantes = 0;
            $rs2 = db_query($link, "SELECT COUNT(*) AS n FROM siniestros_pendientes
                                    WHERE id_siniestro='$id_siniestro' AND responsable='Cliente' AND estado='Pendiente'");
            while ($row = db_fetch_object($rs2)) { $restantes = (int)$row->n; }
            if ($restantes === 0) {
                $rs3 = db_query($link, "SELECT liquidador_nombre, liquidador_correo,
                                               COALESCE(numero_siniestro,'') AS ns,
                                               numero_poliza, nombre_asegurado
                                        FROM siniestros WHERE id='$id_siniestro'");
                while ($row = db_fetch_object($rs3)) {
                    $cliente_completo = true;
                    $liquidador['nombre']           = $row->liquidador_nombre;
                    $liquidador['correo']           = $row->liquidador_correo;
                    $liquidador['numero_siniestro'] = $row->ns;
                    $liquidador['numero_poliza']    = $row->numero_poliza;
                    $liquidador['nombre_asegurado'] = $row->nombre_asegurado;
                }
            }
        }

        db_query($link, "SELECT trazabilidad('" . sqlesc($usuario) . "', 'Actualización pendiente',
                            'ID: $id', 'siniestros_pendientes', '$id', '{$_SERVER['PHP_SELF']}')");
        $ok = true; $mensaje = 'Pendiente actualizado.';
    }
}

db_close($link);
echo json_encode(array(
    'ok' => $ok,
    'mensaje' => $mensaje,
    'cliente_completo' => $cliente_completo,
    'liquidador' => $liquidador
));
?>
