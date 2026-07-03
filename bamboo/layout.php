<?php
/* ============================================================
   BAMBOO SEGUROS · layout.php
   Reemplaza header2.php — abre el shell con sidebar + topbar.
   Cierra con layout_end.php al final de cada página.

   Variables esperadas (opcionales, se setean antes del include):
     $page_title       — título de la pestaña del navegador
     $page_active      — id del item activo del sidebar
                         ("inicio", "clientes", "polizas", "endosos",
                          "tareas", "siniestros", "correos")
     $breadcrumb_main  — texto principal del breadcrumb
     $breadcrumb_sub   — texto secundario
     $uf_valor / $dolar_valor / $fecha_hoy — datos del topbar
     $tareas_pendientes — int opcional para badge
   ============================================================ */

if (!isset($_SESSION)) { session_start(); }
require_once __DIR__ . '/_app_version.php';
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    setcookie('URI', $_SERVER['REQUEST_URI'], time() + 180, "/");
    setcookie('DOMINIO', $_SERVER['HTTP_HOST'], time() + 180, "/");
    header("location: /backend/login/login.php");
    exit;
}

$page_title       = $page_title       ?? 'Bamboo Seguros · Plataforma';
$page_active      = $page_active      ?? '';
$breadcrumb_main  = $breadcrumb_main  ?? '';
$breadcrumb_sub   = $breadcrumb_sub   ?? '';
$uf_valor         = $uf_valor         ?? '—';
$dolar_valor      = $dolar_valor      ?? '—';
$fecha_hoy        = $fecha_hoy        ?? date('d M Y');

$user_name        = $_SESSION['username'] ?? ($_SESSION['nombre'] ?? 'Usuario');
$user_initials    = strtoupper(mb_substr($user_name, 0, 2, 'UTF-8'));

function bb_nav_class($id, $active) {
    return 'nav-link' . ($id === $active ? ' active' : '');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($page_title) ?></title>
<script>window.BB_VERSION = <?= json_encode(bb_app_version()) ?>;</script>

<!-- Fuentes Bamboo -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Varela+Round&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

<!-- Bootstrap 4.6.2 (4.4.1 era incompatible con jQuery 3.5 — rompía collapse/modal) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

<!-- Font Awesome (kit existente) -->
<script src="https://kit.fontawesome.com/7011384382.js" crossorigin="anonymous"></script>

<!-- DataTables -->
<link rel="stylesheet" href="/assets/css/datatables.min.css">

<!-- Bamboo Design System -->
<link rel="stylesheet" href="/assets/css/bamboo/tokens.css">
<link rel="stylesheet" href="/assets/css/bamboo/components.css">
<link rel="stylesheet" href="/assets/css/bamboo/datatables-bamboo.css">

<link rel="icon" href="/bamboo/images/bamboo.png">
</head>
<body>

<div class="app-shell" id="appShell">

  <!-- ================ SIDEBAR ================ -->
  <aside class="bb-sidebar">
    <div class="brand">
      <div class="brand-mark"><img src="/bamboo/images/bamboo.png" alt="Bamboo"></div>
      <div>
        <div class="brand-text">Bamboo</div>
        <div class="brand-sub">Plataforma</div>
      </div>
    </div>

    <div class="nav-section"><span>Operación</span></div>
    <a href="/bamboo/index.php" class="<?= bb_nav_class('inicio', $page_active) ?>" aria-label="Inicio" data-tooltip="Inicio">
      <i class="fas fa-home"></i><span>Inicio</span>
    </a>
    <a href="/bamboo/listado_clientes.php" class="<?= bb_nav_class('clientes', $page_active) ?>" aria-label="Clientes" data-tooltip="Clientes">
      <i class="fas fa-users"></i><span>Clientes</span>
    </a>
    <a href="/bamboo/polizas.php" class="<?= bb_nav_class('polizas', $page_active) ?>" aria-label="Pólizas" data-tooltip="Pólizas">
      <i class="fas fa-file-contract"></i><span>Pólizas</span>
    </a>
    <a href="/bamboo/endosos.php" class="<?= bb_nav_class('endosos', $page_active) ?>" aria-label="Endosos" data-tooltip="Endosos">
      <i class="fas fa-file-signature"></i><span>Endosos</span>
    </a>

    <div class="nav-section"><span>Gestión</span></div>
    <a href="/bamboo/listado_tareas.php" class="<?= bb_nav_class('tareas', $page_active) ?>" aria-label="Tareas" data-tooltip="Tareas">
      <i class="fas fa-tasks"></i><span>Tareas</span>
      <?php if (!empty($tareas_pendientes)): ?>
        <span class="badge badge-warning"><?= (int)$tareas_pendientes ?></span>
      <?php endif; ?>
    </a>
    <a href="/bamboo/listado_siniestros.php" class="<?= bb_nav_class('siniestros', $page_active) ?>" aria-label="Siniestros" data-tooltip="Siniestros">
      <i class="fas fa-exclamation-triangle"></i><span>Siniestros</span>
    </a>
    <a href="/bamboo/admin_email_templates.php" class="<?= bb_nav_class('correos', $page_active) ?>" aria-label="Correos" data-tooltip="Correos">
      <i class="fas fa-envelope"></i><span>Correos</span>
    </a>

    <div class="bb-sidebar-footer">
      <i class="fas fa-code-branch"></i>
      <span>Versión<br><b><?= htmlspecialchars(bb_app_version_label()) ?></b></span>
    </div>
  </aside>

  <!-- ================ CONTENT COLUMN ================ -->
  <div>

    <!-- TOPBAR -->
    <header class="bb-topbar">
      <button type="button" class="toggle" onclick="document.getElementById('appShell').classList.toggle('collapsed');" aria-label="Toggle sidebar">
        <i class="fas fa-bars"></i>
      </button>
      <div class="breadcrumbs">
        <?php if ($breadcrumb_sub): ?>
          <?= htmlspecialchars($breadcrumb_sub) ?> <span class="text-subtle">·</span>
        <?php endif; ?>
        <b><?= htmlspecialchars($breadcrumb_main) ?></b>
      </div>
      <div class="meta">
        <div>UF<b id="bb-uf"><?= htmlspecialchars($uf_valor) ?></b></div>
        <div>USD<b id="bb-usd"><?= htmlspecialchars($dolar_valor) ?></b></div>
        <div id="bb-fecha"><?= htmlspecialchars($fecha_hoy) ?></div>
      </div>
      <div class="dropdown">
        <div class="user" data-toggle="dropdown" aria-expanded="false">
          <div class="avatar"><?= htmlspecialchars($user_initials) ?></div>
          <small><?= htmlspecialchars($user_name) ?></small>
          <i class="fas fa-chevron-down" style="font-size:10px;color:var(--fg-subtle)"></i>
        </div>
        <div class="dropdown-menu dropdown-menu-right">
          <a class="dropdown-item" href="/backend/login/logout.php"><i class="fas fa-sign-out-alt mr-2"></i>Cerrar sesión</a>
        </div>
      </div>
    </header>

    <!-- MAIN — el contenido de cada página va aquí -->
    <main class="bb-main">
