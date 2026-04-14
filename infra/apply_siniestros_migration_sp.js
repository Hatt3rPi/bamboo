// Aplica la migración de Siniestros al proyecto Supabase de São Paulo.
// El MCP apuntaba a Ohio (vrbpzhmsybpfaobrpkoj) por error — este script cubre el gap.
//
// Uso:
//   PG_HOST=aws-1-sa-east-1.pooler.supabase.com \
//   PG_PORT=5432 \
//   PG_USER=postgres.dynnhfqpagwkdynzubmh \
//   PG_PASSWORD=xxxxx \
//   PG_DATABASE=postgres \
//   node infra/apply_siniestros_migration_sp.js
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
CREATE TABLE IF NOT EXISTS siniestros (
  id BIGSERIAL PRIMARY KEY,
  numero_siniestro TEXT NULL,
  id_poliza BIGINT NULL,
  numero_poliza TEXT NULL,
  ramo TEXT NULL,
  tipo_siniestro TEXT NULL,
  fecha_ocurrencia DATE NULL,
  fecha_denuncia DATE NULL,
  rut_asegurado TEXT NULL,
  dv_asegurado TEXT NULL,
  nombre_asegurado TEXT NULL,
  telefono_asegurado TEXT NULL,
  correo_asegurado TEXT NULL,
  descripcion TEXT NULL,
  liquidador_nombre TEXT NULL,
  liquidador_telefono TEXT NULL,
  liquidador_correo TEXT NULL,
  numero_carpeta_liquidador TEXT NULL,
  patente TEXT NULL,
  marca TEXT NULL,
  modelo TEXT NULL,
  anio_vehiculo INTEGER NULL,
  taller_nombre TEXT NULL,
  taller_telefono TEXT NULL,
  estado TEXT NOT NULL DEFAULT 'Abierto',
  presentado BOOLEAN NOT NULL DEFAULT TRUE,
  token TEXT NULL,
  usuario_registro TEXT NULL,
  fecha_ingreso TIMESTAMP NOT NULL DEFAULT now()
);
ALTER TABLE siniestros ADD COLUMN IF NOT EXISTS numero_carpeta_liquidador TEXT NULL;

CREATE INDEX IF NOT EXISTS idx_siniestros_id_poliza ON siniestros(id_poliza);
CREATE INDEX IF NOT EXISTS idx_siniestros_numero_poliza ON siniestros(numero_poliza);
CREATE INDEX IF NOT EXISTS idx_siniestros_estado ON siniestros(estado);
CREATE INDEX IF NOT EXISTS idx_siniestros_fecha_ingreso ON siniestros(fecha_ingreso DESC);

CREATE TABLE IF NOT EXISTS siniestros_items (
  id BIGSERIAL PRIMARY KEY,
  id_siniestro BIGINT NOT NULL REFERENCES siniestros(id) ON DELETE CASCADE,
  numero_item INTEGER NOT NULL,
  UNIQUE(id_siniestro, numero_item)
);
CREATE INDEX IF NOT EXISTS idx_siniestros_items_id_siniestro ON siniestros_items(id_siniestro);

CREATE TABLE IF NOT EXISTS siniestros_bitacora (
  id BIGSERIAL PRIMARY KEY,
  id_siniestro BIGINT NOT NULL REFERENCES siniestros(id) ON DELETE CASCADE,
  estado_anterior TEXT NULL,
  estado_nuevo TEXT NOT NULL,
  usuario TEXT NOT NULL,
  motivo TEXT NULL,
  "timestamp" TIMESTAMP NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_siniestros_bitacora_id_siniestro ON siniestros_bitacora(id_siniestro);

UPDATE siniestros SET estado = 'En proceso' WHERE estado = 'En Proceso';
`;

(async () => {
  try {
    await client.connect();
    console.log(`Conectado a ${process.env.PG_HOST}.`);
    await client.query(migration);
    console.log('Migración aplicada OK.');

    const check = await client.query(`
      SELECT
        (SELECT COUNT(*) FROM information_schema.tables WHERE table_name='siniestros') AS t_siniestros,
        (SELECT COUNT(*) FROM information_schema.tables WHERE table_name='siniestros_items') AS t_items,
        (SELECT COUNT(*) FROM information_schema.tables WHERE table_name='siniestros_bitacora') AS t_bitacora,
        (SELECT COUNT(*) FROM information_schema.columns WHERE table_name='siniestros' AND column_name='numero_carpeta_liquidador') AS col_carpeta
    `);
    console.log('Verificación:', check.rows[0]);
  } catch (e) {
    console.error('ERROR:', e.message);
    process.exit(1);
  } finally {
    await client.end();
  }
})();
