<?php
// One-shot: fuerza el repo /home/customw2/repositories/bamboo a origin/master.
// Pensado para corregir el síntoma del 10-may-2026, donde el deploy.php quedó
// trayendo contenido del branch `redesign`. Después de usarlo, eliminar este
// archivo del repo (commit de borrado).
//
// Uso: GET https://customware.cl/bamboo/__fix_branch_one_shot.php?token=...

$expected = 'cbd6bc9d3bb5d2b08ab564f7d5305a4f62e9d6920c8ac79b72856955a6d804cf';
$got      = isset($_GET['token']) ? $_GET['token'] : '';

if (!hash_equals($expected, $got)) {
    http_response_code(403);
    echo "forbidden";
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

$repo = '/home/customw2/repositories/bamboo';

function r($cmd) {
    return trim((string)shell_exec($cmd . ' 2>&1'));
}

echo "Repo: $repo\n";
echo "BEFORE branch: " . r("cd $repo && git rev-parse --abbrev-ref HEAD") . "\n";
echo "BEFORE head:   " . r("cd $repo && git rev-parse HEAD") . "\n\n";

echo "git fetch origin --prune\n";
echo r("cd $repo && git fetch origin --prune") . "\n\n";

echo "git checkout master\n";
echo r("cd $repo && git checkout master") . "\n\n";

echo "git reset --hard origin/master\n";
echo r("cd $repo && git reset --hard origin/master") . "\n\n";

echo "AFTER branch: " . r("cd $repo && git rev-parse --abbrev-ref HEAD") . "\n";
echo "AFTER head:   " . r("cd $repo && git rev-parse HEAD") . "\n";
echo "\nDone.\n";
