<?php
/* partials/header.php — nav sticky + drawer móvil. Usa $SITE, $SEGUROS, $active. */
$navlinks = [
    'servicios' => ['#servicios', 'Seguros'],
    'como'      => ['/como-operamos', 'Cómo trabajamos'],
    'nosotros'  => ['/nosotros', 'Nosotros'],
    'faq'       => ['/faq', 'Preguntas'],
    'contacto'  => ['/contacto', 'Contacto'],
];
?>
<header class="site-header" id="siteHeader">
  <div class="container">
    <nav class="nav" aria-label="Principal">
      <a href="/" class="nav__brand" aria-label="Bamboo Seguros — Inicio">
        <img src="/assets/img/logo.png" alt="Bamboo Seguros" width="74" height="60">
      </a>

      <ul class="nav__links">
        <li class="nav__has-menu">
          <a href="/#servicios"<?= $active === 'servicios' ? ' aria-current="page"' : '' ?>>Seguros</a>
          <div class="nav__panel">
            <?php foreach ($SEGUROS as $svc): ?>
              <a href="/seguros/<?= e($svc['slug']) ?>">
                <?= bb_icon($svc['icon']) ?><span><?= e($svc['menu']) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </li>
        <li><a href="/como-operamos"<?= $active === 'como' ? ' aria-current="page"' : '' ?>>Cómo trabajamos</a></li>
        <li><a href="/nosotros"<?= $active === 'nosotros' ? ' aria-current="page"' : '' ?>>Nosotros</a></li>
        <li><a href="/faq"<?= $active === 'faq' ? ' aria-current="page"' : '' ?>>Preguntas</a></li>
        <li><a href="/contacto"<?= $active === 'contacto' ? ' aria-current="page"' : '' ?>>Contacto</a></li>
      </ul>

      <div class="nav__cta">
        <a href="<?= e($SITE['portal_login_url']) ?>" class="nav__login" title="Acceso a la web interna">
          <?= bb_icon('login') ?><span>Ingresar</span>
        </a>
        <button type="button" class="btn btn--primary" data-cotizar>Cotizar gratis</button>
        <button type="button" class="nav__toggle" data-drawer-open aria-label="Abrir menú" aria-expanded="false">
          <?= bb_icon('menu') ?>
        </button>
      </div>
    </nav>
  </div>
</header>

<!-- Drawer móvil -->
<div class="mobile-drawer" id="drawer" aria-hidden="true">
  <div class="mobile-drawer__bd" data-drawer-close></div>
  <div class="mobile-drawer__panel" role="dialog" aria-modal="true" aria-label="Menú">
    <div class="mobile-drawer__top">
      <img src="/assets/img/logo.png" alt="Bamboo Seguros" width="49" height="40">
      <button type="button" class="modal__close" data-drawer-close aria-label="Cerrar menú"><?= bb_icon('close') ?></button>
    </div>
    <nav aria-label="Menú móvil">
      <a href="/#servicios" data-drawer-close>Seguros</a>
      <a href="/como-operamos" data-drawer-close>Cómo trabajamos</a>
      <a href="/nosotros" data-drawer-close>Nosotros</a>
      <a href="/faq" data-drawer-close>Preguntas frecuentes</a>
      <a href="/contacto" data-drawer-close>Contacto</a>
    </nav>
    <button type="button" class="btn btn--primary" data-cotizar data-drawer-close>Cotizar gratis</button>
    <a href="<?= e(wa_link()) ?>" class="btn btn--whatsapp" style="margin-top:12px" target="_blank" rel="noopener">
      <?= bb_icon('whatsapp') ?> Escribir por WhatsApp
    </a>
    <a href="<?= e($SITE['portal_login_url']) ?>" class="drawer-login"><?= bb_icon('login') ?> Ingresar a la web interna</a>
  </div>
</div>
