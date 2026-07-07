/* ============================================================
   BAMBOO SEGUROS · LANDING · landing.js
   Vanilla JS. Sin dependencias. Defer.
   Config en data-* del <body> (sin scripts inline → CSP estricta).
   ============================================================ */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  var CFG = {
    wa: 'https://wa.me/' + (document.body.dataset.wa || '56995091193'),
    ga: document.body.dataset.ga === '1'
  };

  function track(name, params) {
    if (CFG.ga && typeof window.gtag === 'function') window.gtag('event', name, params || {});
  }

  /* ---------- Header sticky ---------- */
  var header = $('#siteHeader');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-stuck', window.scrollY > 8); };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ============================================================
     Gestión de diálogos: focus trap + fondo inert (modal y drawer)
     ============================================================ */
  var activeDialog = null;
  function focusables(container) {
    return $$('a[href],button:not([disabled]),input:not([disabled]),textarea:not([disabled]),select:not([disabled]),[tabindex]:not([tabindex="-1"])', container)
      .filter(function (el) { return el.offsetParent !== null || el === document.activeElement; });
  }
  function bgInert(on) {
    ['#siteHeader', '#main', '.footer', '.wa-float', '.mbar'].forEach(function (sel) {
      var el = $(sel);
      if (!el) return;
      if (on) { el.setAttribute('inert', ''); el.setAttribute('aria-hidden', 'true'); }
      else { el.removeAttribute('inert'); el.removeAttribute('aria-hidden'); }
    });
  }
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Tab' || !activeDialog) return;
    var f = focusables(activeDialog);
    if (!f.length) return;
    var first = f[0], last = f[f.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  });

  /* ---------- Drawer móvil ---------- */
  var drawer = $('#drawer');
  var drawerLast = null;
  function openDrawer() {
    if (!drawer) return;
    drawerLast = document.activeElement;
    drawer.classList.add('is-open'); drawer.setAttribute('aria-hidden', 'false');
    var t = $('[data-drawer-open]'); if (t) t.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    bgInert(true); activeDialog = $('.mobile-drawer__panel', drawer);
    var f = $('.mobile-drawer__panel a, .mobile-drawer__panel button', drawer);
    if (f) setTimeout(function () { try { f.focus(); } catch (_) {} }, 60);
  }
  function closeDrawer() {
    if (!drawer || !drawer.classList.contains('is-open')) return;
    drawer.classList.remove('is-open'); drawer.setAttribute('aria-hidden', 'true');
    var t = $('[data-drawer-open]'); if (t) t.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = ''; bgInert(false); activeDialog = null;
    if (drawerLast) { try { drawerLast.focus(); } catch (_) {} }
  }
  // Importante: registrar los cierres ANTES de los abridores de modal,
  // para que un botón con data-cotizar + data-drawer-close cierre el drawer y LUEGO abra el modal.
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
  $$('.marquee__track').forEach(function (tr) { tr.innerHTML += tr.innerHTML; });

  /* ---------- Tabs Personas / Pymes (roving tabindex + flechas) ---------- */
  $$('[data-segtabs]').forEach(function (group) {
    var tabs = $$('.segtab', group);
    function activate(tab) {
      tabs.forEach(function (t) {
        var on = t === tab;
        t.setAttribute('aria-selected', on ? 'true' : 'false');
        t.tabIndex = on ? 0 : -1;
      });
      var target = tab.getAttribute('data-target');
      $$('[data-segpanel]').forEach(function (p) { p.hidden = p.getAttribute('data-segpanel') !== target; });
    }
    tabs.forEach(function (tab, i) {
      tab.addEventListener('click', function () { activate(tab); });
      tab.addEventListener('keydown', function (e) {
        var go = null;
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') go = tabs[(i + 1) % tabs.length];
        else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') go = tabs[(i - 1 + tabs.length) % tabs.length];
        else if (e.key === 'Home') go = tabs[0];
        else if (e.key === 'End') go = tabs[tabs.length - 1];
        if (go) { e.preventDefault(); activate(go); go.focus(); }
      });
    });
  });

  /* ---------- FAQ: acordeón (cerrar otros al abrir) ---------- */
  $$('.faq__item').forEach(function (item) {
    item.addEventListener('toggle', function () {
      if (item.open) $$('.faq__item').forEach(function (o) { if (o !== item) o.open = false; });
    });
  });

  /* ============================================================
     MODAL COTIZAR + FORMULARIO MULTIPASO
     ============================================================ */
  var modal = $('#cotizarModal');
  var form = $('#cotizarForm');
  var lastFocus = null;

  var placeholders = {
    auto: 'Ej: Toyota Yaris 2022, uso particular, full cobertura.',
    'auto|pyme': 'Ej: flota de 4 camionetas comerciales para mi empresa.',
    vida: 'Ej: tengo 35 años, quiero proteger a mi familia.',
    viaje: 'Ej: viaje a Europa 15 días en julio, 2 personas.',
    'accidentes-personales': 'Ej: cobertura para mi hijo en edad escolar / deportes.',
    'incendio-sismo': 'Ej: casa en Ñuñoa, 90 m², con cobertura de sismo.',
    'incendio-sismo|pyme': 'Ej: oficina o local de 120 m² en Providencia, con cobertura de sismo.',
    'salud-individual': 'Ej: busco cobertura complementaria a mi Isapre o Fonasa.',
    'catastrofico-individual': 'Ej: quiero protegerme ante una enfermedad de alto costo.',
    'salud-empresas': 'Ej: seguro de salud para los colaboradores de mi empresa.',
    'catastrofico-empresas': 'Ej: cobertura catastrófica para mi equipo de trabajo.',
    'responsabilidad-civil': 'Ej: local comercial que atiende público / taller.',
    garantia: 'Ej: garantía de fiel cumplimiento para licitación pública.',
    transporte: 'Ej: importo mercadería desde China, vía marítima.',
    apv: 'Ej: quiero mejorar mi pensión y rebajar impuestos.',
    ingenieria: 'Ej: obra de construcción de 6 meses, todo riesgo.',
    'rc-administradores': 'Ej: comité de administración de un condominio de 40 departamentos.'
  };

  if (modal && form) {
    var steps = $$('.qf__step', form);
    var progress = $$('[data-qf-progress] span');
    var current = 1;

    function goStep(n) {
      current = n;
      steps.forEach(function (s) { s.classList.toggle('on', +s.getAttribute('data-step') === n); });
      progress.forEach(function (p, i) { p.classList.toggle('on', i < n); });
      var active = $('.qf__step.on', form);
      if (active) { var h = $('h4', active); if (h) { h.setAttribute('tabindex', '-1'); try { h.focus(); } catch (_) {} } }
    }

    // Placeholder del detalle según ramo + perfil. Permite variantes por segmento:
    // p.ej. incendio para empresa habla de "oficina/local" en vez de "casa".
    function currentSeg() {
      var pb = $('[data-perfil][aria-pressed="true"]', form);
      return pb ? (pb.getAttribute('data-seg') || 'persona') : 'persona';
    }
    function applyPlaceholder() {
      var det = $('[data-qf-detalle]', form); if (!det) return;
      var slug = form.dataset.slug; if (!slug) return;
      var ph = placeholders[slug + '|' + currentSeg()] || placeholders[slug];
      if (ph) det.placeholder = ph;
    }

    function selectType(btn) {
      $$('.qf__type', form).forEach(function (t) { t.classList.remove('sel'); t.setAttribute('aria-pressed', 'false'); });
      btn.classList.add('sel'); btn.setAttribute('aria-pressed', 'true');
      $('#qfTipo').value = btn.getAttribute('data-value');
      form.dataset.slug = btn.getAttribute('data-slug');
      var n = $('.qf__step[data-step="1"] [data-qf-next]', form); if (n) n.disabled = false;
      applyPlaceholder();
    }

    // Filtra los tipos según el perfil (persona muestra persona+ambos; pyme muestra pyme+ambos).
    function filterTypes(seg) {
      $$('.qf__type', form).forEach(function (t) {
        var s = t.getAttribute('data-seg');
        var show = (s === seg || s === 'ambos');
        t.style.display = show ? '' : 'none';
        if (!show && t.classList.contains('sel')) {
          t.classList.remove('sel'); t.setAttribute('aria-pressed', 'false');
          $('#qfTipo').value = '';
          var n = $('.qf__step[data-step="1"] [data-qf-next]', form); if (n) n.disabled = true;
        }
      });
    }
    function setPerfil(btn) {
      $$('[data-perfil]', form).forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
      btn.setAttribute('aria-pressed', 'true');
      $('#qfPerfil').value = btn.getAttribute('data-perfil');
      filterTypes(btn.getAttribute('data-seg'));
      applyPlaceholder();
    }

    function resetForm() {
      try {
        form.reset();
        $('[data-qf-success]', form).classList.remove('on');
        $('[data-qf-error]', form).classList.remove('on');
        $('[data-qf-progress]', modal).style.display = '';
        $$('.qf__type', form).forEach(function (t) { t.classList.remove('sel'); t.setAttribute('aria-pressed', 'false'); });
        $('#qfTipo').value = '';
        delete form.dataset.slug;
        var n1 = $('.qf__step[data-step="1"] [data-qf-next]', form); if (n1) n1.disabled = true;
        $$('.field.invalid', form).forEach(function (f) { f.classList.remove('invalid'); });
        var crow = $('[data-consent-row]', form); if (crow) crow.classList.remove('invalid');
        var pPersona = $('[data-perfil][data-seg="persona"]', form);
        if (pPersona) setPerfil(pPersona); else { $('#qfPerfil').value = 'Persona'; filterTypes('persona'); }
        var sb = $('[data-qf-submit]', form); if (sb) { sb.disabled = false; sb.textContent = sb.dataset.label || 'Quiero mi cotización'; }
        goStep(1);
      } catch (_) {}
    }

    function openModal(slug) {
      lastFocus = document.activeElement;
      resetForm();
      modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      bgInert(true); activeDialog = $('.modal__dialog', modal);
      track('cotizar_open', { seguro: slug || '' });
      if (slug) {
        var t = $('.qf__type[data-slug="' + slug + '"]', form);
        if (t) {
          var pb = $('[data-perfil][data-seg="' + (t.getAttribute('data-seg') === 'pyme' ? 'pyme' : 'persona') + '"]', form);
          if (pb) setPerfil(pb);
          selectType(t); goStep(2); return;
        }
      }
      var first = $('.qf__type', modal); if (first) setTimeout(function () { try { first.focus(); } catch (_) {} }, 60);
    }
    function closeModal() {
      if (!modal.classList.contains('is-open')) return;
      modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = ''; bgInert(false); activeDialog = null;
      if (lastFocus) { try { lastFocus.focus(); } catch (_) {} }
    }

    $$('[data-cotizar]').forEach(function (b) {
      b.addEventListener('click', function (e) { e.preventDefault(); openModal(b.getAttribute('data-slug')); });
    });
    $$('[data-modal-close]').forEach(function (b) { b.addEventListener('click', closeModal); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeModal(); closeDrawer(); } });

    $$('.qf__type', form).forEach(function (btn) { btn.addEventListener('click', function () { selectType(btn); }); });

    $$('[data-perfil]', form).forEach(function (b) {
      b.addEventListener('click', function () { setPerfil(b); });
    });

    $$('[data-qf-next]', form).forEach(function (b) {
      b.addEventListener('click', function () {
        if (current === 1 && !$('#qfTipo').value) return;
        goStep(Math.min(3, current + 1));
      });
    });
    $$('[data-qf-prev]', form).forEach(function (b) { b.addEventListener('click', function () { goStep(Math.max(1, current - 1)); }); });

    function setInvalid(field, bad, input) {
      field.classList.toggle('invalid', bad);
      if (input) input.setAttribute('aria-invalid', bad ? 'true' : 'false');
    }
    function validateStep3() {
      var ok = true, firstBad = null;
      var fName = $('[data-validate="text"]', form), name = $('#qfNombre');
      if (!name.value.trim()) { setInvalid(fName, true, name); ok = false; firstBad = firstBad || name; }
      else setInvalid(fName, false, name);

      var telInput = $('#qfTel'), emailInput = $('#qfEmail');
      var tel = telInput.value.replace(/[^\d+]/g, '');
      var email = emailInput.value.trim();
      var telOk = /^(\+?56)?9\d{8}$/.test(tel);
      var emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
      var fTel = $('[data-validate="tel"]', form), fEmail = $('[data-validate="email"]', form);
      if (telOk || emailOk) {
        // Hay al menos un canal de contacto válido: no bloquear por un typo en el otro.
        setInvalid(fTel, false, telInput); setInvalid(fEmail, false, emailInput);
      } else {
        setInvalid(fTel, true, telInput); setInvalid(fEmail, true, emailInput);
        ok = false; firstBad = firstBad || telInput;
      }
      var crow = $('[data-consent-row]', form), consent = $('#qfConsent');
      if (!consent.checked) { crow.classList.add('invalid'); ok = false; firstBad = firstBad || consent; }
      else crow.classList.remove('invalid');

      if (!ok && firstBad) { try { firstBad.focus(); } catch (_) {} }
      return ok;
    }

    function buildWaLink(name, tipo) {
      var base = (CFG && CFG.wa) || 'https://wa.me/56995091193';
      var msg = 'Hola Adriana, soy ' + (name || '') + '. Coticé un ' + (tipo || 'seguro') + ' en bambooseguros.cl y quiero avanzar.';
      return base + '?text=' + encodeURIComponent(msg);
    }
    function showSuccess(name, tipo) {
      try {
        $('[data-qf-progress]', modal).style.display = 'none';
        steps.forEach(function (s) { s.classList.remove('on'); });
        var ns = $('[data-qf-name]', form); if (ns) ns.textContent = name ? ', ' + name : '';
        var wa = $('[data-qf-wa]', form); if (wa) wa.href = buildWaLink(name, tipo);
        var box = $('[data-qf-success]', form); box.classList.add('on');
        try { box.focus(); } catch (_) {}
      } catch (_) {}
      track('generate_lead', { method: 'form', seguro: tipo });
    }
    function showError(submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = submitBtn.dataset.label || 'Quiero mi cotización';
      var err = $('[data-qf-error]', form); if (err) { err.classList.add('on'); try { err.scrollIntoView({ block: 'nearest' }); } catch (_) {} }
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var err = $('[data-qf-error]', form); if (err) err.classList.remove('on');
      if (!validateStep3()) return;

      var submitBtn = $('[data-qf-submit]', form);
      var name = $('#qfNombre').value.trim();
      var tipo = $('#qfTipo').value;
      if (!submitBtn.dataset.label) submitBtn.dataset.label = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Enviando…';

      fetch('/backend/enviar_cotizacion.php', { method: 'POST', body: new FormData(form) })
        .then(function (r) {
          // 2xx = OK (el correo es best-effort; el lead queda registrado). 4xx/5xx = fallo real.
          if (r.status >= 200 && r.status < 300) showSuccess(name, tipo);
          else showError(submitBtn);
        })
        .catch(function () { showError(submitBtn); });  // sin red: error honesto + WhatsApp en el mensaje
    });
  }

  /* ---------- Tracking de clics WhatsApp / teléfono ---------- */
  $$('a[href*="wa.me"], [data-wa-float]').forEach(function (a) {
    a.addEventListener('click', function () { track('generate_lead', { method: 'whatsapp' }); });
  });
  $$('a[href^="tel:"]').forEach(function (a) {
    a.addEventListener('click', function () { track('generate_lead', { method: 'call' }); });
  });
})();
