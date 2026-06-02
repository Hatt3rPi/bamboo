/* ============================================================
   BAMBOO SEGUROS · LANDING · landing.js
   Vanilla JS. Sin dependencias. Defer.
   ============================================================ */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  function track(name, params) {
    if (window.BAMBOO && window.BAMBOO.ga && typeof window.gtag === 'function') {
      window.gtag('event', name, params || {});
    }
  }

  /* ---------- Header sticky ---------- */
  var header = $('#siteHeader');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-stuck', window.scrollY > 8); };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ---------- Drawer móvil ---------- */
  var drawer = $('#drawer');
  function openDrawer() { if (drawer) { drawer.classList.add('is-open'); drawer.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden'; } }
  function closeDrawer() { if (drawer) { drawer.classList.remove('is-open'); drawer.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; } }
  $$('[data-drawer-open]').forEach(function (b) { b.addEventListener('click', openDrawer); });
  $$('[data-drawer-close]').forEach(function (b) { b.addEventListener('click', closeDrawer); });

  /* ---------- Reveal on scroll ---------- */
  var reveals = $$('.reveal');
  if ('IntersectionObserver' in window && reveals.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
    reveals.forEach(function (el) { io.observe(el); });
  } else { reveals.forEach(function (el) { el.classList.add('in'); }); }

  /* ---------- Marquee aseguradoras (clonar para loop continuo) ---------- */
  $$('.marquee__track').forEach(function (tr) {
    tr.innerHTML += tr.innerHTML;
  });

  /* ---------- Tabs Personas / Pymes ---------- */
  $$('[data-segtabs]').forEach(function (group) {
    var tabs = $$('.segtab', group);
    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        tabs.forEach(function (t) { t.setAttribute('aria-selected', 'false'); });
        tab.setAttribute('aria-selected', 'true');
        var target = tab.getAttribute('data-target');
        $$('[data-segpanel]').forEach(function (p) {
          p.hidden = (p.getAttribute('data-segpanel') !== target);
        });
      });
    });
  });

  /* ---------- FAQ: cerrar otros al abrir uno (acordeón) ---------- */
  $$('.faq__item').forEach(function (item) {
    item.addEventListener('toggle', function () {
      if (item.open) {
        $$('.faq__item').forEach(function (o) { if (o !== item) o.open = false; });
      }
    });
  });

  /* ============================================================
     MODAL COTIZAR + FORMULARIO MULTIPASO
     ============================================================ */
  var modal = $('#cotizarModal');
  var form = $('#cotizarForm');
  var lastFocus = null;

  function openModal() {
    if (!modal) return;
    lastFocus = document.activeElement;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    track('cotizar_open');
    var first = $('.qf__type', modal);
    if (first) setTimeout(function () { first.focus(); }, 60);
  }
  function closeModal() {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    if (lastFocus) lastFocus.focus();
  }

  $$('[data-cotizar]').forEach(function (b) {
    b.addEventListener('click', function (e) {
      e.preventDefault();
      openModal();
      var slug = b.getAttribute('data-slug');
      if (slug) {
        var t = $('.qf__type[data-slug="' + slug + '"]', modal);
        if (t) { selectType(t); goStep(2); }
      }
    });
  });
  $$('[data-modal-close]').forEach(function (b) { b.addEventListener('click', closeModal); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeModal(); closeDrawer(); }
  });

  if (!form) return;

  var steps = $$('.qf__step', form);
  var progress = $$('[data-qf-progress] span');
  var current = 1;
  var placeholders = {
    auto: 'Ej: Toyota Yaris 2022, uso particular, full cobertura.',
    vida: 'Ej: tengo 35 años, quiero proteger a mi familia.',
    viaje: 'Ej: viaje a Europa 15 días en julio, 2 personas.',
    'incendio-sismo': 'Ej: casa en Ñuñoa, 90 m², con sismo.',
    arriendo: 'Ej: arriendo un departamento, quiero garantía de pago.',
    garantia: 'Ej: garantía de fiel cumplimiento para licitación pública.',
    transporte: 'Ej: importo mercadería desde China, vía marítima.'
  };

  function goStep(n) {
    current = n;
    steps.forEach(function (s) { s.classList.toggle('on', +s.getAttribute('data-step') === n); });
    progress.forEach(function (p, i) { p.classList.toggle('on', i < n); });
    var active = $('.qf__step.on', form);
    if (active) { var h = $('h4', active); if (h) h.setAttribute('tabindex', '-1'), h.focus(); }
  }

  function selectType(btn) {
    $$('.qf__type', form).forEach(function (t) { t.classList.remove('sel'); t.setAttribute('aria-checked', 'false'); });
    btn.classList.add('sel'); btn.setAttribute('aria-checked', 'true');
    $('#qfTipo').value = btn.getAttribute('data-value');
    form.dataset.slug = btn.getAttribute('data-slug');
    var nextBtn = $('.qf__step[data-step="1"] [data-qf-next]', form);
    if (nextBtn) nextBtn.disabled = false;
    var det = $('[data-qf-detalle]', form);
    if (det) det.placeholder = placeholders[btn.getAttribute('data-slug')] || det.placeholder;
  }

  $$('.qf__type', form).forEach(function (btn) {
    btn.addEventListener('click', function () { selectType(btn); });
  });

  /* Perfil persona/pyme */
  $$('[data-perfil]', form).forEach(function (b) {
    b.addEventListener('click', function () {
      $$('[data-perfil]', form).forEach(function (x) { x.setAttribute('aria-selected', 'false'); });
      b.setAttribute('aria-selected', 'true');
      $('#qfPerfil').value = b.getAttribute('data-perfil');
    });
  });

  /* Navegación pasos */
  $$('[data-qf-next]', form).forEach(function (b) {
    b.addEventListener('click', function () {
      if (current === 1 && !$('#qfTipo').value) return;
      goStep(Math.min(3, current + 1));
    });
  });
  $$('[data-qf-prev]', form).forEach(function (b) {
    b.addEventListener('click', function () { goStep(Math.max(1, current - 1)); });
  });

  /* Validación paso 3 */
  function setInvalid(field, bad) { field.classList.toggle('invalid', bad); }
  function validateStep3() {
    var ok = true;
    var fName = $('[data-validate="text"]', form), name = $('#qfNombre');
    if (!name.value.trim()) { setInvalid(fName, true); ok = false; } else setInvalid(fName, false);

    var tel = $('#qfTel').value.replace(/[^\d+]/g, '');
    var email = $('#qfEmail').value.trim();
    var telOk = /^(\+?56)?9\d{8}$/.test(tel);
    var emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    var fTel = $('[data-validate="tel"]', form), fEmail = $('[data-validate="email"]', form);
    // al menos uno de los dos; si se escribió y es inválido, marcar
    if (!telOk && !emailOk) {
      setInvalid(fTel, !!tel || (!tel && !email));
      setInvalid(fEmail, !!email || (!tel && !email));
      ok = false;
    } else {
      setInvalid(fTel, !!tel && !telOk);
      setInvalid(fEmail, !!email && !emailOk);
      if ((tel && !telOk) || (email && !emailOk)) ok = false;
    }
    if (!$('#qfConsent').checked) ok = false;
    return ok;
  }

  function buildWaLink(name, tipo) {
    var msg = 'Hola Adriana, soy ' + (name || '') + '. Coticé un ' + (tipo || 'seguro') + ' en bambooseguros.cl y quiero avanzar.';
    return window.BAMBOO.wa + '?text=' + encodeURIComponent(msg);
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!validateStep3()) return;

    var submitBtn = $('[data-qf-submit]', form);
    var name = $('#qfNombre').value.trim();
    var tipo = $('#qfTipo').value;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Enviando…';

    var data = new FormData(form);
    var done = function () {
      // Mostrar éxito (pase lo que pase con el correo: WhatsApp es la red de seguridad)
      $('.qf__progress', modal).style.display = 'none';
      steps.forEach(function (s) { s.classList.remove('on'); });
      var nameSpan = $('[data-qf-name]', form); if (nameSpan) nameSpan.textContent = name ? ', ' + name : '';
      var wa = $('[data-qf-wa]', form); if (wa) wa.href = buildWaLink(name, tipo);
      $('[data-qf-success]', form).classList.add('on');
      track('generate_lead', { method: 'form', seguro: tipo });
    };

    fetch('/backend/enviar_cotizacion.php', { method: 'POST', body: data })
      .then(function (r) { return r.json().catch(function () { return {}; }); })
      .then(done)
      .catch(done);
  });

  /* ---------- Tracking de clics WhatsApp ---------- */
  $$('a[href*="wa.me"], [data-wa-float]').forEach(function (a) {
    a.addEventListener('click', function () { track('generate_lead', { method: 'whatsapp' }); });
  });
  $$('a[href^="tel:"]').forEach(function (a) {
    a.addEventListener('click', function () { track('generate_lead', { method: 'call' }); });
  });
})();
