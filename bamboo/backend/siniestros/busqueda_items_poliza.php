<?php
if (!isset($_SESSION)) {
    session_start();
}
require_once "/home/gestio10/public_html/backend/config.php";

function estandariza_info($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function parse_vehiculo($materia) {
    $out = ['marca' => '', 'modelo' => '', 'anio' => ''];
    if (!$materia) return $out;
    $texto = str_replace(['\\r\\n', '\\n', '\r'], "\n", $materia);

    // Formato A: campos etiquetados "Marca: X / Modelo: Y / Año: Z"
    if (preg_match('/Marca\s*:\s*(.+?)(?:\n|$)/iu', $texto, $m))  $out['marca']  = trim($m[1]);
    if (preg_match('/Modelo\s*:\s*(.+?)(?:\n|$)/iu', $texto, $m)) $out['modelo'] = trim($m[1]);
    if (preg_match('/A[ñn]o\s*:\s*(\d{4})/iu', $texto, $m))       $out['anio']   = $m[1];

    // Formato B (fallback): primera línea con "MARCA [MODELO...] AÑO".
    // Ej: "HYUNDAI ACCENT 2020" / "GREAT WALL POER DELUXE 2023"
    if ($out['marca'] === '' && $out['modelo'] === '' && $out['anio'] === '') {
        $primera_linea = trim(strtok($texto, "\n"));
        // Año = primer match de 4 dígitos entre 1980 y 2099
        if (preg_match('/\b(19[89]\d|20\d{2})\b/', $primera_linea, $ma)) {
            $out['anio'] = $ma[1];
            // Marca y modelo: lo que está antes del año
            $antes = trim(substr($primera_linea, 0, strpos($primera_linea, $ma[1])));
            $palabras = preg_split('/\s+/', $antes, -1, PREG_SPLIT_NO_EMPTY);
            // Marcas conocidas de 2 palabras (heurística pragmática)
            $marcas_compuestas = ['GREAT WALL','ALFA ROMEO','LAND ROVER','MERCEDES BENZ','MERCEDES-BENZ','ASTON MARTIN','ROLLS ROYCE','DFSK','JAC MOTORS'];
            $upper = strtoupper($antes);
            $matched = false;
            foreach ($marcas_compuestas as $mc) {
                if (strpos($upper, $mc) === 0) {
                    $out['marca']  = $mc;
                    $out['modelo'] = trim(substr($antes, strlen($mc)));
                    $matched = true;
                    break;
                }
            }
            if (!$matched && count($palabras) >= 1) {
                $out['marca']  = $palabras[0];
                $out['modelo'] = trim(implode(' ', array_slice($palabras, 1)));
            }
        }
    }
    return $out;
}

db_set_charset($link, 'utf8');
db_select_db($link, DB_NAME);

$id_poliza = estandariza_info($_REQUEST["id_poliza"] ?? '');

$data = array();
if ($id_poliza !== '') {
    $sql = "SELECT i.numero_item, i.materia_asegurada, i.patente_ubicacion
            FROM items i
            JOIN polizas_2 p ON p.numero_poliza = i.numero_poliza
            WHERE p.id = '$id_poliza'
            ORDER BY i.numero_item";
    $resultado = db_query($link, $sql);
    while ($row = db_fetch_object($resultado)) {
        $veh = parse_vehiculo($row->materia_asegurada);
        $data[] = array(
            "numero_item"       => $row->numero_item,
            "materia_asegurada" => $row->materia_asegurada,
            "patente_ubicacion" => $row->patente_ubicacion,
            "patente"           => $row->patente_ubicacion,
            "marca"             => $veh['marca'],
            "modelo"            => $veh['modelo'],
            "anio"              => $veh['anio']
        );
    }
}
db_close($link);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array("data" => $data));
?>
