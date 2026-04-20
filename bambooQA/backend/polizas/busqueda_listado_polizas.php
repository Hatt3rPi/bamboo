<?php
    if(!isset($_SESSION))
    {
        session_start();
    }

require_once "/home/gestio10/public_html/backend/config.php";
    db_set_charset($link, 'utf8');
    db_select_db($link, DB_NAME);

// Query 1: Items agrupados por poliza con json_agg
$items_sql = "SELECT a.numero_poliza,
    COUNT(*) as total_items,
    SUM(a.prima_afecta) as sum_prima_afecta,
    SUM(a.prima_exenta) as sum_prima_exenta,
    SUM(a.prima_neta) as sum_prima_neta,
    SUM(a.prima_bruta_anual) as sum_prima_bruta,
    json_agg(json_build_object(
        'numero_item', a.numero_item::text,
        'materia_asegurada', a.materia_asegurada,
        'patente_ubicacion', a.patente_ubicacion,
        'cobertura', a.cobertura,
        'deducible', a.deducible,
        'tasa_afecta', CONCAT_WS(' ', format_de(a.tasa_afecta, 2), '%'),
        'tasa_exenta', CONCAT_WS(' ', format_de(a.tasa_exenta, 2), '%'),
        'prima_afecta', CONCAT_WS(' ', p.moneda_poliza, format_de(a.prima_afecta, 2)),
        'prima_exenta', CONCAT_WS(' ', p.moneda_poliza, format_de(a.prima_exenta, 2)),
        'prima_neta', CONCAT_WS(' ', p.moneda_poliza, format_de(a.prima_neta, 2)),
        'prima_bruta', CONCAT_WS(' ', p.moneda_poliza, format_de(a.prima_bruta_anual, 2)),
        'monto_asegurado', CONCAT_WS(' ', p.moneda_poliza, format_de(a.monto_asegurado, 2)),
        'venc_gtia', a.venc_gtia::text,
        'nom_clienteA', CONCAT_WS(' ', c.nombre_cliente, c.apellido_paterno, c.apellido_materno),
        'rut_clienteA', CONCAT_WS('-', c.rut_sin_dv, c.dv),
        'telefonoA', c.telefono,
        'correoA', c.correo
    ) ORDER BY a.numero_item) as items_json,
    string_agg(COALESCE(' - ' || a.patente_ubicacion, ''), '' ORDER BY a.numero_item) as consolidado_patentes
    FROM items a
    LEFT JOIN clientes c ON a.rut_asegurado = c.rut_sin_dv AND c.rut_sin_dv IS NOT NULL
    LEFT JOIN polizas_2 p ON a.numero_poliza = p.numero_poliza
    GROUP BY a.numero_poliza";
$items_result = db_query($link, $items_sql);
$items_map = array();
while ($irow = db_fetch_object($items_result)) {
    $items_map[$irow->numero_poliza] = $irow;
}

// Query 2: Endosos agrupados por poliza
$endosos_sql = "SELECT e.id_poliza,
    json_agg(json_build_object(
        'numero_endoso', e.numero_endoso,
        'tipo_endoso', e.tipo_endoso,
        'descripcion_endoso', e.descripcion_endoso,
        'dice', e.dice,
        'debe_decir', e.debe_decir,
        'vigencia_inicial', e.vigencia_inicial::text,
        'vigencia_final', e.vigencia_final::text,
        'fecha_ingreso_endoso', e.fecha_ingreso_endoso::text,
        'fecha_prorroga', e.fecha_prorroga::text
    )) as endosos_json,
    COUNT(*) as nro_endosos
    FROM endosos e
    GROUP BY e.id_poliza";
$endosos_result = db_query($link, $endosos_sql);
$endosos_map = array();
while ($erow = db_fetch_object($endosos_result)) {
    $endosos_map[$erow->id_poliza] = $erow;
}

// Query 3: Siniestros agrupados por poliza (con ítems afectados + señales de atraso)
$siniestros_sql = "SELECT s.id_poliza,
    COUNT(*) as total_siniestros,
    SUM(CASE
          WHEN (s.fecha_ingreso < (NOW() - INTERVAL '24 hours')
                AND (COALESCE(s.numero_siniestro,'')='' OR COALESCE(s.liquidador_nombre,'')=''))
            OR COALESCE(sp.pendientes_cliente,0) > 0
            OR COALESCE(sp.pendientes_liquidador,0) > 0
            OR COALESCE(sp.pendientes_compania,0) > 0
          THEN 1 ELSE 0 END) as siniestros_con_atraso,
    json_agg(json_build_object(
        'id_siniestro', s.id::text,
        'numero_siniestro', COALESCE(s.numero_siniestro, ''),
        'estado', s.estado,
        'fecha_ocurrencia', s.fecha_ocurrencia::text,
        'tipo_siniestro', COALESCE(s.tipo_siniestro, ''),
        'presentado', s.presentado,
        'items_afectados', COALESCE((
            SELECT string_agg(si.numero_item::text, ', ' ORDER BY si.numero_item)
            FROM siniestros_items si WHERE si.id_siniestro = s.id
        ), ''),
        'alarma_24h', (s.fecha_ingreso < (NOW() - INTERVAL '24 hours')
                       AND (COALESCE(s.numero_siniestro,'')='' OR COALESCE(s.liquidador_nombre,'')='')),
        'pendientes_cliente',    COALESCE(sp.pendientes_cliente, 0),
        'pendientes_liquidador', COALESCE(sp.pendientes_liquidador, 0),
        'pendientes_compania',   COALESCE(sp.pendientes_compania, 0)
    ) ORDER BY s.fecha_ocurrencia DESC NULLS LAST) as siniestros_json
    FROM siniestros s
    LEFT JOIN (
        SELECT id_siniestro,
               SUM(CASE WHEN responsable='Cliente'    AND estado='Pendiente' THEN 1 ELSE 0 END) as pendientes_cliente,
               SUM(CASE WHEN responsable='Liquidador' AND estado='Pendiente' THEN 1 ELSE 0 END) as pendientes_liquidador,
               SUM(CASE WHEN responsable='Compañía'  AND estado='Pendiente' THEN 1 ELSE 0 END) as pendientes_compania
        FROM siniestros_pendientes GROUP BY id_siniestro
    ) sp ON sp.id_siniestro = s.id
    WHERE COALESCE(s.estado, '') <> 'Eliminado'
    GROUP BY s.id_poliza";
$siniestros_result = db_query($link, $siniestros_sql);
$siniestros_map = array();
while ($srow = db_fetch_object($siniestros_result)) {
    $siniestros_map[$srow->id_poliza] = $srow;
}

// Query 4: Polizas principales
$sql = "SELECT a.numero_poliza, a.estado, a.tipo_propuesta, a.moneda_poliza,
    a.vigencia_inicial, a.vigencia_final, a.compania, a.ramo,
    DATE_FORMAT(a.vigencia_final, '%m-%Y') as anomes_final,
    DATE_FORMAT(a.vigencia_inicial, '%m-%Y') as anomes_inicial,
    CONCAT_WS(' ', b.nombre_cliente, b.apellido_paterno, b.apellido_materno) as \"nom_clienteP\",
    CONCAT_WS('-', b.rut_sin_dv, b.dv) as \"rut_clienteP\",
    b.telefono as \"telefonoP\", b.correo as \"correoP\",
    a.id as id_poliza, b.id as \"idP\", b.grupo, b.referido,
    a.fech_cancela, a.motivo_cancela
    FROM polizas_2 a
    LEFT JOIN clientes b ON a.rut_proponente = b.rut_sin_dv AND b.rut_sin_dv IS NOT NULL
    WHERE a.estado <> 'Rechazado'";
$resultado = db_query($link, $sql);

$data = array();
while ($row = db_fetch_object($resultado)) {
    $np = $row->numero_poliza;
    $ip = $row->id_poliza;

    // Items desde el mapa pre-cargado
    $has_items = isset($items_map[$np]);
    $items = $has_items ? json_decode($items_map[$np]->items_json, true) : array();
    $total_items = $has_items ? $items_map[$np]->total_items : 0;
    $consolidado = $has_items ? $items_map[$np]->consolidado_patentes : '';
    $sum_afecta = $has_items ? $items_map[$np]->sum_prima_afecta : 0;
    $sum_exenta = $has_items ? $items_map[$np]->sum_prima_exenta : 0;
    $sum_neta = $has_items ? $items_map[$np]->sum_prima_neta : 0;
    $sum_bruta = $has_items ? $items_map[$np]->sum_prima_bruta : 0;

    // Endosos desde el mapa pre-cargado
    $has_endosos = isset($endosos_map[$ip]);
    $endosos = $has_endosos ? json_decode($endosos_map[$ip]->endosos_json, true) : array();
    $nro_endosos = $has_endosos ? $endosos_map[$ip]->nro_endosos : 0;

    // Siniestros desde el mapa pre-cargado
    $has_siniestros = isset($siniestros_map[$ip]);
    $siniestros = $has_siniestros ? json_decode($siniestros_map[$ip]->siniestros_json, true) : array();
    $total_siniestros = $has_siniestros ? $siniestros_map[$ip]->total_siniestros : 0;
    $siniestros_con_atraso = $has_siniestros ? (int)$siniestros_map[$ip]->siniestros_con_atraso : 0;

    $data[] = array(
        "numero_poliza" => $row->numero_poliza,
        "estado" => $row->estado,
        "tipo_propuesta" => $row->tipo_propuesta,
        "moneda_poliza" => $row->moneda_poliza,
        "vigencia_inicial" => $row->vigencia_inicial,
        "vigencia_final" => $row->vigencia_final,
        "compania" => $row->compania,
        "ramo" => $row->ramo,
        "total_prima_afecta" => $row->moneda_poliza . ' ' . number_format((float)$sum_afecta, 2, ',', '.'),
        "total_prima_exenta" => $row->moneda_poliza . ' ' . number_format((float)$sum_exenta, 2, ',', '.'),
        "total_prima_neta" => $row->moneda_poliza . ' ' . number_format((float)$sum_neta, 2, ',', '.'),
        "total_prima_bruta" => $row->moneda_poliza . ' ' . number_format((float)$sum_bruta, 2, ',', '.'),
        "nom_clienteP" => $row->nom_clienteP,
        "rut_clienteP" => $row->rut_clienteP,
        "telefonoP" => $row->telefonoP,
        "correoP" => $row->correoP,
        "idP" => $row->idP,
        "grupo" => $row->grupo,
        "referido" => $row->referido,
        "id_poliza" => $row->id_poliza,
        "anomes_final" => $row->anomes_final,
        "anomes_inicial" => $row->anomes_inicial,
        "items" => $items,
        "nro_endosos" => $nro_endosos,
        "endosos" => $endosos,
        "fecha_cancelacion" => $row->fech_cancela,
        "motivo_cancelacion" => $row->motivo_cancela,
        "consolidado_patentes" => $consolidado,
        "total_items" => $total_items,
        "siniestros" => $siniestros,
        "total_siniestros" => $total_siniestros,
        "siniestros_con_atraso" => $siniestros_con_atraso
    );
}

db_close($link);
echo json_encode(array("data" => $data));
?>
