<?php
/**
 * Capa de abstracción dual MySQL/PostgreSQL
 * v2 - pooler compatible
 * Toggle via DB_ENGINE en .env (mysql|pgsql)
 */

function db_connect() {
    $engine = defined('DB_ENGINE') ? DB_ENGINE : 'mysql';

    if ($engine === 'pgsql') {
        // Usa pooler Supavisor (IPv4) si el host es directo de Supabase
        $pg_host = PG_HOST;
        $pg_user = PG_USERNAME;
        $pg_port = PG_PORT;
        if (strpos($pg_host, 'db.') === 0 && substr($pg_host, -12) === '.supabase.co') {
            // Extraer project ref del host (db.XXXX.supabase.co)
            $ref = str_replace(['db.', '.supabase.co'], '', $pg_host);
            $pg_host = 'aws-1-us-east-2.pooler.supabase.com';
            $pg_user = PG_USERNAME . '.' . $ref;
            $pg_port = '5432';
        }
        $conn_string = "host=" . $pg_host . " port=" . $pg_port . " dbname=" . PG_DATABASE . " user=" . $pg_user . " password=" . PG_PASSWORD . " sslmode=require";
        $link = pg_pconnect($conn_string);
        if (!$link) {
            die("ERROR: No se pudo conectar al servidor PostgreSQL.");
        }
        return $link;
    } else {
        $link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if ($link === false) {
            die("ERROR: No se pudo conectar al servidor MySQL. " . mysqli_connect_error());
        }
        mysqli_set_charset($link, 'utf8');
        return $link;
    }
}

function db_query($link, $sql) {
    $engine = defined('DB_ENGINE') ? DB_ENGINE : 'mysql';

    if ($engine === 'pgsql') {
        $sql = sql_translate($sql);
        $result = pg_query($link, $sql);
        if ($result === false) {
            error_log("PG query error: " . pg_last_error($link) . " | SQL: " . $sql);
        }
        return $result;
    } else {
        return mysqli_query($link, $sql);
    }
}

function db_fetch_object($result) {
    if ($result === false || $result === null) {
        return null;
    }
    $engine = defined('DB_ENGINE') ? DB_ENGINE : 'mysql';

    if ($engine === 'pgsql') {
        return pg_fetch_object($result);
    } else {
        return mysqli_fetch_object($result);
    }
}

function db_close($link) {
    $engine = defined('DB_ENGINE') ? DB_ENGINE : 'mysql';

    if ($engine === 'pgsql') {
        return pg_close($link);
    } else {
        return mysqli_close($link);
    }
}

function db_set_charset($link, $charset) {
    $engine = defined('DB_ENGINE') ? DB_ENGINE : 'mysql';
    if ($engine === 'pgsql') {
        // charset ya configurado en db_connect
        return true;
    } else {
        return mysqli_set_charset($link, $charset);
    }
}

function db_select_db($link, $db) {
    $engine = defined('DB_ENGINE') ? DB_ENGINE : 'mysql';
    if ($engine === 'pgsql') {
        // base de datos ya seleccionada en db_connect
        return true;
    } else {
        return mysqli_select_db($link, $db);
    }
}

/**
 * Prepared statement wrapper para ambos motores.
 * $types: string de tipos mysqli ("s","i","d","b") - se ignora en PG.
 * $params: array de parámetros.
 * Retorna array asociativo con las filas resultantes, o false en error.
 */
function db_prepare_and_execute($link, $sql, $types, $params) {
    $engine = defined('DB_ENGINE') ? DB_ENGINE : 'mysql';

    if ($engine === 'pgsql') {
        $sql = sql_translate($sql);
        // Convertir placeholders ? a $1, $2, etc.
        $i = 0;
        $sql = preg_replace_callback('/\?/', function($m) use (&$i) {
            $i++;
            return '$' . $i;
        }, $sql);

        $result = pg_query_params($link, $sql, $params);
        if ($result === false) {
            error_log("PG prepared error: " . pg_last_error($link) . " | SQL: " . $sql);
            return false;
        }

        $rows = [];
        $num_rows = pg_num_rows($result);
        while ($row = pg_fetch_object($result)) {
            $rows[] = $row;
        }

        return [
            'success' => true,
            'num_rows' => $num_rows,
            'rows' => $rows
        ];
    } else {
        $stmt = mysqli_prepare($link, $sql);
        if (!$stmt) {
            return false;
        }
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return false;
        }
        mysqli_stmt_store_result($stmt);
        $num_rows = mysqli_stmt_num_rows($stmt);

        // Obtener metadatos de columnas para bind_result
        $meta = mysqli_stmt_result_metadata($stmt);
        if ($meta) {
            $columns = [];
            $bind_vars = [];
            while ($field = mysqli_fetch_field($meta)) {
                $columns[] = $field->name;
                $bind_vars[] = null;
            }
            $refs = [];
            for ($j = 0; $j < count($bind_vars); $j++) {
                $refs[$j] = &$bind_vars[$j];
            }
            mysqli_stmt_bind_result($stmt, ...$refs);
            $rows = [];
            while (mysqli_stmt_fetch($stmt)) {
                $obj = new stdClass();
                foreach ($columns as $k => $col) {
                    $obj->$col = $bind_vars[$k];
                }
                $rows[] = clone $obj;
            }
            mysqli_free_result($meta);
        } else {
            $rows = [];
        }

        mysqli_stmt_close($stmt);

        return [
            'success' => true,
            'num_rows' => $num_rows,
            'rows' => $rows
        ];
    }
}

/**
 * Traduce sintaxis MySQL → PostgreSQL.
 */
function sql_translate($sql) {
    // Eliminar backticks
    $sql = str_replace('`', '', $sql);

    // '0000-00-00' → NULL
    $sql = str_replace("'0000-00-00'", "NULL", $sql);

    // =NULL → IS NULL, <>NULL → IS NOT NULL (MySQL quirk)
    $sql = preg_replace('/=\s*NULL\b/i', ' IS NULL', $sql);
    $sql = preg_replace('/<>\s*NULL\b/i', ' IS NOT NULL', $sql);

    // CAST(x AS UNSIGNED) → CAST(x AS INTEGER)
    $sql = preg_replace('/CAST\s*\((.+?)\s+AS\s+UNSIGNED\s*\)/i', 'CAST($1 AS INTEGER)', $sql);

    // SUBSTRING(integer_col, ...) → SUBSTRING(integer_col::text, ...)
    // PostgreSQL SUBSTRING requiere text, no integer
    $sql = preg_replace('/SUBSTRING\s*\(\s*(\w+)\s*,/i', 'SUBSTRING($1::text,', $sql);

    // IF(cond, v1, v2) → CASE WHEN cond THEN v1 ELSE v2 END
    // Manejo recursivo de IF() anidados con paréntesis balanceados
    $maxIterations = 10;
    for ($iter = 0; $iter < $maxIterations; $iter++) {
        $pos = stripos($sql, 'IF(');
        if ($pos === false) break;
        // Verificar que no sea parte de otra palabra (ej: NULLIF)
        if ($pos > 0 && preg_match('/[a-zA-Z_]/', $sql[$pos - 1])) {
            // Buscar siguiente IF( que no sea parte de otra palabra
            $found = false;
            $searchPos = $pos + 3;
            while (($pos = stripos($sql, 'IF(', $searchPos)) !== false) {
                if ($pos === 0 || !preg_match('/[a-zA-Z_]/', $sql[$pos - 1])) {
                    $found = true;
                    break;
                }
                $searchPos = $pos + 3;
            }
            if (!$found) break;
        }

        // Encontrar los 3 argumentos del IF() respetando paréntesis
        $start = $pos + 3; // después de "IF("
        $depth = 1;
        $args = [];
        $argStart = $start;
        for ($c = $start; $c < strlen($sql); $c++) {
            $ch = $sql[$c];
            if ($ch === '(') $depth++;
            elseif ($ch === ')') {
                $depth--;
                if ($depth === 0) {
                    $args[] = trim(substr($sql, $argStart, $c - $argStart));
                    $end = $c;
                    break;
                }
            } elseif ($ch === ',' && $depth === 1) {
                $args[] = trim(substr($sql, $argStart, $c - $argStart));
                $argStart = $c + 1;
            } elseif ($ch === "'" ) {
                // Skip cadena entre comillas simples
                $c++;
                while ($c < strlen($sql) && $sql[$c] !== "'") {
                    if ($sql[$c] === "\\") $c++;
                    $c++;
                }
            }
        }

        if (count($args) === 3 && isset($end)) {
            $replacement = "CASE WHEN " . $args[0] . " THEN " . $args[1] . " ELSE " . $args[2] . " END";
            $sql = substr($sql, 0, $pos) . $replacement . substr($sql, $end + 1);
        } else {
            break;
        }
    }

    // REGEXP → ~
    $sql = preg_replace('/\bREGEXP\b/i', '~', $sql);

    // DATE_FORMAT(date, '%d-%m-%Y') → TO_CHAR(date, 'DD-MM-YYYY')
    // DATE_FORMAT(date, '%m-%Y') → TO_CHAR(date, 'MM-YYYY')
    // DATE_FORMAT(date, '%d/%m/%Y') → TO_CHAR(date, 'DD/MM/YYYY')
    $sql = preg_replace_callback(
        '/DATE_FORMAT\s*\((.+?),\s*\'(.*?)\'\s*\)/i',
        function($m) {
            $expr = $m[1];
            $fmt = $m[2];
            // Convertir formato MySQL → PostgreSQL
            $fmt = str_replace('%Y', 'YYYY', $fmt);
            $fmt = str_replace('%y', 'YY', $fmt);
            $fmt = str_replace('%m', 'MM', $fmt);
            $fmt = str_replace('%d', 'DD', $fmt);
            $fmt = str_replace('%H', 'HH24', $fmt);
            $fmt = str_replace('%i', 'MI', $fmt);
            $fmt = str_replace('%s', 'SS', $fmt);
            return "TO_CHAR(" . $expr . ", '" . $fmt . "')";
        },
        $sql
    );

    // FORMAT(num, decimals, 'de_DE') → format_de(num, decimals)
    $sql = preg_replace(
        '/FORMAT\s*\((.+?),\s*(\d+)\s*,\s*\'de_DE\'\s*\)/i',
        'format_de($1, $2)',
        $sql
    );

    // FORMAT(num, decimals) sin locale → TO_CHAR con formato
    $sql = preg_replace(
        '/FORMAT\s*\((.+?),\s*(\d+)\s*\)/i',
        'format_de($1, $2)',
        $sql
    );

    // DATE_ADD(d, INTERVAL n MONTH) → (d + INTERVAL 'n month')
    $sql = preg_replace_callback(
        '/DATE_ADD\s*\(\s*(.+?)\s*,\s*INTERVAL\s+([+-]?\s*\d+)\s+(MONTH|DAY|YEAR|HOUR|MINUTE|SECOND)\s*\)/i',
        function($m) {
            $date = $m[1];
            $n = preg_replace('/\s+/', '', $m[2]);
            $unit = strtolower($m[3]);
            return "(" . $date . " + INTERVAL '" . $n . " " . $unit . "')";
        },
        $sql
    );

    // MATCH(cols) AGAINST ('term') → to_tsvector('spanish', cols) @@ plainto_tsquery('spanish', 'term')
    // Se maneja como caso especial en busca_cliente.php

    // '' en CASE WHEN → NULL (PG no acepta '' para date/numeric) - al final para capturar IF→CASE WHEN
    $sql = str_replace("THEN ''", "THEN NULL", $sql);
    $sql = str_replace("ELSE ''", "ELSE NULL", $sql);

    return $sql;
}
?>