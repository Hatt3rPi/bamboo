# Reunión Adriana — Siniestros (2026-04-16)

Transcripción original: `C:\Users\fabar\Downloads\Bamboo Transcripción (1).txt`

## Contexto

Revisión en vivo del módulo Siniestros desplegado. Adriana probó el flujo completo creando un siniestro de ejemplo (vehículo) desde listado de pólizas. Tono general: satisfecha con lo implementado, con ajustes puntuales. El foco fue el **seguimiento documental**, que tiene una lógica distinta a la asumida en el MVP.

## Decisiones confirmadas (ya implementadas)

- Tipos de siniestro: **Robo / Choque-Colisión / Incendio / Daños materiales / Responsabilidad civil**. Confirmó "Daño → Daños materiales" y "Otro → Responsabilidad civil".
- Bien propio auto-carga datos del asegurado + ítem.
- Taller aplica **solo a bienes propios** (a terceros se les indemniza, no van a taller).
- Múltiples ítems pueden tener talleres distintos (caso: dos autos, un choque, talleres diferentes).
- Un siniestro puede tener N bienes propios y N terceros.
- Liquidador es único por siniestro.

## Cambios solicitados (pendientes de implementar)

### 1. Ícono/color del botón "Registrar siniestro"
Actualmente no se distingue bien visualmente en la barra de acciones del listado de pólizas. Adriana quiere algo **amarillo** (diferenciable del rojo de "Anular"). Propuestas equivalentes: ambulancia amarilla, estrella amarilla con muchas puntas. El color importa más que el mono.

### 2. Campo "Observaciones" a nivel siniestro
Texto libre adicional a la descripción. Para anotaciones que no caben en los parámetros estructurados. Campo independiente de `descripcion`.

### 3. "Persona" como categoría de daño a tercero
Hoy el dropdown de tercero tiene: vehículo / inmueble / otro. Agregar **"persona"** explícitamente (ej. lesionado). Queda: vehículo / inmueble / persona / otro.

### 4. Primera alarma — 24 horas post-ingreso
Cuando se ingresa un siniestro, la compañía tiene **24h para entregar número de siniestro + nombre del liquidador**. Esta es la primera alarma del flujo (hoy no existe). Se genera al crear el siniestro y se cierra cuando ambos campos se completan.

### 5. Rediseño completo del seguimiento documental

**Principio:** Adriana NO quiere un catálogo exhaustivo de papeles. Solo registra **el papel que va faltando** con quién lo debe. El catálogo actual (`documentos_siniestro`) no le sirve como está planteado.

**Modelo nuevo — "¿Quién la lleva?"**

Cada documento pendiente pertenece a uno de 3 responsables:
- **Cliente** — papeles que el asegurado debe entregar
- **Liquidador** — informe, finiquito
- **Compañía** — fecha de pago, emisión de finiquito

**Flujo típico de un siniestro:**

1. Cliente debe papeles → entrega → se registra fecha de entrega
2. Cuando cliente ya no debe nada → **email automático al liquidador** avisando que puede avanzar
3. Liquidador debe enviar finiquito al cliente
4. Finiquito vuelve al cliente → cliente debe firmarlo
5. Cliente devuelve finiquito firmado al liquidador
6. Liquidador debe enviar finiquito firmado a la **compañía**
7. Compañía debe poner **fecha de pago** (alarma final — caso real reciente: quedó un mes detenido porque el ejecutivo estaba de vacaciones)

**UI implícita:** en la vista del bien afectado debe quedar claro **quién la lleva ahora** y qué debe. Más visual que checklist exhaustivo.

### 6. Email automático al liquidador
Gatillado cuando **desaparecen todos los pendientes del cliente** en un siniestro. Destinatario: correo del liquidador (ya guardado). Contenido: "Para el siniestro X, el cliente ya entregó todos los documentos pendientes, se requiere avanzar con el finiquito."

### 7. Plazos legales del liquidador (pendiente por parte de Adriana)
Adriana no se sabe los plazos de memoria. Los va a conseguir y después definimos. **No bloqueante** para el próximo incremento.

## Metodología acordada

Trabajar en **incrementos chiquititos** (punto de control). No acumular todo en un gran release. Felipe estará operativo durante sus 2 semanas de recuperación (cirugía nasal), aunque con menor intensidad al inicio.

## Preferencia agenda

Reuniones **en la mañana** (por las clases que dicta en la tarde).

## Bugs/detalles menores mencionados al pasar

- El modal del bien mostró bien la diferenciación propio/tercero.
- El listado de siniestros ya muestra los daños propios/terceros por separado (OK).
- Deeplink desde siniestro a seguimiento de bienes funcionando (OK).
