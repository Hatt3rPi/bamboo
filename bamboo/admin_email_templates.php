<?php
if (!isset($_SESSION)) { session_start(); }

$page_title       = 'Plantillas de correo · Bamboo Seguros';
$page_active      = 'correos';
$breadcrumb_main  = 'Plantillas Brevo';
$breadcrumb_sub   = 'Correos';
$tab_correos      = 'plantillas';
require_once 'layout.php';
?>

<style>
.var-chip { cursor:pointer; margin:2px; font-family:var(--font-mono); }
.preview-box { background:var(--bg-subtle); border:1px solid var(--border-default); border-radius:var(--radius-sm); padding:12px; white-space:pre-wrap; font-family:Arial,sans-serif; }
.preview-subject { font-weight:bold; border-bottom:1px solid var(--border-default); padding-bottom:6px; margin-bottom:8px; }
</style>

<div class="bb-page-header">
  <div>
    <h1>Plantillas Brevo</h1>
    <div class="subtitle">Plantillas de correo administradas por Brevo (módulo siniestros y otros)</div>
  </div>
  <button class="btn btn-bamboo" onclick="abrirModalTemplate(null)">
    <i class="fas fa-plus mr-2"></i>Nueva plantilla
  </button>
</div>

<?php include '_tabs_correos.php'; ?>

<div class="card">
  <div class="card-body">
    <table id="tabla_templates" class="display w-100">
      <thead>
        <tr>
          <th>Código</th>
          <th>Nombre</th>
          <th>Módulo</th>
          <th>Asunto</th>
          <th>Activa</th>
          <th>Última edición</th>
          <th>Acciones</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<!-- MODAL EDITOR -->
<div class="modal fade" id="modalTemplate" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document" style="max-width:min(1500px, 95vw)">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTemplateTitle">Plantilla</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="tpl_id">
        <div class="form-row">
          <div class="col-md-4 form-group">
            <label>Código <span style="color:darkred">*</span></label>
            <input type="text" id="tpl_codigo" class="form-control" placeholder="siniestro_liquidador_vehiculo">
          </div>
          <div class="col-md-5 form-group">
            <label>Nombre <span style="color:darkred">*</span></label>
            <input type="text" id="tpl_nombre" class="form-control" placeholder="Notificación al liquidador — vehículos">
          </div>
          <div class="col-md-2 form-group">
            <label>Módulo</label>
            <input type="text" id="tpl_modulo" class="form-control" value="siniestros">
          </div>
          <div class="col-md-1 form-group">
            <label>Activa</label>
            <div><input type="checkbox" id="tpl_activo" checked></div>
          </div>
        </div>
        <div class="form-group">
          <label>Asunto <span style="color:darkred">*</span></label>
          <input type="text" id="tpl_asunto" class="form-control" oninput="refrescarPreview()">
        </div>
        <div class="form-group">
          <label>Cuerpo (texto plano) <span style="color:darkred">*</span></label>
          <textarea id="tpl_cuerpo_texto" class="form-control" rows="10" oninput="refrescarPreview()"></textarea>
        </div>
        <div class="form-group">
          <label>Cuerpo HTML <small class="text-muted">(opcional — si está vacío se genera automáticamente desde el texto)</small></label>
          <textarea id="tpl_cuerpo_html" class="form-control" rows="5" oninput="refrescarPreview()" placeholder="<div>...</div>"></textarea>
        </div>

        <div class="form-row">
          <div class="col-md-6">
            <label>Variables disponibles <small class="text-muted">(click para insertar)</small></label>
            <div id="tpl_variables" class="mb-2"></div>
          </div>
          <div class="col-md-6">
            <label>Preview con datos de ejemplo</label>
            <div class="preview-box" style="min-height:200px">
              <div id="preview_subject" class="preview-subject">—</div>
              <div id="preview_body">—</div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-bamboo" onclick="guardarTemplate()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<?php require_once 'layout_end.php'; ?>

<script>
// Variables conocidas por módulo — para este MVP hardcoded (se podrían traer de BD).
// Mientras no haya más módulos, todos los templates tienen las mismas variables.
var variablesPorModulo = {
    siniestros: [
        { nombre: 'liquidador_nombre',         ejemplo: 'Juan Pérez' },
        { nombre: 'nombre_asegurado',          ejemplo: 'María Soto' },
        { nombre: 'numero_siniestro',          ejemplo: '2026-001234' },
        { nombre: 'numero_carpeta_liquidador', ejemplo: 'CRP-0012' },
        { nombre: 'numero_poliza',             ejemplo: 'POL-56789' },
        { nombre: 'ramo',                      ejemplo: 'VEHÍCULOS' },
        { nombre: 'carpeta_suffix',            ejemplo: ' — Carpeta CRP-0012' }
    ]
};

function escHtml(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function variablesActuales() {
    var mod = $('#tpl_modulo').val() || 'siniestros';
    return variablesPorModulo[mod] || [];
}

function renderChipsVariables() {
    var $c = $('#tpl_variables').empty();
    variablesActuales().forEach(function(v) {
        var chip = $('<span class="badge badge-light var-chip">{{ ' + v.nombre + ' }}</span>');
        chip.on('click', function() { insertarVariableFocus('{{ ' + v.nombre + ' }}'); });
        $c.append(chip);
    });
}

function insertarVariableFocus(texto) {
    var target = document.activeElement;
    if (!target || !target.matches || !target.matches('input#tpl_asunto, textarea#tpl_cuerpo_texto, textarea#tpl_cuerpo_html')) {
        target = document.getElementById('tpl_cuerpo_texto');
    }
    var start = target.selectionStart, end = target.selectionEnd;
    var val = target.value;
    target.value = val.substring(0, start) + texto + val.substring(end);
    target.selectionStart = target.selectionEnd = start + texto.length;
    target.focus();
    refrescarPreview();
}

function aplicarVariables(tmpl, vars) {
    return (tmpl || '').replace(/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*(?:\|([^}]*?))?\s*\}\}/g,
        function(m, nombre, def) {
            var v = vars[nombre];
            if (v === undefined || v === null || v === '') return (def || '').trim();
            return v;
        });
}

function refrescarPreview() {
    var ejemplo = {};
    variablesActuales().forEach(function(v) { ejemplo[v.nombre] = v.ejemplo; });
    var asuntoRend = aplicarVariables($('#tpl_asunto').val(), ejemplo);
    var textoRend  = aplicarVariables($('#tpl_cuerpo_texto').val(), ejemplo);
    var htmlRend   = $('#tpl_cuerpo_html').val()
        ? aplicarVariables($('#tpl_cuerpo_html').val(), ejemplo)
        : '<pre style="white-space:pre-wrap;font-family:Arial">' + escHtml(textoRend) + '</pre>';
    $('#preview_subject').text(asuntoRend || '—');
    $('#preview_body').html(htmlRend || '—');
}

var tabla;
$(function() {
    tabla = $('#tabla_templates').DataTable({
        ajax: { url: '/bamboo/backend/email/busqueda_templates.php' },
        columns: [
            { data: 'codigo' },
            { data: 'nombre' },
            { data: 'modulo' },
            { data: 'asunto' },
            { data: 'activo', render: function(v) {
                return v ? '<span class="badge badge-success">Activa</span>'
                         : '<span class="badge badge-secondary">Inactiva</span>';
            } },
            { data: 'updated_at' },
            { data: null, orderable:false, render: function(r) {
                return '<button class="btn btn-sm btn-outline-secondary mr-1" onclick="abrirModalTemplate(' + r.id + ')">✏️ Editar</button>' +
                       '<button class="btn btn-sm btn-outline-danger" onclick="eliminarTemplate(' + r.id + ')">🗑️</button>';
            } }
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json' }
    });

    $('#tpl_modulo').on('change', renderChipsVariables);
});

function abrirModalTemplate(id) {
    if (id) {
        $.getJSON('/bamboo/backend/email/busqueda_templates.php', function(resp) {
            var t = (resp.data || []).find(function(x){ return x.id == id; });
            if (!t) { alert('No encontrada'); return; }
            $('#modalTemplateTitle').text('Editar plantilla — ' + t.codigo);
            $('#tpl_id').val(t.id);
            $('#tpl_codigo').val(t.codigo);
            $('#tpl_nombre').val(t.nombre);
            $('#tpl_modulo').val(t.modulo);
            $('#tpl_asunto').val(t.asunto);
            $('#tpl_cuerpo_texto').val(t.cuerpo_texto);
            $('#tpl_cuerpo_html').val(t.cuerpo_html || '');
            $('#tpl_activo').prop('checked', !!t.activo);
            renderChipsVariables();
            refrescarPreview();
            $('#modalTemplate').modal('show');
        });
    } else {
        $('#modalTemplateTitle').text('Nueva plantilla');
        $('#tpl_id').val('');
        $('#tpl_codigo').val('');
        $('#tpl_nombre').val('');
        $('#tpl_modulo').val('siniestros');
        $('#tpl_asunto').val('');
        $('#tpl_cuerpo_texto').val('');
        $('#tpl_cuerpo_html').val('');
        $('#tpl_activo').prop('checked', true);
        renderChipsVariables();
        refrescarPreview();
        $('#modalTemplate').modal('show');
    }
}

function guardarTemplate() {
    var codigo = $.trim($('#tpl_codigo').val());
    var nombre = $.trim($('#tpl_nombre').val());
    var asunto = $.trim($('#tpl_asunto').val());
    var cuerpo = $.trim($('#tpl_cuerpo_texto').val());
    if (!codigo || !nombre || !asunto || !cuerpo) { alert('Código, nombre, asunto y cuerpo son obligatorios.'); return; }
    if (!/^[a-z][a-z0-9_]*$/.test(codigo)) { alert('Código inválido. Use solo letras minúsculas, números y _ (ej: siniestro_liquidador_vehiculo).'); return; }
    $.post('/bamboo/backend/email/guarda_template.php', {
        id: $('#tpl_id').val(),
        codigo: codigo,
        nombre: nombre,
        modulo: $('#tpl_modulo').val(),
        asunto: asunto,
        cuerpo_texto: cuerpo,
        cuerpo_html: $('#tpl_cuerpo_html').val(),
        activo: $('#tpl_activo').is(':checked') ? '1' : '0'
    }, null, 'json').done(function(resp) {
        if (resp && resp.ok) {
            $('#modalTemplate').modal('hide');
            tabla.ajax.reload(null, false);
        } else {
            alert('No se pudo guardar: ' + (resp && resp.mensaje ? resp.mensaje : 'error'));
        }
    });
}

function eliminarTemplate(id) {
    if (!confirm('¿Eliminar esta plantilla? No afecta a correos ya enviados.')) return;
    $.post('/bamboo/backend/email/elimina_template.php', { id: id }, null, 'json')
     .done(function(resp) {
        if (resp && resp.ok) tabla.ajax.reload(null, false);
        else alert('Error: ' + (resp && resp.mensaje ? resp.mensaje : 'error'));
     });
}
</script>
