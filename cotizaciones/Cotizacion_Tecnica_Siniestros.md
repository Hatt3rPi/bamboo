# Cotización: Migración a Supabase y Módulo de Gestión de Siniestros

**Proyecto:** Bamboo - Sistema de Gestión de Corretaje de Seguros
**Cliente:** Adriana Sandoval Páez
**Desarrollador:** Felipe Abarca
**Fecha:** 20 de febrero de 2026
**Versión:** 2.0
**Validez:** 30 días corridos desde la fecha de emisión
**Estado:** Pendiente segunda sesión para flujo de vehículos

---

## 1. Contexto

Bamboo es un sistema web de gestión de corretaje de seguros que actualmente administra clientes, pólizas y tareas. Está construido en PHP, MySQL, jQuery, Bootstrap y DataTables, alojado en hosting compartido.

Se requieren dos líneas de trabajo:

1. **Migración de base de datos a Supabase (PostgreSQL):** Modernizar la infraestructura de datos actual, migrando desde MySQL en hosting compartido a Supabase, mejorando escalabilidad, seguridad y capacidad de integración futura.

2. **Módulo de gestión de siniestros:** Incorporar un nuevo módulo para registrar, dar seguimiento y controlar los siniestros asociados a las pólizas de los clientes.

La necesidad del módulo surge de la complejidad operativa del seguimiento de siniestros, que hoy se realiza mediante correos electrónicos y archivos manuales, generando riesgo de pérdida de plazos y falta de visibilidad sobre el estado de cada caso. Caso real: un liquidador retuvo un finiquito por más de un mes sin enviarlo a la compañía, y la corredora no se enteró hasta que el cliente reclamó.

El rol de la corredora en el proceso de siniestros es de **observadora activa**: no ejecuta las acciones del siniestro, pero debe asegurarse de que todas las partes cumplan los plazos. El valor principal del módulo son las **alertas y el control de estado**.

---

## 2. Alcance funcional

### 2.1 Registro de siniestros (CRUD)

- Creación, edición y visualización de siniestros.
- Cada siniestro se vincula a una **póliza** y a un **ítem** específico dentro de esa póliza.
- Campos principales:

| Campo | Tipo | Obligatorio | Notas |
|---|---|---|---|
| Número de siniestro | Texto (clave de búsqueda) | Sí (post-denuncia) | Lo asigna la compañía. No existe en siniestros no presentados |
| Número de carpeta del liquidador | Texto | No | Número interno del liquidador externo |
| Póliza asociada | Relación | Sí | Vínculo a póliza existente en el sistema |
| Ítem asociado | Relación | Sí | Vínculo al ítem específico dentro de la póliza |
| Fecha del siniestro | Fecha | Sí | Cuándo ocurrió el evento |
| Hora del siniestro | Hora | Sí | Hora aproximada del evento |
| Fecha de declaración | Fecha | Sí | Cuándo se declaró a la compañía |
| Descripción del siniestro | Texto largo | Sí | Copy-paste del relato del cliente |
| Daños reportados | Texto largo | Sí | Detalle específico de los daños |
| Liquidador designado | Texto | Sí (post-respuesta) | Nombre de empresa liquidadora (ej: McLaren) |
| Contacto del cliente | Nombre + teléfono + email | Sí | Persona que atenderá al liquidador (no siempre es el titular) |
| Ramo | Selección | Sí | Vehículo u otro ramo |
| Estado general | Selección | Sí | Ver sección de etapas |
| Presentado a compañía | Booleano | Sí | Distingue siniestros presentados de los archivados |
| Motivo de no presentación | Texto | Condicional | Si no se presenta (ej: bajo deducible, cliente desiste) |
| Observaciones / Notas | Texto largo | No | Campo flexible para anotaciones de seguimiento |

- Botón **"Registrar Siniestro"** accesible desde la vista de cada póliza, que precarga los datos de la póliza y del cliente.
- Búsqueda de siniestros también por número de siniestro como punto de entrada alternativo (cuando el liquidador o la compañía contactan con un número y la corredora necesita ubicar la póliza).

### 2.2 Clasificación de siniestros por presentación

**Siniestros no presentados a la compañía:**
- No generan número de siniestro.
- Quedan como registro de archivo (ej: daños bajo deducible y cliente desiste antes de declarar).
- No requieren seguimiento ni alertas.
- Deben ser buscables pero no aparecen en las vistas de seguimiento activo.

**Siniestros presentados a la compañía:**
- Generan número de siniestro.
- Siguen el flujo completo de seguimiento con alertas, independiente de si al final se pagan, se rechazan, o quedan bajo deducible tras el análisis del liquidador.
- Un siniestro presentado que el liquidador determina bajo deducible sigue siendo un siniestro con tratamiento normal (tiene número, tuvo gestión, debe cerrarse formalmente).

### 2.3 Coberturas por siniestro

- Un siniestro puede involucrar **múltiples coberturas** (ej: rotura de cañería + responsabilidad civil).
- Cada cobertura se registra como un sub-registro del siniestro.
- Cada cobertura tiene su propio estado de avance y observaciones.

### 2.4 Afectados por cobertura

- Cada cobertura (especialmente responsabilidad civil) puede tener **múltiples afectados** (ej: departamentos dañados).
- Cada afectado se registra individualmente con:

| Campo | Tipo | Notas |
|---|---|---|
| Identificación del afectado | Texto | Ej: "Departamento 304", "Local 8" |
| Contacto del afectado | Nombre + teléfono + email | **Pendiente confirmar si es individual o uno por siniestro** |
| Presupuesto entregado | Checkbox + fecha | |
| Fotos entregadas | Checkbox + fecha | |
| Estado de documentación | Selección | Pendiente / Entregada / Incompleta / Completa |
| Preinforme recibido | Checkbox + fecha | Del liquidador |
| Conformidad con preinforme | Selección | Conforme / Impugnado |
| Finiquito enviado al afectado | Fecha | |
| Tipo de firma requerida | Selección | Ante notario / Firma simple (default: ante notario) |
| Finiquito firmado y devuelto | Fecha | |
| Finiquito entregado a compañía por liquidador | Fecha | **Punto crítico de control** |
| Indemnización pagada | Fecha + monto | Cierre del ciclo para este afectado |
| Observaciones | Texto largo | |

- El seguimiento se realiza **por cada afectado**, ya que cada uno debe enviar su propia documentación y firmar su propio finiquito.
- El siniestro no avanza a cierre hasta que **todas** las coberturas y **todos** los afectados estén resueltos.

**Estructura de datos:**

```
Siniestro (1)
  └── Cobertura (N)
        └── Afectado (N)
             ├── Presupuesto (checkbox + fecha)
             ├── Fotos (checkbox + fecha)
             ├── Preinforme (checkbox + fecha + conformidad)
             ├── Finiquito (tipo firma + fecha firma + fecha entrega)
             ├── Indemnización (fecha + monto)
             └── Observaciones
```

### 2.5 Gestión de etapas (ramos no-vehículo)

Cada siniestro avanza a través de las siguientes etapas:

| Etapa | Trigger | Descripción |
|-------|---------|-------------|
| No presentado | Registro interno | Siniestro archivado, no declarado a la compañía. Sin seguimiento. |
| Declarado | Corredora informa a compañía | Siniestro informado, pendiente de respuesta con nro y liquidador. |
| Liquidador asignado | Compañía responde | Se ingresa nro de siniestro, liquidador y carpeta. |
| En gestión | Liquidador contacta cliente | Intercambio de documentos entre cliente y liquidador. |
| Pre-informe | Liquidador emite ajuste | Liquidador envía preinforme al cliente para conformidad. |
| Finiquito pendiente | Acuerdo alcanzado | Finiquito emitido, pendiente de firma(s) de afectado(s). |
| Finiquito entregado | Liquidador envía a compañía | Finiquito firmado entregado por liquidador a la compañía. **Punto crítico.** |
| Indemnización pendiente | Compañía procesando | Compañía tiene plazo de días hábiles para pagar. |
| Cerrado | Pago confirmado | Siniestro finalizado. |
| Impugnado | Caso excepcional | Cliente o compañía impugnó (se registra como observación). |

- La etapa general del siniestro se determina por el estado del afectado más atrasado.
- Transiciones de etapa manuales (la usuaria actualiza el estado).

### 2.6 Diferenciación por ramo

- **Otros ramos (no-vehículo):** flujo completo descrito arriba con coberturas, afectados y finiquitos múltiples.
- **Vehículos:** flujo simplificado. Campos adicionales: taller asignado, fecha de evaluación, OK del liquidador para reparación.
- La interfaz se adapta según el ramo seleccionado.

> **Nota:** El flujo detallado de vehículos está pendiente de definición en una segunda sesión. Los puntos identificados incluyen: designación de taller, evaluación, OK del liquidador (punto crítico frecuente) y seguimiento de reparación.

### 2.7 Sistema de alarmas

Este es el **corazón del módulo**. Las alertas se calculan en **días hábiles chilenos** (excluyendo fines de semana y feriados). Los plazos son estándar pero **configurables globalmente**.

**Alertas principales (confirmadas por la cliente):**

| Evento | Plazo por defecto | Acción |
|---|---|---|
| Liquidador asignado, sin contacto al cliente | 1 día hábil (24 hrs) | Contactar liquidador |
| Finiquito en compañía, sin pago | 10 días hábiles | Seguimiento a compañía + aviso al cliente |

**Alertas complementarias (propuestas):**

| Evento | Plazo por defecto | Acción |
|---|---|---|
| Siniestro declarado, sin respuesta de compañía | 2 días hábiles | Seguimiento a compañía |
| Documentación pendiente sin movimiento | Configurable | Seguimiento a cliente/liquidador |
| Preinforme emitido, sin respuesta del cliente | Configurable | Seguimiento a cliente |
| Finiquito enviado, sin firma del afectado | Configurable (más holgado si ante notario) | Seguimiento al afectado |
| Finiquito firmado, sin confirmación de envío a compañía | 1 día hábil | Consultar al liquidador |

**Infraestructura de alarmas:**
- Tabla de feriados chilenos administrable (CRUD de feriados por año).
- Pantalla de configuración para ajustar plazos estándar.
- Indicadores visuales en el listado de siniestros (alerta vencida, próxima a vencer, en plazo).

### 2.8 Búsqueda flexible

- Grilla de búsqueda con filtros múltiples:
  - Número de siniestro (campo principal)
  - Número de póliza
  - Nombre del cliente / RUT
  - Liquidador
  - Número de carpeta
  - Ítem
  - Ramo
  - Estado/etapa
  - Presentado / No presentado
  - Rango de fechas
- Resultados en formato DataTable (consistente con el resto de Bamboo).

### 2.9 Generación de texto para copy-paste

El sistema genera texto estructurado que la corredora pueda copiar y pegar para:
- **Declaración inicial del siniestro** a la compañía (datos de póliza, cliente, fecha, hora, descripción, daños, coberturas afectadas).
- **Información de contacto** del cliente para enviar al liquidador.
- **Seguimiento de estado** (texto tipo para correos de recordatorio).

Esto evita que la corredora tenga que re-escribir la información que ya ingresó al sistema.

---

## 3. Modelo de datos

### 3.1 Relaciones entre entidades del negocio

```
CLIENTE (1) ──── tiene ────── (N) PÓLIZA
                                    │
                         ┌──────────┼──────────┐
                         │          │          │
                    Propuesta    Endoso    Siniestro
                   (embebida)  (embebido)   (NUEVO)
```

> **Nota sobre el modelo actual:** Las propuestas y los endosos no son entidades
> independientes en la base de datos. Actualmente se almacenan como campos dentro
> de la tabla de pólizas:
> - **Propuesta:** `numero_propuesta`, `fecha_envio_propuesta` (una sola por póliza)
> - **Endoso:** campo de texto libre (un solo registro por póliza)
>
> Esto implica que hoy no es posible llevar historial de propuestas ni registrar
> múltiples endosos por póliza. Si se requiere en el futuro, sería un desarrollo
> adicional independiente de esta cotización.

### 3.2 Estructura del módulo de siniestros

El nuevo módulo agrega 5 tablas a la base de datos:

```
PÓLIZA (existente)
  │
  └── SINIESTRO (nueva)
        │   - Nro siniestro, fecha, hora, descripción, daños
        │   - Liquidador (nombre, empresa, carpeta)
        │   - Contacto del gestor del cliente
        │   - Etapa general, ramo, presentado (sí/no)
        │   - Motivo no presentación (condicional)
        │   - Campos vehículo (taller, evaluación, OK liquidador)
        │   - Observaciones
        │
        └── COBERTURA DEL SINIESTRO (nueva)
              │   - Nombre (ej: "Rotura de cañería", "Resp. Civil")
              │   - Etapa propia, observaciones
              │
              └── AFECTADO (nueva)
                    - Identificación (ej: "Depto 304")
                    - Contacto (nombre, teléfono, email)
                    - Presupuesto (checkbox + fecha)
                    - Fotos (checkbox + fecha)
                    - Preinforme (checkbox + fecha + conformidad)
                    - Finiquito (tipo firma + fechas)
                    - Indemnización (fecha + monto)
                    - Observaciones

FERIADOS (nueva)
  - Tabla de feriados chilenos por año, administrable

CONFIGURACIÓN DE PLAZOS (nueva)
  - Plazos estándar en días hábiles, modificables
  - Plazo contacto liquidador (default: 1 día hábil)
  - Plazo pago post-finiquito (default: 10 días hábiles)
  - Plazos de alertas complementarias
```

### 3.3 Diagrama visual

Se adjunta archivo `Diagrama_Modelo_Datos.html` con diagramas interactivos que incluyen:
- Modelo de datos actual (todas las tablas existentes y sus relaciones)
- Modelo propuesto con las nuevas tablas de siniestros
- Flujo del ciclo de vida de un siniestro
- Ejemplo de seguimiento por afectado (caso condominio)

---

## 4. Exclusiones (lo que NO incluye esta cotización)

- Envío automático de correos electrónicos a compañías, liquidadores o clientes.
- Integración con páginas web de compañías aseguradoras.
- Carga o almacenamiento de documentos adjuntos (fotos, PDFs, presupuestos).
- Módulo de reportería o estadísticas de siniestros.
- Separación de propuestas y endosos en tablas independientes (actualmente embebidos en pólizas).
- Campos de marca y modelo de vehículo (mencionados como mejora futura).
- Migración de datos históricos de siniestros.
- Migración del hosting de la aplicación PHP (solo se migra la base de datos).

---

## 5. Fases de desarrollo

El desarrollo se propone en **4 fases incrementales** (Fase 0 + 3 fases del módulo). Cada fase entrega funcionalidad verificable al completarse.

---

### Fase 0: Migración de base de datos a Supabase

**Objetivo:** Migrar la base de datos de MySQL (hosting compartido) a Supabase (PostgreSQL) manteniendo toda la funcionalidad existente operativa.

**Estado actual de la aplicación:**
- 32 archivos PHP con 303 llamadas a `mysqli_`.
- 10 archivos con 26 llamadas a la función almacenada `trazabilidad()`.
- 8 tablas activas + 1 vista + 1 función almacenada.
- Índice FULLTEXT en tabla de clientes para búsqueda por nombre/RUT.

| Componente | Detalle |
|-----------|---------|
| Configuración de Supabase | Creación del proyecto, configuración de acceso, políticas de seguridad básicas |
| Conversión de esquema | Migración de las 8 tablas de MySQL a PostgreSQL, adaptación de tipos de datos, constraints e índices |
| Búsqueda de texto completo | Conversión de FULLTEXT (MySQL) a tsvector/tsquery (PostgreSQL) para búsqueda de clientes |
| Función de trazabilidad | Reescritura de la función almacenada `trazabilidad()` en PL/pgSQL |
| Refactorización del código PHP | Reemplazo de las 303 llamadas `mysqli_*` por PDO con driver PostgreSQL en los 32 archivos afectados |
| Adaptación de SQL | Corrección de sintaxis específica de MySQL (FORMAT(), funciones de fecha, etc.) a PostgreSQL |
| Migración de datos | Exportación de datos existentes de MySQL e importación a Supabase |
| Verificación funcional | Pruebas de todos los módulos existentes (login, clientes, pólizas, tareas, templates, dashboard) |

**Horas estimadas: 30 - 42 horas**

**Entregable:** Bamboo operando con Supabase como base de datos. Todas las funcionalidades existentes verificadas y operativas.

**Riesgo principal:** Incompatibilidades de sintaxis SQL entre MySQL y PostgreSQL no detectadas hasta pruebas. Mitigación: se incluye buffer específico en la estimación.

---

### Fase 1: Funcionalidad base del módulo de siniestros

**Objetivo:** Poder registrar siniestros, buscarlos y generar texto para declaraciones.

| Componente | Detalle |
|-----------|---------|
| CRUD de siniestros | Alta, edición, visualización con todos los campos de la sección 2.1 |
| Contacto por siniestro | Campos de contacto del gestor del cliente |
| Botón desde póliza | "Registrar Siniestro" con precarga de datos |
| Entrada por nro de siniestro | Búsqueda directa por número cuando el liquidador/compañía contacta |
| Siniestros no presentados | Registro interno con motivo, sin número de siniestro |
| Búsqueda flexible | Grilla con todos los filtros de la sección 2.8 |
| Generación de texto copy-paste | Texto estructurado para declaración a compañía e info al liquidador |

**Horas estimadas: 26 - 34 horas**

**Entregable:** Módulo funcional para registrar, consultar y generar texto de siniestros.

---

### Fase 2: Coberturas, afectados y etapas

**Objetivo:** Dar seguimiento detallado a cada siniestro con el modelo de 3 niveles.

| Componente | Detalle |
|-----------|---------|
| Coberturas por siniestro | Registro de múltiples coberturas por siniestro con estado propio |
| Afectados por cobertura | Registro individual con todos los campos de la sección 2.4 (presupuesto, fotos, preinforme, conformidad, finiquito, indemnización) |
| Gestión de etapas | Estados por siniestro y por afectado, determinación automática del estado general por el afectado más atrasado |
| Diferenciación por ramo | Formularios adaptados para vehículos vs otros ramos |

**Horas estimadas: 32 - 43 horas**

**Entregable:** Seguimiento completo de siniestros con estructura de coberturas, afectados y control granular de documentación.

---

### Fase 3: Alarmas y seguimiento proactivo

**Objetivo:** Automatizar el control de plazos con alertas en días hábiles.

| Componente | Detalle |
|-----------|---------|
| Tabla de feriados | CRUD de feriados chilenos, administrable por año |
| Cálculo de días hábiles | Lógica de cómputo excluyendo fines de semana y feriados |
| Configuración de plazos | Pantalla para ajustar todos los plazos estándar (7 alertas) |
| 2 alertas principales | Contacto liquidador (1 día hábil) + pago post-finiquito (10 días hábiles) |
| 5 alertas complementarias | Sin respuesta compañía, documentación sin movimiento, preinforme sin respuesta, finiquito sin firma, finiquito sin envío a compañía |
| Indicadores visuales | Semáforo en listado de siniestros (vencida / próxima / en plazo) |
| Texto de seguimiento | Templates de texto copy-paste para correos de recordatorio |

**Horas estimadas: 24 - 32 horas**

**Entregable:** Sistema de alertas con 7 tipos de alarma en días hábiles, integrado al módulo de siniestros.

---

## 6. Resumen de inversión

**Valor hora: $10.000 CLP**

| Fase | Horas estimadas | Valor (CLP) |
|------|:-:|:-:|
| Fase 0: Migración a Supabase | 30 - 42 hrs | $300.000 - $420.000 |
| Fase 1: Funcionalidad base siniestros | 26 - 34 hrs | $260.000 - $340.000 |
| Fase 2: Coberturas, afectados y etapas | 32 - 43 hrs | $320.000 - $430.000 |
| Fase 3: Alarmas y seguimiento | 24 - 32 hrs | $240.000 - $320.000 |
| **Subtotal desarrollo** | **112 - 151 hrs** | **$1.120.000 - $1.510.000** |
| Buffer imprevistos (20%) | 22 - 30 hrs | $220.000 - $300.000 |
| **Total proyecto** | **134 - 181 hrs** | **$1.340.000 - $1.810.000** |

> El buffer cubre reuniones de revisión, ajustes de requerimientos, corrección de errores y compatibilidades no previstas en la migración.

---

## 7. Preguntas pendientes

Las siguientes preguntas están abiertas y pueden afectar el diseño final. Se recomienda resolverlas antes de iniciar la Fase 2.

| # | Pregunta | Impacto en diseño |
|---|---|---|
| 1 | Cuando hay varios afectados, ¿cada uno tiene su propio contacto (nombre, teléfono, email) o es uno solo para todo el siniestro? | Estructura del registro de afectados |
| 2 | ¿Siempre tienes identificada la póliza al registrar el siniestro, o a veces te llega solo el número de siniestro y necesitas buscar la póliza? | Punto de entrada del registro (se asume que ambos caminos son necesarios) |
| 3 | Flujo completo de siniestros de vehículos (segunda sesión pendiente) | Campos y etapas específicas para ramo vehículos |

---

## 8. Modalidad de trabajo

- **Cobro por hora efectiva** con rango estimado por fase.
- Cada fase se inicia con una breve reunión de validación de alcance.
- Al cierre de cada fase se realiza una demostración y se recibe retroalimentación.
- Las horas se reportan periódicamente.
- Si durante una fase surgen requerimientos adicionales no contemplados, se evalúan y cotizan por separado.
- La Fase 0 (migración) debe completarse y verificarse antes de iniciar el desarrollo del módulo de siniestros.

---

## 9. Supuestos y condiciones

1. La migración a Supabase contempla únicamente la base de datos. La aplicación PHP se mantiene en su hosting actual y se conecta remotamente a Supabase vía PostgreSQL.
2. La cliente proporcionará o confirmará las credenciales y accesos necesarios para crear el proyecto en Supabase.
3. Los feriados chilenos se precargan para el año en curso; la usuaria puede agregar feriados futuros.
4. La usuaria proporcionará un listado detallado de campos requeridos (Excel mencionado en reunión del 5 de febrero).
5. Se requiere al menos una sesión de validación entre fases para confirmar que el entregable cumple las expectativas antes de avanzar.
6. El alcance de cada fase puede ajustarse de común acuerdo antes de su inicio.
7. La segunda sesión para definir el flujo de vehículos debe realizarse antes de completar la Fase 2.

---

## 10. Plazo estimado de entrega

| Fase | Plazo estimado desde inicio |
|------|:--:|
| Fase 0: Migración a Supabase | 2 - 3 semanas |
| Fase 1: Funcionalidad base | 1 - 2 semanas |
| Fase 2: Coberturas, afectados y etapas | 2 - 3 semanas |
| Fase 3: Alarmas y seguimiento | 2 - 3 semanas |
| **Total proyecto** | **7 - 11 semanas** |

> Los plazos dependen de la disponibilidad del desarrollador y de la oportunidad en las sesiones de validación con la cliente.

---

## 11. Decisiones de diseño adoptadas

1. **Seguimiento por afectado, agrupado por cobertura:** Cada cobertura del siniestro tiene su listado de afectados. La cobertura de RC puede tener muchos afectados. El siniestro no cierra hasta que todas las coberturas y todos los afectados estén resueltos.
2. **Dos categorías de siniestro:** No presentados (archivo sin número, sin seguimiento) y presentados (flujo completo con alertas, independiente del resultado final).
3. **Alertas en días hábiles chilenos:** Plazos estándar, configurables globalmente. 2 alertas principales confirmadas + 5 complementarias propuestas.
4. **Finiquito con tipo de firma:** Por defecto ante notario, con opción de firma simple. El tipo de firma puede influir en la holgura de la alerta.
5. **Macroetapas sobre microetapas:** Pocas etapas generales con campo de observaciones flexible, en lugar de muchos campos específicos que quedarían vacíos según el caso.
6. **Separar flujo vehículos vs. otros ramos:** Procesos suficientemente distintos para flujos diferenciados (pendiente segunda sesión).
7. **Migración a Supabase vía PDO:** Se reemplazan las llamadas `mysqli_*` por PDO con driver PostgreSQL, manteniendo la estructura del código PHP existente.

---

*Documento generado el 20 de febrero de 2026.*
*Basado en la transcripción de reunión del 5 de febrero de 2026 y respuestas de la cliente del 11 de febrero de 2026.*
