<?php
    if(!isset($_SESSION))
    {
        session_start();
    }
require_once "/home/gestio10/public_html/backend/config.php";
$busqueda=estandariza_info($_POST["buscacliente"]);
$numero=$trozos=0;
db_set_charset($link, 'utf8');

db_select_db($link, DB_NAME);
if ($busqueda<>''){
  //CUENTA EL NUMERO DE PALABRAS
  $trozos=explode(" ",$busqueda);
  $numero=count($trozos);
 if ($numero==1) {
  //SI SOLO HAY UNA PALABRA DE BUSQUEDA SE ESTABLECE UNA INSTRUCION CON LIKE
  $resultado=db_query($link, 'SELECT CONTACT(rut_sin_dv, \'-\',dv) as rut, concat_ws(\' \',nombre_cliente,  apellido_paterno,  apellido_materno) as nombre  FROM clientes WHERE  where nombre_cliente like \'%'.$busqueda.'%\' or apellido_paterno like \'%'.$busqueda.'%\' or rut_sin_dv like \'%'.$busqueda.'%\' or CONTACT(rut_sin_dv, \'-\',dv) like \'%'.$busqueda.'%\';');
 } elseif ($numero>1) {
 //SI HAY UNA FRASE SE UTILIZA EL ALGORTIMO DE BUSQUEDA AVANZADO DE MATCH AGAINST
 if (DB_ENGINE === 'pgsql') {
    $resultado=db_query($link, "SELECT CONCAT_WS('-',rut_sin_dv, dv) as rut, concat_ws(' ',nombre_cliente, apellido_paterno, apellido_materno) as nombre, ts_rank(to_tsvector('spanish', concat_ws(' ', nombre_cliente, apellido_paterno, apellido_materno, rut_sin_dv)), plainto_tsquery('spanish', '".$busqueda."')) AS Score FROM clientes WHERE to_tsvector('spanish', concat_ws(' ', nombre_cliente, apellido_paterno, apellido_materno, rut_sin_dv)) @@ plainto_tsquery('spanish', '".$busqueda."') ORDER BY Score DESC LIMIT 50;");
 } else {
    $resultado=db_query($link, 'SELECT CONTACT(rut_sin_dv, \'-\',dv) as rut, concat_ws(\' \',nombre_cliente, apellido_paterno,  apellido_materno) as nombre , MATCH(nombre_cliente, apellido_paterno ,apellido_materno , rut_sin_dv) AGAINST ( \''.$busqueda.'\' ) AS Score FROM clientes WHERE MATCH(nombre_cliente, apellido_paterno ,apellido_materno , rut_sin_dv) AGAINST ( \''.$busqueda.'\' ) ORDER BY Score DESC LIMIT 50;');
 }
}
}
While($row=db_fetch_object($resultado))
{
   $rut=$row->rut;
   $nombre=$row->nombre;
   echo $rut." - ".$nombre."<br>";
}
db_close($link);
function estandariza_info($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }
?>