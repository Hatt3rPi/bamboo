# Bamboo Seguros — Redesign visual (worktree `redesign`)

> **Tu primera tarea como Claude entrando a este worktree**: leer este archivo
> entero, hacer una pasada por los archivos clave (sección "Archivos a leer
> antes de planificar") y entregarle al usuario un plan de implementación
> incremental de migración página por página. **No empieces a editar páginas
> sin antes proponer y validar el plan**.

---

## 1. Objetivo

Migrar el portal Bamboo Seguros (PHP server-side, multi-página) a la
**Variante 2 — "Moderna · cálido profesional"** del sistema visual entregado
por Claude Design. Esta variante fue **elegida y aceptada por la usuaria
final (Adriana Sandoval, corredora de seguros)** en la reunión del 22-abr-2026
sobre 3 propuestas comparativas.

**Cambios visuales esperados** (vs. el portal actual):
- Sidebar vertical oscuro (verde Bamboo `#536656` profundo) reemplaza el
  navbar horizontal.
- Topbar con breadcrumbs, UF/USD permanente, fecha, avatar de usuario.
- Tipografías `Inter` (texto), `Fraunces` (headings/displays), `Varela Round`
  (marca), `JetBrains Mono` (números).
- Paleta neutra cálida (beige/crema) reemplaza fondos blancos puros.
- Cards con sombras suaves, radios `8–12px`, KPIs grandes en serif.
- Re-skin completo de DataTables, badges, alerts, tabs, modales, dropdowns.

**Lo que NO cambia en este worktree**:
- Lógica de negocio. **Cero PHP de dominio se toca**.
- Schema de base de datos.
- Endpoints `backend/*/*.php`.
- Comportamiento JS de las páginas (jQuery + Bootstrap modal/dropdown sigue).
- URL slugs de las páginas (`listado_clientes.php` sigue llamándose así).

Es un trabajo **puramente de presentación**: HTML wrapper + CSS + tokens.

---

## 2. Contexto del rediseño

- Cotizado y aceptado por la usuaria como trabajo aparte ($200.000 CLP).
- Reunión origen: `docs/reuniones/2026-04-22_adriana_cierre_flujo.md`
  (sección "Rediseño visual — propuesta comercial").
- Los mockups de las 3 variantes vivían en claude.ai/design (Artifact);
  la elegida es la Variante 2.

**Otros entregables comerciales del paquete (NO son parte de este worktree)**:
- Relanzar landing pública `bambooseguros.cl` (otro repo, otro proyecto).
- Mostrar UF/dólar permanente arriba (este worktree sí lo cubre, ya en
  `layout.php`).
- Prima neta visible en listados (esto sí es parte del rediseño — agregar
  columna a los listados).

---

## 3. Aislamiento — qué tocás, qué NO tocás

| Capa | Estado | Cómo tratarla |
|---|---|---|
| Branch git `redesign` | Aislada de `master`. | Trabajás siempre acá. `master` queda intacto. |
| Carpeta `bamboo/` | Misma estructura que master. | **Editás aquí**. Es el target del rediseño. |
| Carpeta `bambooQA/` | Réplica histórica para QA. | **NO replicar nada acá durante el rediseño.** Cuando se mergee a master, se replica. |
| Carpeta `archivos_por_eliminar/` | Legado. | NO tocar. |
| Supabase São Paulo | Compartida con QA. | NO ejecutar migraciones SQL en este worktree. Schema es solo lectura. |
| Producción `gestionipn.cl/bamboo/` | Servicio en vivo. | Nunca alcanzada por este worktree. |

---

## 4. Ambiente de prueba

- **URL**: `https://redesign.customware.cl/`
- **DocumentRoot**: `/home/customw2/public_html/bambooRedesign/`
- **Login**: mismas credenciales que QA (la BD es la misma).
- **Subdominio creado por API** (no se necesita re-crear).
- **Base de datos**: Supabase São Paulo (proyecto `dynnhfqpagwkdynzubmh`,
  pooler `aws-1-sa-east-1.pooler.supabase.com:5432`).

---

## 5. Cómo deployar

```bash
# desde tu máquina (después de commitear y pushear)
git push origin redesign

# luego, en navegador o curl:
curl https://customware.cl/deploy_redesign.php
```

El script `customware.cl/deploy_redesign.php` (vive en server) hace:
1. `git fetch origin redesign && git checkout redesign && git pull`
2. Copia `bamboo/`, `assets/`, `backend/db.php`, `backend/login/`, `vendor/`,
   `index.php` a `public_html/bambooRedesign/`.
3. Reescribe los includes hardcoded
   `/home/gestio10/public_html` → `/home/customw2/public_html/bambooRedesign`
   en todos los `.php`.
4. Imprime: `Deploy redesign OK - N rutas - 0 errores - HH:MM:SS`.

**Importante**: el `backend/config.php` **NO se toca** por el deploy. Vive
solo en server (`/public_html/bambooRedesign/backend/config.php`) con las
credenciales del Supabase. Si lo borrás accidentalmente, copiá el de
`/public_html/backend/config.php`.

**Ojo**: `infra/deploy_redesign.php` en el repo es solo la "fuente". El que
ejecuta es la copia que vive en `/public_html/`. Si modificás el script, hay
que **subirlo manualmente** vía cPanel API (Fileman/save_file_content) o
File Manager.

---

## 6. Estado actual del worktree (al momento de escribir este doc)

**Commit inicial** (en `redesign`, ya pusheado):
- `assets/css/bamboo/tokens.css` — variables CSS (colores, espaciado, tipo, sombras)
- `assets/css/bamboo/components.css` — overrides Bootstrap + nuevos componentes (`.app-shell`, `.bb-sidebar`, `.bb-topbar`, `.bb-kpi`, etc.)
- `assets/css/bamboo/datatables-bamboo.css` — re-skin DataTables 1.10
- `bamboo/layout.php` — abre el shell (sidebar + topbar + main). Reemplaza `header2.php`.
- `bamboo/layout_end.php` — cierra el shell + carga jQuery 3.5 + Bootstrap JS + DataTables JS + persistencia de colapso del sidebar.
- `infra/deploy_redesign.php` — fuente del deploy script.

**Lo que NO está hecho (pendiente para el plan)**:
- Ninguna página `bamboo/*.php` ha sido migrada todavía a `layout.php`.
  Todas siguen usando `header2.php` (header viejo verde + navbar horizontal).
- Por lo tanto, hoy `https://redesign.customware.cl/` se ve **idéntico a QA**.

---

## 7. Archivos a leer antes de planificar

Recomendado en este orden:

1. `assets/css/bamboo/tokens.css` — entender la paleta y escala.
2. `assets/css/bamboo/components.css` — entender qué clases nuevas existen
   y qué overrides aplica a Bootstrap.
3. `bamboo/layout.php` y `bamboo/layout_end.php` — el shell que reemplaza
   `header2.php`.
4. `bamboo/header2.php` — el header viejo (lo que se reemplaza). Ver qué
   variables/cookies/sesión usa para no romper.
5. `bamboo/index.php` — dashboard. **Página piloto recomendada** para
   primera migración.
6. `bamboo/listado_clientes.php` o `bamboo/listado_polizas.php` — patrón
   de listado con DataTables. Una vez migrado uno, los demás se replican.
7. `bamboo/creacion_cliente.php` o `bamboo/creacion_siniestro.php` — patrón
   de formulario. Igual: uno migrado, los otros se replican.
8. `docs/reuniones/2026-04-22_adriana_cierre_flujo.md` — contexto comercial.

---

## 8. Patrón de migración página por página

Para cada `bamboo/<page>.php` la receta es:

**Antes (típico actual)**:
```php
<?php require_once "header2.php"; ?>
<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/...">
  <script src="https://cdn.../jquery.min.js"></script>
  <!-- duplicados -->
</head>
<body>
  <div class="container">
    <h1>Listado de clientes</h1>
    ...
  </div>
</body>
</html>
```

**Después**:
```php
<?php
$page_title      = 'Listado de clientes — Bamboo';
$page_active     = 'clientes';   // marca el item activo del sidebar
$breadcrumb_main = 'Clientes';
require_once 'layout.php';
?>

<div class="bb-page-header">
  <div>
    <h1>Clientes</h1>
    <div class="subtitle">248 activos · 12 con renovación próxima</div>
  </div>
  <a href="creacion_cliente.php" class="btn btn-primary">
    <i class="fas fa-plus mr-2"></i>Nuevo cliente
  </a>
</div>

<div class="card">
  <div class="card-body p-0">
    <table id="tabla_clientes" class="table table-hover w-100">
      <!-- contenido existente, sin tocar lógica -->
    </table>
  </div>
</div>

<?php require_once 'layout_end.php'; ?>
```

**Cosas a borrar al migrar** una página:
- `<!DOCTYPE html>`, `<html>`, `<head>`, `<body>` — los pone `layout.php`.
- `<link>` a Bootstrap, FontAwesome, DataTables — los carga `layout.php`.
- `<script>` a jQuery, Bootstrap JS, DataTables JS, Popper — los carga
  `layout_end.php`.
- `<div class="container">` exterior — el layout ya da el padding con
  `.bb-main`.
- Cualquier estilo inline `style="background-color: #536656"` y similares —
  ahora se manejan con tokens.

**Valores válidos para `$page_active`**: `inicio`, `clientes`, `polizas`,
`propuestas`, `endosos`, `tareas`, `siniestros`, `correos`.

---

## 9. Orden sugerido de migración

Mi sugerencia (proponé al usuario y validá antes de empezar):

1. **`bamboo/index.php`** (dashboard) — piloto. Validás visualmente toda
   la base: sidebar, topbar, KPIs, layout general.
2. **`bamboo/listado_clientes.php`** — patrón listado #1. Pone a prueba el
   re-skin de DataTables.
3. **`bamboo/listado_polizas.php`** + `listado_propuesta_polizas.php` +
   `listado_endosos.php` + `listado_propuesta_endosos.php` — replican el
   patrón establecido en (2).
4. **`bamboo/listado_siniestros.php`** + `seguimiento_bienes_afectados.php`
   — listado con filtros y chips/badges (ramo siniestros tiene chips
   especiales por responsable: Cliente/Liquidador/Compañía/Taller).
5. **`bamboo/listado_tareas.php`** + `listado_tareas_recurrentes.php`.
6. **`bamboo/creacion_cliente.php`** — patrón formulario #1.
7. **`bamboo/creacion_propuesta_poliza.php` + `creacion_propuesta_endoso.php`**
   — formularios complejos con tabs.
8. **`bamboo/creacion_siniestro.php`** — el más complejo (varios tabs,
   modal de bien afectado, sección Liquidador, sección Taller). Dejarlo
   para cuando los patrones estén estables.
9. **`bamboo/creacion_actividades.php`**.
10. **`bamboo/admin_*.php`** (catálogo documentos, plantillas correos).
11. **`bamboo/resumen2.php`, `template_poliza.php`, `documento_*`.

Después de cada paso: commit + push + deploy y revisar visualmente en
`https://redesign.customware.cl/`.

---

## 10. Tickets / fixes que el design system detectó pendientes

Cuando llegues a estas páginas, considerá:

- **Sub-navegación faltante en sidebar**: hoy "Correos" tiene 3 sub-items
  (`solicitar_info`, `creacion_template`, `admin_email_templates`) y
  "Siniestros" otros 3 (`listado_siniestros`, `seguimiento_bienes_afectados`,
  `admin_catalogo_documentos`). El sidebar nuevo los colapsa a 1 link cada
  uno. **Decisión a proponer**: dejar los sub-items dentro de la página
  contenedora (top tabs) o agregar despliegue al sidebar.
- **`$_SESSION['username']`** se usa en `layout.php` para el avatar. El
  login real setea esa key. OK.
- **Logo**: `layout.php` apunta a `/bamboo/images/bamboo.png`. Existe.
- **Logout**: `layout.php` apunta a `/backend/login/logout.php`. Existe.
- **`$tareas_pendientes`**: variable opcional para badge en sidebar. Hoy
  no se popula desde ninguna página. Si querés activar el badge, hay que
  agregar una query previa al `require_once 'layout.php'` en `index.php`
  (y otras donde aplique).
- **Cookie `historial`** del header viejo (botón "atrás"): el nuevo layout
  no la trae. Si una página la requiere, hay que decidir mantener o
  descartar. Recomendación: descartar, el sidebar ya da contexto.
- **CDNs duplicados**: cada `.php` legacy tiene su propio `<head>` con
  Bootstrap/jQuery/etc. Al migrar, **borrá esos `<link>` y `<script>`** o
  se cargan dos veces y peor: mezcla jQuery 1.11/3.3/3.5 según la página.
- **Estilos inline**: muchas páginas tienen `style="background-color:
  #536656"`. Reemplazar por clases bamboo (`.bg-bamboo`, `.text-bamboo`)
  o tokens.

---

## 11. Convenciones del proyecto a respetar (no son del rediseño)

Vienen del repo madre, hay que mantenerlas:

- Acceso PG: `db_query($link, ...)`, `db_fetch_object($res)`. Nunca tocar
  PostgreSQL directo, siempre vía `backend/db.php`.
- Includes hardcoded: `require_once "/home/gestio10/public_html/backend/config.php"` —
  el deploy reescribe este path. **No cambiar el path en código**.
- Strings con tildes: UTF-8 directo. No usar entidades HTML para tildes.
- Idioma: español.
- Aria-labels y accesibilidad: si añadís componentes nuevos, ponelos.

---

## 12. Reglas de oro

- **Solo se commitea a la rama `redesign`** desde este worktree. Verificalo
  antes de cada push: `git branch --show-current` debe decir `redesign`.
- **NO replicar a `bambooQA/`** durante el rediseño. Esa replicación se
  hace al mergear a master (eso lo hace el dueño del proyecto, no vos).
- **NO ejecutar migraciones SQL** (Supabase está compartida con QA y
  producción).
- **NO tocar `archivos_por_eliminar/`**.
- **Auto-commit y push después de cada bloque coherente** — el dueño del
  proyecto exige GitHub siempre actualizado.
- Cuando una migración se termine y deployes, **avisá visualmente al
  dueño** ("listo, mirá `https://redesign.customware.cl/<page>.php`")
  para que valide.
- **Para preguntas comerciales o de UX no-trivial** (¿agrego sub-nav al
  sidebar? ¿este botón va al lado o abajo?), preguntale al dueño antes
  de implementar.

---

## 13. Qué entregar como primera respuesta al dueño

Una vez leíste lo anterior y los archivos clave, devolvele un plan que
incluya:

- ✅ Confirmación de que entendiste el alcance.
- 📋 Lista ordenada de páginas a migrar (con cualquier ajuste a la
  sección 9 que propongas).
- ⏱️ Estimación de "bloques" lógicos (ej. "Bloque 1: piloto index.php +
  fixes de tokens si surgen — ~30 min" / "Bloque 2: 4 listados con
  DataTables — ~45 min" etc.).
- ❓ Preguntas para resolver antes de empezar (sub-nav, badges, mostrar
  prima neta en listados, etc.).
- 🚦 Una propuesta del **primer commit que vas a hacer** y qué incluiría.

Esperá su OK. Después, ejecutá bloque por bloque, commiteando y
pusheando cada uno. Cada bloque termina con `curl
https://customware.cl/deploy_redesign.php` y aviso visual al dueño.
