<?php
if (!isset($_SESSION)) { session_start(); }

// Filtro opcional por siniestro (para deeplink desde listado_siniestros)
$filtro_id_siniestro = isset($_GET['id_siniestro']) && ctype_digit($_GET['id_siniestro']) ? $_GET['id_siniestro'] : '';
$titulo_extra = '';
if ($filtro_id_siniestro !== '') {
    require_once "/home/gestio10/public_html/backend/config.php";
    db_set_charset($link, 'utf8');
    db_select_db($link, DB_NAME);
    $r = db_query($link, "SELECT COALESCE(numero_siniestro,'') AS ns, numero_poliza FROM siniestros WHERE id='$filtro_id_siniestro' LIMIT 1");
    while ($row = db_fetch_object($r)) {
        $ns = $row->ns !== '' ? $row->ns : 'S' . str_pad($filtro_id_siniestro, 6, '0', STR_PAD_LEFT);
        $titulo_extra = ' / Siniestro ' . htmlspecialchars($ns) . ' (Póliza ' . htmlspecialchars($row->numero_poliza) . ')';
    }
    db_close($link);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="/bambooQA/images/bamboo.png">
<title>Bamboo - Seguimiento bienes afectados</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"
    integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
<link rel="stylesheet" href="/assets/css/datatables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css">
<script src="https://kit.fontawesome.com/7011384382.js" crossorigin="anonymous"></script>
</head>
<body>
<div id="header"><?php include 'header2.php' ?></div>
<!-- jQuery full (sobreescribe al slim que carga header2) y DataTables -->
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>

<div class="container">
  <p>Siniestros / Seguimiento bienes afectados<?php echo $titulo_extra; ?>
    <?php if ($filtro_id_siniestro !== ''): ?>
      &nbsp;<a class="btn btn-sm btn-outline-secondary" href="/bambooQA/seguimiento_bienes_afectados.php">Ver todos</a>
    <?php endif; ?>
  </p>
  <input type="hidden" id="filtro_id_siniestro" value="<?php echo htmlspecialchars($filtro_id_siniestro); ?>">

  <div class="form-row mb-3">
    <div class="col-md-3">
      <label>Estado</label>
      <select class="form-control" id="filtro_estado">
        <option value="">Todos</option>
        <option value="Abierto">Abierto</option>
        <option value="Cerrado">Cerrado</option>
        <option value="Rechazado">Rechazado</option>
      </select>
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="chk_alarma_proxima">
        <label class="form-check-label" for="chk_alarma_proxima">Solo alarmas próximas (7 días)</label>
      </div>
    </div>
  </div>

  <table id="tabla_bienes" class="display" style="width:100%">
    <thead>
      <tr>
        <th>N° Siniestro</th>
        <th>Póliza</th>
        <th>Ramo</th>
        <th>Tipo</th>
        <th>Bien afectado</th>
        <th>Estado</th>
        <th>Alarma</th>
        <th>Docs</th>
        <th>Acciones</th>
      </tr>
    </thead>
  </table>
</div>

<!-- Modal Checklist (reutilizado del form) -->
<div class="modal fade" id="modalChecklist" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="checklistTitle">Checklist</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="checklist_id_bien">
        <table class="table table-sm table-bordered">
          <thead>
            <tr><th>Documento</th><th style="width:18%">Estado</th><th style="width:18%">Fecha entrega</th><th style="width:30%">Notas</th></tr>
          </thead>
          <tbody id="checklist_body"></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="guardarChecklist()">Guardar checklist</button>
      </div>
    </div>
  </div>
</div>

<script>
var tabla;

function escHtml(s) { return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escAttr(s) { return (s||'').replace(/'/g,"\\'").replace(/"/g,'&quot;'); }
function badgeBien(estado) {
    var cls = 'badge-secondary';
    if (estado === 'Abierto')   cls = 'badge-primary';
    if (estado === 'Cerrado')   cls = 'badge-secondary';
    if (estado === 'Rechazado') cls = 'badge-danger';
    return '<span class="badge ' + cls + '">' + estado + '</span>';
}

$(function() {
    tabla = $('#tabla_bienes').DataTable({
        ajax: {
            url: '/bambooQA/backend/siniestros/busqueda_listado_bienes.php',
            data: function(d) {
                d.estado = $('#filtro_estado').val();
                d.alarma_proxima = $('#chk_alarma_proxima').is(':checked') ? 1 : 0;
                var idSin = $('#filtro_id_siniestro').val();
                if (idSin) d.id_siniestro = idSin;
            }
        },
        columns: [
            { data: 'numero_siniestro', defaultContent: '<em>pendiente</em>' },
            { data: 'numero_poliza' },
            { data: 'ramo' },
            { data: 'tipo', render: function(v) {
                return v === 'propio'
                    ? '<span class="badge badge-info">Propio</span>'
                    : '<span class="badge badge-warning">Tercero</span>';
            }},
            { data: 'descripcion' },
            { data: 'estado', render: badgeBien },
            { data: 'fecha_alarma', defaultContent: '—' },
            { data: null, render: function(r) {
                if (!r.docs || r.docs.length === 0) return '<em>sin marcar</em>';
                var iconoEstado = function(est) {
                    switch (est) {
                        case 'Entregado':   return '<span style="color:#28a745;font-weight:bold">✓</span>';
                        case 'En revisión': return '<span style="color:#17a2b8">◐</span>';
                        case 'Rechazado':   return '<span style="color:#dc3545;font-weight:bold">✗</span>';
                        case 'No aplica':   return '<span style="color:#6c757d">—</span>';
                        default:            return '<span style="color:#ffc107">●</span>'; // Pendiente u otro
                    }
                };
                var lineas = r.docs.map(function(d) {
                    return '<div style="white-space:nowrap;line-height:1.4" title="' + escAttr(d.estado) + '">' +
                           iconoEstado(d.estado) + ' ' + escHtml(d.nombre) + '</div>';
                }).join('');
                var resumen = '<small class="text-muted">' + r.entregados + '/' + r.total_docs + ' OK</small>';
                return '<div style="max-width:260px">' + lineas + resumen + '</div>';
            }},
            { data: null, orderable: false, render: function(r) {
                return '<button class="btn btn-sm btn-secondary" onclick="abrirChecklist(' + r.id + ',\'' + escAttr(r.descripcion) + '\')">' +
                         '<i class="fas fa-list-check"></i> Checklist' +
                       '</button> ' +
                       '<a class="btn btn-sm btn-info" href="javascript:void(0)" onclick="irASiniestro(' + r.id_siniestro + ')"><i class="fas fa-external-link-alt"></i></a>';
            }}
        ],
        order: [[6, 'asc']],
        language: { url: '//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json' }
    });

    $('#filtro_estado, #chk_alarma_proxima').on('change', function() { tabla.ajax.reload(); });
});

function irASiniestro(id_siniestro) {
    var form = $('<form method="POST" action="/bambooQA/creacion_siniestro.php">' +
                 '<input name="accion" value="modifica_siniestro">' +
                 '<input name="id_siniestro" value="' + id_siniestro + '">' +
                 '</form>');
    $('body').append(form);
    form.submit();
}

function abrirChecklist(id_bien, descripcion) {
    $('#checklistTitle').text('Checklist — ' + descripcion);
    $('#checklist_id_bien').val(id_bien);
    $('#checklist_body').html('<tr><td colspan="4"><em>Cargando…</em></td></tr>');
    $.getJSON('/bambooQA/backend/siniestros/busqueda_checklist_bien.php', { id_bien: id_bien }, function(resp) {
        var body = $('#checklist_body');
        body.empty();
        (resp.data || []).forEach(function(d) {
            var estados = ['Pendiente','En revisión','Entregado','Rechazado','No aplica'];
            var opts = estados.map(function(e) {
                return '<option value="' + e + '"' + (e === d.estado ? ' selected' : '') + '>' + e + '</option>';
            }).join('');
            body.append(
                '<tr data-id-doc="' + d.id_documento + '">' +
                  '<td>' + escHtml(d.nombre) + '</td>' +
                  '<td><select class="form-control form-control-sm cl-estado">' + opts + '</select></td>' +
                  '<td><input type="date" class="form-control form-control-sm cl-fecha" value="' + (d.fecha_entrega || '') + '"></td>' +
                  '<td><input type="text" class="form-control form-control-sm cl-notas" value="' + escAttr(d.notas || '') + '"></td>' +
                '</tr>'
            );
        });
        if ((resp.data || []).length === 0) {
            body.html('<tr><td colspan="4"><em>No hay documentos activos en el catálogo.</em></td></tr>');
        }
    });
    $('#modalChecklist').modal('show');
}

function guardarChecklist() {
    var id_bien = $('#checklist_id_bien').val();
    var filas = $('#checklist_body tr[data-id-doc]');
    if (filas.length === 0) { $('#modalChecklist').modal('hide'); return; }
    var promesas = [];
    filas.each(function() {
        var $tr = $(this);
        promesas.push($.post('/bambooQA/backend/siniestros/actualiza_documento_bien.php', {
            id_bien: id_bien,
            id_documento: $tr.data('id-doc'),
            estado: $tr.find('.cl-estado').val(),
            fecha_entrega: $tr.find('.cl-fecha').val(),
            notas: $tr.find('.cl-notas').val()
        }));
    });
    $.when.apply($, promesas).done(function() {
        $('#modalChecklist').modal('hide');
        tabla.ajax.reload();
    }).fail(function() {
        alert('Hubo errores al guardar algunos documentos.');
    });
}
</script>
</body>
</html>
