<?php
if (!isset($_SESSION)) {
    session_start();
}

$camino = 'crear_siniestro';
$numero_siniestro = $id_poliza = $numero_poliza = $ramo = '';
$tipo_siniestro = $fecha_ocurrencia = $fecha_denuncia = '';
$rut_asegurado = $dv_asegurado = $nombre_asegurado = $telefono_asegurado = $correo_asegurado = '';
$descripcion = '';
$liquidador_nombre = $liquidador_telefono = $liquidador_correo = '';
$patente = $marca = $modelo = $anio_vehiculo = '';
$taller_nombre = $taller_telefono = '';
$estado = 'Abierto';
$presentado = true;
$compania = '';
$id_siniestro = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == 'modifica_siniestro' && isset($_POST["id_siniestro"])) {
    $camino = 'modifica_siniestro';
    $id_siniestro = $_POST["id_siniestro"];
    require_once "/home/gestio10/public_html/backend/config.php";
    db_set_charset($link, 'utf8');
    db_select_db($link, DB_NAME);
    $resultado = db_query($link, "SELECT s.*, p.compania FROM siniestros s LEFT JOIN polizas_2 p ON s.id_poliza = p.id WHERE s.id = '" . $id_siniestro . "'");
    while ($row = db_fetch_object($resultado)) {
        $id_siniestro        = $row->id;
        $numero_siniestro    = $row->numero_siniestro;
        $id_poliza           = $row->id_poliza;
        $numero_poliza       = $row->numero_poliza;
        $ramo                = $row->ramo;
        $tipo_siniestro      = $row->tipo_siniestro;
        $fecha_ocurrencia    = $row->fecha_ocurrencia;
        $fecha_denuncia      = $row->fecha_denuncia;
        $rut_asegurado       = $row->rut_asegurado;
        $dv_asegurado        = $row->dv_asegurado;
        $nombre_asegurado    = $row->nombre_asegurado;
        $telefono_asegurado  = $row->telefono_asegurado;
        $correo_asegurado    = $row->correo_asegurado;
        $descripcion         = str_replace("\r\n", "\\n", $row->descripcion);
        $liquidador_nombre   = $row->liquidador_nombre;
        $liquidador_telefono = $row->liquidador_telefono;
        $liquidador_correo   = $row->liquidador_correo;
        $patente             = $row->patente;
        $marca               = $row->marca;
        $modelo              = $row->modelo;
        $anio_vehiculo       = $row->anio_vehiculo;
        $taller_nombre       = $row->taller_nombre;
        $taller_telefono     = $row->taller_telefono;
        $estado              = $row->estado;
        $presentado          = $row->presentado;
        $compania            = $row->compania;
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
<link rel="icon" href="/bamboo/images/bamboo.png">
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
    <input type="hidden" id="id_siniestro"   name="id_siniestro"   value="<?php echo $id_siniestro; ?>">
    <input type="hidden" id="id_poliza"       name="id_poliza"       value="<?php echo $id_poliza; ?>">
    <input type="hidden" id="ramo"            name="ramo"            value="<?php echo $ramo; ?>">

    <!-- ==================== SECCIÓN 1: PÓLIZA ASOCIADA ==================== -->
    <h5 class="form-row">&nbsp;Póliza asociada</h5><br>
    <div class="form-row">
      <div class="col-md-4 mb-3">
        <label for="numero_poliza">N° Póliza <span style="color:darkred">*</span></label>
        <div class="input-group">
          <input type="text" class="form-control" id="numero_poliza" name="numero_poliza"
            value="<?php echo $numero_poliza; ?>" placeholder="Ej: 7621783">
          <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="button"
              onclick="$('#modal_poliza').modal('show')">Buscar</button>
          </div>
        </div>
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
          $estados = ['Abierto', 'En proceso', 'Cerrado', 'Rechazado'];
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

    <!-- ==================== SECCIÓN 6: VEHÍCULO ==================== -->
    <div id="seccion_vehiculo" style="display:none">
      <hr>
      <h5 class="form-row">&nbsp;Vehículo</h5><br>
      <div class="form-row">
        <div class="col-md-2 mb-3">
          <label for="patente">Patente</label>
          <input type="text" class="form-control" id="patente" name="patente"
            value="<?php echo $patente; ?>" maxlength="8" placeholder="XXXX00">
        </div>
        <div class="col-md-3 mb-3">
          <label for="marca">Marca</label>
          <input type="text" class="form-control" id="marca" name="marca"
            value="<?php echo $marca; ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label for="modelo">Modelo</label>
          <input type="text" class="form-control" id="modelo" name="modelo"
            value="<?php echo $modelo; ?>">
        </div>
        <div class="col-md-3 mb-3">
          <label for="anio_vehiculo">Año</label>
          <input type="number" class="form-control" id="anio_vehiculo" name="anio_vehiculo"
            value="<?php echo $anio_vehiculo; ?>" min="1990" max="2030">
        </div>
      </div>
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
    <a href="/bamboo/listado_siniestros.php" class="btn btn-secondary">Cancelar</a>
    <br><br>
  </form>
</div>

<!-- =============================================================== -->
<!-- MODAL BÚSQUEDA DE PÓLIZA -->
<!-- =============================================================== -->
<div class="modal fade" id="modal_poliza" tabindex="-1" role="dialog" aria-labelledby="modal_poliza_label" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modal_poliza_label">Buscar Póliza</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-row mb-3">
          <div class="col-md-8">
            <input type="text" class="form-control" id="busqueda_poliza"
              placeholder="Ingrese N° de póliza o RUT del asegurado">
          </div>
          <div class="col-md-4">
            <button class="btn btn-primary" type="button" onclick="buscarPoliza()">Buscar</button>
          </div>
        </div>
        <div id="resultado_busqueda_poliza">
          <table class="table table-hover table-sm" id="tabla_polizas" style="display:none">
            <thead>
              <tr>
                <th>N° Póliza</th>
                <th>Ramo</th>
                <th>Compañía</th>
                <th>Asegurado</th>
                <th>Vigencia</th>
                <th>Estado</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="filas_polizas"></tbody>
          </table>
          <p id="sin_resultados" style="display:none">No se encontraron pólizas.</p>
        </div>
      </div>
    </div>
  </div>
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
// ---- Sanitización client-side ----
function estandariza_info(val) {
    return $.trim(val);
}

// ---- Toggle secciones vehículo y taller ----
function toggleVehiculo(ramo) {
    var ramo_upper = (ramo || '').toUpperCase();
    if (ramo_upper.indexOf('VEH') !== -1 || ramo_upper.indexOf('AUTO') !== -1 || ramo_upper.indexOf('VEHÍCULO') !== -1 || ramo_upper.indexOf('VEHICULO') !== -1) {
        document.getElementById('seccion_vehiculo').style.display = 'block';
        document.getElementById('seccion_taller').style.display = 'block';
    } else {
        document.getElementById('seccion_vehiculo').style.display = 'none';
        document.getElementById('seccion_taller').style.display = 'none';
    }
}

// ---- Validación de campos requeridos ----
function validaSiniestro() {
    var poliza = estandariza_info($('#numero_poliza').val());
    var tipo   = estandariza_info($('#tipo_siniestro').val());
    var fecha  = estandariza_info($('#fecha_ocurrencia').val());

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

    $.redirect('/bamboo/backend/siniestros/crea_siniestro.php', {
        'accion':               accion,
        'id_siniestro':         estandariza_info($('#id_siniestro').val()),
        'id_poliza':            estandariza_info($('#id_poliza').val()),
        'numero_poliza':        estandariza_info($('#numero_poliza').val()),
        'ramo':                 estandariza_info($('#ramo').val()),
        'numero_siniestro':     estandariza_info($('#numero_siniestro').val()),
        'tipo_siniestro':       estandariza_info($('#tipo_siniestro').val()),
        'fecha_ocurrencia':     estandariza_info($('#fecha_ocurrencia').val()),
        'fecha_denuncia':       estandariza_info($('#fecha_denuncia').val()),
        'presentado':           presentado,
        'rut_asegurado':        rut_sin_dv,
        'dv_asegurado':         dv,
        'nombre_asegurado':     estandariza_info($('#nombre_asegurado').val()),
        'telefono_asegurado':   estandariza_info($('#telefono_asegurado').val()),
        'correo_asegurado':     estandariza_info($('#correo_asegurado').val()),
        'descripcion':          estandariza_info($('#descripcion').val()),
        'liquidador_nombre':    estandariza_info($('#liquidador_nombre').val()),
        'liquidador_telefono':  estandariza_info($('#liquidador_telefono').val()),
        'liquidador_correo':    estandariza_info($('#liquidador_correo').val()),
        'patente':              estandariza_info($('#patente').val()),
        'marca':                estandariza_info($('#marca').val()),
        'modelo':               estandariza_info($('#modelo').val()),
        'anio_vehiculo':        estandariza_info($('#anio_vehiculo').val()),
        'taller_nombre':        estandariza_info($('#taller_nombre').val()),
        'taller_telefono':      estandariza_info($('#taller_telefono').val()),
        'estado':               estandariza_info($('#estado').val())
    }, 'post');
}

// ---- Modal: buscar póliza ----
function buscarPoliza() {
    var busqueda = estandariza_info($('#busqueda_poliza').val());
    if (busqueda === '') {
        alert('Ingrese un número de póliza o RUT para buscar.');
        return;
    }
    $.ajax({
        type: 'POST',
        url: '/bamboo/backend/siniestros/busca_poliza_siniestro.php',
        data: { busqueda: busqueda },
        dataType: 'json',
        success: function(response) {
            var filas = '';
            if (response && response.data && response.data.length > 0) {
                $.each(response.data, function(i, p) {
                    filas += '<tr>' +
                        '<td>' + (p.numero_poliza || '') + '</td>' +
                        '<td>' + (p.ramo || '') + '</td>' +
                        '<td>' + (p.compania || '') + '</td>' +
                        '<td>' + (p.nombre_cliente || '') + '</td>' +
                        '<td>' + (p.vigencia_inicial || '') + ' / ' + (p.vigencia_final || '') + '</td>' +
                        '<td>' + (p.estado || '') + '</td>' +
                        '<td><button type="button" class="btn btn-sm btn-success" ' +
                            'onclick="seleccionarPoliza(' +
                            JSON.stringify(p) + ')">Seleccionar</button></td>' +
                        '</tr>';
                });
                $('#filas_polizas').html(filas);
                $('#tabla_polizas').show();
                $('#sin_resultados').hide();
            } else {
                $('#tabla_polizas').hide();
                $('#sin_resultados').show();
            }
        },
        error: function() {
            alert('Error al buscar pólizas. Intente nuevamente.');
        }
    });
}

function seleccionarPoliza(poliza) {
    $('#id_poliza').val(poliza.id);
    $('#numero_poliza').val(poliza.numero_poliza);
    $('#ramo').val(poliza.ramo);
    $('#ramo_display').val(poliza.ramo);
    $('#compania_display').val(poliza.compania);
    // Auto-poblar datos del asegurado
    var rut_completo = (poliza.rut_sin_dv || '') + (poliza.dv ? '-' + poliza.dv : '');
    $('#rut_asegurado').val(rut_completo);
    $('#nombre_asegurado').val(poliza.nombre_cliente || '');
    $('#telefono_asegurado').val(poliza.telefono || '');
    $('#correo_asegurado').val(poliza.correo || '');
    // Toggle vehículo
    toggleVehiculo(poliza.ramo);
    $('#modal_poliza').modal('hide');
}

// ---- Al cargar: inicializar toggles si hay ramo en modo edición ----
$(document).ready(function() {
    var ramo_inicial = '<?php echo addslashes($ramo); ?>';
    if (ramo_inicial !== '') {
        toggleVehiculo(ramo_inicial);
    }
    // Enter en búsqueda de póliza
    $('#busqueda_poliza').on('keypress', function(e) {
        if (e.which === 13) buscarPoliza();
    });
});
</script>
</body>
</html>
