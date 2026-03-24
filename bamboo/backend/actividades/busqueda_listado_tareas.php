<?php
    if(!isset($_SESSION))
    {
        session_start();
    }
$resultado =$codigo=$conta='';
require_once "/home/gestio10/public_html/backend/config.php";
    db_set_charset($link, 'utf8');
    db_select_db($link, DB_NAME);
    $codigo='{
      "data": [';
    $conta=0;

    // ── Pre-carga 1: relaciones de pólizas ──────────────────────────────────
    $map_polizas = array();
    $resul_rel_pol = db_query($link,
        "SELECT tr.id_tarea, p.id as id_poliza, p.estado,
                DATE_FORMAT(p.vigencia_inicial,'%d-%m-%Y') as vigencia_inicial,
                DATE_FORMAT(p.vigencia_final,'%d-%m-%Y') as vigencia_final,
                p.numero_poliza
         FROM tareas_relaciones tr
         JOIN polizas_2 p ON tr.id_relacion = p.id
         WHERE tr.base = 'polizas'
           AND tr.id_tarea IN (SELECT id FROM tareas WHERE estado NOT IN ('Cerrado','Eliminado'))
         ORDER BY p.estado, p.vigencia_final ASC");
    while ($row = db_fetch_object($resul_rel_pol)) {
        $tid = $row->id_tarea;
        if (!isset($map_polizas[$tid])) {
            $map_polizas[$tid] = array(
                'id_poliza'          => array(),
                'estado_poliza'      => array(),
                'estado_poliza_alerta' => array(),
                'vigencia_inicial'   => array(),
                'vigencia_final'     => array(),
                'numero_poliza'      => array()
            );
        }
        switch ($row->estado) {
            case 'Activo':  $estado_pol = 'badge badge-primary'; break;
            case 'Cerrado': $estado_pol = 'badge badge-dark';    break;
            default:        $estado_pol = 'badge badge-light';   break;
        }
        $map_polizas[$tid]['id_poliza'][]            = $row->id_poliza;
        $map_polizas[$tid]['estado_poliza'][]        = $row->estado;
        $map_polizas[$tid]['estado_poliza_alerta'][] = $estado_pol;
        $map_polizas[$tid]['vigencia_inicial'][]     = $row->vigencia_inicial;
        $map_polizas[$tid]['vigencia_final'][]       = $row->vigencia_final;
        $map_polizas[$tid]['numero_poliza'][]        = $row->numero_poliza;
    }

    // ── Pre-carga 2: relaciones de clientes ─────────────────────────────────
    $map_clientes = array();
    $resul_rel_cli = db_query($link,
        "SELECT tr.id_tarea, c.id,
                concat_WS(' ', c.nombre_cliente, c.apellido_paterno, c.apellido_materno) as nombre,
                c.telefono, c.correo
         FROM tareas_relaciones tr
         JOIN clientes c ON tr.id_relacion = c.id
         WHERE tr.base = 'clientes'
           AND tr.id_tarea IN (SELECT id FROM tareas WHERE estado NOT IN ('Cerrado','Eliminado'))");
    while ($row = db_fetch_object($resul_rel_cli)) {
        $tid = $row->id_tarea;
        if (!isset($map_clientes[$tid])) {
            $map_clientes[$tid] = array(
                'id_cliente' => array(),
                'nombre'     => array(),
                'telefono'   => array(),
                'correo'     => array()
            );
        }
        $map_clientes[$tid]['id_cliente'][] = $row->id;
        $map_clientes[$tid]['nombre'][]     = $row->nombre;
        $map_clientes[$tid]['telefono'][]   = $row->telefono;
        $map_clientes[$tid]['correo'][]     = $row->correo;
    }

    // ── Pre-carga 3: relaciones de propuestas ───────────────────────────────
    $map_propuestas = array();
    $resul_rel_prop = db_query($link,
        "SELECT tr.id_tarea, pp.estado,
                DATE_FORMAT(pp.vigencia_inicial,'%d-%m-%Y') as vigencia_inicial,
                DATE_FORMAT(pp.vigencia_final,'%d-%m-%Y') as vigencia_final,
                pp.numero_propuesta
         FROM tareas_relaciones tr
         JOIN propuesta_polizas pp ON tr.id_relacion = pp.id
         WHERE tr.base = 'propuestas'
           AND tr.id_tarea IN (SELECT id FROM tareas WHERE estado NOT IN ('Cerrado','Eliminado'))
         ORDER BY pp.estado, pp.vigencia_final ASC");
    while ($row = db_fetch_object($resul_rel_prop)) {
        $tid = $row->id_tarea;
        if (!isset($map_propuestas[$tid])) {
            $map_propuestas[$tid] = array(
                'estado_propuesta'       => array(),
                'estado_propuesta_alerta'=> array(),
                'vigencia_inicial'       => array(),
                'vigencia_final'         => array(),
                'numero_propuesta'       => array()
            );
        }
        switch ($row->estado) {
            case 'Activo':  $estado_pol = 'badge badge-primary'; break;
            case 'Cerrado': $estado_pol = 'badge badge-dark';    break;
            default:        $estado_pol = 'badge badge-light';   break;
        }
        $map_propuestas[$tid]['estado_propuesta'][]        = $row->estado;
        $map_propuestas[$tid]['estado_propuesta_alerta'][] = $estado_pol;
        $map_propuestas[$tid]['vigencia_inicial'][]        = $row->vigencia_inicial;
        $map_propuestas[$tid]['vigencia_final'][]          = $row->vigencia_final;
        $map_propuestas[$tid]['numero_propuesta'][]        = $row->numero_propuesta;
    }

    // ── Query principal de tareas ────────────────────────────────────────────
    $resul_tareas = db_query($link,
        "SELECT a.id, fecha_ingreso, fecha_vencimiento, tarea, estado, prioridad, procedimiento,
                count(b.id) as relaciones,
                sum(CASE WHEN base = 'polizas'   THEN 1 ELSE 0 END) as polizas,
                sum(CASE WHEN base = 'clientes'  THEN 1 ELSE 0 END) as clientes,
                sum(CASE WHEN base = 'propuestas'THEN 1 ELSE 0 END) as propuestas
         FROM tareas as a
         LEFT JOIN tareas_relaciones as b ON a.id = b.id_tarea
         WHERE estado NOT IN ('Cerrado','Eliminado')
         GROUP BY a.id, fecha_ingreso, fecha_vencimiento, tarea, estado, prioridad, procedimiento");

    while ($tareas = db_fetch_object($resul_tareas)) {
        $conta = $conta + 1;
        $tid   = $tareas->id;

        // Ensamblar datos de relaciones desde los maps pre-cargados
        $relaciones = array(
            "relaciones" => $tareas->relaciones,
            "clientes"   => $tareas->clientes,
            "polizas"    => $tareas->polizas,
            "propuestas" => $tareas->propuestas
        );
        if (isset($map_polizas[$tid])) {
            $relaciones = array_merge($relaciones, $map_polizas[$tid]);
        }
        if (isset($map_clientes[$tid])) {
            $relaciones = array_merge($relaciones, $map_clientes[$tid]);
        }
        if (isset($map_propuestas[$tid])) {
            $relaciones = array_merge($relaciones, $map_propuestas[$tid]);
        }

        switch ($tareas->estado) {
            case 'Pendiente':
                $estado_sw = 'badge badge-primary';
                break;
            case 'Completado':
                $estado_sw = 'badge badge-secondary';
                break;
            case 'Atrasado':
                $estado_sw = 'badge badge-danger';
                break;
            case 'Próximo a vencer':
                $estado_sw = 'badge badge-warning';
                break;
            default:
                $estado_sw = 'badge badge-light';
                break;
        }

        $fila = array_merge(array(
            "id_tarea"       => $tareas->id,
            "fecingreso"     => $tareas->fecha_ingreso,
            "fecvencimiento" => $tareas->fecha_vencimiento,
            "tarea"          => $tareas->tarea,
            "estado"         => $tareas->estado,
            "estado_alerta"  => $estado_sw,
            "procedimiento"  => $tareas->procedimiento,
            "prioridad"      => $tareas->prioridad),
            $relaciones);

        if ($conta == 1) {
            $codigo .= json_encode($fila);
        } else {
            $codigo .= ', ' . json_encode($fila);
        }
    }
    $codigo .= ']}';
    echo $codigo;
?>