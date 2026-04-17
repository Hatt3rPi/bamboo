// Agrega columna observaciones TEXT NULL a siniestros.
// Reunión Adriana 16-abr-2026: campo libre adicional a descripcion.
//
// Uso:
//   PG_HOST=... PG_USER=... PG_PASSWORD=... node infra/apply_siniestros_observaciones_sp.js
const { Client } = require('pg');

const required = ['PG_HOST', 'PG_USER', 'PG_PASSWORD'];
for (const k of required) {
  if (!process.env[k]) {
    console.error(`Falta variable de entorno: ${k}`);
    process.exit(1);
  }
}

const client = new Client({
  host: process.env.PG_HOST,
  port: parseInt(process.env.PG_PORT || '5432', 10),
  user: process.env.PG_USER,
  password: process.env.PG_PASSWORD,
  database: process.env.PG_DATABASE || 'postgres',
  ssl: { rejectUnauthorized: false }
});

const migration = `
ALTER TABLE siniestros ADD COLUMN IF NOT EXISTS observaciones TEXT NULL;
`;

(async () => {
  try {
    await client.connect();
    console.log(`Conectado a ${process.env.PG_HOST}.`);
    await client.query(migration);
    console.log('Migración aplicada OK.');

    const check = await client.query(`
      SELECT column_name, data_type, is_nullable
      FROM information_schema.columns
      WHERE table_name='siniestros' AND column_name='observaciones'
    `);
    console.log('Columna observaciones:', check.rows);
  } catch (e) {
    console.error('ERROR:', e.message);
    process.exit(1);
  } finally {
    await client.end();
  }
})();
