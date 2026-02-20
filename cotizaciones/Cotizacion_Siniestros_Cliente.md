# Cotización: Módulo de Gestión de Siniestros para Bamboo

**Preparado para:** Adriana Sandoval Páez
**Preparado por:** Felipe Abarca
**Fecha:** 20 de febrero de 2026
**Versión:** 2.0
**Validez:** 30 días corridos desde la fecha de emisión

---

## 1. Resumen ejecutivo

Hoy el seguimiento de siniestros se hace por correo y de memoria. Esto genera riesgos reales: plazos que se pierden, finiquitos que quedan retenidos, y clientes que reclaman porque nadie hizo seguimiento.

Esta cotización contempla dos cosas:

1. **Modernización de base de datos Bamboo:** Mover la información del sistema a una infraestructura más moderna, segura y preparada para crecer.

2. **Nuevo módulo de siniestros:** Un espacio dentro de Bamboo para registrar cada siniestro, ver en qué etapa está, saber qué falta y recibir alertas cuando algo se atrasa.

El objetivo: que ningún siniestro se quede sin seguimiento y que puedas saber el estado de cualquier caso en segundos, sin tener que revisar correos.

---

## 2. Qué vas a poder hacer

### 2.1 Registrar siniestros desde la póliza

Cuando un cliente reporta un siniestro, se accede a la póliza correspondiente y se presiona **"Registrar Siniestro"**. El sistema precarga los datos de la póliza y del cliente. Se completan los siguientes campos:

- Fecha y hora del siniestro
- Descripción de lo ocurrido (se puede copiar y pegar desde el correo del cliente)
- Detalle de los daños
- Quién es el contacto que va a atender al liquidador (el administrador, el mayordomo, etc.)
- Qué coberturas están afectadas

Si el siniestro queda bajo el deducible y el cliente desiste, se registra como **"No presentado"** con el motivo. Queda archivado como referencia, sin seguimiento ni alertas.

### 2.2 Generar texto listo para copiar y pegar

Una vez registrado, el sistema genera un texto estructurado con toda la información para copiar y pegar al momento de declarar el siniestro a la compañía (por correo o web), sin necesidad de reescribir lo que el cliente informó. También genera el texto con los datos de contacto para enviar al liquidador.

### 2.3 Buscar cualquier siniestro en segundos

El módulo permite buscar por:
- **Número de siniestro** (búsqueda principal)
- Número de póliza
- Nombre o RUT del cliente
- Nombre del liquidador
- Estado del siniestro
- Rango de fechas

### 2.4 Hacer seguimiento de coberturas y afectados

Un siniestro puede tener varias coberturas (ej: rotura de cañería + responsabilidad civil) y cada cobertura puede tener varios afectados.

**Ejemplo real — el caso del condominio:**

```
Siniestro #453 — Comunidad Edificio Fluidico
│
├── Cobertura: Rotura de cañería
│   └── Comunidad (espacios comunes)
│       ├── Presupuesto: entregado 12/feb
│       ├── Fotos: entregadas 12/feb
│       ├── Finiquito: firmado 20/feb
│       └── Estado: Listo ✓
│
└── Cobertura: Responsabilidad Civil
    ├── Departamento 304
    │   ├── Presupuesto: entregado 15/feb
    │   ├── Fotos: entregadas 15/feb
    │   ├── Finiquito: pendiente firma (sucesión)
    │   └── Estado: Esperando firma ⏳
    │
    └── Departamento 404
        ├── Presupuesto: pendiente
        ├── Fotos: pendiente
        └── Estado: Falta documentación ⚠️
```

De un vistazo se identifica qué falta y quién debe actuar. El siniestro no se cierra hasta que **todos** los afectados estén resueltos.

Para cada afectado el sistema registra:
- Si entregó presupuesto y fotos (con fecha)
- Si recibió el preinforme del liquidador y si está conforme
- Si firmó el finiquito (ante notario o firma simple)
- Si el liquidador envió el finiquito a la compañía
- Si se recibió la indemnización (fecha y monto)

### 2.5 Ver en qué etapa está cada siniestro

Cada siniestro pasa por las siguientes etapas, que corresponden al flujo habitual de gestión:

```
 Cliente reporta          Tú declaras a          La compañía responde
 el siniestro             la compañía            con nro y liquidador
      │                        │                        │
      ▼                        ▼                        ▼
┌──────────┐  ──────▶  ┌──────────────┐  ──────▶  ┌───────────────────┐
│ Registro │           │  Declarado   │           │    Liquidador     │
│ inicial  │           │              │           │    asignado       │
└──────────┘           └──────────────┘           └───────────────────┘
                                                          │
                              ┌────────────────────────────┘
                              ▼
                    ┌──────────────────┐     Liquidador pide documentos,
                    │   En gestión     │     van y vienen mails
                    │                  │     (tú en copia)
                    └──────────────────┘
                              │
                              ▼
                    ┌──────────────────┐     Liquidador propone monto,
                    │   Pre-informe    │     cliente acepta o negocia
                    └──────────────────┘
                              │
                              ▼
                    ┌──────────────────┐     Cada afectado debe firmar
                    │    Finiquito     │     su finiquito
                    │    pendiente     │     (ante notario si corresponde)
                    └──────────────────┘
                              │
                              ▼
                    ┌──────────────────┐     ⚠️ PUNTO CRÍTICO
                    │    Finiquito     │     Verificar que el liquidador
                    │   entregado a    │     efectivamente lo envió
                    │    compañía      │     a la compañía
                    └──────────────────┘
                              │
                              ▼
                    ┌──────────────────┐     10 días hábiles para pagar
                    │  Indemnización   │
                    │    pendiente     │
                    └──────────────────┘
                              │
                              ▼
                    ┌──────────────────┐
                    │     Cerrado      │     Pago confirmado ✓
                    └──────────────────┘
```

La corredora actualiza la etapa a medida que el proceso avanza. El sistema muestra de un vistazo el estado de cada siniestro.

### 2.6 Recibir alertas cuando algo se atrasa

Esta es la **funcionalidad central del módulo**. El sistema calcula los plazos en **días hábiles** (excluyendo fines de semana y feriados) y genera avisos cuando un plazo se atrasa.

**Alertas principales:**

| Situación | Plazo | Qué hacer |
|---|---|---|
| El liquidador fue asignado pero no ha contactado al cliente | 1 día hábil | Contactar al liquidador |
| El finiquito fue entregado a la compañía pero no se ha realizado el pago | 10 días hábiles | Seguimiento a la compañía y aviso al cliente |

**Alertas complementarias:**

| Situación | Qué hacer |
|---|---|
| Siniestro declarado y la compañía no responde | Seguimiento a la compañía |
| Documentación pendiente sin movimiento | Seguimiento al cliente o liquidador |
| Preinforme emitido y el cliente no responde | Seguimiento al cliente |
| Finiquito enviado al afectado sin firma | Seguimiento al afectado |
| Finiquito firmado pero el liquidador no lo envió a la compañía | Consultar al liquidador |

Todos los plazos son ajustables desde una pantalla de configuración.

En el listado de siniestros se muestra un **indicador visual (semáforo)** que señala:
- **Verde:** todo en plazo
- **Amarillo:** próximo a vencer
- **Rojo:** plazo vencido, requiere acción

### 2.7 Diferencia entre siniestros de vehículos y otros ramos

El sistema se adapta según el tipo de siniestro:

- **Otros ramos (incendio, RC, etc.):** flujo completo con coberturas, afectados y finiquitos múltiples.
- **Vehículos:** flujo más simple con taller, evaluación y OK del liquidador.

> El flujo detallado de vehículos se definirá en la segunda sesión de levantamiento.

---

## 3. Qué NO incluye esta cotización

Lo siguiente **no está contemplado** en esta cotización:

- El sistema no envía correos automáticos. La corredora continúa enviándolos, pero con el texto listo para copiar y pegar.
- No se integra con las páginas web de las compañías aseguradoras.
- No almacena documentos adjuntos (fotos, PDFs, presupuestos). Solo registra si fueron entregados o no.
- No genera reportes estadísticos (ej: cantidad de siniestros por compañía al año).
- No incluye campos de marca y modelo de vehículo (mejora futura).
- No contempla migración de siniestros anteriores. Se comienza con los casos nuevos.

---

## 4. Cómo se va a desarrollar

El trabajo se divide en **4 etapas**. Cada etapa entrega funcionalidad operativa. Al final de cada una se realiza una sesión de revisión antes de avanzar a la siguiente.

---

### Etapa 0 — Modernización de la plataforma

**Qué se hace:** Se traslada toda la información de Bamboo a una plataforma más moderna y segura. El sistema se ve y se usa exactamente igual, pero la infraestructura queda mejor preparada para crecer y soportar el nuevo módulo.

**Qué se entrega:** Bamboo operativo sobre la nueva plataforma. Se verifica que todas las funcionalidades existentes sigan funcionando correctamente (clientes, pólizas, tareas).

**Horas estimadas: 30 - 42 horas**

---

### Etapa 1 — Registro y búsqueda de siniestros

**Qué se hace:** Se crea el módulo de siniestros con la capacidad de registrar casos, buscarlos y generar texto para las declaraciones.

**Resultado al completar esta etapa:**
- Registrar un siniestro desde la póliza con un botón
- Buscar cualquier siniestro por número, póliza, cliente o liquidador
- Copiar y pegar el texto de declaración para enviar a la compañía
- Registrar siniestros no presentados como archivo

**Horas estimadas: 26 - 34 horas**

---

### Etapa 2 — Seguimiento de coberturas y afectados

**Qué se hace:** Se agrega la capacidad de registrar las coberturas de cada siniestro y los afectados de cada cobertura, con su seguimiento individual de documentos y finiquitos.

**Resultado al completar esta etapa:**
- Ver cada siniestro desglosado por cobertura y por afectado
- Identificar exactamente qué documento falta y de quién
- Hacer seguimiento de finiquitos por afectado (firma, entrega, pago)
- Ver la etapa general del siniestro de un vistazo
- Diferenciar siniestros de vehículos vs otros ramos

**Horas estimadas: 32 - 43 horas**

---

### Etapa 3 — Alertas y seguimiento proactivo

**Qué se hace:** Se incorpora el sistema de alertas que avisa cuando un plazo se atrasa, calculando en días hábiles reales (excluyendo fines de semana y feriados).

**Resultado al completar esta etapa:**
- Alertas cuando un liquidador no contacta al cliente a tiempo
- Alertas cuando la compañía no paga en plazo
- 5 alertas complementarias de seguimiento
- Indicador visual (semáforo) en el listado de siniestros
- Pantalla de configuración para ajustar plazos
- Administración de feriados chilenos por año

**Horas estimadas: 24 - 32 horas**

---

## 5. Inversión

**Valor hora: $10.000 CLP**

| Etapa | Horas estimadas | Valor (CLP) |
|---|:-:|:-:|
| Etapa 0: Modernización de la plataforma | 30 - 42 hrs | $300.000 - $420.000 |
| Etapa 1: Registro y búsqueda de siniestros | 26 - 34 hrs | $260.000 - $340.000 |
| Etapa 2: Seguimiento de coberturas y afectados | 32 - 43 hrs | $320.000 - $430.000 |
| Etapa 3: Alertas y seguimiento proactivo | 24 - 32 hrs | $240.000 - $320.000 |
| **Subtotal** | **112 - 151 hrs** | **$1.120.000 - $1.510.000** |
| Imprevistos (20%) | 22 - 30 hrs | $220.000 - $300.000 |
| **Total** | **134 - 181 hrs** | **$1.340.000 - $1.810.000** |

> El 20% de imprevistos cubre las reuniones de revisión entre etapas, ajustes que surjan durante el desarrollo y corrección de errores.

---

## 6. Modalidad de trabajo

- Se cobra por **hora efectiva**, con un rango estimado por etapa.
- Antes de cada etapa se realiza una reunión de validación de alcance.
- Al terminar cada etapa se realiza una demostración y se recoge retroalimentación.
- Si durante una etapa surgen requerimientos adicionales no contemplados, se evalúan y cotizan por separado.
- La Etapa 0 (modernización) se completa primero, antes de empezar con el módulo de siniestros.

---

## 7. Preguntas que necesito confirmar contigo

| # | Pregunta |
|---|---|
| 1 | Cuando hay varios afectados (ej: departamentos), ¿cada uno tiene su propio contacto (nombre, teléfono, email) o se maneja un solo contacto para todo el siniestro? |
| 2 | ¿Siempre se tiene identificada la póliza al registrar el siniestro, o a veces llega solo un número de siniestro y se necesita buscar a qué póliza corresponde? |
| 3 | Se requiere una segunda sesión para definir el flujo completo de siniestros de vehículos. |

---

## 8. Plazo estimado

| Etapa | Plazo estimado |
|---|:-:|
| Etapa 0: Modernización de la plataforma | 2 - 3 semanas |
| Etapa 1: Registro y búsqueda | 1 - 2 semanas |
| Etapa 2: Coberturas y afectados | 2 - 3 semanas |
| Etapa 3: Alertas | 2 - 3 semanas |
| **Total** | **7 - 11 semanas** |

> Los plazos dependen de la disponibilidad del desarrollador y de la oportunidad en las sesiones de revisión entre etapas.

---

*Cotización preparada el 20 de febrero de 2026.*
*Basada en la reunión del 5 de febrero y las respuestas recibidas el 11 de febrero de 2026.*
