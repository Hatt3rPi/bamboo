# Idea — Integración Bamboo ↔ Google Drive de Adriana

**Estado:** propuesta, **no implementada**.
**Pedido original:** reunión 16-abr-2026 + conversación exploratoria 21-abr-2026.

## Contexto

Adriana hoy almacena los documentos de cada siniestro (informes del liquidador, finiquitos, recepciones municipales, fotos, etc.) en su Google Drive con plan de 2 TB. Quiere:

- **Ver** desde Bamboo los archivos que ya tiene en Drive (carpetas preexistentes con contenido).
- **Cargar** archivos nuevos desde el form de siniestro hacia su Drive (deseable, no bloqueante para una v1).
- El acceso es **solo para ella** — no se comparten enlaces con liquidador/cliente en esta etapa.

## Datos clave confirmados

| Dato | Valor |
|---|---|
| Tipo de cuenta | Google Workspace con dominio propio |
| Consent screen | **Internal** (solo su dominio) → sin verificación pública de Google, sin trabas de scopes |
| Alcance lectura | Carpetas existentes con contenido ya subido a mano |
| Alcance escritura | Deseable subir desde Bamboo |
| Audiencia | Solo Adriana |

Que sea **Workspace Internal** es la ventaja decisiva: Google no exige pasar por el proceso de verificación de app pública (que toma semanas), y los scopes amplios de Drive quedan habilitados dentro del dominio.

## Arquitectura propuesta

### Flujo OAuth 2.0 (Web application)

1. Adriana entra a Bamboo → click en botón "Conectar Google Drive".
2. Redirect a `accounts.google.com` con scopes solicitados.
3. Adriana autoriza **una sola vez**.
4. Google llama a `callback.php` con un `code`.
5. Bamboo intercambia el `code` por `access_token` + `refresh_token`.
6. Se guarda el `refresh_token` en una nueva tabla `google_tokens (usuario, refresh_token, scope, updated_at)`.
7. A partir de ahí, cada request a Drive usa el helper que refresca automáticamente si el access token expiró.

### Scopes mínimos

| Fase | Scope | Alcance |
|---|---|---|
| Lectura | `https://www.googleapis.com/auth/drive.readonly` | Ver toda la jerarquía de carpetas y archivos |
| Escritura | `https://www.googleapis.com/auth/drive` | Leer + crear + modificar archivos en cualquier parte |

Descartamos `drive.file` (solo archivos creados por la app) porque no podría listar las carpetas que Adriana ya tiene.

### Componentes PHP a construir

```
backend/google/
  ├─ config.php          # client_id, client_secret, redirect_uri (desde .env)
  ├─ auth.php            # genera URL de consent y redirige
  ├─ callback.php        # recibe code, intercambia por tokens, persiste
  ├─ client.php          # factory de Google_Client autenticado con auto-refresh
  ├─ lista_archivos.php  # GET ?id_carpeta=... → JSON con archivos
  └─ sube_archivo.php    # POST multipart → archivo a Drive, retorna metadata
```

Dependencia nueva vía Composer: `google/apiclient` (ya existe `vendor/` en el repo, encaja natural).

### Modelo de datos

**Nueva tabla**
```sql
CREATE TABLE google_tokens (
  id BIGSERIAL PRIMARY KEY,
  usuario TEXT NOT NULL UNIQUE,
  refresh_token TEXT NOT NULL,
  scope TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT now()
);
```

**Columna nueva en `siniestros`**
```sql
ALTER TABLE siniestros ADD COLUMN id_carpeta_drive TEXT NULL;
```
Almacena el ID o URL de la carpeta raíz del siniestro en Drive de Adriana. Se ingresa manualmente en el form (pegar el URL de la carpeta); en fase 3 podría crearse automáticamente.

### UI propuesta en `creacion_siniestro.php`

- Nueva sección colapsable "📂 Archivos en Drive".
- Campo `id_carpeta_drive` (input con label "Carpeta Drive de este siniestro" + ícono 🔗 para abrir en Drive).
- Si está vacío → placeholder "Pegue la URL o ID de la carpeta de Drive".
- Si está poblado → lista los archivos con `nombre · tamaño · fecha · enlace "Abrir"`.
- Botón "📤 Subir archivo" con input file → sube a la carpeta configurada.
- Opcional: asociar archivo subido a un pendiente específico (columna `id_archivo_drive` en `siniestros_pendientes`).

### Setup de una sola vez en Google Cloud Console

El usuario final (Adriana o quien le administre) debe:

1. Crear proyecto en `console.cloud.google.com`.
2. Habilitar Drive API.
3. OAuth consent screen → `Internal` → scopes: `drive` (o `drive.readonly` para fase 1).
4. OAuth 2.0 Client ID → `Web application`:
   - JavaScript origins: `https://gestionipn.cl`
   - Authorized redirect URIs: `https://gestionipn.cl/bamboo/backend/google/callback.php`
5. Descargar `client_id` y `client_secret` → guardar en `.env` del servidor cPanel.

## Plan de rollout por fases

| Fase | Alcance | Riesgo | Esfuerzo relativo |
|---|---|---|---|
| **1** | OAuth + guardar token + listar archivos (solo lectura) de una carpeta configurada | Bajo | 1 bloque |
| **2** | Subir archivos nuevos desde el form hacia esa carpeta | Medio (manejo de multipart, tamaños, quotas) | 1 bloque |
| **3** | Crear subcarpetas automáticas por siniestro + asociar archivo a pendiente específico | Medio-alto (más UI) | 1-2 bloques |

La fase 1 es el MVP de validación: verifica que el OAuth funciona, que los scopes son los correctos y que a Adriana le resulta útil ver sus archivos sin salir de Bamboo.

## Riesgos y consideraciones

- **Refresh token**: si se revoca (ella desconecta la app o pasan 6 meses sin uso), hay que rehacer el flow. Necesita manejo de error "reautenticar".
- **Quotas de Drive API**: 10.000 requests/día por usuario. Para listar una carpeta y subir archivos puntuales está muy lejos del límite.
- **Tamaño de archivos**: la librería PHP soporta uploads resumibles para > 5 MB; conviene usarlos para no bloquear el form.
- **Archivo de configuración**: `client_id` y `client_secret` son **semi-secretos** — `client_secret` en Web app OAuth no es tan crítico como una API key, pero igual va en `.env`, no en el repo.
- **Borrado de archivos**: scope `drive` permite borrar. Conviene no exponer botón de borrar en v1 para evitar accidentes.

## Lo que necesitamos antes de implementar

1. Que Adriana (o quien administre su Workspace) cree el Google Cloud Project y comparta `client_id` + `client_secret`.
2. Confirmar la redirect URI exacta del servidor productivo y de QA.
3. Decisión sobre fase 1 solo lectura vs. arranque directo en fase 2 con lectura+escritura.

## Alternativas descartadas (registro)

- **Link manual a carpeta de Drive**: cero infra, pero sigue siendo manual (Adriana ya lo hace así). No agrega valor.
- **Supabase Storage**: técnicamente más simple, pero Adriana pierde "todo sigue en mi Drive de 2 TB", que fue el pedido explícito.
