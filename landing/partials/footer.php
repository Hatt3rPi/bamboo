<?php
/* partials/footer.php — cierra <main>, footer, WA flotante, barra móvil, modal y scripts. */
?>
</main>

<footer class="footer">
  <div class="container">
    <div class="footer__grid">
      <div class="footer__brand">
        <img src="/assets/img/logo-2.png" alt="Bamboo Seguros — asesoría y confianza" width="180" height="64">
        <p>Corredora de seguros independiente en Chile. Comparamos entre las principales aseguradoras del país para que asegures lo que más quieres, con confianza y sin costo de asesoría.</p>
        <div class="footer__social">
          <?php if ($SITE['instagram']): ?><a href="<?= e($SITE['instagram']) ?>" aria-label="Instagram" target="_blank" rel="noopener"><?= bb_icon('instagram') ?></a><?php endif; ?>
          <?php if ($SITE['facebook']): ?><a href="<?= e($SITE['facebook']) ?>" aria-label="Facebook" target="_blank" rel="noopener"><?= bb_icon('facebook') ?></a><?php endif; ?>
          <?php if ($SITE['linkedin']): ?><a href="<?= e($SITE['linkedin']) ?>" aria-label="LinkedIn" target="_blank" rel="noopener"><?= bb_icon('linkedin') ?></a><?php endif; ?>
        </div>
      </div>

      <div>
        <h4>Navegación</h4>
        <ul class="footer__links">
          <li><a href="/#servicios">Seguros</a></li>
          <li><a href="/como-operamos">Cómo trabajamos</a></li>
          <li><a href="/nosotros">Nosotros</a></li>
          <li><a href="/faq">Preguntas frecuentes</a></li>
          <li><a href="/contacto">Contacto</a></li>
          <li><a href="<?= e($SITE['portal_login_url']) ?>">Acceso interno</a></li>
        </ul>
      </div>

      <div>
        <h4>Seguros</h4>
        <ul class="footer__links">
          <?php foreach (array_slice($SEGUROS, 0, 7) as $svc): ?>
            <li><a href="/seguros/<?= e($svc['slug']) ?>"><?= e($svc['menu']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div>
        <h4>Contacto</h4>
        <div class="footer__contact">
          <a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener"><?= bb_icon('whatsapp') ?> WhatsApp <?= e($SITE['phone_display']) ?></a>
          <a href="tel:+<?= e($SITE['phone_e164']) ?>"><?= bb_icon('phone') ?> <?= e($SITE['phone_display']) ?></a>
          <a href="mailto:<?= e($SITE['email']) ?>"><?= bb_icon('mail') ?> <?= e($SITE['email']) ?></a>
          <?php if ($SITE['comuna']): ?><a href="/contacto"><?= bb_icon('pin') ?> <?= e($SITE['comuna']) ?>, <?= e($SITE['region']) ?></a><?php endif; ?>
        </div>
      </div>
    </div>

    <div class="footer__legal">
      <p>
        <span class="footer__cmf"><?= bb_icon('shield-check') ?> <?= cmf_label() ?></span><br>
        Bamboo Seguros es una corredora de seguros registrada en la Comisión para el Mercado Financiero (CMF) de Chile. La contratación de seguros está sujeta a la evaluación y condiciones de cada compañía aseguradora.
      </p>
      <p>© <?= date('Y') ?> <?= e($SITE['name']) ?>. Todos los derechos reservados.</p>
    </div>
  </div>
</footer>

<!-- WhatsApp flotante -->
<a href="<?= e(wa_link()) ?>" class="wa-float" target="_blank" rel="noopener" aria-label="Cotizar por WhatsApp" data-wa-float>
  <?= bb_icon('whatsapp') ?>
</a>

<!-- Barra sticky móvil -->
<div class="mbar">
  <button type="button" class="btn btn--primary" data-cotizar><?= bb_icon('chat') ?> Cotizar</button>
  <a href="<?= e(wa_link()) ?>" class="btn btn--whatsapp" target="_blank" rel="noopener"><?= bb_icon('whatsapp') ?> WhatsApp</a>
</div>

<?php require __DIR__ . '/cotizar-modal.php'; ?>

<script src="/assets/js/landing.js?v=<?= e((string)($js_v ?? '1')) ?>" defer></script>
</body>
</html>
