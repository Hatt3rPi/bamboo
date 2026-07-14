/* ============================================================
   BAMBOO SEGUROS · Netlify Function · enviar-cotizacion
   Reemplaza a backend/enviar_cotizacion.php en el hosting
   estático: mismo contrato (POST FormData, responde JSON y el
   front solo mira el status HTTP). Envía el lead por Brevo API.

   Requiere env vars en el sitio Netlify:
     BREVO_API_KEY  (obligatoria)
     LEAD_TO_EMAIL  (opcional, default asandoval@bambooseguros.cl)
   ============================================================ */

const TO_EMAIL = process.env.LEAD_TO_EMAIL || 'asandoval@bambooseguros.cl';
const TO_NAME = 'Adriana Sandoval';
const FROM = { name: 'Bamboo Web', email: 'no-reply@bambooseguros.cl' };

// Rate-limit best-effort por IP (la instancia es efímera; complementa al honeypot).
const WINDOW_MS = 10 * 60 * 1000;
const MAX_HITS = 6;
const hits = new Map();

const json = (obj, status) =>
  new Response(JSON.stringify(obj), {
    status,
    headers: { 'content-type': 'application/json; charset=utf-8' },
  });

const clean = (v, max = 500) =>
  typeof v === 'string' ? v.trim().replace(/[\r\n\t]+/g, ' ').slice(0, max) : '';

const esc = (s) =>
  String(s).replace(/[&<>"']/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

export default async (req, context) => {
  if (req.method !== 'POST') return json({ ok: false, error: 'Método no permitido' }, 405);

  const ip = context?.ip || req.headers.get('x-forwarded-for') || '0';
  const now = Date.now();
  const prev = (hits.get(ip) || []).filter((t) => t > now - WINDOW_MS);
  if (prev.length >= MAX_HITS) {
    return json({ ok: false, error: 'Demasiados intentos. Espera unos minutos o escríbenos por WhatsApp.' }, 429);
  }
  prev.push(now);
  hits.set(ip, prev);

  let form;
  try {
    form = await req.formData();
  } catch {
    return json({ ok: false, error: 'Formato inválido' }, 400);
  }

  // Honeypot: si viene relleno, es bot. Respondemos ok para no darle pistas.
  if (clean(form.get('website'))) return json({ ok: true }, 200);

  const tipo    = clean(form.get('tipo_seguro'), 120);
  const perfil  = clean(form.get('perfil'), 40) || 'Persona';
  const detalle = clean(form.get('detalle'), 1500);
  const nombre  = clean(form.get('nombre'), 120);
  const tel     = clean(form.get('telefono'), 40);
  const email   = clean(form.get('email'), 160);
  const consent = !!form.get('consent');

  if (!nombre) return json({ ok: false, error: 'Falta el nombre' }, 400);
  if (!consent) return json({ ok: false, error: 'Falta el consentimiento de contacto' }, 400);
  const telDigits = tel.replace(/[^\d+]/g, '');
  const telOk = /^(\+?56)?9\d{8}$/.test(telDigits);
  const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email);
  if (!telOk && !emailOk) return json({ ok: false, error: 'Necesitamos un teléfono o correo válido' }, 400);

  // ---------- Correo (mismo formato HTML que el PHP original) ----------
  const fecha = new Intl.DateTimeFormat('es-CL', {
    timeZone: 'America/Santiago',
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit', hour12: false,
  }).format(new Date()).replace(/-/g, '-').replace(',', '');

  const rows = {
    'Tipo de seguro': tipo,
    'Para': perfil,
    'Nombre': nombre,
    'Teléfono': tel !== '' ? tel : '—',
    'Correo': email !== '' ? email : '—',
    'Detalle': detalle !== '' ? detalle : '—',
    'Fecha': fecha,
  };
  let rowsHtml = '';
  for (const [k, v] of Object.entries(rows)) {
    rowsHtml +=
      '<tr>' +
      `<td style="padding:7px 16px;color:#5a5851;font:bold 13px Arial,sans-serif;white-space:nowrap;vertical-align:top;border-bottom:1px solid #eee">${esc(k)}</td>` +
      `<td style="padding:7px 16px;color:#26302a;font:14px Arial,sans-serif;border-bottom:1px solid #eee">${esc(v).replace(/\n/g, '<br>')}</td>` +
      '</tr>';
  }
  const acciones = [];
  if (telOk) acciones.push(`<a href="https://wa.me/${telDigits.replace(/[^\d]/g, '')}" style="color:#0b7a43;font-weight:bold;text-decoration:none">Escribir por WhatsApp</a>`);
  if (emailOk) acciones.push(`<a href="mailto:${esc(email)}" style="color:#0b7a43;font-weight:bold;text-decoration:none">Responder correo</a>`);
  const accionesHtml = acciones.length
    ? `<div style="padding:14px 16px;background:#f6f4f0;font:14px Arial,sans-serif">Contactar: ${acciones.join(' &nbsp;·&nbsp; ')}</div>`
    : '';
  const cuerpo =
    '<div style="max-width:560px;margin:auto;border:1px solid #e6e2d8;border-radius:12px;overflow:hidden">' +
    '<div style="background:#536656;color:#fff;padding:16px 18px;font:bold 16px Arial,sans-serif">Nueva cotización web · Bamboo Seguros</div>' +
    `<table style="width:100%;border-collapse:collapse;background:#fff">${rowsHtml}</table>` +
    accionesHtml +
    '</div>';

  const apiKey = process.env.BREVO_API_KEY;
  if (!apiKey) {
    console.error('BREVO_API_KEY no configurada');
    return json({ ok: false }, 200); // el front ofrece continuar por WhatsApp
  }

  const payload = {
    sender: FROM,
    to: [{ email: TO_EMAIL, name: TO_NAME }],
    subject: `Nueva cotización web — ${tipo} — ${nombre}`,
    htmlContent: cuerpo,
  };
  if (emailOk) payload.replyTo = { email, name: nombre };

  let sent = false;
  try {
    const r = await fetch('https://api.brevo.com/v3/smtp/email', {
      method: 'POST',
      headers: { 'api-key': apiKey, 'content-type': 'application/json', accept: 'application/json' },
      body: JSON.stringify(payload),
    });
    sent = r.ok;
    if (!r.ok) console.error('Brevo error', r.status, await r.text());
  } catch (e) {
    console.error('Brevo fetch falló', e);
  }

  // 200 siempre que el lead sea válido: el correo es best-effort y el
  // front (landing.js) trata todo 2xx como éxito + continúa por WhatsApp.
  return json({ ok: sent }, 200);
};
