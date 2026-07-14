<?php
/* ============================================================
   Build estático de la landing para Netlify.

   Levanta `php -S` sobre landing/ y espeja cada ruta a HTML
   plano en landing/dist/ con URLs limpias (carpeta/index.html).
   Las requests van con Host: www.bambooseguros.cl para que
   config.php genere el modo PRODUCCIÓN (portal_login_url
   absoluto a gestionipn.cl).

   Uso:  php infra/build_landing_static.php
   (requiere PHP >= 7.4 en el PATH o pasar la ruta:
    php build_landing_static.php --php-bin="C:/ruta/php.exe")
   ============================================================ */

$repo    = dirname(__DIR__);
$landing = $repo . '/landing';
$dist    = $landing . '/dist';
$host    = '127.0.0.1:8199';

$phpBin = PHP_BINARY;
foreach ($argv as $arg) {
    if (strpos($arg, '--php-bin=') === 0) $phpBin = substr($arg, 10);
}

/* ---------- Rutas a renderizar ---------- */
// Los slugs de seguros son los nombres de archivo en landing/seguros/ (sin _template).
$slugs = [];
foreach (glob($landing . '/seguros/*.php') as $f) {
    $base = basename($f, '.php');
    if ($base[0] !== '_') $slugs[] = $base;
}
sort($slugs);

// request PHP => ruta de salida en dist/
// Archivos PLANOS (nosotros.html, no nosotros/index.html): las pretty URLs de
// Netlify sirven /nosotros -> nosotros.html directo SIN el 301 de trailing
// slash que agregan los directorios, calzando con las canónicas (sin slash).
$routes = [
    '/home.php'          => '/index.html',
    '/nosotros.php'      => '/nosotros.html',
    '/como-operamos.php' => '/como-operamos.html',
    '/contacto.php'      => '/contacto.html',
    '/faq.php'           => '/faq.html',
    '/seguros-pymes.php' => '/seguros-pymes.html',
    '/sitemap.php'       => '/sitemap.xml',
];
foreach ($slugs as $slug) {
    $routes["/seguros/$slug.php"] = "/seguros/$slug.html";
}

/* ---------- Servidor efímero ---------- */
echo "PHP: $phpBin\nServidor: http://$host (docroot landing/)\n";
$proc = proc_open(
    [$phpBin, '-S', $host, '-t', $landing],
    [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes, $landing
);
if (!is_resource($proc)) { fwrite(STDERR, "ERROR: no se pudo iniciar php -S\n"); exit(1); }

$fetch = function ($path) use ($host) {
    $ctx = stream_context_create(['http' => [
        'header'        => "Host: www.bambooseguros.cl\r\n",
        'timeout'       => 20,
        'ignore_errors' => true,
    ]]);
    $body = @file_get_contents("http://$host$path", false, $ctx);
    $status = 0;
    foreach ((array) ($http_response_header ?? []) as $h) {
        if (preg_match('#^HTTP/\S+\s+(\d+)#', $h, $m)) $status = (int) $m[1];
    }
    return [$status, $body];
};

// Esperar a que el server responda.
$up = false;
for ($i = 0; $i < 20; $i++) {
    usleep(250000);
    [$st] = $fetch('/robots.txt');
    if ($st === 200) { $up = true; break; }
}
if (!$up) { fwrite(STDERR, "ERROR: php -S no respondió\n"); proc_terminate($proc); exit(1); }

/* ---------- Limpiar y renderizar ---------- */
$rrmdir = function ($dir) use (&$rrmdir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = "$dir/$f";
        is_dir($p) ? $rrmdir($p) : unlink($p);
    }
    rmdir($dir);
};
$rrmdir($dist);
mkdir($dist, 0777, true);

$errors = 0;
foreach ($routes as $req => $out) {
    [$status, $body] = $fetch($req);
    if ($status !== 200 || $body === false || trim((string) $body) === '') {
        fwrite(STDERR, "FALLA $req (HTTP $status)\n");
        $errors++;
        continue;
    }
    $target = $dist . $out;
    if (!is_dir(dirname($target))) mkdir(dirname($target), 0777, true);
    file_put_contents($target, $body);
    echo sprintf("OK   %-32s -> %s (%d bytes)\n", $req, $out, strlen($body));
}

proc_terminate($proc);
proc_close($proc);

/* ---------- Estáticos ---------- */
$copyDir = function ($src, $dst) use (&$copyDir) {
    mkdir($dst, 0777, true);
    foreach (scandir($src) as $f) {
        if ($f === '.' || $f === '..') continue;
        is_dir("$src/$f") ? $copyDir("$src/$f", "$dst/$f") : copy("$src/$f", "$dst/$f");
    }
};
$copyDir($landing . '/assets', $dist . '/assets');
foreach (['robots.txt', 'llms.txt', 'site.webmanifest'] as $f) {
    copy("$landing/$f", "$dist/$f");
}
echo "OK   assets/ + robots.txt + llms.txt + site.webmanifest copiados\n";

/* ---------- Sanidad ---------- */
$home = (string) file_get_contents($dist . '/index.html');
$checks = [
    'portal absoluto (gestionipn.cl)' => strpos($home, 'https://gestionipn.cl/backend/login/login.php') !== false,
    'canónica www.bambooseguros.cl'   => strpos($home, 'https://www.bambooseguros.cl') !== false,
    'sin fugas localhost'             => strpos($home, '127.0.0.1') === false && stripos($home, 'localhost') === false,
    'sitemap.xml es XML'              => strpos((string) file_get_contents($dist . '/sitemap.xml'), '<urlset') !== false,
];
foreach ($checks as $name => $ok) {
    echo ($ok ? 'CHECK OK  ' : 'CHECK FAIL') . "  $name\n";
    if (!$ok) $errors++;
}

$total = iterator_count(new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dist, FilesystemIterator::SKIP_DOTS)
));
echo "\nBuild " . ($errors ? "CON $errors ERRORES" : 'OK') . " — $total archivos en landing/dist/\n";
exit($errors ? 1 : 0);
