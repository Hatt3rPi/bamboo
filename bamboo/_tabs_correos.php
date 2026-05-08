<?php
/* Tabs internas del módulo Correos — incluido por las 3 páginas:
   solicitar_info.php (default), creacion_template.php, admin_email_templates.php.
   Cada página setea $tab_correos = 'solicitar' | 'editor' | 'plantillas'. */
$tab_correos = $tab_correos ?? 'solicitar';
?>
<ul class="nav nav-tabs mb-4" role="tablist">
  <li class="nav-item">
    <a class="nav-link <?= $tab_correos==='solicitar' ? 'active' : '' ?>" href="/bamboo/solicitar_info.php">
      <i class="fas fa-paper-plane mr-2"></i>Envío de correos
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tab_correos==='editor' ? 'active' : '' ?>" href="/bamboo/creacion_template.php">
      <i class="fas fa-edit mr-2"></i>Editor de templates
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tab_correos==='plantillas' ? 'active' : '' ?>" href="/bamboo/admin_email_templates.php">
      <i class="fas fa-folder-open mr-2"></i>Plantillas Brevo
    </a>
  </li>
</ul>
