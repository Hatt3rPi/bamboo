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
//$('#listado_clientes').dataTable().fnFilter(\"".estandariza_info($_POST["busqueda"])."\")
$buscar= estandariza_info($_POST["busqueda"]);
}

$page_title      = 'Clientes · Bamboo Seguros';
$page_active     = 'clientes';
$breadcrumb_main = 'Listado de clientes';
$breadcrumb_sub  = 'Clientes';
require_once 'layout.php';
?>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.1/css/buttons.dataTables.min.css">

<div class="bb-page-header">
  <div>
    <h1>Clientes</h1>
    <div class="subtitle">Cartera de clientes registrados</div>
  </div>
  <a href="creacion_cliente.php" class="btn btn-bamboo">
    <i class="fas fa-plus mr-2"></i>Nuevo cliente
  </a>
</div>

<div class="card">
  <div class="card-body">
    <table id="listado_clientes" class="display w-100">
      <thead>
        <tr>
          <th></th>
          <th>Rut</th>
          <th>Nombre</th>
          <th>Referido por</th>
          <th>Grupo</th>
          <th>Teléfono</th>
          <th>e-mail</th>
          <th>Dirección Privada</th>
          <th>Dirección Laboral</th>
          <th>id</th>
          <th>apellidop</th>
        </tr>
      </thead>
    </table>
    <div id="botones"></div>
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
<script>
    var table = ''
    $(document).ready(function () {
        table = $('#listado_clientes').DataTable({
            "stateSave": true,    // recuerda página/búsqueda/orden al volver (issue #1)
            "stateDuration": -1,  // sessionStorage: se limpia al cerrar la pestaña
            "ajax": "/bamboo/backend/clientes/busqueda_listado_clientes.php",
            "scrollX": true,
            "columns": [{
                "className": 'details-control',
                "orderable": false,
                "data": null,
                "defaultContent": '<i class="fas fa-search-plus"></i>'
            },
            {
                "data": "rut"
            },
            {
                "data": "nombre"
            },
            {
                "data": "referido"
            },
            {
                "data": "grupo"
            },
            {
                "data": "telefono"
            },
            {
                "data": "correo_electronico"
            },
            {
                "data": "direccionp"
            },
            {
                "data": "direccionl"
            },
            {
                "data": "id"
            },
            {
                "data": "apellidop"
            }

            ],
            //          "search": {
            //          "search": "abarca"
            //          },
            "columnDefs": [{
                "targets": [6, 7, 8, 9, 10],
                "visible": false,
            },
            {
                "targets": [6, 7, 8, 9, 10],
                "searchable": false
            }
            ],
            "order": [
                [10, "asc"]
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
            }
        });
        $("#listado_clientes_filter input")
    .off()
    .on('keyup change', function (e) {
    if (e.keyCode !== 13 || this.value == "") {
        var texto1=this.value.normalize("NFD").replace(/[\u0300-\u036f]/g, "");  
         table.search(texto1)
            .draw();
    }
        
    });
        $('#listado_clientes tbody').on('click', 'td.details-control', function () {
            var tr = $(this).closest('tr');
            var row = table.row(tr);

            if (row.child.isShown()) {
                // This row is already open - close it
                row.child.hide();
                tr.removeClass('shown');
            } else {
                // Open this row
                row.child(format(row.data())).show();
                tr.addClass('shown');
            }
        });
        $('#listado_clientes').dataTable().fnFilter(document.getElementById("var1").value);
        var dd = new Date();
        var fecha = '' + dd.getFullYear() + '-' + (("0" + (dd.getMonth() + 1)).slice(-2)) + '-' + (("0" + (dd
            .getDate() + 1)).slice(-2)) + ' (' + dd.getHours() + dd.getMinutes() + dd.getSeconds() + ')';

        var buttons = new $.fn.dataTable.Buttons(table, {
            buttons: [{
                sheetName: 'Clientes',
                orientation: 'landscape',
                extend: 'excelHtml5',
                filename: 'Listado clientes al: ' + fecha,
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8]
                }
            },
            {
                orientation: 'landscape',
                extend: 'pdfHtml5',
                filename: 'Listado clientes al: ' + fecha,
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8]
                }
            }
            ]
        }).container().appendTo($('#botones'));
    });

    function format(d) {
        // `d` is the original data object for the row
        $conf_tabla = '<table  background-color:#F6F6F6; color:#FFF; cellpadding="5" cellspacing="0" border="1" style="padding-left:50px;">';
        $contactos = '';
        switch (d.contactos) {
            case "1": {
                $contactos = $conf_tabla + '<tr><th></th><th>Contacto 1</th></tr>' +
                    '<tr><td>Nombre</td><td>' + d.nombre1 + '</td></tr>' +
                    '<tr><td>Teléfono</td><td>' + d.telefono1 + '</td></tr>' +
                    '<tr><td>Correo</td><td>' + d.correo1 + '</td></tr></table>'
                break
            }
            case "2": {
                $contactos = $conf_tabla + '<tr><th></th><th>Contacto 1</th><th>Contacto 2</th></tr>' +
                    '<tr><td>Nombre</td><td>' + d.nombre1 + '</td><td>' + d.nombre2 + '</td></tr>' +
                    '<tr><td>Teléfono</td><td>' + d.telefono1 + '</td><td>' + d.telefono2 + '</td></tr>' +
                    '<tr><td>Correo</td><td>' + d.correo1 + '</td><td>' + d.correo2 + '</td></tr></table>'
                break
                }
            case "3": {
                $contactos = $conf_tabla + '<tr><th></th><th>Contacto 1</th><th>Contacto 2</th><th>Contacto 3</th></tr>' +
                    '<tr><td>Nombre</td><td>' + d.nombre1 + '</td><td>' + d.nombre2 + '</td><td>' + d.nombre3 + '</td></tr>' +
                    '<tr><td>Teléfono</td><td>' + d.telefono1 + '</td><td>' + d.telefono2 + '</td><td>' + d.telefono3 + '</td></tr>' +
                    '<tr><td>Correo</td><td>' + d.correo1 + '</td><td>' + d.correo2 + '</td><td>' + d.correo3 + '</td></tr></table>'
                break
                }
            case "4": {
                $contactos = $conf_tabla + '<tr><th></th><th>Contacto 1</th><th>Contacto 2</th><th>Contacto 3</th><th>Contacto 4</th></tr>' +
                    '<tr><td>Nombre</td><td>' + d.nombre1 + '</td><td>' + d.nombre2 + '</td><td>' + d.nombre3 + '</td><td>' + d.nombre4 + '</td></tr>' +
                    '<tr><td>Teléfono</td><td>' + d.telefono1 + '</td><td>' + d.telefono2 + '</td><td>' + d.telefono3 + '</td><td>' + d.telefono4 + '</td></tr>' +
                    '<tr><td>Correo</td><td>' + d.correo1 + '</td><td>' + d.correo2 + '</td><td>' + d.correo3 + '</td><td>' + d.correo4 + '</td></tr></table>'
                break
                }
            case "5": {
                $contactos = $conf_tabla + '<tr><th></th><th>Contacto 1</th><th>Contacto 2</th><th>Contacto 3</th><th>Contacto 4</th><th>Contacto 5</th></tr>' +
                    '<tr><td>Nombre</td><td>' + d.nombre1 + '</td><td>' + d.nombre2 + '</td><td>' + d.nombre3 + '</td><td>' + d.nombre4 + '</td><td>' + d.nombre5 + '</td></tr>' +
                    '<tr><td>Teléfono</td><td>' + d.telefono1 + '</td><td>' + d.telefono2 + '</td><td>' + d.telefono3 + '</td><td>' + d.telefono4 + '</td><td>' + d.telefono5 + '</td></tr>' +
                    '<tr><td>Correo</td><td>' + d.correo1 + '</td><td>' + d.correo2 + '</td><td>' + d.correo3 + '</td><td>' + d.correo4 + '</td><td>' + d.correo5 + '</td></tr></table>'
                break
            }
            default: {
                $contactos = 'Cliente sin contactos registrados';
                break
            }
        }
        return '<table background-color:#F6F6F6; color:#FFF; cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">' +
            '<tr>' +
            '<td>Correo electrónico:</td>' +
            '<td>' + d.correo_electronico + '</td>' +
            '</tr>' +
            '<tr>' +
            '<td>Dirección particular:</td>' +
            '<td>' + d.direccionp + '</td>' +
            '</tr>' +
            '<tr>' +
            '<td>Dirección laboral:</td>' +
            '<td>' + d.direccionl + '</td>' +
            '</tr>' +
            '</tr>' +
            '<tr>' +
            '<td>Acciones</td>' +
            '<td><button title="Buscar información asociada" type="button" id=' + d.id +
            ' name="info" onclick="botones(this.id, this.name)"><i class="fas fa-search"></i></button><a> </a><button title="Editar"  type="button" id=' +
            d.id +
            ' name="modifica" onclick="botones(this.id, this.name)"><i class="fas fa-edit"></i></button><a> </a><button title="Agregar tarea"  type="button" id=' +
            d.id +
            ' name="tarea" onclick="botones(this.id, this.name)"><i class="fas fa-clipboard-list"></i></button></td>' +
            '</tr>' +
            '</table><br>' +
            $contactos + '<br>';
    }

    function botones(id, accion) {
        console.log("ID:" + id + " => acción:" + accion);
        switch (accion) {
            case "elimina": {
                console.log("Cliente eliminado con ID:" + id);
                var r = confirm(
                    "Estás a punto de eliminar los datos de un cliente. ¿Estás seguro de eliminarlo?"
                );
                if (r == true) {
                    $.ajax({
                        type: "POST",
                        url: "/bamboo/backend/clientes/elimina_cliente.php",
                        data: {
                            cliente: id
                        },
                    });
                    $.notify({
                        // options
                        message: 'Cliente eliminado con éxito'
                    }, {
                        // settings
                        type: 'success'
                    });
                    table.ajax.reload();
                    //location
                    break;

                } else {
                    $.notify({
                        // options
                        message: 'Proceso de eliminación de cliente cancelado'
                    }, {
                        // settings
                        type: 'info'
                    });
                    break;
                }
            }
            case "modifica": {
                $.redirect('/bamboo/creacion_cliente.php', {
                    'id_cliente': id
                }, 'post');
                break;
            }
            case "tarea": {
                $.redirect('/bamboo/creacion_actividades.php', {
                    'id_cliente': id
                }, 'post');
                break;
            }
            case "info": {
            $.redirect('/bamboo/resumen2.php', {
                'id': id,
                'base': 'cliente'
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