<?php
require_once __DIR__ . '/data/config.php';

$faqs = [
  ['q' => '¿Qué es un corredor de seguros?', 'a' => 'Un corredor de seguros es un intermediario independiente que asesora al cliente y gestiona la contratación de pólizas con las compañías aseguradoras. En Chile debe estar inscrito en el Registro de Corredores de la CMF. A diferencia de un agente, no representa a una sola aseguradora, sino que compara opciones de varias.'],
  ['q' => '¿Cuánto cuesta contratar un corredor de seguros en Chile?', 'a' => 'Para el cliente, contratar un corredor de seguros no tiene costo adicional. La comisión del corredor la paga la compañía de seguros, no el asegurado.'],
  ['q' => '¿Conviene contratar un seguro con corredor o directamente con la aseguradora?', 'a' => 'Contratar con un corredor conviene cuando se busca asesoría imparcial: el corredor es independiente y compara coberturas y precios de varias compañías (Bamboo trabaja con las principales del mercado), mientras que un canal directo solo ofrece los productos de una. Además, el corredor te acompaña en caso de siniestro.'],
  ['q' => '¿Cómo cotizar un seguro de auto en Chile?', 'a' => 'Para cotizar un seguro de auto: 1) reúne los datos del vehículo (marca, modelo, año, patente) y del propietario (RUT, comuna); 2) indica el uso (particular o comercial); 3) pide a tu corredor que compare coberturas y deducibles entre aseguradoras; 4) revisa prima, deducible y exclusiones antes de contratar. Con un corredor recibes varias cotizaciones con una sola solicitud.'],
  ['q' => '¿Qué seguros puedo contratar con Bamboo Seguros?', 'a' => 'Para personas y pymes: seguro de vehículo, vida, complementario de salud (individual y empresas), viaje, accidentes personales, incendio y sismo, responsabilidad civil, garantía, transporte, hogar, APV e ingeniería, entre otros. Como corredora independiente, los cotizamos con las principales aseguradoras del mercado chileno.'],
  ['q' => '¿Cuáles son las obligaciones de un corredor de seguros?', 'a' => 'Según la CMF, el corredor debe: asesorar a quien desea asegurarse, ofrecer la cobertura más conveniente a sus necesidades, informar las condiciones del contrato y sus modificaciones, asistir al cliente durante la vigencia de la póliza y al momento de un siniestro, y remitir los documentos a la compañía.'],
];

$page = [
  'title' => 'Preguntas Frecuentes sobre Seguros y Corredores | Bamboo Seguros',
  'desc' => '¿Cuánto cuesta un corredor? ¿Conviene corredor o directo? ¿Qué seguros puedo contratar? Resolvemos las dudas más comunes sobre seguros y corredoras en Chile.',
  'canonical' => '/faq',
  'active' => 'faq',
  'schema' => [[
    '@context' => 'https://schema.org', '@type' => 'FAQPage',
    'mainEntity' => array_map(fn($f) => [
      '@type' => 'Question', 'name' => $f['q'],
      'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
    ], $faqs),
  ]],
];
require __DIR__ . '/partials/head.php';
?>

<section class="page-hero">
  <div class="container container--narrow">
    <nav class="crumbs reveal" aria-label="Ruta"><a href="/">Inicio</a><span class="sep">/</span><span>Preguntas frecuentes</span></nav>
    <span class="eyebrow reveal">Preguntas frecuentes</span>
    <h1 class="reveal" data-d="1">Todo lo que quieres saber sobre seguros y corredores</h1>
    <p class="lead reveal" data-d="2">Respuestas claras a las dudas más comunes. Si te queda alguna, escríbenos por WhatsApp y te ayudamos.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="faq reveal">
      <?php foreach ($faqs as $f): ?>
        <details class="faq__item">
          <summary class="faq__q"><?= e($f['q']) ?><span class="pm" aria-hidden="true"></span></summary>
          <div class="faq__a"><?= e($f['a']) ?></div>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section cta-final">
  <div class="container container--narrow reveal">
    <h2>¿Te quedó alguna duda?</h2>
    <p>Conversemos. Te respondemos el mismo día hábil.</p>
    <div class="cta-final__btns">
      <button type="button" class="btn btn--on-dark btn--lg" data-cotizar><?= bb_icon('chat') ?> Cotizar</button>
      <a href="<?= e(wa_link()) ?>" class="btn btn--whatsapp btn--lg" target="_blank" rel="noopener"><?= bb_icon('whatsapp') ?> Escríbenos por WhatsApp</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
