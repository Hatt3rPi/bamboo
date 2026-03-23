<?php
set_time_limit(600);
ini_set('display_errors', 1);
error_reporting(E_ALL);
ob_implicit_flush(true);
echo "<pre>\n";

$old = pg_connect("host=aws-1-us-east-2.pooler.supabase.com port=5432 dbname=postgres user=postgres.vrbpzhmsybpfaobrpkoj password=GwIV17b2UvU2Bh7m sslmode=require");
$new = pg_connect("host=aws-1-sa-east-1.pooler.supabase.com port=5432 dbname=postgres user=postgres.dynnhfqpagwkdynzubmh password=GwIV17b2UvU2Bh7m sslmode=require");

if (!$old) die("Error old DB");
if (!$new) die("Error new DB");
echo "Conexiones OK\n\n";

$tq = pg_query($old, "SELECT table_name FROM information_schema.tables WHERE table_schema='public' AND table_type='BASE TABLE' ORDER BY table_name");
while ($t = pg_fetch_object($tq)) {
    $tn = $t->table_name;
    echo "$tn: ";

    $cq = pg_query($old, "SELECT column_name, udt_name, character_maximum_length, is_nullable, column_default FROM information_schema.columns WHERE table_schema='public' AND table_name='$tn' ORDER BY ordinal_position");
    $defs = [];
    $cnames = [];
    while ($c = pg_fetch_object($cq)) {
        $cnames[] = $c->column_name;
        $tp = $c->udt_name;
        if ($tp == 'varchar' && $c->character_maximum_length) {
            $tp = 'varchar(' . $c->character_maximum_length . ')';
        }
        if ($tp == 'int4') $tp = 'integer';
        if ($tp == 'int2') $tp = 'smallint';
        if ($tp == 'float8') $tp = 'double precision';

        $nl = ($c->is_nullable == 'YES') ? '' : ' NOT NULL';
        $df = $c->column_default ? (' DEFAULT ' . $c->column_default) : '';

        if (strpos($c->column_default ?: '', 'nextval') !== false) {
            $tp = 'SERIAL';
            $df = '';
            $nl = '';
        }

        $defs[] = '"' . $c->column_name . '" ' . $tp . $df . $nl;
    }

    pg_query($new, 'DROP TABLE IF EXISTS "' . $tn . '" CASCADE');
    $cr = pg_query($new, 'CREATE TABLE "' . $tn . '" (' . implode(', ', $defs) . ')');
    if (!$cr) {
        echo "ERR: " . pg_last_error($new) . "\n";
        flush();
        continue;
    }

    $cnt = pg_fetch_object(pg_query($old, 'SELECT count(*) as c FROM "' . $tn . '"'))->c;
    $ok = 0;
    if ($cnt > 0) {
        $dq = pg_query($old, 'SELECT * FROM "' . $tn . '"');
        while ($row = pg_fetch_assoc($dq)) {
            $vs = [];
            foreach ($cnames as $cn) {
                if ($row[$cn] === null) {
                    $vs[] = 'NULL';
                } else {
                    $vs[] = "'" . pg_escape_string($new, $row[$cn]) . "'";
                }
            }
            $ic = '"' . implode('","', $cnames) . '"';
            $ins = 'INSERT INTO "' . $tn . '" (' . $ic . ') VALUES (' . implode(',', $vs) . ')';
            $ir = @pg_query($new, $ins);
            if ($ir) $ok++;
        }
    }
    echo "$ok/$cnt";

    // Fix sequences
    $sq = pg_query($old, "SELECT column_name FROM information_schema.columns WHERE table_schema='public' AND table_name='$tn' AND column_default LIKE 'nextval%'");
    while ($s = pg_fetch_object($sq)) {
        $mx = pg_fetch_object(pg_query($new, 'SELECT COALESCE(MAX("' . $s->column_name . '"),0)+1 as v FROM "' . $tn . '"'))->v;
        @pg_query($new, "SELECT setval(pg_get_serial_sequence('\"$tn\"', '" . $s->column_name . "'), $mx, false)");
    }

    // PK
    $pq = pg_query($old, "SELECT kcu.column_name FROM information_schema.table_constraints tc JOIN information_schema.key_column_usage kcu ON tc.constraint_name=kcu.constraint_name WHERE tc.table_name='$tn' AND tc.constraint_type='PRIMARY KEY'");
    $pks = [];
    while ($p = pg_fetch_object($pq)) {
        $pks[] = '"' . $p->column_name . '"';
    }
    if (!empty($pks)) {
        @pg_query($new, 'ALTER TABLE "' . $tn . '" ADD PRIMARY KEY (' . implode(',', $pks) . ')');
    }

    echo " OK\n";
    flush();
}

echo "\nDONE\n</pre>";
?>
