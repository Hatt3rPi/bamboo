# Reunión Adriana — Siniestros: alertas y automatización (2026-04-21)

Transcripción original: `C:\Users\fabar\OneDrive\Documentos\Github\referencias\OpenMontage\projects\lacuenteria-emilia\assets\video\narradora\Bamboo Transcripción.txt`

Siguiente punto de control acordado: **miércoles 22-abr-2026, 11:00 am** — misma mecánica de incrementos chiquititos.

## Contexto

Sesión corta (~30 min) validando en vivo el Incremento B recién deployado. Adriana entendió y aceptó el modelo "¿Quién la lleva?" pero pidió automatizar las primeras tareas del flujo, que son idénticas en todos los siniestros y hoy tiene que crear a mano. Cambió tambien cómo quiere que aparezcan las alertas y pidió contenido estandarizado para el correo al liquidador.

## Cambios solicitados

### 1. Canales de alerta

Adriana quiere **ambos**:
- **Correo electrónico** cuando una alarma se activa.
- **Widget en el home de Bamboo**, del lado derecho, con "alertas recientes". Ojo: que sean solo las alarmas del flujo nuevo, no la lista vieja enorme que dejó de tener sentido.

### 2. Tareas automáticas iniciales (pre-armadas)

#### 2.a Tarea "Compañía entrega N° siniestro + liquidador + taller"

- Se crea **automáticamente** al insertar un siniestro sin `numero_siniestro`.
- Responsable: **Compañía**.
- Descripción por defecto (editable): "Entregar N° de siniestro, liquidador y taller".
- Alarma a las **24 horas**.
- **Auto-cierre**: cuando Adriana ingresa el `numero_siniestro` en el form, esta tarea pasa sola a **Entregado** con fecha de hoy.
- Importante: en ramos **no vehículo** (incendio) no se espera taller; en vehículos sí. La tarea es única pero el payload entregado varía.

#### 2.b Tarea "Liquidador toma contacto / pide antecedentes"

- Se crea **automáticamente** cuando la tarea 2.a pasa a Entregado (es decir, cuando hay `numero_siniestro` + `liquidador_nombre`).
- Responsable: **Liquidador**.
- Texto condicional por ramo:
  - **Incendio** → "Liquidador pide antecedentes"
  - **Vehículos** → "Liquidador toma contacto con cliente"
- Alarma a las **24 horas**.

#### 2.c Tarea "Cliente debe entregar antecedentes"

- Se crea **automáticamente** cuando 2.b pasa a Entregado.
- Responsable: **Cliente**.
- Alarma: **4 días** (distinto de 24h — el cliente tiene más holgura).
- A partir de aquí el flujo deja de ser automático; los pendientes siguientes son manuales (como ya funciona hoy).

### 3. Estado "Entregado" con fecha automática editable

- Cuando una tarea cambia a **Entregado**, la `fecha_entrega` se autorellena con **hoy**.
- Pero debe ser **editable** (Adriana puede demorarse en registrar la entrega y querer poner la fecha real).
- Hoy el campo ya existe y es editable, falta **pre-llenarlo automáticamente**.

### 4. Correo al liquidador — contenido estandarizado por ramo

Hoy el mailto se genera con un template genérico. Adriana quiere dos templates distintos según ramo.

**Asunto (ambos casos):**
```
Siniestro N° <numero_siniestro> — <nombre_asegurado>
```
(Si hay `numero_carpeta_liquidador`, incluirlo después del siniestro.)

**Cuerpo — incendio (generación de finiquito):**
```
Estimado liquidador,

Le informo que el cliente ya entregó todos los documentos pendientes a su cargo.

Agradeceré proceder con la generación del finiquito.

Saludos cordiales,
Adriana
```

**Cuerpo — vehículo (orden de reparación):**
```
Estimado liquidador,

Se le informa que el vehículo asistió a revisión en el taller designado.

Por favor proceder con la orden de reparación.

Saludos cordiales,
Adriana
```

**Nota:** Adriana se comprometió a enviarme un correo real suyo de cada tipo para ajustar el estilo si hiciera falta.

### 5. Nota / campo opcional de observaciones por tarea

En la sección de pendientes, Adriana valoró poder agregar una **nota** cuando el liquidador deje algo menor sin resolver — ya existe la columna `notas`, solo hace falta dejar más claro en la UI que es opcional.

## Fuera de alcance este sprint (diferidos con nombre)

Adriana explicó el flujo completo hasta el cierre del siniestro. Se dejan para las próximas reuniones:

- Generación/envío del finiquito desde Bamboo.
- Firma del finiquito por parte del cliente.
- Impugnación (cuando el cliente o la compañía rechaza el finiquito).
- Tarea "Compañía informa fecha de pago" (alarma 3 días).
- Tarea "Cliente confirma recepción del pago" (alarma ~10 días).
- Cierre automático del siniestro al confirmar pago.

## Decisiones abiertas (necesito confirmar)

- ¿El correo al liquidador se **sigue disparando vía `mailto:`** (cliente de Adriana) o ahora necesita envío directo desde Bamboo (PHPMailer + SMTP)? El widget de alertas en el home apunta a que ya querría notificaciones automáticas, y eso casa mejor con envío directo.
- ¿El widget de alertas en el home carga en **una nueva landing** post-login o se inyecta en el header?
- La alarma de **4 días** para el Cliente: ¿son 4 días corridos o hábiles?
