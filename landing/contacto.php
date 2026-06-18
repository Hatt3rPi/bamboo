<?php
require_once __DIR__ . '/data/config.php';
$page = [
  'title' => 'Contacto | Cotiza tu Seguro por WhatsApp o Teléfono — Bamboo Seguros',
  'desc' => 'Habla con Bamboo Seguros: cotiza por WhatsApp al ' . $SITE['phone_display'] . ' o escríbenos a ' . $SITE['email'] . '. Asesoría de seguros en todo Chile.',
  'canonical' => '/contacto',
  'active' => 'contacto',
  'schema' => [[
    '@context' => 'https://schema.org', '@type' => 'ContactPage',
    'name' => 'Contacto — Bamboo Seguros', 'url' => $SITE['url'] . '/contacto',
    'about' => ['@id' => $SITE['url'] . '/#organization'],
  ]],
];
require __DIR__ . '/partials/head.php';
?>

<section class="page-hero">
  <div class="container">
    <nav class="crumbs reveal" aria-label="Ruta"><a href="/">Inicio</a><span class="sep">/</span><span>Contacto</span></nav>
    <span class="eyebrow reveal">Contacto</span>
    <h1 class="reveal" data-d="1">Responderemos todas tus consultas</h1>
    <p class="lead reveal" data-d="2">Elige el canal que más te acomode. Te respondemos el mismo día hábil, sin compromiso.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cards">
      <a class="vcard reveal" data-d="1" href="<?= e(wa_link()) ?>" target="_blank" rel="noopener">
        <div class="vcard__ic" style="background:#e7f9ee;color:var(--wa-dark)"><?= bb_icon('whatsapp') ?></div>
        <h3>WhatsApp</h3><p>La vía más rápida. Escríbenos y cotiza rápidamente.</p>
        <span class="svc__go"><?= e($SITE['phone_display']) ?> <?= bb_icon('arrow') ?></span>
      </a>
      <a class="vcard reveal" data-d="2" href="tel:+<?= e($SITE['phone_e164']) ?>">
        <div class="vcard__ic"><?= bb_icon('phone') ?></div>
        <h3>Llámanos</h3><p>¿Prefieres conversarlo? Te atendemos con gusto.</p>
        <span class="svc__go"><?= e($SITE['phone_display']) ?> <?= bb_icon('arrow') ?></span>
      </a>
      <a class="vcard reveal" data-d="3" href="mailto:<?= e($SITE['email']) ?>">
        <div class="vcard__ic"><?= bb_icon('mail') ?></div>
        <h3>Correo</h3><p>Cuéntanos tu caso con detalle y te respondemos.</p>
        <span class="svc__go" style="word-break:break-all"><?= e($SITE['email']) ?></span>
      </a>
      <a href="#" role="button" class="vcard reveal" data-d="4" data-cotizar style="border:1.5px solid var(--bamboo-300);background:var(--bamboo-50)">
        <div class="vcard__ic" style="background:var(--bamboo-600);color:#fff"><?= bb_icon('chat') ?></div>
        <h3>Formulario de cotización</h3><p>Completa 3 pasos simples y armamos tu propuesta.</p>
        <span class="svc__go">Cotizar <?= bb_icon('arrow') ?></span>
      </a>
    </div>

    <div class="reveal" style="margin-top:40px;text-align:center;color:var(--fg-muted)">
      <p><?= bb_icon('shield-check', '') ?> <?= cmf_label() ?> · Atención en todo Chile<?= $SITE['comuna'] ? ' · ' . e($SITE['comuna']) . ', ' . e($SITE['region']) : '' ?></p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
