<?php
if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";
require_once __DIR__ . "/helper_cadena_pendientes.php";
require_once __DIR__ . "/helper_brevo.php";
require_once __DIR__ . "/../email/render_template.php";

function estandariza_info($d) { return htmlspecialchars(stripslashes(trim($d))); }
function sqlesc($v) { return str_replace("'", "''", $v); }

// Helper: NULL-safe DATE para UPDATE (evita la trampa de =NULL → IS NULL en sql_translate).
function sql_date_or_null($v) {
    $v = trim($v);
    return ($v === '') ? "NULL" : "NULLIF('" . sqlesc($v) . "','')::date";
}
function sql_text_or_null($v) {
    $v = (string)$v;
    return ($v === '') ? "NULL" : "'" . sqlesc($v) . "'";
}

/**
 * Dispara un correo automático según el codigo_tarea que acaba de cerrarse.
 * - cliente_entrega (vehículo) → liquidador (aviso "cliente llevó vehículo").
 * - taller_disponibilidad_repuestos → cliente (aviso "hay repuestos disponibles").
 * Registra el envío en siniestros_notificaciones_enviadas.
 */
function disparar_correo_evento_tarea($link, $id_siniestro, $codigo_tarea, $ramo, $usuario) {
    $es_veh = ramo_es_vehiculo($ramo);
    $plantilla = ''; $destinatario_correo = ''; $destinatario_nombre = '';

    // Cargar datos comunes del siniestro
    $datos = array(
        'numero_siniestro'    => '',
        'nombre_asegurado'    => '',
        'correo_asegurado'    => '',
        'liquidador_nombre'   => '',
        'liquidador_correo'   => '',
        'taller_nombre'       => '',
        'taller_telefono'     => '',
        'taller_correo'       => '',
        'vehiculo_descripcion'=> '',
        'corredor_nombre'     => 'Adriana Sandoval'
    );
    $rs = db_query($link, "SELECT COALESCE(s.numero_siniestro,'')   AS numero_siniestro,
                                  COALESCE(s.nombre_asegurado,'')   AS nombre_asegurado,
                                  COALESCE(s.correo_asegurado,'')   AS correo_asegurado,
                                  COALESCE(s.liquidador_nombre,'')  AS liquidador_nombre,
                                  COALESCE(s.liquidador_correo,'')  AS liquidador_correo
                           FROM siniestros s WHERE s.id='$id_siniestro'");
    while ($r = db_fetch_object($rs)) {
        $datos['numero_siniestro']   = $r->numero_siniestro;
        $datos['nombre_asegurado']   = $r->nombre_asegurado;
        $datos['correo_asegurado']   = $r->correo_asegurado;
        $datos['liquidador_nombre']  = $r->liquidador_nombre;
        $datos['liquidador_correo']  = $r->liquidador_correo;
    }
    // Primer bien propio vehicular (para sacar taller + descripción del vehículo)
    $rs = db_query($link, "SELECT COALESCE(taller_nombre,'')   AS taller_nombre,
                                  COALESCE(taller_telefono,'') AS taller_telefono,
                                  COALESCE(taller_correo,'')   AS taller_correo,
                                  COALESCE(descripcion,'')     AS descripcion,
                                  COALESCE(marca,'')           AS marca,
                                  COALESCE(modelo,'')          AS modelo,
                                  COALESCE(anio_vehiculo::text,'') AS anio,
                                  COALESCE(patente,'')         AS patente
                           FROM siniestros_bienes_afectados
                           WHERE id_siniestro='$id_siniestro' AND tipo='propio' AND categoria='vehiculo'
                           ORDER BY id LIMIT 1");
    while ($r = db_fetch_object($rs)) {
        $datos['taller_nombre']   = $r->taller_nombre;
        $datos['taller_telefono'] = $r->taller_telefono;
        $datos['taller_correo']   = $r->taller_correo;
        $partes = array_filter(array($r->marca, $r->modelo, $r->anio ? '(' . $r->anio . ')' : ''));
        $desc = trim(implode(' ', $partes));
        if ($desc === '') $desc = $r->descripcion;
        if ($r->patente !== '') $desc .= ' — PPU ' . $r->patente;
        $datos['vehiculo_descripcion'] = $desc;
    }

    // Decidir plantilla y destinatario
    if ($codigo_tarea === 'cliente_entrega' && $es_veh) {
        $plantilla = 'siniestro_cliente_llevo_vehiculo';
        $destinatario_correo = $datos['liquidador_correo'];
        $destinatario_nombre = $datos['liquidador_nombre'] ?: 'Liquidador';
    } elseif ($codigo_tarea === 'taller_disponibilidad_repuestos') {
        $plantilla = 'siniestro_taller_disponibilidad_repuestos';
        $destinatario_correo = $datos['correo_asegurado'];
        $destinatario_nombre = $datos['nombre_asegurado'] ?: 'Cliente';
    } else {
        return; // No hay correo automático para esta tarea
    }

    if ($destinatario_correo === '') {
        // No tenemos destinatario, registrar como omitido y salir
        $log_tipo = sqlesc($plantilla);
        $log_usr  = sqlesc($usuario);
        db_query($link, "INSERT INTO siniestros_notificaciones_enviadas
                            (id_siniestro, destinatario_email, destinatario_nombre,
                             asunto, cuerpo, proveedor, estado, error_detalle,
                             tipo, usuario, timestamp)
                         VALUES
                            ('$id_siniestro', '', '', '', '', 'omitido', 'omitido',
                             'Sin correo destinatario', '$log_tipo', '$log_usr', NOW())");
        return;
    }

    $render = render_email_template($link, $plantilla, $datos);
    if ($render === null) return;

    $envio = enviar_correo_brevo($destinatario_correo, $destinatario_nombre,
                                 $render['asunto'], $render['texto']);
    $log_to_email  = sqlesc($destinatario_correo);
    $log_to_nombre = sqlesc($destinatario_nombre);
    $log_asunto    = sqlesc($render['asunto']);
    $log_cuerpo    = sqlesc($render['texto']);
    $log_tipo      = sqlesc($plantilla);
    $log_usr       = sqlesc($usuario);
    if ($envio['ok']) {
        $log_mid = sqlesc($envio['message_id'] ?? '');
        db_query($link, "INSERT INTO siniestros_notificaciones_enviadas
                            (id_siniestro, destinatario_email, destinatario_nombre,
                             asunto, cuerpo, proveedor, proveedor_message_id,
                             estado, tipo, usuario, timestamp)
                         VALUES
                            ('$id_siniestro', '$log_to_email', '$log_to_nombre',
                             '$log_asunto', '$log_cuerpo', 'brevo', '$log_mid',
                             'enviado', '$log_tipo', '$log_usr', NOW())");
    } else {
        $log_err = sqlesc($envio['mensaje'] ?? 'error');
        db_query($link, "INSERT INTO siniestros_notificaciones_enviadas
                            (id_siniestro, destinatario_email, destinatario_nombre,
                             asunto, cuerpo, proveedor, estado, error_detalle,
                             tipo, usuario, timestamp)
                         VALUES
                            ('$id_siniestro', '$log_to_email', '$log_to_nombre',
                             '$log_asunto', '$log_cuerpo', 'brevo', 'error',
                             '$log_err', '$log_tipo', '$log_usr', NOW())");
    }
}

header('Content-Type: application/json; charset=utf-8');

$id            = estandariza_info($_POST['id']            ?? '');
$responsable   = estandariza_info($_POST['responsable']   ?? '');
$descripcion   = estandariza_info($_POST['descripcion']   ?? '');
$estado        = estandariza_info($_POST['estado']        ?? '');
$fecha_entrega = estandariza_info($_POST['fecha_entrega'] ?? '');
$notas         = estandariza_info($_POST['notas']         ?? '');
$usuario       = $_SESSION['username'] ?? '';

// Payload extra por tarea (modal "marcar Entregado" con captura).
$payload       = isset($_POST['payload']) && is_array($_POST['payload']) ? $_POST['payload'] : array();
$payload_bienes= isset($_POST['payload_bienes']) && is_array($_POST['payload_bienes']) ? $_POST['payload_bienes'] : array();

$ok = false; $mensaje = '';
$cliente_completo = false;
$liquidador = array('nombre' => '', 'correo' => '', 'numero_siniestro' => '',
                    'numero_poliza' => '', 'nombre_asegurado' => '',
                    'ramo' => '', 'numero_carpeta_liquidador' => '');

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

if (!ctype_digit($id))                                                              { $mensaje = 'id inválido.'; }
elseif (!in_array($responsable, array('Cliente','Liquidador','Compañía','Taller','Usuario'))) { $mensaje = 'responsable inválido.'; }
elseif ($descripcion === '')                                                        { $mensaje = 'descripcion es obligatoria.'; }
elseif (!in_array($estado, array('Pendiente','Entregado','No aplica')))             { $mensaje = 'estado inválido.'; }
else {
    $resp_anterior = ''; $est_anterior = ''; $id_siniestro = ''; $codigo_tarea = '';
    $rs = db_query($link, "SELECT responsable, estado, id_siniestro, COALESCE(codigo_tarea,'') AS codigo_tarea
                           FROM siniestros_pendientes WHERE id='$id'");
    while ($row = db_fetch_object($rs)) {
        $resp_anterior = $row->responsable;
        $est_anterior  = $row->estado;
        $id_siniestro  = $row->id_siniestro;
        $codigo_tarea  = $row->codigo_tarea;
    }

    if ($id_siniestro === '') {
        $mensaje = 'Pendiente no encontrado.';
    } else {
        // Ramo del siniestro (lo necesitamos varias veces)
        $ramo_sin = '';
        $compania_sin = '';
        $rs_meta = db_query($link, "SELECT COALESCE(s.ramo,'') AS ramo, COALESCE(p.compania,'') AS compania
                                    FROM siniestros s
                                    LEFT JOIN polizas_2 p ON p.id = s.id_poliza
                                    WHERE s.id='$id_siniestro'");
        while ($row = db_fetch_object($rs_meta)) {
            $ramo_sin     = $row->ramo;
            $compania_sin = $row->compania;
        }

        // ============================================================
        // CAPTURA POR TAREA
        // Se ejecuta cuando:
        //   (a) la tarea está pasando de Pendiente a Entregado, o
        //   (b) la tarea ya estaba Entregada y se quieren editar/actualizar
        //       los datos capturados (modo edición vía modal Resolver).
        // ============================================================
        $esta_entregando = ($est_anterior === 'Pendiente' && $estado === 'Entregado');
        $editando_entregada = ($est_anterior === 'Entregado' && $estado === 'Entregado');

        if (($esta_entregando || $editando_entregada) && $codigo_tarea !== '') {
            switch ($codigo_tarea) {

                case 'compania_entrega_numero':
                    // Espera: numero_siniestro + datos del liquidador (id existente o nuevo).
                    // En vehículo también: datos del taller por cada bien vehicular.
                    $numero_siniestro = isset($payload['numero_siniestro']) ? trim($payload['numero_siniestro']) : '';
                    $liq_id           = isset($payload['liquidador_id']) ? preg_replace('/[^0-9]/','',$payload['liquidador_id']) : '';
                    $liq_nombre       = isset($payload['liquidador_nombre']) ? trim($payload['liquidador_nombre']) : '';
                    $liq_telefono     = isset($payload['liquidador_telefono']) ? trim($payload['liquidador_telefono']) : '';
                    $liq_correo       = isset($payload['liquidador_correo']) ? trim($payload['liquidador_correo']) : '';

                    // Si vino un liquidador_id existente, levantar sus datos
                    if ($liq_id !== '') {
                        $rs_l = db_query($link, "SELECT nombre, COALESCE(telefono,'') AS telefono, COALESCE(correo,'') AS correo
                                                 FROM liquidadores WHERE id='$liq_id'");
                        while ($row = db_fetch_object($rs_l)) {
                            $liq_nombre   = $row->nombre;
                            $liq_telefono = $row->telefono;
                            $liq_correo   = $row->correo;
                        }
                    }

                    // Validación mínima — solo N° siniestro obligatorio.
                    // El resto (liquidador, taller) puede llegar incompleto: la compañía
                    // a veces solo manda "puntetú" o no tiene los datos en el momento.
                    if ($numero_siniestro === '') { $mensaje = 'Debe ingresar el N° de siniestro.'; break; }

                    // Si vino nombre de liquidador nuevo (sin id), persistirlo en liquidadores
                    // (dedupe por compania+nombre). Solo si tiene al menos nombre + algo más.
                    if ($liq_id === '' && $liq_nombre !== '' && $compania_sin !== '') {
                        $c  = sqlesc($compania_sin);
                        $ln = sqlesc($liq_nombre);
                        $lt = sql_text_or_null($liq_telefono);
                        $lc = sql_text_or_null($liq_correo);
                        // ON CONFLICT actualiza tel/correo si ya existía con ese nombre/compania
                        db_query($link, "INSERT INTO liquidadores (compania, nombre, telefono, correo)
                                         VALUES ('$c', '$ln', $lt, $lc)
                                         ON CONFLICT (compania, nombre) DO UPDATE
                                         SET telefono = COALESCE(EXCLUDED.telefono, liquidadores.telefono),
                                             correo   = COALESCE(EXCLUDED.correo,   liquidadores.correo)");
                    }

                    // Update siniestros con el N° y los datos del liquidador
                    $ns_sql  = sql_text_or_null($numero_siniestro);
                    $ln_sql  = sql_text_or_null($liq_nombre);
                    $lt_sql  = sql_text_or_null($liq_telefono);
                    $lc_sql  = sql_text_or_null($liq_correo);
                    db_query($link, "UPDATE siniestros SET
                                        numero_siniestro    = $ns_sql,
                                        liquidador_nombre   = $ln_sql,
                                        liquidador_telefono = $lt_sql,
                                        liquidador_correo   = $lc_sql,
                                        estado              = 'Abierto'
                                     WHERE id='$id_siniestro'");

                    // Vehículo: además persistir el taller por bien
                    if (ramo_es_vehiculo($ramo_sin)) {
                        foreach ($payload_bienes as $id_bien => $datos) {
                            $id_bien = preg_replace('/[^0-9]/','',$id_bien);
                            if ($id_bien === '') continue;
                            $tn = isset($datos['taller_nombre'])   ? trim($datos['taller_nombre'])   : '';
                            $tt = isset($datos['taller_telefono']) ? trim($datos['taller_telefono']) : '';
                            $tc = isset($datos['taller_correo'])   ? trim($datos['taller_correo'])   : '';
                            $tn_sql = sql_text_or_null($tn);
                            $tt_sql = sql_text_or_null($tt);
                            $tc_sql = sql_text_or_null($tc);
                            db_query($link, "UPDATE siniestros_bienes_afectados SET
                                                taller_nombre   = COALESCE($tn_sql, taller_nombre),
                                                taller_telefono = COALESCE($tt_sql, taller_telefono),
                                                taller_correo   = COALESCE($tc_sql, taller_correo)
                                             WHERE id='$id_bien' AND id_siniestro='$id_siniestro'");
                        }
                    }
                    break;

                case 'liquidador_contacto':
                    // Captura opcional del N° de carpeta del liquidador (cuando ya lo entregó).
                    $ncl = isset($payload['numero_carpeta_liquidador']) ? trim($payload['numero_carpeta_liquidador']) : '';
                    if ($ncl !== '') {
                        $ncl_sql = sql_text_or_null($ncl);
                        db_query($link, "UPDATE siniestros SET numero_carpeta_liquidador = $ncl_sql
                                         WHERE id='$id_siniestro'");
                    }
                    break;

                case 'liquidador_accion':
                    if (ramo_es_vehiculo($ramo_sin)) {
                        // Por bien: fecha orden reparación + flag importación + obs
                        foreach ($payload_bienes as $id_bien => $datos) {
                            $id_bien = preg_replace('/[^0-9]/','',$id_bien);
                            if ($id_bien === '') continue;
                            $fo  = sql_date_or_null($datos['liquidador_fecha_orden_reparacion'] ?? '');
                            $imp = isset($datos['importacion_repuestos']) && $datos['importacion_repuestos'] ? 'TRUE' : 'FALSE';
                            $obs = sql_text_or_null($datos['importacion_repuestos_obs'] ?? '');
                            db_query($link, "UPDATE siniestros_bienes_afectados SET
                                                liquidador_fecha_orden_reparacion = $fo,
                                                importacion_repuestos             = $imp,
                                                importacion_repuestos_obs         = $obs
                                             WHERE id='$id_bien' AND id_siniestro='$id_siniestro'");
                        }
                    } else {
                        // No-vehículo: fecha de generación del finiquito (aplica a todos los bienes propios)
                        $ff = sql_date_or_null($payload['liquidador_fecha_finiquito'] ?? '');
                        db_query($link, "UPDATE siniestros_bienes_afectados SET liquidador_fecha_finiquito = $ff
                                         WHERE id_siniestro='$id_siniestro' AND tipo='propio'");
                    }
                    break;

                case 'cliente_ingreso_taller':
                    foreach ($payload_bienes as $id_bien => $datos) {
                        $id_bien = preg_replace('/[^0-9]/','',$id_bien);
                        if ($id_bien === '') continue;
                        $fi = sql_date_or_null($datos['cliente_fecha_ingreso_taller'] ?? '');
                        db_query($link, "UPDATE siniestros_bienes_afectados SET cliente_fecha_ingreso_taller = $fi
                                         WHERE id='$id_bien' AND id_siniestro='$id_siniestro'");
                    }
                    break;

                case 'cliente_firma_finiquito':
                    $ff = sql_date_or_null($payload['cliente_fecha_firma_finiquito'] ?? '');
                    db_query($link, "UPDATE siniestros_bienes_afectados SET cliente_fecha_firma_finiquito = $ff
                                     WHERE id_siniestro='$id_siniestro' AND tipo='propio'");
                    break;

                case 'taller_fecha_entrega':
                    foreach ($payload_bienes as $id_bien => $datos) {
                        $id_bien = preg_replace('/[^0-9]/','',$id_bien);
                        if ($id_bien === '') continue;
                        $fe = sql_date_or_null($datos['taller_fecha_compromiso_entrega'] ?? '');
                        db_query($link, "UPDATE siniestros_bienes_afectados SET taller_fecha_compromiso_entrega = $fe
                                         WHERE id='$id_bien' AND id_siniestro='$id_siniestro'");
                    }
                    break;

                case 'liquidador_envio_compania':
                    $fec = sql_date_or_null($payload['liquidador_fecha_envio_compania'] ?? '');
                    db_query($link, "UPDATE siniestros SET liquidador_fecha_envio_compania = $fec
                                     WHERE id='$id_siniestro'");
                    if (!ramo_es_vehiculo($ramo_sin)) {
                        $cn = isset($payload['compania_contacto_nombre']) ? trim($payload['compania_contacto_nombre']) : '';
                        $cm = isset($payload['compania_contacto_mail']) ? trim($payload['compania_contacto_mail']) : '';
                        if ($cn !== '' || $cm !== '') {
                            $cn_sql = sql_text_or_null($cn);
                            $cm_sql = sql_text_or_null($cm);
                            db_query($link, "UPDATE siniestros SET
                                                compania_contacto_nombre = COALESCE($cn_sql, compania_contacto_nombre),
                                                compania_contacto_mail   = COALESCE($cm_sql, compania_contacto_mail)
                                             WHERE id='$id_siniestro'");
                        }
                    }
                    break;

                case 'compania_pago':
                    $fp = sql_date_or_null($payload['compania_fecha_pago'] ?? '');
                    db_query($link, "UPDATE siniestros SET compania_fecha_pago = $fp WHERE id='$id_siniestro'");
                    // El cierre del siniestro lo hace cerrar_siniestro_por_pago dentro de promover_cadena_al_entregar.
                    break;

                // cliente_entrega y otros: no requieren persistencia adicional (usa notas)
            }

            if ($mensaje !== '') {
                // Si hubo error de validación de captura, no marcar entregado.
                db_close($link);
                echo json_encode(array('ok' => false, 'mensaje' => $mensaje));
                exit;
            }
        }

        // ============================================================
        // UPDATE estándar del pendiente (estado, fecha_entrega, etc.)
        // ============================================================
        $d  = sqlesc($descripcion);
        $n  = sqlesc($notas);
        $r  = sqlesc($responsable);
        $e  = sqlesc($estado);
        // fecha_entrega ahora es timestamp. Reglas:
        // - Si está pasando a Entregado y la fecha viene SIN hora (input date) o vacía:
        //   usar NOW() — robusto frente a desfase de zona horaria entre navegador y server.
        // - Si viene una fecha distinta a la actual (edición tradicional): parsear como timestamp.
        // - Si el pendiente ya estaba Entregado y la fecha (YYYY-MM-DD) no cambió, preservar
        //   la hora original para no degradar a 00:00 al editar otros campos.
        $tiene_hora = (strpos($fecha_entrega, ':') !== false);
        if ($esta_entregando && ($fecha_entrega === '' || !$tiene_hora)) {
            $fe = "NOW()";
        } elseif ($fecha_entrega !== '') {
            if ($est_anterior === 'Entregado' && !$tiene_hora) {
                $fe = "CASE
                          WHEN to_char(fecha_entrega, 'YYYY-MM-DD') = '" . sqlesc($fecha_entrega) . "'
                          THEN fecha_entrega
                          ELSE NULLIF('" . sqlesc($fecha_entrega) . "','')::timestamp
                       END";
            } else {
                $fe = "NULLIF('" . sqlesc($fecha_entrega) . "','')::timestamp";
            }
        } else {
            $fe = "NULL";
        }
        db_query($link, "UPDATE siniestros_pendientes SET
                            responsable   = '$r',
                            descripcion   = '$d',
                            estado        = '$e',
                            fecha_entrega = $fe,
                            notas         = '$n',
                            updated_at    = NOW()
                         WHERE id = '$id'");

        if ($est_anterior !== $estado || $resp_anterior !== $responsable) {
            $accion = ($est_anterior !== $estado) ? 'Cambio estado' : 'Cambio responsable';
            db_query($link, "INSERT INTO siniestros_pendientes_bitacora
                                (id_pendiente, accion, estado_anterior, estado_nuevo,
                                 responsable_anterior, responsable_nuevo, usuario)
                             VALUES
                                ('$id', '" . sqlesc($accion) . "',
                                 " . ($est_anterior !== $estado ? "'" . sqlesc($est_anterior) . "'" : "NULL") . ",
                                 " . ($est_anterior !== $estado ? "'$e'" : "NULL") . ",
                                 " . ($resp_anterior !== $responsable ? "'" . sqlesc($resp_anterior) . "'" : "NULL") . ",
                                 " . ($resp_anterior !== $responsable ? "'$r'" : "NULL") . ",
                                 '" . sqlesc($usuario) . "')");
        }

        // Cadena automática: al Entregar una tarea con código conocido, disparar la siguiente.
        // Si fue compania_entrega_numero, además promover_al_liquidador (que cierra esa tarea
        // si no se cerró ya y crea liquidador_contacto). Para el resto, promover_cadena_al_entregar
        // se encarga.
        if ($esta_entregando && $codigo_tarea !== '') {
            if ($codigo_tarea === 'compania_entrega_numero') {
                // promover_al_liquidador requiere que numero_siniestro esté en BD; ya lo seteamos arriba.
                promover_al_liquidador($link, $id_siniestro, $ramo_sin, $usuario);
            } else {
                promover_cadena_al_entregar($link, $id_siniestro, $codigo_tarea, $ramo_sin, $usuario);
            }
            // Correos automáticos asociados al cierre de la tarea (no bloquean si fallan).
            @disparar_correo_evento_tarea($link, $id_siniestro, $codigo_tarea, $ramo_sin, $usuario);
        }

        // ¿Se acaba de cerrar el último pendiente del Cliente?
        if ($esta_entregando && $resp_anterior === 'Cliente') {
            $restantes = 0;
            $rs2 = db_query($link, "SELECT COUNT(*) AS n FROM siniestros_pendientes
                                    WHERE id_siniestro='$id_siniestro' AND responsable='Cliente' AND estado='Pendiente'");
            while ($row = db_fetch_object($rs2)) { $restantes = (int)$row->n; }
            if ($restantes === 0) {
                $rs3 = db_query($link, "SELECT liquidador_nombre, liquidador_correo,
                                               COALESCE(numero_siniestro,'') AS ns,
                                               numero_poliza, nombre_asegurado,
                                               COALESCE(ramo,'') AS ramo,
                                               COALESCE(numero_carpeta_liquidador,'') AS ncl
                                        FROM siniestros WHERE id='$id_siniestro'");
                while ($row = db_fetch_object($rs3)) {
                    $cliente_completo = true;
                    $liquidador['nombre']           = $row->liquidador_nombre;
                    $liquidador['correo']           = $row->liquidador_correo;
                    $liquidador['numero_siniestro'] = $row->ns;
                    $liquidador['numero_poliza']    = $row->numero_poliza;
                    $liquidador['nombre_asegurado'] = $row->nombre_asegurado;
                    $liquidador['ramo']             = $row->ramo;
                    $liquidador['numero_carpeta_liquidador'] = $row->ncl;
                }
            }
        }

        db_query($link, "SELECT trazabilidad('" . sqlesc($usuario) . "', 'Actualización pendiente',
                            'ID: $id', 'siniestros_pendientes', '$id', '{$_SERVER['PHP_SELF']}')");
        $ok = true; $mensaje = 'Pendiente actualizado.';
    }
}

db_close($link);
echo json_encode(array(
    'ok' => $ok,
    'mensaje' => $mensaje,
    'cliente_completo' => $cliente_completo,
    'liquidador' => $liquidador
));
?>
