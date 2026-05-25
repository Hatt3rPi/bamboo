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
$observaciones = '';
$liquidador_nombre = $liquidador_telefono = $liquidador_correo = $numero_carpeta_liquidador = '';
$patente = $marca = $modelo = $anio_vehiculo = '';
$taller_nombre = $taller_telefono = '';
$compania_contacto_nombre = $compania_contacto_mail = '';
$estado = 'Número pendiente';
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
        $observaciones             = str_replace("\r\n", "\\n", $row->observaciones ?? '');
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
        $compania_contacto_nombre  = $row->compania_contacto_nombre ?? '';
        $compania_contacto_mail    = $row->compania_contacto_mail ?? '';
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

    // Estado de tareas relevantes de la cadena (para render contextual del form).
    // Si compania_entrega_numero está Pendiente, los campos N°/Estado/Liquidador no se muestran.
    $compania_entrega_pendiente = false;
    $rs_t = db_query($link, "SELECT estado FROM siniestros_pendientes
                             WHERE id_siniestro='" . $id_siniestro . "'
                               AND codigo_tarea='compania_entrega_numero'
                             ORDER BY fecha_creacion DESC LIMIT 1");
    while ($row = db_fetch_object($rs_t)) {
        if ($row->estado === 'Pendiente') { $compania_entrega_pendiente = true; }
    }

    db_close($link);
} else {
    $compania_entrega_pendiente = false;
}
if (!isset($vehiculos_pre)) $vehiculos_pre = array();

// "Bloqueado" = los datos de la compañía aún no llegaron, ya sea porque estamos en
// creación, o estamos en edición pero la tarea sigue Pendiente.
$bloqueo_compania = ($camino != 'modifica_siniestro') || $compania_entrega_pendiente;

// Defensa: si el ramo no es vehicular, limpiar datos de vehículo/taller
// arrastrados desde BD (previene data leakage en edición y guardado inadvertido).
$ramo_upper_php = strtoupper($ramo);
$es_ramo_vehiculo_php = (strpos($ramo_upper_php, 'VEH') !== false || strpos($ramo_upper_php, 'AUTO') !== false);
if (!$es_ramo_vehiculo_php) {
    $vehiculos_pre = array();
    $patente = $marca = $modelo = $anio_vehiculo = '';
    $taller_nombre = $taller_telefono = '';
}
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
    <?php $col_datos_siniestro = (!$bloqueo_compania) ? 'col-md-4' : 'col-md-6'; ?>
    <div class="form-row">
      <?php if (!$bloqueo_compania): ?>
      <div class="col-md-4 mb-3">
        <label for="numero_siniestro">N° Siniestro Compañía</label>
        <input type="text" class="form-control" id="numero_siniestro" name="numero_siniestro"
          value="<?php echo $numero_siniestro; ?>" placeholder="Número entregado por la compañía">
      </div>
      <?php else: ?>
      <input type="hidden" id="numero_siniestro" name="numero_siniestro" value="<?php echo htmlspecialchars($numero_siniestro, ENT_QUOTES); ?>">
      <?php endif; ?>
      <div class="<?php echo $col_datos_siniestro; ?> mb-3">
        <label for="tipo_siniestro">Tipo de Siniestro <span style="color:darkred">*</span></label>
        <select class="form-control" id="tipo_siniestro" name="tipo_siniestro">
          <option value="">-- Seleccione --</option>
          <?php
          // En no-vehículo, "Choque/Colisión" no aplica (Adriana 18-may).
          $tipos = ['Robo', 'Choque/Colisión', 'Incendio', 'Daños materiales', 'Responsabilidad civil'];
          if (!$es_ramo_vehiculo_php) {
              $tipos = array_values(array_diff($tipos, ['Choque/Colisión']));
          }
          foreach ($tipos as $t) {
              $sel = ($tipo_siniestro == $t) ? 'selected' : '';
              echo "<option value=\"$t\" $sel>$t</option>";
          }
          ?>
        </select>
      </div>
      <div class="<?php echo $col_datos_siniestro; ?> mb-3">
        <label for="fecha_ocurrencia">Fecha de Ocurrencia <span style="color:darkred">*</span></label>
        <input type="date" class="form-control" id="fecha_ocurrencia" name="fecha_ocurrencia"
          value="<?php echo $fecha_ocurrencia; ?>">
      </div>
      <?php if (!$bloqueo_compania): ?>
      <div class="col-md-4 mb-3">
        <label for="estado">Estado</label>
        <select class="form-control" id="estado" name="estado">
          <?php
          $estados = ['Número pendiente', 'Abierto', 'Cerrado', 'Rechazado'];
          foreach ($estados as $e) {
              $sel = ($estado == $e) ? 'selected' : '';
              echo "<option value=\"$e\" $sel>$e</option>";
          }
          ?>
        </select>
      </div>
      <?php else: ?>
      <input type="hidden" id="estado" name="estado" value="<?php echo htmlspecialchars($estado, ENT_QUOTES); ?>">
      <?php endif; ?>
    </div>
    <div class="form-row">
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
    <div class="form-row">
      <div class="col-md-12 mb-3">
        <label for="observaciones" class="mb-1"><small class="text-muted">Observaciones adicionales</small></label>
        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"
          placeholder="Notas libres que no caben en la descripción (opcional)"><?php echo $observaciones; ?></textarea>
      </div>
    </div>

    <!-- ==================== SECCIÓN 5: LIQUIDADOR ==================== -->
    <?php if (!$bloqueo_compania): ?>
    <div id="grupo_liquidador">
      <hr>
      <h5 class="form-row">&nbsp;Liquidador <small class="text-muted etapa-hint" style="font-weight:normal"></small></h5><br>
      <div class="etapa-aviso alert alert-warning py-2 mb-2" style="display:none"></div>
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
      <div class="form-row" id="bloque_carpeta_liquidador">
        <div class="col-md-4 mb-3">
          <label for="numero_carpeta_liquidador">N° Carpeta Liquidador</label>
          <input type="text" class="form-control" id="numero_carpeta_liquidador" name="numero_carpeta_liquidador"
            value="<?php echo $numero_carpeta_liquidador; ?>">
        </div>
      </div>
    </div>
    <?php else: ?>
    <input type="hidden" id="liquidador_nombre" name="liquidador_nombre" value="<?php echo htmlspecialchars($liquidador_nombre, ENT_QUOTES); ?>">
    <input type="hidden" id="liquidador_telefono" name="liquidador_telefono" value="<?php echo htmlspecialchars($liquidador_telefono, ENT_QUOTES); ?>">
    <input type="hidden" id="liquidador_correo" name="liquidador_correo" value="<?php echo htmlspecialchars($liquidador_correo, ENT_QUOTES); ?>">
    <input type="hidden" id="numero_carpeta_liquidador" name="numero_carpeta_liquidador" value="<?php echo htmlspecialchars($numero_carpeta_liquidador, ENT_QUOTES); ?>">
    <?php endif; ?>

    <!-- ==================== SECCIÓN 5a: CONTACTO COMPAÑÍA (NO VEHÍCULO) ==================== -->
    <div id="bloque_contacto_compania" style="display:none">
      <hr>
      <h5 class="form-row">&nbsp;Contacto en la compañía
        <small class="text-muted" style="font-weight:normal">— para consultas de pago/indemnización</small>
      </h5><br>
      <div class="etapa-aviso alert alert-warning py-2 mb-2" style="display:none"></div>
      <div class="form-row">
        <div class="col-md-6 mb-3">
          <label for="compania_contacto_nombre">Nombre del contacto</label>
          <input type="text" class="form-control" id="compania_contacto_nombre" name="compania_contacto_nombre"
            value="<?php echo htmlspecialchars($compania_contacto_nombre, ENT_QUOTES); ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label for="compania_contacto_mail">Correo</label>
          <input type="email" class="form-control" id="compania_contacto_mail" name="compania_contacto_mail"
            value="<?php echo htmlspecialchars($compania_contacto_mail, ENT_QUOTES); ?>">
        </div>
      </div>
    </div>

    <!-- ==================== SECCIÓN 5b: BIENES AFECTADOS ==================== -->
    <hr>
    <h5 class="form-row">&nbsp;Bienes afectados</h5><br>
    <ul class="nav nav-tabs" id="tabsBienes" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" id="tab-propios-tab" data-toggle="tab" href="#tab-propios" role="tab">
          Daño propio (<span id="cnt_propios">0</span>)
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="tab-terceros-tab" data-toggle="tab" href="#tab-terceros" role="tab">
          Daño a terceros (<span id="cnt_terceros">0</span>)
        </a>
      </li>
    </ul>
    <div class="tab-content pt-3">
      <div class="tab-pane fade show active" id="tab-propios" role="tabpanel">
        <?php if ($camino != 'modifica_siniestro'): ?>
        <button type="button" class="btn btn-sm btn-primary mb-2" onclick="nuevoBien('propio')">+ Agregar bien propio</button>
        <?php endif; ?>
        <table class="table table-sm table-bordered" id="tabla_bienes_propios">
          <thead><tr><th style="width:14%">Categoría</th><th style="width:30%">Descripción</th><th>Estado</th><th>Alarma</th><th>Acciones</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
      <div class="tab-pane fade" id="tab-terceros" role="tabpanel">
        <?php if ($camino != 'modifica_siniestro'): ?>
        <button type="button" class="btn btn-sm btn-primary mb-2" onclick="nuevoBien('tercero')">+ Agregar bien tercero</button>
        <?php endif; ?>
        <table class="table table-sm table-bordered" id="tabla_bienes_terceros">
          <thead><tr><th style="width:14%">Categoría</th><th style="width:30%">Descripción</th><th>Estado</th><th>Alarma</th><th>Acciones</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
    <?php if ($camino != 'modifica_siniestro'): ?>
    <small class="text-muted">Los bienes se guardan junto con el siniestro al presionar <em>Registrar Siniestro</em>.</small>
    <?php endif; ?>
    <br><br>

  </form>

  <!-- ==================== BOTONES STICKY FOOTER ==================== -->
  <div id="footer_acciones_siniestro"
       style="position:fixed;bottom:0;left:0;right:0;background:#fff;
              border-top:1px solid #dee2e6;padding:10px 20px;z-index:1040;
              box-shadow:0 -2px 4px rgba(0,0,0,0.05)">
    <div class="container" style="text-align:right">
      <button type="button" class="btn" style="background-color:#536656;color:white"
        id="boton_registrar" onclick="registraSiniestro(false)" disabled>
        <?php echo ($camino == 'modifica_siniestro') ? 'Guardar cambios' : 'Registrar Siniestro'; ?>
      </button>
      <?php if (!$bloqueo_compania): ?>
      &nbsp;<button type="button" class="btn btn-success"
        id="boton_registrar_salir" onclick="registraSiniestro(true)"
        title="Guarda y vuelve al listado anterior">
        Guardar y salir
      </button>
      <?php endif; ?>
      <?php if ($camino == 'modifica_siniestro' && $estado == 'Cerrado'): ?>
      &nbsp;<button type="button" class="btn btn-warning" id="boton_reabrir" onclick="reabrirSiniestro()">
        Reabrir siniestro
      </button>
      <?php endif; ?>
      &nbsp;<a href="/bambooQA/listado_siniestros.php" class="btn btn-secondary">Cancelar</a>
    </div>
  </div>
  <style>body { padding-bottom: 80px; }</style>

  <?php if ($camino == 'modifica_siniestro'): ?>
  <!-- =============================================================== -->
  <!-- PENDIENTES POR RESPONSABLE -->
  <!-- =============================================================== -->
  <hr>
  <div class="d-flex align-items-center" style="gap:10px">
    <h5 class="mb-0">Pendientes</h5>
    <span id="widget_quien_lleva" class="badge badge-light" style="font-size:0.9rem">—</span>
    <button type="button" class="btn btn-sm btn-primary ml-auto" onclick="abrirModalPendiente(null)">➕ Agregar pendiente</button>
  </div>
  <br>
  <table class="table table-sm table-striped" id="tabla_pendientes" style="width:100%">
    <thead>
      <tr>
        <th>Responsable</th>
        <th>Descripción</th>
        <th>Estado</th>
        <th>Fecha entrega</th>
        <th>Notas</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody id="filas_pendientes">
      <tr><td colspan="6" class="text-center text-muted"><em>Cargando…</em></td></tr>
    </tbody>
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

// Reemplaza saltos de línea (literales "\r\n" escapados o reales) por un espacio.
// Útil para mostrar texto multi-línea (materia_asegurada, patente_ubicacion) en una sola línea.
function colapsaSaltos(s) {
    if (s === null || s === undefined) return '';
    return String(s)
        .replace(/\\r\\n/g, ' ').replace(/\\n/g, ' ').replace(/\\r/g, ' ')
        .replace(/\r\n/g, ' ').replace(/\n/g, ' ').replace(/\r/g, ' ')
        .replace(/\s+/g, ' ').trim();
}

// ---- Toggle secciones vehículo y taller ----
// Legacy no-op: las secciones Vehículo/Taller fueron removidas. Mantenido
// para que llamadas antiguas no fallen.
function toggleVehiculo(ramo) {
    var ramo_upper = (ramo || '').toUpperCase();
    esRamoVehiculo = (ramo_upper.indexOf('VEH') !== -1 || ramo_upper.indexOf('AUTO') !== -1);
    var sv = document.getElementById('seccion_vehiculo');
    var st = document.getElementById('seccion_taller');
    if (sv) sv.style.display = 'none';
    if (st) st.style.display = 'none';
    // El bloque de contacto compañía solo aplica en siniestros no-vehículo (incendio, etc.)
    var bc = document.getElementById('bloque_contacto_compania');
    if (bc) bc.style.display = esRamoVehiculo ? 'none' : '';
    // N° carpeta liquidador no aplica en vehículo (liquidadores internos no usan carpeta).
    var bcl = document.getElementById('bloque_carpeta_liquidador');
    if (bcl) bcl.style.display = esRamoVehiculo ? 'none' : '';
    recalcEtapas();
}

// =========================================================================
// ETAPAS PROGRESIVAS — habilita secciones solo cuando se cumplió la etapa previa
// Flujo: compañía asigna N° → se habilita liquidador →
//        liquidador asignado → se habilita taller (veh) y contacto compañía (no-veh)
// =========================================================================
function _trim(v) { return (v || '').toString().replace(/^\s+|\s+$/g, ''); }

function bloquearGrupo($grupo, bloquear, mensaje) {
    if (!$grupo || !$grupo.length) return;
    $grupo.find('input, textarea, select').not('[data-etapa-override="1"]')
          .prop('disabled', bloquear);
    var $aviso = $grupo.children('.etapa-aviso').first();
    if (bloquear) {
        $aviso.html('⏳ ' + mensaje).show();
        $grupo.addClass('etapa-bloqueada').css('opacity', '0.75');
    } else {
        $aviso.hide().html('');
        $grupo.removeClass('etapa-bloqueada').css('opacity', '');
    }
}

function recalcEtapas() {
    var tieneNumero     = _trim($('#numero_siniestro').val()) !== '';
    var tieneLiquidador = tieneNumero && _trim($('#liquidador_nombre').val()) !== '';

    bloquearGrupo($('#grupo_liquidador'), !tieneNumero,
        'Los datos del liquidador se completan una vez que la compañía asigne el N° de siniestro.');

    var $bc = $('#bloque_contacto_compania');
    if ($bc.is(':visible')) {
        // En modo creación el contacto compañía es opcional desde el inicio
        // (Adriana puede ya tenerlo). En modo edición se mantiene la lógica
        // de "se habilita cuando hay liquidador".
        var bloquearBc = !MODO_CREACION && !tieneLiquidador;
        bloquearGrupo($bc, bloquearBc,
            'El contacto en la compañía se registra cuando el liquidador ya esté asignado.');
    }

    // El modal de bien puede estar abierto: aplicar al taller si corresponde
    if ($('#modalBien').hasClass('show')) {
        actualizarBloqueoTaller();
    }
}

// Taller del bien: se habilita solo cuando hay liquidador asignado
function actualizarBloqueoTaller() {
    var categoria = $('#bien_categoria').val();
    if (categoria !== 'vehiculo') { return; } // lo gobierna toggleCamposVehiculoBien
    var tieneLiq = _trim($('#numero_siniestro').val()) !== '' &&
                   _trim($('#liquidador_nombre').val()) !== '';
    $('#bien_taller_nombre, #bien_taller_telefono').prop('disabled', !tieneLiq);
    $('#bien_msg_taller_bloqueado').toggle(!tieneLiq);
}

$(document).on('input change', '#numero_siniestro, #liquidador_nombre', recalcEtapas);
$('#modalBien').on('shown.bs.modal', actualizarBloqueoTaller);
$(document).on('change', '#bien_categoria', actualizarBloqueoTaller);

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
                if (it.materia_asegurada) label += ' — ' + colapsaSaltos(it.materia_asegurada).substring(0, 80);
                if (it.patente_ubicacion) label += ' (' + colapsaSaltos(it.patente_ubicacion) + ')';
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
                actualizarBotonRegistrar();
            });
            // Si el modal del bien está abierto, refrescar visibilidad del campo "Ítem afectado"
            if ($('#modalBien').hasClass('show')) {
                setItemAfectadoVisible();
            }
            actualizarBotonRegistrar();
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
// Boolean puro, sin alert, para enable/disable del botón.
function siniestroEsValido() {
    var poliza = estandariza_info($('#numero_poliza').val());
    var tipo   = estandariza_info($('#tipo_siniestro').val());
    var fecha  = estandariza_info($('#fecha_ocurrencia').val());
    var items  = estandariza_info($('#items_seleccionados').val());
    return poliza !== '' && tipo !== '' && fecha !== '' && items !== '';
}

// Wrapper con alerts para el flujo de envío.
function validaSiniestro() {
    var poliza = estandariza_info($('#numero_poliza').val());
    var tipo   = estandariza_info($('#tipo_siniestro').val());
    var fecha  = estandariza_info($('#fecha_ocurrencia').val());
    var items  = estandariza_info($('#items_seleccionados').val());

    if (poliza === '') { alert('Debe ingresar o seleccionar una póliza.'); return false; }
    if (tipo   === '') { alert('Debe seleccionar el tipo de siniestro.'); return false; }
    if (fecha  === '') { alert('Debe ingresar la fecha de ocurrencia.'); return false; }
    if (items  === '') { alert('Debe seleccionar al menos un ítem afectado.'); return false; }
    return true;
}

// Bandera de modo creación, leída desde PHP
var MODO_CREACION = '<?php echo $camino; ?>' === 'crear_siniestro';

// En edición: el botón "Guardar cambios" arranca disabled y se habilita solo
// cuando se detecta mutación en el form (inputs visibles, bienes, etc.).
var formMutado = false;

function marcarFormMutado() {
    if (MODO_CREACION) return;
    formMutado = true;
    actualizarBotonRegistrar();
}

// Aplica disabled al botón según el modo:
// - Creación: requiere siniestroEsValido().
// - Edición: requiere formMutado (al menos un cambio detectado).
function actualizarBotonRegistrar() {
    var $btn = $('#boton_registrar');
    if (MODO_CREACION) {
        $btn.prop('disabled', !siniestroEsValido());
    } else {
        $btn.prop('disabled', !formMutado);
    }
}

// ---- Envío del formulario ----
function registraSiniestro(salirDespues) {
    if (!validaSiniestro()) return;

    var camino = '<?php echo $camino; ?>';
    var accion = (camino === 'modifica_siniestro') ? 'actualizar_siniestro' : 'crear_siniestro';
    var salir  = salirDespues ? '1' : '0';

    // Separar RUT y DV
    var rut_completo = estandariza_info($('#rut_asegurado').val()).replace(/-/g, '');
    var rut_sin_dv   = rut_completo.slice(0, -1);
    var dv           = rut_completo.slice(-1);

    var presentado = $('#no_presentado').is(':checked') ? '0' : '1';

    var payload = {
        'accion':                    accion,
        'salir_despues':             salir,
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
        'observaciones':             estandariza_info($('#observaciones').val()),
        'liquidador_nombre':         estandariza_info($('#liquidador_nombre').val()),
        'liquidador_telefono':       estandariza_info($('#liquidador_telefono').val()),
        'liquidador_correo':         estandariza_info($('#liquidador_correo').val()),
        'numero_carpeta_liquidador': estandariza_info($('#numero_carpeta_liquidador').val()),
        'taller_nombre':             estandariza_info($('#taller_nombre').val()),
        'taller_telefono':           estandariza_info($('#taller_telefono').val()),
        'compania_contacto_nombre':  estandariza_info($('#compania_contacto_nombre').val() || ''),
        'compania_contacto_mail':    estandariza_info($('#compania_contacto_mail').val() || ''),
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

    // Bienes afectados serializados (incluye id si ya existen para update/delete sync)
    payload['bienes_json'] = JSON.stringify(bienesMem.map(function(b) {
        return {
            id:              b.id || null,
            tipo:            b.tipo,
            categoria:       b.categoria,
            descripcion:     b.descripcion,
            direccion:       b.direccion || '',
            item_afectado:   b.item_afectado || '',
            estado:          b.estado,
            fecha_alarma:    b.fecha_alarma,
            observaciones:   b.observaciones,
            patente:         b.patente,
            marca:           b.marca,
            modelo:          b.modelo,
            anio_vehiculo:   b.anio_vehiculo,
            taller_nombre:   b.taller_nombre,
            taller_telefono: b.taller_telefono
        };
    }));

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

// ---- Al cargar: inicializar ----
$(document).ready(function() {
    var ramo_inicial = '<?php echo addslashes($ramo); ?>';
    if (ramo_inicial !== '') {
        toggleVehiculo(ramo_inicial);
    } else {
        recalcEtapas();
    }
    // Cargar ítems si hay póliza preseleccionada
    var id_poliza_inicial = $('#id_poliza').val();
    var items_csv_inicial = $('#items_seleccionados').val();
    if (id_poliza_inicial) {
        cargarItemsPoliza(id_poliza_inicial, items_csv_inicial);
    }
    var id_siniestro_inicial = $('#id_siniestro').val();
    // Bienes afectados: cargar desde BD (modo edición) o inicializar vacío (modo creación)
    cargarBienesAfectados(id_siniestro_inicial);
    // Pendientes: cargar en modo edición
    if (id_siniestro_inicial) {
        cargarPendientes(id_siniestro_inicial);
    }

    // Modo creación: el botón "Registrar Siniestro" arranca disabled hasta que
    // los obligatorios estén completos. Listeners en los inputs clave.
    if (MODO_CREACION) {
        $('#numero_poliza, #tipo_siniestro, #fecha_ocurrencia').on('change input', actualizarBotonRegistrar);
        // El listener de #items_seleccionados ya está en cargarItemsPoliza al hookear .chk_item
    } else {
        // Modo edición: cualquier cambio en inputs del form principal marca mutación.
        // Excluimos inputs dentro de modales (.modal *) para que la edición de un bien
        // no dispare aquí — el guardado del bien (guardarBien) llama marcarFormMutado()
        // explícitamente cuando confirma cambios.
        $('#formulario_siniestro').on('input change', 'input, select, textarea', function() {
            if ($(this).closest('.modal').length === 0) {
                marcarFormMutado();
            }
        });
        // Checkboxes de ítems están dentro del form, ya pasan por el listener delegado.
    }
    actualizarBotonRegistrar();
});

// =========================================================================
// BIENES AFECTADOS — se mantienen en memoria y se serializan al submit
// =========================================================================
var bienesMem = [];  // cada bien: { memkey, id?, tipo, categoria, descripcion, estado, fecha_alarma, observaciones, patente, marca, modelo, anio_vehiculo, taller_nombre, taller_telefono, total_docs?, entregados? }
var bienMemSeq = 0;

function cargarBienesAfectados(id_siniestro) {
    if (!id_siniestro) { bienesMem = []; renderTodosBienes(); return; }
    // Preservar bienes nuevos sin id (el usuario pudo haberlos agregado antes)
    var pendientesSinId = bienesMem.filter(function(b){ return !b.id; });
    $.getJSON('/bambooQA/backend/siniestros/busqueda_bienes_siniestro.php', { id_siniestro: id_siniestro }, function(resp) {
        var fromDb = (resp.data || []).map(function(b) {
            return {
                memkey: ++bienMemSeq,
                id: b.id,
                tipo: b.tipo,
                categoria: b.categoria || 'otro',
                descripcion: b.descripcion || '',
                direccion: b.direccion || '',
                item_afectado: b.item_afectado || '',
                estado: b.estado || 'Abierto',
                fecha_alarma: b.fecha_alarma || '',
                observaciones: b.observaciones || '',
                patente: b.patente || '',
                marca: b.marca || '',
                modelo: b.modelo || '',
                anio_vehiculo: b.anio_vehiculo || '',
                taller_nombre: b.taller_nombre || '',
                taller_telefono: b.taller_telefono || '',
                total_docs: b.total_docs || 0,
                entregados: b.entregados || 0
            };
        });
        bienesMem = fromDb.concat(pendientesSinId);
        renderTodosBienes();
    });
}
function renderTodosBienes() {
    var propios = bienesMem.filter(function(b){ return b.tipo === 'propio'; });
    var terceros = bienesMem.filter(function(b){ return b.tipo === 'tercero'; });
    renderTablaBienes('#tabla_bienes_propios tbody', propios);
    renderTablaBienes('#tabla_bienes_terceros tbody', terceros);
    $('#cnt_propios').text(propios.length);
    $('#cnt_terceros').text(terceros.length);
}
function renderTablaBienes(selector, lista) {
    var tbody = $(selector);
    tbody.empty();
    if (lista.length === 0) {
        tbody.append('<tr><td colspan="5"><em>Sin bienes registrados.</em></td></tr>');
        return;
    }
    lista.forEach(function(b) {
        var badge = badgeBien(b.estado);
        var alarma = b.fecha_alarma ? b.fecha_alarma : '<em>—</em>';
        var catLabel = catBienLabel(b.categoria);
        // En modo edición, los bienes se declararon al crear el siniestro: solo "Ver".
        // En modo creación, se pueden editar y eliminar mientras se arma el form.
        var btnEditar = MODO_CREACION
            ? '<button type="button" class="btn btn-sm btn-outline-info mr-1" onclick="editarBien(' + b.memkey + ')" title="Editar">✏️ Editar</button>'
            : '<button type="button" class="btn btn-sm btn-outline-info mr-1" onclick="editarBien(' + b.memkey + ')" title="Ver">👁️ Ver</button>';
        var btnEliminar = MODO_CREACION
            ? '<button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarBien(' + b.memkey + ')" title="Eliminar">🗑️ Eliminar</button>'
            : '';
        var fila = '<tr data-memkey="' + b.memkey + '">' +
            '<td>' + catLabel + '</td>' +
            '<td>' + escHtml(b.descripcion) + '</td>' +
            '<td>' + badge + '</td>' +
            '<td>' + alarma + '</td>' +
            '<td style="white-space:nowrap">' +
              btnEditar +
              btnEliminar +
            '</td>' +
        '</tr>';
        tbody.append(fila);
    });
}
function catBienLabel(cat) {
    if (cat === 'vehiculo') return '<span class="badge badge-info">Vehículo</span>';
    if (cat === 'inmueble') return '<span class="badge badge-warning">Inmueble</span>';
    if (cat === 'persona')  return '<span class="badge badge-success">Persona</span>';
    return '<span class="badge badge-light">Otro</span>';
}
function badgeBien(estado) {
    var cls = 'badge-secondary';
    if (estado === 'Abierto')   cls = 'badge-primary';
    if (estado === 'Cerrado')   cls = 'badge-secondary';
    if (estado === 'Rechazado') cls = 'badge-danger';
    return '<span class="badge ' + cls + '">' + estado + '</span>';
}
function escHtml(s) { return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escAttr(s) { return (s||'').replace(/'/g,"\\'").replace(/"/g,'&quot;'); }

function toggleCamposVehiculoBien() {
    var esVeh = $('#bien_categoria').val() === 'vehiculo';
    $('#bien_campos_vehiculo').toggle(esVeh);
    $('#bien_campos_taller').toggle(esVeh);
    $('#bien_msg_no_taller').toggle(!esVeh);
    // Dirección del bien: solo aplica para no-vehículo (incendio/inmueble)
    $('#bien_campo_direccion_wrap').toggle(!esVeh);
    // Pestaña Taller: visible solo si es vehículo Y el bien ya tiene taller persistido.
    // Los inputs taller_nombre/telefono se llenan desde la cadena de pendientes,
    // no desde este modal en el momento de creación.
    var tieneTaller = !!($.trim($('#bien_taller_nombre').val()) || $.trim($('#bien_taller_telefono').val()) || $.trim($('#bien_taller_correo').val()));
    setTallerTabVisible(esVeh && tieneTaller);
}

// Filtra las opciones del dropdown de categoría del bien según el ramo de la póliza.
// En no-vehículo, "Vehículo" no aplica (Adriana 18-may).
function filtrarCategoriasBienSegunRamo() {
    var ramo = ($('#ramo').val() || '').toUpperCase();
    var esVeh = ramo.indexOf('VEH') !== -1 || ramo.indexOf('AUTO') !== -1;
    var $opt = $('#bien_categoria option[value="vehiculo"]');
    if (esVeh) {
        $opt.prop('disabled', false).show();
    } else {
        $opt.prop('disabled', true).hide();
        // Si la categoría actual era vehiculo, cambiarla al default
        if ($('#bien_categoria').val() === 'vehiculo') {
            $('#bien_categoria').val('inmueble');
        }
    }
}

// Muestra/oculta la pestaña Taller del modal del bien.
// Si la pestaña activa era Taller y se oculta, redirige a Descripción.
function setTallerTabVisible(visible) {
    var $li = $('#li_tab_taller');
    $li.toggle(!!visible);
    if (!visible) {
        var $tallerLink = $li.find('a');
        if ($tallerLink.hasClass('active')) {
            $('#modalBien .nav-tabs a[href="#tab-bien-desc"]').tab('show');
        }
    }
}

// Muestra/oculta el campo "Ítem afectado de la póliza" según conteo de ítems.
// Visible solo cuando la póliza tiene >1 ítem; si hay 1 (o ninguno cargado aún),
// se oculta porque la sección "Ítems afectados" del form principal cubre la asociación.
function setItemAfectadoVisible() {
    var n = (typeof itemsCache !== 'undefined' && itemsCache) ? itemsCache.length : 0;
    $('#bien_campo_item_afectado_wrap').toggle(n > 1);
}

// Sugerir categoría default según ramo de la póliza (la primera vez que se abre un bien propio)
function categoriaDefaultSegunRamo() {
    var ramo = ($('#ramo').val() || '').toUpperCase();
    if (ramo.indexOf('VEH') !== -1 || ramo.indexOf('AUTO') !== -1) return 'vehiculo';
    if (ramo.indexOf('INCENDIO') !== -1 || ramo.indexOf('HOGAR') !== -1) return 'inmueble';
    return 'otro';
}

// Fecha ISO (YYYY-MM-DD) a N días desde hoy
function fechaEnDiasDesdeHoy(dias) {
    var d = new Date();
    d.setDate(d.getDate() + dias);
    var m = String(d.getMonth() + 1).padStart(2, '0');
    var dd = String(d.getDate()).padStart(2, '0');
    return d.getFullYear() + '-' + m + '-' + dd;
}

// Para bien PROPIO categoría vehículo: toma el siguiente ítem marcado sin bien asociado aún.
// Matcheamos por patente (lo más identificable) y caemos back al orden de marca.
function siguienteItemVehiculoSinBien() {
    var csv = ($('#items_seleccionados').val() || '').split(',').map(function(x){ return $.trim(x); }).filter(Boolean);
    if (!csv.length || !itemsCache.length) return null;
    // Números ya usados por bienes propios (de vehículo) en memoria
    var patentesUsadas = bienesMem
        .filter(function(b){ return b.tipo === 'propio' && b.categoria === 'vehiculo' && b.patente; })
        .map(function(b){ return String(b.patente).toUpperCase(); });
    for (var i = 0; i < csv.length; i++) {
        var ni = csv[i];
        var it = itemsCache.find(function(x){ return String(x.numero_item) === ni; });
        if (!it) continue;
        var pat = String(it.patente || it.patente_ubicacion || '').toUpperCase();
        if (pat && patentesUsadas.indexOf(pat) === -1) return it;
        if (!pat) {
            // si el ítem no tiene patente (p.ej. inmueble mezclado), saltar
            continue;
        }
    }
    return null;
}

function nuevoBien(tipo) {
    $('#bien_id').val('');
    $('#bien_memkey').val('');
    $('#bien_tipo').val(tipo);
    filtrarCategoriasBienSegunRamo();
    var cat = (tipo === 'propio') ? categoriaDefaultSegunRamo() : 'otro';
    $('#bien_categoria').val(cat);
    $('#bien_descripcion').val('');
    $('#bien_direccion').val(''); $('#bien_item_afectado').val('');
    $('#bien_patente').val(''); $('#bien_marca').val(''); $('#bien_modelo').val(''); $('#bien_anio').val('');
    $('#bien_taller_nombre').val(''); $('#bien_taller_telefono').val(''); $('#bien_taller_correo').val('');
    $('#bien_estado').val('Abierto');
    $('#bien_estado_original').val('');
    $('#bien_fecha_alarma').val(fechaEnDiasDesdeHoy(7));
    $('#bien_observaciones').val('');
    $('#bien_motivo').val('');
    $('#bien_motivo_wrap').hide();
    toggleCamposVehiculoBien();

    // Pre-poblar datos del próximo ítem marcado (solo para bien propio + vehículo)
    if (tipo === 'propio' && cat === 'vehiculo') {
        var it = siguienteItemVehiculoSinBien();
        if (it) {
            $('#bien_patente').val(it.patente || it.patente_ubicacion || '');
            $('#bien_marca').val(it.marca || '');
            $('#bien_modelo').val(it.modelo || '');
            $('#bien_anio').val(it.anio || '');
            // Descripción sugerida para que el usuario no quede con el campo obligatorio vacío
            var descSugerida = [it.marca, it.modelo, it.anio ? '(' + it.anio + ')' : '']
                .filter(Boolean).join(' ').trim();
            if (descSugerida) $('#bien_descripcion').val(descSugerida);
        }
    }
    // Pre-poblar dirección desde el ítem (bien propio + no-vehículo: inmueble/incendio).
    // El campo patente_ubicacion de la póliza lleva la dirección en este caso.
    if (tipo === 'propio' && cat !== 'vehiculo') {
        var csv = ($('#items_seleccionados').val() || '').split(',').map(function(x){ return $.trim(x); }).filter(Boolean);
        if (csv.length && itemsCache.length) {
            var it = itemsCache.find(function(x){ return String(x.numero_item) === csv[0]; });
            if (it && it.patente_ubicacion) {
                $('#bien_direccion').val(colapsaSaltos(it.patente_ubicacion));
            }
        }
    }

    // Reset al tab Descripción y checklist vacío (bien no persistido todavía)
    $('#modalBien .nav-tabs a[href="#tab-bien-desc"]').tab('show');
    cargarChecklistBien(null);

    // Bien recién creado: nunca tiene datos del taller. Se llenan desde la cadena
    // de pendientes (modal "marcar Entregado" de liquidador_contacto en vehículo).
    setTallerTabVisible(false);
    setItemAfectadoVisible();

    $('#modalBienTitle').text('Nuevo bien ' + (tipo === 'propio' ? 'propio' : 'de tercero'));
    $('#modalBien').modal('show');
}
function editarBien(memkey) {
    var b = bienesMem.find(function(x){ return x.memkey === memkey; });
    if (!b) { alert('Bien no encontrado.'); return; }
    $('#bien_id').val(b.id || '');
    $('#bien_memkey').val(b.memkey);
    $('#bien_tipo').val(b.tipo);
    filtrarCategoriasBienSegunRamo();
    $('#bien_categoria').val(b.categoria || 'otro');
    $('#bien_descripcion').val(b.descripcion);
    $('#bien_direccion').val(b.direccion || '');
    $('#bien_item_afectado').val(b.item_afectado || '');
    $('#bien_patente').val(b.patente || '');
    $('#bien_marca').val(b.marca || '');
    $('#bien_modelo').val(b.modelo || '');
    $('#bien_anio').val(b.anio_vehiculo || '');
    $('#bien_taller_nombre').val(b.taller_nombre || '');
    $('#bien_taller_telefono').val(b.taller_telefono || '');
    $('#bien_taller_correo').val(b.taller_correo || '');
    $('#bien_estado').val(b.estado);
    $('#bien_estado_original').val(b.estado);
    $('#bien_fecha_alarma').val(b.fecha_alarma || '');
    $('#bien_observaciones').val(b.observaciones || '');
    $('#bien_motivo').val('');
    $('#bien_motivo_wrap').hide();
    toggleCamposVehiculoBien();
    $('#modalBien .nav-tabs a[href="#tab-bien-desc"]').tab('show');
    cargarChecklistBien(b.id || null);

    // Pestaña Taller visible solo si el bien es vehículo y ya tiene datos persistidos
    var esVeh = (b.categoria === 'vehiculo');
    var tieneTaller = !!(b.taller_nombre || b.taller_telefono || b.taller_correo);
    setTallerTabVisible(esVeh && tieneTaller);
    setItemAfectadoVisible();

    $('#modalBienTitle').text('Editar bien ' + (b.tipo === 'propio' ? 'propio' : 'de tercero'));
    $('#modalBien').modal('show');
}
$(document).on('change', '#bien_estado', function() {
    var original = $('#bien_estado_original').val();
    var actual = $(this).val();
    if (original && original !== actual) {
        $('#bien_motivo_wrap').show();
    } else {
        $('#bien_motivo_wrap').hide();
    }
});
function guardarBien() {
    var desc = $.trim($('#bien_descripcion').val());
    if (!desc) { alert('La descripción es obligatoria.'); return; }
    var memkey = $('#bien_memkey').val();
    var bien_id = $('#bien_id').val();
    var categoria = $('#bien_categoria').val();
    var datos = {
        tipo:            $('#bien_tipo').val(),
        categoria:       categoria,
        descripcion:     desc,
        direccion:       $('#bien_direccion').val(),
        item_afectado:   $('#bien_item_afectado').val(),
        estado:          $('#bien_estado').val(),
        fecha_alarma:    $('#bien_fecha_alarma').val(),
        observaciones:   $('#bien_observaciones').val(),
        patente:         categoria === 'vehiculo' ? $('#bien_patente').val() : '',
        marca:           categoria === 'vehiculo' ? $('#bien_marca').val() : '',
        modelo:          categoria === 'vehiculo' ? $('#bien_modelo').val() : '',
        anio_vehiculo:   categoria === 'vehiculo' ? $('#bien_anio').val() : '',
        taller_nombre:   categoria === 'vehiculo' ? $('#bien_taller_nombre').val() : '',
        taller_telefono: categoria === 'vehiculo' ? $('#bien_taller_telefono').val() : '',
        taller_correo:   categoria === 'vehiculo' ? $('#bien_taller_correo').val() : ''
    };

    if (memkey) {
        var idx = bienesMem.findIndex(function(x){ return x.memkey == memkey; });
        if (idx >= 0) { Object.assign(bienesMem[idx], datos); }
    } else {
        bienesMem.push(Object.assign({ memkey: ++bienMemSeq }, datos));
    }
    marcarFormMutado();

    var cerrar = function() {
        $('#modalBien').modal('hide');
        renderTodosBienes();
    };
    // Si el bien ya está persistido, guardar también el checklist inline
    if (bien_id) {
        persistirChecklistBien(bien_id, cerrar);
    } else {
        cerrar();
    }
}
function eliminarBien(memkey) {
    var b = bienesMem.find(function(x){ return x.memkey === memkey; });
    if (!b) return;
    var msg = b.id
        ? '¿Eliminar este bien? Al guardar el siniestro se borrarán también su checklist y bitácora.'
        : '¿Quitar este bien del formulario?';
    if (!confirm(msg)) return;
    bienesMem = bienesMem.filter(function(x){ return x.memkey !== memkey; });
    marcarFormMutado();
    renderTodosBienes();
}

// =========================================================================
// CHECKLIST INLINE — desactivado (Adriana decidió no usar la pestaña Documentación,
// reunión 11-may). Stubs para no romper las llamadas existentes desde nuevoBien/editarBien/guardarBien.
function cargarChecklistBien(id_bien) { /* no-op */ }
function persistirChecklistBien(id_bien, cb) { if (cb) cb(); }

// =========================================================================
// PENDIENTES POR RESPONSABLE (Cliente / Liquidador / Compañía / Taller)
// =========================================================================
var pendientesMem = [];
var liquidadorContacto = { nombre: '', correo: '', numero_siniestro: '', numero_poliza: '', nombre_asegurado: '' };

function badgeResp(resp, usuario) {
    if (resp === 'Cliente')    return '<span class="badge badge-info">Cliente</span>';
    if (resp === 'Liquidador') return '<span class="badge badge-warning">Liquidador</span>';
    if (resp === 'Compañía')  return '<span class="badge badge-dark">Compañía</span>';
    if (resp === 'Taller')     return '<span class="badge" style="background:#8e44ad;color:#fff">🔧 Taller</span>';
    if (resp === 'Usuario')    return '<span class="badge" style="background:#0d6efd;color:#fff">👤 ' + escHtml(usuario || 'Usuario') + '</span>';
    return '<span class="badge badge-light">—</span>';
}
function badgeEstadoPend(est) {
    if (est === 'Pendiente')  return '<span class="badge badge-secondary">Pendiente</span>';
    if (est === 'Entregado')  return '<span class="badge badge-success">Entregado</span>';
    if (est === 'No aplica')  return '<span class="badge badge-light">No aplica</span>';
    return est || '';
}

function calcularQuienLleva() {
    var abiertos = pendientesMem.filter(function(p){ return p.estado === 'Pendiente'; });
    if (abiertos.length === 0) {
        $('#widget_quien_lleva').removeClass('badge-light badge-info badge-warning badge-dark')
            .addClass('badge-success').text('✅ Sin pendientes');
        return;
    }
    var orden = ['Cliente','Liquidador','Compañía','Taller'];
    for (var i = 0; i < orden.length; i++) {
        if (abiertos.some(function(p){ return p.responsable === orden[i]; })) {
            var cls = orden[i] === 'Cliente' ? 'badge-info'
                    : orden[i] === 'Liquidador' ? 'badge-warning'
                    : orden[i] === 'Compañía'  ? 'badge-dark'
                    : 'badge-taller';
            var estilo = orden[i] === 'Taller' ? 'background:#8e44ad;color:#fff' : '';
            $('#widget_quien_lleva').removeClass('badge-light badge-info badge-warning badge-dark badge-success badge-taller')
                .addClass(cls)
                .attr('style', estilo)
                .text('Ahora la lleva: ' + orden[i]);
            return;
        }
    }
}

function refrescarSelectBienesPendiente() {
    var $sel = $('#pend_id_bien');
    var actual = $sel.val();
    $sel.empty().append('<option value="">— Siniestro en general —</option>');
    (bienesMem || []).forEach(function(b) {
        if (!b.id) return; // solo bienes persistidos
        var label = (b.tipo === 'propio' ? 'Propio' : 'Tercero') + ' — ' + (b.descripcion || '(sin desc.)');
        $sel.append('<option value="' + b.id + '">' + label + '</option>');
    });
    if (actual) $sel.val(actual);
}

function renderPendientes() {
    var $body = $('#filas_pendientes');
    if (!pendientesMem.length) {
        $body.html('<tr><td colspan="6" class="text-center text-muted"><em>Sin pendientes registrados.</em></td></tr>');
        calcularQuienLleva();
        return;
    }
    var html = '';
    pendientesMem.forEach(function(p) {
        var notasHtml = p.notas ? escHtml(p.notas) : '';
        var esRegistroHistorico = (p.responsable === 'Usuario' || p.codigo_tarea === 'creacion_siniestro');
        var botonRecordatorio = (p.estado === 'Pendiente' && !esRegistroHistorico)
            ? '<button type="button" class="btn btn-sm btn-outline-primary mr-1" title="Enviar recordatorio amigable" onclick="enviarRecordatorio(' + p.id + ')">✉️</button>'
            : '';
        var botonResolver = (p.estado === 'Pendiente' && !esRegistroHistorico)
            ? '<button type="button" class="btn btn-sm btn-success mr-1" title="Marcar como Entregado" onclick="abrirModalResolver(' + p.id + ')">✅</button>'
            : '';
        // Si la tarea está Entregada y tiene datos capturables, el lápiz abre el modal
        // Resolver pre-cargado (para ver/editar lo capturado). Si no, modal genérico.
        var btnEditarOnclick = (p.estado === 'Entregado' && tieneCaptura(p.codigo_tarea))
            ? 'abrirModalResolver(' + p.id + ')'
            : 'abrirModalPendiente(' + p.id + ')';
        var botonEditar = !esRegistroHistorico
            ? '<button type="button" class="btn btn-sm btn-outline-secondary mr-1" onclick="' + btnEditarOnclick + '">✏️</button>'
            : '';
        var botonEliminar = !esRegistroHistorico
            ? '<button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarPendiente(' + p.id + ')">🗑️</button>'
            : '';
        html += '<tr>' +
            '<td>' + badgeResp(p.responsable, p.usuario_creacion) + '</td>' +
            '<td>' + escHtml(p.descripcion) + '</td>' +
            '<td>' + badgeEstadoPend(p.estado) + '</td>' +
            '<td>' + fmtFechaHora(p.fecha_entrega) + '</td>' +
            '<td><small>' + notasHtml + '</small></td>' +
            '<td style="white-space:nowrap">' +
                botonResolver +
                botonRecordatorio +
                botonEditar +
                botonEliminar +
            '</td>' +
        '</tr>';
    });
    $body.html(html);
    calcularQuienLleva();
}

function cargarPendientes(id_siniestro) {
    if (!id_siniestro) return;
    $.getJSON('/bambooQA/backend/siniestros/busqueda_pendientes_siniestro.php', { id_siniestro: id_siniestro }, function(resp) {
        pendientesMem = (resp && resp.data) || [];
        renderPendientes();
    });
}

function abrirModalPendiente(id) {
    refrescarSelectBienesPendiente();
    if (id) {
        var p = pendientesMem.find(function(x){ return x.id == id; });
        if (!p) return;
        $('#modalPendienteTitle').text('Editar pendiente');
        $('#pend_id').val(p.id);
        $('#pend_responsable').val(p.responsable);
        $('#pend_estado').val(p.estado);
        $('#pend_fecha_entrega').val(p.fecha_entrega ? String(p.fecha_entrega).slice(0,10) : '');
        $('#pend_descripcion').val(p.descripcion);
        $('#pend_notas').val(p.notas || '');
        $('#pend_id_bien').val(p.id_bien || '');
        $('#pend_edicion_extras').show();
    } else {
        $('#modalPendienteTitle').text('Nuevo pendiente');
        $('#pend_id').val('');
        $('#pend_responsable').val('Cliente');
        $('#pend_estado').val('Pendiente');
        $('#pend_fecha_entrega').val('');
        $('#pend_descripcion').val('');
        $('#pend_notas').val('');
        $('#pend_id_bien').val('');
        $('#pend_edicion_extras').hide();
    }
    $('#modalPendiente').modal('show');
}

// Auto-llenar fecha de entrega con hoy al marcar Entregado
$(document).on('change', '#pend_estado', function() {
    if ($(this).val() === 'Entregado' && !$('#pend_fecha_entrega').val()) {
        var d = new Date();
        var iso = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
        $('#pend_fecha_entrega').val(iso);
    }
});

// ============================================================
// MODAL "Resolver pendiente" (marca Entregado y captura datos)
// ============================================================

function fechaHoyIso() {
    var d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}

// Formatea timestamp como 'YYYY-MM-DD HH:MM' en zona Santiago de Chile.
// Si es solo fecha (sin ':' en la entrada), devuelve solo 'YYYY-MM-DD'.
function fmtFechaHora(s) {
    if (!s) return '—';
    var raw = String(s);
    // Postgres timestamptz se serializa como '2026-05-11 03:48:19.284377+00'.
    // JS Date requiere offset con minutos ('+00:00'), así que normalizamos.
    var iso = raw.replace(' ', 'T').replace(/([+-]\d{2})$/, '$1:00');
    var d = new Date(iso);
    if (isNaN(d.getTime())) return raw;
    var hasTime = raw.indexOf(':') !== -1;
    try {
        var fmt = new Intl.DateTimeFormat('es-CL', {
            timeZone: 'America/Santiago',
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: hasTime ? '2-digit' : undefined,
            minute: hasTime ? '2-digit' : undefined,
            hour12: false
        });
        var parts = fmt.formatToParts(d);
        var get = function(t) {
            var p = parts.find(function(x){ return x.type === t; });
            return p ? p.value : '';
        };
        var fecha = get('year') + '-' + get('month') + '-' + get('day');
        if (!hasTime) return fecha;
        return fecha + ' ' + get('hour') + ':' + get('minute');
    } catch (e) {
        // Fallback: hora local del navegador
        var p = function(n){ return String(n).padStart(2,'0'); };
        var fecha = d.getFullYear() + '-' + p(d.getMonth()+1) + '-' + p(d.getDate());
        if (!hasTime) return fecha;
        return fecha + ' ' + p(d.getHours()) + ':' + p(d.getMinutes());
    }
}

function bienesPropiosVehiculares() {
    return (bienesMem || []).filter(function(b){
        return b.tipo === 'propio' && b.categoria === 'vehiculo' && b.id;
    });
}

// Lista de codigos de tarea que capturan datos al cerrar.
// Si una tarea con este código ya está Entregada y se quiere editar sus datos,
// se abre el modal Resolver en modo "Editar datos" en lugar del modal genérico.
var TAREAS_CON_CAPTURA = [
    'compania_entrega_numero',
    'liquidador_contacto',
    'cliente_entrega',
    'cliente_entrega_faltantes',
    'liquidador_accion',
    'cliente_ingreso_taller',
    'cliente_firma_finiquito',
    'taller_fecha_entrega',
    'liquidador_envio_compania',
    'compania_pago',
    'taller_disponibilidad_repuestos'
];

function tieneCaptura(codigo_tarea) {
    return codigo_tarea && TAREAS_CON_CAPTURA.indexOf(codigo_tarea) !== -1;
}

function abrirModalResolver(id_pendiente) {
    var p = pendientesMem.find(function(x){ return x.id == id_pendiente; });
    if (!p) return;
    // Refrescar bienesMem desde BD antes de abrir el modal, así los inputs
    // pre-cargados reflejan datos actualizados (importante al editar tareas
    // ya entregadas tras un location.reload reciente).
    var id_siniestro = $('#id_siniestro').val();
    if (id_siniestro) {
        $.ajax({
            url: '/bambooQA/backend/siniestros/busqueda_bienes_siniestro.php',
            data: { id_siniestro: id_siniestro },
            dataType: 'json',
            async: false  // necesitamos los datos antes de renderizar
        }).done(function(resp) {
            var fromDb = (resp.data || []).map(function(b) {
                return Object.assign({ memkey: ++bienMemSeq, _persisted: true }, b);
            });
            bienesMem = fromDb;
        });
    }
    // Si la tarea ya está Entregada, el modal está en modo "ver/editar datos"
    var modoEdicion = (p.estado === 'Entregado');
    $('#modalResolverPendiente .modal-title').html(
        modoEdicion ? '✏️ Editar datos de la tarea' : '✅ Marcar como Entregado'
    );
    $('#modalResolverPendiente .btn-success').text(
        modoEdicion ? 'Guardar cambios' : 'Marcar Entregado'
    );
    $('#modalResolverPendiente').data('modo_edicion', modoEdicion);
    $('#resolver_pend_id').val(p.id);
    $('#resolver_codigo_tarea').val(p.codigo_tarea || '');
    $('#resolver_descripcion').html('<strong>' + escHtml(p.descripcion) + '</strong>');
    $('#resolver_notas').val(p.notas || '');
    $('#resolver_body').html('<em>Cargando…</em>');
    $('#modalResolverPendiente').modal('show');

    var ramo = ($('#ramo').val() || '').toUpperCase();
    var esVeh = ramo.indexOf('VEH') !== -1 || ramo.indexOf('AUTO') !== -1;

    switch (p.codigo_tarea) {
        case 'compania_entrega_numero':
            renderResolver_companiaEntrega(p);
            break;
        case 'liquidador_contacto':
            renderResolver_liquidadorContacto(p, esVeh);
            break;
        case 'cliente_entrega':
            renderResolver_clienteEntrega(p);
            break;
        case 'cliente_entrega_faltantes':
            $('#resolver_body').html(
                '<p class="text-muted">El cliente entregó los documentos que faltaban. ' +
                'Al guardar, el flujo continúa con el liquidador para preparar el borrador del finiquito.</p>'
            );
            break;
        case 'liquidador_accion':
            esVeh ? renderResolver_liquidadorAccionVeh(p) : renderResolver_liquidadorAccionNoVeh(p);
            break;
        case 'taller_disponibilidad_repuestos':
            renderResolver_tallerDisponibilidad(p);
            break;
        case 'cliente_ingreso_taller':
            renderResolver_clienteIngresoTaller(p);
            break;
        case 'cliente_firma_finiquito':
            renderResolver_clienteFirmaFiniquito(p);
            break;
        case 'taller_fecha_entrega':
            renderResolver_tallerFechaEntrega(p);
            break;
        case 'liquidador_envio_compania':
            renderResolver_liquidadorEnvioCompania(p, esVeh);
            break;
        case 'compania_pago':
            renderResolver_companiaPago(p);
            break;
        default:
            renderResolver_generico(p);
    }
}

function renderResolver_companiaEntrega(p) {
    $('#resolver_body').html('<em>Cargando liquidadores conocidos…</em>');
    $.getJSON('/bambooQA/backend/siniestros/busqueda_liquidadores_compania.php',
        { id_siniestro: p.id_siniestro }, function(resp) {
        var liqs = (resp && resp.data) || [];
        var compania = (resp && resp.compania) || '';
        var hayConocidos = liqs.length > 0;
        var ramo = ($('#ramo').val() || '').toUpperCase();
        var esVeh = ramo.indexOf('VEH') !== -1 || ramo.indexOf('AUTO') !== -1;

        // Header del bloque liquidador
        var headerLiquidador = '<h6>Liquidador asignado' +
            (compania ? ' <small class="text-muted">(' + escHtml(compania) + ')</small>' : '') +
            '</h6>';

        // Dropdown de liquidadores conocidos (solo si hay alguno).
        var dropdownHtml = '';
        if (hayConocidos) {
            var opts = '<option value="">— Nuevo liquidador —</option>';
            liqs.forEach(function(l) {
                opts += '<option value="' + l.id + '"' +
                        ' data-nombre="'   + escAttr(l.nombre)   + '"' +
                        ' data-telefono="' + escAttr(l.telefono) + '"' +
                        ' data-correo="'   + escAttr(l.correo)   + '">' +
                        escHtml(l.nombre) + (l.correo ? ' — ' + escHtml(l.correo) : '') +
                        '</option>';
            });
            dropdownHtml =
              '<div class="form-group">' +
                '<label>Liquidador conocido</label>' +
                '<select class="form-control" id="rp_liquidador_id" onchange="onResolverLiquidadorSelect()">' +
                  opts +
                '</select>' +
              '</div>';
        }

        var helperText = hayConocidos
            ? 'Datos del liquidador (opcionales). Si selecciona un conocido sus datos se reusan; los nuevos se persisten para futuros siniestros de la misma compañía.'
            : 'Datos del liquidador (opcionales). Se persisten para futuros siniestros de la misma compañía.';

        // Bloque taller (solo en vehículo, por cada bien vehicular)
        var bloqueTaller = '';
        if (esVeh) {
            var vehs = bienesPropiosVehiculares();
            if (vehs.length > 0) {
                bloqueTaller = '<hr><h6>Taller designado por la compañía</h6>';
                vehs.forEach(function(b) {
                    bloqueTaller +=
                      '<div class="border rounded p-2 mb-2">' +
                        '<strong>🚗 ' + escHtml(b.descripcion) + '</strong>' +
                        '<div class="form-row mt-2">' +
                          '<div class="col-md-4 form-group mb-1">' +
                            '<label>Nombre del taller</label>' +
                            '<input type="text" class="form-control rp-bien"' +
                            ' data-bien="' + b.id + '" data-field="taller_nombre"' +
                            ' value="' + escAttr(b.taller_nombre || '') + '">' +
                          '</div>' +
                          '<div class="col-md-4 form-group mb-1">' +
                            '<label>Teléfono</label>' +
                            '<input type="text" class="form-control rp-bien"' +
                            ' data-bien="' + b.id + '" data-field="taller_telefono"' +
                            ' value="' + escAttr(b.taller_telefono || '') + '"' +
                            ' placeholder="56 9 XXXX XXXX">' +
                          '</div>' +
                          '<div class="col-md-4 form-group mb-1">' +
                            '<label>Correo</label>' +
                            '<input type="email" class="form-control rp-bien"' +
                            ' data-bien="' + b.id + '" data-field="taller_correo"' +
                            ' value="' + escAttr(b.taller_correo || '') + '">' +
                          '</div>' +
                        '</div>' +
                      '</div>';
                });
            }
        }

        // Pre-cargar valores actuales del siniestro (modo edición tras Entregado).
        var actNS  = $.trim($('#numero_siniestro').val() || '');
        var actLN  = $.trim($('#liquidador_nombre').val() || '');
        var actLT  = $.trim($('#liquidador_telefono').val() || '');
        var actLC  = $.trim($('#liquidador_correo').val() || '');

        var html =
          '<div class="form-group">' +
            '<label>N° Siniestro Compañía <span class="text-danger">*</span></label>' +
            '<input type="text" class="form-control" id="rp_numero_siniestro"' +
            ' value="' + escAttr(actNS) + '"' +
            ' placeholder="Número entregado por la compañía">' +
          '</div>' +
          '<hr>' +
          headerLiquidador +
          dropdownHtml +
          '<div class="form-row">' +
            '<div class="col-md-4 form-group">' +
              '<label>Nombre</label>' +
              '<input type="text" class="form-control" id="rp_liquidador_nombre" value="' + escAttr(actLN) + '">' +
            '</div>' +
            '<div class="col-md-4 form-group">' +
              '<label>Teléfono</label>' +
              '<input type="text" class="form-control" id="rp_liquidador_telefono"' +
              ' value="' + escAttr(actLT) + '"' +
              ' placeholder="56 9 XXXX XXXX">' +
            '</div>' +
            '<div class="col-md-4 form-group">' +
              '<label>Correo</label>' +
              '<input type="email" class="form-control" id="rp_liquidador_correo" value="' + escAttr(actLC) + '">' +
            '</div>' +
          '</div>' +
          '<small class="text-muted">' + helperText + '</small>' +
          bloqueTaller;
        $('#resolver_body').html(html);
    });
}

function onResolverLiquidadorSelect() {
    var $sel = $('#rp_liquidador_id');
    var id = $sel.val();
    if (id === '') {
        $('#rp_liquidador_nombre, #rp_liquidador_telefono, #rp_liquidador_correo')
            .val('').prop('readonly', false);
    } else {
        var $opt = $sel.find('option:selected');
        $('#rp_liquidador_nombre').val($opt.data('nombre')).prop('readonly', true);
        $('#rp_liquidador_telefono').val($opt.data('telefono')).prop('readonly', true);
        $('#rp_liquidador_correo').val($opt.data('correo')).prop('readonly', true);
    }
}

function renderResolver_liquidadorContacto(p, esVeh) {
    var msg = esVeh
        ? 'Liquidador tomó contacto con el cliente.'
        : 'Liquidador tomó contacto y pidió antecedentes al cliente.';
    $('#resolver_body').html(
        '<p class="text-muted">' + msg + '</p>' +
        '<div class="form-group">' +
            '<label>N° Carpeta Liquidador <small class="text-muted">(opcional)</small></label>' +
            '<input type="text" class="form-control" id="rp_numero_carpeta_liquidador"' +
            ' placeholder="Si el liquidador la entregó">' +
        '</div>'
    );
}

function renderResolver_clienteEntrega(p) {
    var ramo = ($('#ramo').val() || '').toUpperCase();
    var esVeh = ramo.indexOf('VEH') !== -1 || ramo.indexOf('AUTO') !== -1;
    var html = '<p class="text-muted">El cliente entregó los antecedentes solicitados. ' +
        'Puede agregar notas en el campo de abajo si desea dejar registro de qué entregó.</p>';
    // En no-vehículo: opción para registrar documentos que aún faltan.
    // Si se llena, no avanza directo a liquidador_accion sino que crea una
    // sub-tarea cliente_entrega_faltantes hasta que se entreguen.
    if (!esVeh) {
        html +=
          '<hr>' +
          '<div class="form-group">' +
            '<label>Documentos faltantes <small class="text-muted">(opcional)</small></label>' +
            '<textarea class="form-control" id="rp_documentos_faltantes" rows="2"' +
            ' placeholder="Si el liquidador identifica documentos que aún faltan, lístelos aquí. Se creará una sub-tarea para el cliente."></textarea>' +
            '<small class="text-muted">Si lo deja vacío, el flujo avanza al liquidador para que prepare el borrador del finiquito.</small>' +
          '</div>';
    }
    $('#resolver_body').html(html);
}

function renderResolver_liquidadorAccionVeh(p) {
    var vehs = bienesPropiosVehiculares();
    if (!vehs.length) {
        $('#resolver_body').html('<div class="alert alert-warning">No hay bienes propios vehiculares.</div>');
        return;
    }
    var html = '<p class="text-muted">El liquidador emitió la orden de reparación. Por bien:</p>';
    vehs.forEach(function(b) {
        var obsStyle = b.importacion_repuestos ? '' : ' style="display:none"';
        html += '<div class="border rounded p-2 mb-2">' +
                  '<strong>🚗 ' + escHtml(b.descripcion) + '</strong>' +
                  '<div class="form-row mt-2">' +
                    '<div class="col-md-5 form-group mb-1">' +
                      '<label>Fecha orden de reparación</label>' +
                      '<input type="date" class="form-control rp-bien"' +
                      ' data-bien="' + b.id + '" data-field="liquidador_fecha_orden_reparacion"' +
                      ' value="' + (b.liquidador_fecha_orden_reparacion || fechaHoyIso()) + '">' +
                    '</div>' +
                    '<div class="col-md-7 form-group mb-1 d-flex align-items-end">' +
                      '<div class="form-check mb-2">' +
                        '<input type="checkbox" class="form-check-input rp-bien rp-toggle-imp"' +
                        ' id="rp_imp_' + b.id + '"' +
                        ' data-bien="' + b.id + '" data-field="importacion_repuestos" value="1"' +
                        (b.importacion_repuestos ? ' checked' : '') + '>' +
                        '<label class="form-check-label" for="rp_imp_' + b.id + '">Hay importación de repuestos</label>' +
                      '</div>' +
                    '</div>' +
                  '</div>' +
                  '<div class="form-group mb-0 rp-obs-wrap" data-for-bien="' + b.id + '"' + obsStyle + '>' +
                    '<label>Observación de importación <small class="text-muted">(opcional)</small></label>' +
                    '<textarea class="form-control rp-bien"' +
                    ' data-bien="' + b.id + '" data-field="importacion_repuestos_obs" rows="1">' +
                    escHtml(b.importacion_repuestos_obs || '') + '</textarea>' +
                  '</div>' +
                '</div>';
    });
    $('#resolver_body').html(html);
}

// Toggle del textarea "Observación de importación" según checkbox
$(document).on('change', '.rp-toggle-imp', function() {
    var idBien = $(this).data('bien');
    $('.rp-obs-wrap[data-for-bien="' + idBien + '"]').toggle($(this).is(':checked'));
});

function renderResolver_liquidadorAccionNoVeh(p) {
    $('#resolver_body').html(
        '<p class="text-muted">El liquidador generó el finiquito.</p>' +
        '<div class="form-group">' +
            '<label>Fecha de generación del finiquito</label>' +
            '<input type="date" class="form-control" id="rp_liquidador_fecha_finiquito"' +
            ' value="' + fechaHoyIso() + '">' +
        '</div>'
    );
}

function renderResolver_tallerDisponibilidad(p) {
    $('#resolver_body').html(
        '<p class="text-muted">El taller confirmó que los repuestos están disponibles ' +
        'y se puede coordinar el reingreso del vehículo. Al marcar entregado se enviará ' +
        'un correo al cliente avisándole.</p>'
    );
}

function renderResolver_clienteIngresoTaller(p) {
    var vehs = bienesPropiosVehiculares();
    if (!vehs.length) {
        $('#resolver_body').html('<div class="alert alert-warning">No hay bienes propios vehiculares.</div>');
        return;
    }
    var html = '<p class="text-muted">Fecha en que el cliente ingresó el vehículo al taller:</p>';
    vehs.forEach(function(b) {
        html += '<div class="border rounded p-2 mb-2">' +
                  '<strong>🚗 ' + escHtml(b.descripcion) + '</strong>' +
                  '<div class="form-row mt-2">' +
                    '<div class="col-md-6 form-group mb-0">' +
                      '<label>Fecha de ingreso al taller</label>' +
                      '<input type="date" class="form-control rp-bien"' +
                      ' data-bien="' + b.id + '" data-field="cliente_fecha_ingreso_taller"' +
                      ' value="' + (b.cliente_fecha_ingreso_taller || fechaHoyIso()) + '">' +
                    '</div>' +
                  '</div>' +
                '</div>';
    });
    $('#resolver_body').html(html);
}

function renderResolver_clienteFirmaFiniquito(p) {
    $('#resolver_body').html(
        '<p class="text-muted">Cliente firmó el finiquito.</p>' +
        '<div class="form-group">' +
            '<label>Fecha firma finiquito</label>' +
            '<input type="date" class="form-control" id="rp_cliente_fecha_firma_finiquito"' +
            ' value="' + fechaHoyIso() + '">' +
        '</div>'
    );
}

function renderResolver_tallerFechaEntrega(p) {
    var vehs = bienesPropiosVehiculares();
    if (!vehs.length) {
        $('#resolver_body').html('<div class="alert alert-warning">No hay bienes propios vehiculares.</div>');
        return;
    }
    var html = '<p class="text-muted">Fecha que confirmó el taller para la entrega del vehículo:</p>';
    vehs.forEach(function(b) {
        html += '<div class="border rounded p-2 mb-2">' +
                  '<strong>🚗 ' + escHtml(b.descripcion) + '</strong>' +
                  '<div class="form-row mt-2">' +
                    '<div class="col-md-6 form-group mb-0">' +
                      '<label>Fecha compromiso de entrega</label>' +
                      '<input type="date" class="form-control rp-bien"' +
                      ' data-bien="' + b.id + '" data-field="taller_fecha_compromiso_entrega"' +
                      ' value="' + (b.taller_fecha_compromiso_entrega || '') + '">' +
                    '</div>' +
                  '</div>' +
                '</div>';
    });
    $('#resolver_body').html(html);
}

function renderResolver_liquidadorEnvioCompania(p, esVeh) {
    var html = '<p class="text-muted">Liquidador confirmó envío del finiquito a la compañía.</p>' +
        '<div class="form-group">' +
            '<label>Fecha de envío</label>' +
            '<input type="date" class="form-control" id="rp_liquidador_fecha_envio_compania"' +
            ' value="' + fechaHoyIso() + '">' +
        '</div>';
    if (!esVeh) {
        html +=
        '<hr><h6>Contacto en la compañía <small class="text-muted">(para consultas de pago)</small></h6>' +
        '<div class="form-row">' +
            '<div class="col-md-6 form-group">' +
                '<label>Nombre del contacto</label>' +
                '<input type="text" class="form-control" id="rp_compania_contacto_nombre">' +
            '</div>' +
            '<div class="col-md-6 form-group">' +
                '<label>Correo</label>' +
                '<input type="email" class="form-control" id="rp_compania_contacto_mail">' +
            '</div>' +
        '</div>';
    }
    $('#resolver_body').html(html);
}

function renderResolver_companiaPago(p) {
    $('#resolver_body').html(
        '<p class="text-muted">La compañía confirmó el pago. Al guardar, el siniestro se cierra automáticamente.</p>' +
        '<div class="form-group">' +
            '<label>Fecha de pago / indemnización</label>' +
            '<input type="date" class="form-control" id="rp_compania_fecha_pago"' +
            ' value="' + fechaHoyIso() + '">' +
        '</div>'
    );
}

function renderResolver_generico(p) {
    $('#resolver_body').html(
        '<p class="text-muted">Esta tarea no requiere captura de datos adicionales. ' +
        'Puede agregar una nota libre y marcar entregado.</p>'
    );
}

function guardarResolverPendiente() {
    var id = $('#resolver_pend_id').val();
    var codigo = $('#resolver_codigo_tarea').val();
    var p = pendientesMem.find(function(x){ return x.id == id; });
    if (!p) return;

    // Construir payload
    var payload = {};
    var payload_bienes = {};

    $('#resolver_body').find('input, select, textarea').each(function() {
        var $el = $(this);
        var bien = $el.data('bien');
        if (typeof bien !== 'undefined' && bien !== null && bien !== '') {
            var f = $el.data('field');
            if (!f) return;
            var bk = String(bien);
            payload_bienes[bk] = payload_bienes[bk] || {};
            if ($el.attr('type') === 'checkbox') {
                payload_bienes[bk][f] = $el.is(':checked') ? '1' : '';
            } else {
                payload_bienes[bk][f] = $el.val();
            }
        } else if (this.id && this.id.indexOf('rp_') === 0) {
            payload[this.id.substring(3)] = $el.val();
        }
    });

    // Si la tarea ya estaba Entregada, conservamos su fecha original para no
    // sobrescribirla con la fecha de hoy.
    var modoEdicion = $('#modalResolverPendiente').data('modo_edicion') === true;
    var fechaEnvio = modoEdicion && p.fecha_entrega
        ? String(p.fecha_entrega).slice(0, 19).replace('T', ' ')
        : fechaHoyIso();
    var data = {
        id:             p.id,
        responsable:    p.responsable,
        descripcion:    p.descripcion,
        estado:         'Entregado',
        fecha_entrega:  fechaEnvio,
        notas:          $('#resolver_notas').val(),
        payload:        payload,
        payload_bienes: payload_bienes
    };

    $.post('/bambooQA/backend/siniestros/actualiza_pendiente.php', data, null, 'json')
        .done(function(resp) {
            if (resp && resp.ok) {
                $('#modalResolverPendiente').modal('hide');
                if (resp.cliente_completo && resp.liquidador && resp.liquidador.correo) {
                    // Mostrar el modal "Notificar liquidador". La recarga del form se
                    // hace al cerrar ese modal (listener hidden.bs.modal más abajo).
                    liquidadorContacto = resp.liquidador;
                    $('#notif_liq_nombre').text(resp.liquidador.nombre || '(sin nombre)');
                    $('#notif_liq_correo').text(resp.liquidador.correo);
                    $('#modalNotificarLiquidador').modal('show');
                } else {
                    // Refresh parcial: la mayoría de tareas solo modifican datos del bien
                    // o crean la siguiente tarea, no cambian el form principal.
                    // Para tareas que sí cambian campos visibles del siniestro mismo
                    // (N° siniestro, Estado, Liquidador, contacto compañía, cierre),
                    // recargamos toda la página porque pueden revelar/ocultar bloques.
                    var TAREAS_QUE_RECARGAN_FORM = [
                        'compania_entrega_numero',   // N°, Estado→Abierto, Liquidador revelado
                        'liquidador_contacto',       // N° Carpeta Liquidador (no-veh)
                        'liquidador_envio_compania', // fecha envío, contacto compañía (no-veh)
                        'compania_pago'              // fecha pago + estado→Cerrado
                    ];
                    if (TAREAS_QUE_RECARGAN_FORM.indexOf(codigo) !== -1) {
                        location.reload();
                    } else {
                        // Refresh parcial: tabla de pendientes + bienesMem.
                        // No se pierde scroll ni hay flash de recarga.
                        var id_sin = $('#id_siniestro').val();
                        if (id_sin) {
                            cargarPendientes(id_sin);
                            cargarBienesAfectados(id_sin);
                        }
                    }
                }
            } else {
                alert('No se pudo: ' + (resp && resp.mensaje ? resp.mensaje : 'error'));
            }
        }).fail(function() {
            alert('Error de red al guardar.');
        });
}

// Cuando el modal de "Notificar liquidador" se cierre (por cancelar o tras
// enviar el correo), recargar para reflejar los cambios en el form.
$(document).on('hidden.bs.modal', '#modalNotificarLiquidador', function() {
    location.reload();
});

function guardarPendiente() {
    var id_siniestro = $('#id_siniestro').val();
    if (!id_siniestro) { alert('Guarde el siniestro primero.'); return; }
    var id = $('#pend_id').val();
    var descripcion = $.trim($('#pend_descripcion').val());
    if (!descripcion) { alert('La descripción es obligatoria.'); return; }
    var data = {
        id_siniestro: id_siniestro,
        id_bien: $('#pend_id_bien').val(),
        responsable: $('#pend_responsable').val(),
        descripcion: descripcion,
        fecha_entrega: $('#pend_fecha_entrega').val(),
        notas: $('#pend_notas').val()
    };
    var url, accionMsg;
    if (id) {
        data.id = id;
        data.estado = $('#pend_estado').val();
        url = '/bambooQA/backend/siniestros/actualiza_pendiente.php';
        accionMsg = 'actualizar';
    } else {
        url = '/bambooQA/backend/siniestros/crea_pendiente.php';
        accionMsg = 'crear';
    }
    $.post(url, data, null, 'json').done(function(resp) {
        if (resp && resp.ok) {
            $('#modalPendiente').modal('hide');
            if (resp.cliente_completo && resp.liquidador && resp.liquidador.correo) {
                liquidadorContacto = resp.liquidador;
                $('#notif_liq_nombre').text(resp.liquidador.nombre || '(sin nombre)');
                $('#notif_liq_correo').text(resp.liquidador.correo);
                $('#modalNotificarLiquidador').modal('show');
            }
            cargarPendientes(id_siniestro);
        } else {
            alert('No se pudo ' + accionMsg + ': ' + (resp && resp.mensaje ? resp.mensaje : 'error'));
        }
    }).fail(function() {
        alert('Error de red al ' + accionMsg + ' el pendiente.');
    });
}

function eliminarPendiente(id) {
    if (!confirm('¿Eliminar este pendiente?')) return;
    $.post('/bambooQA/backend/siniestros/elimina_pendiente.php', { id: id }, null, 'json').done(function(resp) {
        if (resp && resp.ok) {
            cargarPendientes($('#id_siniestro').val());
        } else {
            alert('No se pudo eliminar: ' + (resp && resp.mensaje ? resp.mensaje : 'error'));
        }
    });
}

function enviarRecordatorio(id_pendiente) {
    var p = pendientesMem.find(function(x){ return x.id == id_pendiente; });
    if (!p) return;
    if (!confirm('¿Enviar un recordatorio amigable al responsable (' + p.responsable + ')?')) return;
    $.post('/bambooQA/backend/siniestros/enviar_recordatorio.php',
        { id_pendiente: id_pendiente }, null, 'json')
      .done(function(resp) {
        if (resp && resp.ok) {
            if (resp.enviado_por === 'brevo') {
                alert('Recordatorio enviado por correo.');
            } else if (resp.mailto_url) {
                window.location.href = resp.mailto_url;
            } else {
                alert('Recordatorio registrado.');
            }
            cargarPendientes($('#id_siniestro').val());
        } else {
            alert('No se pudo enviar el recordatorio: ' + (resp && resp.mensaje ? resp.mensaje : 'error'));
        }
      }).fail(function() {
        alert('Error de red al enviar el recordatorio.');
      });
}

function esRamoVehiculoJS(ramo) {
    var r = (ramo || '').toUpperCase();
    return r.indexOf('VEH') !== -1 || r.indexOf('AUTO') !== -1;
}

function construirCorreoLiquidador(L) {
    var nsin = L.numero_siniestro || '(sin N° de siniestro)';
    var asegurado = L.nombre_asegurado || '';
    var carpeta = L.numero_carpeta_liquidador ? (' — Carpeta ' + L.numero_carpeta_liquidador) : '';
    var subject = 'Siniestro N° ' + nsin + carpeta + ' — ' + asegurado;
    var body;
    if (esRamoVehiculoJS(L.ramo)) {
        body = 'Estimado/a ' + (L.nombre || 'liquidador') + ',\n\n' +
               'Se le informa que el vehículo del asegurado ' + asegurado +
               ' (siniestro ' + nsin + ') asistió a revisión en el taller designado.\n\n' +
               'Por favor proceder con la orden de reparación.\n\n' +
               'Saludos cordiales,\nAdriana';
    } else {
        body = 'Estimado/a ' + (L.nombre || 'liquidador') + ',\n\n' +
               'Le informo que el asegurado ' + asegurado +
               ' (siniestro ' + nsin + ') ya entregó todos los documentos pendientes a su cargo.\n\n' +
               'Agradeceré proceder con la generación del finiquito.\n\n' +
               'Saludos cordiales,\nAdriana';
    }
    return { subject: subject, body: body };
}

function abrirMailtoLiquidador(L, asunto, cuerpo) {
    window.location.href = 'mailto:' + encodeURIComponent(L.correo) +
                           '?subject=' + encodeURIComponent(asunto) +
                           '&body=' + encodeURIComponent(cuerpo);
}

function enviarCorreoLiquidador() {
    var L = liquidadorContacto || {};
    if (!L.correo) { $('#modalNotificarLiquidador').modal('hide'); return; }
    var id_siniestro = $('#id_siniestro').val();
    if (!id_siniestro) {
        // Modo creación: fallback directo a mailto.
        var m = construirCorreoLiquidador(L);
        abrirMailtoLiquidador(L, m.subject, m.body);
        $('#modalNotificarLiquidador').modal('hide');
        return;
    }
    $.post('/bambooQA/backend/siniestros/notifica_liquidador.php',
           { id_siniestro: id_siniestro }, null, 'json')
     .done(function(resp) {
        $('#modalNotificarLiquidador').modal('hide');
        if (resp && resp.ok) {
            alert('✉️ Correo enviado al liquidador (' + resp.destinatario + ').');
        } else if (resp && resp.proveedor === 'no_configurado' && resp.asunto) {
            // Fallback a mailto con el contenido que ya armó el backend.
            abrirMailtoLiquidador(L, resp.asunto, resp.cuerpo);
        } else {
            alert('No se pudo enviar el correo: ' + (resp && resp.mensaje ? resp.mensaje : 'error') +
                  '\n\nSe abrirá el cliente de correo como alternativa.');
            var m = construirCorreoLiquidador(L);
            abrirMailtoLiquidador(L, m.subject, m.body);
        }
     })
     .fail(function() {
        $('#modalNotificarLiquidador').modal('hide');
        var m = construirCorreoLiquidador(L);
        abrirMailtoLiquidador(L, m.subject, m.body);
     });
}
</script>

<!-- ==================== MODAL BIEN AFECTADO (rediseñado con tabs) ==================== -->
<div class="modal fade" id="modalBien" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalBienTitle">Bien afectado</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="bien_id">
        <input type="hidden" id="bien_memkey">
        <input type="hidden" id="bien_tipo">
        <input type="hidden" id="bien_estado_original">

        <ul class="nav nav-tabs" role="tablist">
          <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-bien-desc" role="tab">Descripción</a></li>
          <li class="nav-item" id="li_tab_taller"><a class="nav-link" data-toggle="tab" href="#tab-bien-taller" role="tab">Taller</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-bien-seg" role="tab">Seguimiento</a></li>
        </ul>

        <div class="tab-content pt-3">
          <!-- TAB 1: Descripción del bien -->
          <div class="tab-pane fade show active" id="tab-bien-desc" role="tabpanel">
            <div class="form-row">
              <div class="col-md-4 form-group">
                <label>Categoría <span style="color:darkred">*</span></label>
                <select class="form-control" id="bien_categoria" onchange="toggleCamposVehiculoBien()">
                  <option value="vehiculo">Vehículo</option>
                  <option value="inmueble">Inmueble</option>
                  <option value="persona">Persona</option>
                  <option value="otro">Otro</option>
                </select>
              </div>
              <div class="col-md-8 form-group">
                <label>Descripción <span style="color:darkred">*</span></label>
                <textarea class="form-control" id="bien_descripcion" rows="2" placeholder="Ej: Dpto 304, Pasillo 2° piso, Auto del Sr. Pérez…"></textarea>
              </div>
            </div>
            <div class="form-row">
              <div class="col-md-6 form-group" id="bien_campo_direccion_wrap">
                <label>Dirección del bien <small class="text-muted">(incendio / inmueble)</small></label>
                <input type="text" class="form-control" id="bien_direccion"
                  placeholder="Calle, número, comuna">
              </div>
              <div class="col-md-6 form-group" id="bien_campo_item_afectado_wrap">
                <label>Ítem afectado de la póliza <small class="text-muted">(para vehículos o por ítem)</small></label>
                <input type="text" class="form-control" id="bien_item_afectado"
                  placeholder="Ej: Ítem 2 — Camioneta Hilux PPU ABCD01">
              </div>
            </div>
            <div id="bien_campos_vehiculo">
              <div class="form-row">
                <div class="col-md-3 form-group">
                  <label>Patente</label>
                  <input type="text" class="form-control" id="bien_patente" maxlength="8" placeholder="XXXX00">
                </div>
                <div class="col-md-3 form-group">
                  <label>Marca</label>
                  <input type="text" class="form-control" id="bien_marca">
                </div>
                <div class="col-md-4 form-group">
                  <label>Modelo</label>
                  <input type="text" class="form-control" id="bien_modelo">
                </div>
                <div class="col-md-2 form-group">
                  <label>Año</label>
                  <input type="number" class="form-control" id="bien_anio" min="1990" max="2030">
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 2: Taller -->
          <div class="tab-pane fade" id="tab-bien-taller" role="tabpanel">
            <div id="bien_msg_no_taller" class="alert alert-info" style="display:none">
              Los datos del taller solo aplican cuando la categoría del bien es <strong>Vehículo</strong>.
            </div>
            <div id="bien_msg_taller_bloqueado" class="alert alert-warning" style="display:none">
              ⏳ Los datos del taller se habilitan cuando el liquidador haya sido asignado y emita la orden de reparación.
            </div>
            <div id="bien_campos_taller">
              <div class="form-row">
                <div class="col-md-4 form-group">
                  <label>Nombre</label>
                  <input type="text" class="form-control" id="bien_taller_nombre">
                </div>
                <div class="col-md-4 form-group">
                  <label>Teléfono</label>
                  <input type="text" class="form-control" id="bien_taller_telefono" placeholder="56 9 XXXX XXXX">
                </div>
                <div class="col-md-4 form-group">
                  <label>Correo</label>
                  <input type="email" class="form-control" id="bien_taller_correo">
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 3: Seguimiento -->
          <div class="tab-pane fade" id="tab-bien-seg" role="tabpanel">
            <div class="form-row">
              <div class="col-md-6 form-group">
                <label>Estado</label>
                <select class="form-control" id="bien_estado">
                  <option value="Abierto">Abierto</option>
                  <option value="Cerrado">Cerrado</option>
                  <option value="Rechazado">Rechazado</option>
                </select>
              </div>
              <div class="col-md-6 form-group">
                <label>Fecha alarma / próxima revisión</label>
                <input type="date" class="form-control" id="bien_fecha_alarma">
              </div>
            </div>
            <div class="form-group">
              <label>Observaciones</label>
              <textarea class="form-control" id="bien_observaciones" rows="3"></textarea>
            </div>
            <div class="form-group" id="bien_motivo_wrap" style="display:none">
              <label>Motivo del cambio de estado</label>
              <input type="text" class="form-control" id="bien_motivo">
            </div>
          </div>

        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="guardarBien()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- =============================================================== -->
<!-- MODAL PENDIENTE -->
<!-- =============================================================== -->
<div class="modal fade" id="modalPendiente" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalPendienteTitle">Pendiente</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="pend_id">
        <div class="form-group">
          <label>¿Quién la lleva? <span style="color:darkred">*</span></label>
          <select class="form-control" id="pend_responsable">
            <option value="Cliente">Cliente</option>
            <option value="Liquidador">Liquidador</option>
            <option value="Compañía">Compañía</option>
            <option value="Taller">Taller</option>
          </select>
        </div>
        <div class="form-group">
          <label>Descripción del pendiente <span style="color:darkred">*</span></label>
          <textarea class="form-control" id="pend_descripcion" rows="2"
            placeholder="Ej: Recepción municipal del edificio, Finiquito firmado, Fecha de pago…"></textarea>
        </div>
        <div class="form-group">
          <label>Bien asociado (opcional)</label>
          <select class="form-control" id="pend_id_bien">
            <option value="">— Siniestro en general —</option>
          </select>
        </div>
        <div class="form-group">
          <label>Notas</label>
          <textarea class="form-control" id="pend_notas" rows="2"></textarea>
        </div>
        <!-- Solo visible en modo edición -->
        <div id="pend_edicion_extras" style="display:none">
          <hr>
          <div class="form-row">
            <div class="col-md-6 form-group">
              <label>Estado</label>
              <select class="form-control" id="pend_estado">
                <option value="Pendiente">Pendiente</option>
                <option value="Entregado">Entregado</option>
                <option value="No aplica">No aplica</option>
              </select>
            </div>
            <div class="col-md-6 form-group">
              <label>Fecha entrega</label>
              <input type="date" class="form-control" id="pend_fecha_entrega">
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="guardarPendiente()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- =============================================================== -->
<!-- MODAL NOTIFICAR LIQUIDADOR -->
<!-- =============================================================== -->
<div class="modal fade" id="modalNotificarLiquidador" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">✉️ Notificar al liquidador</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <p>El cliente ya entregó todos sus pendientes.</p>
        <p class="mb-0">¿Desea generar un correo al liquidador <strong id="notif_liq_nombre"></strong>
          (<span id="notif_liq_correo"></span>) para avanzar con el finiquito?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Más tarde</button>
        <button type="button" class="btn btn-primary" onclick="enviarCorreoLiquidador()">✉️ Abrir correo</button>
      </div>
    </div>
  </div>
</div>

<!-- =============================================================== -->
<!-- MODAL RESOLVER PENDIENTE (marcar Entregado con captura)        -->
<!-- =============================================================== -->
<div class="modal fade" id="modalResolverPendiente" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">✅ Marcar como Entregado</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="resolver_pend_id">
        <input type="hidden" id="resolver_codigo_tarea">
        <div id="resolver_descripcion" class="mb-3"></div>
        <div id="resolver_body"></div>
        <div class="form-group mt-3">
          <label>Notas <small class="text-muted">(opcional)</small></label>
          <textarea class="form-control" id="resolver_notas" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" onclick="guardarResolverPendiente()">Marcar Entregado</button>
      </div>
    </div>
  </div>
</div>

</body>
</html>
