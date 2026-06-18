<?php
/* seguros/_template.php — plantilla de página de ramo. Cada archivo /seguros/<slug>.php
   define $SLUG y luego incluye esto. NO se sirve directo. */
require_once __DIR__ . '/../data/config.php';
require_once __DIR__ . '/../data/seguros.php';

$SLUG = $SLUG ?? '';
$s = $SEGUROS[$SLUG] ?? null;
if (!$s) { http_response_code(404); header('Location: /'); exit; }

$wa_msg = 'Hola Adriana, quiero cotizar un ' . $s['nombre'] . ' (vi tu web).';

/* Schema: Service + BreadcrumbList + FAQPage (si hay faqs) */
$schema = [
  [
    '@context' => 'https://schema.org',
    '@type' => 'Service',
    'name' => $s['nombre'],
    'serviceType' => $s['nombre'],
    'description' => $s['lead'],
    'areaServed' => ['@type' => 'Country', 'name' => 'Chile'],
    'provider' => ['@id' => $SITE['url'] . '/#organization'],
    'url' => $SITE['url'] . '/seguros/' . $s['slug'],
  ],
  [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
      ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => $SITE['url'] . '/'],
      ['@type' => 'ListItem', 'position' => 2, 'name' => 'Seguros', 'item' => $SITE['url'] . '/'],
      ['@type' => 'ListItem', 'position' => 3, 'name' => $s['nombre'], 'item' => $SITE['url'] . '/seguros/' . $s['slug']],
    ],
  ],
];
if (!empty($s['faqs'])) {
  $schema[] = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(fn($f) => [
      '@type' => 'Question', 'name' => $f['q'],
      'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
    ], $s['faqs']),
  ];
}

$page = [
  'title' => $s['meta_title'],
  'desc' => $s['meta_desc'],
  'canonical' => '/seguros/' . $s['slug'],
  'active' => 'servicios',
  'schema' => $schema,
];

require __DIR__ . '/../partials/head.php';
?>

<section class="page-hero">
  <div class="container">
    <nav class="crumbs reveal" aria-label="Ruta">
      <a href="/">Inicio</a><span class="sep">/</span>
      <a href="/#servicios">Seguros</a><span class="sep">/</span>
      <span><?= e($s['nombre']) ?></span>
    </nav>
    <div style="display:flex;align-items:center;gap:16px" class="reveal" data-d="1">
      <div class="svc__ic" style="width:56px;height:56px"><?= bb_icon($s['icon']) ?></div>
      <h1><?= e($s['h1']) ?></h1>
    </div>
    <p class="lead reveal" data-d="2"><?= e($s['lead']) ?></p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="svc-layout">
      <div class="prose reveal">
        <h2>¿Qué cubre?</h2>
        <ul class="cov">
          <?php foreach ($s['coberturas'] as $c): ?>
            <li><?= bb_icon('check-circle') ?> <span><?= e($c) ?></span></li>
          <?php endforeach; ?>
        </ul>

        <h2>¿Para quién es?</h2>
        <p><?= e($s['para_quien']) ?> Como corredora independiente, cotizamos este seguro con las aseguradoras del mercado chileno y te asesoramos sin costo para que elijas con toda la información.</p>

        <h3>¿Por qué cotizarlo con Bamboo?</h3>
        <p>Comparamos coberturas, deducibles y precios entre las principales compañías por ti. Te explicamos todo en simple y, si algún día tienes un siniestro, te acompañamos en el proceso hasta que quede resuelto. Y nuestra asesoría no te cuesta nada: solo le pagas la prima a la compañía.</p>

        <?php if (!empty($s['faqs'])): ?>
          <h2 style="margin-top:44px">Preguntas frecuentes</h2>
          <div class="faq" style="margin-top:8px">
            <?php foreach ($s['faqs'] as $f): ?>
              <details class="faq__item">
                <summary class="faq__q"><?= e($f['q']) ?><span class="pm" aria-hidden="true"></span></summary>
                <div class="faq__a"><?= e($f['a']) ?></div>
              </details>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <aside class="svc-aside reveal" data-d="1">
        <h3>Cotiza tu <?= e($s['menu']) ?></h3>
        <p>Sin costo y sin compromiso. Te respondemos el mismo día hábil.</p>
        <button type="button" class="btn btn--primary btn--block" data-cotizar data-slug="<?= e($s['slug']) ?>"><?= bb_icon('chat') ?> Cotizar</button>
        <a href="<?= e(wa_link($wa_msg)) ?>" class="btn btn--whatsapp btn--block" style="margin-top:12px" target="_blank" rel="noopener"><?= bb_icon('whatsapp') ?> Cotizar por WhatsApp</a>
        <p class="qf__micro" style="margin-top:16px"><?= bb_icon('shield-check') ?> <?= cmf_label() ?></p>
      </aside>
    </div>

    <!-- Otros seguros -->
    <div class="reveal" style="margin-top:clamp(48px,7vw,80px)">
      <h2 style="font-size:var(--step-2);margin-bottom:24px">Otros seguros que cotizamos</h2>
      <div class="svc-grid">
        <?php
        $otros = array_filter($SEGUROS, fn($x) => $x['slug'] !== $s['slug']);
        foreach (array_slice($otros, 0, 4) as $o): ?>
          <a class="svc" href="/seguros/<?= e($o['slug']) ?>">
            <div class="svc__ic"><?= bb_icon($o['icon']) ?></div>
            <h3><?= e($o['nombre']) ?></h3>
            <span class="svc__go">Ver <?= bb_icon('arrow') ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
