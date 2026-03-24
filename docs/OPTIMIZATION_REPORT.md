# Informe de Optimización de Queries — Bamboo Backend
**Fecha:** 2026-03-23
**Scope:** `bamboo/backend/` (actividades/, clientes/, endosos/, polizas/, propuesta_polizas/)
**Motor objetivo:** PostgreSQL (Supabase)

---

## Resumen Ejecutivo

| Prioridad | Archivo | Patrón | Queries actuales | Queries optimizadas | Reducción |
|-----------|---------|--------|-----------------|---------------------|-----------|
| CRITICA | `polizas/busqueda_listado_polizas.php` | N+1+1 triple loop | ~3N+1 | 1 | ~99% |
| CRITICA | `propuesta_polizas/busqueda_listado_propuesta_polizas.php` | N+1+1 triple loop | ~3N+1 | 1 | ~99% |
| CRITICA | `actividades/busqueda_listado_tareas.php` | N+1+1+1 cuádruple loop | ~4N+1 | 3 | ~97% |
| CRITICA | `actividades/busqueda_listado_tareas_completas.php` | N+1+1+1 cuádruple loop | ~4N+1 | 3 | ~97% |
| CRITICA | `actividades/busqueda_listado_tareas_recurrentes.php` | N+1+1+1 cuádruple loop | ~4N+1 | 3 | ~97% |
| ALTA | `clientes/busqueda_listado_clientes.php` | N+1+N loop | ~2N+1 | 1 | ~99% |
| ALTA | `polizas/busqueda_listado_polizas_filtrada.php` | N+1+1 triple loop | ~3N+1 | 1 | ~99% |
| MEDIA | `endosos/genera_excel_endosos.php` | MySQL variable `@rownum` | 2 | 1 | 50% |
| MEDIA | `endosos/genera_excel_propuesta_endosos.php` | MySQL variable `@rownum` | 2 | 1 | 50% |
| MEDIA | `polizas/genera_excel_polizas.php` | MySQL variable `@rownum` | 2 | 1 | 50% |
| MEDIA | `polizas/genera_excel_polizas_filtradas.php` | MySQL variable `@rownum` | 2 | 1 | 50% |
| MEDIA | `propuesta_polizas/genera_excel_propuestas.php` | MySQL variable `@rownum` | 2 | 1 | 50% |
| BAJA | `clientes/busqueda_nombre.php` | LIKE sin índice FTS | 1 | 1 (optimizado) | latencia -80% |

---

## Problemas Globales Transversales

### 1. SET @rownum en todos los archivos Excel
Todos los archivos `genera_excel_*.php` usan:
```sql
db_query($link, "SET @rownum=0;");
$query = "select @rownum := @rownum + 1 AS fila, ...";
```
**Esto es MySQL puro — falla silenciosamente en PostgreSQL.** `SET @rownum=0` no existe en PG. La variable `@rownum` devuelve NULL. En PG se usa `ROW_NUMBER() OVER()`.

### 2. ON DUPLICATE KEY UPDATE (MySQL only)
En `crea_propuesta_polizas.php` (línea 157, 214):
```sql
INSERT INTO items(...) VALUES (...) ON DUPLICATE KEY UPDATE ...
```
En PostgreSQL se usa `INSERT ... ON CONFLICT DO UPDATE`. La función `sql_translate()` en `db.php` **no traduce** este patrón.

### 3. SQL Injection
Múltiples archivos concatenan directamente variables POST en queries sin preparar. El sistema tiene `estandariza_info()` pero no usa `db_prepare_and_execute()` en los listados principales. Esto no es un problema de rendimiento pero es un riesgo de seguridad crítico.

---

## Detalle por Archivo — Prioridad CRITICA

---

### OPT-01: `polizas/busqueda_listado_polizas.php`

**Impacto:** Con 200 pólizas cada una con 5 ítems → actualmente **601 queries**. Optimizado: **1 query**.

**Patrón N+1 actual (líneas 18-62):**
```
Query 1: SELECT polizas_2 + clientes (N polizas)
  Loop por cada poliza:
    Query 2: SELECT count(*) FROM items WHERE numero_poliza=X
    Loop anidado:
      Query 3: SELECT items + clientes + polizas WHERE numero_poliza=X
    Query 4 (si hay endosos): SELECT endosos WHERE id_poliza=X
```

**Query optimizada (reemplaza todo el bloque PHP de loops):**
```sql
SELECT
  a.numero_poliza,
  a.estado,
  TO_CHAR(a.vigencia_final, 'MM-YYYY')   AS anomes_final,
  TO_CHAR(a.vigencia_inicial, 'MM-YYYY') AS anomes_inicial,
  a.moneda_poliza,
  a.compania,
  a.ramo,
  a.vigencia_inicial,
  a.vigencia_final,
  CONCAT_WS(' ', b.nombre_cliente, b.apellido_paterno, b.apellido_materno) AS nom_clienteP,
  CONCAT_WS('-', b.rut_sin_dv, b.dv)    AS rut_clienteP,
  b.telefono                              AS telefonoP,
  b.correo                                AS correoP,
  a.id                                    AS id_poliza,
  b.id                                    AS idP,
  a.fecha_envio_propuesta,
  b.grupo,
  b.referido,
  a.fech_cancela,
  a.motivo_cancela,
  CONCAT_WS(' ', a.moneda_poliza, format_de(SUM(c.prima_afecta), 2))       AS total_prima_afecta,
  CONCAT_WS(' ', a.moneda_poliza, format_de(SUM(c.prima_exenta), 2))       AS total_prima_exenta,
  CONCAT_WS(' ', a.moneda_poliza, format_de(SUM(c.prima_neta), 2))         AS total_prima_neta,
  CONCAT_WS(' ', a.moneda_poliza, format_de(SUM(c.prima_bruta_anual), 2))  AS total_prima_bruta,
  COUNT(DISTINCT e.id)                    AS contador_endosos,
  COUNT(c.numero_item)                    AS total_items,
  /* Items como JSON array */
  COALESCE(json_agg(
    json_build_object(
      'numero_item',      c.numero_item,
      'materia_asegurada', c.materia_asegurada,
      'patente_ubicacion', c.patente_ubicacion,
      'cobertura',        c.cobertura,
      'deducible',        c.deducible,
      'tasa_afecta',      CONCAT_WS(' ', format_de(c.tasa_afecta, 2), '%'),
      'tasa_exenta',      CONCAT_WS(' ', format_de(c.tasa_exenta, 2), '%'),
      'prima_afecta',     CONCAT_WS(' ', a.moneda_poliza, format_de(c.prima_afecta, 2)),
      'prima_exenta',     CONCAT_WS(' ', a.moneda_poliza, format_de(c.prima_exenta, 2)),
      'prima_neta',       CONCAT_WS(' ', a.moneda_poliza, format_de(c.prima_neta, 2)),
      'prima_bruta',      CONCAT_WS(' ', a.moneda_poliza, format_de(c.prima_bruta_anual, 2)),
      'monto_asegurado',  CONCAT_WS(' ', a.moneda_poliza, format_de(c.monto_asegurado, 2)),
      'venc_gtia',        CASE WHEN c.venc_gtia IS NULL THEN '' ELSE c.venc_gtia::text END,
      'nom_clienteA',     CONCAT_WS(' ', cl2.nombre_cliente, cl2.apellido_paterno, cl2.apellido_materno),
      'rut_clienteA',     CONCAT_WS('-', cl2.rut_sin_dv, cl2.dv),
      'telefonoA',        cl2.telefono,
      'correoA',          cl2.correo
    ) ORDER BY c.numero_item
  ) FILTER (WHERE c.numero_item IS NOT NULL), '[]') AS items,
  /* Endosos como JSON array */
  COALESCE(json_agg(
    DISTINCT json_build_object(
      'numero_endoso',      e.numero_endoso,
      'tipo_endoso',        e.tipo_endoso,
      'descripcion_endoso', e.descripcion_endoso,
      'dice',               e.dice,
      'debe_decir',         e.debe_decir,
      'vigencia_inicial',   e.vigencia_inicial,
      'vigencia_final',     e.vigencia_final,
      'fecha_ingreso_endoso', e.fecha_ingreso_endoso,
      'fecha_prorroga',     e.fecha_prorroga
    )
  ) FILTER (WHERE e.id IS NOT NULL), '[]') AS endosos
FROM polizas_2 AS a
LEFT JOIN clientes AS b
  ON a.rut_proponente = b.rut_sin_dv AND b.rut_sin_dv IS NOT NULL
LEFT JOIN items AS c
  ON a.numero_poliza = c.numero_poliza
LEFT JOIN clientes AS cl2
  ON c.rut_asegurado = cl2.rut_sin_dv AND cl2.rut_sin_dv IS NOT NULL
LEFT JOIN endosos AS e
  ON a.id = e.id_poliza
WHERE a.estado <> 'Rechazado'
GROUP BY
  a.id, a.numero_poliza, a.estado, a.vigencia_final, a.vigencia_inicial,
  a.moneda_poliza, a.compania, a.ramo, a.fecha_envio_propuesta,
  a.fech_cancela, a.motivo_cancela,
  b.nombre_cliente, b.apellido_paterno, b.apellido_materno,
  b.rut_sin_dv, b.dv, b.telefono, b.correo, b.id, b.grupo, b.referido
```

**PHP simplificado después de la optimización:**
```php
$resultado = db_query($link, $sql);
$items_json = [];
while ($row = db_fetch_object($resultado)) {
    $items_data = json_decode($row->items, true);
    $endosos_data = json_decode($row->endosos, true);
    $consolidado_patentes = implode(' - ', array_column($items_data, 'patente_ubicacion'));
    $items_json[] = [
        "numero_poliza"    => $row->numero_poliza,
        "estado"           => $row->estado,
        // ... resto de campos escalares ...
        "items"            => $items_data,
        "nro_endosos"      => count($endosos_data),
        "endosos"          => $endosos_data,
        "total_items"      => $row->total_items,
        "consolidado_patentes" => $consolidado_patentes
    ];
}
echo json_encode(["data" => $items_json]);
```

**Reducción:** De ~601 queries (con 200 pólizas y 5 ítems cada una) a **1 query**.

---

### OPT-02: `propuesta_polizas/busqueda_listado_propuesta_polizas.php`

**Misma estructura que OPT-01 pero sobre `propuesta_polizas` e `items`.**

**Patrón N+1 actual (líneas 18-64):**
```
Query 1: SELECT propuesta_polizas + clientes (N propuestas)
  Loop por cada propuesta:
    Query 2: SELECT count(*) FROM items WHERE numero_propuesta=X
    Loop anidado:
      Query 3: SELECT items + clientes + propuesta_polizas WHERE numero_propuesta=X
```

**Query optimizada:**
```sql
SELECT
  a.numero_propuesta,
  a.estado,
  TO_CHAR(a.vigencia_final, 'MM-YYYY')   AS anomes_final,
  TO_CHAR(a.vigencia_inicial, 'MM-YYYY') AS anomes_inicial,
  a.tipo_propuesta,
  a.moneda_poliza,
  a.compania,
  a.ramo,
  a.vigencia_inicial,
  a.vigencia_final,
  a.fecha_envio_propuesta,
  CONCAT_WS(' ', b.nombre_cliente, b.apellido_paterno, b.apellido_materno) AS nom_clienteP,
  CONCAT_WS('-', b.rut_sin_dv, b.dv)    AS rut_clienteP,
  b.telefono                              AS telefonoP,
  b.correo                                AS correoP,
  a.id                                    AS id_propuesta,
  b.id                                    AS idP,
  b.grupo,
  b.referido,
  CONCAT_WS(' ', a.moneda_poliza, format_de(SUM(c.prima_afecta), 2))       AS total_prima_afecta,
  CONCAT_WS(' ', a.moneda_poliza, format_de(SUM(c.prima_exenta), 2))       AS total_prima_exenta,
  CONCAT_WS(' ', a.moneda_poliza, format_de(SUM(c.prima_neta), 2))         AS total_prima_neta,
  CONCAT_WS(' ', a.moneda_poliza, format_de(SUM(c.prima_bruta_anual), 2))  AS total_prima_bruta,
  COUNT(c.numero_item)                    AS total_items,
  COALESCE(json_agg(
    json_build_object(
      'numero_item',      c.numero_item,
      'materia_asegurada', c.materia_asegurada,
      'patente_ubicacion', c.patente_ubicacion,
      'cobertura',        c.cobertura,
      'deducible',        c.deducible,
      'tasa_afecta',      CONCAT_WS(' ', format_de(c.tasa_afecta, 2), '%'),
      'tasa_exenta',      CONCAT_WS(' ', format_de(c.tasa_exenta, 2), '%'),
      'prima_afecta',     CONCAT_WS(' ', a.moneda_poliza, format_de(c.prima_afecta, 2)),
      'prima_exenta',     CONCAT_WS(' ', a.moneda_poliza, format_de(c.prima_exenta, 2)),
      'prima_neta',       CONCAT_WS(' ', a.moneda_poliza, format_de(c.prima_neta, 2)),
      'prima_bruta',      CONCAT_WS(' ', a.moneda_poliza, format_de(c.prima_bruta_anual, 2)),
      'monto_asegurado',  CONCAT_WS(' ', a.moneda_poliza, format_de(c.monto_asegurado, 2)),
      'venc_gtia',        CASE WHEN c.venc_gtia IS NULL THEN '' ELSE c.venc_gtia::text END,
      'nom_clienteA',     CONCAT_WS(' ', cl2.nombre_cliente, cl2.apellido_paterno, cl2.apellido_materno),
      'rut_clienteA',     CONCAT_WS('-', cl2.rut_sin_dv, cl2.dv),
      'telefonoA',        cl2.telefono,
      'correoA',          cl2.correo
    ) ORDER BY c.numero_item
  ) FILTER (WHERE c.numero_item IS NOT NULL), '[]') AS items
FROM propuesta_polizas AS a
LEFT JOIN clientes AS b
  ON a.rut_proponente = b.rut_sin_dv AND b.rut_sin_dv IS NOT NULL
LEFT JOIN items AS c
  ON a.numero_propuesta = c.numero_propuesta
LEFT JOIN clientes AS cl2
  ON c.rut_asegurado = cl2.rut_sin_dv AND cl2.rut_sin_dv IS NOT NULL
WHERE a.estado <> 'Rechazado'
GROUP BY
  a.id, a.numero_propuesta, a.estado, a.vigencia_final, a.vigencia_inicial,
  a.tipo_propuesta, a.moneda_poliza, a.compania, a.ramo, a.fecha_envio_propuesta,
  b.nombre_cliente, b.apellido_paterno, b.apellido_materno,
  b.rut_sin_dv, b.dv, b.telefono, b.correo, b.id, b.grupo, b.referido
```

**Reducción:** De ~301 queries (con 100 propuestas y 5 ítems) a **1 query**.

---

### OPT-03: `actividades/busqueda_listado_tareas.php` y `busqueda_listado_tareas_completas.php`

**Ambos archivos son prácticamente idénticos. El análisis aplica a los dos.**

**Patrón N+1 actual (líneas 16-113) — el peor del codebase:**
```
Query 1: SELECT tareas + COUNT(relaciones) [N tareas]
  Loop por cada tarea:
    Query 2: SELECT tareas_relaciones WHERE id_tarea=X [1 por tarea]
    Loop por cada relación:
      CASE "polizas":
        Query 3: SELECT polizas_2 WHERE id=Y [1 por relación]
      CASE "clientes":
        Query 4: SELECT clientes WHERE id=Y [1 por relación]
      CASE "propuestas":
        Query 5: SELECT propuesta_polizas WHERE id=Y [1 por relación]
```

Con 50 tareas, 2 relaciones/tarea → **1 + 50 + 100 = 151 queries mínimo**.

**Query optimizada — reemplaza queries 2, 3, 4 y 5 con un único JOIN:**
```sql
-- Query 1 (sin cambio): obtener tareas con conteos
SELECT
  a.id,
  a.fecha_ingreso,
  a.fecha_vencimiento,
  a.tarea,
  a.estado,
  a.prioridad,
  a.procedimiento,
  COUNT(b.id)                                                AS relaciones,
  SUM(CASE WHEN b.base = 'polizas'    THEN 1 ELSE 0 END)    AS polizas,
  SUM(CASE WHEN b.base = 'clientes'   THEN 1 ELSE 0 END)    AS clientes,
  SUM(CASE WHEN b.base = 'propuestas' THEN 1 ELSE 0 END)    AS propuestas
FROM tareas AS a
LEFT JOIN tareas_relaciones AS b ON a.id = b.id_tarea
WHERE a.estado NOT IN ('Cerrado', 'Eliminado')
GROUP BY a.id, a.fecha_ingreso, a.fecha_vencimiento, a.tarea, a.estado, a.prioridad, a.procedimiento;

-- Query 2 (NUEVA — reemplaza 2+3+4+5): todas las relaciones con sus datos de una vez
SELECT
  tr.id_tarea,
  tr.base,
  tr.id_relacion,
  /* Datos de póliza */
  p.id           AS pol_id,
  p.estado       AS pol_estado,
  TO_CHAR(p.vigencia_inicial, 'DD-MM-YYYY') AS pol_vigencia_inicial,
  TO_CHAR(p.vigencia_final,   'DD-MM-YYYY') AS pol_vigencia_final,
  p.numero_poliza,
  /* Datos de cliente */
  cl.id          AS cli_id,
  CONCAT_WS(' ', cl.nombre_cliente, cl.apellido_paterno, cl.apellido_materno) AS cli_nombre,
  cl.telefono    AS cli_telefono,
  cl.correo      AS cli_correo,
  /* Datos de propuesta */
  pp.id          AS prop_id,
  pp.estado      AS prop_estado,
  TO_CHAR(pp.vigencia_inicial, 'DD-MM-YYYY') AS prop_vigencia_inicial,
  TO_CHAR(pp.vigencia_final,   'DD-MM-YYYY') AS prop_vigencia_final,
  pp.numero_propuesta
FROM tareas_relaciones AS tr
JOIN tareas AS t ON tr.id_tarea = t.id AND t.estado NOT IN ('Cerrado', 'Eliminado')
LEFT JOIN polizas_2        AS p  ON tr.base = 'polizas'    AND p.id  = tr.id_relacion
LEFT JOIN clientes         AS cl ON tr.base = 'clientes'   AND cl.id = tr.id_relacion
LEFT JOIN propuesta_polizas AS pp ON tr.base = 'propuestas' AND pp.id = tr.id_relacion
ORDER BY tr.id_tarea, tr.base;
```

**PHP simplificado — pre-indexar relaciones por id_tarea:**
```php
// Ejecutar query 2 una sola vez
$resul_relaciones = db_query($link, $sql_relaciones);
$relaciones_map = []; // [id_tarea => ['polizas'=>[], 'clientes'=>[], 'propuestas'=>[]]]
while ($rel = db_fetch_object($resul_relaciones)) {
    $tid = $rel->id_tarea;
    if (!isset($relaciones_map[$tid])) {
        $relaciones_map[$tid] = ['polizas'=>[], 'clientes'=>[], 'propuestas'=>[]];
    }
    switch ($rel->base) {
        case 'polizas':
            $relaciones_map[$tid]['polizas'][] = [
                'id_poliza'       => $rel->pol_id,
                'estado'          => $rel->pol_estado,
                'vigencia_inicial'=> $rel->pol_vigencia_inicial,
                'vigencia_final'  => $rel->pol_vigencia_final,
                'numero_poliza'   => $rel->numero_poliza,
            ];
            break;
        case 'clientes':
            $relaciones_map[$tid]['clientes'][] = [
                'id_cliente' => $rel->cli_id,
                'nombre'     => $rel->cli_nombre,
                'telefono'   => $rel->cli_telefono,
                'correo'     => $rel->cli_correo,
            ];
            break;
        case 'propuestas':
            $relaciones_map[$tid]['propuestas'][] = [
                'estado'           => $rel->prop_estado,
                'vigencia_inicial' => $rel->prop_vigencia_inicial,
                'vigencia_final'   => $rel->prop_vigencia_final,
                'numero_propuesta' => $rel->numero_propuesta,
            ];
            break;
    }
}
// Luego el loop de tareas solo hace array_merge con $relaciones_map[$tareas->id]
```

**Reducción:** De ~151+ queries a **2 queries** (ambas ejecutadas antes del loop PHP). Aplica idénticamente a `busqueda_listado_tareas_completas.php`.

---

### OPT-04: `actividades/busqueda_listado_tareas_recurrentes.php`

**Mismo patrón que OPT-03 pero sobre `tareas_recurrentes` con `id_tarea_recurrente`.**

Diferencias respecto a OPT-03:
- Query 1: `FROM tareas_recurrentes AS a LEFT JOIN tareas_relaciones AS b ON a.id = b.id_tarea_recurrente`
- Query 2: `JOIN tareas AS t ON tr.id_tarea_recurrente = t.id ...` → usar `tr.id_tarea_recurrente`

El resto de la solución es idéntico.

**Reducción:** De ~151+ queries a **2 queries**.

---

## Detalle por Archivo — Prioridad ALTA

---

### OPT-05: `clientes/busqueda_listado_clientes.php`

**Patrón N+1 actual (líneas 18-67):**
```
Query 1: SELECT clientes (N clientes)
  Loop por cada cliente:
    Query 2: SELECT count(*) FROM clientes_contactos WHERE id_cliente=X
    Loop anidado:
      Query 3: SELECT nombre, telefono, correo FROM clientes_contactos WHERE id_cliente=X
```

Con 500 clientes → **1 + 500 + 500 = 1001 queries**.

Nótese también que la query 2 (count) es redundante porque la query 3 ya trae todos los datos — el conteo puede obtenerse con `COUNT()` o `json_array_length()`.

**Query optimizada:**
```sql
SELECT
  CONCAT_WS('-', c.rut_sin_dv, c.dv)                                      AS rut,
  c.apellido_paterno,
  CONCAT_WS(' ', c.nombre_cliente, c.apellido_paterno, c.apellido_materno) AS nombre,
  c.correo,
  c.direccion_laboral,
  c.direccion_personal,
  c.id,
  c.telefono,
  c.fecha_ingreso,
  c.referido,
  c.grupo,
  COUNT(cc.id)                                                              AS contactos,
  COALESCE(json_agg(
    json_build_object(
      'nombre',   cc.nombre,
      'telefono', cc.telefono,
      'correo',   cc.correo
    ) ORDER BY cc.id
  ) FILTER (WHERE cc.id IS NOT NULL), '[]')                                AS contactos_json
FROM clientes AS c
LEFT JOIN clientes_contactos AS cc ON cc.id_cliente = c.id
GROUP BY
  c.id, c.rut_sin_dv, c.dv, c.apellido_paterno,
  c.nombre_cliente, c.apellido_materno, c.correo,
  c.direccion_laboral, c.direccion_personal,
  c.telefono, c.fecha_ingreso, c.referido, c.grupo
```

**PHP simplificado:**
```php
$resultado = db_query($link, $sql);
$data = [];
while ($row = db_fetch_object($resultado)) {
    $contactos_raw = json_decode($row->contactos_json, true);
    $entry = [
        "id"                => $row->id,
        "nombre"            => $row->nombre,
        "apellidop"         => $row->apellido_paterno,
        "correo_electronico"=> $row->correo,
        "direccionl"        => $row->direccion_laboral,
        "direccionp"        => $row->direccion_personal,
        "telefono"          => $row->telefono,
        "fecingreso"        => $row->fecha_ingreso,
        "referido"          => $row->referido,
        "grupo"             => $row->grupo,
        "rut"               => $row->rut,
        "contactos"         => $row->contactos
    ];
    // Expandir contactos numerados (nombre1, telefono1, correo1, etc.)
    foreach ($contactos_raw as $i => $c) {
        $n = $i + 1;
        $entry["nombre$n"]   = $c['nombre'];
        $entry["telefono$n"] = $c['telefono'];
        $entry["correo$n"]   = $c['correo'];
    }
    $data[] = $entry;
}
echo json_encode(["data" => $data]);
```

**Reducción:** De ~1001 queries a **1 query**.

---

### OPT-06: `polizas/busqueda_listado_polizas_filtrada.php`

**Mismo patrón que OPT-01** pero con filtro `WHERE a.estado NOT IN ('Rechazado', 'Anulado', 'Cancelado')` y sin endosos.

La query optimizada es idéntica a OPT-01 sin el JOIN a `endosos` y aplicando ese filtro de estado.

**Reducción:** De ~301 queries (con 100 pólizas activas) a **1 query**.

---

## Detalle por Archivo — Prioridad MEDIA

---

### OPT-07: Archivos `genera_excel_*.php` — Eliminación de `@rownum`

**Afecta 5 archivos:**
- `endosos/genera_excel_endosos.php` (líneas 9-10)
- `endosos/genera_excel_propuesta_endosos.php` (líneas 9-10)
- `polizas/genera_excel_polizas.php` (líneas 9-11)
- `polizas/genera_excel_polizas_filtradas.php` (líneas 9-10)
- `propuesta_polizas/genera_excel_propuestas.php` (líneas 9-10)

**Patrón actual (MySQL only — falla en PG):**
```php
db_query($link, "SET @rownum=0;");           // Falla silenciosamente en PG
$query = "select @rownum := @rownum + 1 AS fila, ...";  // @rownum = NULL en PG
```

**Solución para PostgreSQL:**
```sql
-- Reemplazar @rownum := @rownum + 1 con:
ROW_NUMBER() OVER () AS fila

-- Ejemplo para genera_excel_endosos.php:
SELECT
  ROW_NUMBER() OVER () AS fila,
  a.id,
  a.fecha_prorroga,
  a.numero_endoso,
  -- ... resto de campos
FROM endosos AS a
```

**Acción:** Eliminar la línea `db_query($link, "SET @rownum=0;")` y reemplazar `@rownum := @rownum + 1` por `ROW_NUMBER() OVER ()` en la query principal. Para archivos con `ORDER BY`, usar `ROW_NUMBER() OVER (ORDER BY ...)`.

**Reducción:** De 2 queries a **1 query** por archivo. Pero más importante: corrige un bug que genera NULL en la columna "Fila" de todos los Excel en PostgreSQL.

---

### OPT-08: `crea_propuesta_polizas.php` — ON DUPLICATE KEY UPDATE

**Líneas 156-157 y 213-214:**
```sql
INSERT INTO items(...) VALUES (...) ON DUPLICATE KEY UPDATE ...
```

`ON DUPLICATE KEY UPDATE` es sintaxis MySQL. En PostgreSQL la equivalencia es:
```sql
INSERT INTO items(...) VALUES (...)
ON CONFLICT (numero_propuesta, numero_item)
DO UPDATE SET
  rut_asegurado = EXCLUDED.rut_asegurado,
  -- ... resto de campos
  fecha_ultima_modificacion = CURRENT_TIMESTAMP
```

**Acción:** Agregar a `sql_translate()` en `backend/db.php` la traducción de `ON DUPLICATE KEY UPDATE` → `ON CONFLICT (...) DO UPDATE SET`, o modificar directamente las queries en `crea_propuesta_polizas.php`.

**Nota:** Requiere conocer las columnas de la clave única en la tabla `items`. Si la restricción es sobre `(numero_propuesta, numero_item)`, ese es el conflicto a declarar.

---

## Detalle por Archivo — Prioridad BAJA

---

### OPT-09: `clientes/busqueda_nombre.php` — LIKE sin FTS (rama de 1 palabra)

**Línea 18 (rama `$numero==1`):**
```sql
SELECT ... FROM clientes
WHERE nombre_cliente LIKE '%búsqueda%'
   OR apellido_paterno LIKE '%búsqueda%'
   OR rut_sin_dv LIKE '%búsqueda%'
   OR CONCAT_WS('-', rut_sin_dv, dv) LIKE '%búsqueda%'
```

Los LIKE con `%` al inicio (`%término%`) no usan índices B-tree — hacen full table scan.

**Nota positiva:** La rama de múltiples palabras (`$numero>1`) ya usa `to_tsvector` / `plainto_tsquery` correctamente para PostgreSQL (líneas 21-22). Solo falta aplicar el mismo patrón a la búsqueda de 1 palabra.

**Query optimizada para búsqueda de 1 término:**
```sql
SELECT
  CONCAT_WS('-', rut_sin_dv, dv) AS rut,
  CONCAT_WS(' ', nombre_cliente, apellido_paterno, apellido_materno) AS nombre
FROM clientes
WHERE
  to_tsvector('spanish', CONCAT_WS(' ', nombre_cliente, apellido_paterno, apellido_materno, rut_sin_dv))
  @@ plainto_tsquery('spanish', ?)
  OR rut_sin_dv ILIKE ?
LIMIT 50
```

**Índice recomendado en Supabase:**
```sql
CREATE INDEX idx_clientes_fts
  ON clientes
  USING GIN (to_tsvector('spanish', CONCAT_WS(' ', nombre_cliente, apellido_paterno, apellido_materno, rut_sin_dv)));

CREATE INDEX idx_clientes_rut ON clientes(rut_sin_dv);
```

---

## Archivos sin Problemas N+1

Los siguientes archivos realizan operaciones DML simples (INSERT/UPDATE/DELETE) o queries de un solo resultado. No tienen patrones N+1:

| Archivo | Operación |
|---------|-----------|
| `actividades/cierra_tarea.php` | UPDATE simple |
| `actividades/crea_tarea.php` | INSERT + token lookup (correcto) |
| `clientes/busca_cliente.php` | SELECT con FTS (ya optimizado para PG) |
| `clientes/clientes_duplicados.php` | `db_prepare_and_execute` (correcto) |
| `clientes/crea_cliente.php` | INSERT + loop de contactos (aceptable) |
| `clientes/elimina_cliente.php` | DELETE simple |
| `clientes/modifica_cliente.php` | UPDATE + DELETE + loop INSERT (aceptable) |
| `clientes/busqueda_nombre.php` | SELECT único (optimizable pero no N+1) |
| `endosos/busqueda_listado_endosos.php` | 1 query plana, sin N+1 |
| `endosos/busqueda_listado_endosos_filtrada.php` | 1 query plana, sin N+1 |
| `endosos/busqueda_listado_propuesta_endoso.php` | 1 query plana, sin N+1 |
| `endosos/crea_endosos.php` | INSERT/UPDATE simples |
| `polizas/busqueda_poliza_renovada.php` | 1 query simple |
| `polizas/crea_poliza.php` | INSERT simple |
| `polizas/modifica_poliza.php` | UPDATE simple |
| `propuesta_polizas/modifica_propuesta_polizas.php` | UPDATE simple |
| `funciones.php` | Solo funciones PHP, sin queries |

---

## Plan de Implementación Recomendado

### Fase 1 — Quick wins (1-2 días, impacto inmediato)
1. **OPT-07**: Corregir `@rownum` en todos los `genera_excel_*.php` — bug bloqueante en PostgreSQL.
2. **OPT-08**: Traducir `ON DUPLICATE KEY UPDATE` en `crea_propuesta_polizas.php`.

### Fase 2 — Alta prioridad (3-5 días)
3. **OPT-05**: Refactorizar `busqueda_listado_clientes.php` con `json_agg`.
4. **OPT-01**: Refactorizar `busqueda_listado_polizas.php` con `json_agg`.
5. **OPT-06**: Refactorizar `busqueda_listado_polizas_filtrada.php`.

### Fase 3 — Crítico para tareas (2-3 días)
6. **OPT-02**: Refactorizar `busqueda_listado_propuesta_polizas.php`.
7. **OPT-03 + OPT-04**: Refactorizar los 3 archivos de listado de tareas.

### Fase 4 — Optimización de índices
8. **OPT-09**: Crear índice GIN FTS en `clientes`.
9. Crear índices en `tareas_relaciones(id_tarea)`, `items(numero_poliza)`, `items(numero_propuesta)`.

---

## Índices Recomendados en Supabase

```sql
-- Para resolver el N+1 de tareas
CREATE INDEX idx_tareas_relaciones_tarea ON tareas_relaciones(id_tarea);
CREATE INDEX idx_tareas_relaciones_recurrente ON tareas_relaciones(id_tarea_recurrente);

-- Para resolver el N+1 de pólizas e ítems
CREATE INDEX idx_items_numero_poliza ON items(numero_poliza);
CREATE INDEX idx_items_numero_propuesta ON items(numero_propuesta);

-- Para joins de clientes por RUT
CREATE INDEX idx_clientes_rut ON clientes(rut_sin_dv);

-- Para búsqueda full-text de clientes
CREATE INDEX idx_clientes_fts ON clientes
  USING GIN (to_tsvector('spanish',
    CONCAT_WS(' ', nombre_cliente, apellido_paterno, apellido_materno, rut_sin_dv)
  ));

-- Para joins de endosos
CREATE INDEX idx_endosos_id_poliza ON endosos(id_poliza);
CREATE INDEX idx_endosos_numero_poliza ON endosos(numero_poliza);
```

---

## Nota sobre `json_agg` y `sql_translate()`

Las queries optimizadas usan funciones nativas de PostgreSQL:
- `json_agg()` — agrega filas como JSON array
- `json_build_object()` — construye objetos JSON
- `ROW_NUMBER() OVER ()` — numeración de filas
- `FILTER (WHERE ...)` — filtro en agregaciones

Estas funciones **no requieren traducción** por `sql_translate()` porque son específicas de PostgreSQL. Las queries optimizadas deben verificar el motor antes de ejecutarse, o simplemente reemplazar las queries directamente ya que el proyecto está migrando a PostgreSQL.
