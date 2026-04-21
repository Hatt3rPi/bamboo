<?php
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";
header('Content-Type: application/json; charset=utf-8');

function estandariza_info($d) { return trim(stripslashes($d)); }
function sqlesc($v) { return str_replace("'", "''", $v); }

$id           = estandariza_info($_POST['id']            ?? '');
$codigo       = estandariza_info($_POST['codigo']        ?? '');
$nombre       = estandariza_info($_POST['nombre']        ?? '');
$modulo       = estandariza_info($_POST['modulo']        ?? 'siniestros');
$asunto       = estandariza_info($_POST['asunto']        ?? '');
$cuerpo_texto = estandariza_info($_POST['cuerpo_texto']  ?? '');
$cuerpo_html  = estandariza_info($_POST['cuerpo_html']   ?? '');
$activo       = isset($_POST['activo']) && ($_POST['activo'] === '1' || $_POST['activo'] === 'true' || $_POST['activo'] === 1);
$usuario      = $_SESSION['username'] ?? '';

if ($codigo === '' || $nombre === '' || $asunto === '' || $cuerpo_texto === '') {
    echo json_encode(array('ok' => false, 'mensaje' => 'Código, nombre, asunto y cuerpo son obligatorios.'));
    exit;
}

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

$c  = sqlesc($codigo);
$n  = sqlesc($nombre);
$m  = sqlesc($modulo);
$a  = sqlesc($asunto);
$bt = sqlesc($cuerpo_texto);
$bh = sqlesc($cuerpo_html);
$u  = sqlesc($usuario);
$act = $activo ? 'TRUE' : 'FALSE';
$html_sql = $cuerpo_html === '' ? 'NULL' : "'$bh'";

if (ctype_digit($id) && $id !== '') {
    db_query($link, "UPDATE email_templates SET
                        codigo='$c', nombre='$n', modulo='$m',
                        asunto='$a', cuerpo_texto='$bt', cuerpo_html=$html_sql,
                        activo=$act, updated_at=NOW(), updated_by='$u'
                     WHERE id='$id'");
    $accion = 'update';
} else {
    db_query($link, "INSERT INTO email_templates
                        (codigo, nombre, modulo, asunto, cuerpo_texto, cuerpo_html, activo, updated_by)
                     VALUES
                        ('$c', '$n', '$m', '$a', '$bt', $html_sql, $act, '$u')
                     ON CONFLICT (codigo) DO UPDATE SET
                        nombre=EXCLUDED.nombre, modulo=EXCLUDED.modulo,
                        asunto=EXCLUDED.asunto, cuerpo_texto=EXCLUDED.cuerpo_texto,
                        cuerpo_html=EXCLUDED.cuerpo_html, activo=EXCLUDED.activo,
                        updated_at=NOW(), updated_by=EXCLUDED.updated_by");
    $accion = 'insert';
}

db_query($link, "SELECT trazabilidad('$u', 'Guardar email template ($accion)',
                    'codigo: $c', 'email_templates', '$id', '{$_SERVER['PHP_SELF']}')");

db_close($link);
echo json_encode(array('ok' => true, 'mensaje' => 'Plantilla guardada.'));
?>
