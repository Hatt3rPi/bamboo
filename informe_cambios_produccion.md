# Informe de Cambios: Repositorio vs. Produccion

**Fecha:** 11 de febrero de 2026
**Objetivo:** Identificar todos los cambios realizados manualmente en el servidor de produccion (bamboo-prod) respecto al repositorio original (bamboo).
**Metodo:** Comparacion archivo por archivo mediante diff entre ambas carpetas.

---

## Resumen Ejecutivo

Se detectaron cambios significativos en produccion que representan una evolucion importante del sistema. Los cambios principales son:

| Categoria | Cantidad |
|-----------|----------|
| Archivos nuevos (solo en produccion) | ~30 archivos |
| Archivos modificados | ~30 archivos |
| Archivos movidos a `archivos_por_eliminar/` | ~15 archivos |
| Archivos CSS/JS sin cambios | 5 archivos |

Las modificaciones se agrupan en 5 grandes areas:

1. **Reestructuracion de base de datos:** Migracion de tabla `polizas` a `polizas_2` + `items`
2. **Modulo nuevo: Propuestas de Polizas** (CRUD completo + exportacion Excel)
3. **Modulo nuevo: Endosos** (CRUD completo + exportacion Excel)
4. **Mejoras de navegacion:** Nuevo menu, sistema de historial con cookies, botones de descarga Excel
5. **Correcciones tecnicas:** `mysqli_close()`, `mysqli_select_db()`, simplificacion de DataTables

---

## 1. Modulos Completamente Nuevos

### 1.1 Sistema de Propuestas de Polizas

Modulo funcional completo para la gestion de propuestas de polizas antes de que se conviertan en polizas formales.

| Archivo | Tipo | Descripcion |
|---------|------|-------------|
| `creacion_propuesta_poliza.php` | Frontend | Formulario de creacion/edicion de propuestas. Maneja creacion, renovacion, modificacion y conversion a poliza. Incluye gestion de multiples items por propuesta. |
| `listado_propuesta_polizas.php` | Frontend | Listado con DataTables. Columnas: estado, tipo, fecha envio, vigencias, compania, ramo, moneda, proponente, grupo, referido. Boton de descarga Excel. |
| `documento_propuesta_poliza.php` | Frontend | Genera documento visual/PDF de la propuesta con jsPDF + html2canvas. Muestra datos completos del proponente, asegurado, primas por item y totales. |
| `backend/propuesta_polizas/busqueda_listado_propuesta_polizas.php` | Backend API | Devuelve JSON con todas las propuestas y sus items asociados. Consumido por DataTables. |
| `backend/propuesta_polizas/crea_propuesta_polizas.php` | Backend | Procesa creacion de propuestas (42KB). Maneja acciones: eliminar, cancelar, anular, crear y actualizar. Incluye trazabilidad. |
| `backend/propuesta_polizas/modifica_propuesta_polizas.php` | Backend | Procesa modificacion de propuestas existentes. Registra trazabilidad. |
| `backend/propuesta_polizas/genera_excel_propuestas.php` | Backend | Genera archivo Excel (.xlsx) con PhpSpreadsheet. |

### 1.2 Sistema de Endosos

Modulo funcional completo para la gestion de endosos (modificaciones a polizas existentes).

| Archivo | Tipo | Descripcion |
|---------|------|-------------|
| `creacion_propuesta_endoso.php` | Frontend | Formulario de creacion/edicion (1126 lineas). Secciones accordion: info general, primas/montos, comentarios. Maneja 6 flujos distintos. Obtiene fecha desde API mindicador.cl. |
| `listado_endosos.php` | Frontend | Listado DataTables de endosos. Columnas: propuesta, tipo, poliza, proponente, fechas. Boton descarga Excel. |
| `listado_propuesta_endosos.php` | Frontend | Listado DataTables de propuestas de endoso. Incluye estado y motivo de rechazo. |
| `documento_propuesta_endoso.php` | Frontend | Genera documento visual/PDF de propuesta de endoso con jsPDF + html2canvas. Usa `logo_bamboo _verde.png`. |
| `backend/endosos/busqueda_listado_endosos.php` | Backend API | Devuelve JSON con todos los endosos. |
| `backend/endosos/busqueda_listado_endosos_filtrada.php` | Backend API | Devuelve JSON filtrado por `id_poliza`. |
| `backend/endosos/busqueda_listado_propuesta_endoso.php` | Backend API | Devuelve JSON con propuestas de endoso. |
| `backend/endosos/crea_endosos.php` | Backend | Procesa creacion/actualizacion de endosos (14KB). 6 acciones: crear propuesta manual/web, actualizar propuesta/endoso, crear endoso, rechazar. Genera numeros automaticos (E000001, E000002...). |
| `backend/endosos/genera_excel_endosos.php` | Backend | Genera Excel de endosos con PhpSpreadsheet. |
| `backend/endosos/genera_excel_propuesta_endosos.php` | Backend | Genera Excel de propuestas de endoso. |

### 1.3 Exportacion Excel de Polizas

| Archivo | Descripcion |
|---------|-------------|
| `backend/polizas/genera_excel_polizas.php` | Genera Excel completo de polizas (54 columnas A-BB). Consulta `polizas_2` con JOINs a `items`, `clientes` y `endosos`. Usa PhpSpreadsheet con encabezados en negrita, panel congelado, auto-filtros. Nombre de archivo con fecha/hora. |
| `backend/polizas/genera_excel_polizas_filtradas.php` | Genera Excel filtrado por polizas con vigencia proxima a vencer (N dias configurable via parametro). |

### 1.4 Imagenes Nuevas

| Archivo | Uso |
|---------|-----|
| `images/logo_bamboo _verde.png` | Usado en documentos PDF de propuestas. **Nota: el nombre tiene un espacio.** |
| `images/flecha_atras.png` | Verificar si esta en uso. |
| `images/Volver_atras.png` | Verificar si esta en uso. |

---

## 2. Archivos Modificados - Backend

### 2.1 Backend Actividades (Tareas)

#### `backend/actividades/busqueda_listado_tareas.php`
- Se agrego soporte para **"propuestas"** como nuevo tipo de relacion: `sum(CASE WHEN base ='propuestas' THEN 1 ELSE 0 END) as propuestas`
- Se agrego `"propuestas" =>& $tareas->propuestas` al array de relaciones
- Se agrego variable `$numero_propuesta` a los arrays inicializados
- La query de polizas cambio de tabla `polizas` (con JOINs complejos a clientes) a tabla `polizas_2` (query simplificada)
- Se eliminaron muchos campos del array de polizas: ramo, compania, materia_asegurada, patente_ubicacion, cobertura, datos de proponente/asegurado
- Se agrego bloque completo `case "propuestas"` que consulta tabla `propuesta_polizas`

#### `backend/actividades/busqueda_listado_tareas_completas.php`
- Mismos cambios que `busqueda_listado_tareas.php`
- Adicionalmente: la query principal cambio de `fecha_completada as fecha_cierre` a `fecha_ingreso, fecha_completada` como campos separados

#### `backend/actividades/busqueda_listado_tareas_recurrentes.php`
- Mismos cambios de soporte para propuestas
- Migracion de query de `polizas` a `polizas_2`
- Bloque nuevo `case "propuestas"`

#### `backend/actividades/cierra_tarea.php`
- Se agrego `mysqli_select_db($link, 'gestio10_asesori1_bamboo')` (correccion de conexion a BD)

#### `backend/actividades/crea_tarea.php`
- Sin cambios funcionales (solo diferencias de encoding)

### 2.2 Backend Clientes

#### `backend/clientes/busca_cliente.php`
- Se agrego `mysqli_close($link)` al final

#### `backend/clientes/busqueda_listado_clientes.php`
- Se agrego `mysqli_close($link)` antes de `echo $codigo`

#### `backend/clientes/busqueda_nombre.php`
- Sin cambios funcionales

#### `backend/clientes/clientes_duplicados.php`
- Sin cambios funcionales

#### `backend/clientes/crea_cliente.php`
- Se agrego `mysqli_close($link)` despues del ciclo de contactos

#### `backend/clientes/elimina_cliente.php`
- Se agrego `mysqli_select_db($link, 'gestio10_asesori1_bamboo')`
- Se agrego `mysqli_close($link)`

#### `backend/clientes/modifica_cliente.php`
- Se agrego `mysqli_set_charset($link, 'utf8')` y `mysqli_select_db(...)` al inicio del archivo
- Se agrego `mysqli_close($link)` al final

### 2.3 Backend Polizas

#### `backend/polizas/busqueda_listado_polizas.php` (REESCRITURA MAYOR)
- Query SQL reescrita completamente: de tabla `polizas` con ~50 campos planos a tabla `polizas_2` con JOINs a `items`, `clientes` y `endosos`
- Nuevo sistema de **items anidados**: cada poliza ahora tiene un array de items con materia_asegurada, patente, cobertura, deducible, tasas, primas individuales
- Nuevo sistema de **endosos anidados**: cada poliza tiene un array de endosos con numero, tipo, descripcion, dice/debe_decir, vigencias
- Primas se calculan con `SUM()` agrupando por items
- Agrega campo `consolidado_patentes` que concatena patentes de todos los items
- Se agrego `mysqli_close($link)`

#### `backend/polizas/busqueda_listado_polizas_filtrada.php` (REESCRITURA MAYOR)
- Mismos cambios que `busqueda_listado_polizas.php`
- Filtro adicional: `where a.estado not in ('Rechazado', 'Anulado', 'Cancelado')` (se agrego 'Rechazado')
- No incluye sistema de endosos (a diferencia de la version sin filtrar)

#### `backend/polizas/busqueda_poliza_renovada.php`
- Se agrego comentario `//obsoleto?` al inicio
- Se agrego `mysqli_close($link)`

#### `backend/polizas/crea_poliza.php`
- Todos los `INSERT INTO polizas` y `UPDATE polizas` cambiaron a `INSERT INTO polizas_2` y `UPDATE polizas_2`
- Se agrego `mysqli_close($link)` (NOTA: esta ubicado dentro de la funcion `cambia_puntos_por_coma()`, lo que podria cerrar la conexion prematuramente)

#### `backend/polizas/modifica_poliza.php`
- Se agrego comentario `//obsoleto?` al inicio
- Se agrego `mysqli_close($link)` (ubicado despues de un `break`, podria ser codigo inalcanzable)

### 2.4 `backend/funciones.php`
- Sin cambios funcionales

---

## 3. Archivos Modificados - Frontend

### 3.1 `header2.php` (CAMBIO MAYOR)

**Sistema de historial/navegacion (nuevo):**
- Bloque PHP completo para gestionar historial via cookies JSON
- Funcion `retrocede()` que recupera la ultima entrada del historial
- Cookies: `valida_arreglo`, `arreglo`, `historial`

**Menu de navegacion:**
- Seccion Polizas cambio de 2 a 4 opciones:
  - "Creacion Propuesta" -> `creacion_propuesta_poliza.php`
  - "Listado Propuesta" -> `listado_propuesta_polizas.php`
  - "Creacion Poliza Web" -> funcion JS `crear_poliza_web()`
  - "Listado de polizas" (se mantiene)
- Nueva seccion **"Endosos"** con 2 opciones:
  - "Listado Propuesta Endosos" -> `listado_propuesta_endosos.php`
  - "Listado de Endosos" -> `listado_endosos.php`

**Scripts nuevos:**
- `jquery.redirect.js` (plugin de redireccion jQuery)
- `js-cookie` (libreria para manejo de cookies)

**Funciones JS nuevas:**
- `volveratras()`: navega al registro anterior del historial
- `crear_poliza_web()`: redirige a creacion de propuesta con accion especifica

### 3.2 `index.php`

- Query de ramos cambio de `polizas` a `polizas_2` con JOIN a `ramos_agrupados`, incluyendo calculo de porcentaje
- Se agrego variable `$porcentaje` y `mysqli_close($link)`
- Se agrego segundo canvas para grafico adicional (`myChart2`)
- Tabla de polizas simplificada: de ~40 columnas a ~13 columnas esenciales
- Se agrego boton "Descargar Excel" en seccion de polizas
- DataTable de polizas cambio: ahora usa `busqueda_listado_polizas.php` en vez de `_filtrada`
- DataTable simplificado: ya no muestra `poliza (item)`, solo numero de poliza
- Mensaje "No hay registros asociados" cambio a "Se estan cargando los registros. Espera unos segundos mas."
- `scrollX` cambio de `true` a `false` en varias tablas

### 3.3 `creacion_actividades.php`

- Se agrego `mysqli_set_charset` y `mysqli_select_db` al inicio
- Se agrego variable `$num_prop_poliza=0`
- Query de polizas cambio de `polizas` a `polizas_2` con JOIN a `items`
- Se eliminaron columnas materia_asegurada, patente_ubicacion, cobertura de la tabla HTML
- Se agrego tabla HTML completa para "Datos propuesta poliza Asociada"
- Se agrego bloque PHP nuevo para manejar llegada desde propuesta (`$_POST["id_propuesta"]`)
- Se agrego bloque PHP nuevo para manejar llegada desde propuesta de poliza (`$_POST["id_prop_poliza"]`)
- Funcion JS `post()` ahora incluye recopilacion de checkboxes de propuestas
- Se eliminaron bloques de comentarios obsoletos

### 3.4 `creacion_cliente.php`

- Labels cambiaron: "Direccion Principal" -> "Direccion Particular", "Direccion Secundaria" -> "Direccion Comercial"
- Se elimino dialogo de confirmacion al salir (`onbeforeunload`)
- Se agrego `mysqli_close($link)`
- Campo "Referido" cambio de input texto a dropdown con AJAX
- Seccion de contactos reescrita: ahora permite agregar/eliminar multiples contactos dinamicamente

### 3.5 `creacion_template.php`

- Se agrego `mysqli_set_charset` y `mysqli_select_db` al inicio
- Se agrego `mysqli_close($link)` al final del procesamiento
- Se agregaron nuevas opciones de instancia de template:
  - "Enviar poliza - Varios Items" (`varios_items_envio_poliza`)
  - "Reenviar poliza - Varios Items" (`varios_items_reenvio_poliza`)

### 3.6 `listado_clientes.php`

- Se agrego `mysqli_set_charset` y `mysqli_select_db` al inicio
- Mensaje de zero records cambio a "Se estan cargando los registros..."
- Funcion `format()` modificada para incluir logica de cookies de historial

### 3.7 `listado_polizas.php` (CAMBIO MAYOR)

- Se agrego `mysqli_set_charset` y `mysqli_select_db` al inicio
- Tabla simplificada de ~40 columnas a ~13 columnas esenciales
- Se eliminaron: asegurado, materia, deducible, primas individuales, comisiones, pagos, boletas, vendedor, cuotas
- Se agrego boton "Descargar Excel" -> `backend/polizas/genera_excel_polizas.php`
- Columna poliza ya no muestra `poliza (item)`, solo numero de poliza
- SearchPanes reducido de columnas `[2,3,13,14]` a solo `[2]`

### 3.8 `listado_tareas.php`

- Se agrego `mysqli_set_charset` y `mysqli_select_db` al inicio
- Se agrego columna "Propuestas asociadas" (`numero_propuesta[]`) en DataTable
- Tabla de detalle de polizas simplificada (sin materia asegurada, compania, ramo)
- Se agrego tabla de detalle de propuestas asociadas en vista expandible
- Boton de poliza cambio de "Modificar" (fas fa-edit) a "Buscar" (fas fa-search)
- Mensaje de zero records actualizado

### 3.9 `listado_tareas_recurrentes.php` (CRECIMIENTO +47%)

- Se agrego `mysqli_set_charset` y `mysqli_select_db` al inicio
- Se agrego hoja de estilos DataTables Checkboxes
- Se agrego columna de checkbox para seleccion multiple
- Se agrego columna "Propuestas asociadas"
- Se agrego modal Bootstrap para "Finalizar multiples tareas"
- Funciones JS nuevas: `listado_tareas_multiples()`, `actualiza_multitarea()`
- Detalle expandible ahora incluye propuestas y logica de historial

### 3.10 `resumen2.php` (CRECIMIENTO +56%, de 1632 a 2545 lineas)

- Backend PHP refactorizado: queries cambian a `polizas_2` con JOINs a `items`
- Se agregaron variables: `$endosos`, `$propuestas`, `$propuestas_endosos`
- Se agregaron 3 nuevos `case` en el switch: `'propuesta'`, `'endoso'`, `'propuesta_endoso'`
- De **4 pestanas** a **7 pestanas**: se agregan Propuesta, Endoso, Propuesta Endoso
- Se agregan 3 nuevos DataTables con endpoints AJAX propios
- Se agregan 3 funciones JS de detalle expandible: `format_propuestas()`, `format_endosos()`, `format_propuestas_endosos()`

### 3.11 `solicitar_info.php`

- Se agregaron nuevas opciones de ramo: "SEGURO INTEGRAL DE COMERCIO", "SALUD"
- Se agregaron nuevas instancias: "Envio propuesta", "Cobranza", "Siniestro"

### 3.12 `template_poliza.php`

- Query cambio de tabla `polizas` a `polizas_2` con JOIN a `items` y `clientes`
- Variables adaptadas al nuevo modelo de datos

---

## 4. Archivos CSS y JS

**Resultado: Sin cambios.** Los 5 archivos de librerias y estilos son identicos entre repositorio y produccion.

| Archivo | Estado |
|---------|--------|
| `css/bootstrap-4.3.1.css` | Identico |
| `css/estilos.css` | Identico |
| `js/bootstrap-4.3.1.js` | Identico |
| `js/jquery-3.3.1.min.js` | Identico |
| `js/popper.min.js` | Identico |

**Nota:** Se detecto un posible bug en `estilos.css` linea 14: `tr:nth-child(en)` deberia ser `tr:nth-child(even)`. Este error existe en ambas versiones.

---

## 5. Archivos de Prueba / Desarrollo

Los siguientes archivos con nombre "test" existen solo en produccion. Algunos son archivos de prueba descartables y otros son versiones funcionales en desarrollo:

| Archivo | Descripcion | Recomendacion |
|---------|-------------|---------------|
| `TEST_CESAR.php` | Version de prueba del formulario de endosos con datos hardcodeados (`$_POST["numero_poliza"]='872'`). | Eliminar de produccion |
| `test2.php` | Dashboard mejorado: mejor manejo de errores, graficos de entradas/salidas, filtro por dias de vencimiento. **Es funcional.** | Revisar si reemplaza a `index.php` |
| `test2_cesar.php` | Formulario de clientes con validacion de RUT, contactos dinamicos, dropdown de referido. **Es funcional.** | Revisar si reemplaza a `creacion_cliente.php` |
| `test3.php` | Header/barra de navegacion con indicadores economicos (UF, dolar via mindicador.cl). **Es funcional.** | Revisar si reemplaza a `header2.php` |
| `test3_cesar.php` | Componente grande (+48K tokens). Requiere revision adicional. | Revisar |
| `test4.php` | Backend de creacion/modificacion de tareas con tokens unicos y trazabilidad. **Es funcional.** | Revisar si complementa al existente |

---

## 6. Archivos Movidos a `archivos_por_eliminar/`

El siguiente directorio contiene 15 archivos obsoletos. Muchos apuntan a la base de datos de QA (`gestio10_asesori1_bamboo_QA`) y no son funcionales en produccion.

| Archivo | Descripcion |
|---------|-------------|
| `aceptar_poliza.php` | Formulario obsoleto para aceptar/renovar polizas. Apunta a BD de QA. |
| `busqueda.php` | Pagina de busqueda de clientes. Apunta a BD de QA. |
| `Consolidado.php` | Pagina resumen de clientes, polizas y tareas. Apunta a BD de QA. |
| `Creacion_Cliente.css` | Hoja de estilos obsoleta (colores verde #0D5519, #20BD39). |
| `creacion_poliza.php` | Formulario antiguo de creacion de polizas. Apunta a BD de QA. |
| `creacion_poliza_web.php` | Version web del formulario de polizas. Apunta a BD de QA. |
| `header.php` | Version antigua del header. |
| `luciano.php` | Archivo vacio. |
| `modificacion_cliente.php` | Formulario de modificacion de clientes. Apunta a BD de QA. |
| `prueba.html` | HTML de prueba para tabla dinamica de contactos. |
| `resumen.php` | Pagina resumen similar a Consolidado.php. Apunta a BD de QA. |
| `test.php` | Estructura basica vacia con titulo "Creacion Poliza". |
| `test.html` | HTML vacio. |
| `test1.html` | Formulario de polizas apuntando a backend de QA. |

**Estos archivos pueden eliminarse de produccion sin riesgo.**

---

## 7. Otros Archivos Solo en Produccion

| Archivo | Descripcion |
|---------|-------------|
| `.ftpquota` | Generado automaticamente por cPanel. No versionar. |
| `error_log` (raiz) | Log de errores PHP. No versionar. |
| `backend/actividades/error_log` | Log de errores. No versionar. |
| `backend/clientes/error_log` | Log de errores. No versionar. |
| `backend/polizas/error_log` | Log de errores. No versionar. |
| `backend/endosos/error_log` | Log de errores. No versionar. |
| `backend/propuesta_polizas/error_log` | Log de errores. No versionar. |

---

## 8. Archivos del Repositorio que Ya No Existen en Produccion

Los siguientes archivos existen en el repositorio pero fueron eliminados de la raiz de produccion (algunos movidos a `archivos_por_eliminar/`):

| Archivo | Observacion |
|---------|-------------|
| `busqueda.php` | Movido a `archivos_por_eliminar/` |
| `Consolidado.php` | Movido a `archivos_por_eliminar/` |
| `creacion_poliza.php` | Reemplazado por `creacion_propuesta_poliza.php` |
| `Creacion_Cliente.css` | Movido a `archivos_por_eliminar/` |
| `header.php` | Movido a `archivos_por_eliminar/` (reemplazado por `header2.php`) |
| `modificacion_cliente.php` | Movido a `archivos_por_eliminar/` |
| `resumen.php` | Movido a `archivos_por_eliminar/` (reemplazado por `resumen2.php`) |
| `test.php` | Movido a `archivos_por_eliminar/` |
| `test1.html` | Movido a `archivos_por_eliminar/` |

---

## 9. Patrones Transversales de Cambio

| Patron | Archivos afectados | Detalle |
|--------|-------------------|---------|
| Migracion `polizas` -> `polizas_2` + `items` | ~15 archivos | Nuevo modelo normalizado con items por poliza |
| Soporte para propuestas en tareas | ~8 archivos | Nuevo `case "propuestas"` en busqueda de tareas |
| Agregado `mysqli_close($link)` | ~12 archivos | Cierre explicito de conexion a BD |
| Agregado `mysqli_select_db()` al inicio | ~10 archivos | Seleccion explicita de BD |
| Simplificacion de DataTables | ~5 archivos | Menos columnas, datos esenciales |
| Sistema de historial via cookies | ~6 archivos | Navegacion con retroceso |
| Nuevos endpoints `_2.php` | ~3 archivos | Versionado de APIs AJAX |
| Botones "Descargar Excel" | ~4 archivos | Exportacion de datos |

---

## 10. Observaciones y Posibles Problemas

1. **`backend/polizas/crea_poliza.php`:** Se agrego `mysqli_close($link)` dentro de la funcion `cambia_puntos_por_coma()`. Esta funcion se llama multiples veces durante el procesamiento de variables POST, lo que podria **cerrar la conexion prematuramente** antes de ejecutar los INSERTs.

2. **`backend/polizas/modifica_poliza.php`:** El `mysqli_close($link)` esta ubicado despues de un `break`, por lo que podria ser **codigo inalcanzable**.

3. **Archivos marcados como `//obsoleto?`:** `busqueda_poliza_renovada.php` y `modifica_poliza.php` tienen este comentario. Confirmar si aun se usan.

4. **Endpoints `_2.php` referenciados en frontend:** Algunos archivos frontend apuntan a versiones `_2` de los endpoints AJAX (ej: `busqueda_listado_polizas_2.php`) que no aparecieron en la copia de produccion analizada. Verificar si existen en el servidor.

5. **Nombre de imagen con espacio:** `images/logo_bamboo _verde.png` tiene un espacio en el nombre. Funciona pero puede causar problemas en ciertos contextos.

6. **Bug en `estilos.css`:** Linea 14 tiene `tr:nth-child(en)` que deberia ser `tr:nth-child(even)`.

---

## Acciones Sugeridas

1. **Confirmar** que todos los cambios listados son correctos y esperados.
2. **Confirmar** el estado de los archivos `test*.php` -- cuales son versiones finales que deben reemplazar a los originales y cuales son borradores.
3. **Confirmar** si los archivos en `archivos_por_eliminar/` pueden eliminarse definitivamente.
4. **Revisar** las observaciones de la seccion 10 (posibles bugs).
5. Una vez confirmado, sincronizar el repositorio con el estado actual de produccion.
