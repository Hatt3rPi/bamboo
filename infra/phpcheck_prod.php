<?php
/* ============================================================
   Diagnóstico one-shot para el deploy de la landing en el cPanel
   bamboose (Planet Hosting). COMPATIBLE PHP 5.6: debe poder correr
   ANTES de subir la versión de PHP del dominio.

   Uso: subirlo al docroot con un nombre con sufijo aleatorio y SIN
   guion bajo inicial (el .htaccess de la landing bloquea ^_), p.ej.
   bbcheck-k3x9.php, abrirlo en el navegador y BORRARLO al terminar.
   ============================================================ */
header('Content-Type: text/plain; charset=utf-8');

echo "PHP: " . PHP_VERSION . "\n";
echo "SAPI: " . php_sapi_name() . "\n";
echo "Server: " . (isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '?') . "\n";
echo "DOCUMENT_ROOT: " . (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '?') . "\n";
echo "ZipArchive: " . (class_exists('ZipArchive') ? 'OK' : 'NO') . "\n";
echo "mail(): " . (function_exists('mail') ? 'disponible' : 'NO disponible') . "\n";
echo "mbstring (mb_substr): " . (function_exists('mb_substr') ? 'OK' : 'NO (la landing tiene fallback)') . "\n";
$tmp = sys_get_temp_dir();
echo "temp dir: $tmp " . (is_writable($tmp) ? '(escribible — rate-limit del formulario OK)' : '(NO escribible)') . "\n";
echo "mod_rewrite/htaccess: se verifica navegando /nosotros tras el deploy\n";

echo "\nLa landing requiere PHP >= 7.4. Version actual: " . (version_compare(PHP_VERSION, '7.4', '>=') ? 'SUFICIENTE' : 'INSUFICIENTE — cambiar en MultiPHP antes de extraer la landing') . "\n";

/* Prueba de humo real de la sintaxis 7.4 que usa la landing (fn() y ??).
   Se hace vía archivo temporal: si el motor no la soporta, el include
   muere con Parse error — por eso va AL FINAL del script. */
echo "\nProbando sintaxis PHP 7.4 (si el output se corta aqui, el motor NO la soporta)...\n";
$probe = tempnam($tmp, 'bbchk');
file_put_contents($probe, '<?php return array_sum(array_map(fn($x) => $x ?? 0, array(1, 2, null)));');
$result = @include $probe;
@unlink($probe);
echo "Sintaxis 7.4 (fn/??): " . ($result === 3 ? 'OK — la landing correra en este motor' : 'FALLA') . "\n";
