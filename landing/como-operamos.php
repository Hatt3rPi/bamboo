<?php
require_once __DIR__ . '/data/config.php';
$page = [
  'title' => 'Cómo Operamos | Asesoría de Seguros Gratis y Personalizada — Bamboo',
  'desc' => 'Te escuchamos, comparamos las mejores compañías y contratas tu póliza. La asesoría de Bamboo es gratis: solo pagas la prima a la aseguradora. Descubre cómo trabajamos.',
  'canonical' => '/como-operamos',
  'active' => 'como',
  'schema' => [[
    '@context' => 'https://schema.org', '@type' => 'HowTo',
    'name' => 'Cómo cotizar y contratar un seguro con Bamboo',
    'step' => [
      ['@type' => 'HowToStep', 'position' => 1, 'name' => 'Cuéntanos qué necesitas', 'text' => 'Nos escribes por WhatsApp o nos llamas. Te hacemos unas pocas preguntas para entender qué quieres proteger.'],
      ['@type' => 'HowToStep', 'position' => 2, 'name' => 'Comparamos por ti', 'text' => 'Revisamos las opciones de más de 15 aseguradoras y armamos una propuesta clara con coberturas y precios.'],
      ['@type' => 'HowToStep', 'position' => 3, 'name' => 'Contratas y te acompañamos', 'text' => 'Eliges con toda la información. Gestionamos todo y quedamos a tu lado para renovaciones, dudas y siniestros.'],
    ],
  ]],
];
require __DIR__ . '/partials/head.php';
?>

<section class="page-hero">
  <div class="container">
    <nav class="crumbs reveal" aria-label="Ruta"><a href="/">Inicio</a><span class="sep">/</span><span>Cómo trabajamos</span></nav>
    <span class="eyebrow reveal">Cómo operamos</span>
    <h1 class="reveal" data-d="1">Te asesoramos para que tomes una decisión informada</h1>
    <p class="lead reveal" data-d="2">Como corredora, intermediamos entre tú —que necesitas proteger tu vida o tu patrimonio— y la compañía de seguros. Con confianza, claridad y experiencia.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="steps">
      <div class="step reveal" data-d="1"><div class="step__n"></div><div class="step__b"><h3>Cuéntanos qué necesitas</h3><p>Estás interesado en contratar un seguro. Nos escribes por WhatsApp o nos llamas, y te hacemos unas pocas preguntas para entender qué quieres proteger.</p></div></div>
      <div class="step reveal" data-d="2"><div class="step__n"></div><div class="step__b"><h3>Buscamos las mejores alternativas</h3><p>Comparamos coberturas, deducibles y precios entre más de 15 aseguradoras y te armamos una propuesta clara, explicada en simple.</p></div></div>
      <div class="step reveal" data-d="3"><div class="step__n"></div><div class="step__b"><h3>Contratas tu póliza y te acompañamos</h3><p>Eliges con toda la información sobre la mesa. Gestionamos todo y quedamos a tu lado para renovaciones, dudas y, sobre todo, el día de un siniestro.</p></div></div>
    </div>
  </div>
</section>

<section class="section band">
  <div class="container">
    <div class="band__inner reveal">
      <div class="band__badge"><?= bb_icon('shield-check') ?></div>
      <div>
        <span class="eyebrow" style="color:var(--earth-300)">Lo más importante</span>
        <h2>El cliente únicamente paga la prima de su seguro</h2>
        <p>Tú solo le pagas la prima a la compañía, igual que si contrataras directo. <strong>Son las compañías quienes comisionan a los corredores de seguros</strong>, no tú. Así tienes un experto de tu lado sin costo adicional.</p>
        <span class="band__seal"><?= bb_icon('shield-check') ?> <?= cmf_label() ?></span>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container container--narrow reveal" style="text-align:center">
    <span class="eyebrow eyebrow--center"><?= bb_icon('handshake') ?> Cuando ocurre un siniestro</span>
    <h2 style="margin-top:14px">No te dejamos solo frente a la compañía</h2>
    <p class="lead" style="margin-top:18px">En la eventualidad de un siniestro, tendrás a Bamboo cerca de ti: te orientamos y damos seguimiento al caso hasta que sea indemnizado, según corresponda. Esa es la verdadera diferencia de contar con una corredora que te conoce.</p>
    <div class="cta-final__btns" style="justify-content:center;margin-top:32px">
      <button type="button" class="btn btn--primary btn--lg" data-cotizar><?= bb_icon('chat') ?> Cotizar gratis</button>
      <a href="<?= e(wa_link()) ?>" class="btn btn--ghost btn--lg" target="_blank" rel="noopener"><?= bb_icon('whatsapp') ?> Hablar por WhatsApp</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
