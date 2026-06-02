<?php
/* ============================================================
   BAMBOO SEGUROS · partials/head.php
   Abre <html>…<head>…</head><body> + nav. Cada página define $page
   ANTES de incluir esto. Cierra con partials/footer.php.
   $page = ['title','desc','canonical'(path),'og_image','active','schema'(array de arrays JSON-LD)]
   ============================================================ */

require_once __DIR__ . '/../data/config.php';
require_once __DIR__ . '/../data/seguros.php';
require_once __DIR__ . '/icons.php';

$page      = $page ?? [];
$title     = $page['title'] ?? $SITE['default_title'];
$desc      = $page['desc']  ?? $SITE['default_desc'];
$path      = $page['canonical'] ?? '/';
$canonical = rtrim($SITE['url'], '/') . $path;
$ogimg     = rtrim($SITE['url'], '/') . ($page['og_image'] ?? $SITE['og_image']);
$active    = $page['active'] ?? '';

/* ---------- CSP (solo landing) ----------
   Se emite por PHP, NO por .htaccess: en LiteSpeed co-alojado el gating por env
   no es fiable y la CSP terminaba bloqueando el portal (jQuery/Bootstrap/DataTables).
   Así solo las páginas de la landing reciben CSP. */
if (!headers_sent()) {
    $csp = "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; "
         . "img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
         . "font-src 'self' https://fonts.gstatic.com; "
         . "script-src 'self'" . ($SITE['ga4_id'] !== '' ? " 'unsafe-inline' https://www.googletagmanager.com" : "") . "; "
         . "connect-src 'self'" . ($SITE['ga4_id'] !== '' ? " https://www.google-analytics.com https://www.googletagmanager.com" : "") . "; "
         . "form-action 'self'";
    header('Content-Security-Policy: ' . $csp);
}

/* ---------- Schema sitewide: InsuranceAgency ---------- */
$services = [];
foreach ($SEGUROS as $svc) {   // NO usar $s: la plantilla de ramo lo necesita intacto
    $services[] = [
        '@type' => 'Offer',
        'itemOffered' => [
            '@type' => 'Service',
            'name' => $svc['nombre'],
            'serviceType' => $svc['nombre'],
            'url' => $SITE['url'] . '/seguros/' . $svc['slug'],
        ],
    ];
}
$sameAs = array_values(array_filter([$SITE['instagram'], $SITE['facebook'], $SITE['linkedin']]));

$founder = ['@type' => 'Person', 'name' => $SITE['founder'], 'jobTitle' => 'Corredora de Seguros'];
if ($SITE['cmf_reg'] !== '') {
    $founder['hasCredential'] = [
        '@type' => 'EducationalOccupationalCredential',
        'credentialCategory' => 'Inscripción en el Registro de Corredores de Seguros de la CMF',
        'identifier' => $SITE['cmf_reg'],
    ];
}

$org = [
    '@context' => 'https://schema.org',
    '@type' => 'InsuranceAgency',
    '@id' => $SITE['url'] . '/#organization',
    'name' => $SITE['name'],
    'legalName' => $SITE['legal_name'],
    'url' => $SITE['url'] . '/',
    'logo' => $SITE['url'] . '/assets/img/logo.png',
    'image' => $ogimg,
    'description' => $SITE['default_desc'],
    'slogan' => $SITE['tagline'],
    'telephone' => '+' . $SITE['phone_e164'],
    'email' => $SITE['email'],
    'areaServed' => ['@type' => 'Country', 'name' => 'Chile'],
    'knowsAbout' => array_values(array_map(fn($s) => $s['nombre'], $SEGUROS)),
    'founder' => $founder,
    'contactPoint' => [
        '@type' => 'ContactPoint',
        'telephone' => '+' . $SITE['phone_e164'],
        'contactType' => 'sales',
        'availableLanguage' => ['es-CL'],
    ],
    'hasOfferCatalog' => [
        '@type' => 'OfferCatalog',
        'name' => 'Seguros que intermediamos',
        'itemListElement' => $services,
    ],
];
if ($sameAs) $org['sameAs'] = $sameAs;
if ($SITE['comuna'] !== '') {
    $org['address'] = array_filter([
        '@type' => 'PostalAddress',
        'streetAddress' => $SITE['address'] ?: null,
        'addressLocality' => $SITE['comuna'],
        'addressRegion' => $SITE['region'],
        'addressCountry' => 'CL',
    ]);
}

$website = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    '@id' => $SITE['url'] . '/#website',
    'url' => $SITE['url'] . '/',
    'name' => $SITE['name'],
    'inLanguage' => 'es-CL',
    'publisher' => ['@id' => $SITE['url'] . '/#organization'],
];

$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP;
$schemaBlocks = array_merge([$org, $website], $page['schema'] ?? []);
?>
<!DOCTYPE html>
<html lang="<?= e($SITE['lang']) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($desc) ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <link rel="alternate" hreflang="es-cl" href="<?= e($canonical) ?>">
  <link rel="alternate" hreflang="x-default" href="<?= e($canonical) ?>">
  <meta name="robots" content="index,follow,max-image-preview:large">
  <meta name="theme-color" content="#536656">
  <meta name="author" content="<?= e($SITE['founder']) ?>">

  <!-- Open Graph -->
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= e($SITE['name']) ?>">
  <meta property="og:locale" content="<?= e($SITE['locale']) ?>">
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($desc) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:image" content="<?= e($ogimg) ?>">
  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($title) ?>">
  <meta name="twitter:description" content="<?= e($desc) ?>">
  <meta name="twitter:image" content="<?= e($ogimg) ?>">

  <!-- Favicons (TODO: generar set raster favicon.ico / apple-touch-icon.png 180 / icon-192 / icon-512) -->
  <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
  <link rel="manifest" href="/site.webmanifest">

  <!-- Fuentes -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,500;9..144,600&family=Inter:wght@400;500;600;700&family=Varela+Round&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/assets/css/landing.css">

<?php foreach ($schemaBlocks as $block): ?>
  <script type="application/ld+json"><?= json_encode($block, $jsonFlags) ?></script>
<?php endforeach; ?>

<?php if ($SITE['ga4_id'] !== ''): ?>
  <!-- GA4 (reemplaza la Universal Analytics descontinuada) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($SITE['ga4_id']) ?>"></script>
  <script>
    window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}
    gtag('js',new Date());gtag('config','<?= e($SITE['ga4_id']) ?>');
  </script>
<?php endif; ?>
</head>
<body data-wa="<?= e($SITE['phone_e164']) ?>" data-ga="<?= $SITE['ga4_id'] !== '' ? '1' : '' ?>">
<a href="#main" class="skip-link">Saltar al contenido</a>
<?php require __DIR__ . '/header.php'; ?>
<main id="main">
