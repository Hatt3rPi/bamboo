# Reunión Adriana — Cierre del flujo de siniestros (2026-04-22)

Participantes: Felipe Abarca, Adriana Sandoval.
Contexto: Felipe con cirugía nasal pendiente (mañana 23-abr); Adriana con dolor de cabeza.
Transcripción origen: `Bamboo - customware Transcripción.txt` (22-abr-2026).

## Objetivo
Cerrar el flujo automático del siniestro hasta el pago, incluyendo la figura del taller y
los recordatorios opcionales. Además, revisión de la UI de creación del siniestro y
propuesta comercial de rediseño visual del portal.

## Decisiones confirmadas

### Alertas (ya entregadas en incremento C)
- Alerta pasiva funcionando: muestra cliente, pendiente actual y redirige al siniestro.
- Adriana validó el diseño. Mostrar dos alertas separadas si son dos pendientes del
  mismo siniestro. Mostrar al menos el nombre del cliente/asegurado.

### Creación/edición del siniestro
- **Guardar y Guardar-y-salir**: agregar el segundo botón. `Guardar` refresca la pantalla
  (estado actual); `Guardar y salir` redirige a la pantalla origen.
- **Estado automático**: al tipear `numero_siniestro` + liquidador, el estado debe pasar
  a `Abierto` sin depender del guardado posterior. (Ya implementado en backend, verificar
  en UI.)
- **Nuevo bien propio incendio**: capturar dirección + descripción del daño.
- **Nuevo bien propio vehículos**: capturar el ítem de la póliza afectado (hoy es texto
  libre). En taller también debe quedar registrado el ítem.

### Cadena automática de pendientes — flujo completo

| # | Responsable | Evento que la crea | Alarma | Código tarea |
|---|-------------|--------------------|--------|--------------|
| 1 | Compañía   | Crear siniestro sin N° | 24 h | `compania_entrega_numero` ✅ |
| 2 | Liquidador | Ingreso de N° siniestro | 24 h | `liquidador_contacto` ✅ |
| 3 | Cliente    | Liquidador pide antecedentes | 4 días | `cliente_entrega` ✅ |
| 4 | Liquidador | Cliente entrega antecedentes | 24 h | `liquidador_accion` (nuevo) |
| 5a | Cliente   | Liquidador da orden de reparación (vehículos) | 2 días | `cliente_ingreso_taller` (nuevo) |
| 5b | Cliente   | Liquidador envía finiquito (no vehículos) | 4 días | `cliente_firma_finiquito` (nuevo) |
| 6a | Taller    | Cliente marca ingreso al taller | 5 días hábiles | `taller_fecha_entrega` (nuevo) |
| 6b | Liquidador | Cliente devuelve finiquito firmado | 24 h | `liquidador_envio_compania` (nuevo) |
| 7 | Compañía   | Liquidador confirma envío a la compañía | 72 h | `compania_pago` (nuevo) |
| 8 | —         | Compañía confirma pago | cierra siniestro automáticamente | — |

### Plantillas Brevo
- **Corrección textual**: `siniestro_liquidador_no_vehiculo` — eliminar la palabra
  "todos". Texto final: *"ya entregó los documentos pendientes a su cargo"*. Motivo:
  si falta alguno, la palabra "todos" hace que el liquidador reclame.
- **Nuevas plantillas**:
  - `siniestro_cliente_orden_reparacion` — aviso al cliente tras orden de reparación:
    "Ya tiene orden de reparación. Por favor avíseme cuando ingrese el auto al taller."
  - `siniestro_liquidador_cliente_firmo` — aviso al liquidador: "El cliente devolvió
    finiquito firmado, favor confirmar fecha de envío a la compañía."
  - `siniestro_compania_fecha_pago` — aviso a la compañía: "Agradeceré informe fecha de
    indemnización/transferencia al cliente por el siniestro N° X."
  - `siniestro_recordatorio_amigable` — recordatorio opcional, tono suave, parametrizado
    por responsable actual.

### Nuevos responsables y contactos
- **Taller** como cuarto responsable de pendientes (hoy: Cliente/Liquidador/Compañía).
- Persistir contacto del taller (nombre persona, teléfono, mail) junto al nombre del
  taller que ya existe. Adriana suele consultar al taller por la fecha de entrega.
- **Contacto de compañía** (solo incendio, porque en vehículos no aplica): capturar
  nombre y mail de la persona a cargo. Vehículos maneja empleados rotativos, no sirve.

### Acción "Enviar recordatorio"
- Dentro de las acciones de cada pendiente, un botón opcional `✉️ Enviar recordatorio`
  que dispara correo con tono suave al responsable actual. No modifica el estado, solo
  registra en la bitácora. Adriana lo quiere especialmente para el liquidador de vehículos
  (los de incendio son puntuales).

## Fuera de este incremento

### Rediseño visual (propuesta comercial)
- Adriana aceptó la propuesta "moderna con sidebar" (opción 2 de las 3 mostradas).
- Precio: **$200.000**, incluye:
  - Renovar look & feel del sistema interno.
  - Relanzar `bambooseguros.cl` (hoy llega mail con "pura basura", no trae datos reales).
  - Mostrar valor UF y dólar arriba permanente.
  - Prima neta en listados.
- Trabajo aparte, no parte de este sprint.

### Tarjetas impresas
- Tema personal, Adriana pidió cotización paralela. No afecta el código.

## Pendiente operacional
- Recuperar contraseña del correo `a.sandoval@bambooseguros.cl` (Adriana la olvidó
  durante la llamada; la cambió a `Rafaelito2020*`).

## Próxima reunión
Sin fecha fijada. Objetivo: validar cierre del flujo implementado.
