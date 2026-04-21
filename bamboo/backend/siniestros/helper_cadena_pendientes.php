<?php
/**
 * Helper de cadena automática de pendientes iniciales de un siniestro.
 * Reunión Adriana 21-abr-2026.
 *
 * Códigos de tarea en `siniestros_pendientes.codigo_tarea`:
 *   - 'compania_entrega_numero'  — al crear sin N°, responsable Compañía, 24h.
 *   - 'liquidador_contacto'      — al recibir N°, responsable Liquidador, 24h.
 *   - 'cliente_entrega'          — al Entregar la de liquidador, responsable Cliente, 4 días.
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
        if ($codigo_tarea_entregada === 'liquidador_contacto') {
            crear_pendiente_auto(
                $link, $id_siniestro, 'cliente_entrega', 'Cliente',
                descripcion_tarea_cliente($ramo), 4, $usuario
            );
        }
    }
}
?>
