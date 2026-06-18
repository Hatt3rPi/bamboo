<?php
require_once __DIR__ . '/data/config.php';
require_once __DIR__ . '/data/seguros.php';
$page = [
  'title' => 'Seguros para Pymes y Empresas en Chile | Bamboo Seguros',
  'desc' => 'Seguros para pymes: responsabilidad civil, incendio, transporte, garantía e ingeniería. Una corredora independiente que compara por ti las aseguradoras del mercado, sin costo. Cotiza.',
  'canonical' => '/seguros-pymes',
  'active' => 'servicios',
  'schema' => [[
    '@context' => 'https://schema.org', '@type' => 'CollectionPage',
    'name' => 'Seguros para Pymes y Empresas', 'url' => $SITE['url'] . '/seguros-pymes',
  ]],
];
require __DIR__ . '/partials/head.php';
?>

<section class="page-hero">
  <div class="container">
    <nav class="crumbs reveal" aria-label="Ruta"><a href="/">Inicio</a><span class="sep">/</span><span>Seguros para Pymes</span></nav>
    <span class="eyebrow reveal"><?= bb_icon('briefcase') ?> Empresas y Pymes</span>
    <h1 class="reveal" data-d="1">Protege tu empresa con quien conoce el mercado por dentro</h1>
    <p class="lead reveal" data-d="2">Responsabilidad civil, garantías para licitaciones, transporte de carga, ingeniería y más. Comparamos entre las aseguradoras del mercado y te asesoramos sin costo para que tu pyme opere tranquila.</p>
    <div class="hero__cta reveal" data-d="3" style="margin-top:28px">
      <button type="button" class="btn btn--primary btn--lg" data-cotizar><?= bb_icon('chat') ?> Cotizar para mi empresa</button>
      <a href="<?= e(wa_link('Hola Adriana, quiero cotizar un seguro para mi empresa o pyme.')) ?>" class="btn btn--ghost btn--lg" target="_blank" rel="noopener"><?= bb_icon('whatsapp') ?> WhatsApp</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head section-head--center reveal">
      <span class="eyebrow eyebrow--center">Coberturas para tu negocio</span>
      <h2>Seguros pensados para pymes y empresas</h2>
    </div>
    <div class="svc-grid">
      <?php foreach (seguros_por_segmento('pyme') as $s): ?>
        <a class="svc reveal" href="/seguros/<?= e($s['slug']) ?>">
          <div class="svc__ic"><?= bb_icon($s['icon']) ?></div>
          <h3><?= e($s['nombre']) ?></h3>
          <p><?= e($s['para_quien']) ?></p>
          <span class="svc__go">Cotizar <?= bb_icon('arrow') ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--cream">
  <div class="container">
    <div class="section-head section-head--center reveal">
      <span class="eyebrow eyebrow--center">Por qué Bamboo para tu pyme</span>
      <h2>Un aliado, no un proveedor más</h2>
    </div>
    <div class="cards">
      <article class="vcard reveal" data-d="1"><div class="vcard__ic"><?= bb_icon('compare') ?></div><h3>Comparamos el mercado</h3><p>Las aseguradoras del mercado compitiendo por tu empresa. Tú decides con todas las opciones sobre la mesa.</p></article>
      <article class="vcard reveal" data-d="2"><div class="vcard__ic"><?= bb_icon('document') ?></div><h3>Garantías ágiles</h3><p>Pólizas de garantía para licitaciones —seriedad de la oferta, fiel cumplimiento— más económicas que la boleta bancaria.</p></article>
      <article class="vcard reveal" data-d="3"><div class="vcard__ic"><?= bb_icon('handshake') ?></div><h3>Acompañamiento real</h3><p>Si tu empresa tiene un siniestro, gestionamos el caso contigo hasta su resolución. Sin costo de asesoría.</p></article>
    </div>
  </div>
</section>

<section class="section cta-final">
  <div class="container container--narrow reveal">
    <h2>Cotiza los seguros de tu empresa</h2>
    <p>Cuéntanos tu rubro y qué necesitas proteger. Te armamos una propuesta a la medida, sin compromiso.</p>
    <div class="cta-final__btns">
      <button type="button" class="btn btn--on-dark btn--lg" data-cotizar><?= bb_icon('chat') ?> Cotizar para mi empresa</button>
      <a href="<?= e(wa_link('Hola Adriana, quiero cotizar un seguro para mi empresa o pyme.')) ?>" class="btn btn--whatsapp btn--lg" target="_blank" rel="noopener"><?= bb_icon('whatsapp') ?> WhatsApp</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
