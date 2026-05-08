<?php
/* Tabs internas del módulo Siniestros — incluido por las 3 páginas:
   listado_siniestros.php, seguimiento_bienes_afectados.php, admin_catalogo_documentos.php.
   Cada página setea $tab_siniestros = 'lista' | 'bienes' | 'catalogo'. */
$tab_siniestros = $tab_siniestros ?? 'lista';
?>
<ul class="nav nav-tabs mb-4" role="tablist">
  <li class="nav-item">
    <a class="nav-link <?= $tab_siniestros==='lista' ? 'active' : '' ?>" href="/bamboo/listado_siniestros.php">
      <i class="fas fa-list mr-2"></i>Listado
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tab_siniestros==='bienes' ? 'active' : '' ?>" href="/bamboo/seguimiento_bienes_afectados.php">
      <i class="fas fa-cube mr-2"></i>Bienes afectados
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tab_siniestros==='catalogo' ? 'active' : '' ?>" href="/bamboo/admin_catalogo_documentos.php">
      <i class="fas fa-folder-open mr-2"></i>Catálogo de documentos
    </a>
  </li>
</ul>
