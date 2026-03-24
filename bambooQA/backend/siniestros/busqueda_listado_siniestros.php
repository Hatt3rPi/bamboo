<?php
    if(!isset($_SESSION))
    {
        session_start();
    }

require_once "/home/gestio10/public_html/backend/config.php";
    db_set_charset($link, 'utf8');
    db_select_db($link, DB_NAME);

$sql = "SELECT s.id, s.numero_siniestro, s.numero_poliza, s.ramo, s.tipo_siniestro,
    s.fecha_ocurrencia, s.fecha_denuncia, s.estado, s.presentado,
    s.nombre_asegurado, s.rut_asegurado, s.dv_asegurado,
    s.telefono_asegurado, s.correo_asegurado,
    s.descripcion, s.liquidador_nombre, s.liquidador_telefono, s.liquidador_correo,
    s.patente, s.marca, s.modelo, s.anio_vehiculo,
    s.taller_nombre, s.taller_telefono,
    s.fecha_ingreso,
    CONCAT_WS(' ', c.nombre_cliente, c.apellido_paterno, c.apellido_materno) as \"nom_cliente\",
    CONCAT_WS('-', c.rut_sin_dv, c.dv) as \"rut_cliente\",
    c.telefono as \"tel_cliente\", c.correo as \"correo_cliente\",
    p.compania
FROM siniestros s
LEFT JOIN clientes c ON s.rut_asegurado = c.rut_sin_dv AND c.rut_sin_dv IS NOT NULL
LEFT JOIN polizas_2 p ON s.id_poliza = p.id
ORDER BY s.fecha_ingreso DESC";

$resultado = db_query($link, $sql);
$data = array();
while ($row = db_fetch_object($resultado)) {
    $data[] = array(
        "id_siniestro"         => $row->id,
        "numero_siniestro"     => $row->numero_siniestro,
        "numero_poliza"        => $row->numero_poliza,
        "ramo"                 => $row->ramo,
        "tipo_siniestro"       => $row->tipo_siniestro,
        "fecha_ocurrencia"     => $row->fecha_ocurrencia,
        "fecha_denuncia"       => $row->fecha_denuncia,
        "estado"               => $row->estado,
        "presentado"           => $row->presentado,
        "nombre_asegurado"     => $row->nombre_asegurado,
        "rut_asegurado"        => $row->rut_asegurado,
        "dv_asegurado"         => $row->dv_asegurado,
        "telefono_asegurado"   => $row->telefono_asegurado,
        "correo_asegurado"     => $row->correo_asegurado,
        "descripcion"          => $row->descripcion,
        "liquidador_nombre"    => $row->liquidador_nombre,
        "liquidador_telefono"  => $row->liquidador_telefono,
        "liquidador_correo"    => $row->liquidador_correo,
        "patente"              => $row->patente,
        "marca"                => $row->marca,
        "modelo"               => $row->modelo,
        "anio_vehiculo"        => $row->anio_vehiculo,
        "taller_nombre"        => $row->taller_nombre,
        "taller_telefono"      => $row->taller_telefono,
        "fecha_ingreso"        => $row->fecha_ingreso,
        "nom_cliente"          => $row->nom_cliente,
        "rut_cliente"          => $row->rut_cliente,
        "tel_cliente"          => $row->tel_cliente,
        "correo_cliente"       => $row->correo_cliente,
        "compania"             => $row->compania
    );
}
db_close($link);
echo json_encode(array("data" => $data));
?>
