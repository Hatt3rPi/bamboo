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
│  "Compañía debe entregar N° de siniestro, liquidador        │
│   asignado y taller."                                       │
│                                                             │
│  📥 Al cerrar pide:                                         │
│    • N° Siniestro Compañía                                  │
│    • Liquidador: dropdown (conocidos) o nombre + tel/correo │
│    • Taller por bien vehicular: nombre / teléfono / correo  │
│                                                             │
│  💾 Persiste en: siniestros.numero_siniestro,               │
│     liquidador_nombre/telefono/correo + tabla liquidadores  │
│     + siniestros_bienes_afectados.taller_* (por bien)       │
│  ⚡ Efecto: estado siniestro → Abierto                      │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  [2] LIQUIDADOR · 24h            codigo: liquidador_contacto│
│  ─────────────────────────────────────────────────────────  │
│  "Liquidador toma contacto con el cliente."                 │
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
│  "Cliente lleva el vehículo al taller designado."           │
│  (evaluación inicial; el cliente vuelve a casa)             │
│                                                             │
│  📥 Al cerrar pide:                                         │
│    • Notas (opcional)                                       │
│                                                             │
│  ✉️  Correo automático al liquidador:                       │
│     "El cliente llevó el vehículo al taller, proceda con    │
│     la orden de reparación."                                │
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
│  [5] TALLER · 4 días                                        │
│      codigo: taller_disponibilidad_repuestos  (NUEVA)       │
│  ─────────────────────────────────────────────────────────  │
│  "Taller debe confirmar disponibilidad de repuestos."       │
│  Bucle de seguimiento (puede tardar meses si hay importación)│
│                                                             │
│  📥 Al cerrar pide: nada (solo confirmación)                │
│                                                             │
│  ✉️  Correo automático al cliente:                          │
│     "El taller confirmó que hay repuestos disponibles.      │
│     Coordine el reingreso del vehículo."                    │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  [6] CLIENTE · 2 días        codigo: cliente_ingreso_taller │
│  ─────────────────────────────────────────────────────────  │
│  "Cliente debe avisar el día de ingreso del vehículo al     │
│   taller." (reingreso para reparación efectiva)             │
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
│  [7] TALLER · 5 días hábiles  codigo: taller_fecha_entrega  │
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
│  [8] LIQUIDADOR · 24h    codigo: liquidador_envio_compania  │
│  ─────────────────────────────────────────────────────────  │
│  "Liquidador debe confirmar el envío del finiquito a la     │
│   compañía."                                                │
│                                                             │
│  📥 Al cerrar pide:                                         │
│    • Fecha de envío                                         │
│                                                             │
│  💾 Persiste en: siniestros.liquidador_fecha_envio_compania │
│  ⚡ Efecto: estado siniestro → 🔒 Cerrado (automático)      │
│  (en vehículo NO existe compania_pago: el cliente no recibe │
│   pago, recibe el vehículo reparado)                        │
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
| 1 | Captura tras `compania_entrega_numero` | N° + liquidador + **taller por bien** (todos opcionales salvo N°) | N° + liquidador (opcionales salvo N°) |
| 3 | Descripción `cliente_entrega` | "Lleva el vehículo al taller" + ✉️ correo al liquidador | "Entrega antecedentes" |
| 4 | `liquidador_accion` | "Emite orden de reparación" + fecha + flag importación | "Genera finiquito" + fecha |
| 5 | Tarea adicional veh | **`taller_disponibilidad_repuestos`** + ✉️ correo al cliente | — |
| 6 | Tarea siguiente | `cliente_ingreso_taller` (reingreso) | `cliente_firma_finiquito` |
| 7 | Tarea adicional | `taller_fecha_entrega` | — |
| 7/8 | Contacto compañía | No se pide | Se pide en `liquidador_envio_compania` |
| Final | Cierre | `liquidador_envio_compania` → cierra | `compania_pago` → cierra |
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

## Correos automáticos al cerrar tarea

Disparados desde `disparar_correo_evento_tarea()` en `actualiza_pendiente.php`. Si Brevo
no está configurado, el envío se registra como "omitido" en `siniestros_notificaciones_enviadas`
sin interrumpir el flujo.

| # | Evento (tarea + ramo)                        | Destinatario | Plantilla Brevo                            |
|---|----------------------------------------------|--------------|--------------------------------------------|
| 1 | `cliente_entrega` + vehículo                 | Liquidador   | `siniestro_cliente_llevo_vehiculo`         |
| 2 | `cliente_entrega` + no-vehículo              | Liquidador   | `siniestro_liquidador_no_vehiculo`         |
| 3 | `taller_disponibilidad_repuestos` (vehículo) | Cliente      | `siniestro_taller_disponibilidad_repuestos`|
| 4 | `cliente_firma_finiquito` (no-vehículo)      | Liquidador   | `siniestro_liquidador_cliente_firmo`       |

Las plantillas son editables por Adriana desde `admin_email_templates.php`.

## Pendientes futuros respecto a este diagrama

- **Etapa "Cliente coordina fecha de revisión"** previa a `cliente_ingreso_taller` (no modelada todavía).
- **Distinción revisión vs ingreso a reparación efectiva** (grúa) (no modelada).
- **Recordatorios automáticos al taller** cuando `importacion_repuestos = TRUE` (requiere cron + Brevo).
