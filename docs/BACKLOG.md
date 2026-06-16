# Backlog de issues — Bamboo

Registro de issues/mejoras pendientes. GitHub Issues está deshabilitado en el repo,
así que el seguimiento vive aquí (versionado). Estado: `abierto` | `en progreso` | `cerrado`.

---

## #1 — Navegación "volver": preservar el estado del listado (página, filtros, scroll)

- **Estado:** abierto
- **Prioridad:** media-alta (UX de trabajo diario)
- **Reportado por:** Adriana — 16-jun-2026

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
- **Reportado por:** Adriana — 16-jun-2026

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
