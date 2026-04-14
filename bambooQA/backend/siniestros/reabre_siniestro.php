<?php
if (!isset($_SESSION)) {
    session_start();
}
require_once "/home/gestio10/public_html/backend/config.php";

function estandariza_info($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

$id_siniestro = estandariza_info($_POST["id_siniestro"] ?? '');
$motivo       = estandariza_info($_POST["motivo"]       ?? '');
$usuario      = $_SESSION['username'] ?? '';

$mensaje = '';
$ok = false;

if ($id_siniestro === '' || $motivo === '') {
    $mensaje = 'Debe indicar el ID del siniestro y el motivo de reapertura.';
} else {
    db_set_charset($link, 'utf8');
    db_select_db($link, DB_NAME);

    $res = db_query($link, "SELECT estado FROM siniestros WHERE id = '$id_siniestro'");
    $estado_anterior = '';
    while ($row = db_fetch_object($res)) {
        $estado_anterior = $row->estado;
    }

    if ($estado_anterior === '') {
        $mensaje = 'Siniestro no encontrado.';
    } elseif ($estado_anterior !== 'Cerrado') {
        $mensaje = 'Solo se pueden reabrir siniestros en estado Cerrado.';
    } else {
        db_query($link, "UPDATE siniestros SET estado = 'En proceso' WHERE id = '$id_siniestro'");
        db_query($link, "INSERT INTO siniestros_bitacora (id_siniestro, estado_anterior, estado_nuevo, usuario, motivo)
                         VALUES ('$id_siniestro', '$estado_anterior', 'En proceso', '$usuario', '$motivo')");
        db_query($link, "SELECT trazabilidad('$usuario', 'Reapertura siniestro', 'Motivo: $motivo', 'siniestros', '$id_siniestro', '{$_SERVER['PHP_SELF']}')");
        $mensaje = 'Siniestro reabierto correctamente.';
        $ok = true;
    }
    db_close($link);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array("ok" => $ok, "mensaje" => $mensaje));
?>
