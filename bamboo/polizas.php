<?php
/* Hub del módulo Pólizas — 4 tarjetas con accesos a las acciones clave +
   contadores en vivo desde la BD. */

if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";
db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

function bb_count($link, $sql) {
    $r = db_query($link, $sql);
    if (!$r) return null;
    while ($row = db_fetch_object($r)) { return (int)$row->n; }
    return 0;
}

$count_polizas    = bb_count($link, "SELECT COUNT(*) AS n FROM polizas_2 WHERE estado NOT IN ('Cancelado', 'Anulado')");
$count_propuestas = bb_count($link, "SELECT COUNT(*) AS n FROM propuesta_polizas");
db_close($link);

$page_title      = 'Pólizas · Bamboo Seguros';
$page_active     = 'polizas';
$breadcrumb_main = 'Pólizas';
$breadcrumb_sub  = 'Plataforma';
require_once 'layout.php';
?>

<div class="bb-page-header">
  <div>
    <h1>Pólizas</h1>
    <div class="subtitle">Gestión de propuestas y emisión de pólizas</div>
  </div>
</div>

<div class="bb-hub-grid">

  <a href="creacion_propuesta_poliza.php" class="bb-hub-card">
    <div class="bb-hub-card-icon"><i class="fas fa-file-signature"></i></div>
    <div class="bb-hub-card-body">
      <h3>Nueva propuesta</h3>
      <p class="desc">Crear una propuesta de póliza desde cero (formulario tradicional).</p>
      <div class="bb-hub-card-meta">
        <span>Crear</span><span class="arrow">→</span>
      </div>
    </div>
  </a>

  <a href="listado_propuesta_polizas.php" class="bb-hub-card">
    <div class="bb-hub-card-icon"><i class="fas fa-file-alt"></i></div>
    <div class="bb-hub-card-body">
      <h3>Propuestas de póliza</h3>
      <p class="desc">Propuestas en curso, aprobadas y emitidas — historial completo.</p>
      <div class="bb-hub-card-meta">
        <span class="count"><?= $count_propuestas ?? '—' ?> registradas</span>
        <span class="arrow">→</span>
      </div>
    </div>
  </a>

  <a href="#" onclick="crear_poliza_web(); return false;" class="bb-hub-card is-warm">
    <div class="bb-hub-card-icon"><i class="fas fa-globe"></i></div>
    <div class="bb-hub-card-body">
      <h3>Póliza web</h3>
      <p class="desc">Modo agilizado para emitir una póliza ya cotizada en la web de la compañía.</p>
      <div class="bb-hub-card-meta">
        <span>Crear</span><span class="arrow">→</span>
      </div>
    </div>
  </a>

  <a href="listado_polizas.php" class="bb-hub-card">
    <div class="bb-hub-card-icon"><i class="fas fa-file-contract"></i></div>
    <div class="bb-hub-card-body">
      <h3>Pólizas</h3>
      <p class="desc">Cartera de pólizas vigentes, vencidas, canceladas y anuladas.</p>
      <div class="bb-hub-card-meta">
        <span class="count"><?= $count_polizas ?? '—' ?> vigentes</span>
        <span class="arrow">→</span>
      </div>
    </div>
  </a>

</div>

<?php require_once 'layout_end.php'; ?>
