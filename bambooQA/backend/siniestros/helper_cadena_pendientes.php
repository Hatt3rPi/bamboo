<?php
/**
 * Helper de cadena automática de pendientes iniciales de un siniestro.
 * Reuniones Adriana 21-abr-2026 + 22-abr-2026.
 *
 * Códigos de tarea en `siniestros_pendientes.codigo_tarea`:
 *   - 'compania_entrega_numero'   — al crear sin N°, resp. Compañía, 24h.
 *   - 'liquidador_contacto'       — al recibir N°, resp. Liquidador, 24h.
 *   - 'cliente_entrega'           — tras liquidador_contacto, resp. Cliente, 4 días.
 *   - 'liquidador_accion'         — tras cliente_entrega, resp. Liquidador, 24h.
 *                                   (finiquito si no-veh, orden reparación si veh)
 *   - 'cliente_ingreso_taller'    — (veh) tras liquidador_accion, resp. Cliente, 2 días.
 *   - 'taller_fecha_entrega'      — (veh) tras cliente_ingreso_taller, resp. Taller, 5 días.
 *   - 'cliente_firma_finiquito'   — (no-veh) tras liquidador_accion, resp. Cliente, 4 días.
 *   - 'liquidador_envio_compania' — tras taller_fecha_entrega / cliente_firma_finiquito,
 *                                   resp. Liquidador, 24h.
 *   - 'compania_pago'             — tras liquidador_envio_compania, resp. Compañía, 3 días.
 *                                   Al Entregar, se cierra el siniestro automáticamente.
 *
 * Las funciones reciben $link ya inicializado (db_select_db + charset).
 * Asumen que `sqlesc` y `estandariza_info` ya están declarados en el caller.
 */

if (!function_exists('ramo_es_vehiculo')) {
    function ramo_es_vehiculo($ramo) {
        $u = strtoupper($ramo ?? '');
        return (strpos($u, 'VEH') !== false || strpos($u, 'AUTO') !== false);
    }
}

if (!function_exists('pendiente_existe')) {
    function pendiente_existe($link, $id_siniestro, $codigo) {
        $c = str_replace("'", "''", $codigo);
        $res = db_query($link, "SELECT id FROM siniestros_pendientes
                                WHERE id_siniestro='$id_siniestro' AND codigo_tarea='$c' LIMIT 1");
        while ($row = db_fetch_object($res)) { return (int)$row->id; }
        return 0;
    }
}

if (!function_exists('crear_pendiente_auto')) {
    function crear_pendiente_auto($link, $id_siniestro, $codigo, $responsable, $descripcion, $dias_alarma, $usuario) {
        if (pendiente_existe($link, $id_siniestro, $codigo)) return 0;
        $c  = str_replace("'", "''", $codigo);
        $r  = str_replace("'", "''", $responsable);
        $d  = str_replace("'", "''", $descripcion);
        $u  = str_replace("'", "''", $usuario);
        $da = (int)$dias_alarma;
        db_query($link, "INSERT INTO siniestros_pendientes
                            (id_siniestro, responsable, descripcion, estado,
                             dias_alarma, auto_generada, codigo_tarea, usuario_creacion)
                         VALUES
                            ('$id_siniestro', '$r', '$d', 'Pendiente', $da, TRUE, '$c', '$u')");
        $id_nuevo = 0;
        $rs = db_query($link, "SELECT currval('siniestros_pendientes_id_seq') AS id");
        while ($row = db_fetch_object($rs)) { $id_nuevo = (int)$row->id; }
        if ($id_nuevo) {
            db_query($link, "INSERT INTO siniestros_pendientes_bitacora
                                (id_pendiente, accion, estado_anterior, estado_nuevo,
                                 responsable_anterior, responsable_nuevo, usuario)
                             VALUES
                                ('$id_nuevo', 'Auto-creación', NULL, 'Pendiente', NULL, '$r', '$u')");
        }
        return $id_nuevo;
    }
}

if (!function_exists('descripcion_tarea_compania')) {
    function descripcion_tarea_compania($ramo) {
        return ramo_es_vehiculo($ramo)
            ? 'Compañía debe entregar N° de siniestro, liquidador asignado y taller.'
            : 'Compañía debe entregar N° de siniestro y liquidador asignado.';
    }
}

if (!function_exists('descripcion_tarea_liquidador')) {
    function descripcion_tarea_liquidador($ramo) {
        return ramo_es_vehiculo($ramo)
            ? 'Liquidador toma contacto con el cliente.'
            : 'Liquidador pide antecedentes al cliente.';
    }
}

if (!function_exists('descripcion_tarea_cliente')) {
    function descripcion_tarea_cliente($ramo) {
        return ramo_es_vehiculo($ramo)
            ? 'Cliente lleva el vehículo al taller designado.'
            : 'Cliente entrega los antecedentes solicitados.';
    }
}

if (!function_exists('descripcion_tarea_liquidador_accion')) {
    function descripcion_tarea_liquidador_accion($ramo) {
        return ramo_es_vehiculo($ramo)
            ? 'Liquidador emite la orden de reparación.'
            : 'Liquidador genera el finiquito.';
    }
}

if (!function_exists('descripcion_tarea_cliente_ingreso_taller')) {
    function descripcion_tarea_cliente_ingreso_taller() {
        return 'Cliente debe avisar el día de ingreso del vehículo al taller.';
    }
}

if (!function_exists('descripcion_tarea_taller_fecha')) {
    function descripcion_tarea_taller_fecha() {
        return 'Taller debe confirmar la fecha de entrega del vehículo.';
    }
}

if (!function_exists('descripcion_tarea_cliente_firma')) {
    function descripcion_tarea_cliente_firma() {
        return 'Cliente debe firmar y devolver el finiquito.';
    }
}

if (!function_exists('descripcion_tarea_liquidador_envio')) {
    function descripcion_tarea_liquidador_envio() {
        return 'Liquidador debe confirmar el envío del finiquito a la compañía.';
    }
}

if (!function_exists('descripcion_tarea_compania_pago')) {
    function descripcion_tarea_compania_pago() {
        return 'Compañía debe confirmar fecha de indemnización/transferencia al cliente.';
    }
}

if (!function_exists('cerrar_siniestro_por_pago')) {
    function cerrar_siniestro_por_pago($link, $id_siniestro, $usuario) {
        $u = str_replace("'", "''", $usuario);
        $estado_anterior = '';
        $rs = db_query($link, "SELECT COALESCE(estado,'') AS estado
                               FROM siniestros WHERE id='$id_siniestro'");
        while ($row = db_fetch_object($rs)) { $estado_anterior = $row->estado; }
        if ($estado_anterior === 'Cerrado') { return; }

        db_query($link, "UPDATE siniestros SET estado='Cerrado' WHERE id='$id_siniestro'");
        db_query($link, "INSERT INTO siniestros_bitacora
                            (id_siniestro, estado_anterior, estado_nuevo, usuario, motivo)
                         VALUES
                            ('$id_siniestro',
                             " . ($estado_anterior !== '' ? "'" . str_replace("'","''",$estado_anterior) . "'" : "NULL") . ",
                             'Cerrado',
                             '" . ($u !== '' ? $u : 'sistema') . "',
                             'Cierre automático: compañía confirmó pago')");
    }
}

/**
 * Llamado al crear un siniestro. Decide qué tarea auto-inicial crear:
 * - Sin N° siniestro → tarea Compañía.
 * - Con N° siniestro ya al crear → tarea Liquidador directamente.
 */
if (!function_exists('bootstrap_cadena_siniestro')) {
    function bootstrap_cadena_siniestro($link, $id_siniestro, $ramo, $numero_siniestro, $usuario) {
        if (trim($numero_siniestro) === '') {
            crear_pendiente_auto(
                $link, $id_siniestro, 'compania_entrega_numero', 'Compañía',
                descripcion_tarea_compania($ramo), 1, $usuario
            );
        } else {
            crear_pendiente_auto(
                $link, $id_siniestro, 'liquidador_contacto', 'Liquidador',
                descripcion_tarea_liquidador($ramo), 1, $usuario
            );
        }
    }
}

/**
 * Llamado cuando se actualiza un siniestro y `numero_siniestro` deja de estar vacío.
 * Cierra la tarea compañía (si existe) y crea la tarea liquidador.
 */
if (!function_exists('promover_al_liquidador')) {
    function promover_al_liquidador($link, $id_siniestro, $ramo, $usuario) {
        $id_comp = pendiente_existe($link, $id_siniestro, 'compania_entrega_numero');
        if ($id_comp > 0) {
            $u = str_replace("'", "''", $usuario);
            db_query($link, "UPDATE siniestros_pendientes
                             SET estado='Entregado', fecha_entrega=CURRENT_DATE, updated_at=NOW()
                             WHERE id='$id_comp' AND estado='Pendiente'");
            db_query($link, "INSERT INTO siniestros_pendientes_bitacora
                                (id_pendiente, accion, estado_anterior, estado_nuevo, usuario)
                             VALUES
                                ('$id_comp', 'Auto-entregado (ingreso N° siniestro)', 'Pendiente', 'Entregado', '$u')");
        }
        crear_pendiente_auto(
            $link, $id_siniestro, 'liquidador_contacto', 'Liquidador',
            descripcion_tarea_liquidador($ramo), 1, $usuario
        );
    }
}

/**
 * Llamado desde actualiza_pendiente cuando una tarea con código conocido pasa a Entregado.
 * Dispara la siguiente en la cadena.
 */
if (!function_exists('promover_cadena_al_entregar')) {
    function promover_cadena_al_entregar($link, $id_siniestro, $codigo_tarea_entregada, $ramo, $usuario) {
        $es_veh = ramo_es_vehiculo($ramo);

        switch ($codigo_tarea_entregada) {
            case 'liquidador_contacto':
                crear_pendiente_auto(
                    $link, $id_siniestro, 'cliente_entrega', 'Cliente',
                    descripcion_tarea_cliente($ramo), 4, $usuario
                );
                break;

            case 'cliente_entrega':
                crear_pendiente_auto(
                    $link, $id_siniestro, 'liquidador_accion', 'Liquidador',
                    descripcion_tarea_liquidador_accion($ramo), 1, $usuario
                );
                break;

            case 'liquidador_accion':
                if ($es_veh) {
                    crear_pendiente_auto(
                        $link, $id_siniestro, 'cliente_ingreso_taller', 'Cliente',
                        descripcion_tarea_cliente_ingreso_taller(), 2, $usuario
                    );
                } else {
                    crear_pendiente_auto(
                        $link, $id_siniestro, 'cliente_firma_finiquito', 'Cliente',
                        descripcion_tarea_cliente_firma(), 4, $usuario
                    );
                }
                break;

            case 'cliente_ingreso_taller':
                crear_pendiente_auto(
                    $link, $id_siniestro, 'taller_fecha_entrega', 'Taller',
                    descripcion_tarea_taller_fecha(), 5, $usuario
                );
                break;

            case 'taller_fecha_entrega':
            case 'cliente_firma_finiquito':
                crear_pendiente_auto(
                    $link, $id_siniestro, 'liquidador_envio_compania', 'Liquidador',
                    descripcion_tarea_liquidador_envio(), 1, $usuario
                );
                break;

            case 'liquidador_envio_compania':
                crear_pendiente_auto(
                    $link, $id_siniestro, 'compania_pago', 'Compañía',
                    descripcion_tarea_compania_pago(), 3, $usuario
                );
                break;

            case 'compania_pago':
                cerrar_siniestro_por_pago($link, $id_siniestro, $usuario);
                break;
        }
    }
}
?>
