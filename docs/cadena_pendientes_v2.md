# Cadena automática de pendientes — v2 (11-may-2026)

Diagrama visual de las tareas que se generan automáticamente en el flujo de
siniestros, los datos que captura cada modal "Marcar como Entregado" y dónde
se persiste cada uno.

Fuente de verdad: `bamboo/backend/siniestros/helper_cadena_pendientes.php` y
`bamboo/backend/siniestros/actualiza_pendiente.php` (switch por `codigo_tarea`).

---

## 🚗 Vehículo

```
┌─────────────────────────────────────────────────────────────┐
│  CREACIÓN DEL SINIESTRO                                     │
│  Usuario crea siniestro desde la póliza                     │
│  → Se genera 1ª tarea (Compañía) + registro histórico       │
│    "Creación siniestro" (auto-entregado)                    │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  [1] COMPAÑÍA · 24h          codigo: compania_entrega_numero│
│  ─────────────────────────────────────────────────────────  │
│  "Compañía debe entregar N° de siniestro y liquidador       │
│   asignado."                                                │
│                                                             │
│  📥 Al cerrar pide:                                         │
│    • N° Siniestro Compañía                                  │
│    • Liquidador: dropdown (conocidos) o nombre + tel/correo │
│                                                             │
│  💾 Persiste en: siniestros.numero_siniestro,               │
│     liquidador_nombre/telefono/correo + tabla liquidadores  │
│  ⚡ Efecto: estado siniestro → Abierto                      │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  [2] LIQUIDADOR · 24h            codigo: liquidador_contacto│
│  ─────────────────────────────────────────────────────────  │
│  "Liquidador toma contacto con el cliente y le entrega los  │
│   datos del taller designado."                              │
│                                                             │
│  📥 Al cerrar pide (por cada bien vehicular):               │
│    • Nombre del taller                                      │
│    • Teléfono del taller                                    │
│    • Correo del taller                                      │
│                                                             │
│  💾 Persiste en: siniestros_bienes_afectados.taller_*       │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  [3] CLIENTE · 4 días                codigo: cliente_entrega│
│  ─────────────────────────────────────────────────────────  │
│  "Cliente lleva el vehículo al taller designado."           │
│                                                             │
│  📥 Al cerrar pide:                                         │
│    • Notas (opcional)                                       │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  [4] LIQUIDADOR · 24h            codigo: liquidador_accion  │
│  ─────────────────────────────────────────────────────────  │
│  "Liquidador emite la orden de reparación."                 │
│                                                             │
│  📥 Al cerrar pide (por cada bien vehicular):               │
│    • Fecha orden de reparación                              │
│    • [ ] Hay importación de repuestos                       │
│    • Observación de importación (si flag activo)            │
│                                                             │
│  💾 Persiste en: siniestros_bienes_afectados.               │
│     liquidador_fecha_orden_reparacion, importacion_*        │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  [5] CLIENTE · 2 días        codigo: cliente_ingreso_taller │
│  ─────────────────────────────────────────────────────────  │
│  "Cliente debe avisar el día de ingreso del vehículo al     │
│   taller."                                                  │
│                                                             │
│  📥 Al cerrar pide (por cada bien vehicular):               │
│    • Fecha ingreso al taller                                │
│                                                             │
│  💾 Persiste en: siniestros_bienes_afectados.               │
│     cliente_fecha_ingreso_taller                            │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  [6] TALLER · 5 días hábiles  codigo: taller_fecha_entrega  │
│  ─────────────────────────────────────────────────────────  │
│  "Taller debe confirmar la fecha de entrega del vehículo."  │
│                                                             │
│  📥 Al cerrar pide (por cada bien vehicular):               │
│    • Fecha compromiso de entrega                            │
│                                                             │
│  💾 Persiste en: siniestros_bienes_afectados.               │
│     taller_fecha_compromiso_entrega                         │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  [7] LIQUIDADOR · 24h    codigo: liquidador_envio_compania  │
│  ─────────────────────────────────────────────────────────  │
│  "Liquidador debe confirmar el envío del finiquito a la     │
│   compañía."                                                │
│                                                             │
│  📥 Al cerrar pide:                                         │
│    • Fecha de envío                                         │
│                                                             │
│  💾 Persiste en: siniestros.liquidador_fecha_envio_compania │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  [8] COMPAÑÍA · 3 días              codigo: compania_pago   │
│  ─────────────────────────────────────────────────────────  │
│  "Compañía debe confirmar fecha de indemnización /          │
│   transferencia al cliente."                                │
│                                                             │
│  📥 Al cerrar pide:                                         │
│    • Fecha de pago                                          │
│                                                             │
│  💾 Persiste en: siniestros.compania_fecha_pago             │
│  ⚡ Efecto: estado siniestro → 🔒 Cerrado (automático)      │
└─────────────────────────────────────────────────────────────┘
```

---

## 🏠 No vehículo (incendio / daños materiales / etc.)

```
┌─────────────────────────────────────────────────────────────┐
│  CREACIÓN DEL SINIESTRO                                     │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  [1] COMPAÑÍA · 24h          codigo: compania_entrega_numero│
│  ─────────────────────────────────────────────────────────  │
│  "Compañía debe entregar N° de siniestro y liquidador       │
│   asignado."                                                │
│                                                             │
│  📥 Al cerrar pide:                                         │
│    • N° Siniestro Compañía                                  │
│    • Liquidador: dropdown o nombre + tel/correo             │
│                                                             │
│  💾 Persiste en: siniestros.numero_siniestro + liquidador_* │
│  ⚡ Efecto: estado siniestro → Abierto                      │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  [2] LIQUIDADOR · 24h            codigo: liquidador_contacto│
│  ─────────────────────────────────────────────────────────  │
│  "Liquidador pide antecedentes al cliente."                 │
│                                                             │
│  📥 Al cerrar pide:                                         │
│    • N° Carpeta Liquidador (opcional)                       │
│                                                             │
│  💾 Persiste en: siniestros.numero_carpeta_liquidador       │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  [3] CLIENTE · 4 días                codigo: cliente_entrega│
│  ─────────────────────────────────────────────────────────  │
│  "Cliente entrega los antecedentes solicitados."            │
│                                                             │
│  📥 Al cerrar pide:                                         │
│    • Notas (opcional)                                       │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  [4] LIQUIDADOR · 24h            codigo: liquidador_accion  │
│  ─────────────────────────────────────────────────────────  │
│  "Liquidador genera el finiquito."                          │
│                                                             │
│  📥 Al cerrar pide:                                         │
│    • Fecha de generación del finiquito                      │
│                                                             │
│  💾 Persiste en: siniestros_bienes_afectados.               │
│     liquidador_fecha_finiquito (todos los bienes propios)   │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  [5] CLIENTE · 4 días     codigo: cliente_firma_finiquito   │
│  ─────────────────────────────────────────────────────────  │
│  "Cliente debe firmar y devolver el finiquito."             │
│                                                             │
│  📥 Al cerrar pide:                                         │
│    • Fecha firma finiquito                                  │
│                                                             │
│  💾 Persiste en: siniestros_bienes_afectados.               │
│     cliente_fecha_firma_finiquito                           │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  [6] LIQUIDADOR · 24h    codigo: liquidador_envio_compania  │
│  ─────────────────────────────────────────────────────────  │
│  "Liquidador debe confirmar el envío del finiquito a la     │
│   compañía."                                                │
│                                                             │
│  📥 Al cerrar pide:                                         │
│    • Fecha de envío                                         │
│    • Contacto compañía: nombre + correo                     │
│                                                             │
│  💾 Persiste en: siniestros.liquidador_fecha_envio_compania │
│     + siniestros.compania_contacto_nombre / _mail           │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  [7] COMPAÑÍA · 3 días              codigo: compania_pago   │
│  ─────────────────────────────────────────────────────────  │
│  "Compañía debe confirmar fecha de indemnización /          │
│   transferencia al cliente."                                │
│                                                             │
│  📥 Al cerrar pide:                                         │
│    • Fecha de pago                                          │
│                                                             │
│  💾 Persiste en: siniestros.compania_fecha_pago             │
│  ⚡ Efecto: estado siniestro → 🔒 Cerrado (automático)      │
└─────────────────────────────────────────────────────────────┘
```

---

## Diferencias clave entre los dos flujos

| # | Punto | Vehículo | No vehículo |
|---|---|---|---|
| 2 | Captura tras `liquidador_contacto` | **Taller** (nombre/tel/correo) por bien | N° Carpeta Liquidador (opcional) |
| 3 | Descripción `cliente_entrega` | "Lleva el vehículo al taller" | "Entrega antecedentes" |
| 4 | `liquidador_accion` | "Emite orden de reparación" + fecha + flag importación | "Genera finiquito" + fecha |
| 5 | Tarea siguiente | `cliente_ingreso_taller` | `cliente_firma_finiquito` |
| 6 | Tarea adicional | `taller_fecha_entrega` (no existe en no-veh) | — |
| 6/7 | Contacto compañía | No se pide | Se pide en `liquidador_envio_compania` |
| Total | Pasos | 8 | 7 |

---

## Mapeo `codigo_tarea` ↔ archivo

- Generación de tareas: `bamboo/backend/siniestros/helper_cadena_pendientes.php`
  - `bootstrap_cadena_siniestro()`: arranca la cadena al crear el siniestro.
  - `promover_al_liquidador()`: cierra tarea 1 y crea tarea 2.
  - `promover_cadena_al_entregar()`: switch que crea la siguiente tarea por `codigo_tarea`.
  - `cerrar_siniestro_por_pago()`: cierra el siniestro al completar `compania_pago`.
- Captura de datos al "Marcar Entregado": `bamboo/backend/siniestros/actualiza_pendiente.php` (switch por `codigo_tarea`).
- Render del modal: `bamboo/creacion_siniestro.php` funciones `renderResolver_*`.

## Pendientes futuros respecto a este diagrama

- **Etapa "Cliente coordina fecha de revisión"** previa a `cliente_ingreso_taller` (no modelada todavía).
- **Distinción revisión vs ingreso a reparación efectiva** (grúa) (no modelada).
- **Recordatorios automáticos al taller** cuando `importacion_repuestos = TRUE` (requiere cron + Brevo).
