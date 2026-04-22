// Incremento E — Cierre del flujo de siniestros (reunión Adriana 22-abr-2026).
//
// Cambios:
//   1. siniestros_bienes_afectados: + direccion, + item_afectado.
//   2. siniestros: + compania_contacto_nombre/mail.
//   3. siniestros_pendientes: ampliar CHECK(responsable) con 'Taller'.
//   4. email_templates: quitar "todos" del cuerpo no-vehículo + 4 plantillas nuevas.
//
// Uso:
//   PG_HOST=... PG_USER=... PG_PASSWORD=... node infra/apply_siniestros_incremento_e_sp.js
const { Client } = require('pg');

const required = ['PG_HOST', 'PG_USER', 'PG_PASSWORD'];
for (const k of required) {
  if (!process.env[k]) { console.error(`Falta ${k}`); process.exit(1); }
}

const client = new Client({
  host: process.env.PG_HOST,
  port: parseInt(process.env.PG_PORT || '5432', 10),
  user: process.env.PG_USER,
  password: process.env.PG_PASSWORD,
  database: process.env.PG_DATABASE || 'postgres',
  ssl: { rejectUnauthorized: false }
});

const schema = `
-- 1. Bienes afectados: dirección (incendio) + ítem afectado (vehículos/otros)
ALTER TABLE siniestros_bienes_afectados
  ADD COLUMN IF NOT EXISTS direccion TEXT NULL,
  ADD COLUMN IF NOT EXISTS item_afectado TEXT NULL;

-- 2. Siniestro: contacto de compañía (principalmente para incendio)
ALTER TABLE siniestros
  ADD COLUMN IF NOT EXISTS compania_contacto_nombre TEXT NULL,
  ADD COLUMN IF NOT EXISTS compania_contacto_mail   TEXT NULL;

-- 3. Pendientes: permitir responsable 'Taller'
DO $$
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.constraint_column_usage
    WHERE table_name = 'siniestros_pendientes' AND constraint_name LIKE '%responsable%'
  ) THEN
    EXECUTE 'ALTER TABLE siniestros_pendientes DROP CONSTRAINT IF EXISTS siniestros_pendientes_responsable_check';
  END IF;
  EXECUTE $sql$
    ALTER TABLE siniestros_pendientes
      ADD CONSTRAINT siniestros_pendientes_responsable_check
      CHECK (responsable IN ('Cliente','Liquidador','Compañía','Taller'))
  $sql$;
EXCEPTION WHEN duplicate_object THEN
  -- ya existe con el nuevo set de valores, seguir
END$$;
`;

// Plantilla no-vehículo actualizada (SIN la palabra "todos") — cambio pedido por Adriana.
const cuerpoNoVeh =
`Estimado/a {{ liquidador_nombre|liquidador }},

Le informo que el asegurado {{ nombre_asegurado }} (siniestro {{ numero_siniestro }}) ya entregó los documentos pendientes a su cargo.

Agradeceré proceder con la generación del finiquito.

Saludos cordiales,
Adriana`;

// Nuevas plantillas del incremento E
const varsCliente = JSON.stringify([
  { nombre: 'nombre_asegurado',    descripcion: 'Asegurado',              ejemplo: 'María Soto' },
  { nombre: 'numero_siniestro',    descripcion: 'N° de siniestro',        ejemplo: '2026-001234' },
  { nombre: 'numero_poliza',       descripcion: 'N° de póliza',           ejemplo: 'POL-56789' },
  { nombre: 'tipo_siniestro',      descripcion: 'Tipo de siniestro',      ejemplo: 'Choque' }
]);

const asuntoStd = 'Siniestro N° {{ numero_siniestro }} — {{ nombre_asegurado }}';

const cuerpoClienteOrdenReparacion =
`Estimado/a {{ nombre_asegurado }},

Le informo que la compañía ya emitió la orden de reparación para el siniestro N° {{ numero_siniestro }}.

Por favor, avíseme en cuanto ingrese el vehículo al taller para coordinar el seguimiento.

Saludos cordiales,
Adriana`;

const cuerpoLiquidadorClienteFirmo =
`Estimado/a {{ liquidador_nombre|liquidador }},

El asegurado {{ nombre_asegurado }} (siniestro {{ numero_siniestro }}) devolvió el finiquito firmado.

Agradeceré confirmar la fecha de envío del finiquito a la compañía.

Saludos cordiales,
Adriana`;

const cuerpoCompaniaFechaPago =
`Estimado/a {{ compania_contacto_nombre|equipo de indemnizaciones }},

Agradeceré informar la fecha de indemnización o transferencia al cliente para el siniestro N° {{ numero_siniestro }} (asegurado {{ nombre_asegurado }}, póliza {{ numero_poliza }}).

Saludos cordiales,
Adriana`;

const cuerpoRecordatorioAmigable =
`Estimado/a {{ destinatario_nombre|equipo }},

Les envío un recordatorio amable respecto del siniestro N° {{ numero_siniestro }} ({{ nombre_asegurado }}).

Tenemos pendiente: {{ descripcion_pendiente }}.

Agradeceré me confirmen el estado para mantener actualizado el seguimiento.

Saludos cordiales,
Adriana`;

const varsRecordatorio = JSON.stringify([
  { nombre: 'destinatario_nombre', descripcion: 'Nombre del destinatario',        ejemplo: 'Juan Pérez' },
  { nombre: 'nombre_asegurado',    descripcion: 'Asegurado',                      ejemplo: 'María Soto' },
  { nombre: 'numero_siniestro',    descripcion: 'N° de siniestro',                ejemplo: '2026-001234' },
  { nombre: 'descripcion_pendiente', descripcion: 'Texto del pendiente actual',   ejemplo: 'Respuesta del liquidador' }
]);

const varsLiquidador = JSON.stringify([
  { nombre: 'liquidador_nombre',   descripcion: 'Nombre del liquidador',          ejemplo: 'Juan Pérez' },
  { nombre: 'nombre_asegurado',    descripcion: 'Asegurado',                      ejemplo: 'María Soto' },
  { nombre: 'numero_siniestro',    descripcion: 'N° de siniestro',                ejemplo: '2026-001234' }
]);

const varsCompania = JSON.stringify([
  { nombre: 'compania_contacto_nombre', descripcion: 'Contacto de la compañía',   ejemplo: 'Paula Díaz' },
  { nombre: 'nombre_asegurado',    descripcion: 'Asegurado',                      ejemplo: 'María Soto' },
  { nombre: 'numero_siniestro',    descripcion: 'N° de siniestro',                ejemplo: '2026-001234' },
  { nombre: 'numero_poliza',       descripcion: 'N° de póliza',                   ejemplo: 'POL-56789' }
]);

(async () => {
  try {
    await client.connect();
    console.log(`Conectado a ${process.env.PG_HOST}.`);
    await client.query(schema);
    console.log('Schema aplicado OK.');

    // Plantilla existente — actualizar cuerpo (quitar "todos")
    await client.query(
      `UPDATE email_templates SET cuerpo_texto = $1, updated_at = now()
       WHERE codigo = 'siniestro_liquidador_no_vehiculo'`,
      [cuerpoNoVeh]
    );
    console.log('Template siniestro_liquidador_no_vehiculo actualizado (sin "todos").');

    // Plantillas nuevas — ON CONFLICT DO NOTHING para ser idempotente
    await client.query(`
      INSERT INTO email_templates (codigo, nombre, modulo, asunto, cuerpo_texto, variables)
      VALUES
        ('siniestro_cliente_orden_reparacion', 'Aviso al cliente — orden de reparación emitida',
         'siniestros', $1, $2, $3::jsonb),
        ('siniestro_liquidador_cliente_firmo', 'Aviso al liquidador — cliente firmó finiquito',
         'siniestros', $1, $4, $5::jsonb),
        ('siniestro_compania_fecha_pago',      'Aviso a compañía — fecha de pago',
         'siniestros', $1, $6, $7::jsonb),
        ('siniestro_recordatorio_amigable',    'Recordatorio amigable al responsable actual',
         'siniestros', $1, $8, $9::jsonb)
      ON CONFLICT (codigo) DO NOTHING
    `, [
      asuntoStd,
      cuerpoClienteOrdenReparacion, varsCliente,
      cuerpoLiquidadorClienteFirmo, varsLiquidador,
      cuerpoCompaniaFechaPago,      varsCompania,
      cuerpoRecordatorioAmigable,   varsRecordatorio
    ]);
    console.log('Seed plantillas nuevas aplicado.');

    const r = await client.query(
      "SELECT codigo, nombre FROM email_templates WHERE modulo='siniestros' ORDER BY codigo"
    );
    console.log('Templates siniestros:', r.rows);
  } catch (e) {
    console.error('ERROR:', e.message);
    process.exit(1);
  } finally {
    await client.end();
  }
})();
