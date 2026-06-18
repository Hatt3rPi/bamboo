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

    // ---- Acceso al portal interno (web de gestión) ----
    // Co-alojado: el login del portal vive en /backend/login/login.php del mismo dominio.
    // Si el portal queda en otro host, poner la URL absoluta aquí.
    'portal_login_url' => '/backend/login/login.php',

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
    'cmf_reg'       => '8404',                  // Reg. Auxiliares del Comercio de Seguros CMF (Cert. N° 585, 10-oct-2018)
    'cmf_url'       => 'https://www.cmfchile.cl/portal/principal/613/w3-channel.html',

    // ---- Redes (solo LinkedIn por decisión del cliente) ----
    'instagram'     => '',
    'facebook'      => '',
    'linkedin'      => 'https://www.linkedin.com/in/adriana-sandoval-819256149/',

    // ---- Analítica ----
    // Sin analítica por decisión del cliente. Para activar GA4 a futuro:
    // crear propiedad GA4 y poner aquí el ID 'G-XXXXXXXXXX' (carga gtag y mide cotizaciones).
    'ga4_id'        => '',

    // ---- Defaults SEO ----
    'default_title' => 'Bamboo Seguros | Corredora de Seguros — Cotiza Sin Compromiso',
    'default_desc'  => 'Corredora de seguros independiente en Chile. Comparamos por ti las aseguradoras del mercado: auto, vida, viaje, hogar, pymes y más. Cotiza hoy.',
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

/** Escape corto. */
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Texto de la credencial CMF — requisito legal: persona natural + nombre + código.
 *  (Bamboo es nombre de fantasía; quien opera es Adriana Sandoval como persona natural.) */
function cmf_label(): string {
    global $SITE;
    $base = e($SITE['founder']) . ' · corredora de seguros, persona natural inscrita en la CMF';
    return $SITE['cmf_reg'] !== ''
        ? $base . ' · Código CMF N° ' . e($SITE['cmf_reg'])
        : $base;
}
