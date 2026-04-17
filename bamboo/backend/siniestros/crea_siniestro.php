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
$numero_siniestro          = estandariza_info($_POST["numero_siniestro"]          ?? '');
$id_poliza                 = estandariza_info($_POST["id_poliza"]                 ?? '');
$numero_poliza             = estandariza_info($_POST["numero_poliza"]             ?? '');
$ramo                      = estandariza_info($_POST["ramo"]                      ?? '');
$tipo_siniestro            = estandariza_info($_POST["tipo_siniestro"]            ?? '');
$fecha_ocurrencia          = estandariza_info($_POST["fecha_ocurrencia"]          ?? '');
$fecha_denuncia            = estandariza_info($_POST["fecha_denuncia"]            ?? '');
$presentado                = isset($_POST["presentado"]) && $_POST["presentado"] == '1';
$rut_asegurado             = estandariza_info($_POST["rut_asegurado"]             ?? '');
$dv_asegurado              = estandariza_info($_POST["dv_asegurado"]              ?? '');
$nombre_asegurado          = estandariza_info($_POST["nombre_asegurado"]          ?? '');
$telefono_asegurado        = estandariza_info($_POST["telefono_asegurado"]        ?? '');
$correo_asegurado          = estandariza_info($_POST["correo_asegurado"]          ?? '');
$descripcion               = estandariza_info($_POST["descripcion"]               ?? '');
$observaciones             = estandariza_info($_POST["observaciones"]             ?? '');
$liquidador_nombre         = estandariza_info($_POST["liquidador_nombre"]         ?? '');
$liquidador_telefono       = estandariza_info($_POST["liquidador_telefono"]       ?? '');
$liquidador_correo         = estandariza_info($_POST["liquidador_correo"]         ?? '');
$numero_carpeta_liquidador = estandariza_info($_POST["numero_carpeta_liquidador"] ?? '');
$patente                   = estandariza_info($_POST["patente"]                   ?? '');
$marca                     = estandariza_info($_POST["marca"]                     ?? '');
$modelo                    = estandariza_info($_POST["modelo"]                    ?? '');
$anio_vehiculo             = estandariza_info($_POST["anio_vehiculo"]             ?? '');
$taller_nombre             = estandariza_info($_POST["taller_nombre"]             ?? '');
$taller_telefono           = estandariza_info($_POST["taller_telefono"]           ?? '');
$estado                    = estandariza_info($_POST["estado"]                    ?? 'Número pendiente');

// Neutralizar campos vehículo si el ramo no es vehicular (defensa backend).
$ramo_upper = strtoupper($ramo);
$es_ramo_vehiculo = (strpos($ramo_upper, 'VEH') !== false || strpos($ramo_upper, 'AUTO') !== false);
if (!$es_ramo_vehiculo) {
    $patente = $marca = $modelo = $anio_vehiculo = '';
    $taller_nombre = $taller_telefono = '';
    $_POST['vehiculo_patente'] = $_POST['vehiculo_marca'] = $_POST['vehiculo_modelo'] = $_POST['vehiculo_anio'] = array();
}
$items_seleccionados       = estandariza_info($_POST["items_seleccionados"]       ?? '');

$usuario = $_SESSION['username'] ?? '';

// Parser de items_seleccionados (CSV "1,3,5")
function parse_items_csv($csv) {
    $out = array();
    foreach (explode(',', $csv) as $v) {
        $v = trim($v);
        if ($v !== '' && ctype_digit($v)) {
            $out[] = (int) $v;
        }
    }
    return array_values(array_unique($out));
}

// Sincroniza bienes afectados contra BD (INSERT / UPDATE / DELETE).
// $bienes_array: array de objetos con id (opcional), tipo, categoria, descripcion, etc.
function sincroniza_bienes($link, $id_siniestro, $bienes_array, $usuario) {
    $sqlesc = function($v) { return str_replace("'", "''", (string)$v); };
    // IDs existentes en BD
    $existentes = array();
    $res = db_query($link, "SELECT id FROM siniestros_bienes_afectados WHERE id_siniestro = '$id_siniestro'");
    while ($r = db_fetch_object($res)) { $existentes[] = (int)$r->id; }

    $ids_mantenidos = array();
    foreach ($bienes_array as $b) {
        $bien_id        = isset($b['id']) && ctype_digit((string)$b['id']) ? (int)$b['id'] : 0;
        $tipo           = in_array($b['tipo'] ?? '', array('propio','tercero')) ? $b['tipo'] : 'propio';
        $categoria      = in_array($b['categoria'] ?? '', array('vehiculo','inmueble','persona','otro')) ? $b['categoria'] : 'otro';
        $descripcion    = trim($b['descripcion'] ?? '');
        if ($descripcion === '') continue; // ignorar filas vacías
        $estado         = in_array($b['estado'] ?? '', array('Abierto','Cerrado','Rechazado')) ? $b['estado'] : 'Abierto';
        $observaciones  = $b['observaciones'] ?? '';
        $fecha_alarma   = $b['fecha_alarma'] ?? '';
        $patente        = ($categoria === 'vehiculo') ? ($b['patente']       ?? '') : '';
        $marca          = ($categoria === 'vehiculo') ? ($b['marca']         ?? '') : '';
        $modelo         = ($categoria === 'vehiculo') ? ($b['modelo']        ?? '') : '';
        $anio           = ($categoria === 'vehiculo') ? (string)($b['anio_vehiculo'] ?? '') : '';
        $taller_nombre  = ($categoria === 'vehiculo') ? ($b['taller_nombre']   ?? '') : '';
        $taller_telefono = ($categoria === 'vehiculo') ? ($b['taller_telefono'] ?? '') : '';

        $d  = $sqlesc($descripcion);
        $o  = $sqlesc($observaciones);
        $p  = $sqlesc($patente);
        $ma = $sqlesc($marca);
        $mo = $sqlesc($modelo);
        $tn = $sqlesc($taller_nombre);
        $tt = $sqlesc($taller_telefono);
        $fa = ($fecha_alarma !== '') ? "NULLIF('" . $sqlesc($fecha_alarma) . "','')::date" : "NULL";
        $av = ctype_digit($anio) ? "'$anio'::integer" : "NULL";

        if ($bien_id > 0 && in_array($bien_id, $existentes)) {
            // UPDATE
            $estado_anterior = '';
            $res = db_query($link, "SELECT estado FROM siniestros_bienes_afectados WHERE id = '$bien_id'");
            while ($r = db_fetch_object($res)) { $estado_anterior = $r->estado; }

            db_query($link, "UPDATE siniestros_bienes_afectados SET
                                tipo = '$tipo',
                                categoria = '$categoria',
                                descripcion = '$d',
                                estado = '$estado',
                                observaciones = '$o',
                                fecha_alarma = $fa,
                                patente = '$p',
                                marca = '$ma',
                                modelo = '$mo',
                                anio_vehiculo = $av,
                                taller_nombre = '$tn',
                                taller_telefono = '$tt',
                                updated_at = NOW()
                             WHERE id = '$bien_id'");

            if ($estado_anterior !== '' && $estado_anterior !== $estado) {
                db_query($link, "INSERT INTO siniestros_bienes_bitacora (id_bien, estado_anterior, estado_nuevo, usuario, motivo)
                                 VALUES ('$bien_id', '$estado_anterior', '$estado', '$usuario', 'Edición desde form siniestro')");
            }
            $ids_mantenidos[] = $bien_id;
        } else {
            // INSERT
            db_query($link, "INSERT INTO siniestros_bienes_afectados
                                (id_siniestro, tipo, categoria, descripcion, estado, observaciones, fecha_alarma,
                                 patente, marca, modelo, anio_vehiculo, taller_nombre, taller_telefono)
                             VALUES ('$id_siniestro', '$tipo', '$categoria', '$d', '$estado', '$o', $fa,
                                     '$p', '$ma', '$mo', $av, '$tn', '$tt')");
            $r = db_query($link, "SELECT currval('siniestros_bienes_afectados_id_seq') AS id");
            $id_nuevo = null;
            while ($fila = db_fetch_object($r)) { $id_nuevo = (int)$fila->id; }
            if ($id_nuevo) {
                db_query($link, "INSERT INTO siniestros_bienes_bitacora (id_bien, estado_anterior, estado_nuevo, usuario, motivo)
                                 VALUES ('$id_nuevo', NULL, '$estado', '$usuario', 'Creación desde form siniestro')");
                $ids_mantenidos[] = $id_nuevo;
            }
        }
    }

    // DELETE bienes que existían pero ya no vienen en el POST
    $a_borrar = array_diff($existentes, $ids_mantenidos);
    foreach ($a_borrar as $idb) {
        db_query($link, "DELETE FROM siniestros_bienes_afectados WHERE id = '$idb'");
    }
}

// Obtiene datos de vehículo por ítem desde el POST: vehiculo_patente[1], vehiculo_marca[1], ...
function veh_por_item($numero_item) {
    $sanitize = function($v) {
        $v = trim($v);
        $v = stripslashes($v);
        $v = htmlspecialchars($v);
        return $v;
    };
    return array(
        'patente'       => isset($_POST['vehiculo_patente'][$numero_item])       ? $sanitize($_POST['vehiculo_patente'][$numero_item])       : '',
        'marca'         => isset($_POST['vehiculo_marca'][$numero_item])         ? $sanitize($_POST['vehiculo_marca'][$numero_item])         : '',
        'modelo'        => isset($_POST['vehiculo_modelo'][$numero_item])        ? $sanitize($_POST['vehiculo_modelo'][$numero_item])        : '',
        'anio_vehiculo' => isset($_POST['vehiculo_anio'][$numero_item])          ? $sanitize($_POST['vehiculo_anio'][$numero_item])          : ''
    );
}

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

$mensaje  = '';
$busqueda = '';
$listado  = '/bamboo/listado_siniestros.php';

switch ($accion) {

    case 'crear_siniestro':
        $mensaje = 'Siniestro registrado correctamente';
        $token   = bin2hex(random_bytes(6));

        // Auto-ajuste estado según N° compañía al momento de crear
        if ($numero_siniestro === '') {
            $estado = 'Número pendiente';
        } elseif ($estado === 'Número pendiente') {
            $estado = 'Abierto';
        }

        $query   = "INSERT INTO siniestros (
                        numero_siniestro, id_poliza, numero_poliza, ramo,
                        tipo_siniestro, fecha_ocurrencia, fecha_denuncia,
                        rut_asegurado, dv_asegurado, nombre_asegurado,
                        telefono_asegurado, correo_asegurado,
                        descripcion, observaciones,
                        liquidador_nombre, liquidador_telefono, liquidador_correo,
                        numero_carpeta_liquidador,
                        patente, marca, modelo, anio_vehiculo,
                        taller_nombre, taller_telefono,
                        estado, presentado, token, usuario_registro
                    ) VALUES (
                        '$numero_siniestro', " . ($id_poliza !== '' ? "'$id_poliza'" : "NULL") . ", '$numero_poliza', '$ramo',
                        '$tipo_siniestro',
                        " . ($fecha_ocurrencia ? "'$fecha_ocurrencia'" : "NULL") . ",
                        " . ($fecha_denuncia   ? "'$fecha_denuncia'"   : "NULL") . ",
                        '$rut_asegurado', '$dv_asegurado', '$nombre_asegurado',
                        '$telefono_asegurado', '$correo_asegurado',
                        '$descripcion', '$observaciones',
                        '$liquidador_nombre', '$liquidador_telefono', '$liquidador_correo',
                        '$numero_carpeta_liquidador',
                        '$patente', '$marca', '$modelo',
                        " . ($anio_vehiculo ? "'$anio_vehiculo'" : "NULL") . ",
                        '$taller_nombre', '$taller_telefono',
                        '$estado', " . ($presentado ? "TRUE" : "FALSE") . ",
                        '$token', '$usuario'
                    )";
        db_query($link, $query);

        // Obtener ID generado y definir término de búsqueda post-redirect.
        // Si tiene N° compañía, filtramos por ese. Si no, por N° póliza para que
        // el listado muestre el siniestro recién creado (numero_siniestro vacío
        // no es filtrable directamente).
        $id_nuevo = 0;
        $res = db_query($link, "SELECT id, numero_siniestro FROM siniestros WHERE token='$token'");
        while ($fila = db_fetch_object($res)) {
            $id_nuevo = $fila->id;
            $busqueda = $fila->numero_siniestro ? $fila->numero_siniestro : $numero_poliza;
        }

        // Insertar ítems asociados + datos de vehículo por ítem
        if ($id_nuevo > 0) {
            $items = parse_items_csv($items_seleccionados);
            foreach ($items as $ni) {
                $veh = veh_por_item($ni);
                $anio_sql = ($veh['anio_vehiculo'] !== '' && ctype_digit($veh['anio_vehiculo'])) ? "'" . $veh['anio_vehiculo'] . "'" : "NULL";
                db_query($link, "INSERT INTO siniestros_items (id_siniestro, numero_item, patente, marca, modelo, anio_vehiculo)
                                 VALUES ('$id_nuevo', '$ni', '" . $veh['patente'] . "', '" . $veh['marca'] . "', '" . $veh['modelo'] . "', $anio_sql)
                                 ON CONFLICT (id_siniestro, numero_item) DO UPDATE SET
                                    patente = EXCLUDED.patente,
                                    marca = EXCLUDED.marca,
                                    modelo = EXCLUDED.modelo,
                                    anio_vehiculo = EXCLUDED.anio_vehiculo");
            }

            // Bitácora inicial
            db_query($link, "INSERT INTO siniestros_bitacora (id_siniestro, estado_anterior, estado_nuevo, usuario, motivo)
                             VALUES ('$id_nuevo', NULL, '$estado', '$usuario', 'Creación')");

            // Sincronizar bienes afectados
            $bienes_json = $_POST['bienes_json'] ?? '';
            if ($bienes_json !== '') {
                $bienes = json_decode(stripslashes($bienes_json), true);
                if (is_array($bienes)) {
                    sincroniza_bienes($link, $id_nuevo, $bienes, $usuario);
                }
            }
        }

        db_query($link, "SELECT trazabilidad('$usuario', 'Creación siniestro', '" .
            str_replace("'", "**", $query) . "', 'siniestros', '$busqueda', '{$_SERVER['PHP_SELF']}')");
        break;

    case 'actualizar_siniestro':
        $id       = estandariza_info($_POST["id_siniestro"]);
        $mensaje  = 'Siniestro actualizado correctamente';
        $busqueda = $numero_siniestro;

        // Leer estado anterior antes del UPDATE
        $estado_anterior = '';
        $res = db_query($link, "SELECT estado FROM siniestros WHERE id = '$id'");
        while ($row = db_fetch_object($res)) {
            $estado_anterior = $row->estado;
        }

        // Auto-promoción: si seguía en 'Número pendiente' y ahora llega N° compañía, pasa a 'Abierto'
        if ($estado_anterior === 'Número pendiente' && $numero_siniestro !== '' && $estado === 'Número pendiente') {
            $estado = 'Abierto';
        }

        // NULLIF(...,'')::tipo evita el literal '= NULL' que sql_translate
        // reemplaza por 'IS NULL' y rompería el UPDATE SET.
        $query = "UPDATE siniestros SET
                        numero_siniestro          = '$numero_siniestro',
                        tipo_siniestro            = '$tipo_siniestro',
                        fecha_ocurrencia          = NULLIF('$fecha_ocurrencia', '')::date,
                        fecha_denuncia            = NULLIF('$fecha_denuncia', '')::date,
                        nombre_asegurado          = '$nombre_asegurado',
                        telefono_asegurado        = '$telefono_asegurado',
                        correo_asegurado          = '$correo_asegurado',
                        descripcion               = '$descripcion',
                        observaciones             = '$observaciones',
                        liquidador_nombre         = '$liquidador_nombre',
                        liquidador_telefono       = '$liquidador_telefono',
                        liquidador_correo         = '$liquidador_correo',
                        numero_carpeta_liquidador = '$numero_carpeta_liquidador',
                        patente                   = '$patente',
                        marca                     = '$marca',
                        modelo                    = '$modelo',
                        anio_vehiculo             = NULLIF('$anio_vehiculo', '')::integer,
                        taller_nombre             = '$taller_nombre',
                        taller_telefono           = '$taller_telefono',
                        estado                    = '$estado',
                        presentado                = " . ($presentado ? "TRUE" : "FALSE") . "
                    WHERE id = '$id'";
        db_query($link, $query);

        // Bitácora si el estado cambió
        if ($estado_anterior !== '' && $estado_anterior !== $estado) {
            db_query($link, "INSERT INTO siniestros_bitacora (id_siniestro, estado_anterior, estado_nuevo, usuario, motivo)
                             VALUES ('$id', '$estado_anterior', '$estado', '$usuario', 'Edición')");
        }

        // Resincronizar ítems (DELETE + INSERT) con datos de vehículo por ítem
        db_query($link, "DELETE FROM siniestros_items WHERE id_siniestro = '$id'");
        $items = parse_items_csv($items_seleccionados);
        foreach ($items as $ni) {
            $veh = veh_por_item($ni);
            $anio_sql = ($veh['anio_vehiculo'] !== '' && ctype_digit($veh['anio_vehiculo'])) ? "'" . $veh['anio_vehiculo'] . "'" : "NULL";
            db_query($link, "INSERT INTO siniestros_items (id_siniestro, numero_item, patente, marca, modelo, anio_vehiculo)
                             VALUES ('$id', '$ni', '" . $veh['patente'] . "', '" . $veh['marca'] . "', '" . $veh['modelo'] . "', $anio_sql)
                             ON CONFLICT (id_siniestro, numero_item) DO UPDATE SET
                                patente = EXCLUDED.patente,
                                marca = EXCLUDED.marca,
                                modelo = EXCLUDED.modelo,
                                anio_vehiculo = EXCLUDED.anio_vehiculo");
        }

        // Sincronizar bienes afectados
        $bienes_json = $_POST['bienes_json'] ?? '';
        if ($bienes_json !== '') {
            $bienes = json_decode(stripslashes($bienes_json), true);
            if (is_array($bienes)) {
                sincroniza_bienes($link, $id, $bienes, $usuario);
            }
        }

        db_query($link, "SELECT trazabilidad('$usuario', 'Actualización siniestro', '" .
            str_replace("'", "**", $query) . "', 'siniestros', '$id', '{$_SERVER['PHP_SELF']}')");
        break;

    case 'eliminar_siniestro':
        $id      = estandariza_info($_POST["id_siniestro"]);
        $mensaje = 'Siniestro eliminado correctamente';
        $query   = "UPDATE siniestros SET estado = 'Eliminado' WHERE id = '$id'";
        db_query($link, $query);
        db_query($link, "SELECT trazabilidad('$usuario', 'Eliminación siniestro', '" .
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
