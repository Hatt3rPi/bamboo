<?php
/**
 * Envía el correo automático al liquidador cuando el cliente entregó todo.
 * Reunión 21-abr-2026.
 *
 * Entrada POST:
 *   - id_siniestro (obligatorio)
 *
 * Retorno JSON:
 *   - ok, mensaje, proveedor, message_id, asunto (para feedback al usuario)
 *
 * Cae a modo 'no configurado' si no hay BREVO_API_KEY; en ese caso devuelve
 * asunto + cuerpo para que el frontend arme el mailto como fallback.
 */
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";
require_once __DIR__ . "/helper_brevo.php";
require_once __DIR__ . "/../email/render_template.php";

function estandariza_info($d) { return htmlspecialchars(stripslashes(trim($d))); }
function sqlesc($v) { return str_replace("'", "''", $v); }

header('Content-Type: application/json; charset=utf-8');

$id_siniestro = estandariza_info($_POST['id_siniestro'] ?? '');
$usuario      = $_SESSION['username'] ?? '';

if (!ctype_digit($id_siniestro)) {
    echo json_encode(array('ok' => false, 'mensaje' => 'id_siniestro inválido'));
    exit;
}

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

$s = null;
$rs = db_query($link, "SELECT liquidador_nombre, liquidador_correo,
                              COALESCE(numero_siniestro,'') AS ns,
                              numero_poliza, nombre_asegurado,
                              COALESCE(ramo,'') AS ramo,
                              COALESCE(numero_carpeta_liquidador,'') AS ncl
                       FROM siniestros WHERE id='$id_siniestro'");
while ($row = db_fetch_object($rs)) { $s = $row; }

if (!$s) {
    echo json_encode(array('ok' => false, 'mensaje' => 'Siniestro no encontrado'));
    db_close($link); exit;
}
if (!$s->liquidador_correo) {
    echo json_encode(array('ok' => false, 'mensaje' => 'El liquidador no tiene correo registrado.'));
    db_close($link); exit;
}

$ramo_up = strtoupper($s->ramo);
$es_vehiculo = (strpos($ramo_up, 'VEH') !== false || strpos($ramo_up, 'AUTO') !== false);
$nsin  = $s->ns ?: '(sin N° de siniestro)';

$codigo_tpl = $es_vehiculo ? 'siniestro_liquidador_vehiculo' : 'siniestro_liquidador_no_vehiculo';
$vars = array(
    'liquidador_nombre'         => $s->liquidador_nombre ?? '',
    'nombre_asegurado'          => $s->nombre_asegurado ?? '',
    'numero_siniestro'          => $nsin,
    'numero_carpeta_liquidador' => $s->ncl ?? '',
    'numero_poliza'             => $s->numero_poliza ?? '',
    'ramo'                      => $s->ramo ?? '',
    'carpeta_suffix'            => $s->ncl !== '' ? ' — Carpeta ' . $s->ncl : ''
);
$rendered = render_email_template($link, $codigo_tpl, $vars);
if ($rendered === null) {
    echo json_encode(array('ok' => false, 'mensaje' => "Plantilla '$codigo_tpl' no encontrada o inactiva."));
    db_close($link); exit;
}
$asunto = $rendered['asunto'];
$cuerpo = $rendered['texto'];

// Si Brevo no está configurado, devolver datos para que el frontend use mailto.
if (!brevo_configurado()) {
    echo json_encode(array(
        'ok' => false,
        'proveedor' => 'no_configurado',
        'mensaje' => 'Brevo no configurado — usa mailto como fallback.',
        'asunto' => $asunto,
        'cuerpo' => $cuerpo,
        'destinatario' => $s->liquidador_correo,
        'destinatario_nombre' => $s->liquidador_nombre
    ));
    db_close($link); exit;
}

$r = enviar_correo_brevo($s->liquidador_correo, $s->liquidador_nombre, $asunto, $cuerpo);

$asunto_esc = sqlesc($asunto);
$cuerpo_esc = sqlesc($cuerpo);
$dest_esc   = sqlesc($s->liquidador_correo);
$dn_esc     = sqlesc($s->liquidador_nombre ?? '');
$usr_esc    = sqlesc($usuario);
$mid_esc    = sqlesc($r['message_id'] ?? '');
$err_esc    = sqlesc($r['mensaje'] ?? '');
$estado     = $r['ok'] ? 'enviado' : 'error';
db_query($link, "INSERT INTO siniestros_notificaciones_enviadas
                    (id_siniestro, destinatario_email, destinatario_nombre, asunto, cuerpo,
                     proveedor, proveedor_message_id, estado, error_detalle, tipo, usuario)
                 VALUES
                    ('$id_siniestro', '$dest_esc', '$dn_esc', '$asunto_esc', '$cuerpo_esc',
                     'brevo', " . ($r['message_id'] ? "'$mid_esc'" : "NULL") . ",
                     '$estado', " . ($r['ok'] ? "NULL" : "'$err_esc'") . ",
                     'cliente_completo', '$usr_esc')");

db_query($link, "SELECT trazabilidad('$usr_esc', 'Correo liquidador',
                    'Siniestro: $id_siniestro, estado: $estado', 'siniestros',
                    '$id_siniestro', '{$_SERVER['PHP_SELF']}')");

db_close($link);
echo json_encode(array(
    'ok' => $r['ok'],
    'proveedor' => 'brevo',
    'mensaje' => $r['mensaje'],
    'message_id' => $r['message_id'],
    'asunto' => $asunto,
    'destinatario' => $s->liquidador_correo
));
?>
