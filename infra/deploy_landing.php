<?php
// Deploy de la LANDING pública (bambooseguros.cl) a un subdominio de staging
// en customware. Vive en /home/customw2/public_html/deploy_landing.php y se
// sube manualmente (NO se copia solo). Fuente: repo bamboo/infra/deploy_landing.php
//
// La landing es AUTOCONTENIDA: no usa la BD ni el backend/config.php del portal,
// y usa rutas relativas (__DIR__) + rutas web absolutas. Por eso NO necesita el
// sed de /home/gestio10 que sí hace deploy_redesign.php.
//
// IMPORTANTE: $dst debe ser el DocumentRoot del subdominio de la landing.
// Para subdominio "landing.customware.cl" cPanel suele usar public_html/landing.

$src = "/home/customw2/repositories/bamboo";
$dst = "/home/customw2/public_html/landingBamboo";  // ajustar al docroot real del subdominio

function run($cmd) { return shell_exec($cmd . " 2>&1"); }

$log = "";
$log .= run("cd $src && git fetch origin redesign && git checkout redesign && git pull origin redesign");
$log .= run("mkdir -p $dst");
// Copia el contenido de landing/ (incluye .htaccess y backend/.htaccess) al docroot.
$log .= run("cp -R $src/landing/. $dst/");

echo "Deploy landing OK - " . date("H:i:s") . "\n";
echo "Destino: $dst\n";
if (stripos($log, 'error') !== false || stripos($log, 'fatal') !== false || stripos($log, 'no such') !== false) {
    echo "\n--- Log (revisar) ---\n$log";
}
?>
