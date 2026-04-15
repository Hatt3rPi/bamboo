<?php
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";

function estandariza_info($d) { return htmlspecialchars(stripslashes(trim($d))); }
function sqlesc($v) { return str_replace("'", "''", $v); }

header('Content-Type: application/json; charset=utf-8');

$id_bien       = estandariza_info($_POST['id_bien']        ?? '');
$id_documento  = estandariza_info($_POST['id_documento']   ?? '');
$estado        = estandariza_info($_POST['estado']         ?? 'Pendiente');
$fecha_entrega = estandariza_info($_POST['fecha_entrega']  ?? '');
$notas         = estandariza_info($_POST['notas']          ?? '');
$usuario       = $_SESSION['username'] ?? '';

$estados_validos = array('Pendiente','En revisión','Entregado','Rechazado','No aplica');
if (!in_array($estado, $estados_validos)) { $estado = 'Pendiente'; }

$ok = false; $mensaje = '';

if (!ctype_digit($id_bien) || !ctype_digit($id_documento)) {
    echo json_encode(array('ok' => false, 'mensaje' => 'id_bien o id_documento inválido.'));
    exit;
}

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

$n  = sqlesc($notas);
$fe = ($fecha_entrega !== '') ? "NULLIF('$fecha_entrega','')::date" : "NULL";

// UPSERT: inserta si no existe, actualiza si ya existe (patrón on-demand).
db_query($link, "INSERT INTO siniestros_bienes_documentos (id_bien, id_documento, estado, fecha_entrega, notas, updated_at)
                 VALUES ('$id_bien', '$id_documento', '$estado', $fe, '$n', NOW())
                 ON CONFLICT (id_bien, id_documento) DO UPDATE SET
                    estado = EXCLUDED.estado,
                    fecha_entrega = EXCLUDED.fecha_entrega,
                    notas = EXCLUDED.notas,
                    updated_at = NOW()");

db_query($link, "SELECT trazabilidad('$usuario', 'Actualización doc bien', 'Bien: $id_bien, doc: $id_documento, estado: $estado', 'siniestros_bienes_documentos', '$id_bien', '{$_SERVER['PHP_SELF']}')");

db_close($link);
echo json_encode(array('ok' => true, 'mensaje' => 'Documento actualizado.'));
?>
