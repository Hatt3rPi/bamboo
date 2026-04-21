<?php
/**
 * Helper de envío de correo vía Brevo (ex Sendinblue) API transaccional.
 *
 * Configuración esperada (de getenv o $_ENV):
 *   - BREVO_API_KEY
 *   - BREVO_SENDER_EMAIL  (remitente verificado en Brevo)
 *   - BREVO_SENDER_NAME   (ej. "Adriana Sandoval")
 *
 * brevo_configurado() indica si el entorno tiene las 3 variables.
 * enviar_correo_brevo() retorna un array con 'ok', 'mensaje', 'message_id'.
 */

if (!function_exists('brevo_config')) {
    function brevo_config() {
        return array(
            'api_key'      => getenv('BREVO_API_KEY')      ?: ($_ENV['BREVO_API_KEY']      ?? ''),
            'sender_email' => getenv('BREVO_SENDER_EMAIL') ?: ($_ENV['BREVO_SENDER_EMAIL'] ?? ''),
            'sender_name'  => getenv('BREVO_SENDER_NAME')  ?: ($_ENV['BREVO_SENDER_NAME']  ?? 'Bamboo')
        );
    }
}

if (!function_exists('brevo_configurado')) {
    function brevo_configurado() {
        $c = brevo_config();
        return $c['api_key'] !== '' && $c['sender_email'] !== '';
    }
}

if (!function_exists('enviar_correo_brevo')) {
    function enviar_correo_brevo($to_email, $to_name, $subject, $text_body) {
        $c = brevo_config();
        if ($c['api_key'] === '' || $c['sender_email'] === '') {
            return array('ok' => false, 'mensaje' => 'Brevo no configurado (faltan variables de entorno).', 'message_id' => null);
        }
        $html = nl2br(htmlspecialchars($text_body));
        $payload = array(
            'sender'      => array('name' => $c['sender_name'], 'email' => $c['sender_email']),
            'to'          => array(array('email' => $to_email, 'name' => $to_name ?: $to_email)),
            'subject'     => $subject,
            'htmlContent' => '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.5">' . $html . '</div>',
            'textContent' => $text_body
        );
        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => array(
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $c['api_key']
            ),
            CURLOPT_TIMEOUT        => 15
        ));
        $resp_body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err  = curl_error($ch);
        curl_close($ch);
        if ($curl_err) {
            return array('ok' => false, 'mensaje' => 'cURL error: ' . $curl_err, 'message_id' => null);
        }
        $resp = json_decode($resp_body, true);
        if ($http_code >= 200 && $http_code < 300) {
            return array('ok' => true, 'mensaje' => 'Enviado.', 'message_id' => $resp['messageId'] ?? null);
        }
        $msg = isset($resp['message']) ? $resp['message'] : ('HTTP ' . $http_code . ': ' . $resp_body);
        return array('ok' => false, 'mensaje' => $msg, 'message_id' => null);
    }
}
?>
