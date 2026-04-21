// Agrega columna dias_alarma a siniestros_pendientes para distinguir
// plazos de alarma según tipo de tarea (24h compañía/liquidador vs 4 días cliente).
// Reunión Adriana 21-abr-2026: cadena automática de tareas iniciales.
//
// Uso:
//   PG_HOST=... PG_USER=... PG_PASSWORD=... node infra/apply_siniestros_pendientes_autocadena_sp.js
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
ALTER TABLE siniestros_pendientes ADD COLUMN IF NOT EXISTS dias_alarma INTEGER NOT NULL DEFAULT 1;
ALTER TABLE siniestros_pendientes ADD COLUMN IF NOT EXISTS auto_generada BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE siniestros_pendientes ADD COLUMN IF NOT EXISTS codigo_tarea TEXT NULL;
CREATE INDEX IF NOT EXISTS idx_siniestros_pendientes_codigo_tarea
  ON siniestros_pendientes(id_siniestro, codigo_tarea) WHERE codigo_tarea IS NOT NULL;
`;

(async () => {
  try {
    await client.connect();
    console.log(`Conectado a ${process.env.PG_HOST}.`);
    await client.query(migration);
    console.log('Migración aplicada OK.');

    const check = await client.query(`
      SELECT column_name, data_type, column_default
      FROM information_schema.columns
      WHERE table_name='siniestros_pendientes'
        AND column_name IN ('dias_alarma','auto_generada','codigo_tarea')
      ORDER BY column_name
    `);
    console.log('Columnas:', check.rows);
  } catch (e) {
    console.error('ERROR:', e.message);
    process.exit(1);
  } finally {
    await client.end();
  }
})();
