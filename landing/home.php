<?php
require_once __DIR__ . '/data/config.php';
require_once __DIR__ . '/data/seguros.php';

/* FAQ del home (también alimenta el schema FAQPage) */
$home_faqs = [
  ['q' => '¿Cuánto me cuesta la asesoría de Bamboo?', 'a' => 'Nada. Tú solo le pagas la prima a la compañía de seguros, igual que si contrataras directo. Nuestra comisión la paga la aseguradora, así que tienes un experto de tu lado sin gastar un peso de más.'],
  ['q' => '¿Es lo mismo contratar con Bamboo que directo con la aseguradora?', 'a' => 'El precio es el mismo, pero con Bamboo tienes asesoría independiente, comparación entre las compañías del mercado y acompañamiento si algún día tienes un siniestro. No representamos a una sola marca: trabajamos para ti.'],
  ['q' => '¿Con qué aseguradoras trabajan?', 'a' => 'Con las principales del país: Sura, BCI Seguros, Mapfre, Chubb, HDI, Consorcio, Reale, Renta Nacional, Southbridge y más. Comparamos entre todas para encontrar tu mejor opción.'],
  ['q' => '¿Cómo cotizo un seguro?', 'a' => 'Puedes escribirnos por WhatsApp o completar el formulario de cotización, te hacemos unas pocas preguntas y te enviamos una propuesta clara con coberturas y precios.'],
  ['q' => '¿Qué pasa si tengo un siniestro?', 'a' => 'Nos avisas a nosotros primero. Te guiamos en todo el proceso con la compañía —orientación y seguimiento del caso— hasta que quede resuelto. No te dejamos solo frente a un call center.'],
  ['q' => '¿Bamboo está regulada?', 'a' => 'Sí. Bamboo opera a través de Adriana Sandoval, corredora de seguros persona natural inscrita en la Comisión para el Mercado Financiero (CMF) de Chile (Código CMF N° 8404), requisito legal para ejercer y garantía de nuestras obligaciones de asesoría.'],
];

$page = [
  'title' => $SITE['default_title'],
  'desc'  => $SITE['default_desc'],
  'canonical' => '/',
  'active' => '',
  // FAQPage vive solo en /faq y en las páginas de ramo (evita solape de intent
  // entre home y /faq). El acordeón visual del home se mantiene sin schema.
  'schema' => [],
];

$insurers = ['sura','bci','mapfre','chubb','hdi','consorcio','reale','renta','southbridge','zurich','confuturo','unnio','colmena','augustar','coris'];
$insurer_names = ['sura'=>'Sura','bci'=>'BCI Seguros','mapfre'=>'Mapfre','chubb'=>'Chubb','hdi'=>'HDI','consorcio'=>'Consorcio','reale'=>'Reale','renta'=>'Renta Nacional','southbridge'=>'Southbridge','zurich'=>'Zurich','confuturo'=>'Confuturo','unnio'=>'Unnio','colmena'=>'Colmena','augustar'=>'Augustar','coris'=>'Coris'];
$ext = [];

require __DIR__ . '/partials/head.php';
?>

<!-- ============ HERO ============ -->
<section class="hero">
  <div class="container">
    <div class="hero__grid">
      <div class="hero__copy">
        <span class="eyebrow hero__eyebrow reveal"><?= bb_icon('leaf') ?> Corredora de seguros · Chile</span>
        <h1 class="reveal" data-d="1">El seguro correcto, explicado <span class="ink-underline">en simple</span> y elegido para ti</h1>
        <p class="hero__lead reveal" data-d="2">Comparamos por ti en el mercado para encontrar tu mejor opción. Asesoría personalizada, independiente y <strong>cercana</strong>.</p>
        <div class="hero__cta reveal" data-d="3">
          <button type="button" class="btn btn--primary btn--lg" data-cotizar><?= bb_icon('chat') ?> Cotiza tu seguro</button>
          <a href="<?= e(wa_link()) ?>" class="btn btn--ghost btn--lg" target="_blank" rel="noopener"><?= bb_icon('whatsapp') ?> Hablar con Adriana</a>
        </div>
        <ul class="hero__trust reveal" data-d="4">
          <li><?= bb_icon('check-circle') ?> Respuesta el mismo día</li>
          <li><?= bb_icon('check-circle') ?> Asesoría independiente</li>
          <li><?= bb_icon('shield-check') ?> Corredora inscrita en la CMF</li>
        </ul>
      </div>

      <div class="hero__visual reveal" data-d="2" aria-hidden="true">
        <div class="hero__blob"><?= bb_icon('leaf', 'hero__blob-leaf') ?></div>
        <?= bb_icon('leaf', 'hero__leaf hero__leaf--1') ?>
        <?= bb_icon('leaf', 'hero__leaf hero__leaf--2') ?>
        <div class="hero__card hero__card--1">
          <span class="ic"><?= bb_icon('compare') ?></span>
          <span><b>Comparamos</b><span>por ti</span></span>
        </div>
        <div class="hero__card hero__card--2">
          <span class="ic"><?= bb_icon('clock') ?></span>
          <span><b>Hoy</b><span>te respondemos</span></span>
        </div>
        <div class="hero__card hero__card--3">
          <span class="ic"><?= bb_icon('shield-check') ?></span>
          <span><b>CMF</b><span>corredora inscrita</span></span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ ASEGURADORAS ============ -->
<section class="insurers" aria-label="Compañías con las que trabajamos">
  <div class="container">
    <p class="insurers__label">Comparamos entre las mejores aseguradoras de Chile <b>para encontrar tu mejor opción</b></p>
  </div>
  <div class="marquee" aria-hidden="true">
    <div class="marquee__track">
      <?php foreach ($insurers as $key): $e = $ext[$key] ?? 'png'; ?>
        <img src="/assets/img/companies/<?= e($key) ?>.<?= e($e) ?>" alt="<?= e($insurer_names[$key] ?? ucfirst($key)) ?>" loading="lazy">
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ POR QUÉ BAMBOO ============ -->
<section class="section section--cream" id="por-que">
  <div class="container">
    <div class="section-head section-head--center reveal">
      <span class="eyebrow eyebrow--center">Por qué Bamboo</span>
      <h2>No somos una compañía de seguros.<br>Somos tu corredora.</h2>
      <p>Trabajamos para ti. Esa es toda la diferencia.</p>
    </div>
    <div class="cards">
      <article class="vcard reveal" data-d="1">
        <div class="vcard__ic"><?= bb_icon('compare') ?></div>
        <h3>Asesoría independiente</h3>
        <p>Comparamos por ti en el mercado y te recomendamos lo que de verdad te conviene. Te lo explicamos en simple.</p>
      </article>
      <article class="vcard reveal" data-d="2">
        <div class="vcard__ic"><?= bb_icon('shield-check') ?></div>
        <h3>Transparencia</h3>
        <p>Tú solo le pagas la prima a la compañía, igual que si contrataras directo; nunca le pagas a Bamboo. La comisión la paga la aseguradora.</p>
      </article>
      <article class="vcard reveal" data-d="3">
        <div class="vcard__ic"><?= bb_icon('handshake') ?></div>
        <h3>Te acompañamos en el siniestro</h3>
        <p>El día que algo pasa, nos llamas a nosotros —no a un call center—. Te guiamos en todo el proceso hasta que tu caso quede resuelto.</p>
      </article>
      <article class="vcard reveal" data-d="4">
        <div class="vcard__ic"><?= bb_icon('heart') ?></div>
        <h3>Una persona que te conoce</h3>
        <p>Atención humana de principio a fin. Siempre hablas con alguien que conoce tu historia y tus pólizas por tu nombre.</p>
      </article>
    </div>
  </div>
</section>

<!-- ============ CÓMO TRABAJAMOS ============ -->
<section class="section" id="como-trabajamos">
  <div class="container">
    <div class="section-head section-head--center reveal">
      <span class="eyebrow eyebrow--center">Cómo trabajamos</span>
      <h2>Cotizar es simple y conversado</h2>
      <p>Nos cuentas qué necesitas y nosotros hacemos el trabajo.</p>
    </div>
    <div class="steps">
      <div class="step reveal" data-d="1"><div class="step__n"></div><div class="step__b"><h3>Cuéntanos qué necesitas</h3><p>Escríbenos por WhatsApp o llámanos. Te hacemos unas pocas preguntas para entender qué quieres proteger.</p></div></div>
      <div class="step reveal" data-d="2"><div class="step__n"></div><div class="step__b"><h3>Comparamos por ti</h3><p>Revisamos las opciones del mercado y armamos una propuesta clara, con coberturas y precios explicados en simple.</p></div></div>
      <div class="step reveal" data-d="3"><div class="step__n"></div><div class="step__b"><h3>Contratas y te acompañamos</h3><p>Eliges con la información sobre la mesa. Nosotros gestionamos todo y quedamos a tu lado para renovaciones, dudas y siniestros.</p></div></div>
    </div>
    <div class="steps__cta reveal">
      <button type="button" class="btn btn--primary btn--lg" data-cotizar><?= bb_icon('chat') ?> Empezar ahora</button>
    </div>
  </div>
</section>

<!-- ============ SERVICIOS ============ -->
<section class="section section--sand" id="servicios">
  <div class="container">
    <div class="section-head section-head--center reveal">
      <span class="eyebrow eyebrow--center">Seguros</span>
      <h2>Seguros para cada etapa de tu vida y tu negocio</h2>
      <p>Elige lo que necesitas y cotiza rápidamente.</p>
    </div>
    <div class="reveal" style="text-align:center">
      <div class="segtabs" role="tablist" aria-label="Personas o Pymes" data-segtabs>
        <button class="segtab" role="tab" id="tab-persona" aria-controls="panel-persona" aria-selected="true"  tabindex="0"  data-target="persona">Para ti y tu familia</button>
        <button class="segtab" role="tab" id="tab-pyme"    aria-controls="panel-pyme"    aria-selected="false" tabindex="-1" data-target="pyme">Para tu Pyme o empresa</button>
      </div>
    </div>

    <?php foreach (['persona', 'pyme'] as $seg): ?>
      <div class="svc-grid reveal" data-segpanel="<?= $seg ?>" role="tabpanel" id="panel-<?= $seg ?>" aria-labelledby="tab-<?= $seg ?>" tabindex="0"<?= $seg === 'pyme' ? ' hidden' : '' ?> style="margin-top:8px">
        <?php foreach (seguros_por_segmento($seg) as $s): ?>
          <a class="svc" href="/seguros/<?= e($s['slug']) ?>">
            <div class="svc__ic"><?= bb_icon($s['icon']) ?></div>
            <h3><?= e($s['nombre']) ?></h3>
            <p><?= e($s['para_quien_' . $seg] ?? $s['para_quien']) ?></p>
            <span class="svc__go">Cotizar <?= bb_icon('arrow') ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Banda de costo de asesoría eliminada a pedido de Adriana (jun-2026). -->

<!-- ============ SOBRE ADRIANA ============ -->
<section class="section" id="nosotros-preview">
  <div class="container">
    <div class="about">
      <div class="about__photo reveal">
        <div class="ph">
          <img src="/assets/img/adriana.png" alt="Adriana Sandoval, corredora de seguros de Bamboo" width="512" height="512">
        </div>
        <div class="about__exp"><b>+30</b><span>años de experiencia</span></div>
      </div>
      <div class="about__copy reveal" data-d="1">
        <span class="eyebrow">Detrás de Bamboo</span>
        <blockquote>«Fundé Bamboo para que contratar un seguro deje de ser un trámite frío y complicado. Te explico todo en simple, comparo por ti en el mercado y, si algún día tienes un siniestro, me tienes a mí —no un call center— hasta el final.»</blockquote>
        <div class="about__sign"><?= e($SITE['founder']) ?><span><?= e($SITE['founder_role']) ?></span></div>
        <div class="about__tags">
          <span class="tag"><?= bb_icon('check') ?> Asesoría independiente</span>
          <span class="tag"><?= bb_icon('shield-check') ?> Corredora inscrita en la CMF</span>
          <span class="tag"><?= bb_icon('handshake') ?> Atención personal</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ TESTIMONIOS ============ -->
<!-- Testimonios reales de clientes (recopilados por Adriana, jun-2026). -->
<section class="section section--cream" id="testimonios">
  <div class="container">
    <div class="section-head section-head--center reveal">
      <span class="eyebrow eyebrow--center">Clientes</span>
      <h2>Lo que dicen quienes confían en Bamboo</h2>
    </div>
    <div class="tcards">
      <?php
      $tst = [
        ['t' => 'Llevamos cerca de 10 años trabajando con Bamboo y su gerente Adriana Sandoval. Destacamos su gran profesionalismo, sus prontas respuestas y su experiencia para gestionar las mejores opciones de seguros, siempre orientando a la cobertura que mejor se acomoda a las expectativas de nuestra empresa.', 'n' => 'Rubén Córdoba Pizarro', 'r' => 'Gerente General · Rescate Familiar', 'a' => 'R'],
        ['t' => 'Después de 2 años estoy muy contento de contar con una agente que vela por mis intereses. Su servicio es mucho más que informar: sugiere acciones, muestra el camino, advierte riesgos y está siempre disponible. Una profesional muy responsable, que cuida verdaderamente a sus clientes.', 'n' => 'Bernardo Andrews', 'r' => 'Cliente', 'a' => 'B'],
        ['t' => 'Elegí Bamboo especialmente por la cordialidad y eficiencia en la atención de Adriana, tanto en el acompañamiento al elegir una compañía —por sus coberturas y costos— como en la orientación al momento de un siniestro. 100% recomendable.', 'n' => 'Alex Ambler', 'r' => 'Cliente', 'a' => 'A'],
        ['t' => 'La atención de Adriana me ha brindado la confianza y tranquilidad que busco al tomar decisiones importantes. Destaco su asesoría personalizada, su profundo conocimiento de la industria y su compromiso por encontrar las mejores alternativas. La recomiendo totalmente.', 'n' => 'Anamorella Peñalba', 'r' => 'Cliente', 'a' => 'A'],
        ['t' => 'Prefiero la atención de Adriana por dos razones: su reacción inmediata y proactiva ante los siniestros, y su respuesta en tiempo real y detallada a cada consulta que le hago como cliente.', 'n' => 'Roberto de Groote', 'r' => 'Cliente', 'a' => 'R'],
        ['t' => 'Asesorarme por Adriana ha sido una experiencia excelente. Destaco su compromiso, su impecable gestión, muy resolutiva, y la tranquilidad de saber que estás en manos de expertos.', 'n' => 'Marcela Jara', 'r' => 'Cliente', 'a' => 'M'],
        ['t' => 'Excelente asesoramiento en la búsqueda de mejores alternativas en el mercado asegurador, con un servicio de post venta cercano y ágil.', 'n' => 'César Naranjo Ramírez', 'r' => 'Cliente', 'a' => 'C'],
        ['t' => 'Me siento «segura» por el excelente servicio y asesoría.', 'n' => 'Gabriela Darvasi', 'r' => 'Cliente', 'a' => 'G'],
        ['t' => 'Destaca por su responsabilidad, confianza y atención oportuna.', 'n' => 'Karolina Schachter', 'r' => 'Cliente', 'a' => 'K'],
      ];
      foreach ($tst as $i => $t): ?>
        <article class="tcard reveal" data-d="<?= $i + 1 ?>">
          <div class="tcard__stars"><?= str_repeat(bb_icon('star'), 5) ?></div>
          <p>«<?= e($t['t']) ?>»</p>
          <div class="tcard__who">
            <div class="tcard__av"><?= e($t['a']) ?></div>
            <div><b><?= e($t['n']) ?></b><span><?= e($t['r']) ?></span></div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ FAQ ============ -->
<section class="section" id="faq-home">
  <div class="container">
    <div class="section-head section-head--center reveal">
      <span class="eyebrow eyebrow--center">Preguntas frecuentes</span>
      <h2>Resolvemos tus dudas</h2>
    </div>
    <div class="faq reveal">
      <?php foreach ($home_faqs as $f): ?>
        <details class="faq__item">
          <summary class="faq__q"><?= e($f['q']) ?><span class="pm" aria-hidden="true"></span></summary>
          <div class="faq__a"><?= e($f['a']) ?></div>
        </details>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:32px" class="reveal"><a href="/faq" class="btn btn--ghost">Ver todas las preguntas <?= bb_icon('arrow') ?></a></div>
  </div>
</section>

<!-- ============ CTA FINAL ============ -->
<section class="section cta-final">
  <div class="container container--narrow">
    <div class="reveal">
      <h2>¿Listo para asegurar lo que más te importa?</h2>
      <p>Cotiza con Bamboo rápidamente. Te respondemos el mismo día, comparamos por ti y te acompañamos siempre.</p>
      <div class="cta-final__btns">
        <button type="button" class="btn btn--on-dark btn--lg" data-cotizar><?= bb_icon('chat') ?> Cotizar</button>
        <a href="<?= e(wa_link()) ?>" class="btn btn--whatsapp btn--lg" target="_blank" rel="noopener"><?= bb_icon('whatsapp') ?> Cotizar por WhatsApp</a>
      </div>
      <p class="cta-final__alt">También puedes escribirnos: <a href="tel:+<?= e($SITE['phone_e164']) ?>"><?= e($SITE['phone_display']) ?></a> · <a href="mailto:<?= e($SITE['email']) ?>"><?= e($SITE['email']) ?></a></p>
      <div class="cta-final__micro">
        <span><?= bb_icon('check') ?> Asesoría independiente</span>
        <span><?= bb_icon('shield-check') ?> Corredora inscrita en la CMF</span>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
