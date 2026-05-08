<?php
if (!isset($_SESSION)) { session_start(); }

$page_title       = 'Catálogo de documentos · Bamboo Seguros';
$page_active      = 'siniestros';
$breadcrumb_main  = 'Catálogo de documentos';
$breadcrumb_sub   = 'Siniestros';
$tab_siniestros   = 'catalogo';
require_once 'layout.php';
?>

<div class="bb-page-header">
  <div>
    <h1>Catálogo de documentos</h1>
    <div class="subtitle">Documentos solicitables por bien afectado en un siniestro</div>
  </div>
  <button id="btn_nuevo" class="btn btn-bamboo">
    <i class="fas fa-plus mr-2"></i>Nuevo documento
  </button>
</div>

<?php include '_tabs_siniestros.php'; ?>

<div class="card mb-3">
  <div class="card-body">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="chk_inactivos">
      <label class="form-check-label" for="chk_inactivos">Incluir inactivos</label>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <table id="tabla_catalogo" class="display w-100">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Descripción</th>
          <th>Orden</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<!-- Modal crear/editar -->
<div class="modal fade" id="modal_doc" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modal_title">Nuevo documento</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="doc_id">
        <div class="form-group">
          <label for="doc_nombre">Nombre <span style="color:darkred">*</span></label>
          <input type="text" class="form-control" id="doc_nombre" maxlength="200">
        </div>
        <div class="form-group">
          <label for="doc_descripcion">Descripción</label>
          <textarea class="form-control" id="doc_descripcion" rows="3"></textarea>
        </div>
        <div class="form-group">
          <label for="doc_orden">Orden (menor = aparece primero)</label>
          <input type="number" class="form-control" id="doc_orden" value="0" min="0">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-bamboo" id="btn_guardar">Guardar</button>
      </div>
    </div>
  </div>
</div>

<?php require_once 'layout_end.php'; ?>

<script>
var tabla;
var endpoint_crud = '/bamboo/backend/siniestros/crea_documento_catalogo.php';
var endpoint_list = '/bamboo/backend/siniestros/busqueda_listado_catalogo_documentos.php';

$(function() {
  tabla = $('#tabla_catalogo').DataTable({
    ajax: { url: endpoint_list, data: function(d){ d.incluir_inactivos = $('#chk_inactivos').is(':checked') ? 1 : 0; } },
    columns: [
      { data: 'id' },
      { data: 'nombre' },
      { data: 'descripcion', defaultContent: '' },
      { data: 'orden' },
      { data: 'activo', render: function(v) {
          return v == 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-secondary">Inactivo</span>';
      }},
      { data: null, orderable: false, render: function(r) {
          var btnEdit = '<button class="btn btn-sm btn-info btn-edit" data-id="'+r.id+'"><i class="fas fa-edit"></i></button> ';
          var btnTog  = r.activo == 1
              ? '<button class="btn btn-sm btn-warning btn-toggle" data-id="'+r.id+'" data-action="desactivar_documento">Desactivar</button>'
              : '<button class="btn btn-sm btn-success btn-toggle" data-id="'+r.id+'" data-action="activar_documento">Activar</button>';
          return btnEdit + btnTog;
      }}
    ],
    order: [[3, 'asc']],
    language: { url: '//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json' }
  });

  $('#chk_inactivos').on('change', function(){ tabla.ajax.reload(); });

  $('#btn_nuevo').on('click', function() {
    $('#modal_title').text('Nuevo documento');
    $('#doc_id').val('');
    $('#doc_nombre').val('');
    $('#doc_descripcion').val('');
    $('#doc_orden').val('0');
    $('#modal_doc').modal('show');
  });

  $('#tabla_catalogo').on('click', '.btn-edit', function() {
    var row = tabla.row($(this).closest('tr')).data();
    $('#modal_title').text('Editar documento');
    $('#doc_id').val(row.id);
    $('#doc_nombre').val(row.nombre);
    $('#doc_descripcion').val(row.descripcion || '');
    $('#doc_orden').val(row.orden);
    $('#modal_doc').modal('show');
  });

  $('#tabla_catalogo').on('click', '.btn-toggle', function() {
    var id = $(this).data('id');
    var action = $(this).data('action');
    if (!confirm('¿Confirmar ' + (action === 'desactivar_documento' ? 'desactivación' : 'activación') + '?')) return;
    $.post(endpoint_crud, { accion: action, id: id }, function(resp) {
      alert(resp.mensaje || 'OK');
      if (resp.ok) tabla.ajax.reload();
    }, 'json');
  });

  $('#btn_guardar').on('click', function() {
    var id = $('#doc_id').val();
    var data = {
      accion: id === '' ? 'crear_documento' : 'actualizar_documento',
      id: id,
      nombre: $('#doc_nombre').val(),
      descripcion: $('#doc_descripcion').val(),
      orden: $('#doc_orden').val()
    };
    if (!data.nombre.trim()) { alert('El nombre es obligatorio.'); return; }
    $.post(endpoint_crud, data, function(resp) {
      alert(resp.mensaje || 'OK');
      if (resp.ok) { $('#modal_doc').modal('hide'); tabla.ajax.reload(); }
    }, 'json');
  });
});
</script>
