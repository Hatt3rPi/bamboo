<?php
/* Endpoint liviano de versión del deploy. NO requiere login ni BD —
   solo devuelve un identificador que cambia con cada deploy, para que
   el cliente detecte que hay una nueva versión y ofrezca recargar. */
require_once __DIR__ . '/_app_version.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
echo json_encode(array('v' => bb_app_version()));
