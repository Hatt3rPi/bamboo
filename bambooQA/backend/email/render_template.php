<?php
/**
 * Helper de renderizado de plantillas de correo.
 * Soporta:
 *   - {{ variable }}            → reemplazo simple
 *   - {{ variable|default }}    → si la variable viene vacía/null, usa "default"
 *
 * Lee las plantillas desde la tabla email_templates en Supabase.
 * Retorna array con 'asunto', 'texto', 'html' ya renderizados.
 * 'html' se genera desde 'texto' con nl2br+escape si la plantilla no tiene cuerpo_html.
 */

if (!function_exists('render_email_template')) {
    /**
     * @param mysqli|resource $link    Conexión DB ya inicializada.
     * @param string          $codigo  Código único del template.
     * @param array           $vars    Variables a reemplazar. Keys coinciden con {{nombre}}.
     * @return array|null  ['asunto'=>..., 'texto'=>..., 'html'=>...] o null si no existe / inactiva.
     */
    function render_email_template($link, $codigo, $vars = array()) {
        $c = str_replace("'", "''", $codigo);
        $asunto = ''; $texto = ''; $html = null;
        $encontrado = false;
        $res = db_query($link, "SELECT asunto, cuerpo_texto, cuerpo_html
                                FROM email_templates
                                WHERE codigo='$c' AND activo=TRUE LIMIT 1");
        while ($row = db_fetch_object($res)) {
            $asunto = $row->asunto;
            $texto  = $row->cuerpo_texto;
            $html   = $row->cuerpo_html;
            $encontrado = true;
        }
        if (!$encontrado) return null;

        $asunto = aplicar_variables_template($asunto, $vars);
        $texto  = aplicar_variables_template($texto,  $vars);
        if ($html !== null && $html !== '') {
            $html = aplicar_variables_template($html, $vars);
        } else {
            $html = '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.6">' .
                    nl2br(htmlspecialchars($texto)) . '</div>';
        }
        return array('asunto' => $asunto, 'texto' => $texto, 'html' => $html);
    }
}

if (!function_exists('aplicar_variables_template')) {
    function aplicar_variables_template($tmpl, $vars) {
        // Matching tolerante a espacios: {{ nombre }} o {{nombre}} con opcional |default
        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*(?:\|([^}]*?))?\s*\}\}/',
            function ($m) use ($vars) {
                $nombre  = $m[1];
                $defecto = isset($m[2]) ? trim($m[2]) : '';
                $valor = $vars[$nombre] ?? '';
                if ($valor === '' || $valor === null) {
                    return $defecto;
                }
                return (string)$valor;
            },
            $tmpl
        );
    }
}
?>
