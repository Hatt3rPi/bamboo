<?php
require_once __DIR__ . '/data/config.php';

$person = [
  '@context' => 'https://schema.org',
  '@type' => 'Person',
  '@id' => $SITE['url'] . '/#adriana',
  'name' => $SITE['founder'],
  'jobTitle' => 'Corredora de Seguros',
  'worksFor' => ['@id' => $SITE['url'] . '/#organization'],
  'description' => 'Corredora de seguros con más de 30 años de experiencia en el mercado asegurador chileno, en áreas técnicas y comerciales de compañías de seguros y en corredoras.',
  'knowsLanguage' => 'es-CL',
];
if ($SITE['cmf_reg'] !== '') {
  $person['hasCredential'] = [
    '@type' => 'EducationalOccupationalCredential',
    'credentialCategory' => 'Inscripción en el Registro de Corredores de Seguros de la CMF',
    'identifier' => $SITE['cmf_reg'],
  ];
}

$page = [
  'title' => 'Quiénes Somos | Bamboo Seguros — Asesoría Independiente en Seguros',
  'desc' => 'Somos una corredora de seguros independiente regulada por la CMF, con más de 30 años de experiencia. Te asesoramos para que tomes una decisión informada, sin costo. Conócenos.',
  'canonical' => '/nosotros',
  'active' => 'nosotros',
  'schema' => [
    [
      '@context' => 'https://schema.org', '@type' => 'AboutPage',
      'name' => 'Quiénes Somos — Bamboo Seguros', 'url' => $SITE['url'] . '/nosotros',
      'mainEntity' => ['@id' => $SITE['url'] . '/#adriana'],
      'about' => ['@id' => $SITE['url'] . '/#organization'],
    ],
    $person,
  ],
];
require __DIR__ . '/partials/head.php';
?>

<section class="page-hero">
  <div class="container">
    <nav class="crumbs reveal" aria-label="Ruta"><a href="/">Inicio</a><span class="sep">/</span><span>Nosotros</span></nav>
    <span class="eyebrow reveal">Quiénes somos</span>
    <h1 class="reveal" data-d="1">Detrás de Bamboo hay una persona que te conoce</h1>
    <p class="lead reveal" data-d="2">Bamboo Seguros nace para que contratar un seguro deje de ser un trámite frío. Somos una corredora independiente: comparamos por ti, te explicamos en simple y te acompañamos hasta el final.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="about">
      <div class="about__photo reveal">
        <div class="ph">
          <img src="/assets/img/adriana.png" alt="Adriana Sandoval, corredora de seguros de Bamboo" width="512" height="512">
        </div>
        <div class="about__exp"><b>+30</b><span>años de experiencia</span></div>
      </div>
      <div class="about__copy reveal" data-d="1">
        <span class="eyebrow"><?= e($SITE['founder']) ?></span>
        <h2 style="font-size:var(--step-3);margin:10px 0 18px">Fundadora y corredora de seguros</h2>
        <p style="color:var(--fg-muted)">Más de 30 años en el mercado asegurador, en áreas técnicas y comerciales de compañías de seguros y en corredoras. Ese recorrido me da algo valioso para ti: conozco el negocio desde adentro de las aseguradoras y también desde el lado del corredor.</p>
        <p style="color:var(--fg-muted)">Eso significa que sé exactamente qué mirar en una póliza, dónde están las diferencias que importan y cómo conseguir la mejor cobertura al mejor precio para tu caso.</p>
        <div class="about__tags">
          <span class="tag"><?= bb_icon('check') ?> Áreas técnicas y comerciales</span>
          <span class="tag"><?= bb_icon('shield-check') ?> Inscrita en la CMF</span>
          <span class="tag"><?= bb_icon('handshake') ?> Atención personal</span>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section section--cream">
  <div class="container">
    <div class="section-head section-head--center reveal">
      <span class="eyebrow eyebrow--center">Nuestro compromiso</span>
      <h2>Cómo queremos cuidarte</h2>
    </div>
    <div class="cards">
      <article class="vcard reveal" data-d="1"><div class="vcard__ic"><?= bb_icon('chat') ?></div><h3>Claridad</h3><p>Respuestas claras y sencillas, en el menor tiempo posible, para abordar lo que de verdad necesitas.</p></article>
      <article class="vcard reveal" data-d="2"><div class="vcard__ic"><?= bb_icon('handshake') ?></div><h3>Cumplimiento</h3><p>Cumplimos los compromisos que asumimos contigo. Tu protección es nuestra responsabilidad y prioridad.</p></article>
      <article class="vcard reveal" data-d="3"><div class="vcard__ic"><?= bb_icon('heart') ?></div><h3>Cercanía</h3><p>Atención personalizada, de calidad y confianza. Hablas siempre con alguien que conoce tu historia.</p></article>
      <article class="vcard reveal" data-d="4"><div class="vcard__ic"><?= bb_icon('shield-check') ?></div><h3>Acompañamiento</h3><p>Asesoría permanente: en la contratación, en las renovaciones y, sobre todo, el día de un siniestro.</p></article>
    </div>
  </div>
</section>

<section class="section">
  <div class="container container--narrow reveal" style="text-align:center">
    <span class="eyebrow eyebrow--center">Nuestra diferencia</span>
    <p class="lead" style="margin-top:18px">La diferencia de Bamboo es la calidad, el asesoramiento, la cercanía y la transparencia. Con un fuerte conocimiento del mercado, buscamos una relación a largo plazo: que estés tranquilo hoy y que cuentes con nosotros mañana. Estamos enfocados en personas y pymes que quieren proteger su vida y su patrimonio.</p>
  </div>
</section>

<section class="section cta-final">
  <div class="container container--narrow reveal">
    <h2>Conversemos sobre lo que quieres proteger</h2>
    <p>Cuéntanos qué necesitas y te asesoramos con la experiencia de quien conoce el mercado por dentro.</p>
    <div class="cta-final__btns">
      <button type="button" class="btn btn--on-dark btn--lg" data-cotizar><?= bb_icon('chat') ?> Cotizar</button>
      <a href="<?= e(wa_link()) ?>" class="btn btn--whatsapp btn--lg" target="_blank" rel="noopener"><?= bb_icon('whatsapp') ?> Hablar por WhatsApp</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
