<?php
// Script de deploy para customware.cl (QA).
// Vive en /home/customw2/public_html/deploy.php y se actualiza manualmente
// (NO se copia automáticamente; este script no se deploya a sí mismo).
// Fuente: repo bamboo/infra/deploy.php — mantener ambos en sync.

$src = "/home/customw2/repositories/bamboo";
$dst = "/home/customw2/public_html";

function run($cmd) {
    return shell_exec($cmd . " 2>&1");
}

$log = "";
$log .= run("cd $src && git pull origin master");
$log .= run("mkdir -p $dst/bamboo $dst/bambooQA $dst/assets $dst/backend $dst/vendor");
$log .= run("cp -R $src/bamboo/. $dst/bamboo/");
$log .= run("cp -R $src/bambooQA/. $dst/bambooQA/");
$log .= run("cp -R $src/assets/. $dst/assets/");
$log .= run("cp $src/backend/db.php $dst/backend/");
$log .= run("cp -R $src/backend/login $dst/backend/");
$log .= run("cp -R $src/vendor/. $dst/vendor/");
$log .= run("cp $src/index.php $dst/");

$count = 0;
$errores = 0;
foreach (["$dst/bamboo", "$dst/bambooQA", "$dst/backend/login"] as $dir) {
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
            $nuevo = str_replace('/home/gestio10/public_html', '/home/customw2/public_html', $content);
            @mkdir(dirname($path), 0755, true);
            if (@file_put_contents($path, $nuevo) !== false) {
                $count++;
            } else {
                $errores++;
            }
        }
    }
}
echo "Deploy OK - $count rutas - $errores errores - " . date("H:i:s") . "\n";
if ($errores > 0) echo "\nLog:\n$log";
?>
