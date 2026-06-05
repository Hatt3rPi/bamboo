-- Corrige mojibake (doble-encoding UTF-8) en propuesta_polizas.ramo.
-- 65 filas, 7 valores distintos. Ej: 'VEH - VehÃ­culos Particulares' -> 'VEH - Vehículos Particulares'.
-- Conversión validada en vista previa; coincide con el <select> del front y con polizas_2.ramo (sano).
-- Solo afecta filas con mojibake (LIKE '%Ã%'). El front actual ya inserta UTF-8 correcto.
BEGIN;
UPDATE propuesta_polizas
SET ramo = convert_from(convert_to(ramo,'LATIN1'),'UTF8')
WHERE ramo LIKE '%Ã%';
COMMIT;
