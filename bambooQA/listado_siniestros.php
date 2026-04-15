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
$buscar= estandariza_info($_POST["busqueda"]);
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/bambooQA/images/bamboo.png">
    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"
        integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <link rel="stylesheet" href="/assets/css/datatables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" type="text/css"
        href="https://cdn.datatables.net/buttons/1.6.1/css/buttons.dataTables.min.css" />


    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"
        integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous">
    </script>
            <script src="/assets/js/bootstrap-notify.js"></script>
        <script src="/assets/js/bootstrap-notify.min.js"></script>
    <script src="https://kit.fontawesome.com/7011384382.js" crossorigin="anonymous"></script>
</head>


<body>

    <!-- body code goes here -->
    <div id="header"><?php include 'header2.php' ?></div>
    <div class="container">
        <p> Siniestros / Listado de Siniestros <br>
        </p>
        <br>
        <div class="container">
            <table class="display" style="width:100%" id="listado_siniestros">
                   <tr>
                    <th></th>
                    <th>Estado</th>
                    <th>N° Siniestro</th>
                    <th>N° Póliza</th>
                    <th>Fecha Ocurrencia</th>
                    <th>Ramo</th>
                    <th>Tipo Siniestro</th>
                    <th>Ítems</th>
                    <th>Bienes</th>
                    <th>Cliente</th>
                    <th>Liquidador</th>
                    <th>Patente</th>
                    <th>Compañía</th>
                    </tr>

            </table>

        <div id="auxiliar" style="display: none;">
            <input id="var1" value="<?php
        echo htmlspecialchars($buscar);?>">
        </div>
        <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"
            integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous">
        </script>
        <script src="/assets/js/jquery.redirect.js"></script>

        <script src="/assets/js/datatables.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js">
        </script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js">
        </script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.6.1/js/dataTables.buttons.min.js">
        </script>
        <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.html5.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.print.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.8.4/moment.min.js"></script>
    <script src="https://cdn.datatables.net/plug-ins/1.10.19/sorting/datetime-moment.js"></script>

</body>

</html>
<script>
var table = ''
$(document).ready(function() {
    table = $('#listado_siniestros').DataTable({
        "ajax": "/bambooQA/backend/siniestros/busqueda_listado_siniestros.php",
        "scrollX": true,
        "searchPanes":{
            "columns":[1],
        },
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
                "data": "numero_siniestro",
                title: "N° Siniestro"
            }, //2
            {
                "data": "numero_poliza",
                title: "N° Póliza"
            }, //3
            {
                "data": "fecha_ocurrencia",
                title: "Fecha Ocurrencia"
            }, //4
            {
                "data": "ramo",
                title: "Ramo"
            }, //5
            {
                "data": "tipo_siniestro",
                title: "Tipo Siniestro"
            }, //6
            {
                "data": "items_afectados",
                title: "Ítems",
                defaultContent: ""
            }, //7
            {
                "data": null,
                title: "Bienes",
                orderable: false,
                render: function(r) {
                    var p = r.bienes_propios || 0, t = r.bienes_terceros || 0;
                    if (p === 0 && t === 0) return '<em>—</em>';
                    return '<span class="badge badge-info">' + p + ' propio' + (p===1?'':'s') + '</span> ' +
                           '<span class="badge badge-warning">' + t + ' tercero' + (t===1?'':'s') + '</span>';
                }
            }, //8
            {
                "data": "nom_cliente",
                title: "Cliente"
            }, //9
            {
                "data": "liquidador_nombre",
                title: "Liquidador"
            }, //9
            {
                "data": "patente",
                title: "Patente"
            }, //10
            {
                "data": "compania",
                title: "Compañía"
            } //11
        ],
        "columnDefs": [
            {
                targets: 1,
                render: function (data, type, row, meta) {
                    var estado = '';
                    switch (data) {
                        case 'Abierto':
                            estado = '<span class="badge badge-primary">' + data + '</span>';
                            break;
                        case 'Número pendiente':
                            estado = '<span class="badge badge-info">' + data + '</span>';
                            break;
                        case 'Cerrado':
                            estado = '<span class="badge badge-secondary">' + data + '</span>';
                            break;
                        case 'Rechazado':
                            estado = '<span class="badge badge-danger">' + data + '</span>';
                            break;
                        default:
                            estado = '<span class="badge badge-light">' + data + '</span>';
                            break;
                    }
                    // Badge extra si siniestro no fue presentado
                    if (row.presentado === false || row.presentado === 'f' || row.presentado === 0 || row.presentado === '0') {
                        estado += ' <span class="badge badge-dark">No presentado</span>';
                    }
                    return estado;
                }
            },
            {
                targets: 4,
                render: function(data, type, full) {
                    if (data == null || data == "0000-00-00") {
                        return '';
                    } else {
                        return moment(data).format('YYYY/MM/DD');
                    }
                }
            }
        ],
        "order": [
            [4, "desc"]
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
    $("#listado_siniestros_filter input")
    .off()
    .on('keyup change', function (e) {
    if (e.keyCode !== 13 || this.value == "") {
        var texto1=this.value.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
         table.search(texto1)
            .draw();
    }
    });
    $('#listado_siniestros tbody').on('click', 'td.details-control', function() {
        var tr = $(this).closest('tr');
        var row = table.row(tr);

        if (row.child.isShown()) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
        } else {
            // Open this row
            row.child(format_siniestro(row.data())).show();
            tr.addClass('shown');
        }
    });
    $('#listado_siniestros').dataTable().fnFilter(document.getElementById("var1").value);
});

function format_siniestro(d) {
    // `d` is the original data object for the row

    var seccion_vehiculo = '';
    if (d.vehiculos && d.vehiculos.length > 0) {
        var filas_veh = '';
        for (var vi = 0; vi < d.vehiculos.length; vi++) {
            var v = d.vehiculos[vi];
            filas_veh +=
                '<tr>' +
                    '<td>Ítem ' + v.numero_item + '</td>' +
                    '<td>' + (v.patente || '') + '</td>' +
                    '<td>' + (v.marca || '') + '</td>' +
                    '<td>' + (v.modelo || '') + '</td>' +
                    '<td>' + (v.anio_vehiculo || '') + '</td>' +
                '</tr>';
        }
        seccion_vehiculo =
            '<tr>' +
                '<td VALIGN=TOP>Vehículos: </td>' +
                '<td>' +
                    '<table class="table table-striped" style="width:100%">' +
                        '<thead><tr>' +
                            '<th>Ítem</th><th>Patente</th><th>Marca</th><th>Modelo</th><th>Año</th>' +
                        '</tr></thead>' +
                        '<tbody>' + filas_veh + '</tbody>' +
                    '</table>' +
                '</td>' +
            '</tr>';
    }

    var seccion_taller = '';
    if (d.taller_nombre) {
        seccion_taller =
            '<tr>' +
                '<td VALIGN=TOP>Taller: </td>' +
                '<td>' +
                    '<table class="table table-striped" style="width:100%">' +
                        '<tr>' +
                            '<td>Nombre:</td>' +
                            '<td>' + (d.taller_nombre || '') + '</td>' +
                        '</tr>' +
                        '<tr>' +
                            '<td>Teléfono:</td>' +
                            '<td>' + (d.taller_telefono || '') + '</td>' +
                        '</tr>' +
                    '</table>' +
                '</td>' +
            '</tr>';
    }

    var botones =
        '<button title="Editar Siniestro" type="button" id="' + d.id_siniestro + '" name="editar_siniestro" onclick="botones(this.id, this.name)"><i class="fas fa-edit"></i></button><a> </a>' +
        '<button style="background-color: #FF0000" title="Eliminar Siniestro" type="button" id="' + d.id_siniestro + '" name="eliminar_siniestro" onclick="botones(this.id, this.name)"><i class="fas fa-trash"></i></button><a> </a>' +
        '<button title="Crear Tarea" type="button" id="' + d.id_siniestro + '" name="tarea_siniestro" onclick="botones(this.id, this.name)"><i class="fas fa-clipboard-list"></i></button>';

    return '<table background-color:#F6F6F6; color:#FFF; cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">' +
        '<tr>' +
            '<td VALIGN=TOP>Asegurado: </td>' +
            '<td>' +
                '<table class="table table-striped" style="width:100%">' +
                    '<tr>' +
                        '<td>Nombre:</td>' +
                        '<td>' + (d.nombre_asegurado || d.nom_cliente || '') + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<td>RUT:</td>' +
                        '<td>' + (d.rut_asegurado || '') + (d.dv_asegurado ? '-' + d.dv_asegurado : '') + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<td>Teléfono:</td>' +
                        '<td>' + (d.telefono_asegurado || d.tel_cliente || '') + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<td>Correo:</td>' +
                        '<td>' + (d.correo_asegurado || d.correo_cliente || '') + '</td>' +
                    '</tr>' +
                '</table>' +
            '</td>' +
        '</tr>' +
        '<tr>' +
            '<td VALIGN=TOP>Descripción: </td>' +
            '<td>' + (d.descripcion || '') + '</td>' +
        '</tr>' +
        '<tr>' +
            '<td VALIGN=TOP>Liquidador: </td>' +
            '<td>' +
                '<table class="table table-striped" style="width:100%">' +
                    '<tr>' +
                        '<td>Nombre:</td>' +
                        '<td>' + (d.liquidador_nombre || '') + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<td>Teléfono:</td>' +
                        '<td>' + (d.liquidador_telefono || '') + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<td>Correo:</td>' +
                        '<td>' + (d.liquidador_correo || '') + '</td>' +
                    '</tr>' +
                '</table>' +
            '</td>' +
        '</tr>' +
        seccion_vehiculo +
        seccion_taller +
        '<tr>' +
            '<td VALIGN=TOP>Acciones: </td>' +
            '<td>' +
            botones +
            '</td>' +
        '</tr>' +
        '</table>';
}

function botones(id, accion) {
    console.log("ID:" + id + " => acción:" + accion);
    switch (accion) {
        case "editar_siniestro": {
            $.redirect('/bambooQA/creacion_siniestro.php', {
                'id_siniestro': id,
                'accion': 'modifica_siniestro'
            }, 'post');
            break;
        }
        case "eliminar_siniestro": {
            var r2 = confirm("Estás a punto de eliminar este siniestro ¿Deseas continuar?");
            if (r2 == true) {
                $.redirect('/bambooQA/backend/siniestros/elimina_siniestro.php', {
                    'id_siniestro': id,
                    'accion': accion
                }, 'post');
            }
            break;
        }
        case "tarea_siniestro": {
            $.redirect('/bambooQA/creacion_actividades.php', {
                'id_siniestro': id
            }, 'post');
            break;
        }
    }
}

(function(){

 function removeAccents ( data ) {
     if ( data.normalize ) {
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
