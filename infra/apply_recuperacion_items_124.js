// Recuperación de items de la póliza 124 (daño bug 'WEB') desde backup prod.
// Lee infra/recuperacion_items_124.sql y lo ejecuta en São Paulo (transacción).
// Uso:
//   PG_HOST=... PG_USER=... PG_PASSWORD=... node infra/apply_recuperacion_items_124.js [archivo.sql]
// Requiere `pg` (se resuelve desde el worktree bamboo si no está local).
let Client;
try { ({ Client } = require('pg')); }
catch (e) { ({ Client } = require('../../bamboo/node_modules/pg')); }
const fs = require('fs');
const path = require('path');

const required = ['PG_HOST', 'PG_USER', 'PG_PASSWORD'];
for (const k of required) {
  if (!process.env[k]) { console.error(`Falta variable de entorno: ${k}`); process.exit(1); }
}

const sqlFile = process.argv[2] || path.join(__dirname, 'recuperacion_items_124.sql');
const sql = fs.readFileSync(sqlFile, 'utf8');

const client = new Client({
  host: process.env.PG_HOST,
  port: parseInt(process.env.PG_PORT || '5432', 10),
  user: process.env.PG_USER,
  password: process.env.PG_PASSWORD,
  database: process.env.PG_DATABASE || 'postgres',
  ssl: { rejectUnauthorized: false }
});

(async () => {
  try {
    await client.connect();
    console.log(`Conectado a ${process.env.PG_HOST}. Aplicando ${path.basename(sqlFile)} ...`);
    const before = await client.query("SELECT count(*)::int AS n FROM items WHERE numero_poliza='124'");
    console.log('items en poliza 124 ANTES:', before.rows[0].n);
    // El archivo ya trae BEGIN/COMMIT; se ejecuta como bloque.
    await client.query(sql);
    const after = await client.query("SELECT count(*)::int AS n FROM items WHERE numero_poliza='124'");
    console.log('items en poliza 124 DESPUES:', after.rows[0].n);
  } catch (e) {
    console.error('ERROR:', e.message);
    process.exit(1);
  } finally {
    await client.end();
  }
})();
