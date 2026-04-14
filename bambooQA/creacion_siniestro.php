<?php
if (!isset($_SESSION)) {
    session_start();
}

// Solo es accesible vía POST desde listado_polizas (crear) o desde el botón editar (modificar).
// Cualquier acceso directo (GET o POST sin contexto) redirige al listado de pólizas.
$es_creacion_valida  = $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id_poliza"]) && !isset($_POST["accion"]);
$es_edicion_valida   = $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == 'modifica_siniestro' && isset($_POST["id_siniestro"]);
if (!$es_creacion_valida && !$es_edicion_valida) {
    header("Location: /bambooQA/listado_polizas.php");
    exit;
}

$camino = 'crear_siniestro';
$numero_siniestro = $id_poliza = $numero_poliza = $ramo = '';
$tipo_siniestro = $fecha_ocurrencia = $fecha_denuncia = '';
$rut_asegurado = $dv_asegurado = $nombre_asegurado = $telefono_asegurado = $correo_asegurado = '';
$descripcion = '';
$liquidador_nombre = $liquidador_telefono = $liquidador_correo = $numero_carpeta_liquidador = '';
$patente = $marca = $modelo = $anio_vehiculo = '';
$taller_nombre = $taller_telefono = '';
$estado = 'Abierto';
$presentado = true;
$compania = '';
$id_siniestro = '';
$items_seleccionados_csv = '';

// Ingreso desde listado de pólizas (creación pre-poblada)
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST["accion"]) && isset($_POST["id_poliza"])) {
    require_once "/home/gestio10/public_html/backend/config.php";
    db_set_charset($link, 'utf8');
    db_select_db($link, DB_NAME);

    $id_poliza_param = preg_replace('/[^0-9]/', '', $_POST["id_poliza"]);
    $items_csv_param = preg_replace('/[^0-9,]/', '', $_POST["items_seleccionados"] ?? '');
    $items_seleccionados_csv = $items_csv_param;

    $res = db_query($link, "SELECT p.id, p.numero_poliza, p.ramo, p.compania,
                                   CONCAT_WS(' ', c.nombre_cliente, c.apellido_paterno, c.apellido_materno) AS nombre_cliente,
                                   c.rut_sin_dv, c.dv, c.telefono, c.correo
                            FROM polizas_2 p
                            LEFT JOIN clientes c ON p.rut_proponente = c.rut_sin_dv
                            WHERE p.id = '$id_poliza_param'
                            LIMIT 1");
    while ($row = db_fetch_object($res)) {
        $id_poliza          = $row->id;
        $numero_poliza      = $row->numero_poliza;
        $ramo               = $row->ramo;
        $compania           = $row->compania;
        $nombre_asegurado   = $row->nombre_cliente;
        $rut_asegurado      = $row->rut_sin_dv;
        $dv_asegurado       = $row->dv;
        $telefono_asegurado = $row->telefono;
        $correo_asegurado   = $row->correo;
    }
    db_close($link);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == 'modifica_siniestro' && isset($_POST["id_siniestro"])) {
    $camino = 'modifica_siniestro';
    $id_siniestro = $_POST["id_siniestro"];
    require_once "/home/gestio10/public_html/backend/config.php";
    db_set_charset($link, 'utf8');
    db_select_db($link, DB_NAME);
    $resultado = db_query($link, "SELECT s.*, p.compania FROM siniestros s LEFT JOIN polizas_2 p ON s.id_poliza = p.id WHERE s.id = '" . $id_siniestro . "'");
    while ($row = db_fetch_object($resultado)) {
        $id_siniestro              = $row->id;
        $numero_siniestro          = $row->numero_siniestro;
        $id_poliza                 = $row->id_poliza;
        $numero_poliza             = $row->numero_poliza;
        $ramo                      = $row->ramo;
        $tipo_siniestro            = $row->tipo_siniestro;
        $fecha_ocurrencia          = $row->fecha_ocurrencia;
        $fecha_denuncia            = $row->fecha_denuncia;
        $rut_asegurado             = $row->rut_asegurado;
        $dv_asegurado              = $row->dv_asegurado;
        $nombre_asegurado          = $row->nombre_asegurado;
        $telefono_asegurado        = $row->telefono_asegurado;
        $correo_asegurado          = $row->correo_asegurado;
        $descripcion               = str_replace("\r\n", "\\n", $row->descripcion);
        $liquidador_nombre         = $row->liquidador_nombre;
        $liquidador_telefono       = $row->liquidador_telefono;
        $liquidador_correo         = $row->liquidador_correo;
        $numero_carpeta_liquidador = $row->numero_carpeta_liquidador;
        $patente                   = $row->patente;
        $marca                     = $row->marca;
        $modelo                    = $row->modelo;
        $anio_vehiculo             = $row->anio_vehiculo;
        $taller_nombre             = $row->taller_nombre;
        $taller_telefono           = $row->taller_telefono;
        $estado                    = $row->estado;
        $presentado                = $row->presentado;
        $compania                  = $row->compania;
    }

    // Ítems asociados + datos de vehículo por ítem
    $items_pre = array();
    $vehiculos_pre = array(); // map numero_item => {patente, marca, modelo, anio}
    $res_items = db_query($link, "SELECT numero_item, patente, marca, modelo, anio_vehiculo FROM siniestros_items WHERE id_siniestro = '$id_siniestro' ORDER BY numero_item");
    while ($row = db_fetch_object($res_items)) {
        $items_pre[] = $row->numero_item;
        $vehiculos_pre[$row->numero_item] = array(
            'patente' => $row->patente,
            'marca'   => $row->marca,
            'modelo'  => $row->modelo,
            'anio'    => $row->anio_vehiculo
        );
    }
    $items_seleccionados_csv = implode(',', $items_pre);
    db_close($link);
}
if (!isset($vehiculos_pre)) $vehiculos_pre = array();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="/bambooQA/images/bamboo.png">
<title>Bamboo - Siniestros</title>
<!-- Bootstrap -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"
    integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
<link rel="stylesheet" href="/assets/css/datatables.min.css">
</head>
<body>
<div id="header">
<?php include 'header2.php' ?>
</div>

<div class="container">
  <p>Siniestros / <?php echo ($camino == 'modifica_siniestro') ? 'Modificación / N° ' . $numero_siniestro : 'Creación'; ?><br></p>

  <!-- =============================================================== -->
  <!-- FORMULARIO -->
  <!-- =============================================================== -->
  <form id="formulario_siniestro">
    <input type="hidden" id="id_siniestro"          name="id_siniestro"          value="<?php echo $id_siniestro; ?>">
    <input type="hidden" id="id_poliza"             name="id_poliza"             value="<?php echo $id_poliza; ?>">
    <input type="hidden" id="ramo"                  name="ramo"                  value="<?php echo $ramo; ?>">
    <input type="hidden" id="items_seleccionados"   name="items_seleccionados"   value="<?php echo $items_seleccionados_csv; ?>">

    <!-- ==================== SECCIÓN 1: PÓLIZA ASOCIADA ==================== -->
    <h5 class="form-row">&nbsp;Póliza asociada</h5><br>
    <div class="form-row">
      <div class="col-md-4 mb-3">
        <label for="numero_poliza">N° Póliza <span style="color:darkred">*</span></label>
        <input type="text" class="form-control" id="numero_poliza" name="numero_poliza"
          value="<?php echo $numero_poliza; ?>" readonly>
      </div>
      <div class="col-md-4 mb-3">
        <label for="ramo_display">Ramo</label>
        <input type="text" class="form-control" id="ramo_display" value="<?php echo $ramo; ?>" readonly>
      </div>
      <div class="col-md-4 mb-3">
        <label for="compania_display">Compañía</label>
        <input type="text" class="form-control" id="compania_display" value="<?php echo $compania; ?>" readonly>
      </div>
    </div>

    <!-- ==================== SECCIÓN 1b: ÍTEMS AFECTADOS ==================== -->
    <div id="seccion_items" style="<?php echo ($id_poliza ? '' : 'display:none'); ?>">
      <hr>
      <h5 class="form-row">&nbsp;Ítems afectados <span style="color:darkred">*</span></h5><br>
      <div class="form-row">
        <div class="col-md-12 mb-3">
          <div id="lista_items_checkboxes" class="border rounded p-2" style="min-height:50px">
            <em>Seleccione una póliza para ver sus ítems.</em>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== SECCIÓN 2: DATOS DEL SINIESTRO ==================== -->
    <hr>
    <h5 class="form-row">&nbsp;Datos del Siniestro</h5><br>
    <div class="form-row">
      <div class="col-md-4 mb-3">
        <label for="numero_siniestro">N° Siniestro Compañía</label>
        <input type="text" class="form-control" id="numero_siniestro" name="numero_siniestro"
          value="<?php echo $numero_siniestro; ?>" placeholder="Número entregado por la compañía">
      </div>
      <div class="col-md-4 mb-3">
        <label for="tipo_siniestro">Tipo de Siniestro <span style="color:darkred">*</span></label>
        <select class="form-control" id="tipo_siniestro" name="tipo_siniestro">
          <option value="">-- Seleccione --</option>
          <?php
          $tipos = ['Robo', 'Choque', 'Colisión', 'Incendio', 'Daño', 'Otro'];
          foreach ($tipos as $t) {
              $sel = ($tipo_siniestro == $t) ? 'selected' : '';
              echo "<option value=\"$t\" $sel>$t</option>";
          }
          ?>
        </select>
      </div>
      <div class="col-md-4 mb-3">
        <label for="estado">Estado</label>
        <select class="form-control" id="estado" name="estado">
          <?php
          $estados = ['Abierto', 'Número pendiente', 'En proceso', 'Cerrado', 'Rechazado'];
          foreach ($estados as $e) {
              $sel = ($estado == $e) ? 'selected' : '';
              echo "<option value=\"$e\" $sel>$e</option>";
          }
          ?>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="col-md-4 mb-3">
        <label for="fecha_ocurrencia">Fecha de Ocurrencia <span style="color:darkred">*</span></label>
        <input type="date" class="form-control" id="fecha_ocurrencia" name="fecha_ocurrencia"
          value="<?php echo $fecha_ocurrencia; ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label for="fecha_denuncia">Fecha de Denuncia</label>
        <input type="date" class="form-control" id="fecha_denuncia" name="fecha_denuncia"
          value="<?php echo $fecha_denuncia; ?>">
      </div>
      <div class="col-md-4 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="no_presentado" name="no_presentado"
            <?php echo (!$presentado) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="no_presentado">Siniestro no presentado</label>
        </div>
      </div>
    </div>

    <!-- ==================== SECCIÓN 3: ASEGURADO ==================== -->
    <hr>
    <h5 class="form-row">&nbsp;Datos del Asegurado</h5><br>
    <div class="form-row">
      <div class="col-md-5 mb-3">
        <label for="nombre_asegurado">Nombre Completo</label>
        <input type="text" class="form-control" id="nombre_asegurado" name="nombre_asegurado"
          value="<?php echo $nombre_asegurado; ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label for="rut_asegurado">RUT</label>
        <input type="text" class="form-control" id="rut_asegurado" name="rut_asegurado"
          value="<?php echo $rut_asegurado . ($dv_asegurado ? '-' . $dv_asegurado : ''); ?>"
          placeholder="12345678-9">
      </div>
      <div class="col-md-2 mb-3">
        <label for="telefono_asegurado">Teléfono</label>
        <input type="text" class="form-control" id="telefono_asegurado" name="telefono_asegurado"
          value="<?php echo $telefono_asegurado; ?>" placeholder="56 9 XXXX XXXX">
      </div>
      <div class="col-md-2 mb-3">
        <label for="correo_asegurado">Correo</label>
        <input type="email" class="form-control" id="correo_asegurado" name="correo_asegurado"
          value="<?php echo $correo_asegurado; ?>">
      </div>
    </div>

    <!-- ==================== SECCIÓN 4: DESCRIPCIÓN ==================== -->
    <hr>
    <h5 class="form-row">&nbsp;Descripción del Siniestro</h5><br>
    <div class="form-row">
      <div class="col-md-12 mb-3">
        <textarea class="form-control" id="descripcion" name="descripcion" rows="6"
          placeholder="Copie y pegue la descripción del siniestro aquí"><?php echo $descripcion; ?></textarea>
      </div>
    </div>

    <!-- ==================== SECCIÓN 5: LIQUIDADOR ==================== -->
    <hr>
    <h5 class="form-row">&nbsp;Liquidador</h5><br>
    <div class="form-row">
      <div class="col-md-4 mb-3">
        <label for="liquidador_nombre">Nombre</label>
        <input type="text" class="form-control" id="liquidador_nombre" name="liquidador_nombre"
          value="<?php echo $liquidador_nombre; ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label for="liquidador_telefono">Teléfono</label>
        <input type="text" class="form-control" id="liquidador_telefono" name="liquidador_telefono"
          value="<?php echo $liquidador_telefono; ?>" placeholder="56 9 XXXX XXXX">
      </div>
      <div class="col-md-4 mb-3">
        <label for="liquidador_correo">Correo</label>
        <input type="email" class="form-control" id="liquidador_correo" name="liquidador_correo"
          value="<?php echo $liquidador_correo; ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="col-md-4 mb-3">
        <label for="numero_carpeta_liquidador">N° Carpeta Liquidador</label>
        <input type="text" class="form-control" id="numero_carpeta_liquidador" name="numero_carpeta_liquidador"
          value="<?php echo $numero_carpeta_liquidador; ?>">
      </div>
    </div>

    <!-- ==================== SECCIÓN 6: VEHÍCULOS (dinámico, 1 bloque por ítem marcado) ==================== -->
    <div id="seccion_vehiculo" style="display:none">
      <hr>
      <h5 class="form-row">&nbsp;Vehículos afectados</h5><br>
      <div id="contenedor_vehiculos"></div>
    </div>

    <!-- ==================== SECCIÓN 7: TALLER ==================== -->
    <div id="seccion_taller" style="display:none">
      <hr>
      <h5 class="form-row">&nbsp;Taller</h5><br>
      <div class="form-row">
        <div class="col-md-6 mb-3">
          <label for="taller_nombre">Nombre Taller</label>
          <input type="text" class="form-control" id="taller_nombre" name="taller_nombre"
            value="<?php echo $taller_nombre; ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label for="taller_telefono">Teléfono Taller</label>
          <input type="text" class="form-control" id="taller_telefono" name="taller_telefono"
            value="<?php echo $taller_telefono; ?>" placeholder="56 9 XXXX XXXX">
        </div>
      </div>
    </div>

    <!-- ==================== BOTONES ==================== -->
    <hr>
    <button type="button" class="btn" style="background-color:#536656;color:white"
      id="boton_registrar" onclick="registraSiniestro()">
      <?php echo ($camino == 'modifica_siniestro') ? 'Guardar cambios' : 'Registrar Siniestro'; ?>
    </button>
    &nbsp;
    <?php if ($camino == 'modifica_siniestro' && $estado == 'Cerrado'): ?>
    <button type="button" class="btn btn-warning" id="boton_reabrir" onclick="reabrirSiniestro()">
      Reabrir siniestro
    </button>
    &nbsp;
    <?php endif; ?>
    <a href="/bambooQA/listado_siniestros.php" class="btn btn-secondary">Cancelar</a>
    <br><br>
  </form>

  <?php if ($camino == 'modifica_siniestro'): ?>
  <!-- =============================================================== -->
  <!-- HISTORIAL DE CAMBIOS DE ESTADO -->
  <!-- =============================================================== -->
  <hr>
  <h5>Historial de cambios de estado</h5>
  <table class="table table-sm table-striped" id="tabla_bitacora" style="width:100%">
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Usuario</th>
        <th>Desde</th>
        <th>Hasta</th>
        <th>Motivo</th>
      </tr>
    </thead>
    <tbody id="filas_bitacora"></tbody>
  </table>
  <br><br>
  <?php endif; ?>
</div>

<!-- =============================================================== -->
<!-- SCRIPTS -->
<!-- =============================================================== -->
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"
  integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"
  integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
<script src="/assets/js/jquery.redirect.js"></script>
<script src="/assets/js/js.cookie.min.js"></script>

<script>
// ---- Datos pre-cargados desde servidor ----
var vehiculosPre   = <?php echo json_encode($vehiculos_pre); ?>;  // map numero_item → {patente,marca,modelo,anio}
var itemsCache     = [];                                           // datos de items crudos de la póliza actual (de busqueda_items_poliza)
var esRamoVehiculo = false;

// ---- Sanitización client-side ----
function estandariza_info(val) {
    return $.trim(val);
}

// ---- Toggle secciones vehículo y taller ----
function toggleVehiculo(ramo) {
    var ramo_upper = (ramo || '').toUpperCase();
    esRamoVehiculo = (ramo_upper.indexOf('VEH') !== -1 || ramo_upper.indexOf('AUTO') !== -1);
    document.getElementById('seccion_vehiculo').style.display = esRamoVehiculo ? 'block' : 'none';
    document.getElementById('seccion_taller').style.display   = esRamoVehiculo ? 'block' : 'none';
    renderVehiculos();
}

// ---- Renderiza un bloque Vehículo por cada ítem marcado ----
function renderVehiculos() {
    var cont = $('#contenedor_vehiculos');
    if (!esRamoVehiculo) { cont.empty(); return; }
    var seleccionados = [];
    $('.chk_item:checked').each(function() { seleccionados.push(String($(this).val())); });

    // Conservar valores actuales que el usuario pueda haber editado
    var valoresActuales = {};
    $('.veh-block').each(function() {
        var ni = $(this).data('item');
        valoresActuales[ni] = {
            patente: $(this).find('.veh-patente').val(),
            marca:   $(this).find('.veh-marca').val(),
            modelo:  $(this).find('.veh-modelo').val(),
            anio:    $(this).find('.veh-anio').val()
        };
    });

    cont.empty();
    if (seleccionados.length === 0) {
        cont.html('<em>Seleccione al menos un ítem afectado.</em>');
        return;
    }
    $.each(seleccionados, function(i, ni) {
        // Prioridad: valores editados > vehiculosPre (modo edición) > itemsCache (parsed de materia)
        var datos = valoresActuales[ni] || vehiculosPre[ni] || null;
        if (!datos) {
            var it = itemsCache.find(function(x) { return String(x.numero_item) === ni; });
            if (it) datos = { patente: it.patente || '', marca: it.marca || '', modelo: it.modelo || '', anio: it.anio || '' };
        }
        datos = datos || { patente: '', marca: '', modelo: '', anio: '' };

        var bloque = '' +
            '<div class="veh-block border rounded p-2 mb-3" data-item="' + ni + '">' +
              '<strong>Vehículo — Ítem ' + ni + '</strong>' +
              '<div class="form-row mt-2">' +
                '<div class="col-md-2 mb-2">' +
                  '<label>Patente</label>' +
                  '<input type="text" class="form-control veh-patente" name="vehiculo_patente[' + ni + ']" value="' + (datos.patente || '') + '" maxlength="8" placeholder="XXXX00">' +
                '</div>' +
                '<div class="col-md-3 mb-2">' +
                  '<label>Marca</label>' +
                  '<input type="text" class="form-control veh-marca" name="vehiculo_marca[' + ni + ']" value="' + (datos.marca || '') + '">' +
                '</div>' +
                '<div class="col-md-4 mb-2">' +
                  '<label>Modelo</label>' +
                  '<input type="text" class="form-control veh-modelo" name="vehiculo_modelo[' + ni + ']" value="' + (datos.modelo || '') + '">' +
                '</div>' +
                '<div class="col-md-3 mb-2">' +
                  '<label>Año</label>' +
                  '<input type="number" class="form-control veh-anio" name="vehiculo_anio[' + ni + ']" value="' + (datos.anio || '') + '" min="1990" max="2030">' +
                '</div>' +
              '</div>' +
            '</div>';
        cont.append(bloque);
    });
}

// ---- Cargar ítems de la póliza en checkboxes ----
function cargarItemsPoliza(id_poliza, items_csv_pre) {
    if (!id_poliza) {
        $('#seccion_items').hide();
        return;
    }
    $('#seccion_items').show();
    $('#lista_items_checkboxes').html('<em>Cargando ítems...</em>');

    var preseleccionados = (items_csv_pre || '').split(',').map(function(x) { return $.trim(x); }).filter(function(x){ return x !== ''; });

    $.ajax({
        type: 'GET',
        url: '/bambooQA/backend/siniestros/busqueda_items_poliza.php',
        data: { id_poliza: id_poliza },
        dataType: 'json',
        success: function(response) {
            var data = (response && response.data) || [];
            if (data.length === 0) {
                $('#lista_items_checkboxes').html('<em>Esta póliza no tiene ítems registrados.</em>');
                return;
            }
            var html = '';
            $.each(data, function(i, it) {
                var checked = '';
                // Default: marcar si está pre-seleccionado, o si es ítem único y no hay pre-selección
                if (preseleccionados.indexOf(String(it.numero_item)) !== -1) {
                    checked = 'checked';
                } else if (data.length === 1 && preseleccionados.length === 0) {
                    checked = 'checked';
                }
                var label = 'Ítem ' + it.numero_item;
                if (it.materia_asegurada) label += ' — ' + String(it.materia_asegurada).substring(0, 80);
                if (it.patente_ubicacion) label += ' (' + it.patente_ubicacion + ')';
                html += '<div class="form-check">' +
                        '<input class="form-check-input chk_item" type="checkbox" value="' + it.numero_item + '" id="chk_item_' + it.numero_item + '" ' + checked + '>' +
                        '<label class="form-check-label" for="chk_item_' + it.numero_item + '">' + label + '</label>' +
                        '</div>';
            });
            $('#lista_items_checkboxes').html(html);
            itemsCache = data;
            actualizarItemsSeleccionados();
            renderVehiculos();
            $('.chk_item').on('change', function() {
                actualizarItemsSeleccionados();
                renderVehiculos();
            });
        },
        error: function() {
            $('#lista_items_checkboxes').html('<em style="color:darkred">Error cargando ítems.</em>');
        }
    });
}

function actualizarItemsSeleccionados() {
    var seleccionados = [];
    $('.chk_item:checked').each(function() {
        seleccionados.push($(this).val());
    });
    $('#items_seleccionados').val(seleccionados.join(','));
}

// ---- Validación de campos requeridos ----
function validaSiniestro() {
    var poliza = estandariza_info($('#numero_poliza').val());
    var tipo   = estandariza_info($('#tipo_siniestro').val());
    var fecha  = estandariza_info($('#fecha_ocurrencia').val());
    var items  = estandariza_info($('#items_seleccionados').val());

    if (poliza === '') {
        alert('Debe ingresar o seleccionar una póliza.');
        return false;
    }
    if (tipo === '') {
        alert('Debe seleccionar el tipo de siniestro.');
        return false;
    }
    if (fecha === '') {
        alert('Debe ingresar la fecha de ocurrencia.');
        return false;
    }
    if (items === '') {
        alert('Debe seleccionar al menos un ítem afectado.');
        return false;
    }
    return true;
}

// ---- Envío del formulario ----
function registraSiniestro() {
    if (!validaSiniestro()) return;

    var camino = '<?php echo $camino; ?>';
    var accion = (camino === 'modifica_siniestro') ? 'actualizar_siniestro' : 'crear_siniestro';

    // Separar RUT y DV
    var rut_completo = estandariza_info($('#rut_asegurado').val()).replace(/-/g, '');
    var rut_sin_dv   = rut_completo.slice(0, -1);
    var dv           = rut_completo.slice(-1);

    var presentado = $('#no_presentado').is(':checked') ? '0' : '1';

    var payload = {
        'accion':                    accion,
        'id_siniestro':              estandariza_info($('#id_siniestro').val()),
        'id_poliza':                 estandariza_info($('#id_poliza').val()),
        'numero_poliza':             estandariza_info($('#numero_poliza').val()),
        'ramo':                      estandariza_info($('#ramo').val()),
        'items_seleccionados':       estandariza_info($('#items_seleccionados').val()),
        'numero_siniestro':          estandariza_info($('#numero_siniestro').val()),
        'tipo_siniestro':            estandariza_info($('#tipo_siniestro').val()),
        'fecha_ocurrencia':          estandariza_info($('#fecha_ocurrencia').val()),
        'fecha_denuncia':            estandariza_info($('#fecha_denuncia').val()),
        'presentado':                presentado,
        'rut_asegurado':             rut_sin_dv,
        'dv_asegurado':              dv,
        'nombre_asegurado':          estandariza_info($('#nombre_asegurado').val()),
        'telefono_asegurado':        estandariza_info($('#telefono_asegurado').val()),
        'correo_asegurado':          estandariza_info($('#correo_asegurado').val()),
        'descripcion':               estandariza_info($('#descripcion').val()),
        'liquidador_nombre':         estandariza_info($('#liquidador_nombre').val()),
        'liquidador_telefono':       estandariza_info($('#liquidador_telefono').val()),
        'liquidador_correo':         estandariza_info($('#liquidador_correo').val()),
        'numero_carpeta_liquidador': estandariza_info($('#numero_carpeta_liquidador').val()),
        'taller_nombre':             estandariza_info($('#taller_nombre').val()),
        'taller_telefono':           estandariza_info($('#taller_telefono').val()),
        'estado':                    estandariza_info($('#estado').val())
    };
    // Arrays de vehículo por ítem (claves vehiculo_patente[N])
    $('.veh-block').each(function() {
        var ni = $(this).data('item');
        payload['vehiculo_patente[' + ni + ']'] = estandariza_info($(this).find('.veh-patente').val());
        payload['vehiculo_marca['   + ni + ']'] = estandariza_info($(this).find('.veh-marca').val());
        payload['vehiculo_modelo['  + ni + ']'] = estandariza_info($(this).find('.veh-modelo').val());
        payload['vehiculo_anio['    + ni + ']'] = estandariza_info($(this).find('.veh-anio').val());
    });

    $.redirect('/bambooQA/backend/siniestros/crea_siniestro.php', payload, 'post');
}

// ---- Reabrir siniestro ----
function reabrirSiniestro() {
    var motivo = prompt('Ingrese el motivo de reapertura (obligatorio):');
    if (motivo === null) return;
    motivo = $.trim(motivo);
    if (motivo === '') {
        alert('El motivo es obligatorio para reabrir el siniestro.');
        return;
    }
    $.ajax({
        type: 'POST',
        url: '/bambooQA/backend/siniestros/reabre_siniestro.php',
        data: {
            id_siniestro: $('#id_siniestro').val(),
            motivo: motivo
        },
        dataType: 'json',
        success: function(response) {
            alert(response.mensaje || 'Operación completada.');
            if (response.ok) {
                window.location.reload();
            }
        },
        error: function() {
            alert('Error al reabrir el siniestro.');
        }
    });
}

// ---- Cargar historial de bitácora ----
function cargarBitacora(id_siniestro) {
    if (!id_siniestro) return;
    $.ajax({
        type: 'GET',
        url: '/bambooQA/backend/siniestros/busqueda_bitacora_siniestro.php',
        data: { id_siniestro: id_siniestro },
        dataType: 'json',
        success: function(response) {
            var data = (response && response.data) || [];
            var html = '';
            if (data.length === 0) {
                html = '<tr><td colspan="5"><em>Sin cambios registrados.</em></td></tr>';
            } else {
                $.each(data, function(i, r) {
                    html += '<tr>' +
                        '<td>' + (r.fecha || '') + '</td>' +
                        '<td>' + (r.usuario || '') + '</td>' +
                        '<td>' + (r.estado_anterior || '—') + '</td>' +
                        '<td>' + (r.estado_nuevo || '') + '</td>' +
                        '<td>' + (r.motivo || '') + '</td>' +
                        '</tr>';
                });
            }
            $('#filas_bitacora').html(html);
        }
    });
}

// ---- Al cargar: inicializar ----
$(document).ready(function() {
    var ramo_inicial = '<?php echo addslashes($ramo); ?>';
    if (ramo_inicial !== '') {
        toggleVehiculo(ramo_inicial);
    }
    // Cargar ítems si hay póliza preseleccionada
    var id_poliza_inicial = $('#id_poliza').val();
    var items_csv_inicial = $('#items_seleccionados').val();
    if (id_poliza_inicial) {
        cargarItemsPoliza(id_poliza_inicial, items_csv_inicial);
    }
    // Cargar bitácora en modo edición
    var id_siniestro_inicial = $('#id_siniestro').val();
    if (id_siniestro_inicial) {
        cargarBitacora(id_siniestro_inicial);
    }
});
</script>
</body>
</html>
