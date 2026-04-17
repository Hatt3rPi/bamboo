// Crea tablas siniestros_pendientes y siniestros_pendientes_bitacora.
// Reunión Adriana 16-abr-2026: nuevo modelo de seguimiento documental
// centrado en "¿Quién la lleva?" (Cliente/Liquidador/Compañía).
//
// Uso:
//   PG_HOST=... PG_USER=... PG_PASSWORD=... node infra/apply_siniestros_pendientes_sp.js
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
CREATE TABLE IF NOT EXISTS siniestros_pendientes (
  id BIGSERIAL PRIMARY KEY,
  id_siniestro BIGINT NOT NULL REFERENCES siniestros(id) ON DELETE CASCADE,
  id_bien BIGINT NULL REFERENCES siniestros_bienes_afectados(id) ON DELETE SET NULL,
  responsable TEXT NOT NULL CHECK (responsable IN ('Cliente','Liquidador','Compañía')),
  descripcion TEXT NOT NULL,
  estado TEXT NOT NULL DEFAULT 'Pendiente' CHECK (estado IN ('Pendiente','Entregado','No aplica')),
  fecha_creacion TIMESTAMP NOT NULL DEFAULT now(),
  fecha_entrega DATE NULL,
  notas TEXT NULL,
  usuario_creacion TEXT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_siniestros_pendientes_id_siniestro ON siniestros_pendientes(id_siniestro);
CREATE INDEX IF NOT EXISTS idx_siniestros_pendientes_responsable_estado ON siniestros_pendientes(responsable, estado);

CREATE TABLE IF NOT EXISTS siniestros_pendientes_bitacora (
  id BIGSERIAL PRIMARY KEY,
  id_pendiente BIGINT NOT NULL REFERENCES siniestros_pendientes(id) ON DELETE CASCADE,
  accion TEXT NOT NULL,
  estado_anterior TEXT NULL,
  estado_nuevo TEXT NULL,
  responsable_anterior TEXT NULL,
  responsable_nuevo TEXT NULL,
  usuario TEXT NULL,
  timestamp TIMESTAMP NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_siniestros_pendientes_bitacora_id_pendiente
  ON siniestros_pendientes_bitacora(id_pendiente);
`;

(async () => {
  try {
    await client.connect();
    console.log(`Conectado a ${process.env.PG_HOST}.`);
    await client.query(migration);
    console.log('Migración aplicada OK.');

    const check = await client.query(`
      SELECT table_name
      FROM information_schema.tables
      WHERE table_name IN ('siniestros_pendientes','siniestros_pendientes_bitacora')
      ORDER BY table_name
    `);
    console.log('Tablas creadas:', check.rows.map(r => r.table_name));
  } catch (e) {
    console.error('ERROR:', e.message);
    process.exit(1);
  } finally {
    await client.end();
  }
})();
