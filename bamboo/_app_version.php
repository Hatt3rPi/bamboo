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

/* Etiqueta legible (fecha/hora del deploy en hora de Chile) para mostrar
   en el sidebar. No altera el timezone global del request. */
function bb_app_version_label() {
    $v = (int) bb_app_version();
    if ($v <= 0) { return ''; }
    try {
        $dt = new DateTime('@' . $v);
        $dt->setTimezone(new DateTimeZone('America/Santiago'));
        return $dt->format('d-m-Y H:i');
    } catch (Exception $e) {
        return '';
    }
}
