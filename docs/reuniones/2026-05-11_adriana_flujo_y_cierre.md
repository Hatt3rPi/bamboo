# Reunión Adriana — Flujo y cierre de siniestros (2026-05-11)

Participantes: Felipe Abarca, Adriana Sandoval.
Duración: ~32 min. Felipe demostró en vivo el flujo refinado con captura por tarea.
Transcripción origen: `Flujo y cierre de siniestros Transcripción.txt` (11-may-2026).
Próxima reunión: **viernes 15-may-2026, 10:00 AM**.

## Síntesis ejecutiva

1. La captura de **N° siniestro, liquidador y taller en `compania_entrega_numero` está OK**,
   pero solo el N° siniestro debe ser obligatorio. El resto Adriana lo recibe "puntetú"
   con frecuencia.
2. **Falta una tarea intermedia** entre `liquidador_accion` (emite orden de reparación) y
   `cliente_ingreso_taller`: la confirmación de **disponibilidad de repuestos** por parte
   del taller. Sin esto, los tiempos del flujo vehicular no reflejan la realidad cuando
   hay que importar.
3. **`compania_pago` no aplica en vehículos**. En vehículo, no hay liquidación monetaria
   al cliente — se entrega el vehículo reparado. La tarea final del flujo vehicular
   debe ser `liquidador_envio_compania` (o algo equivalente), y de ahí cierre directo.
4. **Correos automáticos** en dos puntos clave: al cerrar `cliente_entrega` (avisar al
   liquidador que el cliente llevó el auto) y al cerrar `taller_disponibilidad_repuestos`
   (avisar al cliente que hay repuestos).
5. Adriana se va a informar cómo funciona el **finiquito en vehículos** (no sabe si lo
   firma en el taller, si lo manda el liquidador después, etc.). Lo aclara para la
   próxima reunión.

## Decisiones nuevas / refinamientos

### `compania_entrega_numero` — solo N° obligatorio

Texto exacto de Adriana: *"está bueno que me exijan el número de siniestro, pero lo otro
me debe permitir poner puntos, puntetú… a veces no tiene… eso yo o sea que me lo pida
pero no que me lo exija"*.

Reglas a aplicar:
- **N° siniestro**: obligatorio.
- **Nombre del liquidador**: opcional (compañía a veces solo manda "puntetú" o no lo informa).
- **Teléfono / correo del liquidador**: opcional.
- **Taller (nombre/tel/correo) por bien**: todo opcional.

Hoy el backend valida `nombre + (teléfono o correo)` del liquidador. Hay que relajar a:
solo el N° de siniestro es obligatorio.

### Nueva tarea: `taller_disponibilidad_repuestos`

Inserción entre `liquidador_accion` (Liquidador emite orden) y `cliente_ingreso_taller`
(Cliente lleva el auto al taller).

- **Responsable**: Taller (puede ser también Liquidador según quién esté a cargo de informar).
- **Alarma**: 4 días (bucle de seguimiento).
- **Descripción**: "Taller debe confirmar disponibilidad de repuestos."
- **Aparece solo si** el flag `importacion_repuestos` se marcó en `liquidador_accion`. Si
  no hubo importación, el flujo va directo de `liquidador_accion` a `cliente_ingreso_taller`.
- **Al cerrar pide**: nada (es una confirmación binaria). Auto-fecha = NOW().
- **Efecto**: dispara correo automático al cliente avisándole.

### `cliente_ingreso_taller` (sin cambios estructurales, ajuste de contexto)

Pasa a ser **el reingreso del vehículo al taller para reparación efectiva**, no la
evaluación inicial. En la evaluación inicial el cliente lleva el auto y vuelve a casa;
ahora vuelve cuando hay repuestos. La fecha capturada es la de reingreso.

### Correos automáticos al cerrar tareas

| Tarea cerrada | Correo a | Texto base |
|---|---|---|
| `cliente_entrega` (Cliente lleva vehículo a evaluación) | Liquidador | "El cliente ya llevó el vehículo al taller designado." |
| `taller_disponibilidad_repuestos` (nueva) | Cliente | "El taller confirmó que hay disponibilidad de repuestos. Coordine con el taller para el reingreso." |
| `cliente_ingreso_taller` | **No** se envía correo (Adriana: *"ya es tema del taller"*) |

### Cierre del flujo vehicular ≠ no-vehicular

- **No-vehículo**: termina con `compania_pago` → cierra siniestro (ya implementado).
- **Vehículo**: NO hay `compania_pago`. El flujo termina cuando se entrega el vehículo
  y el liquidador cierra el finiquito con la compañía. La tarea final actual
  `liquidador_envio_compania` debe disparar el cierre automático del siniestro.
  - Adriana: *"esa tarea va a desaparecer de acá"* (refiriéndose a `compania_pago` en
    vehículo).

### Finiquito en vehículos — pendiente de aclarar

Adriana dice: *"Es el liquidador con el finiquito. Esa es la parte que me está faltando
averiguar… no sé si el finiquito está en poder del taller… porque la compañía no se va a
arriesgar a que el taller haga…"*.

Lo va a averiguar. En lo posible para la reunión del 15-may.

### Flexibilidad de responsable de `taller_disponibilidad_repuestos`

Adriana: *"Puede ser cualquiera de los dos [Taller o Liquidador] que sea el que esté a
cargo de saber si llegaron o no llegaron los repuestos"*. Lo dejamos asignado a Taller
por defecto (podría cambiarse manualmente en el form).

## Wording menor

- "Cliente lleva el vehículo al taller designado." (texto actual de `cliente_entrega`
  para vehículo) Adriana lo confirma OK. Es claro que es la **evaluación inicial**, no
  la reparación efectiva.

## Rol del corredor — contexto que Adriana quiso dejar claro

Adriana explicó que el rol real del corredor (según CMF) es **asesorar y acompañar al
cliente durante toda la vigencia**, no solo venderle el seguro. Por eso necesita las
alarmas y el seguimiento — para poder estar encima del flujo aunque el cliente no le
avise proactivamente. Eso valida toda la inversión en el journey automático.

## Pendientes futuros (parking)

- **Correos institucionales** al cliente cada 2-3 meses recordando que Adriana es su
  ejecutiva. Adriana acepta automatizarlo. Fuera de sprint actual.
- **Acceso al computador de Adriana** para revisar archivos del hosting (Planet Hosting,
  vence sept 2027). Pendiente sesión.
- **Hosting Planet Hosting**: validar configuración con ellos. Felipe vio un correo
  sospechoso ("renueva tu dominio") y no confía en el remitente. Hay que confirmar
  directamente con Planet Hosting si es phishing.

## Plan de implementación priorizado (para discutir antes de codear)

### Bloque A — Cambios estructurales del flujo vehicular (alta prioridad)

A1. Relajar validación de `compania_entrega_numero`: solo N° siniestro obligatorio.
A2. Nueva tarea `taller_disponibilidad_repuestos` (Taller, 4 días) condicional al flag
    `importacion_repuestos`.
A3. Cambiar cierre del flujo vehicular: `compania_pago` no aplica; el cierre se hace en
    `liquidador_envio_compania` (o renombrar a una etiqueta más coherente).

### Bloque B — Correos automáticos (media prioridad)

B1. Al cerrar `cliente_entrega` (vehículo) → correo al liquidador.
B2. Al cerrar `taller_disponibilidad_repuestos` → correo al cliente.

Plantillas Brevo nuevas o reutilizar las existentes (`siniestro_*`).

### Bloque C — Aclaraciones pendientes (no codear hasta confirmar)

C1. Finiquito en vehículos: Adriana lo va a averiguar.
C2. Hosting Planet Hosting: sesión aparte.

## Acciones para la próxima reunión (viernes 15-may 10:00)

1. **Implementar Bloque A** antes de la reunión y demostrar el flujo completo.
2. **Implementar Bloque B** si Adriana confirma los textos de los correos.
3. **Adriana llega con info del finiquito de vehículos** (a su disposición).
4. Reportar estado del Bloque 1 anterior (correos del dominio bambooseguros) — sigue
   pendiente.
