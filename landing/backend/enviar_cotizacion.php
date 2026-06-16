<?php
/* ============================================================
   BAMBOO SEGUROS · backend/enviar_cotizacion.php
   Recibe el formulario de cotización (POST), valida, envía
   correo a Adriana y responde JSON. Sin dependencias.
   ============================================================ */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../data/config.php';

// El servidor corre en UTC; mostramos la fecha del lead en hora local de Chile.
// America/Santiago maneja el cambio invierno/verano (UTC-4 / UTC-3) automáticamente.
date_default_timezone_set('America/Santiago');

function fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}
function clean($v, int $max = 500): string {
    if (!is_string($v)) return '';            // input tipo array[] -> string vacío (evita TypeError/500)
    $v = trim($v);
    $v = str_replace(["\r", "\n", "\t"], ' ', $v); // anti header-injection en campos cortos
    return function_exists('mb_substr') ? mb_substr($v, 0, $max) : substr($v, 0, $max);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Método no permitido', 405);

// Honeypot: si viene relleno, es bot. Respondemos ok para no darle pistas.
if (!empty($_POST['website'])) { echo json_encode(['ok' => true]); exit; }

// Rate-limiting por IP (anti mail-bombing): máx. 6 envíos cada 10 min.
// El estado vive en el temp del sistema (fuera del docroot, sin PII, solo timestamps).
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0');
$rlFile = sys_get_temp_dir() . '/bamboo_rl_' . md5($ip) . '.json';
$now = time(); $window = 600; $maxHits = 6;
$hits = [];
if (is_file($rlFile)) {
    $prev = json_decode((string)@file_get_contents($rlFile), true);
    if (is_array($prev)) $hits = array_filter($prev, fn($t) => is_int($t) && $t > $now - $window);
}
if (count($hits) >= $maxHits) fail('Demasiados intentos. Espera unos minutos o escríbenos por WhatsApp.', 429);
$hits[] = $now;
@file_put_contents($rlFile, json_encode(array_values($hits)), LOCK_EX);

$tipo    = clean($_POST['tipo_seguro'] ?? '', 120);
$perfil  = clean($_POST['perfil'] ?? 'Persona', 40);
$detalle = clean($_POST['detalle'] ?? '', 1500);
$nombre  = clean($_POST['nombre'] ?? '', 120);
$tel     = clean($_POST['telefono'] ?? '', 40);
$email   = clean($_POST['email'] ?? '', 160);
$consent = !empty($_POST['consent']);

if ($nombre === '')                 fail('Falta el nombre');
if (!$consent)                      fail('Falta el consentimiento de contacto');
$telOk   = (bool) preg_match('/^(\+?56)?9\d{8}$/', preg_replace('/[^\d+]/', '', $tel));
$emailOk = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
if (!$telOk && !$emailOk)           fail('Necesitamos un teléfono o correo válido');

// ---------- Construir correo (HTML legible, sin IP) ----------
$asunto = "Nueva cotización web — {$tipo} — {$nombre}";
$rows = [
    'Tipo de seguro' => $tipo,
    'Para'           => $perfil,
    'Nombre'         => $nombre,
    'Teléfono'       => $tel !== ''     ? $tel     : '—',
    'Correo'         => $email !== ''   ? $email   : '—',
    'Detalle'        => $detalle !== '' ? $detalle : '—',
    'Fecha'          => date('d-m-Y H:i'),
];

// Texto plano (para el log de respaldo).
$plain = "Nueva cotización desde bambooseguros.cl\n";
foreach ($rows as $k => $v) $plain .= str_pad($k, 16) . ": {$v}\n";

// HTML (para el correo, fácil de escanear).
$rowsHtml = '';
foreach ($rows as $k => $v) {
    $rowsHtml .= '<tr>'
        . '<td style="padding:7px 16px;color:#5a5851;font:bold 13px Arial,sans-serif;white-space:nowrap;vertical-align:top;border-bottom:1px solid #eee">' . htmlspecialchars($k, ENT_QUOTES) . '</td>'
        . '<td style="padding:7px 16px;color:#26302a;font:14px Arial,sans-serif;border-bottom:1px solid #eee">' . nl2br(htmlspecialchars($v, ENT_QUOTES)) . '</td>'
        . '</tr>';
}
$acciones = [];
if ($telOk)   $acciones[] = '<a href="https://wa.me/' . preg_replace('/[^\d]/', '', $tel) . '" style="color:#0b7a43;font-weight:bold;text-decoration:none">Escribir por WhatsApp</a>';
if ($emailOk) $acciones[] = '<a href="mailto:' . htmlspecialchars($email, ENT_QUOTES) . '" style="color:#0b7a43;font-weight:bold;text-decoration:none">Responder correo</a>';
$accionesHtml = $acciones ? '<div style="padding:14px 16px;background:#f6f4f0;font:14px Arial,sans-serif">Contactar: ' . implode(' &nbsp;·&nbsp; ', $acciones) . '</div>' : '';
$cuerpo = '<div style="max-width:560px;margin:auto;border:1px solid #e6e2d8;border-radius:12px;overflow:hidden">'
    . '<div style="background:#536656;color:#fff;padding:16px 18px;font:bold 16px Arial,sans-serif">Nueva cotización web · Bamboo Seguros</div>'
    . '<table style="width:100%;border-collapse:collapse;background:#fff">' . $rowsHtml . '</table>'
    . $accionesHtml . '</div>';

$fromDomain = parse_url($SITE['url'], PHP_URL_HOST) ?: 'bambooseguros.cl';
$fromDomain = preg_replace('/^www\./', '', $fromDomain);
$headers  = "From: Bamboo Web <no-reply@{$fromDomain}>\r\n";
if ($emailOk) $headers .= "Reply-To: {$nombre} <{$email}>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "X-Mailer: BambooLanding\r\n";

// Respaldo a log (texto plano; protegido por backend/.htaccess; rota a .1 sobre 5 MB).
$logFile = __DIR__ . '/leads.log';
if (is_file($logFile) && filesize($logFile) > 5 * 1024 * 1024) {
    @rename($logFile, $logFile . '.1');
}
@file_put_contents($logFile, $plain . "\n", FILE_APPEND | LOCK_EX);

$sent = @mail($SITE['email'], '=?UTF-8?B?' . base64_encode($asunto) . '?=', $cuerpo, $headers);

// El correo es best-effort: el lead queda en el log y el usuario sigue por WhatsApp.
echo json_encode(['ok' => (bool) $sent], JSON_UNESCAPED_UNICODE);
