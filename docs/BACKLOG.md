# Backlog de issues — Bamboo

Registro de issues/mejoras pendientes. GitHub Issues está deshabilitado en el repo,
así que el seguimiento vive aquí (versionado). Estado: `abierto` | `en progreso` | `cerrado`.

---

## #1 — Navegación "volver": preservar el estado del listado (página, filtros, scroll)

- **Estado:** en validación (implementado en `bamboo/`, desplegado a redesign.customware.cl — 16-jun-2026)
- **Prioridad:** media-alta (UX de trabajo diario)
- **Reportado por:** Adriana — 16-jun-2026

**Implementado (commit 9f1ae0e).**
- `stateSave: true` + `stateDuration: -1` (sessionStorage) en los 8 listados
  DataTables → recuerdan página, búsqueda y orden durante la sesión.
- Helper global `bbVolver(fallbackUrl)` en `layout_end.php`: el botón "Volver"
  usa `history.back()` para regresar a la página de origen (donde el listado se
  restaura con su estado vía bfcache/stateSave); si no hay origen interno en el
  historial, navega al fallback.
- Los 6 botones "Volver al listado" de las páginas de creación/edición (endoso,
  siniestro, propuesta de póliza, actividad, cliente, template póliza) usan `bbVolver`.

**Réplica a bambooQA (parcial — 16-jun-2026).** `bambooQA/` es la versión antigua
(sin rediseñar): cada listado trae su `<head>` inline, usa `header2.php`, no tiene
`layout_end.php` ni botones "Volver al listado", y su "volver" es otro paradigma
(`volveratras()` → `retrocede()` server-side + `$.redirect` POST). Se replicó **solo
el `stateSave`** a sus 8 listados (núcleo del fix; funciona con cualquier mecanismo de
volver). NO se portó `bbVolver`/botones: alto esfuerzo en código que no se despliega en
redesign y que quedará obsoleto cuando redesign sea master.

**Pendiente.**
- Validar en redesign.customware.cl el caso reportado (pólizas pág. 5 → crear
  endoso → Volver → pág. 5) y el botón "atrás" del navegador.
- El scroll vertical de la ventana no se restaura (stateSave cubre la paginación,
  que es el caso del reporte); evaluar si hace falta.

**Problema.** Al entrar a una acción desde un listado y luego volver, se pierde la
posición. Ejemplo: estoy en el *listado de pólizas, página 5*, hago clic en *crear
endoso*; al retroceder debería volver a la **página 5** del listado de pólizas (con los
mismos filtros, orden, búsqueda y scroll), pero el listado vuelve al inicio.

**Comportamiento esperado.** Un "volver" que devuelva al usuario exactamente al estado
en que estaba: página de paginación, término de búsqueda, columna/orden y scroll.

**Notas técnicas / enfoques posibles.**
- Los listados usan DataTables. DataTables tiene `stateSave: true` (persiste página,
  búsqueda, orden y largo en `localStorage`) — la opción más barata de probar.
- Alternativa más explícita: reflejar el estado del listado en la URL (query params:
  `?page=5&search=...&order=...`) y restaurarlo al cargar; así el back del navegador
  también funciona.
- Considerar un botón "← Volver" coherente en las pantallas de acción (crear/editar
  endoso, póliza, etc.) que regrese al listado de origen con su estado.
- Validar que aplica a los listados principales: pólizas, propuestas, endosos,
  clientes, siniestros, tareas.

---

## #2 — Responsividad: el contenido se corta horizontalmente en la pantalla de la usuaria

- **Estado:** abierto
- **Prioridad:** alta (afecta el trabajo diario de la usuaria principal)
- **Reportado por:** Adriana — 16-jun-2026 · **reconfirmado 17-jun** ("La pantalla no se
  muestra entera, debo correr el cursor hacia la derecha para verla completa" — visto en
  el listado de Siniestros, mismo síntoma).

**Problema.** La app no es totalmente responsiva. En la pantalla que la usuaria usa para
trabajar, el contenido se **corta a la derecha**: los botones de acción ("← Pólizas" y
"Descargar") quedan parcialmente fuera de la vista y la tabla se recorta, sin un scroll
horizontal adecuado.

**Entorno reportado (de las capturas).**
- Pantalla de alta resolución/densidad: **2880 × 1800 @ 60 Hz**, GPU Intel Arc 140T.
- Con el escalado de Windows (típico en estas pantallas) el viewport efectivo es más
  angosto, y el layout no se adapta a ese ancho.
- Vista afectada visible: listado de propuestas/pólizas — header (UF/USD/fecha/usuario),
  barra de acciones "← Pólizas" / "Descargar", "Búsqueda rápida" y tabla con columnas
  Proponente / Grupo / Referido.

**Comportamiento esperado.** El contenido debe ajustarse al ancho disponible (o tener
scroll horizontal contenido) en cualquier resolución/escalado, sin recortar botones ni
columnas.

**Notas técnicas / enfoques posibles.**
- Revisar contenedores con ancho fijo en `px` o `min-width` que exceda el viewport;
  preferir anchos fluidos (`%`, `max-width`, `clamp()`).
- Envolver las tablas en un contenedor con `overflow-x:auto` para que no empujen el
  layout.
- Revisar la barra de acciones (botones "Pólizas"/"Descargar") para que se reacomode
  (wrap) en anchos menores.
- Auditar media queries / breakpoints; probar a distintos niveles de escalado de Windows
  (125 %, 150 %, 175 %) que son los que cambian el viewport efectivo en pantallas 2.8K/4K.
- Verificar `<meta name="viewport">` y el layout base del portal.

---

## #3 — Tooltips en los íconos del sidebar colapsado

- **Estado:** ✅ resuelto (commit 54d18c3, desplegado a redesign.customware.cl — 17-jun-2026)
- **Reportado por:** Adriana — 17-jun-2026

**Problema.** Con el sidebar colapsado (solo íconos), al pasar el cursor no se veía a qué
sección llevaba cada ícono; había que pinchar para saberlo.

**Solución.** `aria-label` + `data-tooltip` en los 7 nav-links (`layout.php`) y un tooltip
CSS en `components.css` que aparece de inmediato al hover cuando el sidebar está colapsado.

---

## #4 — Exportar a Excel el historial de un siniestro por fecha

- **Estado:** abierto
- **Prioridad:** media
- **Reportado por:** Adriana — 17-jun-2026

**Problema.** No se puede exportar el historial/bitácora de un mismo siniestro (por fecha)
a Excel. Adriana lo necesita para "reconstituir escena" y enviar a la compañía cuando hay
un problema.

**Notas.** Ya existe la bitácora del siniestro (`backend/siniestros/busqueda_bitacora_siniestro.php`).
Falta un botón "Exportar historial" que genere un Excel ordenado por fecha (patrón de los
`genera_excel_*.php` con PhpSpreadsheet ya usados en el repo).

---

## #5 — Bug: "Agregar bien propio" trae los datos del auto

- **Estado:** abierto (por investigar)
- **Prioridad:** media-alta (confunde el flujo de bienes afectados)
- **Reportado por:** Adriana — 17-jun-2026

**Problema.** En "Bienes afectados" del siniestro, al pinchar **"Agregar bien propio"** se
precargan los datos del auto; el objetivo del botón no queda claro.

**Notas.** Vive en `creacion_siniestro.php` (+ `backend/siniestros/crea_bien_afectado.php`).
Revisar la precarga del formulario de bien propio: probablemente hereda datos del ítem/póliza
(vehículo) cuando no debería, o falta distinguir "bien propio" vs "daño a terceros".

---

## #6 — Fecha editable en cada etapa del siniestro (no asumir la fecha de sistema)

- **Estado:** abierto
- **Prioridad:** alta (Adriana no registra los eventos el día que ocurren)
- **Reportado por:** Adriana — 17-jun-2026

**Problema.** Al ingresar el n° de siniestro (y en cada etapa) el sistema asume la fecha
de hoy. Adriana no siempre ingresa el mismo día, así que **cada etapa debe pedir la fecha**
del evento real, editable.

**Notas.** `creacion_siniestro.php` + backend de crea/actualiza. Agregar campo fecha
(por defecto hoy, editable) en cada etapa/registro; guardar la fecha ingresada, no `now()`.

---

## #7 — Opción "No" en el modal "Notificar al liquidador"

- **Estado:** abierto
- **Prioridad:** media
- **Reportado por:** Adriana — 17-jun-2026

**Problema.** El modal "Notificar al liquidador" ofrece solo "Más tarde" y "Abrir correo".
Falta una opción **"No"** para casos en que no corresponde notificar (ej. el liquidador ya
dio la orden de reparación), que cierre el pendiente sin generar correo.

**Notas.** Modal en `creacion_siniestro.php`; el envío en `backend/siniestros/notifica_liquidador.php`
y el estado del pendiente en `backend/siniestros/actualiza_pendiente.php`. Agregar acción "No"
que marque el pendiente como resuelto/no-aplica sin abrir correo.
