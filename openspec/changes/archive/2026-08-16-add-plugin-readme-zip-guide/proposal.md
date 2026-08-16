# Proposal: add-plugin-readme-zip-guide

## Why

El repositorio no tiene `README.md` en la raíz: quien lo abre (en GitHub o en local) no ve qué es el proyecto ni cómo instalar el plugin. Además, la única documentación existente (`local/emaillog/README.md`) explica cómo copiar la carpeta al servidor, pero **no** describe cómo empaquetar el plugin en un ZIP ni qué debe contener exactamente ese ZIP, que es la vía normal de instalación en Moodle (**Administración del sitio → Extensiones → Instalar módulos externos**). Un ZIP mal armado (carpeta raíz con nombre distinto de `emaillog`, doble carpeta contenedora, ficheros de desarrollo incluidos) es rechazado por el validador de Moodle o instala el plugin con un componente incorrecto.

## What Changes

- Nuevo `README.md` en la raíz del repositorio, en español, como puerta de entrada del proyecto. Contendrá:
  - Qué es el plugin, versión y compatibilidad (`local_emaillog`, release 0.1.0, `requires = 2025041400`, probado en Moodle 5.0/5.1/5.2), y madurez ALPHA.
  - Funcionalidades relevantes: captura de emails, visor filtrable en Informes, política de retención con purga programada, capacidad `local/emaillog:view`, Privacy API.
  - La limitación esencial: las llamadas directas a `email_to_user()` que tienen éxito no se registran; sus fallos sí.
  - Mapa del repositorio (qué hay en `local/`, `openspec/`) para distinguir lo que es el plugin de lo que es planificación.
- **Sección de empaquetado ZIP** (el foco de este cambio), que especificará:
  - El nombre del ZIP recomendado (`local_emaillog_moodle50_2026072500.zip`) y el nombre obligatorio de la carpeta raíz dentro del ZIP: `emaillog/` (debe coincidir con el nombre del plugin sin el prefijo `local_`).
  - El **listado exacto y completo de los 19 ficheros** que debe contener el ZIP, con una línea por fichero explicando su función.
  - Qué **no** debe incluirse (`.git/`, `openspec/`, `.claude/`, `.agent/`, `.codex/`, ficheros de macOS `.DS_Store` y `__MACOSX/`).
  - El comando reproducible para generar el ZIP desde la raíz del repositorio y el comando para verificar su contenido antes de subirlo.
  - Los dos caminos de instalación (subida del ZIP desde la interfaz y descompresión manual en `local/` o `public/local/` según la versión de Moodle) y los pasos posteriores (página de notificaciones, ajuste de retención).
- Enlace desde el `README.md` raíz al `README.md` del plugin, que se mantiene sin cambios como documentación funcional detallada que viaja dentro del ZIP.

## Capabilities

### New Capabilities
- `plugin-packaging-docs`: documentación en la raíz del repositorio que describe el plugin y especifica de forma verificable el contenido, la estructura y el procedimiento de generación del ZIP de instalación.

### Modified Capabilities

<!-- Ninguna: las specs existentes (email-capture, email-log-viewer, log-retention) describen comportamiento del plugin, que no cambia. -->

## Impact

- Solo documentación: **no se modifica ningún fichero PHP del plugin**, ni el esquema de base de datos, ni el comportamiento en ejecución.
- Fichero nuevo: `README.md` en la raíz del repositorio.
- `local/emaillog/README.md` se conserva tal cual (es el README que se distribuye dentro del ZIP).
- Dependencia de mantenimiento: el listado de ficheros del ZIP y el número de versión del README quedan acoplados al contenido real de `local/emaillog/` y a `version.php`; si se añade o elimina un fichero del plugin habrá que actualizar el README.
- Sin impacto en instalaciones existentes de Moodle.
