// Crea tabla email_templates y seedea con los 2 templates del liquidador
// (vehículo / no-vehículo) que hoy están hardcoded en notifica_liquidador.php.
//
// Reunión Adriana 21-abr-2026: administrador de plantillas de correo.
//
// Uso:
//   PG_HOST=... PG_USER=... PG_PASSWORD=... node infra/apply_email_templates_sp.js
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

const schema = `
CREATE TABLE IF NOT EXISTS email_templates (
  id BIGSERIAL PRIMARY KEY,
  codigo TEXT NOT NULL UNIQUE,
  nombre TEXT NOT NULL,
  modulo TEXT NOT NULL DEFAULT 'siniestros',
  asunto TEXT NOT NULL,
  cuerpo_texto TEXT NOT NULL,
  cuerpo_html TEXT NULL,
  variables JSONB NOT NULL DEFAULT '[]'::jsonb,
  activo BOOLEAN NOT NULL DEFAULT true,
  updated_at TIMESTAMP NOT NULL DEFAULT now(),
  created_at TIMESTAMP NOT NULL DEFAULT now(),
  updated_by TEXT NULL
);
CREATE INDEX IF NOT EXISTS idx_email_templates_modulo_activo
  ON email_templates(modulo, activo);
`;

const varsLiquidador = JSON.stringify([
  { nombre: 'liquidador_nombre',         descripcion: 'Nombre del liquidador',                        ejemplo: 'Juan Pérez' },
  { nombre: 'nombre_asegurado',          descripcion: 'Nombre del asegurado del siniestro',           ejemplo: 'María Soto' },
  { nombre: 'numero_siniestro',          descripcion: 'Número de siniestro asignado por la compañía', ejemplo: '2026-001234' },
  { nombre: 'numero_carpeta_liquidador', descripcion: 'Número de carpeta del liquidador (opcional)',  ejemplo: 'CRP-0012' },
  { nombre: 'numero_poliza',             descripcion: 'Número de la póliza',                          ejemplo: 'POL-56789' },
  { nombre: 'ramo',                      descripcion: 'Ramo del siniestro',                           ejemplo: 'VEHÍCULOS' },
  { nombre: 'carpeta_suffix',            descripcion: 'Texto pre-armado " — Carpeta X" o vacío',      ejemplo: ' — Carpeta CRP-0012' }
]);

const asunto = 'Siniestro N° {{ numero_siniestro }}{{ carpeta_suffix }} — {{ nombre_asegurado }}';

const cuerpoVeh =
`Estimado/a {{ liquidador_nombre|liquidador }},

Se le informa que el vehículo del asegurado {{ nombre_asegurado }} (siniestro {{ numero_siniestro }}) asistió a revisión en el taller designado.

Por favor proceder con la orden de reparación.

Saludos cordiales,
Adriana`;

const cuerpoNoVeh =
`Estimado/a {{ liquidador_nombre|liquidador }},

Le informo que el asegurado {{ nombre_asegurado }} (siniestro {{ numero_siniestro }}) ya entregó todos los documentos pendientes a su cargo.

Agradeceré proceder con la generación del finiquito.

Saludos cordiales,
Adriana`;

(async () => {
  try {
    await client.connect();
    await client.query(schema);
    console.log('Schema OK.');

    await client.query(`
      INSERT INTO email_templates (codigo, nombre, modulo, asunto, cuerpo_texto, variables)
      VALUES
        ('siniestro_liquidador_vehiculo',    'Notificación al liquidador — vehículos',
         'siniestros', $1, $2, $4::jsonb),
        ('siniestro_liquidador_no_vehiculo', 'Notificación al liquidador — no vehículos',
         'siniestros', $1, $3, $4::jsonb)
      ON CONFLICT (codigo) DO NOTHING
    `, [asunto, cuerpoVeh, cuerpoNoVeh, varsLiquidador]);
    console.log('Seed aplicado.');

    const r = await client.query(
      "SELECT codigo, nombre, substring(asunto for 60) AS asunto FROM email_templates ORDER BY codigo"
    );
    console.log('Templates registrados:', r.rows);
  } catch (e) {
    console.error('ERROR:', e.message);
    process.exit(1);
  } finally {
    await client.end();
  }
})();
