<?php
if (!isset($_SESSION)) { session_start(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="/bamboo/images/bamboo.png">
<title>Bamboo — Plantillas de correo</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"
    integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
<link rel="stylesheet" href="/assets/css/datatables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="/assets/css/layout.css">
<script src="https://kit.fontawesome.com/7011384382.js" crossorigin="anonymous"></script>
<style>
.var-chip { cursor:pointer; margin:2px; font-family:monospace; }
.preview-box { background:#f8f9fa; border:1px solid #dee2e6; border-radius:4px; padding:12px; white-space:pre-wrap; font-family:Arial,sans-serif; }
.preview-subject { font-weight:bold; border-bottom:1px solid #dee2e6; padding-bottom:6px; margin-bottom:8px; }
</style>
</head>
<body>
<div id="header"><?php include 'header2.php' ?></div>
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"
    integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

<div class="container">
  <p>Administración / Plantillas de correo</p>
  <div class="mb-3">
    <button class="btn btn-primary btn-sm" onclick="abrirModalTemplate(null)">➕ Nueva plantilla</button>
  </div>
  <table id="tabla_templates" class="display" style="width:100%">
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

<!-- MODAL EDITOR -->
<div class="modal fade" id="modalTemplate" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
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
        <button type="button" class="btn btn-primary" onclick="guardarTemplate()">Guardar</button>
      </div>
    </div>
  </div>
</div>

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
</body>
</html>
