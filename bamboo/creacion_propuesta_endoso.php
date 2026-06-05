<?php
if ( !isset( $_SESSION ) ) {
  session_start();
}
require_once "/home/gestio10/public_html/backend/config.php";
db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);


//$_SERVER[ "REQUEST_METHOD" ] = "POST";
//$_POST["accion"] = 'crear_endoso';
//$_POST["id"]='72';
//$_POST["numero_poliza"]='885';
//$_POST["numero_endoso"]="test";
//echo "'".$_POST["numero_propuesta"]."' '";
//echo $_POST["numero_endoso"]."'";
$numero_propuesta='';
$camino=$_POST["accion"];
if ($_SERVER[ "REQUEST_METHOD" ] == "POST" and ($_POST["accion"] == 'crea_propuesta_endoso_web' or $_POST["accion"] == 'crea_propuesta_endoso_manual'))
{
        $query = "select distinct a.numero_poliza, a.compania, a.id as id_poliza,a.ramo, a.vigencia_inicial, a.vigencia_final, CONCAT_WS('-',a.rut_proponente, a.dv_proponente) as rut_proponente, CONCAT_WS(' ',b.nombre_cliente, b.apellido_paterno, ' ', b.apellido_materno) as nombre_proponente, FORMAT(sum(c.prima_afecta), 2, 'de_DE') as total_prima_afecta, FORMAT(sum(c.prima_exenta), 2, 'de_DE') as total_prima_exenta, FORMAT(sum(c.prima_neta), 2, 'de_DE') as total_prima_neta, FORMAT(sum(c.prima_bruta_anual), 2, 'de_DE') as total_prima_bruta, FORMAT(sum(c.monto_asegurado), 2, 'de_DE') as total_monto_asegurado, a.moneda_poliza from polizas_2 as a left join clientes as b on a.rut_proponente=b.rut_sin_dv left join items as c on a.numero_poliza=c.numero_poliza where a.id='".$_POST["numero_poliza"]."' group by a.numero_poliza, a.compania, a.id, a.ramo, a.vigencia_inicial, a.vigencia_final, a.rut_proponente, a.dv_proponente, b.nombre_cliente, b.apellido_paterno, b.apellido_materno, a.moneda_poliza";
        $resultado = db_query($link, $query );
        While( $row = db_fetch_object( $resultado ) ) {
            $numero_poliza = $row->numero_poliza;
            $ramo=$row->ramo;
            $id_poliza = $row->id_poliza;
            $compania = $row->compania;
            $vigencia_inicial = $row->vigencia_inicial;
            $vigencia_final = $row->vigencia_final;
            $rut_proponente = $row->rut_proponente;
            $nombre_proponente = $row->nombre_proponente;
            $total_prima_afecta = $row->total_prima_afecta;
            $total_prima_exenta = $row->total_prima_exenta;
            $total_prima_neta = $row->total_prima_neta;
            $total_prima_bruta = $row->total_prima_bruta;
            $total_monto_asegurado = $row->total_monto_asegurado;
            $moneda_poliza = $row->moneda_poliza;
        }
}
elseif ($_SERVER[ "REQUEST_METHOD" ] == "POST" and ($_POST["accion"] == 'actualiza_propuesta' or $_POST["accion"] == 'crear_endoso')){
        $query = "select * from propuesta_endosos where id='".$_POST["id"]."'";
        $resultado = db_query($link, $query );
        While( $row = db_fetch_object( $resultado ) ) {
            $numero_propuesta = $row->numero_propuesta_endoso;
            $numero_poliza = $row->numero_poliza;
            $ramo=$row->ramo;
            $id=$row->id;
            $id_poliza = $row->id_poliza;
            $compania = $row->compania;
            $vigencia_inicial = $row->vigencia_inicial;
            $vigencia_final = $row->vigencia_final;
            $rut_proponente = $row->rut_proponente.'-'.$row->dv_proponente;
            $nombre_proponente = $row->nombre_proponente;
            $prima_neta_afecta = $row->prima_neta_afecta;
            $prima_neta_exenta = $row->prima_neta_exenta;
            $prima_neta = $row->prima_neta;
            $iva = $row->IVA;
            $prima_total = $row->prima_total;
            $total_monto_asegurado = $row->monto_asegurado_endoso;
            $moneda_poliza_endoso = $row->moneda_poliza_endoso;
            $tasa_afecta_endoso=$row->tasa_afecta_endoso;
            $tasa_exenta_endoso=$row->tasa_exenta_endoso;
            $tipo_endoso=$row->tipo_endoso;
            $fecha_ingreso=$row->fecha_ingreso;
            $descripcion_endoso=str_replace("\r\n", "\\n",$row->descripcion_endoso);
            $dice=str_replace("\r\n", "\\n",$row->dice);
            $debe_decir=str_replace("\r\n", "\\n",$row->debe_decir);
            $fecha_prorroga=$row->fecha_prorroga;
            $comentarios=str_replace("\r\n", "\\n",$row->comentario_endoso);
        }

}
elseif($_SERVER[ "REQUEST_METHOD" ] == "POST" and $_POST["accion"] == 'actualiza_endoso'){
        //no funcionando
        $query = "select * from endosos where id='".$_POST["id"]."'";
        $resultado = db_query($link, $query );
        While( $row = db_fetch_object( $resultado ) ) {
            $numero_propuesta=$row->numero_propuesta_endoso;
            $numero_endoso = $row->numero_endoso;
            $fecha_emision = $row->fecha_emision;
            $numero_poliza = $row->numero_poliza;
            $ramo=$row->ramo;
            $id=$row->id;
            $id_poliza = $row->id_poliza;
            $compania = $row->compania;
            $vigencia_inicial = $row->vigencia_inicial;
            $vigencia_final = $row->vigencia_final;
            $rut_proponente = $row->rut_proponente.'-'.$row->dv_proponente;
            $nombre_proponente = $row->nombre_proponente;
            $prima_neta_afecta = $row->prima_neta_afecta;
            $prima_neta_exenta = $row->prima_neta_exenta;
            $iva = $row->IVA;
            $prima_neta = $row->prima_neta;
            $prima_total = $row->prima_total;
            $total_monto_asegurado = $row->monto_asegurado_endoso;
            $moneda_poliza_endoso = $row->moneda_poliza_endoso;
            $tasa_afecta_endoso=$row->tasa_afecta_endoso;
            $tasa_exenta_endoso=$row->tasa_exenta_endoso;
            $tipo_endoso=$row->tipo_endoso;
            $fecha_ingreso=$row->fecha_ingreso;
            $descripcion_endoso=str_replace("\r\n", "\\n",$row->descripcion_endoso);
            $dice=str_replace("\r\n", "\\n",$row->dice);
            $debe_decir=str_replace("\r\n", "\\n",$row->debe_decir);
            $comentario=str_replace("\r\n", "\\n",$row->comentario_endoso);
        } 
}
?>
<?php
$page_title       = 'Propuesta de endoso · Bamboo Seguros';
$page_active      = 'endosos';
$breadcrumb_main  = 'Crear / editar propuesta de endoso';
$breadcrumb_sub   = 'Endosos';
require_once 'layout.php';
?>

<div class="bb-page-header">
  <div>
    <h1>Propuesta de endoso</h1>
    <div class="subtitle">Creación manual o vía propuesta web</div>
  </div>
  <a href="listado_propuesta_endosos.php" class="btn btn-secondary">
    <i class="fas fa-arrow-left mr-2"></i>Volver al listado
  </a>
</div>

<div class="card">
  <div class="card-body">
<div id="titulo1" style="display:flex">
  <p style="display:none">Propuesta de Endoso / Creación manual</p>
  <br>
</div>
<div id=titulo5 style="display:none">
  <p>Propuesta de Endoso / Creación WEB</p>
  <br>
</div>
<div id=titulo2 style="display:none">
  <p>Propuesta de Endoso / Aceptar propuesta de Endoso: <?php  echo $numero_propuesta; ?></p>
  <br>
</div>
<div id=titulo3 style="display:none">
  <p>Endoso / Editar Endoso: <?php  echo $numero_endoso; ?></p>
  <br>
</div>
<div id=titulo4 style="display:none">
  <p>Propuesta de Endoso / Editar propuesta: <?php  echo $numero_propuesta; ?></p>
  <br>
</div>
  <form action="/bamboo/backend/propuesta_endoso/crea_propuesta_endoso.php" class="needs-validation" method="POST" id="formulario"  novalidate>
  
<div id="accordionExample">
      <div class="card mb-4">
        <div class="card-header" id="headingOne">
          <h5 class="mb-0">Información general del endoso</h5>
        </div>
        <div id="collapseOne" aria-labelledby="headingOne">
          <div class="card-body" id="card-body-one">
            
        <div class="form-row">
            <div class="col-md-3" id="caja_numero_endoso" style="display:none">
                    <label for="monto"><b>Número de Endoso</b></label>
                    <span class="text-danger">*</span>
                    <div class="md-form">
                    <input type="text" class="form-control" id="nro_endoso" name="nro_endoso">
                    </div>
                
            </div>
            <div class="col-md-3">
                <label for = "motivo_endoso"><b>Motivo del Endoso</b></label>
                <span class="text-danger">*</span>
                <br>
                <select class="form-control" id="motivo_endoso" name="motivo_endoso" onchange=cambio_motivo() required>
                    <option value="">Selecciona Motivo</option>
                    <option value="Endoso Aumento">Endoso Aumento</option>
                    <option value="Endoso de Disminución">Endoso de Disminución</option>
                    <option value="Endoso Prorroga">Endoso Prorroga</option>
                    <option value="Endoso Sin Movimiento">Endoso Sin Movimiento</option>
                     <option value="Endoso de Anulación">Endoso de Anulación</option>
                    <option value="Endoso de Cancelación">Endoso de Cancelación</option>
                </select>
                <div class="invalid-feedback">No puedes dejar este campo en blanco</div>
            </div>
            
            <div class="col-md-3">
              <label for="fecha_emision">Fecha Emisión Endoso</label>
              <span class="text-danger">*</span>
              <div class="md-form">
                <input placeholder="Selected date" type="date" id="fecha_emision" name="fecha_emision"
                                              class="form-control"  max= "9999-12-31">
              </div>
            </div>
            
          <div class="col-md-4" id="col_fecha_ingreso" style="display:none">
                <label for="fecha_prorroga"><b>Fecha Prorroga:&nbsp;</b></label>
                <span class="text-danger">*</span>
                <div class="md-form">

                   <input placeholder="Selected date" type="date" name="fecha_prorroga" id="fecha_prorroga" onchange="prorroga()" value=""
                      class="form-control" max= "9999-12-31">
                      <div class="invalid-feedback">No puedes dejar este campo en blanco</div>
                </div>
          </div>
        </div>
         <br>
        <div class ="form-row">
            <div class="col-md-5">
                <label for="ramo"><b>Ramo</b></label>
                <span class="text-danger">*</span>
                <div class="md-form">
                    <select class="form-control" name="ramo" id="ramo" onChange="cambia_deducible();" required> 
                                        
                  <option value="">Selecciona un ramo</option>
                  <option value="AP - Accidentes Personales">ACCIDENTES PERSONALES - Accidentes Personales</option>
                  <option value="AP - Protección Financiera">ACCIDENTES PERSONALES - Protección Financiera</option>
                  <option value="ASISTENCIA EN VIAJE">ASISTENCIA EN VIAJE</option>
                  <option value="INC - Condominio">INCENDIO - Condominio</option>
                  <option value="INC - Hogar">INCENDIO - Hogar</option>
                  <option value="INC - Misceláneos">INCENDIO - Misceláneos</option>
                  <option value="INC - Perjuicio por Paralización">INCENDIO - Perjuicio por Paralización</option>
                  <option value="INC - Pyme">INCENDIO - Pyme</option>
                  <option value="INC - TRBF (Todo Riesgo Bienes Físicos)">INCENDIO - TRBF (Todo Riesgo Bienes Físicos)</option>
                  <option value="D&O Condominio">RESPONSABILIDAD CIVIL - D&O Condominio</option>
                  <option value="RC General">RESPONSABILIDAD CIVIL - RC General</option>
                  <option value="VEH - Vehículos Comerciales Livianos">VEHÍCULOS - Vehículos Comerciales Livianos</option>
                  <option value="VEH - Vehículos Particulares">VEHÍCULOS - Vehículos Particulares</option>
                  <option value="VEH - Vehículos Pesados">VEHÍCULOS - Vehículos Pesados</option>
                  <option value="null">--------------------------------------------------------------</option>
                  <option value="AVERÍA DE MAQUINARIA">AVERÍA DE MAQUINARIA</option>
                  <option value="CASCO - Aéreo">CASCO - Aéreo</option>
                  <option value="CASCO - Marítimo">CASCO - Marítimo</option>
                  <option value="Garantía">GARANTÍA</option>
                  <option value="ING - Equipo Contratistas">INGENIERÍA - Equipo Contratistas</option>
                  <option value="ING - Equipo Móvil Agrícola">INGENIERÍA - Equipo Móvil Agrícola</option>
                  <option value="ING - Equipos Electrónicos">INGENIERÍA - Equipos Electrónicos</option>
                  <option value="ING- TRC (Todo Riesgo Construcción)">INGENIERÍA - TRC (Todo Riesgo Construcción)</option>
                  <option value="REMESA DE VALORES">REMESA DE VALORES</option>
                  <option value="ROBO CON FUERZA">ROBO CON FUERZA EN LAS COSAS Y VIOLENCIA EN LAS PERSONAS</option>
                  <option value="ROTURA DE CRISTALES">ROTURA DE CRISTALES</option>
                  <option value="SEGURO ARRIENDO">SEGURO ARRIENDO</option>
                  <option value="SEGURO DE CRÉDITO">SEGURO DE CRÉDITO</option>
                  <option value="CABOTAJE">TRANSPORTE - CABOTAJE</option>
                  <option value="INTERNACIONAL">TRANSPORTE - INTERNACIONAL</option>
                  <option value="APV">VIDA - APV</option>
                  <option value="VIDA">VIDA - VIDA</option>
                  <option value="AP">AP</option>
                  <option value="D&O">D&O</option>
                  <option value="INC">INC</option>
                  <option value="PyME">PyME</option>
                  <option value="RC">RC</option>
                  <option value="VEH">VEH</option>
                </select>
                    <div class="invalid-feedback">Debes seleccionar un Ramo</div>
                </div>
                
            </div>
            <div class="col-md-4">
                <label for="nro_poliza"><b>Compañía</b></label>
                <span class="text-danger">*</span>
                <select class="form-control" name="selcompania" id="compania" required>
                  <option value="">Selecciona una compañía</option>
                  <option value="Axa Assistance">Axa Assistance</option>
                  <option value="BCI Seguros">BCI Seguros</option>
                  <option value="Chilena Consolidada">Chilena Consolidada</option>
                  <option value="CHUBB">CHUBB</option>
                  <option value="Confuturo">Confuturo</option>
                  <option value="Consorcio">Consorcio</option>
                  <option value="Continental">Continental</option>
                  <option value="Contempora">Contempora</option>
                  <option value="Coris">Coris</option>
                  <option value="HDI Seguros">HDI Seguros</option>
                  <option value="Liberty">Liberty</option>                       
                  <option value="Mapfre">Mapfre</option>
                  <option value="Ohio National Financial Group">Ohio National</option>
                  <option value="Orsan">Orsan</option>
                  <option value="Reale Seguros">Reale Seguros</option>
                  <option value="Renta Nacional">Renta Nacional</option>
                  <option value="Southbridge">Southbridge</option>
                  <option value="Sur Asistencia">Sur Asistencia</option>
                  <option value="Suaval">Suaval</option>
                  <option value="Sura">Sura</option>
                  <option value="STARR">STARR</option>
                  <option value="Unnio">Unnio</option>
                </select>
                <div class="invalid-feedback">Debes seleccionar una Compañía</div>
              
            </div>
            
            <div class="col-md-2">
                <label for="nro_poliza"><b>Número de Póliza</b></label>
                <span class="text-danger">*</span>
                <div class="md-form">
                   <input type="text" class="form-control" id="nro_poliza"
                                          name="nro_poliza" readonly>
                </div>
                <div style="color:red; visibility: hidden" id="validador6">Debes seleccionar Fecha de Vencimiento</div>
            </div>
        </div>
        <div class="form-row">
            <div class="col-md-5">
                <label for="corredor"><b>Corredor</b></label>
                    <input type="text" class="form-control" id="corredor"
                                          name="corredor" value="Adriana Sandoval Páez">
            </div>
            <div class="col-md-4">
            <label for="rut_corredor"><b>RUT Corredor</b></label>
             <input type="text" class="form-control" id="rut_corredor"
                                          name="rut_corredor" value="10.228.002-4">
             </div>
              
          </div>
        
          <br>
           <br>
          <div class="form-row">
            <div class="col-md-3">
              <label for="fechaprimer"><b>Fecha Ingreso</b></label>
              <span class="text-danger">*</span>
              <div class="md-form">
                <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso" max= "9999-12-31" >
              </div>
            </div>
            <div class="col-md-3">
              <label for="fecha_vigencia"><b>Fecha Vigencia Inicial</b></label>
              <span class="text-danger">*</span>
              <div class="md-form">
                <input type="date" class="form-control" id="fecha_vigencia_inicial" name="fecha_vigencia_inicial" onchange="calculadias()"  max= "9999-12-31">
              </div>
            </div>
            <div class="col-md-3">
              <label for="fecha_vigencia"><b>Fecha Vigencia Final</b></label>
              <span class="text-danger">*</span>
              <div class="md-form">
                <input type="date" class="form-control" id="fecha_vigencia_final" name="fecha_vigencia_final" max= "9999-12-31" onchange="calculadias()">
              </div>
            </div>
            <div class="col-md-3">
              <label for="dias"><b>Días</b></label>
              <span class="text-danger">*</span>
              <div class="md-form">
                <input type="number" class="form-control" id="dias" name="dias" max= "365">
              </div>
            </div>
            
          </div>
          <br>
          <div class = "form-row">
            <div class="col-md-3 mb-3">
                <label for="RUT"><b>RUT Proponente</b></label>
                <span class="text-danger">*</span>
                <input type="text" class="form-control" id="rutprop" name="rutprop"
                              placeholder="1111111-1" required>
                <div class="invalid-feedback">Dígito verificador no válido. Verifica rut </div>
             </div>
             
            <div class="col-md-5">
                <label for="Nombre"><b>Nombre Proponente</b></label>
                <span class="text-danger">*</span>
                <input type="text" id="nombre_prop" class="form-control" name="nombre" required>
                              
                <div   style="color:red; font-size: 12px ; visibility: hidden" id="validador10">No puedes dejar este campo
                 en blanco</div>
             <br>
            </div>
              
          </div>

         
        <div class = "form-row">
            
            <div class="col">
                <label for="descripción_endoso"><b>Descripción del Endoso</b></label>
                <span class="text-danger">*</span>
                <textarea class="form-control" rows="2" style="height:100px" id='descripcion_endoso' name='descripcion_endoso' style="text-indent:0px" ; required></textarea>
                
             <br>
            </div>
              
          </div> 
          
          <div class = "form-row">
            
            <div class="col-md-6">
                <label for="descripción_endoso"><b>Dice</b></label>
                <span class="text-danger">*</span>
                <textarea class="form-control" rows="2" style="height:100px" id='dice' name='dice' style="text-indent:0px" ; required></textarea>
                
             <br>
            </div>
            <div class="col-md-6">
                <label for="descripción_endoso"><b>Debe Decir</b></label>
                <span class="text-danger">*</span>
                <textarea class="form-control" rows="2" style="height:100px" id='debe_decir' name='debe_decir' style="text-indent:0px" ; required></textarea>
                
             <br>
            </div>
              
          </div> 
        
          </div>
        </div>
      </div>
      <div class="card mb-4">
        <div class="card-header" id="headingTwo">
          <h5 class="mb-0">Primas y montos</h5>
        </div>
        <div id="collapseTwo" aria-labelledby="headingTwo">
          <div class="card-body" id="card-body-two">
            <div class="form-row">
                <div class="col-md-2">
                    <label for="monto"><b>Monto</b></label>
                    <span class="text-danger">*</span>
                <div class="md-form">
                    <input type="number" class="form-control" id="monto" name="monto" onchange="calculatasas()">
                </div>
                <div style="color:red; visibility: hidden" id="validador6">Debes seleccionar Fecha de Vencimiento</div>
                </div>

            <div class="col-md-2">
                <label for="moneda_poliza"><b>Moneda Póliza</b></label>
                <select class="form-control" id="moneda_poliza" name="moneda_poliza">
                    <option value="UF" <?php if ($_SERVER[ "REQUEST_METHOD" ] == "POST" && $moneda_poliza == "UF") echo "selected" ?> >UF</option>
                    <option value="USD" <?php if ($_SERVER[ "REQUEST_METHOD" ] == "POST" && $moneda_poliza == "USD") echo "selected" ?> >USD</option>
                    <option value="CLP" <?php if ($_SERVER[ "REQUEST_METHOD" ] == "POST" && $moneda_poliza == "CLP") echo "selected" ?> >CLP</option>
                </select>
            </div>
            <div class="col-md-2">
            <label for="tasa_afecta"><b>Tasa Afecta %</b></label>
                <div class="md-form">
                    <input type="number" step="0.01" placeholder="0,00"  class="form-control" id="tasa_afecta">
                </div>
            </div>
            <div class="col-md-2">
            <label for="tasa_exenta"><b>Tasa Exenta %</b></label>
                <div class="md-form">
                    <input type="number" step="0.01" placeholder="0,00"  class="form-control" id="tasa_exenta">
                </div>
            </div>
          </div>
          <div class="form-row">
            <div class="col-md-2">
            <label for="moneda_poliza"><b>Prima Afecta</b></label>
                <div class="md-form">
                    <input type="number" step="0.01" class="form-control" id="prima_neta_afecta" name="prima_neta_afecta" onchange="calculatasas(),calculaIVA(),calculaprimatotal()">
                </div>
            </div>
                <div class="col-md-2">
                    <label for="monto"><b>Prima Exenta</b></label>
                    <span class="text-danger">*</span>
                <div class="md-form">
                    <input type="number" step="0.01" class="form-control" id="prima_neta_exenta" name="prima_neta_exenta" onchange="calculatasas(),calculaprimatotal(); dosdecimales(this.id)">
                </div>
                </div>


            
            <div class="col-md-2">
            <label for="moneda_poliza"><b>Prima Neta Total</b></label>
                <div class="md-form">
                    <input type="number" step="0.01" class="form-control" id="prima_neta" name="prima_neta">
                    
             </div>
            </div>
            <div class="col-md-2">
                <label for="monto"><b>IVA</b></label>
                    <span class="text-danger">*</span>
                <div class="md-form">
                    <input type="number" step="0.01" placeholder="0,00" class="form-control" id="iva" name="iva" onchange="calculaprimatotal();">
                </div>
            </div>
            <div class="col-md-2">
            <label for="prima_bruta"><b>Prima Bruta</b></label>
                <div class="md-form">
                    <input type="number" step="0.01" class="form-control" id="prima_total" name="prima_total">
                    
             </div>
            </div>
          </div>
         </div>
        </div>
        
  
  
  
  
        </div>
        <div class="card mb-4" id="card_confirma">
            <div class="card-header" id="headingthree">
             <h5 class="mb-0">Comentarios</h5>
            </div>
        <div id="collapsethree" aria-labelledby="headingthree">
         <div class="card-body" id="card-body-three">

            <div class="form-row">
                <label for="comentario_externo"><b>Comentarios </b></label>
            <br>
                    <textarea class="form-control" rows="2" style="height:100px" id='comentarios' name='comentario'
                              style="text-indent:0px" ;></textarea>
            
            </div>
           
        
         </div>
        </div>
        </div>
   
</div>
    
</form>

<button class="btn btn-bamboo" type="button" style="display:none" id='boton_submit' onclick="genera_propuesta()"></button>
<button class="btn btn-bamboo" type="button" id='boton_prueba' onclick="validados()">Registrar</button>

  </div>
</div>

<?php require_once 'layout_end.php'; ?>

<!-- Libs específicas de la propuesta -->
<script src="/assets/js/validarRUT.js"></script>
<script src="/assets/js/bootstrap-notify.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>





<script>


$("#boton_prueba").click(function(e){

    blnFormValidity= $('#formulario')[0].checkValidity()
   document.getElementById('formulario').classList.add('was-validated');
    if(blnFormValidity==false){
        
         
        e.preventDefault();
        event.stopImmediatePropagation();
        event.stopPropagation();
        return false
    }

})

function validados(){
    
     var descripcion = document.getElementById('descripcion_endoso').value;
     var dice = document.getElementById('dice').value;
     var debe = document.getElementById('debe_decir').value;
     var motivo = document.getElementById('motivo_endoso').value;
     var orgn = '<?php echo $camino; ?>';
     var nro_endoso = document.getElementById('nro_endoso').value;
     var fecha_emision = document.getElementById('fecha_emision').value;
    if(orgn =="crear_endoso"){
        
        if (descripcion =='' || dice ==''|| debe ==''|| motivo ==null || nro_endoso ==""|| fecha_emision ==""){
        
        alert("Existen campos obligatorios sin completar")
        
    }
    
    else {
        
        genera_propuesta()
    }
        
        
    }
     
    else
    {
        
    if (descripcion =='' || dice ==''|| debe ==''|| motivo ==null){
        
        alert("Hay Campos Requeditos Sin Llenar")
        
    }
    
    else {
        
        genera_propuesta()
    }
    
    }

    
}



function cambio_motivo(){
    
    var motivo_endoso = document.getElementById("motivo_endoso").value;

    if(motivo_endoso == "Endoso Prorroga") {
      
        document.getElementById("col_fecha_ingreso").style.display ="block";
    }  
    
    
    else{
        
        document.getElementById("col_fecha_ingreso").style.display ="none";
    }
    
    if (motivo_endoso =="Endoso Sin Movimiento"){
        
        document.getElementById("monto").value = "";
        document.getElementById("tasa_afecta").value = "";
        document.getElementById("tasa_exenta").value = "";
        document.getElementById("prima_neta_exenta").value = "";
        document.getElementById("iva").value = "";
        document.getElementById("prima_neta_afecta").value = "";
        document.getElementById("prima_total").value = "";
        
        
    }
    
}

function prorroga(){
    
    var fecha = new Date();
    fecha =document.getElementById('fecha_prorroga').value
   document.getElementById('fecha_vigencia_final').value = fecha;
    console.log(fecha);
    
}
    
    
document.addEventListener("DOMContentLoaded", function(event) {
   


    var orgn = '<?php echo $camino; ?>';
    console.log(orgn)
        switch (orgn) 
        {
          case 'crea_propuesta_endoso_manual': {
            document.getElementById("ramo").value = '<?php echo $ramo; ?>';
            document.getElementById("compania").value = '<?php echo $compania; ?>';
            document.getElementById("nro_poliza").value = '<?php echo $numero_poliza; ?>';
            document.getElementById("fecha_vigencia_inicial").value = '<?php echo $vigencia_inicial; ?>';
            document.getElementById("fecha_vigencia_final").value = '<?php echo $vigencia_final; ?>';
            document.getElementById("rutprop").value = '<?php echo $rut_proponente; ?>';
            document.getElementById("nombre_prop").value = '<?php echo $nombre_proponente; ?>';
            //document.getElementById("monto").value = '< ?php echo $total_monto_asegurado*1; ?>';
            document.getElementById("moneda_poliza").value = '<?php echo $moneda_poliza; ?>';
            //document.getElementById("prima_neta_exenta").value = '< ?php echo $total_prima_exenta*1; ?>';
            //document.getElementById("iva").value = '< ?php echo $total_prima_afecta*0.19; ?>';
            //document.getElementById("prima_neta_afecta").value = '< ?php echo $total_prima_afecta*1; ?>';
            //document.getElementById("prima_total").value = '< ?php echo $total_prima_bruta*1; ?>';
            document.getElementById("titulo1").style.display = "flex";
            document.getElementById("titulo2").style.display = "none";
            document.getElementById("titulo3").style.display = "none";
            document.getElementById("titulo4").style.display = "none";
            document.getElementById("titulo5").style.display = "none";
            
            break;  
          }
          case 'crea_propuesta_endoso_web': {
            document.getElementById("ramo").value = '<?php echo $ramo; ?>';
            document.getElementById("compania").value = '<?php echo $compania; ?>';
            document.getElementById("nro_poliza").value = '<?php echo $numero_poliza; ?>';
            document.getElementById("fecha_vigencia_inicial").value = '<?php echo $vigencia_inicial; ?>';
            document.getElementById("fecha_vigencia_final").value = '<?php echo $vigencia_final; ?>';
            document.getElementById("rutprop").value = '<?php echo $rut_proponente; ?>';
            document.getElementById("nombre_prop").value = '<?php echo $nombre_proponente; ?>';
            //document.getElementById("monto").value = '< ?php echo $total_monto_asegurado*1; ?>';
            document.getElementById("moneda_poliza").value = '<?php echo $moneda_poliza; ?>';
            //document.getElementById("prima_neta_exenta").value = '< ?php echo $total_prima_exenta*1; ?>';
            //document.getElementById("iva").value = '< ?php echo $total_prima_afecta*0.19; ?>';
            //document.getElementById("prima_neta_afecta").value = '< ?php echo $total_prima_afecta*1; ?>';
            //document.getElementById("prima_total").value = '< ?php echo $total_prima_bruta*1; ?>';
            document.getElementById("titulo1").style.display = "none";
            document.getElementById("titulo2").style.display = "none";
            document.getElementById("titulo3").style.display = "none";
            document.getElementById("titulo4").style.display = "none";
            document.getElementById("titulo5").style.display = "flex";
            document.getElementById("caja_numero_endoso").style.display = "block";
            document.getElementById("nro_endoso").required = "true"; 
            document.getElementById("fecha_emision").required = "true";
            if('<?php echo $tipo_endoso; ?>' == "Endoso Prorroga") {
                document.getElementById("col_fecha_ingreso").style.display ="block";
            }
            
            break;  
          }
           case 'actualiza_propuesta':{
            document.getElementById("ramo").value = '<?php echo $ramo; ?>';
            document.getElementById("compania").value = '<?php echo $compania; ?>';
            document.getElementById("nro_poliza").value = '<?php echo $numero_poliza; ?>';
            document.getElementById("fecha_vigencia_inicial").value = '<?php echo $vigencia_inicial; ?>';
            document.getElementById("fecha_vigencia_final").value = '<?php echo $vigencia_final; ?>';
            document.getElementById("rutprop").value = '<?php echo $rut_proponente; ?>';
            document.getElementById("nombre_prop").value = '<?php echo $nombre_proponente; ?>';
            document.getElementById("monto").value = '<?php echo $total_monto_asegurado*1; ?>';
            document.getElementById("moneda_poliza").value = '<?php echo $moneda_poliza_endoso; ?>';
            document.getElementById("prima_neta_exenta").value = '<?php echo $prima_neta_exenta*1; ?>';
            document.getElementById("prima_neta").value = '<?php echo $prima_neta*1; ?>';
            document.getElementById("iva").value = '<?php echo $iva*1; ?>';
            document.getElementById("prima_neta_afecta").value = '<?php echo $prima_neta_afecta*1; ?>';
            document.getElementById("prima_total").value = '<?php echo $prima_total*1; ?>';
            document.getElementById("motivo_endoso").value = '<?php echo $tipo_endoso; ?>';
            document.getElementById("fecha_ingreso").value = '<?php echo $fecha_ingreso; ?>';
            document.getElementById("descripcion_endoso").value = '<?php echo $descripcion_endoso; ?>';
            document.getElementById("dice").value = '<?php echo $dice; ?>';
            document.getElementById("debe_decir").value = '<?php echo $debe_decir; ?>';
            document.getElementById("tasa_afecta").value = '<?php echo $tasa_afecta_endoso*1; ?>';
            document.getElementById("tasa_exenta").value = '<?php echo $tasa_exenta_endoso*1; ?>';
            document.getElementById("fecha_prorroga").value='<?php echo $fecha_prorroga; ?>';
            document.getElementById("comentarios").value = '<?php echo $comentarios; ?>';
            document.getElementById("titulo1").style.display = "none";
            document.getElementById("titulo2").style.display = "none";
            document.getElementById("titulo3").style.display = "none";
            document.getElementById("titulo4").style.display = "flex";
            document.getElementById("titulo5").style.display = "none";

            if('<?php echo $tipo_endoso; ?>' == "Endoso Prorroga") {
              document.getElementById("col_fecha_ingreso").style.display ="block";
            }
               
               break;
               
           }
           case 'actualiza_endoso':{
            document.getElementById("ramo").value = '<?php echo $ramo; ?>';
            document.getElementById("compania").value = '<?php echo $compania; ?>';
            document.getElementById("nro_poliza").value = '<?php echo $numero_poliza; ?>';
            document.getElementById("fecha_vigencia_inicial").value = '<?php echo $vigencia_inicial; ?>';
            document.getElementById("fecha_vigencia_final").value = '<?php echo $vigencia_final; ?>';
            document.getElementById("rutprop").value = '<?php echo $rut_proponente; ?>';
            document.getElementById("nombre_prop").value = '<?php echo $nombre_proponente; ?>';
            document.getElementById("monto").value = '<?php echo $total_monto_asegurado*1; ?>';
            document.getElementById("moneda_poliza").value = '<?php echo $moneda_poliza_endoso; ?>';
            document.getElementById("prima_neta_exenta").value = '<?php echo $prima_neta_exenta*1; ?>';
            document.getElementById("iva").value = '<?php echo $iva*1; ?>';
            document.getElementById("prima_neta_afecta").value = '<?php echo $prima_neta_afecta*1; ?>';
            document.getElementById("prima_neta").value = '<?php echo $prima_neta*1; ?>';
            document.getElementById("prima_total").value = '<?php echo $prima_total*1; ?>';
            document.getElementById("motivo_endoso").value = '<?php echo $tipo_endoso; ?>';
            document.getElementById("fecha_ingreso").value = '<?php echo $fecha_ingreso; ?>';
            document.getElementById("descripcion_endoso").value = '<?php echo $descripcion_endoso; ?>';
            document.getElementById("dice").value = '<?php echo $dice; ?>';
            document.getElementById("debe_decir").value = '<?php echo $debe_decir; ?>';
            document.getElementById("tasa_afecta").value = '<?php echo $tasa_afecta_endoso*1; ?>';
            document.getElementById("tasa_exenta").value = '<?php echo $tasa_exenta_endoso*1; ?>';
            document.getElementById("nro_endoso").value = '<?php echo $numero_endoso; ?>';
            document.getElementById("fecha_emision").value = '<?php echo $fecha_emision; ?>';
            document.getElementById("comentarios").value = '<?php echo $comentarios; ?>';
            document.getElementById("fecha_prorroga").value='<?php echo $fecha_prorroga; ?>';
              document.getElementById("titulo1").style.display = "none";
              document.getElementById("titulo2").style.display = "none";
              document.getElementById("titulo3").style.display = "flex";
              document.getElementById("titulo4").style.display = "none";
              document.getElementById("titulo5").style.display = "none";
              document.getElementById("caja_numero_endoso").style.display = "block";
              if('<?php echo $tipo_endoso; ?>' == "Endoso Prorroga") {
              document.getElementById("col_fecha_ingreso").style.display ="block";
            }            
               break;
               
           }
           case 'crear_endoso':{
            document.getElementById("ramo").value = '<?php echo $ramo; ?>';
            document.getElementById("compania").value = '<?php echo $compania; ?>';
            document.getElementById("nro_poliza").value = '<?php echo $numero_poliza; ?>';
            document.getElementById("fecha_vigencia_inicial").value = '<?php echo $vigencia_inicial; ?>';
            document.getElementById("fecha_vigencia_final").value = '<?php echo $vigencia_final; ?>';
            document.getElementById("rutprop").value = '<?php echo $rut_proponente; ?>';
            document.getElementById("nombre_prop").value = '<?php echo $nombre_proponente; ?>';
            document.getElementById("monto").value = '<?php echo $total_monto_asegurado*1; ?>';
            document.getElementById("moneda_poliza").value = '<?php echo $moneda_poliza_endoso; ?>';
            document.getElementById("prima_neta_exenta").value = '<?php echo $prima_neta_exenta*1; ?>';
            document.getElementById("iva").value = '<?php echo $iva*1; ?>';
            document.getElementById("prima_neta_afecta").value = '<?php echo $prima_neta_afecta*1; ?>';
            document.getElementById("prima_neta").value = '<?php echo $prima_neta*1; ?>';
            document.getElementById("prima_total").value = '<?php echo $prima_total*1; ?>';
            document.getElementById("motivo_endoso").value = '<?php echo $tipo_endoso; ?>';
            document.getElementById("fecha_ingreso").value = '<?php echo $fecha_ingreso; ?>';
            document.getElementById("descripcion_endoso").value = '<?php echo $descripcion_endoso; ?>';
            document.getElementById("dice").value = '<?php echo $dice; ?>';
            document.getElementById("debe_decir").value = '<?php echo $debe_decir; ?>';
            document.getElementById("fecha_prorroga").value='<?php echo $fecha_prorroga; ?>';
            document.getElementById("tasa_afecta").value = '<?php echo $tasa_afecta_endoso*1; ?>';
            document.getElementById("tasa_exenta").value = '<?php echo $tasa_exenta_endoso*1; ?>';
            document.getElementById("comentarios").value = '<?php echo $comentarios; ?>';
                document.getElementById("titulo1").style.display = "none";
                document.getElementById("titulo2").style.display = "flex";
                document.getElementById("titulo3").style.display = "none";
                document.getElementById("titulo4").style.display = "none";
                document.getElementById("titulo5").style.display = "none";
                document.getElementById("caja_numero_endoso").style.display = "block";
                document.getElementById("nro_endoso").required = "true"; 
                document.getElementById("fecha_emision").required = "true";  
                if('<?php echo $tipo_endoso; ?>' == "Endoso Prorroga") {
              document.getElementById("col_fecha_ingreso").style.display ="block";
            }
               break;
               
           }
           
           
        }
        
//<<---PONER FECHA y CALCULAR DIAS--->>

    var request = new XMLHttpRequest()
    request.open('GET', 'https://mindicador.cl/api', true)
    request.onload = function() {
        // Begin accessing JSON data here
        var data = JSON.parse(this.response)
        if (request.status >= 200 && request.status < 400) {
            let date = new Date(data.fecha)
            let day = date.getDate()
            let month = date.getMonth() + 1
            let year = date.getFullYear()
            
        if (month < 10) {
            if (day < 10) {
                var fecha = `${year}-0${month}-0${day}`}
            else {
                var fecha = `${year}-0${month}-${day}`
            }
        } else {
            if (day < 10) {
                var fecha = `${year}-${month}-0${day}`}
            else {
                var fecha = `${year}-${month}-${day}`
            }
        }
            console.log(fecha);
            document.getElementById('fecha_ingreso').value = fecha;
    } else {}
}
    request.send();

    calculadias();
    
//CALCULAR TASAS

console.log(document.getElementById('monto').value);
console.log(document.getElementById('prima_neta_exenta').value);
console.log(document.getElementById('prima_neta_exenta').value);
console.log(document.getElementById('tasa_afecta').value);

   

})

function calculadias(){

    if (document.getElementById('fecha_vigencia_inicial').value!==""){
         var inicio  = new Date(document.getElementById('fecha_vigencia_inicial').value); 
         var final = new Date(document.getElementById('fecha_vigencia_final').value); 
         var diferencia = final.getTime() - inicio.getTime() 
         document.getElementById('dias').value= diferencia/86400000 ;
    }
}

function calculatasas(){
    
    var monto =    document.getElementById('monto').value;
    var prima_neta_exenta = document.getElementById('prima_neta_exenta').value;
    
    var prima_neta_afecta = document.getElementById('prima_neta_afecta').value;
    
    var tasa_afecta = prima_neta_afecta/monto*100;
    var tasa_exenta = prima_neta_exenta/monto*100;
    
    document.getElementById('tasa_afecta').value = tasa_afecta.toFixed(2);
    document.getElementById('tasa_exenta').value = tasa_exenta.toFixed(2);
}

function calculaIVA(){
    
    var prima_neta_afecta = document.getElementById('prima_neta_afecta').value;
    document.getElementById('iva').value = prima_neta_afecta*0.19;
    
     dosdecimales('iva');
    
}

function dosdecimales(id){
    
   
    
    valor= document.getElementById(id).value;
    
    console.log(id);
    console.log(valor);
    
    
    
    valor = parseFloat(this.valor).toFixed(2);
    document.getElementById(id).value = valor;
  
   
}
function calculaprimatotal(){
    var prima_neta_exenta = document.getElementById('prima_neta_exenta').value;
    var prima_neta_afecta = document.getElementById('prima_neta_afecta').value;
    var iva = document.getElementById('iva').value;
    
    var primaneta;
    var primatotal;
    
    
    primaneta = parseFloat(document.getElementById('prima_neta_exenta').value)+parseFloat(document.getElementById('prima_neta_afecta').value);
    primatotal = parseFloat(document.getElementById('prima_neta_exenta').value)+parseFloat(document.getElementById('prima_neta_afecta').value)+parseFloat(document.getElementById('iva').value);
    
    document.getElementById('prima_neta').value = primaneta.toFixed(2);
    
    document.getElementById('prima_total').value = primatotal.toFixed(2);
    
}


function genera_propuesta(){


    var camino='<?php echo $camino; ?>';

    switch (camino) {
        case 'crea_propuesta_endoso_manual': {
          //$.redirect('/bamboo/test_felipe.php', {
        $.redirect('/bamboo/backend/endosos/crea_endosos.php', {
          'tipo_endoso':document.getElementById('motivo_endoso').value,
          'ramo': document.getElementById('ramo').value,
          'compania': document.getElementById('compania').value,
          'nro_poliza': document.getElementById('nro_poliza').value,
          'fecha_ingreso':document.getElementById('fecha_ingreso').value,
          'fecha_vigencia_inicial': document.getElementById('fecha_vigencia_inicial').value,
          'fecha_vigencia_final': document.getElementById('fecha_vigencia_final').value,
          'rutprop':document.getElementById('rutprop').value,
          'nombre': document.getElementById('nombre_prop').value,
          'descripcion_endoso': document.getElementById('descripcion_endoso').value,
          'dice':document.getElementById('dice').value,
          'debe_decir': document.getElementById('debe_decir').value,
          'monto': document.getElementById('monto').value,
          'moneda_poliza':document.getElementById('moneda_poliza').value,
          'prima_neta_exenta': document.getElementById('prima_neta_exenta').value,
          'iva': document.getElementById('iva').value,
          'prima_neta_afecta':document.getElementById('prima_neta_afecta').value,
          'prima_neta': document.getElementById('prima_neta').value,
          'prima_total': document.getElementById('prima_total').value,
          'tasa_afecta': document.getElementById('tasa_afecta').value,
          'tasa_exenta': document.getElementById('tasa_exenta').value,
          'comentario_endoso': document.getElementById('comentarios').value,
          'fecha_prorroga': document.getElementById('fecha_prorroga').value,
          'id_poliza':'<?php echo $id_poliza; ?>',
          'accion':camino
          }, 'post');
        break;
        }
        case 'actualiza_propuesta': {
          //$.redirect('/bamboo/test_felipe.php', {
        $.redirect('/bamboo/backend/endosos/crea_endosos.php', {
          'id': '<?php echo $id; ?>',
          'tipo_endoso':document.getElementById('motivo_endoso').value,
          'ramo': document.getElementById('ramo').value,
          'compania': document.getElementById('compania').value,
          'nro_poliza': document.getElementById('nro_poliza').value,
          'fecha_ingreso':document.getElementById('fecha_ingreso').value,
          'fecha_vigencia_inicial': document.getElementById('fecha_vigencia_inicial').value,
          'fecha_vigencia_final': document.getElementById('fecha_vigencia_final').value,
          'rutprop':document.getElementById('rutprop').value,
          'nombre': document.getElementById('nombre_prop').value,
          'descripcion_endoso': document.getElementById('descripcion_endoso').value,
          'dice':document.getElementById('dice').value,
          'debe_decir': document.getElementById('debe_decir').value,
          'monto': document.getElementById('monto').value,
          'moneda_poliza':document.getElementById('moneda_poliza').value,
          'prima_neta_exenta': document.getElementById('prima_neta_exenta').value,
          'iva': document.getElementById('iva').value,
          'prima_neta_afecta':document.getElementById('prima_neta_afecta').value,
          'prima_neta': document.getElementById('prima_neta').value,
          'prima_total': document.getElementById('prima_total').value,
          'tasa_afecta': document.getElementById('tasa_afecta').value,
          'tasa_exenta': document.getElementById('tasa_exenta').value,
          'comentario_endoso': document.getElementById('comentarios').value,
          'fecha_prorroga': document.getElementById('fecha_prorroga').value,
          'id_poliza':'<?php echo $id_poliza; ?>',
          'numero_propuesta_endoso':'<?php echo $numero_propuesta ?>',
          'accion':camino
          }, 'post');
        break;
        }
        case 'crear_endoso': {
          //$.redirect('/bamboo/test_felipe.php', {
        $.redirect('/bamboo/backend/endosos/crea_endosos.php', {
          'id': '<?php echo $id; ?>',
          'tipo_endoso':document.getElementById('motivo_endoso').value,
          'ramo': document.getElementById('ramo').value,
          'compania': document.getElementById('compania').value,
          'nro_poliza': document.getElementById('nro_poliza').value,
          'fecha_ingreso':document.getElementById('fecha_ingreso').value,
          'fecha_vigencia_inicial': document.getElementById('fecha_vigencia_inicial').value,
          'fecha_vigencia_final': document.getElementById('fecha_vigencia_final').value,
          'rutprop':document.getElementById('rutprop').value,
          'nombre': document.getElementById('nombre_prop').value,
          'descripcion_endoso': document.getElementById('descripcion_endoso').value,
          'dice':document.getElementById('dice').value,
          'debe_decir': document.getElementById('debe_decir').value,
          'monto': document.getElementById('monto').value,
          'moneda_poliza':document.getElementById('moneda_poliza').value,
          'prima_neta_exenta': document.getElementById('prima_neta_exenta').value,
          'iva': document.getElementById('iva').value,
          'prima_neta_afecta':document.getElementById('prima_neta_afecta').value,
          'prima_neta': document.getElementById('prima_neta').value,
          'prima_total': document.getElementById('prima_total').value,
          'tasa_afecta': document.getElementById('tasa_afecta').value,
          'tasa_exenta': document.getElementById('tasa_exenta').value,
          'id_poliza':'<?php echo $id_poliza; ?>',
          'numero_propuesta_endoso':'<?php echo $numero_propuesta ?>',
          'numero_endoso':document.getElementById("nro_endoso").value,
          'fecha_emision':document.getElementById("fecha_emision").value,
          'fecha_prorroga': document.getElementById('fecha_prorroga').value,
          'comentario_endoso':document.getElementById("comentarios").value,
          'accion':camino
          }, 'post');
        break;
        }
        case 'crea_propuesta_endoso_web': {
        $.redirect('/bamboo/backend/endosos/crea_endosos.php', {
          'tipo_endoso':document.getElementById('motivo_endoso').value,
          'ramo': document.getElementById('ramo').value,
          'compania': document.getElementById('compania').value,
          'nro_poliza': document.getElementById('nro_poliza').value,
          'fecha_ingreso':document.getElementById('fecha_ingreso').value,
          'fecha_vigencia_inicial': document.getElementById('fecha_vigencia_inicial').value,
          'fecha_vigencia_final': document.getElementById('fecha_vigencia_final').value,
          'rutprop':document.getElementById('rutprop').value,
          'nombre': document.getElementById('nombre_prop').value,
          'descripcion_endoso': document.getElementById('descripcion_endoso').value,
          'dice':document.getElementById('dice').value,
          'debe_decir': document.getElementById('debe_decir').value,
          'monto': document.getElementById('monto').value,
          'moneda_poliza':document.getElementById('moneda_poliza').value,
          'prima_neta_exenta': document.getElementById('prima_neta_exenta').value,
          'iva': document.getElementById('iva').value,
          'prima_neta_afecta':document.getElementById('prima_neta_afecta').value,
          'prima_neta': document.getElementById('prima_neta').value,
          'prima_total': document.getElementById('prima_total').value,
          'tasa_afecta': document.getElementById('tasa_afecta').value,
          'tasa_exenta': document.getElementById('tasa_exenta').value,
          'id_poliza':'<?php echo $id_poliza; ?>',
          'numero_propuesta_endoso':'web',
          'numero_endoso':document.getElementById("nro_endoso").value,
          'fecha_emision':document.getElementById("fecha_emision").value,
          'fecha_prorroga': document.getElementById('fecha_prorroga').value,
          'comentario_endoso':document.getElementById("comentarios").value,
          'accion':camino
          }, 'post');
        break;
        }
        case 'actualiza_endoso': {
          //$.redirect('/bamboo/test_felipe.php', {
        $.redirect('/bamboo/backend/endosos/crea_endosos.php', {
          'id': '<?php echo $id; ?>',
          'tipo_endoso':document.getElementById('motivo_endoso').value,
          'ramo': document.getElementById('ramo').value,
          'compania': document.getElementById('compania').value,
          'nro_poliza': document.getElementById('nro_poliza').value,
          'fecha_ingreso':document.getElementById('fecha_ingreso').value,
          'fecha_vigencia_inicial': document.getElementById('fecha_vigencia_inicial').value,
          'fecha_vigencia_final': document.getElementById('fecha_vigencia_final').value,
          'rutprop':document.getElementById('rutprop').value,
          'nombre': document.getElementById('nombre_prop').value,
          'descripcion_endoso': document.getElementById('descripcion_endoso').value,
          'dice':document.getElementById('dice').value,
          'debe_decir': document.getElementById('debe_decir').value,
          'monto': document.getElementById('monto').value,
          'moneda_poliza':document.getElementById('moneda_poliza').value,
          'prima_neta_exenta': document.getElementById('prima_neta_exenta').value,
          'iva': document.getElementById('iva').value,
          'prima_neta_afecta':document.getElementById('prima_neta_afecta').value,
          'prima_neta': document.getElementById('prima_neta').value,
          'prima_total': document.getElementById('prima_total').value,
          'tasa_afecta': document.getElementById('tasa_afecta').value,
          'tasa_exenta': document.getElementById('tasa_exenta').value,
          'id_poliza':'<?php echo $id_poliza; ?>',
          'numero_propuesta_endoso':'<?php echo $numero_propuesta ?>',
          'numero_endoso':document.getElementById("nro_endoso").value,
          'fecha_emision':document.getElementById("fecha_emision").value,
          'fecha_prorroga': document.getElementById('fecha_prorroga').value,
          'comentario_endoso':document.getElementById("comentarios").value,
          'accion':camino
          }, 'post');
        break;
        }
    }
   }
  
</script>

