<?php
    if(!isset($_SESSION))
    {
        session_start();
    }

require_once "/home/gestio10/public_html/backend/config.php";
    db_set_charset($link, 'utf8');
    db_select_db($link, DB_NAME);

// Query 1: Pre-cargar relaciones de polizas agrupadas por id_tarea_recurrente
$rel_polizas_sql = "SELECT tr.id_tarea_recurrente,
    json_agg(json_build_object(
        'id_poliza',        p.id::text,
        'numero_poliza',    p.numero_poliza,
        'estado',           p.estado,
        'vigencia_inicial', TO_CHAR(p.vigencia_inicial, 'DD-MM-YYYY'),
        'vigencia_final',   TO_CHAR(p.vigencia_final,   'DD-MM-YYYY')
    ) ORDER BY p.estado, p.vigencia_final) as polizas_json
    FROM tareas_relaciones tr
    INNER JOIN polizas_2 p ON tr.id_relacion = p.id::text
    WHERE tr.base = 'polizas'
    GROUP BY tr.id_tarea_recurrente";
$rel_polizas_result = db_query($link, $rel_polizas_sql);
$polizas_rel_map = array();
while ($row = db_fetch_object($rel_polizas_result)) {
    $polizas_rel_map[$row->id_tarea_recurrente] = json_decode($row->polizas_json, true);
}

// Query 2: Pre-cargar relaciones de clientes agrupadas por id_tarea_recurrente
$rel_clientes_sql = "SELECT tr.id_tarea_recurrente,
    json_agg(json_build_object(
        'id_cliente', c.id::text,
        'nombre',     CONCAT_WS(' ', c.nombre_cliente, c.apellido_paterno, c.apellido_materno),
        'telefono',   c.telefono,
        'correo',     c.correo
    )) as clientes_json
    FROM tareas_relaciones tr
    INNER JOIN clientes c ON tr.id_relacion = c.id::text
    WHERE tr.base = 'clientes'
    GROUP BY tr.id_tarea_recurrente";
$rel_clientes_result = db_query($link, $rel_clientes_sql);
$clientes_rel_map = array();
while ($row = db_fetch_object($rel_clientes_result)) {
    $clientes_rel_map[$row->id_tarea_recurrente] = json_decode($row->clientes_json, true);
}

// Query 3: Pre-cargar relaciones de propuestas agrupadas por id_tarea_recurrente
$rel_propuestas_sql = "SELECT tr.id_tarea_recurrente,
    json_agg(json_build_object(
        'numero_propuesta', pp.numero_propuesta,
        'estado',           pp.estado,
        'vigencia_inicial', TO_CHAR(pp.vigencia_inicial, 'DD-MM-YYYY'),
        'vigencia_final',   TO_CHAR(pp.vigencia_final,   'DD-MM-YYYY')
    ) ORDER BY pp.estado, pp.vigencia_final) as propuestas_json
    FROM tareas_relaciones tr
    INNER JOIN propuesta_polizas pp ON tr.id_relacion = pp.id::text
    WHERE tr.base = 'propuestas'
    GROUP BY tr.id_tarea_recurrente";
$rel_propuestas_result = db_query($link, $rel_propuestas_sql);
$propuestas_rel_map = array();
while ($row = db_fetch_object($rel_propuestas_result)) {
    $propuestas_rel_map[$row->id_tarea_recurrente] = json_decode($row->propuestas_json, true);
}

// Query 4: Tareas recurrentes principales
$resul_tareas = db_query($link, "SELECT a.id, fecha_ingreso, fecha_fin, tarea, estado, prioridad, dia_recordatorio,
    count(b.id) as relaciones,
    sum(CASE WHEN base = 'polizas'    THEN 1 ELSE 0 END) as polizas,
    sum(CASE WHEN base = 'clientes'   THEN 1 ELSE 0 END) as clientes,
    sum(CASE WHEN base = 'propuestas' THEN 1 ELSE 0 END) as propuestas
    FROM tareas_recurrentes a
    LEFT JOIN tareas_relaciones b ON a.id = b.id_tarea_recurrente
    WHERE estado not in ('Cerrado', 'Eliminado')
    GROUP BY a.id, fecha_ingreso, fecha_fin, tarea, estado, prioridad, dia_recordatorio");

$data = array();
while ($tareas = db_fetch_object($resul_tareas)) {
    $tid = $tareas->id;

    // Estado badge
    switch ($tareas->estado) {
        case 'Pendiente':         $estado_sw = 'badge badge-primary';   break;
        case 'Completado':        $estado_sw = 'badge badge-secondary'; break;
        case 'Atrasado':          $estado_sw = 'badge badge-danger';    break;
        case 'Próximo a vencer':  $estado_sw = 'badge badge-warning';   break;
        default:                  $estado_sw = 'badge badge-light';     break;
    }

    // Ensamblar relaciones desde mapas pre-cargados
    $rel = array(
        "relaciones"  => $tareas->relaciones,
        "clientes"    => $tareas->clientes,
        "polizas"     => $tareas->polizas,
        "propuestas"  => $tareas->propuestas
    );

    if (isset($polizas_rel_map[$tid])) {
        $pol_list = $polizas_rel_map[$tid];
        $estado_arr       = array();
        $estado_alerta    = array();
        $vig_inicial      = array();
        $vig_final        = array();
        $num_poliza       = array();
        $id_poliza_arr    = array();
        foreach ($pol_list as $p) {
            switch ($p['estado']) {
                case 'Activo':  $ep = 'badge badge-primary'; break;
                case 'Cerrado': $ep = 'badge badge-dark';    break;
                default:        $ep = 'badge badge-light';   break;
            }
            $estado_arr[]    = $p['estado'];
            $estado_alerta[] = $ep;
            $vig_inicial[]   = $p['vigencia_inicial'];
            $vig_final[]     = $p['vigencia_final'];
            $num_poliza[]    = $p['numero_poliza'];
            $id_poliza_arr[] = $p['id_poliza'];
        }
        $rel = array_merge($rel, array(
            "estado_poliza"       => $estado_arr,
            "estado_poliza_alerta"=> $estado_alerta,
            "vigencia_inicial"    => $vig_inicial,
            "vigencia_final"      => $vig_final,
            "numero_poliza"       => $num_poliza,
            "id_poliza"           => $id_poliza_arr
        ));
    }

    if (isset($clientes_rel_map[$tid])) {
        $cli_list = $clientes_rel_map[$tid];
        $id_cli   = array();
        $nombre   = array();
        $telefono = array();
        $correo   = array();
        foreach ($cli_list as $c) {
            $id_cli[]   = $c['id_cliente'];
            $nombre[]   = $c['nombre'];
            $telefono[] = $c['telefono'];
            $correo[]   = $c['correo'];
        }
        $rel = array_merge($rel, array(
            "id_cliente" => $id_cli,
            "nombre"     => $nombre,
            "telefono"   => $telefono,
            "correo"     => $correo
        ));
    }

    if (isset($propuestas_rel_map[$tid])) {
        $prop_list        = $propuestas_rel_map[$tid];
        $estado_arr       = array();
        $estado_alerta    = array();
        $vig_inicial      = array();
        $vig_final        = array();
        $num_propuesta    = array();
        foreach ($prop_list as $pr) {
            switch ($pr['estado']) {
                case 'Activo':  $ep = 'badge badge-primary'; break;
                case 'Cerrado': $ep = 'badge badge-dark';    break;
                default:        $ep = 'badge badge-light';   break;
            }
            $estado_arr[]   = $pr['estado'];
            $estado_alerta[]= $ep;
            $vig_inicial[]  = $pr['vigencia_inicial'];
            $vig_final[]    = $pr['vigencia_final'];
            $num_propuesta[]= $pr['numero_propuesta'];
        }
        $rel = array_merge($rel, array(
            "estado_propuesta"       => $estado_arr,
            "estado_propuesta_alerta"=> $estado_alerta,
            "vigencia_inicial"       => $vig_inicial,
            "vigencia_final"         => $vig_final,
            "numero_propuesta"       => $num_propuesta
        ));
    }

    $data[] = array_merge(array(
        "id_tarea"        => $tareas->id,
        "fecingreso"      => $tareas->fecha_ingreso,
        "fecha_fin"       => $tareas->fecha_fin,
        "tarea"           => $tareas->tarea,
        "estado"          => $tareas->estado,
        "estado_alerta"   => $estado_sw,
        "dia_recordatorio"=> $tareas->dia_recordatorio,
        "prioridad"       => $tareas->prioridad
    ), $rel);
}

db_close($link);
echo json_encode(array("data" => $data));
?>