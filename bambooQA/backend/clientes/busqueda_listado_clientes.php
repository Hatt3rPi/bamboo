<?php
    if(!isset($_SESSION))
    {
        session_start();
    }

require_once "/home/gestio10/public_html/backend/config.php";
    db_set_charset($link, 'utf8');
    db_select_db($link, DB_NAME);

// Una sola query con LEFT JOIN — reemplaza ~795 queries
$sql = "SELECT c.id, CONCAT_WS('-', c.rut_sin_dv, c.dv) as rut, c.apellido_paterno,
    CONCAT_WS(' ', c.nombre_cliente, c.apellido_paterno, c.apellido_materno) as nombre,
    c.correo, c.direccion_laboral, c.direccion_personal, c.telefono, c.fecha_ingreso, c.referido, c.grupo,
    COUNT(cc.id_cliente) as contactos_count,
    COALESCE(json_agg(json_build_object('nombre', cc.nombre, 'telefono', cc.telefono, 'correo', cc.correo)) FILTER (WHERE cc.id_cliente IS NOT NULL), '[]'::json) as contactos_json
    FROM clientes c
    LEFT JOIN clientes_contactos cc ON c.id = cc.id_cliente
    GROUP BY c.id, c.rut_sin_dv, c.dv, c.apellido_paterno, c.nombre_cliente, c.apellido_materno,
    c.correo, c.direccion_laboral, c.direccion_personal, c.telefono, c.fecha_ingreso, c.referido, c.grupo
    ORDER BY c.id";

$resultado = db_query($link, $sql);
$data = array();

while($row = db_fetch_object($resultado)) {
    $item = array(
        "id" => $row->id,
        "nombre" => $row->nombre,
        "apellidop" => $row->apellido_paterno,
        "correo_electronico" => $row->correo,
        "direccionl" => $row->direccion_laboral,
        "direccionp" => $row->direccion_personal,
        "telefono" => $row->telefono,
        "fecingreso" => $row->fecha_ingreso,
        "referido" => $row->referido,
        "grupo" => $row->grupo,
        "rut" => $row->rut,
        "contactos" => $row->contactos_count
    );

    $contactos = json_decode($row->contactos_json, true);
    if (is_array($contactos)) {
        foreach ($contactos as $i => $c) {
            $n = $i + 1;
            $item["nombre".$n] = $c['nombre'];
            $item["telefono".$n] = $c['telefono'];
            $item["correo".$n] = $c['correo'];
        }
    }

    $data[] = $item;
}

db_close($link);
echo json_encode(array("data" => $data));
?>