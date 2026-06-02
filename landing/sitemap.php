<?php
/* sitemap.php — sitemap dinámico (servido como /sitemap.xml vía .htaccess).
   Siempre refleja el catálogo de seguros. */
require_once __DIR__ . '/data/config.php';
require_once __DIR__ . '/data/seguros.php';
header('Content-Type: application/xml; charset=utf-8');

$base = rtrim($SITE['url'], '/');
$today = date('Y-m-d');

$urls = [
    ['/', '1.0'],
    ['/seguros-pymes', '0.9'],
    ['/como-operamos', '0.7'],
    ['/nosotros', '0.7'],
    ['/faq', '0.7'],
    ['/contacto', '0.6'],
];
foreach ($SEGUROS as $s) {
    $urls[] = ['/seguros/' . $s['slug'], '0.8'];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as [$loc, $prio]) {
    echo "  <url>\n";
    echo "    <loc>{$base}{$loc}</loc>\n";
    echo "    <lastmod>{$today}</lastmod>\n";
    echo "    <priority>{$prio}</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>' . "\n";
