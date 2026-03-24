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

$accion = estandariza_info($_POST["accion"]);

// Sanitizar campos POST
$numero_siniestro    = estandariza_info($_POST["numero_siniestro"]    ?? '');
$id_poliza           = estandariza_info($_POST["id_poliza"]           ?? '');
$numero_poliza       = estandariza_info($_POST["numero_poliza"]       ?? '');
$ramo                = estandariza_info($_POST["ramo"]                ?? '');
$tipo_siniestro      = estandariza_info($_POST["tipo_siniestro"]      ?? '');
$fecha_ocurrencia    = estandariza_info($_POST["fecha_ocurrencia"]    ?? '');
$fecha_denuncia      = estandariza_info($_POST["fecha_denuncia"]      ?? '');
$presentado          = isset($_POST["presentado"]) && $_POST["presentado"] == '1';
$rut_asegurado       = estandariza_info($_POST["rut_asegurado"]       ?? '');
$dv_asegurado        = estandariza_info($_POST["dv_asegurado"]        ?? '');
$nombre_asegurado    = estandariza_info($_POST["nombre_asegurado"]    ?? '');
$telefono_asegurado  = estandariza_info($_POST["telefono_asegurado"]  ?? '');
$correo_asegurado    = estandariza_info($_POST["correo_asegurado"]    ?? '');
$descripcion         = estandariza_info($_POST["descripcion"]         ?? '');
$liquidador_nombre   = estandariza_info($_POST["liquidador_nombre"]   ?? '');
$liquidador_telefono = estandariza_info($_POST["liquidador_telefono"] ?? '');
$liquidador_correo   = estandariza_info($_POST["liquidador_correo"]   ?? '');
$patente             = estandariza_info($_POST["patente"]             ?? '');
$marca               = estandariza_info($_POST["marca"]               ?? '');
$modelo              = estandariza_info($_POST["modelo"]              ?? '');
$anio_vehiculo       = estandariza_info($_POST["anio_vehiculo"]       ?? '');
$taller_nombre       = estandariza_info($_POST["taller_nombre"]       ?? '');
$taller_telefono     = estandariza_info($_POST["taller_telefono"]     ?? '');
$estado              = estandariza_info($_POST["estado"]              ?? 'Abierto');

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

$mensaje  = '';
$busqueda = '';
$listado  = '/bambooQA/listado_siniestros.php';

switch ($accion) {

    case 'crear_siniestro':
        $mensaje = 'Siniestro registrado correctamente';
        $token   = bin2hex(random_bytes(6));
        $query   = "INSERT INTO siniestros (
                        numero_siniestro, id_poliza, numero_poliza, ramo,
                        tipo_siniestro, fecha_ocurrencia, fecha_denuncia,
                        rut_asegurado, dv_asegurado, nombre_asegurado,
                        telefono_asegurado, correo_asegurado,
                        descripcion,
                        liquidador_nombre, liquidador_telefono, liquidador_correo,
                        patente, marca, modelo, anio_vehiculo,
                        taller_nombre, taller_telefono,
                        estado, presentado, token, usuario_registro
                    ) VALUES (
                        '$numero_siniestro', '$id_poliza', '$numero_poliza', '$ramo',
                        '$tipo_siniestro',
                        " . ($fecha_ocurrencia ? "'$fecha_ocurrencia'" : "NULL") . ",
                        " . ($fecha_denuncia   ? "'$fecha_denuncia'"   : "NULL") . ",
                        '$rut_asegurado', '$dv_asegurado', '$nombre_asegurado',
                        '$telefono_asegurado', '$correo_asegurado',
                        '$descripcion',
                        '$liquidador_nombre', '$liquidador_telefono', '$liquidador_correo',
                        '$patente', '$marca', '$modelo',
                        " . ($anio_vehiculo ? "'$anio_vehiculo'" : "NULL") . ",
                        '$taller_nombre', '$taller_telefono',
                        '$estado', " . ($presentado ? "TRUE" : "FALSE") . ",
                        '$token', '{$_SESSION['username']}'
                    )";
        db_query($link, $query);

        // Obtener ID generado para armar número de siniestro legible
        $res = db_query($link, "SELECT id, numero_siniestro FROM siniestros WHERE token='$token'");
        while ($fila = db_fetch_object($res)) {
            $busqueda = $fila->numero_siniestro
                ? $fila->numero_siniestro
                : 'S' . str_pad($fila->id, 6, '0', STR_PAD_LEFT);
        }

        db_query($link, "SELECT trazabilidad('{$_SESSION['username']}', 'Creación siniestro', '" .
            str_replace("'", "**", $query) . "', 'siniestros', '$busqueda', '{$_SERVER['PHP_SELF']}')");
        break;

    case 'actualizar_siniestro':
        $id       = estandariza_info($_POST["id_siniestro"]);
        $mensaje  = 'Siniestro actualizado correctamente';
        $busqueda = $numero_siniestro;
        $query    = "UPDATE siniestros SET
                        numero_siniestro    = '$numero_siniestro',
                        tipo_siniestro      = '$tipo_siniestro',
                        fecha_ocurrencia    = " . ($fecha_ocurrencia ? "'$fecha_ocurrencia'" : "NULL") . ",
                        fecha_denuncia      = " . ($fecha_denuncia   ? "'$fecha_denuncia'"   : "NULL") . ",
                        nombre_asegurado    = '$nombre_asegurado',
                        telefono_asegurado  = '$telefono_asegurado',
                        correo_asegurado    = '$correo_asegurado',
                        descripcion         = '$descripcion',
                        liquidador_nombre   = '$liquidador_nombre',
                        liquidador_telefono = '$liquidador_telefono',
                        liquidador_correo   = '$liquidador_correo',
                        patente             = '$patente',
                        marca               = '$marca',
                        modelo              = '$modelo',
                        anio_vehiculo       = " . ($anio_vehiculo ? "'$anio_vehiculo'" : "NULL") . ",
                        taller_nombre       = '$taller_nombre',
                        taller_telefono     = '$taller_telefono',
                        estado              = '$estado',
                        presentado          = " . ($presentado ? "TRUE" : "FALSE") . "
                    WHERE id = '$id'";
        db_query($link, $query);
        db_query($link, "SELECT trazabilidad('{$_SESSION['username']}', 'Actualización siniestro', '" .
            str_replace("'", "**", $query) . "', 'siniestros', '$id', '{$_SERVER['PHP_SELF']}')");
        break;

    case 'eliminar_siniestro':
        $id      = estandariza_info($_POST["id_siniestro"]);
        $mensaje = 'Siniestro eliminado correctamente';
        $query   = "UPDATE siniestros SET estado = 'Eliminado' WHERE id = '$id'";
        db_query($link, $query);
        db_query($link, "SELECT trazabilidad('{$_SESSION['username']}', 'Eliminación siniestro', '" .
            str_replace("'", "**", $query) . "', 'siniestros', '$id', '{$_SERVER['PHP_SELF']}')");
        break;
}

db_close($link);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="/assets/js/jquery.redirect.js"></script>
</head>
<body>
<script>
var mensaje  = '<?php echo $mensaje; ?>';
var busqueda = '<?php echo $busqueda; ?>';
var listado  = '<?php echo $listado; ?>';
alert(mensaje);
$.redirect(listado, { 'busqueda': busqueda }, 'post');
</script>
</body>
</html>
