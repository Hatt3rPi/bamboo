<?php
    if(!isset($_SESSION)) 
    { 
        session_start(); 
    } 
$buscar='';
function estandariza_info($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }
require_once "/home/gestio10/public_html/backend/config.php";
db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);
$num=0;
 $busqueda=$busqueda_err=$data='';
 $rut=$nombre=$telefono=$correo=$lista='';

if($_SERVER["REQUEST_METHOD"] == "POST" and isset($_POST["busqueda"])==true){
    // Check if username is empty
//$('#listado_polizas').dataTable().fnFilter(\"".estandariza_info($_POST["busqueda"])."\")
$buscar= estandariza_info($_POST["busqueda"]);
}

$page_title      = 'Propuestas de endoso · Bamboo Seguros';
$page_active     = 'endosos';
$breadcrumb_main = 'Propuestas de endoso';
$breadcrumb_sub  = 'Endosos';
require_once 'layout.php';
?>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.1/css/buttons.dataTables.min.css">

<div class="bb-page-header">
  <div>
    <h1>Propuestas de endoso</h1>
    <div class="subtitle">Endosos en propuesta, pendientes de aprobación o emisión</div>
  </div>
  <div class="d-flex flex-wrap" style="gap:var(--space-2)">
    <a href="endosos.php" class="btn btn-secondary">
      <i class="fas fa-arrow-left mr-2"></i>Endosos
    </a>
    <a href="creacion_propuesta_endoso.php" class="btn btn-bamboo">
      <i class="fas fa-plus mr-2"></i>Nueva propuesta
    </a>
    <button type="button" class="btn btn-secondary" onclick="window.location.href='/bamboo/backend/endosos/genera_excel_propuesta_endosos.php'">
      <i class="fas fa-file-excel mr-2"></i>Excel
    </button>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <table class="display w-100" id="listado_propuesta_endosos">
      <thead>
        <tr>
          <th></th>
          <th>Estado</th>
          <th>Nro Propuesta Endoso</th>
          <th>Tipo Endoso</th>
          <th>Nro Póliza</th>
          <th>Rut proponente</th>
          <th>Nombre proponente</th>
          <th>Fecha ingreso</th>
          <th>Inicio Vigencia</th>
          <th>Fin Vigencia</th>
          <th>Fecha Prorroga</th>
        </tr>
      </thead>
    </table>
    <div id="botones_poliza"></div>
  </div>
</div>

<div id="auxiliar" style="display: none;">
  <input id="var1" value="<?php echo htmlspecialchars($buscar);?>">
</div>

<?php require_once 'layout_end.php'; ?>

<!-- Libs específicas del listado -->
<script src="/assets/js/bootstrap-notify.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.6.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.8.4/moment.min.js"></script>
<script src="https://cdn.datatables.net/plug-ins/1.10.19/sorting/datetime-moment.js"></script>
<script>
var table_propuestas_endosos = ''
$(document).ready(function() {
    table_propuestas_endosos = $('#listado_propuesta_endosos').DataTable({
        "ajax": "/bamboo/backend/endosos/busqueda_listado_propuesta_endoso.php",
        "scrollX": true,
        "dom": 'Pfrtip',
        "columns": [{
                "className": 'details-control',
                "orderable": false,
                "data": null,
                "defaultContent": '<i class="fas fa-search-plus"></i>'
            }, //0
            {
                "data": "estado",
                title: "Estado"
            }, //1
            { 
                data: "numero_propuesta_endoso", 
                title: "Nro Propuesta Endoso",
            }, //2
            {
                "data": "tipo_endoso",
                title: "Tipo Endoso"
            }, //3
            {
                "data": "rut_proponente",
                title: "Rut proponente"
            }, //3
            {
                "data": "nombre_proponente",
                title: "Nombre proponente"
            }, //3
            {
                "data": "numero_poliza",
                title: "Número Póliza"
            }, //4
            {
                "data": "fecha_ingreso_endoso",
                title: "Fecha ingreso"
            }, //5
            {
                "data": "vigencia_inicial",
                title: "Inicio Vigencia"
            }, //6
            {
                "data": "vigencia_final",
                title: "Fin Vigencia"
            }, //7
            {
                "data": "fecha_prorroga",
                title: "Fecha Prorroga"
            } //7
        ],
        "columnDefs": 
        [
         {
        targets: 1,
        render: function (data, type, row, meta) {
             var estado='';
            switch (data) {
                        case 'Emitido':
                            estado='<span class="badge badge-primary">'+data+'</span>';
                            break;
                        case 'Rechazado':
                            estado='<span class="badge badge-danger">'+data+'</span>';
                            break;
                        case 'Cancelado':
                            estado='<span class="badge badge-dark">'+data+'</span>';
                            break;
                        default:
                            estado='<span class="badge badge-light">'+data+'</span>';
                            break;
                    }
          return estado;  //render link in cell
        }},
        {
        targets: [7,8,9,10],
         render: function(data, type, full)
         {
            if (data==null || data=="0000-00-00")
            {
                return '';
            }
            else
            {
                return moment(data).format('YYYY/MM/DD');
            }
         }}
        ],
        "order": [
            [1, "desc"],
            [2, "desc"]
        ],
        "oLanguage": {
            "sSearch": "Búsqueda rápida",
            "sLengthMenu": 'Mostrar <select>' +
                '<option value="10">10</option>' +
                '<option value="25">30</option>' +
                '<option value="50">50</option>' +
                '<option value="-1">todos</option>' +
                '</select> registros',
                "sInfoFiltered": "(Resultado búsqueda: _TOTAL_ de _MAX_ registros totales)",
            "sLengthMenu": "Muestra _MENU_ registros por página",
            "sZeroRecords": "Se están cargando los registros. Espera unos segundos más.",
            "sInfo": "Mostrando página _PAGE_ de _PAGES_",
            "sInfoEmpty": "No hay registros disponibles",
            "oPaginate": {
                "sNext": "Siguiente",
                "sPrevious": "Anterior",
                "sLast": "Última"
            }
        },
        "language": {
            "searchPanes": {
                "title":{
                    _: 'Filtros seleccionados - %d',
                    0: 'Sin Filtros Seleccionados',
                    1: '1 Filtro Seleccionado',
                }
            }
        }
    });
    $("#listado_propuesta_endosos_filter input")
    .off()
    .on('keyup change', function (e) {
    if (e.keyCode !== 13 || this.value == "") {
        var texto1=this.value.normalize("NFD").replace(/[\u0300-\u036f]/g, "");  
        table_propuestas_endosos.search(texto1)
            .draw();
    }
        
    });
    $('#listado_propuesta_endosos tbody').on('click', 'td.details-control', function() {
        var tr = $(this).closest('tr');
        var row = table_propuestas_endosos.row(tr);

        if (row.child.isShown()) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
        } else {
            // Open this row
            row.child(format_propuesta_endoso(row.data())).show();
            tr.addClass('shown');
        }
    });
    $('#listado_propuesta_endosos').dataTable().fnFilter(document.getElementById("var1").value);
 
});

function format_propuesta_endoso(d) {
    // `d` is the original data object for the row
    var ext_cancelado='';

    var botones='';
    if (d.estado=='Rechazado'){
        ext_cancelado='<tr>' +
        '<td>Motivo rechazo:</td>' +
        '<td>' + d.motivo_rechazo + '</td>' +
        '</tr>';
    }
    if (d.estado=='Pendiente'){
        botones='<tr>'+
            '<td VALIGN=TOP>Acciones:</td>' +
            '<td>' +
                '<button title="Emitir Endoso" type="button" id=' + d.id + ' name="crear_endoso" onclick="botones(this.id, this.name, \'propuesta_endoso\')"><i class="fa fa-thumbs-up"></i></button><a> </a>' +
                '<button title="Rechazar Endoso"  type="button" id=' + d.id + ' name="rechazar_propuesta" onclick="botones(this.id, this.name, \'propuesta_endoso\')"><i class="fa fa-thumbs-down"></i></button>' +
                '<button title="Generar documento" type="button" id=' + d.numero_propuesta_endoso + ' name="generar_documento" onclick="botones(this.id, this.name, \'propuesta_endoso\')"><i class="fa fa-file-pdf-o"></i></button><a> </a>' +
                '<button title="Buscar información asociada" type="button" id=' + d.numero_propuesta_endoso + ' name="info" onclick="botones(this.id, this.name, \'propuesta_endoso\')"><i class="fas fa-search"></i></button><a> </a>' +
                '<button title="Editar Propuesta Endoso"  type="button" id=' + d.id + ' name="actualiza_propuesta" onclick="botones(this.id, this.name, \'propuesta_endoso\')"><i class="fas fa-edit"></i></button><a> </a>' +
            '</td>' +
        '</tr>' +
        '</table>';
    }
    else{
        botones='<tr><td VALIGN=TOP>Acciones:</td>' +
        '<td>' +
        '<button title="Generar documento" type="button" id=' + d.numero_propuesta_endoso + ' name="generar_documento" onclick="botones(this.id, this.name, \'propuesta_endoso\')"><i class="fa fa-file-pdf-o"></i></button><a> </a>' +
        '<button title="Buscar información asociada" type="button" id=' + d.numero_propuesta_endoso + ' name="info" onclick="botones(this.id, this.name, \'propuesta_endoso\')"><i class="fas fa-search"></i></button><a> </a>' +
        '</td>' +
        '</tr>' +
        '</table>';
    }

    return '<table background-color:#F6F6F6; color:#FFF; cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">' +
    '<tr>' +
            ext_cancelado +
            '<td VALIGN=TOP>Primas: </td>' +
            '<td>'+
                 '<table class="table table-striped" style="width:100%">'+
                    '<tr>' +
                        '<td>Total Prima afecta:</td>' +
                        '<td>' + d.prima_neta_afecta + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<td>Total Prima exenta:</td>' +
                        '<td>' + d.prima_neta_exenta + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<td>Total Prima neta anual:</td>' +
                        '<td>' + d.prima_neta + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<td>Total Prima bruta anual:</td>' +
                        '<td>' + d.prima_total + '</td>' +
                    '</tr>' +
                '</table>'+
            '</td>' +
        '</tr>' +
        '<tr>' +
        '<td VALIGN=TOP>Detalle: </td>' +
            '<td>'+
                '<table class="table table-striped" style="padding-left:50px;" cellpadding="5" cellspacing="0" border="0" id="listado_polizas">'+
                    '<tr>'+
                        '<th>Descripción</th>'+
                        '<th>Dice</th>'+
                        '<th>Debe Decir</th>'+
                        '<th>Comentario</th>'+
                    '</tr>'+
                    '<tr>'+
                    '<td>' + d.descripcion_endoso + '</td>'+
                    '<td>' + d.dice + '</td>'+
                    '<td>' + d.debe_decir + '</td>'+
                    '<td>' + d.comentario_endoso + '</td>'+
                '</table>'+
            '</td>' +
        '</tr>' +     
        botones +
        '<tr>' +
            '<td> </td>' +
            '<td> </td>' +
        '</tr>' ;
}
function botones(id, accion, base) {
    console.log("ID:" + id + " => acción:" + accion);
    switch (accion) {
        case "rechazar_propuesta": {
                var motivo = window.prompt('Ingresa el motivo del rechazo', '');
                var r2 = confirm("Estás a punto de rechazar esta propuesta de endoso ¿Deseas continuar?");
                
                if (r2 == true) {
                $.redirect('/bamboo/backend/endosos/crea_endosos.php', {
                    'numero_propuesta': id,
                    'id': id,
                    'accion':accion,
                    'motivo':motivo
                }, 'post');
                }
            break;
        }

        case "actualiza_propuesta": {
            $.redirect('/bamboo/creacion_propuesta_endoso.php', {
            //$.redirect('/bamboo/test_felipe2.php', {    
                'numero_propuesta': id,
                'id': id,
                'accion': accion
            }, 'post');
            break;
        }
        case "tarea": {
                $.redirect('/bamboo/creacion_actividades.php', {
                    'id_propuesta': id
                }, 'post');
            break;
        }
        case "info": {
            $.redirect('/bamboo/resumen2.php', {
                'id': id,
                'base': base
            }, 'post');
            break;
        }
        case "crear_endoso": {
            $.redirect('/bamboo/creacion_propuesta_endoso.php', {
                'numero_propuesta': id,
                'id': id,
                'accion': accion
            }, 'post');
            break;
        }
        case "generar_documento": {
            $.redirect('/bamboo/documento_propuesta_endoso.php', {
                'numero_propuesta': id,
                'id': id,
                'accion': accion
            }, 'post');
            break;
        }
    }
}
(function(){
 
 function removeAccents ( data ) {
     if ( data.normalize ) {
         // Use I18n API if avaiable to split characters and accents, then remove
         // the accents wholesale. Note that we use the original data as well as
         // the new to allow for searching of either form.
         return data +' '+ data
             .normalize('NFD')
             .replace(/[\u0300-\u036f]/g, '');
     }
  
     return data;
 } 
  
 var searchType = jQuery.fn.DataTable.ext.type.search;
  
 searchType.string = function ( data ) {
     return ! data ?
         '' :
         typeof data === 'string' ?
             removeAccents( data ) :
             data;
 };
  
 searchType.html = function ( data ) {
     return ! data ?
         '' :
         typeof data === 'string' ?
             removeAccents( data.replace( /<.*?>/g, '' ) ) :
             data;
 };
  
 }());
</script>
