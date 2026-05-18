# Reunión Adriana — Demo flujo vehículo + revisión no-vehículo (2026-05-18)

Participantes: Felipe Abarca, Adriana Sandoval.
Duración: ~40 min. Demo en vivo del flujo vehículo + caminata por el flujo no-vehículo.
Transcripción origen: `Bamboo Transcripción (4).txt` (18-may-2026).
Próxima reunión: **lunes 25-may-2026, 10:00 AM**.
Migración a producción: **lunes 25-may, ~20:30** (Felipe avisa a Adriana de no trabajar desde las 17:00).
Marcha blanca: **martes 26-may** en adelante.

## Síntesis ejecutiva

1. **Flujo vehículo aprobado** "casi terminado". Adriana quiere probar en marcha blanca real.
2. **Eliminar `liquidador_contacto` del flujo vehículo**. La compañía entrega el taller al cliente directo en `compania_entrega_numero`. No hay paso intermedio donde el liquidador "tome contacto".
3. **Flujo no-vehículo** caminado en detalle:
   - Tipos: sacar "Choque/Colisión" (no aplica).
   - Modal del bien: sacar categoría "Vehículo".
   - Dirección + descripción: hay redundancia, simplificar.
   - Categoría confusa para no-vehículo. Solo importan los tabs "Daño propio" vs "Daño a terceros".
4. **`cliente_firma_finiquito` queda con texto "finiquito"** por ahora. Adriana sabe que técnicamente el preinforme va antes pero no quiere modelar el bucle preinforme→aprueba/rechaza ahora. *"Déjalo en finiquito y vamos viendo cómo se va comportando."*
5. **Correos automáticos pendientes**:
   - Al cerrar `cliente_entrega` (no-veh) → al liquidador ("cliente entregó antecedentes").
   - Al cerrar `cliente_firma_finiquito` → al liquidador (la plantilla `siniestro_liquidador_cliente_firmo` ya existe).
6. **Brevo**: Felipe le anuncia que tiene Plan B si Planet Hosting no destrabe el DNS — 20 min de trabajo, sin costo (todavía gratis). Lo aplicará si soporte no responde.

## Cambios estructurales del flujo

### Vehículo

| # | Cambio | Razón |
|---|---|---|
| 1 | **Eliminar `liquidador_contacto`** | El cliente recibe los datos del taller directo en `compania_entrega_numero`. No hay "primer contacto" del liquidador en vehículo (es interno de la compañía). |
| 2 | N° carpeta liquidador NO aplica en vehículo | Liquidadores internos no usan carpeta. |
| 3 | Mantener resto: `compania_entrega_numero` → `cliente_entrega` (lleva auto para evaluación) → `liquidador_accion` (orden reparación) → `taller_disponibilidad_repuestos` → `cliente_ingreso_taller` → `taller_fecha_entrega` → `liquidador_envio_compania` → cierre. | Validado en demo. |

### No-vehículo

| # | Cambio | Razón |
|---|---|---|
| 1 | Sacar "Choque/Colisión" del dropdown de tipo | Solo aplica a vehículo. |
| 2 | Sacar categoría "Vehículo" del modal del bien | "Vehículo no corre aquí." |
| 3 | Eliminar duplicación dirección/descripción | Adriana: confusa. La descripción del bien debería autocompletarse de la dirección, o la dirección no debería pedirse separadamente. |
| 4 | Mantener: `compania_entrega_numero` → `liquidador_contacto` (carpeta opcional) → `cliente_entrega` (antecedentes) → `liquidador_accion` (preinforme) → `cliente_firma_finiquito` → `liquidador_envio_compania` → `compania_pago` → cierre. | Validado en demo. |

## Correos automáticos

Estado actual:
- ✅ `cliente_entrega` (veh) → liquidador (orden de reparación) — plantilla `siniestro_cliente_llevo_vehiculo`.
- ✅ `taller_disponibilidad_repuestos` → cliente — plantilla `siniestro_taller_disponibilidad_repuestos`.

Pendientes de wirear:
- ⛔ `cliente_entrega` (no-veh) → liquidador (cliente entregó antecedentes). Plantilla puede ser `siniestro_liquidador_no_vehiculo` (existe pero no se dispara hoy en este punto).
- ⛔ `cliente_firma_finiquito` → liquidador. Plantilla `siniestro_liquidador_cliente_firmo` existe pero no está wireada.

## Caso de bucle preinforme/rechazo (no se modela ahora)

Adriana describió el caso real:
- Liquidador manda preinforme.
- Cliente revisa. Si conforme → mismo doc se convierte en finiquito, lo firma, va al notario.
- Si NO conforme → preinforme vuelve al liquidador. Reciclo.

Decisión: NO modelar el rechazo ahora. *"Hasta hasta que ahí llegamos ahí llegamos."* La descripción del estado se queda como "finiquito" (no "preinforme") en `cliente_firma_finiquito` por ahora.

## Migración a producción

- **Lunes 25-may ~20:30**: Felipe migra BD. Adriana no debe trabajar desde las 17:00.
- **Martes 26-may**: arranca marcha blanca con todas las nuevas funcionalidades.
- Antes del lunes 25, Felipe se compromete a presentar el cambio de colores/look & feel terminado.

## Plan de acción priorizado (para discutir)

### Crítico para llegar al lunes 25
- A1. **Eliminar `liquidador_contacto` del flujo vehículo** (cambio en helper + UPDATE pendientes en BD si los hay).
- A2. **Filtros no-vehículo**: sacar "Choque/Colisión" del tipo, sacar "Vehículo" de la categoría del bien.
- A3. **Correos automáticos faltantes** (cliente_entrega no-veh + cliente_firma_finiquito).
- A4. **Cambio de colores / look & feel** (branch redesign — requiere mergear con master).
- A5. **Migración BD** (script + ventana 20:30).

### Diferido (no crítico)
- B1. Simplificación de dirección/descripción del bien para no-vehículo.
- B2. Bucle preinforme/rechazo (Adriana lo dejó para más adelante).
- B3. Etapa "Cliente coordina fecha de revisión" (pendiente desde 11-may).

### Bloqueado por terceros
- Brevo DNS de `bambooseguros.cl`: ticket abierto a Planet Hosting. Si no responden, Felipe aplica plan B.
