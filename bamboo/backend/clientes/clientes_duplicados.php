<?php
    if(!isset($_SESSION))
    {
        session_start();
    }
require_once "/home/gestio10/public_html/backend/config.php";

$resultado = '';
if (isset($_POST['rut']) && !empty($_POST['rut']))
{
    db_set_charset($link, 'utf8');
    db_select_db($link, DB_NAME);
    // Trae los nombres de TODOS los clientes con ese RUT. (Antes comparaba
    // num_rows == 1, que fallaba cuando ya había 2+ duplicados → dejaba de alertar.)
    $sql = "SELECT nombre_cliente FROM clientes WHERE rut_sin_dv = ?";

    $result = db_prepare_and_execute($link, $sql, "s", [estandariza_info($_POST['rut'])]);

    if ($result && $result['success']) {
        if ($result['num_rows'] >= 1) {
            $nombres = array();
            foreach ($result['rows'] as $row) {
                if (!empty($row->nombre_cliente)) { $nombres[] = $row->nombre_cliente; }
            }
            echo json_encode(array(
                "resultado" => "duplicado",
                "cantidad"  => $result['num_rows'],
                "nombres"   => $nombres
            ));
        } else {
            echo json_encode(array(
                "resultado" => "valido"
            ));
        }
    } else {
        echo json_encode(array(
            "resultado" => "error"
        ));
    }
}
function estandariza_info($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>