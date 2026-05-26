<?php
/* Hub del módulo Endosos — 3 tarjetas con contadores en vivo. */

if (!isset($_SESSION)) { session_start(); }
require_once "/home/gestio10/public_html/backend/config.php";
db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

function bb_count_endosos($link, $sql) {
    $r = db_query($link, $sql);
    if (!$r) return null;
    while ($row = db_fetch_object($r)) { return (int)$row->n; }
    return 0;
}

$count_endosos    = bb_count_endosos($link, "SELECT COUNT(*) AS n FROM endosos");
$count_propuestas = bb_count_endosos($link, "SELECT COUNT(*) AS n FROM propuesta_endosos");
db_close($link);

$page_title      = 'Endosos · Bamboo Seguros';
$page_active     = 'endosos';
$breadcrumb_main = 'Endosos';
$breadcrumb_sub  = 'Plataforma';
require_once 'layout.php';
?>

<div class="bb-page-header">
  <div>
    <h1>Endosos</h1>
    <div class="subtitle">Propuestas y emisión de endosos sobre pólizas vigentes</div>
  </div>
</div>

<div class="bb-hub-grid">

  <a href="creacion_propuesta_endoso.php" class="bb-hub-card">
    <div class="bb-hub-card-icon"><i class="fas fa-file-signature"></i></div>
    <div class="bb-hub-card-body">
      <h3>Nueva propuesta de endoso</h3>
      <p class="desc">Crear una propuesta de endoso vía formulario manual o web.</p>
      <div class="bb-hub-card-meta">
        <span>Crear</span><span class="arrow">→</span>
      </div>
    </div>
  </a>

  <a href="listado_propuesta_endosos.php" class="bb-hub-card">
    <div class="bb-hub-card-icon"><i class="fas fa-file-alt"></i></div>
    <div class="bb-hub-card-body">
      <h3>Propuestas de endoso</h3>
      <p class="desc">Propuestas en curso, pendientes de aprobación o emisión.</p>
      <div class="bb-hub-card-meta">
        <span class="count"><?= $count_propuestas ?? '—' ?> registradas</span>
        <span class="arrow">→</span>
      </div>
    </div>
  </a>

  <a href="listado_endosos.php" class="bb-hub-card">
    <div class="bb-hub-card-icon"><i class="fas fa-file-contract"></i></div>
    <div class="bb-hub-card-body">
      <h3>Endosos</h3>
      <p class="desc">Endosos emitidos sobre pólizas vigentes — historial completo.</p>
      <div class="bb-hub-card-meta">
        <span class="count"><?= $count_endosos ?? '—' ?> emitidos</span>
        <span class="arrow">→</span>
      </div>
    </div>
  </a>

</div>

<?php require_once 'layout_end.php'; ?>
