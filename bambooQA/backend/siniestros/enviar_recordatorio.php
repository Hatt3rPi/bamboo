<?php
/**
 * Envía un recordatorio amigable al responsable actual de un pendiente.
 * Reunión 22-abr-2026 — botón opcional en cada fila de la tabla de pendientes.
 *
 * Entrada POST:
 *   - id_pendiente (obligatorio)
 *
 * Retorno JSON:
 *   - ok, mensaje, enviado_por ('brevo'|'mailto'), mailto_url (si aplica)
 */
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";
require_once __DIR__ . "/helper_brevo.php";
require_once __DIR__ . "/../email/render_template.php";

function estandariza_info($d) { return htmlspecialchars(stripslashes(trim($d))); }
function sqlesc($v) { return str_replace("'", "''", $v); }

header('Content-Type: application/json; charset=utf-8');

$id_pendiente = estandariza_info($_POST['id_pendiente'] ?? '');
$usuario      = $_SESSION['username'] ?? '';

if (!ctype_digit($id_pendiente)) {
    echo json_encode(array('ok' => false, 'mensaje' => 'id_pendiente inválido'));
    exit;
}

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

// Datos del pendiente + siniestro + contactos posibles
$info = null;
$rs = db_query($link, "SELECT p.id, p.id_siniestro, p.responsable, p.descripcion, p.estado,
                              s.liquidador_nombre, s.liquidador_correo,
                              COALESCE(s.numero_siniestro,'') AS numero_siniestro,
                              s.numero_poliza, s.nombre_asegurado, s.correo_asegurado,
                              COALESCE(s.ramo,'') AS ramo,
                              s.compania_contacto_nombre, s.compania_contacto_mail,
                              COALESCE(b.taller_nombre,'')   AS taller_nombre,
                              COALESCE(b.taller_telefono,'') AS taller_telefono
                       FROM siniestros_pendientes p
                       JOIN siniestros s ON s.id = p.id_siniestro
                       LEFT JOIN siniestros_bienes_afectados b ON b.id = p.id_bien
                       WHERE p.id='$id_pendiente'");
while ($row = db_fetch_object($rs)) { $info = $row; }

if (!$info) {
    echo json_encode(array('ok' => false, 'mensaje' => 'Pendiente no encontrado'));
    db_close($link); exit;
}
if ($info->estado !== 'Pendiente') {
    echo json_encode(array('ok' => false, 'mensaje' => 'Solo se pueden recordar pendientes abiertos.'));
    db_close($link); exit;
}

// Resolver destinatario según responsable
$dest_mail = ''; $dest_nombre = '';
switch ($info->responsable) {
    case 'Cliente':
        $dest_mail   = $info->correo_asegurado;
        $dest_nombre = $info->nombre_asegurado;
        break;
    case 'Liquidador':
        $dest_mail   = $info->liquidador_correo;
        $dest_nombre = $info->liquidador_nombre;
        break;
    case 'Compañía':
        $dest_mail   = $info->compania_contacto_mail;
        $dest_nombre = $info->compania_contacto_nombre ?: 'equipo de la compañía';
        break;
    case 'Taller':
        // El taller no tiene columna dedicada de mail; por ahora cae a mailto vacío.
        $dest_mail   = '';
        $dest_nombre = $info->taller_nombre ?: 'taller';
        break;
}

if ($dest_mail === '') {
    echo json_encode(array(
        'ok' => false,
        'mensaje' => 'No hay correo registrado para el responsable (' . $info->responsable . ').'
    ));
    db_close($link); exit;
}

$vars = array(
    'destinatario_nombre'   => $dest_nombre,
    'nombre_asegurado'      => $info->nombre_asegurado ?? '',
    'numero_siniestro'      => $info->numero_siniestro !== '' ? $info->numero_siniestro : '(sin N°)',
    'descripcion_pendiente' => $info->descripcion
);
$rendered = render_email_template($link, 'siniestro_recordatorio_amigable', $vars);
if ($rendered === null) {
    echo json_encode(array('ok' => false, 'mensaje' => "Plantilla 'siniestro_recordatorio_amigable' no disponible."));
    db_close($link); exit;
}
$asunto = $rendered['asunto'];
$cuerpo = $rendered['texto'];

// Si Brevo no está configurado, generar mailto para que el frontend lo abra.
if (!brevo_configurado()) {
    $mailto = 'mailto:' . rawurlencode($dest_mail)
            . '?subject=' . rawurlencode($asunto)
            . '&body='    . rawurlencode($cuerpo);

    db_query($link, "INSERT INTO siniestros_pendientes_bitacora
                        (id_pendiente, accion, usuario)
                     VALUES
                        ('$id_pendiente', 'Recordatorio enviado (mailto)', '" . sqlesc($usuario) . "')");

    echo json_encode(array(
        'ok' => true,
        'mensaje' => 'Brevo no configurado — abriendo cliente de correo.',
        'enviado_por' => 'mailto',
        'mailto_url' => $mailto
    ));
    db_close($link); exit;
}

// Envío Brevo
$r = enviar_correo_brevo($dest_mail, $dest_nombre, $asunto, $cuerpo);

$id_siniestro = $info->id_siniestro;
$asunto_esc = sqlesc($asunto);
$cuerpo_esc = sqlesc($cuerpo);
$dest_esc   = sqlesc($dest_mail);
$dn_esc     = sqlesc($dest_nombre);
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
                     'recordatorio', '$usr_esc')");

db_query($link, "INSERT INTO siniestros_pendientes_bitacora
                    (id_pendiente, accion, usuario)
                 VALUES
                    ('$id_pendiente', 'Recordatorio enviado (brevo)', '$usr_esc')");

db_query($link, "SELECT trazabilidad('$usr_esc', 'Recordatorio pendiente',
                    'Pendiente: $id_pendiente, destinatario: $dest_esc', 'siniestros_pendientes',
                    '$id_pendiente', '{$_SERVER['PHP_SELF']}')");

db_close($link);
echo json_encode(array(
    'ok' => $r['ok'],
    'enviado_por' => 'brevo',
    'mensaje' => $r['mensaje'] ?? 'ok',
    'message_id' => $r['message_id'] ?? null
));
?>
