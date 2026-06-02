<?php
/* Versión de la app para detectar nuevos deploys (cache-busting).
   La versión = mtime más reciente de un set de archivos core. El deploy
   copia estos archivos → su mtime cambia → la versión cambia. No requiere
   tocar el script de deploy ni la BD. */
function bb_app_version() {
    $base = __DIR__;
    $files = array(
        $base . '/layout.php',
        $base . '/layout_end.php',
        $base . '/../assets/css/bamboo/components.css',
        $base . '/../assets/css/bamboo/tokens.css',
        $base . '/../assets/js/bamboo/version-check.js',
    );
    $v = 0;
    foreach ($files as $f) {
        $m = @filemtime($f);
        if ($m && $m > $v) { $v = $m; }
    }
    return (string) $v;
}
