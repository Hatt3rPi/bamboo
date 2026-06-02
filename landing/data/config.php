<?php
/* ============================================================
   BAMBOO SEGUROS · LANDING · CONFIGURACIÓN GLOBAL
   Fuente única de verdad para NAP, marca, redes, SEO y contacto.
   Mantener IDÉNTICO al Google Business Profile y al schema (NAP).
   ------------------------------------------------------------
   ⚠️ CAMPOS POR CONFIRMAR CON ADRIANA (marcados con TODO):
     - cmf_reg  : número de inscripción en el Registro CMF
     - comuna / region / address : si atiende con/sin oficina física
     - social   : URLs reales de Instagram / Facebook / LinkedIn
     - ga4_id   : ID de la propiedad GA4 nueva (reemplaza la UA muerta)
   ============================================================ */

$SITE = [
    // ---- Identidad ----
    'name'        => 'Bamboo Seguros',
    'legal_name'  => 'Bamboo Seguros — Corredora de Seguros',
    'tagline'     => 'Asesoría y confianza',
    'founder'     => 'Adriana Sandoval Páez',
    'founder_role'=> 'Corredora de Seguros · Fundadora',

    // ---- URL canónica ----
    'url'         => 'https://www.bambooseguros.cl',
    'locale'      => 'es_CL',
    'lang'        => 'es-CL',

    // ---- Contacto (NAP) ----
    'phone_e164'    => '56995091193',          // sin +, sin espacios (para wa.me / tel: / schema)
    'phone_display' => '+569 9509 1193',
    'email'         => 'asandoval@bambooseguros.cl',

    // ---- Ubicación / área de servicio ----
    // TODO: confirmar si hay oficina física o es Service Area Business (sin dirección)
    'comuna'        => '',                      // TODO ej. 'Las Condes'
    'region'        => 'Región Metropolitana',  // TODO confirmar
    'address'       => '',                      // TODO calle y número (vacío = sin oficina pública)
    'area_served'   => 'Chile',

    // ---- Regulación ----
    'cmf_reg'       => '',                      // TODO N° de inscripción CMF (vacío = se muestra sin número)
    'cmf_url'       => 'https://www.cmfchile.cl/portal/principal/613/w3-channel.html',

    // ---- Redes ----
    // TODO: reemplazar por las URLs reales
    'instagram'     => '',                      // ej. https://www.instagram.com/bambooseguros
    'facebook'      => 'https://www.facebook.com/profile.php?id=328123681176499',
    'linkedin'      => '',

    // ---- Analítica ----
    'ga4_id'        => '',                      // TODO ej. 'G-XXXXXXXXXX' (vacío = no carga gtag)

    // ---- Defaults SEO ----
    'default_title' => 'Bamboo Seguros | Corredora de Seguros — Cotiza Gratis y Sin Compromiso',
    'default_desc'  => 'Corredora de seguros independiente en Chile. Comparamos +15 aseguradoras por ti y te asesoramos sin costo: auto, vida, viaje, hogar, pymes y más. Cotiza hoy.',
    // TODO: generar og-bamboo.jpg (1200x630) y apuntar aquí. Mientras, usamos el logo.
    'og_image'      => '/assets/img/logo.png',
];

/* ---------- Helpers ---------- */

/** Link wa.me con mensaje pre-rellenado (URL-encoded). */
function wa_link(string $msg = ''): string {
    global $SITE;
    $base = 'https://wa.me/' . $SITE['phone_e164'];
    if ($msg === '') {
        $msg = 'Hola Adriana, vengo de bambooseguros.cl y quiero cotizar un seguro.';
    }
    return $base . '?text=' . rawurlencode($msg);
}

/** Link de redirección propia para atribución (UTM → GA4). Cae a wa_link directo si no se usa. */
function wa_track(string $src, string $msg = ''): string {
    return '/wa.php?src=' . urlencode($src) . ($msg !== '' ? '&m=' . urlencode($msg) : '');
}

/** Escape corto. */
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Texto de la credencial CMF (con o sin número). */
function cmf_label(): string {
    global $SITE;
    return $SITE['cmf_reg'] !== ''
        ? 'Corredora inscrita en la CMF · Reg. N° ' . e($SITE['cmf_reg'])
        : 'Corredora de seguros inscrita en la CMF';
}
