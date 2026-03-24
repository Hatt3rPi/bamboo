# Mejores Prácticas: PHP + Supabase (PostgreSQL) — Velocidad y Eficiencia

> Documento generado: 2026-03-23
> Contexto: App PHP vanilla conectada a Supabase PostgreSQL via Supavisor (pooler).
> Versión Supavisor: 1.0+ | PostgreSQL: 15/16 | PHP: 8.x

---

## Indice

1. [Conexión eficiente a Supabase desde PHP](#1-conexión-eficiente-a-supabase-desde-php)
2. [Optimización de queries](#2-optimización-de-queries)
3. [Patrones PHP para PostgreSQL](#3-patrones-php-para-postgresql)
4. [Caché y performance](#4-caché-y-performance)
5. [Supabase-specific](#5-supabase-specific)
6. [Anti-patrones a evitar](#6-anti-patrones-a-evitar)
7. [Diagnóstico rápido: checklist](#7-diagnóstico-rápido-checklist)

---

## 1. Conexión eficiente a Supabase desde PHP

### 1.1 Supavisor: Session Mode vs Transaction Mode

Supabase expone dos modos de pooler a través de **Supavisor** (su pooler nativo en Elixir):

| Característica             | Session Mode (puerto 5432) | Transaction Mode (puerto 6543) |
|----------------------------|----------------------------|--------------------------------|
| Conexión dedicada          | Si — por cliente           | No — se comparte por query     |
| Prepared statements        | Soportados                 | **NO soportados**              |
| Concurrencia               | Menor (1 conn por cliente) | Mayor (reutiliza conns)        |
| Ideal para                 | Apps con sesiones largas   | Serverless / alta concurrencia |
| SET LOCAL / search_path    | Soportado                  | Limitado                       |
| LISTEN / NOTIFY            | Soportado                  | No soportado                   |

> **Cambio importante (febrero 2025):** Supabase deprecó Session Mode en el puerto 6543.
> El puerto 6543 ahora es exclusivamente Transaction Mode.
> El puerto 5432 sigue siendo Session Mode.

**Para esta app PHP (servidor persistente + cPanel):** usar **Session Mode (5432)** es la elección correcta porque:
- Se reutiliza la misma conexión durante el request.
- Permite prepared statements con `pg_prepare` / `pg_execute`.
- Soporta `SET search_path` y variables de sesión.

### 1.2 `pg_connect` vs `pg_pconnect`

```php
// pg_connect: abre una nueva conexion por cada request
$link = pg_connect("host=... dbname=...");

// pg_pconnect: reutiliza conexiones persistentes del proceso PHP-FPM
$link = pg_pconnect("host=... dbname=...");
```

**`pg_pconnect` en entornos PHP-FPM / cPanel:**
- PHP-FPM mantiene procesos worker vivos entre requests.
- `pg_pconnect` reutiliza la conexión del proceso si los parámetros son idénticos.
- Reduce el overhead de TLS handshake + autenticación (~5-15ms por request).
- **Riesgo:** si la conexión queda en estado sucio (transacción abierta), el siguiente request la hereda. Mitigar con `pg_connection_reset()` al inicio.

```php
// Patron seguro con pg_pconnect
function db_connect_safe(string $conn_string): \PgSql\Connection {
    $link = pg_pconnect($conn_string);
    if (!$link) {
        throw new \RuntimeException("No se pudo conectar a PostgreSQL");
    }
    // Resetear estado de la conexion reutilizada
    $status = pg_connection_status($link);
    if ($status === PGSQL_CONNECTION_BAD) {
        pg_connection_reset($link);
    }
    // Asegurarse que no hay transaccion pendiente
    $tx = pg_transaction_status($link);
    if ($tx === PGSQL_TRANSACTION_INERROR || $tx === PGSQL_TRANSACTION_INTRANS) {
        pg_query($link, "ROLLBACK");
    }
    return $link;
}
```

### 1.3 String de conexión óptimo para Supabase

```php
// Recomendado: Session Mode via pooler (IPv4 compatible)
$conn_string = implode(' ', [
    'host=aws-0-us-east-1.pooler.supabase.com',   // pooler IPv4
    'port=5432',                                    // Session Mode
    'dbname=postgres',
    'user=postgres.TU_PROJECT_REF',                // usuario con project ref
    'password=' . PG_PASSWORD,
    'sslmode=require',                             // SSL obligatorio
    'connect_timeout=10',                           // timeout de conexion
    'application_name=bamboo_php',                 // identificacion en logs
    'options=--search_path=public',                // schema por defecto
]);

$link = pg_pconnect($conn_string);
```

**Parámetros clave:**

| Parámetro          | Valor recomendado    | Por qué                                          |
|--------------------|----------------------|--------------------------------------------------|
| `sslmode`          | `require`            | Supabase exige SSL; `verify-full` es más seguro  |
| `connect_timeout`  | `10`                 | Evita requests colgados                          |
| `application_name` | nombre de tu app     | Visible en `pg_stat_activity` para diagnóstico   |
| `options`          | `--search_path=public` | Evita calificar tabla con schema en cada query |

### 1.4 Singleton de conexión dentro de un request

En PHP vanilla (sin framework), la forma más sencilla de no abrir múltiples conexiones en el mismo request es usar una variable global o un singleton estático:

```php
// backend/db.php — patron singleton para el request actual
function db_get_connection(): \PgSql\Connection {
    static $link = null;

    if ($link !== null && pg_connection_status($link) === PGSQL_CONNECTION_OK) {
        return $link;
    }

    $conn_string = build_conn_string(); // tu funcion con los parametros
    $link = pg_pconnect($conn_string);

    if (!$link) {
        error_log("[DB] Fallo conexion PostgreSQL");
        http_response_code(503);
        die(json_encode(['error' => 'Servicio no disponible']));
    }

    // Limpiar estado si es conexion reutilizada
    $tx = pg_transaction_status($link);
    if (in_array($tx, [PGSQL_TRANSACTION_INERROR, PGSQL_TRANSACTION_INTRANS])) {
        pg_query($link, "ROLLBACK");
    }

    return $link;
}
```

---

## 2. Optimización de queries

### 2.1 El problema N+1 en esta app (caso real)

El patrón más crítico encontrado en `busqueda_listado_clientes.php` y `busqueda_listado_polizas.php`:

```php
// ANTI-PATRON ACTUAL: N+1 queries
$resultado = db_query($link, "SELECT id, nombre FROM clientes");
while ($row = db_fetch_object($resultado)) {
    // 1 query extra por cada cliente => N queries adicionales
    $contactos = db_query($link, "SELECT * FROM clientes_contactos WHERE id_cliente='{$row->id}'");
    // ...procesar contactos
}
// Si hay 200 clientes => 1 + 200 = 201 queries al servidor
```

**Solución: `json_agg` + un solo JOIN**

```sql
-- Una sola query que trae clientes con sus contactos embebidos como JSON
SELECT
    c.id,
    CONCAT_WS(' ', c.nombre_cliente, c.apellido_paterno, c.apellido_materno) AS nombre,
    CONCAT_WS('-', c.rut_sin_dv, c.dv) AS rut,
    c.correo,
    c.telefono,
    c.fecha_ingreso,
    c.referido,
    c.grupo,
    COALESCE(
        json_agg(
            json_build_object(
                'nombre',   cc.nombre,
                'telefono', cc.telefono,
                'correo',   cc.correo
            ) ORDER BY cc.id
        ) FILTER (WHERE cc.id IS NOT NULL),
        '[]'::json
    ) AS contactos
FROM clientes c
LEFT JOIN clientes_contactos cc ON cc.id_cliente = c.id
GROUP BY c.id, c.nombre_cliente, c.apellido_paterno, c.apellido_materno,
         c.rut_sin_dv, c.dv, c.correo, c.telefono,
         c.fecha_ingreso, c.referido, c.grupo
ORDER BY c.apellido_paterno;
```

```php
// PHP: una sola query, leer contactos del JSON
$result = pg_query($link, $sql);
$data = [];
while ($row = pg_fetch_object($result)) {
    $contactos = json_decode($row->contactos, true); // array de contactos
    $data[] = [
        'id'       => $row->id,
        'nombre'   => $row->nombre,
        'rut'      => $row->rut,
        'contactos' => $contactos,
    ];
}
echo json_encode(['data' => $data]);
```

**Resultado:** de 201 queries a 1. Reducción de latencia proporcional a N.

### 2.2 Refactorizando `busqueda_listado_polizas.php` (caso polizas + items + endosos)

El archivo actual ejecuta 3 queries por póliza (1 principal + 1 count items + 1 items + 1 endosos) = hasta 4N+1 queries.

```sql
-- Query unificada: polizas con items y endosos en JSON
WITH polizas_base AS (
    SELECT
        p.id                  AS id_poliza,
        p.numero_poliza,
        p.estado,
        p.moneda_poliza,
        p.compania,
        p.ramo,
        p.vigencia_inicial,
        p.vigencia_final,
        TO_CHAR(p.vigencia_final,   'MM-YYYY') AS anomes_final,
        TO_CHAR(p.vigencia_inicial, 'MM-YYYY') AS anomes_inicial,
        p.fech_cancela,
        p.motivo_cancela,
        p.fecha_envio_propuesta,
        -- Sumas de prima
        SUM(i.prima_afecta)     AS total_prima_afecta_raw,
        SUM(i.prima_exenta)     AS total_prima_exenta_raw,
        SUM(i.prima_neta)       AS total_prima_neta_raw,
        SUM(i.prima_bruta_anual) AS total_prima_bruta_raw,
        -- Cliente proponente
        CONCAT_WS(' ', c.nombre_cliente, c.apellido_paterno, c.apellido_materno) AS nom_clienteP,
        CONCAT_WS('-', c.rut_sin_dv, c.dv)   AS rut_clienteP,
        c.telefono   AS telefonoP,
        c.correo     AS correoP,
        c.id         AS idP,
        c.grupo,
        c.referido,
        COUNT(DISTINCT e.id)   AS contador_endosos
    FROM polizas_2 p
    LEFT JOIN clientes c
        ON p.rut_proponente = c.rut_sin_dv AND c.rut_sin_dv IS NOT NULL
    LEFT JOIN items i
        ON p.numero_poliza = i.numero_poliza
    LEFT JOIN endosos e
        ON p.id = e.id_poliza
    WHERE p.estado <> 'Rechazado'
    GROUP BY p.id, p.numero_poliza, p.estado, p.moneda_poliza, p.compania, p.ramo,
             p.vigencia_inicial, p.vigencia_final, p.fech_cancela, p.motivo_cancela,
             p.fecha_envio_propuesta,
             c.nombre_cliente, c.apellido_paterno, c.apellido_materno,
             c.rut_sin_dv, c.dv, c.telefono, c.correo, c.id, c.grupo, c.referido
),
items_json AS (
    SELECT
        i.numero_poliza,
        json_agg(
            json_build_object(
                'numero_item',      i.numero_item,
                'materia_asegurada', i.materia_asegurada,
                'patente_ubicacion', i.patente_ubicacion,
                'cobertura',        i.cobertura,
                'prima_afecta',     i.prima_afecta,
                'prima_exenta',     i.prima_exenta,
                'prima_neta',       i.prima_neta,
                'prima_bruta',      i.prima_bruta_anual,
                'monto_asegurado',  i.monto_asegurado
            ) ORDER BY i.numero_item
        ) AS items
    FROM items i
    GROUP BY i.numero_poliza
),
endosos_json AS (
    SELECT
        e.id_poliza,
        json_agg(
            json_build_object(
                'numero_endoso',       e.numero_endoso,
                'tipo_endoso',         e.tipo_endoso,
                'descripcion_endoso',  e.descripcion_endoso,
                'dice',                e.dice,
                'debe_decir',          e.debe_decir,
                'vigencia_inicial',    e.vigencia_inicial,
                'vigencia_final',      e.vigencia_final,
                'fecha_ingreso_endoso', e.fecha_ingreso_endoso
            ) ORDER BY
                CASE WHEN e.numero_endoso ~ '^[0-9]+$'
                     THEN e.numero_endoso::integer ELSE 999999999 END,
                e.numero_endoso
        ) AS endosos
    FROM endosos e
    GROUP BY e.id_poliza
)
SELECT
    pb.*,
    COALESCE(ij.items,   '[]'::json) AS items,
    COALESCE(ej.endosos, '[]'::json) AS endosos
FROM polizas_base pb
LEFT JOIN items_json   ij ON ij.numero_poliza = pb.numero_poliza
LEFT JOIN endosos_json ej ON ej.id_poliza     = pb.id_poliza
ORDER BY pb.vigencia_final DESC;
```

```php
// PHP simplificado con la query unificada
$result = pg_query($link, $sql_unificado);
$output = ['data' => []];
while ($row = pg_fetch_object($result)) {
    $output['data'][] = [
        'numero_poliza'       => $row->numero_poliza,
        'estado'              => $row->estado,
        'moneda_poliza'       => $row->moneda_poliza,
        'vigencia_inicial'    => $row->vigencia_inicial,
        'vigencia_final'      => $row->vigencia_final,
        'compania'            => $row->compania,
        'ramo'                => $row->ramo,
        'nom_clienteP'        => $row->nom_clienteP,
        'rut_clienteP'        => $row->rut_clienteP,
        'total_prima_bruta'   => $row->total_prima_bruta_raw,
        'items'               => json_decode($row->items, true),
        'endosos'             => json_decode($row->endosos, true),
        'contador_endosos'    => $row->contador_endosos,
    ];
}
echo json_encode($output);
```

**Antes:** 1 + 3N queries (N = cantidad de pólizas). Con 100 pólizas: 301 queries.
**Después:** 1 query total.

### 2.3 CTEs (Common Table Expressions) para legibilidad y performance

Las CTEs no siempre son más rápidas que subqueries (PostgreSQL puede materialization-forzarlas), pero para queries complejas mejoran la legibilidad y en muchos casos el planner las optimiza correctamente.

```sql
-- CTE para resumen de actividades por usuario
WITH tareas_resumen AS (
    SELECT
        asignado_a,
        COUNT(*) FILTER (WHERE estado = 'Pendiente') AS pendientes,
        COUNT(*) FILTER (WHERE estado = 'Completada') AS completadas,
        MIN(fecha_vencimiento) FILTER (WHERE estado = 'Pendiente') AS proxima_fecha
    FROM tareas
    WHERE fecha_vencimiento >= CURRENT_DATE - INTERVAL '30 days'
    GROUP BY asignado_a
)
SELECT
    u.nombre,
    u.correo,
    COALESCE(tr.pendientes, 0)   AS tareas_pendientes,
    COALESCE(tr.completadas, 0)  AS tareas_completadas,
    tr.proxima_fecha
FROM usuarios u
LEFT JOIN tareas_resumen tr ON tr.asignado_a = u.id
ORDER BY tr.pendientes DESC NULLS LAST;
```

> **Tip PostgreSQL 12+:** Usa `WITH ... AS MATERIALIZED` para forzar materialización cuando
> la CTE se referencia múltiples veces y el resultado es pequeño. Usa `AS NOT MATERIALIZED`
> para permitir que el planner la integre en el plan principal.

### 2.4 Batch inserts (INSERT de múltiples filas)

```php
// ANTI-PATRON: INSERT fila por fila en un loop
foreach ($polizas as $p) {
    pg_query($link, "INSERT INTO polizas_2 (...) VALUES (...)"); // N roundtrips
}

// PATRON CORRECTO: INSERT con multiples VALUES
function batch_insert(
    \PgSql\Connection $link,
    string $table,
    array $columns,
    array $rows,
    int $chunk_size = 500
): void {
    if (empty($rows)) return;

    $col_list = implode(', ', $columns);
    $chunks   = array_chunk($rows, $chunk_size);

    foreach ($chunks as $chunk) {
        $placeholders = [];
        $params       = [];
        $param_index  = 1;

        foreach ($chunk as $row) {
            $row_placeholders = [];
            foreach ($row as $value) {
                $row_placeholders[] = '$' . $param_index++;
                $params[]           = $value;
            }
            $placeholders[] = '(' . implode(', ', $row_placeholders) . ')';
        }

        $sql = "INSERT INTO {$table} ({$col_list}) VALUES "
             . implode(', ', $placeholders);

        $result = pg_query_params($link, $sql, $params);
        if ($result === false) {
            throw new \RuntimeException("Batch insert falló: " . pg_last_error($link));
        }
    }
}

// Uso
batch_insert($link, 'items', ['numero_poliza', 'numero_item', 'prima_afecta'], [
    ['POL-001', 1, 150000],
    ['POL-001', 2, 200000],
    ['POL-002', 1, 75000],
    // ... hasta 500 por chunk
]);
```

**Alternativa con COPY para cargas masivas (>10k filas):**

```php
// pg_copy_from es el metodo mas rapido para carga masiva
$rows = [];
foreach ($data as $item) {
    $rows[] = $item['numero_poliza'] . "\t" . $item['prima'] . "\n";
}
pg_copy_from($link, 'items', $rows, "\t");
```

### 2.5 Prepared statements con `pg_prepare` / `pg_execute`

Usar `pg_prepare` / `pg_execute` cuando la **misma query** se ejecuta múltiples veces en el mismo request (no funciona entre requests via pooler transaction mode):

```php
// Preparar una vez por request
pg_prepare($link, 'get_polizas_cliente', "
    SELECT p.numero_poliza, p.estado, p.vigencia_final
    FROM polizas_2 p
    WHERE p.rut_proponente = $1 AND p.estado <> 'Rechazado'
    ORDER BY p.vigencia_final DESC
    LIMIT $2
");

// Ejecutar N veces sin re-parsear
$ruts = ['12345678', '87654321', '11223344'];
foreach ($ruts as $rut) {
    $result = pg_execute($link, 'get_polizas_cliente', [$rut, 10]);
    while ($row = pg_fetch_object($result)) {
        // procesar
    }
}
```

Para queries de una sola ejecución, `pg_query_params` es suficiente y más simple:

```php
// Seguro contra SQL injection sin necesidad de prepare/execute
$result = pg_query_params($link,
    "SELECT id, nombre FROM clientes WHERE rut_sin_dv = $1",
    [$rut]
);
```

> **Regla:** Siempre usar `pg_query_params` o `pg_execute` con parámetros separados.
> Nunca interpolar variables del usuario directamente en el string SQL.

### 2.6 EXPLAIN ANALYZE para diagnóstico

```php
// Wrapper para diagnosticar queries lentas en desarrollo
function explain_query(\PgSql\Connection $link, string $sql, array $params = []): void {
    if (!defined('APP_DEBUG') || !APP_DEBUG) return;

    $explain_sql = "EXPLAIN (ANALYZE, BUFFERS, FORMAT TEXT) " . $sql;
    $result = pg_query_params($link, $explain_sql, $params);
    if ($result) {
        $lines = [];
        while ($row = pg_fetch_row($result)) {
            $lines[] = $row[0];
        }
        error_log("[EXPLAIN]\n" . implode("\n", $lines));
    }
}

// Uso en desarrollo
explain_query($link,
    "SELECT * FROM polizas_2 WHERE rut_proponente = $1 AND estado = $2",
    ['12345678', 'Activo']
);
```

**Qué buscar en EXPLAIN ANALYZE:**

| Señal de alerta                    | Causa probable                          | Solución                         |
|------------------------------------|-----------------------------------------|----------------------------------|
| `Seq Scan` en tabla grande         | Falta índice en columna del WHERE       | `CREATE INDEX`                   |
| `cost=0..99999`                    | Query muy costosa                       | Revisar JOINs y filtros          |
| `rows=1` pero `actual rows=50000`  | Estadísticas desactualizadas            | `ANALYZE tabla`                  |
| `Hash Join` con tabla enorme       | JOIN sin índice en columna de join      | Índice en columna de join        |
| `Nested Loop` con N alto           | Patrón N+1 en la misma query            | Reescribir con `json_agg`        |

---

## 3. Patrones PHP para PostgreSQL

### 3.1 Fetch strategies: `pg_fetch_all` vs fetch fila por fila

```php
// Opcion A: fetch fila por fila (bueno para resultados grandes, bajo memoria)
$result = pg_query($link, $sql);
while ($row = pg_fetch_object($result)) {
    // procesar fila inmediatamente, no acumula en memoria
    output_row($row);
}
pg_free_result($result);

// Opcion B: pg_fetch_all (bueno para resultados pequeños, procesamiento posterior)
$result = pg_query($link, $sql);
$rows   = pg_fetch_all($result) ?: []; // array indexado, falseado a []
pg_free_result($result);
// Ahora se puede hacer array_filter, usort, etc.

// Opcion C: pg_fetch_all_columns (solo una columna, muy eficiente)
$result  = pg_query($link, "SELECT id FROM clientes ORDER BY id");
$ids     = pg_fetch_all_columns($result, 0); // [1, 2, 3, ...]
```

**Regla:** Para listados paginados o respuestas JSON completas, `pg_fetch_all` es cómodo.
Para exports de miles de filas, iterar fila a fila evita memory exhaustion.

### 3.2 JSON aggregation para reducir roundtrips

```sql
-- Patron: traer datos padre + hijos en una sola query
-- Clientes con sus polizas activas embebidas

SELECT
    c.id,
    c.nombre_cliente,
    COALESCE(
        json_agg(
            json_build_object(
                'numero_poliza',  p.numero_poliza,
                'compania',       p.compania,
                'ramo',           p.ramo,
                'vigencia_final', p.vigencia_final::text
            )
            ORDER BY p.vigencia_final DESC
        ) FILTER (WHERE p.id IS NOT NULL),
        '[]'::json
    ) AS polizas_activas,
    COUNT(p.id) AS total_polizas
FROM clientes c
LEFT JOIN polizas_2 p
    ON p.rut_proponente = c.rut_sin_dv
    AND p.estado = 'Activo'
GROUP BY c.id, c.nombre_cliente;
```

**Funciones JSON de PostgreSQL más útiles:**

| Función                  | Descripción                                           |
|--------------------------|-------------------------------------------------------|
| `json_agg(expr)`         | Agrega filas en un array JSON                         |
| `json_build_object(k,v)` | Construye un objeto JSON clave-valor                  |
| `jsonb_agg(expr)`        | Como json_agg pero retorna JSONB (indexable)          |
| `json_object_agg(k, v)`  | Agrupa en un objeto JSON con claves dinámicas         |
| `FILTER (WHERE cond)`    | Filtra qué filas entran en la agregación              |
| `COALESCE(expr, '[]')`   | Evita NULL cuando no hay filas                        |
| `row_to_json(t)`         | Convierte una fila entera a JSON                      |

### 3.3 Vistas materializadas para dashboards

Un dashboard de resumen (total pólizas por compañía, primas totales, etc.) que lee toda la tabla en cada carga es el candidato perfecto para una vista materializada.

```sql
-- Crear la vista materializada del dashboard principal
CREATE MATERIALIZED VIEW resumen_dashboard AS
SELECT
    compania,
    ramo,
    COUNT(*)                           AS total_polizas,
    COUNT(*) FILTER (WHERE estado = 'Activo')      AS polizas_activas,
    COUNT(*) FILTER (WHERE estado = 'Cancelado')   AS polizas_canceladas,
    SUM(prima_bruta_anual)             AS suma_prima_bruta,
    SUM(prima_neta)                    AS suma_prima_neta,
    TO_CHAR(NOW(), 'YYYY-MM-DD HH24:MI') AS ultima_actualizacion
FROM polizas_2
JOIN items ON polizas_2.numero_poliza = items.numero_poliza
GROUP BY compania, ramo
ORDER BY suma_prima_bruta DESC;

-- Indice unico necesario para REFRESH CONCURRENTLY
CREATE UNIQUE INDEX ON resumen_dashboard (compania, ramo);

-- Refrescar sin bloquear lecturas (requiere el indice unico)
REFRESH MATERIALIZED VIEW CONCURRENTLY resumen_dashboard;
```

```php
// PHP: leer desde la vista materializada (velocidad de SELECT en tabla pequeña)
$result = pg_query($link, "SELECT * FROM resumen_dashboard ORDER BY suma_prima_bruta DESC");
$data   = pg_fetch_all($result) ?: [];
echo json_encode($data);

// Refrescar la vista (llamar desde un cron o endpoint admin)
function refresh_dashboard(\PgSql\Connection $link): void {
    pg_query($link, "REFRESH MATERIALIZED VIEW CONCURRENTLY resumen_dashboard");
}
```

**Estrategia de refresco:**
- Cron job cada 5-15 minutos vía `pg_cron` en Supabase.
- O refresco manual después de operaciones que modifican pólizas.
- Usar `CONCURRENTLY` para no bloquear lecturas durante el refresco.

```sql
-- Activar pg_cron en Supabase (ya viene instalado)
-- Refresh cada 10 minutos
SELECT cron.schedule(
    'refresh-dashboard',
    '*/10 * * * *',
    'REFRESH MATERIALIZED VIEW CONCURRENTLY resumen_dashboard'
);
```

### 3.4 Funciones PL/pgSQL para lógica server-side

Mover lógica compleja al servidor elimina múltiples roundtrips. En esta app ya se usa `trazabilidad()` y `ANOMES()` — extender ese patrón:

```sql
-- Funcion que crea una poliza y registra en trazabilidad atomicamente
CREATE OR REPLACE FUNCTION crear_poliza(
    p_rut_proponente  TEXT,
    p_dv_proponente   TEXT,
    p_compania        TEXT,
    p_ramo            TEXT,
    p_numero_poliza   TEXT,
    p_vigencia_inicial DATE,
    p_vigencia_final  DATE,
    p_prima_bruta     NUMERIC,
    p_usuario         TEXT
) RETURNS INTEGER AS $$
DECLARE
    v_id_poliza INTEGER;
BEGIN
    INSERT INTO polizas_2 (
        rut_proponente, dv_proponente, compania, ramo,
        numero_poliza, vigencia_inicial, vigencia_final,
        prima_bruta_anual, estado
    ) VALUES (
        p_rut_proponente, p_dv_proponente, p_compania, p_ramo,
        p_numero_poliza, p_vigencia_inicial, p_vigencia_final,
        p_prima_bruta, 'Activo'
    )
    RETURNING id INTO v_id_poliza;

    -- Auditoria atomica con la misma transaccion
    PERFORM trazabilidad(
        p_usuario,
        'Crea poliza',
        'numero_poliza=' || p_numero_poliza,
        'poliza',
        v_id_poliza,
        '/bamboo/backend/polizas/crea_poliza.php'
    );

    RETURN v_id_poliza;
END;
$$ LANGUAGE plpgsql;
```

```php
// PHP: una sola llamada hace el INSERT + trazabilidad atomicamente
$result = pg_query_params($link,
    "SELECT crear_poliza($1, $2, $3, $4, $5, $6, $7, $8, $9)",
    [
        $rut_prop, $dv_prop, $compania, $ramo,
        $nro_poliza, $vigencia_inicial, $vigencia_final,
        $prima_bruta, $_SESSION['username']
    ]
);
$row       = pg_fetch_row($result);
$id_poliza = $row[0];
```

---

## 4. Caché y performance

### 4.1 APCu: caché de datos frecuentes en memoria

APCu almacena datos en memoria compartida del proceso PHP. Es ideal para datos que cambian poco: listas de compañías, tipos de ramo, configuraciones.

```php
// Funcion helper para cache con APCu
function cache_get_or_set(string $key, callable $loader, int $ttl = 300): mixed {
    if (function_exists('apcu_fetch')) {
        $success = false;
        $data    = apcu_fetch($key, $success);
        if ($success) {
            return $data;
        }
    }

    $data = $loader();

    if (function_exists('apcu_store') && $data !== null) {
        apcu_store($key, $data, $ttl);
    }

    return $data;
}

// Uso: lista de compañias (cambia raramente)
$companias = cache_get_or_set('companias_lista', function() use ($link) {
    $result = pg_query($link, "SELECT DISTINCT compania FROM polizas_2 ORDER BY compania");
    return pg_fetch_all($result) ?: [];
}, ttl: 600); // 10 minutos

// Uso: resumen de dashboard (refrescar cada 5 min)
$dashboard = cache_get_or_set('dashboard_resumen', function() use ($link) {
    $result = pg_query($link, "SELECT * FROM resumen_dashboard");
    return pg_fetch_all($result) ?: [];
}, ttl: 300);
```

**Limitaciones de APCu:**
- Solo funciona en el mismo servidor / proceso PHP-FPM.
- No se comparte entre múltiples servidores web.
- Si necesitas caché distribuida, usa **Redis** (disponible via Upstash en Supabase o externo).

### 4.2 ETag / 304 para responses que no cambian

```php
// Respuesta con ETag basado en hash del contenido
function send_json_with_etag(array $data): void {
    $json = json_encode($data);
    $etag = '"' . md5($json) . '"';

    header('Content-Type: application/json; charset=utf-8');
    header('ETag: ' . $etag);
    header('Cache-Control: private, max-age=60'); // 1 minuto en cliente

    // Si el cliente ya tiene la version actual, devolver 304
    $if_none_match = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    if ($if_none_match === $etag) {
        http_response_code(304);
        exit;
    }

    http_response_code(200);
    echo $json;
}

// Uso
$polizas = fetch_polizas($link);
send_json_with_etag($polizas);
```

**Con timestamp de ultima modificacion (Last-Modified):**

```php
function send_json_with_last_modified(array $data, string $last_modified_sql): void {
    global $link;
    $ts_result = pg_query($link, $last_modified_sql);
    $ts_row    = pg_fetch_row($ts_result);
    $last_ts   = strtotime($ts_row[0]);

    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $last_ts) . ' GMT');
    header('Cache-Control: private, max-age=30');

    $if_modified = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
    if ($if_modified && strtotime($if_modified) >= $last_ts) {
        http_response_code(304);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode($data);
}

// Ejemplo
send_json_with_last_modified(
    $polizas,
    "SELECT MAX(updated_at) FROM polizas_2"
);
```

### 4.3 Paginación eficiente: keyset vs OFFSET

**OFFSET es lento en tablas grandes** porque PostgreSQL debe leer y descartar todas las filas anteriores.

```sql
-- OFFSET: lento en paginas altas (PostgreSQL lee 10050 filas para devolver 50)
SELECT * FROM polizas_2
WHERE estado <> 'Rechazado'
ORDER BY id DESC
LIMIT 50 OFFSET 10000;  -- MALO para pagina 200

-- KEYSET (cursor): siempre usa el indice, O(1) sin importar la pagina
-- Primera pagina (sin cursor)
SELECT id, numero_poliza, vigencia_final, compania
FROM polizas_2
WHERE estado <> 'Rechazado'
ORDER BY vigencia_final DESC, id DESC
LIMIT 50;

-- Pagina siguiente (usando el ultimo id y vigencia_final vistos)
SELECT id, numero_poliza, vigencia_final, compania
FROM polizas_2
WHERE estado <> 'Rechazado'
  AND (vigencia_final, id) < ('2024-12-31', 4521)  -- cursor del ultimo item
ORDER BY vigencia_final DESC, id DESC
LIMIT 50;
```

```php
// Implementacion PHP de paginacion keyset
function get_polizas_page(
    \PgSql\Connection $link,
    int $limit = 50,
    ?string $cursor_fecha = null,
    ?int $cursor_id = null
): array {
    if ($cursor_fecha !== null && $cursor_id !== null) {
        $result = pg_query_params($link, "
            SELECT id, numero_poliza, vigencia_final, compania, estado
            FROM polizas_2
            WHERE estado <> 'Rechazado'
              AND (vigencia_final, id) < ($1::date, $2)
            ORDER BY vigencia_final DESC, id DESC
            LIMIT $3
        ", [$cursor_fecha, $cursor_id, $limit]);
    } else {
        $result = pg_query_params($link, "
            SELECT id, numero_poliza, vigencia_final, compania, estado
            FROM polizas_2
            WHERE estado <> 'Rechazado'
            ORDER BY vigencia_final DESC, id DESC
            LIMIT $1
        ", [$limit]);
    }

    $rows = pg_fetch_all($result) ?: [];

    // Cursor para la siguiente pagina
    $next_cursor = null;
    if (count($rows) === $limit) {
        $last = end($rows);
        $next_cursor = [
            'fecha' => $last['vigencia_final'],
            'id'    => $last['id'],
        ];
    }

    return [
        'data'        => $rows,
        'next_cursor' => $next_cursor,
        'has_more'    => $next_cursor !== null,
    ];
}
```

**Cuando usar OFFSET:**
- Paginación numerada visible (página 1, 2, 3...) en tablas pequeñas (<10k filas).
- Reportes administrativos donde el usuario salta a una página específica.

**Cuando usar Keyset:**
- Feeds, listados largos, scroll infinito.
- Cualquier tabla con >50k filas.
- Cuando la consistencia importa (OFFSET puede saltar filas si hay inserts concurrentes).

---

## 5. Supabase-specific

### 5.1 Row Level Security (RLS): consideraciones de performance

RLS agrega un predicado automático a cada query. Si las columnas referenciadas en las policies no tienen índice, cada query hace un Seq Scan.

**Regla de oro:** Crear índice en cada columna usada en policies RLS.

```sql
-- Policy tipica: solo ver tus propias polizas
CREATE POLICY "ver_propias_polizas" ON polizas_2
    FOR SELECT
    USING (rut_proponente = current_setting('app.current_user_rut', TRUE));

-- OBLIGATORIO: indice en la columna de la policy
CREATE INDEX idx_polizas_rut_proponente ON polizas_2 (rut_proponente);

-- Si la policy filtra por tenant/grupo, indexar esa columna tambien
CREATE INDEX idx_clientes_grupo ON clientes (grupo);
```

**Optimizacion: wrapping de funciones para evitar re-evaluacion por fila**

```sql
-- LENTO: auth.uid() se evalua por cada fila
CREATE POLICY "slow_policy" ON tabla
    USING (user_id = auth.uid());

-- RAPIDO: la funcion se evalua una vez por statement
CREATE POLICY "fast_policy" ON tabla
    USING (user_id = (SELECT auth.uid()));
-- El SELECT fuerza un "initPlan" que cachea el resultado por statement
```

**En esta app (PHP con usuario propio, sin Supabase Auth):**
Si no se usa `auth.uid()` de Supabase sino autenticación propia por sesión PHP, RLS puede configurarse via `SET LOCAL`:

```php
// Establecer variable de sesion para RLS customizado
pg_query($link, "SET LOCAL app.current_user_rut = '" . pg_escape_string($link, $rut) . "'");

// O via funcion parametrizada (mas seguro)
pg_query_params($link, "SELECT set_config('app.current_user_rut', $1, TRUE)", [$rut]);
```

```sql
-- Policy que usa la variable de sesion PHP
CREATE POLICY "polizas_por_rut" ON polizas_2
    FOR ALL
    USING (
        rut_proponente = current_setting('app.current_user_rut', TRUE)
        OR current_setting('app.is_admin', TRUE) = 'true'
    );
```

### 5.2 Supabase REST API vs conexión directa PostgreSQL

| Criterio                    | REST API (PostgREST)          | Conexion directa (pg_connect) |
|-----------------------------|-------------------------------|-------------------------------|
| Latencia por query          | Mayor (+HTTP overhead)        | Menor (protocolo nativo)      |
| Queries complejas (JOINs)   | Limitado a tablas simples     | Sin restricciones             |
| CTEs / funciones PL/pgSQL   | No disponible directamente    | Si                            |
| Transacciones multi-query   | Solo con RPC functions        | Si, nativo                    |
| RLS automático              | Si (usa Supabase Auth)        | Manual via SET LOCAL          |
| Cambios de schema en caliente| Si (autodescubre)            | Requiere reconexion           |
| Autenticación               | JWT de Supabase Auth          | Password + SSL                |
| Ideal para                  | Frontend JS / apps sin backend| Backend PHP con logica compleja|

**Recomendacion para esta app:**
Mantener la conexion directa via pg_connect/Supavisor. La REST API agrega latencia HTTP
extra que no tiene sentido cuando ya se tiene un backend PHP.

**Cuándo considerar REST API:**
- Llamadas desde JavaScript del frontend directamente a Supabase (sin pasar por PHP).
- Prototipos rápidos donde la seguridad via RLS + JWT es suficiente.

### 5.3 Edge Functions para operaciones pesadas

Las Edge Functions (Deno/TypeScript, ejecutadas en el edge de Supabase) son útiles para:

```
Casos de uso ideales para Edge Functions:
- Envio de emails en background (sin bloquear el request PHP)
- Procesamiento de webhooks externos
- Generacion de PDFs / reportes pesados
- Notificaciones push / SMS
- Integracion con APIs externas (compañias de seguros)
- Jobs recurrentes via pg_cron + Edge Function
```

**Invocar una Edge Function desde PHP:**

```php
// Llamar una Edge Function de Supabase desde PHP
function invoke_edge_function(
    string $function_name,
    array $payload,
    bool $wait = false
): ?array {
    $url  = SUPABASE_URL . '/functions/v1/' . $function_name;
    $body = json_encode($payload);

    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", [
                'Content-Type: application/json',
                'Authorization: Bearer ' . SUPABASE_SERVICE_ROLE_KEY,
                'Content-Length: ' . strlen($body),
            ]),
            'content' => $body,
            'timeout' => $wait ? 30 : 3, // si no se espera respuesta, timeout corto
        ],
    ];

    $context  = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);

    if ($response === false || !$wait) {
        return null; // fire-and-forget
    }

    return json_decode($response, true);
}

// Ejemplo: enviar propuesta por email sin bloquear el response
invoke_edge_function('enviar-propuesta', [
    'id_propuesta' => $id_propuesta,
    'correo'       => $correo_cliente,
    'usuario'      => $_SESSION['username'],
], wait: false); // fire-and-forget

echo json_encode(['success' => true, 'id' => $id_propuesta]);
```

### 5.4 Índices recomendados para esta app

```sql
-- polizas_2: columnas de busqueda y JOIN frecuentes
CREATE INDEX IF NOT EXISTS idx_polizas_rut_proponente  ON polizas_2 (rut_proponente);
CREATE INDEX IF NOT EXISTS idx_polizas_estado          ON polizas_2 (estado);
CREATE INDEX IF NOT EXISTS idx_polizas_compania_ramo   ON polizas_2 (compania, ramo);
CREATE INDEX IF NOT EXISTS idx_polizas_vigencia_final  ON polizas_2 (vigencia_final DESC);
CREATE INDEX IF NOT EXISTS idx_polizas_numero          ON polizas_2 (numero_poliza);

-- items: JOIN frecuente con polizas_2
CREATE INDEX IF NOT EXISTS idx_items_numero_poliza     ON items (numero_poliza);
CREATE INDEX IF NOT EXISTS idx_items_rut_asegurado     ON items (rut_asegurado);

-- endosos: JOIN con polizas_2
CREATE INDEX IF NOT EXISTS idx_endosos_id_poliza       ON endosos (id_poliza);

-- clientes: busqueda por RUT y texto
CREATE INDEX IF NOT EXISTS idx_clientes_rut            ON clientes (rut_sin_dv);
CREATE INDEX IF NOT EXISTS idx_clientes_grupo          ON clientes (grupo);

-- Full-text search en clientes (ya implementado en busca_cliente.php)
CREATE INDEX IF NOT EXISTS idx_clientes_fts ON clientes
    USING GIN (
        to_tsvector('spanish',
            concat_ws(' ', nombre_cliente, apellido_paterno, apellido_materno, rut_sin_dv)
        )
    );

-- tareas: busqueda por estado y fecha
CREATE INDEX IF NOT EXISTS idx_tareas_estado           ON tareas (estado);
CREATE INDEX IF NOT EXISTS idx_tareas_fecha_venc       ON tareas (fecha_vencimiento);
CREATE INDEX IF NOT EXISTS idx_tareas_asignado         ON tareas (asignado_a);

-- Indice compuesto para keyset pagination
CREATE INDEX IF NOT EXISTS idx_polizas_keyset ON polizas_2 (vigencia_final DESC, id DESC)
    WHERE estado <> 'Rechazado';
```

**Diagnosticar índices faltantes en Supabase:**

```sql
-- Tablas con Seq Scans frecuentes (candidatos a indexar)
SELECT
    schemaname,
    relname AS tabla,
    seq_scan,
    idx_scan,
    seq_tup_read,
    n_live_tup AS filas_vivas
FROM pg_stat_user_tables
WHERE seq_scan > 100
ORDER BY seq_scan DESC
LIMIT 20;

-- Indices que nunca se usan (candidatos a eliminar)
SELECT
    indexrelname AS indice,
    relname AS tabla,
    idx_scan AS veces_usado
FROM pg_stat_user_indexes
JOIN pg_index USING (indexrelid)
WHERE idx_scan = 0
  AND NOT indisprimary
  AND NOT indisunique
ORDER BY pg_relation_size(indexrelid) DESC;
```

---

## 6. Anti-patrones a evitar

### 6.1 Queries dentro de loops (N+1) — ya cubierto en sección 2

```php
// MAL: busqueda_listado_clientes.php actual
while ($row = db_fetch_object($resultado)) {
    $contactos = db_query($link, "SELECT * FROM clientes_contactos WHERE id_cliente='{$row->id}'");
    // ...
}

// BIEN: un solo JOIN con json_agg (ver seccion 2.1)
```

### 6.2 `SELECT *` cuando solo necesitas pocas columnas

```php
// MAL: trae todas las columnas (incluyendo textos largos, BLOBs)
$result = pg_query($link, "SELECT * FROM polizas_2");

// BIEN: solo las columnas necesarias
$result = pg_query($link, "
    SELECT id, numero_poliza, estado, compania, vigencia_final
    FROM polizas_2
    WHERE estado <> 'Rechazado'
");
```

**Impacto:** `SELECT *` aumenta el tráfico de red entre Supabase y el servidor PHP,
y evita que PostgreSQL use index-only scans.

### 6.3 No usar índices en columnas de WHERE/JOIN

```php
// MAL: query sobre columna sin indice
$result = pg_query($link,
    "SELECT * FROM polizas_2 WHERE compania = 'Mapfre'" // sin indice en compania
);

// BIEN: crear el indice primero (ver seccion 5.4)
// CREATE INDEX idx_polizas_compania ON polizas_2 (compania);
```

### 6.4 Interpolacion directa de variables de usuario en SQL

```php
// MAL: SQL injection posible
$rut = $_POST['rut'];
$result = pg_query($link, "SELECT * FROM clientes WHERE rut_sin_dv = '$rut'");
// Si rut = "' OR '1'='1" => desastre

// BIEN: siempre usar parametros
$result = pg_query_params($link,
    "SELECT * FROM clientes WHERE rut_sin_dv = $1",
    [$_POST['rut']]
);
```

**En la app actual:** `estandariza_info()` usa `htmlspecialchars()` que NO protege contra SQL injection.
`pg_query_params` / `pg_escape_string` son la protección correcta.

### 6.5 Conexiones sin cerrar / recursos no liberados

```php
// MAL: no liberar resultados grandes
$result = pg_query($link, "SELECT * FROM polizas_2"); // puede ser MB de datos
// ... procesar
// nunca se llama pg_free_result($result)

// BIEN: liberar explicitamente cuando ya no se necesita
$result = pg_query($link, $sql);
$data   = pg_fetch_all($result) ?: [];
pg_free_result($result); // liberar memoria del resultado

// BIEN: con pg_pconnect NO cerrar la conexion (se reutiliza)
// NO llamar pg_close($link) si se usa pg_pconnect
```

### 6.6 No usar transacciones para operaciones múltiples

```php
// MAL: operaciones sin transaccion (datos inconsistentes si falla a la mitad)
db_query($link, "UPDATE polizas_2 SET tipo_poliza='Renovada' WHERE id=$id_renovada");
db_query($link, "INSERT INTO polizas_2 (...) VALUES (...)");
db_query($link, "SELECT trazabilidad(...)");
// Si la segunda query falla, la primera quedo aplicada => inconsistencia

// BIEN: envolver en transaccion
pg_query($link, "BEGIN");
try {
    $r1 = pg_query($link, "UPDATE polizas_2 SET tipo_poliza='Renovada' WHERE id=$1", ...);
    if (!$r1) throw new \Exception(pg_last_error($link));

    $r2 = pg_query_params($link, "INSERT INTO polizas_2 (...) VALUES (...)", $params);
    if (!$r2) throw new \Exception(pg_last_error($link));

    pg_query_params($link, "SELECT trazabilidad($1, $2, $3, $4, $5, $6)",
        [$usuario, 'Renueva poliza', $detalle, 'poliza', $id, $_SERVER['PHP_SELF']]);

    pg_query($link, "COMMIT");
} catch (\Exception $e) {
    pg_query($link, "ROLLBACK");
    error_log("[DB] Transaccion revertida: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar. Operacion revertida.']);
}
```

### 6.7 Usar `pg_pconnect` con Transaction Mode del pooler

```php
// MAL: pg_pconnect + Transaction Mode de Supavisor (puerto 6543)
// Las conexiones persistentes no son compatibles con transaction mode:
// - El pooler asigna conexiones distintas entre queries
// - Los prepared statements se pierden entre roundtrips
// - pg_pconnect "reutiliza" la conexion del proceso PHP,
//   pero el pooler ya la rotatp

// BIEN: con Transaction Mode usar pg_connect (no pconnect)
// O mejor aun: usar Session Mode (puerto 5432) con pg_pconnect
```

### 6.8 Comparaciones de texto en columnas numéricas

```sql
-- MAL: comparar RUT como texto sin cast (en PG no siempre funciona)
SELECT * FROM polizas_2 WHERE rut_proponente = 12345678;
-- Si rut_proponente es TEXT, PostgreSQL no hace cast automatico del integer

-- BIEN: tipos consistentes
SELECT * FROM polizas_2 WHERE rut_proponente = '12345678';
-- O mejor: definir rut_sin_dv como TEXT desde el inicio y siempre usar strings
```

---

## 7. Diagnóstico rápido: checklist

Antes de optimizar, medir. Esta checklist ayuda a identificar el cuello de botella:

### 7.1 Logging de queries lentas en Supabase

```sql
-- En Supabase Dashboard > Database > Extensions, activar pg_stat_statements
CREATE EXTENSION IF NOT EXISTS pg_stat_statements;

-- Queries mas lentas del ultimo periodo
SELECT
    LEFT(query, 100)       AS query_truncada,
    calls                  AS veces_ejecutada,
    ROUND(total_exec_time::numeric / calls, 2) AS ms_promedio,
    ROUND(total_exec_time::numeric, 0)         AS ms_total,
    rows / calls           AS filas_promedio
FROM pg_stat_statements
WHERE calls > 10
ORDER BY total_exec_time DESC
LIMIT 20;

-- Resetear estadisticas
SELECT pg_stat_statements_reset();
```

### 7.2 Detectar N+1 en PHP (development helper)

```php
// Agregar al inicio del request en development
if (defined('APP_DEBUG') && APP_DEBUG) {
    $GLOBALS['_query_log'] = [];
    $GLOBALS['_query_count'] = 0;
}

// Wrapper de db_query para logging
function db_query_logged(\PgSql\Connection $link, string $sql): mixed {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $start = microtime(true);
        $result = pg_query($link, $sql);
        $elapsed_ms = round((microtime(true) - $start) * 1000, 2);
        $GLOBALS['_query_count']++;
        $GLOBALS['_query_log'][] = ['ms' => $elapsed_ms, 'sql' => substr($sql, 0, 200)];
        if ($elapsed_ms > 100) {
            error_log("[SLOW QUERY {$elapsed_ms}ms] " . $sql);
        }
        return $result;
    }
    return pg_query($link, $sql);
}

// Al final del request: reportar total
register_shutdown_function(function() {
    if (!defined('APP_DEBUG') || !APP_DEBUG) return;
    $count = $GLOBALS['_query_count'] ?? 0;
    $total_ms = array_sum(array_column($GLOBALS['_query_log'] ?? [], 'ms'));
    error_log("[DB SUMMARY] {$count} queries en {$total_ms}ms");
    if ($count > 10) {
        error_log("[DB WARNING] Posible N+1: {$count} queries en un solo request");
    }
});
```

### 7.3 Checklist de performance en orden de impacto

```
[ ] 1. Eliminar N+1 queries con json_agg + JOINs (mayor impacto)
[ ] 2. Crear indices en columnas de WHERE, JOIN y ORDER BY
[ ] 3. Reemplazar SELECT * por columnas especificas
[ ] 4. Usar pg_query_params en lugar de interpolacion de strings
[ ] 5. Activar pg_pconnect para reutilizar conexiones (Session Mode)
[ ] 6. Crear vistas materializadas para dashboards y reportes pesados
[ ] 7. Implementar APCu para listas de referencia (companias, ramos)
[ ] 8. Reemplazar OFFSET con keyset pagination en listados largos
[ ] 9. Envolver operaciones multi-query en transacciones
[ ] 10. Usar pg_stat_statements para identificar las queries mas lentas
[ ] 11. Considerar REFRESH MATERIALIZED VIEW CONCURRENTLY con pg_cron
[ ] 12. Mover operaciones pesadas (emails, PDFs) a Edge Functions async
```

---

## Referencias

- [Supabase: Connect to your database](https://supabase.com/docs/guides/database/connecting-to-postgres)
- [Supabase: Supavisor FAQ](https://supabase.com/docs/guides/troubleshooting/supavisor-faq-YyP5tI)
- [Supabase: RLS Performance and Best Practices](https://supabase.com/docs/guides/troubleshooting/rls-performance-and-best-practices-Z5Jjwv)
- [GitHub: Session Mode Deprecation (feb 2025)](https://github.com/orgs/supabase/discussions/32755)
- [GitHub: Supavisor prepared statements issue](https://github.com/supabase/supavisor/issues/69)
- [PHP Manual: pg_query_params](https://www.php.net/manual/en/function.pg-query-params.php)
- [PostgreSQL: PREPARE documentation](https://www.postgresql.org/docs/current/sql-prepare.html)
- [PostgreSQL: REFRESH MATERIALIZED VIEW](https://www.postgresql.org/docs/current/sql-refreshmaterializedview.html)
- [Keyset pagination vs OFFSET](https://blog.sequinstream.com/keyset-cursors-not-offsets-for-postgres-pagination/)
- [Supabase Edge Functions: Background Tasks](https://supabase.com/docs/guides/functions/background-tasks)
- [PostgreSQL Performance Tuning 2025](https://www.mydbops.com/blog/postgresql-parameter-tuning-best-practices)
- [RLS optimization: wrapping auth.uid()](https://medium.com/@antstack/optimizing-rls-performance-with-supabase-postgres-fa4e2b6e196d)
- [json_agg para eliminar N+1](https://medium.com/@clementgrimault/optimize-the-way-you-fetch-relationships-with-postgresql-7711fe6457d2)
