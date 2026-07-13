# Runbook — Deploy de la landing a producción (cPanel bamboose / Planet Hosting)

Publica el contenido de `landing/` (rama `redesign`, lo aprobado en redesign.customware.cl)
como sitio raíz de **bambooseguros.cl**, reemplazando la web vieja de 2019.

**Contexto clave:**
- El hosting está (o estaba) en **PHP 5.6.40**; la landing requiere **PHP ≥ 7.4** → el
  Paso 1 es un GATE: sin selector de versión no hay deploy.
- Acceso solo por el panel web (puerto 2083 cerrado; el bridge 443 es intermitente/
  geobloqueado). Todo se hace con **File Manager** + **MultiPHP**.
- El correo de Adriana vive en **Google Workspace** — este deploy NO toca DNS ni MX.
- El paquete `landing_prod.zip` se genera desde el repo (ver "Generar el paquete").

---

## Paso 1 — GATE: verificar el selector de PHP (solo mirar, nada cambia)

1. Entrar al portal de clientes de Planet Hosting → cPanel de la cuenta `bamboose`.
2. Buscar **"MultiPHP Manager"** o **"Select PHP Version"** (CloudLinux).
3. ¿Aparece PHP **7.4, 8.0, 8.1, 8.2 o superior** como opción para bambooseguros.cl?
   - **SÍ** → anotar las versiones disponibles y seguir al Paso 2. **NO cambiar la
     versión todavía** (podría romper el sitio viejo que sigue en línea).
   - **NO** → **DETENERSE**. Enviar el ticket de abajo y reevaluar (alternativa: Netlify).

### Plantilla de ticket a Planet Hosting (si no hay PHP moderno)

> Asunto: Habilitar PHP 8.x para la cuenta bamboose (bambooseguros.cl)
>
> Hola, necesitamos publicar una nueva versión del sitio bambooseguros.cl que
> requiere PHP 7.4 o superior (idealmente 8.1+). En el cPanel de la cuenta
> `bamboose` no aparece un selector de versión de PHP (MultiPHP / Select PHP
> Version) o solo ofrece 5.6. ¿Pueden habilitar PHP 8.x para este dominio o
> indicarnos cómo seleccionarlo? Gracias.

## Paso 2 — Diagnóstico del motor

1. Renombrar `infra/phpcheck_prod.php` con sufijo aleatorio y SIN `_` inicial
   (ej. `bbcheck-k3x9.php`) y subirlo por File Manager a `public_html/`.
2. Abrir `https://bambooseguros.cl/bbcheck-k3x9.php` (desde una red que llegue al
   sitio). Revisar: versión PHP, ZipArchive OK, mail() disponible, temp escribible.
3. Dejarlo subido: se vuelve a consultar tras el cambio de versión (Paso 5). Se
   borra al final.

## Paso 3 — Backup del sitio viejo (obligatorio)

1. File Manager → `public_html/` → **Settings → Show Hidden Files (dotfiles)** ✔.
2. Select All → **Compress** → zip → nombre `backup_sitio2019_YYYYMMDD.zip`.
3. **Move** del zip a `/home/bamboose/` (fuera del docroot) y **Download** de una
   copia local. Sin backup descargado NO continuar.

## Paso 4 — Preparar el docroot

1. **Renombrar `_admin_bridge.php` → `bb-bridge-<sufijo>.php`** (elegir sufijo
   aleatorio y ANOTARLO). El `.htaccess` nuevo bloquea todo archivo que empiece
   con `_`; sin renombrarlo perdemos el acceso programable a la zona DNS.
2. Borrar el resto del contenido de `public_html/` (el sitio viejo ya está
   respaldado; los 301 del `.htaccess` nuevo cubren las URLs antiguas). Conservar
   SOLO: el bridge renombrado y el phpcheck del Paso 2.

## Paso 5 — Subir la landing y cambiar PHP (ventana única)

1. Upload de `landing_prod.zip` a `public_html/` → **Extract** ahí mismo → borrar el zip.
2. Verificar con Show Hidden Files que **`.htaccess` quedó en `public_html/`** y que
   existen `home.php`, `data/`, `partials/`, `seguros/`, `assets/`, `backend/`.
3. **MultiPHP Manager** → bambooseguros.cl → **PHP 8.x** (la más alta disponible).
4. Refrescar el phpcheck del Paso 2: debe decir versión 8.x y "Sintaxis 7.4: OK".
5. Borrar el phpcheck.
6. **SSL/TLS Status** → verificar cert válido para `bambooseguros.cl` y
   `www.bambooseguros.cl`; si está vencido/pendiente → **Run AutoSSL**.

## Paso 6 — Verificación (desde red que llegue al sitio: móvil 4G / red de Adriana)

- [ ] `https://www.bambooseguros.cl/` muestra la landing nueva
- [ ] `http://bambooseguros.cl/` redirige a `https://www.bambooseguros.cl/` (301)
- [ ] `/seguros/auto`, `/seguros-pymes`, `/nosotros`, `/faq` cargan (URLs limpias = rewrite OK)
- [ ] `/somos.php` → 301 → `/nosotros`; `/seguros-vehiculo.php` → 301 → `/seguros/auto`
- [ ] `/sitemap.xml`, `/robots.txt`, `/llms.txt` responden
- [ ] Formulario de cotización end-to-end → correo llega a asandoval@bambooseguros.cl
      (revisar spam) y `backend/leads.log` se escribió (verlo por File Manager)
- [ ] Botón "Acceso interno" (header/footer) → login de gestionipn.cl
- [ ] Botón WhatsApp abre wa.me con mensaje prellenado
- [ ] `/data/config.php` y `/partials/head.php` devuelven 403 (bloqueo de includes)

## Rollback (minutos)

1. Borrar el contenido nuevo de `public_html/`.
2. Extraer `backup_sitio2019_YYYYMMDD.zip` (está en `/home/bamboose/`) de vuelta en
   `public_html/`.
3. MultiPHP → devolver la versión a la que tenía (5.6).

---

## Generar el paquete `landing_prod.zip` (desde el repo, rama `redesign`)

Contenido de `landing/` con `.htaccess`, `robots.txt`, `llms.txt`, `site.webmanifest`;
excluye `.gitignore` y `backend/leads.log`. El zip debe extraer los archivos en la
raíz (sin carpeta contenedora `landing/`).

⚠️ **NO usar `[System.IO.Compression.ZipFile]::CreateFromDirectory` de PowerShell/.NET
Framework**: escribe las rutas internas con `\` y cPanel (Linux) extrae los archivos
como nombres planos corruptos (`data\config.php`). Generar el zip con PHP:

```bash
# 1) Copia limpia
cp -R landing /tmp/landing_pkg
rm -f /tmp/landing_pkg/.gitignore /tmp/landing_pkg/backend/leads.log
# 2) Zip con ZipArchive (rutas con "/" garantizadas). Con el PHP portable en Windows,
#    habilitar la extensión: php -d extension_dir=<php>/ext -d extension=zip ...
php -d extension=zip -r '
$src = $argv[1]; $zip = new ZipArchive();
$zip->open($argv[2], ZipArchive::CREATE | ZipArchive::OVERWRITE);
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) if ($f->isFile())
    $zip->addFile($f->getPathname(), str_replace("\\", "/", substr($f->getPathname(), strlen($src) + 1)));
$zip->close();' /tmp/landing_pkg /tmp/landing_prod.zip
```

Validar antes de subir: ninguna entrada con `\`, `.htaccess` presente en la raíz,
~62 archivos.
