<?php
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";

function estandariza_info($d) { return htmlspecialchars(stripslashes(trim($d))); }
function sqlesc($v) { return str_replace("'", "''", $v); }

header('Content-Type: application/json; charset=utf-8');

$accion        = estandariza_info($_POST['accion']         ?? '');
$id            = estandariza_info($_POST['id']             ?? '');
$id_siniestro  = estandariza_info($_POST['id_siniestro']   ?? '');
$tipo          = estandariza_info($_POST['tipo']           ?? '');
$descripcion   = estandariza_info($_POST['descripcion']    ?? '');
$estado        = estandariza_info($_POST['estado']         ?? 'Abierto');
$observaciones = estandariza_info($_POST['observaciones']  ?? '');
$fecha_alarma  = estandariza_info($_POST['fecha_alarma']   ?? '');
$motivo        = estandariza_info($_POST['motivo']         ?? '');
$usuario       = $_SESSION['username'] ?? '';

$ok = false; $mensaje = ''; $id_nuevo = null;

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

switch ($accion) {
    case 'crear_bien':
        if (!ctype_digit($id_siniestro)) { $mensaje = 'id_siniestro inválido.'; break; }
        if (!in_array($tipo, array('propio','tercero'))) { $mensaje = 'tipo inválido.'; break; }
        if ($descripcion === '') { $mensaje = 'descripcion es obligatoria.'; break; }
        if (!in_array($estado, array('Abierto','Cerrado','Rechazado'))) { $estado = 'Abierto'; }
        $d = sqlesc($descripcion); $o = sqlesc($observaciones);
        $fa = ($fecha_alarma !== '') ? "NULLIF('$fecha_alarma','')::date" : "NULL";
        db_query($link, "INSERT INTO siniestros_bienes_afectados (id_siniestro, tipo, descripcion, estado, observaciones, fecha_alarma)
                         VALUES ('$id_siniestro', '$tipo', '$d', '$estado', '$o', $fa)
                         RETURNING id");
        // En PG pg_insert_id no es común; fallback con SELECT currval.
        $r = db_query($link, "SELECT currval('siniestros_bienes_afectados_id_seq') AS id");
        while ($fila = db_fetch_object($r)) { $id_nuevo = $fila->id; }
        if ($id_nuevo) {
            db_query($link, "INSERT INTO siniestros_bienes_bitacora (id_bien, estado_anterior, estado_nuevo, usuario, motivo)
                             VALUES ('$id_nuevo', NULL, '$estado', '$usuario', 'Creación')");
        }
        db_query($link, "SELECT trazabilidad('$usuario', 'Creación bien afectado', 'Siniestro: $id_siniestro, tipo: $tipo', 'siniestros_bienes_afectados', '$id_siniestro', '{$_SERVER['PHP_SELF']}')");
        $ok = true; $mensaje = 'Bien creado.';
        break;

    case 'actualizar_bien':
        if (!ctype_digit($id)) { $mensaje = 'id inválido.'; break; }
        if ($descripcion === '') { $mensaje = 'descripcion es obligatoria.'; break; }
        if (!in_array($estado, array('Abierto','Cerrado','Rechazado'))) { $estado = 'Abierto'; }

        // Leer estado anterior para bitácora
        $estado_anterior = '';
        $r = db_query($link, "SELECT estado FROM siniestros_bienes_afectados WHERE id = '$id'");
        while ($row = db_fetch_object($r)) { $estado_anterior = $row->estado; }

        $d = sqlesc($descripcion); $o = sqlesc($observaciones);
        $fa = ($fecha_alarma !== '') ? "NULLIF('$fecha_alarma','')::date" : "NULL";
        db_query($link, "UPDATE siniestros_bienes_afectados SET
                            descripcion = '$d',
                            estado = '$estado',
                            observaciones = '$o',
                            fecha_alarma = $fa,
                            updated_at = NOW()
                         WHERE id = '$id'");

        if ($estado_anterior !== '' && $estado_anterior !== $estado) {
            $m = sqlesc($motivo !== '' ? $motivo : 'Edición');
            db_query($link, "INSERT INTO siniestros_bienes_bitacora (id_bien, estado_anterior, estado_nuevo, usuario, motivo)
                             VALUES ('$id', '$estado_anterior', '$estado', '$usuario', '$m')");
        }
        db_query($link, "SELECT trazabilidad('$usuario', 'Actualización bien afectado', 'ID: $id', 'siniestros_bienes_afectados', '$id', '{$_SERVER['PHP_SELF']}')");
        $ok = true; $mensaje = 'Bien actualizado.';
        break;

    case 'eliminar_bien':
        if (!ctype_digit($id)) { $mensaje = 'id inválido.'; break; }
        // Hard delete: CASCADE borra docs + bitácora. Es data local del siniestro; OK.
        db_query($link, "DELETE FROM siniestros_bienes_afectados WHERE id = '$id'");
        db_query($link, "SELECT trazabilidad('$usuario', 'Eliminación bien afectado', 'ID: $id', 'siniestros_bienes_afectados', '$id', '{$_SERVER['PHP_SELF']}')");
        $ok = true; $mensaje = 'Bien eliminado.';
        break;

    default:
        $mensaje = 'Acción no reconocida.';
}

db_close($link);
echo json_encode(array('ok' => $ok, 'mensaje' => $mensaje, 'id' => $id_nuevo));
?>
