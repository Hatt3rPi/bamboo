<?php
// Script de deploy para redesign.customware.cl
// Vive en /home/customw2/public_html/deploy_redesign.php y se actualiza
// manualmente (NO se copia automáticamente).
// Fuente: repo bamboo/infra/deploy_redesign.php — mantener ambos en sync.
//
// Diferencias vs deploy.php (QA):
//   - Pull de la rama 'redesign' (no master).
//   - Destino raíz: /home/customw2/public_html/bambooRedesign/ (DocumentRoot
//     del subdominio redesign.customware.cl).
//   - sed reemplaza /home/gestio10/public_html → /home/customw2/public_html/bambooRedesign
//     para que los includes hardcoded de config.php apunten al backend aislado
//     de redesign.

$src = "/home/customw2/repositories/bamboo";
$dst = "/home/customw2/public_html/bambooRedesign";

function run($cmd) {
    return shell_exec($cmd . " 2>&1");
}

$log = "";
$log .= run("cd $src && git fetch origin redesign && git checkout redesign && git pull origin redesign");
$log .= run("mkdir -p $dst/bamboo $dst/assets $dst/backend $dst/vendor");
$log .= run("cp -R $src/bamboo/. $dst/bamboo/");
$log .= run("cp -R $src/assets/. $dst/assets/");
$log .= run("cp $src/backend/db.php $dst/backend/");
$log .= run("cp -R $src/backend/login $dst/backend/");
$log .= run("cp -R $src/vendor/. $dst/vendor/");
// NO copiar index.php a la raíz: en el sitio unificado la raíz es la LANDING
// (deploy_landing.php pone su index.php). El portal se entra por /bamboo y el
// botón "Ingresar" -> /backend/login/login.php. Copiar el gateway del portal
// aquí pisaría la landing y mandaría la home al login.
// $log .= run("cp $src/index.php $dst/");   // <- desactivado a propósito

$count = 0;
$errores = 0;
foreach (["$dst/bamboo", "$dst/backend/login"] as $dir) {
    if (!is_dir($dir)) continue;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') continue;
        $path = $file->getPathname();
        if (!is_file($path) || !is_writable($path)) continue;
        $content = @file_get_contents($path);
        if ($content === false) continue;
        if (strpos($content, '/home/gestio10/public_html') !== false) {
            $nuevo = str_replace(
                '/home/gestio10/public_html',
                '/home/customw2/public_html/bambooRedesign',
                $content
            );
            @mkdir(dirname($path), 0755, true);
            if (@file_put_contents($path, $nuevo) !== false) {
                $count++;
            } else {
                $errores++;
            }
        }
    }
}
echo "Deploy redesign OK - $count rutas - $errores errores - " . date("H:i:s") . "\n";
if ($errores > 0) echo "\nLog:\n$log";
?>
