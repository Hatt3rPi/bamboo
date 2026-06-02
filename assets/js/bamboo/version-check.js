/* ============================================================
   BAMBOO · Aviso de nueva versión + borrador del formulario
   - Cada ~60s compara la versión cargada (window.BB_VERSION) contra
     /bamboo/version.php. Si cambió (nuevo deploy) muestra una barra
     discreta abajo con botón "Actualizar".
   - Al actualizar: guarda un borrador de lo que el usuario tenía escrito
     (incluidos los ítems dinámicos vía hook window.bbDraftRehydrate),
     recarga la misma página y restaura el borrador.
   ============================================================ */
(function () {
  'use strict';

  var CHECK_MS     = 60000;                 // intervalo de chequeo
  var ENDPOINT     = '/bamboo/version.php';
  var DRAFT_PREFIX = 'bb_draft_';
  var current      = (typeof window.BB_VERSION !== 'undefined' && window.BB_VERSION !== null)
                       ? String(window.BB_VERSION) : null;

  function draftKey() { return DRAFT_PREFIX + location.pathname; }

  // ---------- Captura / restauración de campos ----------
  function collectFields() {
    var out = [];
    var els = document.querySelectorAll('input, select, textarea');
    for (var i = 0; i < els.length; i++) {
      var el = els[i];
      var key = el.id || el.name;
      if (!key) continue;
      var t = (el.type || '').toLowerCase();
      if (t === 'password' || t === 'file' || t === 'button' || t === 'submit') continue;
      var rec = { key: key, type: t };
      if (t === 'checkbox' || t === 'radio') { rec.checked = el.checked; rec.value = el.value; }
      else { rec.value = el.value; }
      out.push(rec);
    }
    return out;
  }

  function findEl(key) {
    var el = document.getElementById(key);
    if (el) return el;
    var byName = document.getElementsByName(key);
    if (byName && byName.length === 1) return byName[0];
    return null;
  }

  function applyField(rec) {
    var el = findEl(rec.key);
    if (!el) return;
    var t = (el.type || '').toLowerCase();
    if (t === 'checkbox' || t === 'radio') { el.checked = !!rec.checked; }
    else if (typeof rec.value !== 'undefined') { el.value = rec.value; }
  }

  function saveDraft() {
    try {
      var data = {
        ts: (+new Date()),
        contador: (document.getElementById('contador') ? document.getElementById('contador').value : null),
        fields: collectFields()
      };
      sessionStorage.setItem(draftKey(), JSON.stringify(data));
    } catch (e) {}
  }

  function restoreDraft() {
    var raw;
    try { raw = sessionStorage.getItem(draftKey()); } catch (e) { return; }
    if (!raw) return;
    var data;
    try { data = JSON.parse(raw); } catch (e) { try { sessionStorage.removeItem(draftKey()); } catch (e2) {} return; }

    var fields = data.fields || [];

    // 1) Restaurar primero radios/checkboxes/selects "de control" que existan,
    //    para que la reconstrucción de dinámicos lea el estado correcto.
    for (var i = 0; i < fields.length; i++) {
      if (fields[i].type === 'radio' || fields[i].type === 'checkbox' ||
          fields[i].type === 'select-one' || fields[i].type === 'select-multiple') {
        applyField(fields[i]);
      }
    }

    // 2) Reconstruir elementos dinámicos (ítems, bienes, etc.) si la página
    //    define un rehidratador. Recibe el borrador completo.
    try {
      if (typeof window.bbDraftRehydrate === 'function') { window.bbDraftRehydrate(data); }
    } catch (e) {}

    // 3) Restaurar TODOS los campos (ahora ya existen los dinámicos).
    for (var j = 0; j < fields.length; j++) { applyField(fields[j]); }

    try { sessionStorage.removeItem(draftKey()); } catch (e) {}
    toast('✔ Restauramos lo que estabas trabajando.');
  }

  // ---------- UI ----------
  function toast(msg) {
    var t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = 'position:fixed;bottom:16px;right:16px;background:#2e3b30;color:#fff;' +
      'padding:10px 16px;border-radius:8px;z-index:20000;font-size:13px;box-shadow:0 4px 16px rgba(0,0,0,.25)';
    document.body.appendChild(t);
    setTimeout(function () {
      t.style.transition = 'opacity .4s'; t.style.opacity = '0';
      setTimeout(function () { if (t.parentNode) t.parentNode.removeChild(t); }, 400);
    }, 4500);
  }

  var barShown = false;
  function showUpdateBar() {
    if (barShown || document.getElementById('bb-update-bar')) return;
    barShown = true;
    var bar = document.createElement('div');
    bar.id = 'bb-update-bar';
    bar.style.cssText = 'position:fixed;left:0;right:0;bottom:0;z-index:19000;background:#2e3b30;' +
      'color:#fff;padding:12px 20px;display:flex;align-items:center;gap:16px;justify-content:center;' +
      'font-size:14px;box-shadow:0 -2px 12px rgba(0,0,0,.2)';
    bar.innerHTML =
      '<span><strong>Hay una nueva versión disponible.</strong> Actualizá para tener los últimos cambios.</span>' +
      '<button type="button" id="bb-update-now">Actualizar</button>' +
      '<button type="button" id="bb-update-later">Después</button>';
    document.body.appendChild(bar);

    var bNow = document.getElementById('bb-update-now');
    var bLater = document.getElementById('bb-update-later');
    bNow.style.cssText = 'background:#fff;color:#2e3b30;border:0;border-radius:6px;padding:7px 18px;font-weight:600;cursor:pointer';
    bLater.style.cssText = 'background:transparent;color:#fff;border:1px solid rgba(255,255,255,.4);border-radius:6px;padding:7px 14px;cursor:pointer';

    bNow.onclick = function () { saveDraft(); location.reload(); };
    bLater.onclick = function () { if (bar.parentNode) bar.parentNode.removeChild(bar); barShown = false; };
  }

  // ---------- Polling ----------
  function check() {
    fetch(ENDPOINT, { cache: 'no-store', headers: { 'Cache-Control': 'no-cache' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (j) {
        if (!j || j.v == null) return;
        var v = String(j.v);
        if (current === null) { current = v; return; }   // establece baseline si no vino del layout
        if (v !== current) { showUpdateBar(); }
      })
      .catch(function () {});
  }

  // ---------- Arranque ----------
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', restoreDraft);
  } else {
    restoreDraft();
  }
  setTimeout(check, 5000);
  setInterval(check, CHECK_MS);
})();
