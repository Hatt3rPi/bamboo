<?php
/* partials/cotizar-modal.php — formulario de cotización 3 pasos. Incluido desde footer.php. */
?>
<div class="modal" id="cotizarModal" aria-hidden="true">
  <div class="modal__backdrop" data-modal-close></div>
  <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="qfTitle">
    <div class="modal__head">
      <h3 id="qfTitle">Cotiza tu seguro gratis</h3>
      <button type="button" class="modal__close" data-modal-close aria-label="Cerrar"><?= bb_icon('close') ?></button>
    </div>

    <div class="qf__progress" data-qf-progress aria-hidden="true">
      <span class="on"></span><span></span><span></span>
    </div>

    <div class="modal__body">
      <form id="cotizarForm" novalidate>
        <!-- PASO 1 -->
        <section class="qf__step on" data-step="1">
          <div class="qf__steplabel">Paso 1 de 3</div>
          <h4>¿Qué quieres asegurar?</h4>
          <p>Elige una opción y te ayudamos a encontrar la mejor entre más de 15 compañías.</p>
          <div class="qf__types" role="group" aria-label="Tipo de seguro">
            <?php foreach ($SEGUROS as $svc): ?>
              <button type="button" class="qf__type" aria-pressed="false"
                      data-value="<?= e($svc['nombre']) ?>" data-slug="<?= e($svc['slug']) ?>">
                <?= bb_icon($svc['icon']) ?><b><?= e($svc['menu']) ?></b>
              </button>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="tipo_seguro" id="qfTipo" required>
          <div class="qf__nav">
            <button type="button" class="btn btn--primary btn--block" data-qf-next disabled>Continuar</button>
          </div>
        </section>

        <!-- PASO 2 -->
        <section class="qf__step" data-step="2">
          <div class="qf__steplabel">Paso 2 de 3</div>
          <h4>Cuéntanos un poco más</h4>
          <p>Mientras más nos cuentes, mejor será tu cotización. (Opcional)</p>
          <div class="field">
            <span class="form-label" id="qfPerfilLbl">¿Es para ti o para tu empresa?</span>
            <div class="segtabs" role="group" aria-labelledby="qfPerfilLbl" style="display:inline-flex">
              <button type="button" class="segtab" aria-pressed="true" data-perfil="Persona">Para mí</button>
              <button type="button" class="segtab" aria-pressed="false" data-perfil="Pyme / Empresa">Para mi empresa</button>
            </div>
            <input type="hidden" name="perfil" id="qfPerfil" value="Persona">
          </div>
          <div class="field">
            <label for="qfDetalle">Detalle</label>
            <textarea id="qfDetalle" name="detalle" data-qf-detalle
              placeholder="Ej: auto modelo 2022 uso particular, o arriendo de un departamento en Ñuñoa."></textarea>
          </div>
          <div class="qf__nav">
            <button type="button" class="btn btn--ghost qf__back" data-qf-prev aria-label="Volver al paso anterior"><?= bb_icon('arrow') ?></button>
            <button type="button" class="btn btn--primary" data-qf-next>Continuar</button>
          </div>
        </section>

        <!-- PASO 3 -->
        <section class="qf__step" data-step="3">
          <div class="qf__steplabel">Paso 3 de 3</div>
          <h4>Déjanos tus datos y te contactamos</h4>
          <p>Adriana revisará tu caso personalmente. Sin compromiso.</p>
          <div class="field" data-validate="text">
            <label for="qfNombre">Nombre</label>
            <input type="text" id="qfNombre" name="nombre" autocomplete="name" required aria-describedby="err-nombre">
            <span class="err" id="err-nombre">Cuéntanos tu nombre.</span>
          </div>
          <div class="field" data-validate="tel">
            <label for="qfTel">Teléfono (WhatsApp)</label>
            <input type="tel" id="qfTel" name="telefono" autocomplete="tel" inputmode="tel" placeholder="+56 9 ..." aria-describedby="err-tel">
            <span class="err" id="err-tel">Ingresa un número de celular válido (+56 9 ...).</span>
          </div>
          <div class="field" data-validate="email">
            <label for="qfEmail">Correo</label>
            <input type="email" id="qfEmail" name="email" autocomplete="email" placeholder="tucorreo@ejemplo.cl" aria-describedby="err-email">
            <span class="err" id="err-email">Ingresa un correo válido (o déjanos tu teléfono).</span>
          </div>
          <label class="checkrow" data-consent-row>
            <input type="checkbox" name="consent" id="qfConsent" required>
            <span>Autorizo a Bamboo Seguros a contactarme para enviarme mi cotización.</span>
          </label>
          <!-- honeypot anti-bot -->
          <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
          <div class="qf__nav">
            <button type="button" class="btn btn--ghost qf__back" data-qf-prev aria-label="Volver al paso anterior"><?= bb_icon('arrow') ?></button>
            <button type="submit" class="btn btn--primary" data-qf-submit>Quiero mi cotización gratis</button>
          </div>
          <div class="qf__error" data-qf-error role="alert">
            No pudimos enviar tu solicitud. Inténtalo de nuevo o escríbenos por WhatsApp al <?= e($SITE['phone_display']) ?>.
          </div>
          <p class="qf__micro"><?= bb_icon('shield-check') ?> No compartimos tus datos. Solo los usamos para tu cotización.</p>
        </section>

        <!-- ÉXITO -->
        <div class="qf__success" data-qf-success role="status" tabindex="-1">
          <div class="tick"><?= bb_icon('check-circle') ?></div>
          <h4>¡Listo<span data-qf-name></span>! Recibimos tu solicitud</h4>
          <p>Adriana revisará tu caso y te contactará a la brevedad. ¿Quieres adelantar la conversación con un ejecutivo?</p>
          <a href="#" class="btn btn--whatsapp btn--block btn--lg" data-qf-wa target="_blank" rel="noopener">
            <?= bb_icon('whatsapp') ?> Continuar por WhatsApp
          </a>
        </div>
      </form>
    </div>
  </div>
</div>
