<?php
//obsoleto?
    if(!isset($_SESSION)) 
    { 
        session_start(); 
    } 
require_once "/home/gestio10/public_html/backend/config.php";
$id_renovada=$_GET[ "id_a_renovar" ];
    db_set_charset($link, 'utf8');
    db_select_db($link, DB_NAME);
    //$sql = "SELECT id FROM clientes WHERE CONTACT(rut_sin_dv, \'-\',dv) = ?";
    $sql = "select id, numero_poliza from polizas where id_poliza_renovada=".$id_renovada;
    $resultado=db_query($link, $sql);
    $codigo='{
        "data": [';
        $conta=0;
    While($row=db_fetch_object($resultado))
  {
    $conta=$conta+1;
    if ($conta==1){
        $codigo.= json_encode(array("id" =>& $row->id, "numero_poliza" =>& $row->numero_poliza));
      } else {
      $codigo.= ', '.json_encode(array("id" =>& $row->id, "numero_poliza" =>& $row->numero_poliza));
    }

  }

  $codigo.=']}';
  db_close($link);
  echo $codigo;
?>