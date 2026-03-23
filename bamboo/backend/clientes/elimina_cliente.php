<?php
    if(!isset($_SESSION)) 
    { 
        session_start(); 
    } 
require_once "/home/gestio10/public_html/backend/config.php";
$id=$_POST["cliente"];
db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);
$query='delete from clientes WHERE id='.$id.';';
db_query($link, $query);
db_query($link, "select trazabilidad('".$_SESSION["username"]."', 'Eliminar cliente', '".str_replace("'","**",$query)."','cliente',".$id.", '".$_SERVER['PHP_SELF']."')");
db_close($link);
function estandariza_info($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }
  header("Location:http://gestionipn.cl/bamboo/listado_clientes.php");
?>