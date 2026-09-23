# Reunión Adriana — Correos del dominio + journey siniestros (2026-05-08)

Participantes: Felipe Abarca, Adriana Sandoval.
Contexto: Felipe agotado tras evento Vintage Forum (jue–vie 7–8 may), internet
intermitente por robo de cables BTR/Movistar. Reunión 53 min con cámara apagada.
Transcripción origen: `Bamboo Transcripción (2).txt` (08-may-2026).

## Síntesis ejecutiva

1. **Cambio de prioridad**: Adriana redirigió la prioridad de "rediseño visual" a
   **arreglar el envío de correos del dominio + paso a producción**. El rediseño
   sigue acordado pero no es lo urgente.
2. **Compromiso de plazos firmes**: la próxima semana cerramos última versión y
   migramos. Próxima reunión **lunes 11-may-2026 09:00**.
3. **Incremento E del journey de siniestros tiene bugs serios** detectados en demo
   en vivo. El flujo automático se rompe al ingresar el `numero_siniestro` y al
   confirmar tareas.
4. **Refinamiento del flujo vehículos**: agregar etapa previa de "coordinación de
   fecha de revisión", distinguir revisión vs ingreso a reparación, registrar
   sub-estado de "importación de repuestos".
5. **Feedback explícito de Adriana sobre la mecánica de las reuniones**: llegar con
   la transcripción anterior revisada y el flujo testeado en profundidad antes de
   sentarse a la reunión.

## Decisiones nuevas

### Prioridad reordenada

- **Antes**: rediseño visual ($200.000 ya aceptado en la reunión 22-abr) era el
  foco de las semanas siguientes.
- **Ahora**: Adriana pidió textualmente: *"a mí eso está bien, estoy de acuerdo,
  pero no es mi prioridad, mi prioridad es arreglar el tema de los mails en la
  página web para que la información me llegue, porque a lo mejor estos cien mails
  que te conté puede haber sido cien personas que en el tiempo, lo único que se me
  ocurre, han intentado contactarme y que de todo y nada sirve."*
- Felipe respondió que el paso a producción "es de un paraguazo" y va completo:
  *"yo apostaría que ya la próxima semana vamos a tener la última versión y ahí
  migramos."*

### Compromiso de plazos

- **Próxima reunión**: lunes 11-may-2026, 09:00.
- **Próxima semana**: cerrar última versión + migrar a producción.
- *"Si llegás con tarea hecha nos demoramos 10 minutos, y si no nos demoramos 30
  y los dos perdemos tiempo."* (Adriana)
- Adriana se ofrece a recomendar a Felipe a clientes potenciales (contactos de
  Escuela de Seguros, Banco Santander, Mutual de Carabineros), pero condicionado
  a que cumpla los plazos cuando ella derive.

### Correos del dominio (bambooseguros)

- Tema arrastrado de la reunión anterior con problemas en NIC.cl y Mixtepec
  (fue problema de Mixtepec, no de Adriana).
- **Clave NIC.cl confirmada en vivo**: `[REDACTADA]` — la clave se guarda fuera del
  repo (gestor de claves / memoria privada). El punto sí era parte de la clave.
- Felipe se dejó como **contacto técnico** del dominio en NIC.cl. Adriana aceptó
  vía link de invitación que le llegó a `fabarca212@gmail.com`. Esto permite a
  Felipe hacer cambios DNS sin pedir credenciales nuevamente.
- Felipe envió correo de verificación, Adriana lo reenvió a Felipe con código
  (10 min de validez).
- **Pendiente**: con el acceso técnico, Felipe debe terminar la configuración SPF
  / DKIM / MX o equivalente en NIC.cl + Mixtepec para que el envío saliente desde
  bambooseguros funcione (es decir, que Brevo o el SMTP del hosting puedan
  autenticar correos como `@bambooseguros.cl`).

## Bugs del incremento E detectados en la demo

Felipe intentó demostrar el flujo en vivo y se rompió la cadena automática.
Notas de los puntos rotos:

### B1 — "Marcar como resuelto" requiere 3 acciones manuales

Al cerrar la primera tarea (Compañía entrega N° siniestro + liquidador), hoy el
usuario debe:
1. Tipear el `numero_siniestro` y el `liquidador`.
2. Setear manualmente la `fecha_entrega`.
3. Cambiar el estado a "Entregado".

**Esperado**: el botón de marcar resuelto abre un modal con los campos faltantes
del bien (acá: número siniestro + liquidador asignado). Al guardar:
- Se persisten los datos en la tabla del siniestro/bien donde corresponda.
- `fecha_entrega = hoy` se autocompleta.
- Estado pasa a "Entregado" automáticamente.
- Se genera la siguiente tarea de la cadena (`liquidador_contacto`).

Adriana: *"Cambio de estado más ingreso de la información que debía mandar. Son
las tres cosas. Actualizar la fecha de entrega cuando yo lleno los datos que faltan."*

### B2 — Cadena automática no avanza

Tras ingresar el `numero_siniestro` y guardar, **no se generó la siguiente tarea**
de la cadena (`liquidador_contacto`). En la implementación del incremento C esto
debería pasar automáticamente. Verificar:
- ¿El trigger en `crea_siniestro.php` se activa solo desde el form de creación y
  no desde la actualización del bien?
- ¿La cadena depende de un campo que en la demo quedó en NULL?

### B3 — Tareas de "ingreso de información" sin lugar donde guardar

Felipe lo dijo expresamente: *"hay tareas de ingreso de información y esa
información no se está ingresando en ninguna parte."* Las tareas de la cadena
(taller fecha entrega, liquidador firmó cliente, etc.) no tienen un campo
asociado donde persistir el dato que están pidiendo. Diseño actual: la tarea solo
guarda metadata (estado, fecha, observaciones). El input concreto (fecha de
entrega del taller, etc.) se pierde.

**Acción**: cada tarea de la cadena que pide un dato concreto debe declarar
*qué campo del siniestro/bien* va a poblar. Posibles campos nuevos en
`siniestros` o `siniestros_bienes_afectados`:
- `taller_fecha_compromiso_entrega` (date)
- `liquidador_fecha_orden_reparacion` (date)
- `cliente_fecha_ingreso_taller` (date)
- `cliente_fecha_firma_finiquito` (date)
- `compania_fecha_pago` (date)

Cada tarea, al marcarse como resuelta, abre modal pidiendo ese campo, lo guarda
y avanza la cadena.

## Refinamiento del flujo Vehículos

Adriana corrigió la secuencia que está hoy implementada. Orden correcto:

1. **Cliente coordina fecha para revisión de su auto** (NUEVA — no estaba).
2. **Cliente lleva el auto al taller para revisión**.
3. **Liquidador da la orden de reparación** (CRÍTICO — *"el liquidador da la
   orden cuando se le ocurre"*; Adriana necesita esta alarma sí o sí porque es
   donde se demoran).
4. (opcional) **Importación de repuestos**: si aplica, el cliente vuelve a casa
   a esperar; el taller no avisa, hay que hinchar.
5. **Taller confirma fecha de entrega** (recién después de orden de reparación).
6. **Cliente firma finiquito** — NO es una liquidación; es solo conformidad
   para que no haya reclamos posteriores. Sin decisiones del cliente.
7. **Liquidador envía finiquito a la compañía** (en vehículos el liquidador es
   empleado de la compañía, no externo, pero se mantiene el rótulo "liquidador"
   en la UI).
8. **Compañía paga** → cierre automático.

### Distinciones clave que faltan modelar

- **Revisión vs ingreso a reparación**: en la mayoría de los casos el cliente
  lleva el auto a revisión, vuelve a casa, y reingresa cuando el taller le avisa.
  El "ingreso para reparación efectiva" solo aplica cuando va por grúa o el auto
  no se puede usar.
- **Importación de repuestos como sub-estado**: si aplica, hay que registrar
  flag + activar recordatorios al taller (porque el taller no avisa
  proactivamente). *"De repente hay pasos que yo hoy día no sea capaz de hacer
  seguimiento y le ponga cualquier cosa, pero no quiero modificar el sistema
  cuando crezca."* — Adriana quiere el campo en la BD aunque no lo use al 100%.
- **Casos de arrepentimiento**: el cliente NO puede "quitar el siniestro de
  encima" una vez activado, pero puede:
  - Comprarse el repuesto por su cuenta y pedir reembolso.
  - Pedir indemnización por demora extrema.
  - Impugnar la reparación si quedó mal.

  Estos no requieren modelar este sprint, pero sí dejar `observaciones` libre
  por bien para registrarlo manualmente.

## Pendientes futuros (no este sprint)

- **Devolverse en páginas sin perder datos**: hoy el usuario sale del form y
  pierde lo escrito. Adriana ya se acostumbró pero lo deja anotado.
- **Adjuntar documentos por cliente sin límite**: cada cliente acumula muchos
  archivos; el sistema final debería soportar eso a nivel de pólizas, endosos,
  siniestros.
- **Bug en endosos**: hoy un "endoso de cancelación" NO cancela la póliza; hay
  que ir a cancelar la póliza aparte. Adriana lo reportó como mejora a futuro,
  no urgente.

## Feedback explícito sobre la mecánica de las reuniones

Adriana fue directa al final: *"yo me esperé, enchufé, pero no fue lo
suficiente"* / *"la idea es que [la transcripción] la podés revisar todo antes
porque a veces nos va a pasar que no vamos a poder hacerlo de un día pa'l otro o
justo cuando tú lo trabajaste, pa que no nos pase esto."*

Felipe reconoció: *"venía desenchufado"* + *"hice los checklist, pero en mi
mente no me acuerdo del orden que lo fuimos comentando"*.

**Acuerdo**: antes de cada reunión, Felipe llega con:
- La transcripción de la reunión anterior revisada.
- El flujo o feature objeto de la reunión testeado en profundidad
  (no solo "implementado pero no probado").
- Una minuta corta de qué cambió desde la última reunión.

## Acciones para el lunes 11-may 09:00

Orden de prioridad (no se debe negociar este orden sin justificación explícita):

1. **Cerrar configuración SMTP/DNS de bambooseguros** — que Adriana reciba los
   correos del formulario de la web. Aprovechar el acceso técnico recién
   otorgado en NIC.cl.
2. **Arreglar B1 + B2 + B3 del journey de siniestros** — modal de "marcar como
   resuelto" con captura del dato + persistencia + autoavance de cadena.
3. **Agregar etapa "Cliente coordina fecha revisión"** y campo de "importación
   de repuestos" en bien vehículo.
4. **Testear el flujo completo de extremo a extremo en QA antes de la reunión**.
5. Llegar con minuta corta para abrir la reunión sin improvisar.
