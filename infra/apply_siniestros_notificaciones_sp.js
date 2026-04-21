// Tabla de log de correos enviados desde Bamboo (Brevo).
// Reunión 21-abr-2026: email automático al liquidador.
//
// Uso:
//   PG_HOST=... PG_USER=... PG_PASSWORD=... node infra/apply_siniestros_notificaciones_sp.js
const { Client } = require('pg');
const required = ['PG_HOST', 'PG_USER', 'PG_PASSWORD'];
for (const k of required) {
  if (!process.env[k]) { console.error(`Falta ${k}`); process.exit(1); }
}
const client = new Client({
  host: process.env.PG_HOST,
  port: parseInt(process.env.PG_PORT || '5432', 10),
  user: process.env.PG_USER, password: process.env.PG_PASSWORD,
  database: process.env.PG_DATABASE || 'postgres',
  ssl: { rejectUnauthorized: false }
});
const migration = `
CREATE TABLE IF NOT EXISTS siniestros_notificaciones_enviadas (
  id BIGSERIAL PRIMARY KEY,
  id_siniestro BIGINT NOT NULL REFERENCES siniestros(id) ON DELETE CASCADE,
  destinatario_email TEXT NOT NULL,
  destinatario_nombre TEXT NULL,
  asunto TEXT NOT NULL,
  cuerpo TEXT NOT NULL,
  proveedor TEXT NOT NULL DEFAULT 'brevo',
  proveedor_message_id TEXT NULL,
  estado TEXT NOT NULL DEFAULT 'enviado' CHECK (estado IN ('enviado','error')),
  error_detalle TEXT NULL,
  tipo TEXT NULL,
  usuario TEXT NULL,
  timestamp TIMESTAMP NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_siniestros_notif_id_siniestro
  ON siniestros_notificaciones_enviadas(id_siniestro, timestamp DESC);
`;
(async () => {
  try {
    await client.connect();
    await client.query(migration);
    console.log('Migración aplicada OK.');
    const check = await client.query(`SELECT column_name FROM information_schema.columns
                                       WHERE table_name='siniestros_notificaciones_enviadas'
                                       ORDER BY ordinal_position`);
    console.log('Columnas:', check.rows.map(r => r.column_name));
  } catch (e) { console.error('ERROR:', e.message); process.exit(1); }
  finally { await client.end(); }
})();
