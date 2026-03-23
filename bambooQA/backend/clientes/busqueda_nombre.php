<?php
    if(!isset($_SESSION))
    {
        session_start();
    }
require_once "/home/gestio10/public_html/backend/config.php";
$resultado=$resultado1 =$busqueda= '';
    $busqueda=$_POST["rut"];

    db_set_charset($link, 'utf8');
    db_select_db($link, DB_NAME);
    $sql = "SELECT id, nombre_cliente, apellido_paterno, apellido_materno FROM clientes WHERE rut_sin_dv = ?";

    $result = db_prepare_and_execute($link, $sql, "s", [estandariza_info($busqueda)]);

    if ($result && $result['success']) {
        if ($result['num_rows'] == 1) {
            $row = $result['rows'][0];
            echo json_encode(array(
                "resultado" => "antiguo",
                "id" => $row->id,
                "nombre" => $row->nombre_cliente,
                "apellidop" => $row->apellido_paterno,
                "apellidom" => $row->apellido_materno
            ));
        } else {
            $resultado = 'valido';
            echo json_encode(array(
                "resultado"=>"nuevo",
            ));
        }
    } else {
        $resultado = 'weeoe';
        echo json_encode(array(
            "resultado"=>"error",
        ));
    }

function estandariza_info($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

?>