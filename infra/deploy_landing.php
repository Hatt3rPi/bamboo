<?php
// Deploy de la LANDING pública CO-ALOJADA con el portal en redesign.customware.cl.
// La landing pasa a ser la raíz pública (index.php), y el portal sigue accesible
// bajo /bamboo, /backend/login, etc. — igual que el plan de producción en
// bambooseguros.cl ("todo a través de bambooseguros, sin subdominios").
//
// Vive en /home/customw2/public_html/deploy_landing.php (subir manual).
// Fuente: repo bamboo/infra/deploy_landing.php
//
// La landing es AUTOCONTENIDA (no usa BD ni backend/config.php del portal) y sus
// assets NO colisionan con los del portal (verificado: landing usa landing.css/
// landing.js/logo*/companies; el portal usa datatables/layout/bamboo/sort_*).
// La CSP del .htaccess está limitada a las rutas de la landing (no /bamboo,
// /backend, /vendor) para no romper el portal.

$src = "/home/customw2/repositories/bamboo";
$dst = "/home/customw2/public_html/bambooRedesign";  // MISMO docroot que el portal redesign

function run($cmd) { return shell_exec($cmd . " 2>&1"); }

$log = "";
$log .= run("cd $src && git fetch origin redesign && git checkout redesign && git pull origin redesign");

// Backup (idempotente) del index.php gateway del portal (por si hay que revertir).
if (is_file("$dst/index.php") && !is_file("$dst/index.php.portal-bak")) {
    @copy("$dst/index.php", "$dst/index.php.portal-bak");
}
// .htaccess: SOLO respaldar si existe uno que NO sea de la landing (preservar el del
// portal si lo hubiera). Evita el bug de respaldar nuestro propio .htaccess.
// La landing .htaccess es portal-safe (no toca /bamboo,/backend,/bambooQA,/vendor).
if (is_file("$dst/.htaccess") && !is_file("$dst/.htaccess.portal-bak")) {
    $cur = (string) @file_get_contents("$dst/.htaccess");
    if (strpos($cur, 'BAMBOO SEGUROS · LANDING') === false) {
        @copy("$dst/.htaccess", "$dst/.htaccess.portal-bak");
    }
}

// Copiar la landing al docroot (merge). index.php y .htaccess pasan a ser de la landing.
$log .= run("cp -R $src/landing/. $dst/");

echo "Deploy landing (co-alojada) OK - " . date("H:i:s") . "\n";
echo "Docroot: $dst\n";
echo "Backups del portal (si existían): index.php.portal-bak, .htaccess.portal-bak\n";
echo "Pasos a verificar manualmente:\n";
echo " 1) Si el .htaccess del portal tenía directivas LiteSpeed/timeout, fusionarlas en el nuevo.\n";
echo " 2) El login del portal (backend/login/login.php) debe redirigir tras login a /bamboo/ (no a /),\n";
echo "    ya que / ahora es la landing.\n";
echo " 3) deploy_redesign.php YA NO debe copiar index.php a la raíz (línea desactivada).\n";
echo "    Asegurate de que la copia del server de deploy_redesign.php tenga esa línea comentada,\n";
echo "    o este index.php de la landing se vuelve a pisar y la home cae al login.\n";
if (stripos($log, 'error') !== false || stripos($log, 'fatal') !== false || stripos($log, 'no such') !== false) {
    echo "\n--- Log (revisar) ---\n$log";
}
?>
