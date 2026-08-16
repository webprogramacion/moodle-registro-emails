## Context

El repositorio contiene un único plugin de Moodle, `local_emaillog`, bajo `local/emaillog/` (19 ficheros, ~116 KB), más el andamiaje de planificación de OpenSpec (`openspec/`) y configuración de agentes (`.claude/`, `.agent/`, `.codex/`). La raíz del repositorio no tiene `README.md`.

La documentación existente, `local/emaillog/README.md`, está en inglés y cubre bien el comportamiento del plugin (qué captura, qué no, retención, privacidad), pero su sección de instalación se limita a "copia la carpeta en `local/`". No dice nada del ZIP, que es la vía habitual para un administrador que no tiene acceso al sistema de ficheros del servidor.

Restricciones que condicionan el diseño:

- El validador de Moodle (`\core\update\validator`) exige que el ZIP tenga **exactamente un directorio en su raíz**, que ese directorio contenga `version.php`, y que el nombre del directorio coincida con el nombre del plugin derivado de `$plugin->component` (`local_emaillog` → `emaillog`). Un ZIP creado comprimiendo la carpeta desde Finder de macOS añade `__MACOSX/` y `.DS_Store`, lo que rompe la regla del directorio único o genera avisos.
- La ubicación de destino depende de la versión: Moodle 5.0 usa `<moodleroot>/local/`, mientras que 5.1 y 5.2 mueven el core bajo `public/`, luego `<moodleroot>/public/local/`. Esto ya está documentado en el README del plugin y debe repetirse en el de la raíz porque es la fuente de error más común al descomprimir a mano.
- El repositorio está estructurado con el prefijo `local/` para reflejar la ruta de destino en Moodle; por tanto el ZIP **no** debe contener ese nivel `local/`, solo `emaillog/`.

## Goals / Non-Goals

**Goals:**

- Dar a quien abre el repositorio una visión completa del plugin en una sola página: qué hace, en qué versiones funciona, qué captura y qué no, y dónde se configura.
- Especificar el ZIP de instalación de forma **verificable**: nombre del fichero, estructura de directorios, listado exacto de los 19 ficheros con su función, exclusiones, y comando reproducible de generación y de verificación.
- Documentar los dos caminos de instalación (subida del ZIP e instalación manual) y los pasos posteriores.
- Escribir en español, alineado con el idioma que el usuario usa en este repositorio y con los artefactos OpenSpec existentes.

**Non-Goals:**

- No se modifica ningún fichero PHP del plugin ni su comportamiento.
- No se reescribe ni se traduce `local/emaillog/README.md`; se enlaza desde el nuevo README y se mantiene como documentación funcional que viaja dentro del ZIP.
- No se añade un script de build (`build.sh`, Makefile) ni automatización de release en CI: el comando de una línea documentado en el README es suficiente para el tamaño actual del proyecto.
- No se publica el plugin en el directorio de plugins de Moodle ni se documenta ese proceso.

## Decisions

### 1. README nuevo en la raíz, en lugar de ampliar el del plugin

El `README.md` de la raíz es lo que se ve en GitHub y lo que lee quien clona el repositorio; el del plugin es lo que se ve dentro del ZIP ya instalado. Las instrucciones de empaquetado son una operación **sobre** el repositorio (excluir `openspec/`, ejecutar `zip` desde `local/`), no sobre el plugin instalado, así que su sitio natural es la raíz.

*Alternativa descartada:* añadir la sección de ZIP a `local/emaillog/README.md`. Se descartó porque ese fichero se distribuye dentro del propio ZIP, donde instrucciones para construir el ZIP son ruido, y porque describiría rutas (`openspec/`, `.claude/`) que no existen en la copia instalada.

### 2. Duplicar (en resumen) el contenido funcional en lugar de solo enlazar

El README de la raíz repite en forma condensada lo esencial —compatibilidad, funcionalidades, la limitación de `email_to_user()`, retención, privacidad— y enlaza al README del plugin para el detalle. Un README de raíz que solo dijera "ver `local/emaillog/README.md`" no cumple lo que se pide ("que yo vea las cosas relevantes del plugin").

*Coste asumido:* dos ficheros que pueden divergir. Se mitiga manteniendo el resumen corto y sin cifras que no estén también en `version.php`.

### 3. Listado explícito de los 19 ficheros, no un patrón glob

La sección del ZIP enumera cada fichero con una línea de explicación, en vez de decir "todo el contenido de `local/emaillog/`". Es lo que permite a quien recibe el ZIP verificar que está completo antes de subirlo, y es lo que el usuario pidió explícitamente. El listado se genera a partir del contenido real del directorio, no de memoria.

*Riesgo aceptado:* el listado envejece si se añaden ficheros; ver Risks.

### 4. Comando de empaquetado con `zip -r ... -x` desde `local/`

```
cd local && zip -r ../local_emaillog_2026072500.zip emaillog -x '*.DS_Store' -x '__MACOSX/*'
```

Ejecutar `zip` desde `local/` con la ruta relativa `emaillog` produce directamente la estructura exigida por Moodle (un único directorio raíz `emaillog/`) sin necesidad de copiar a un directorio temporal. Se documenta `unzip -l` como paso de verificación previo a la subida.

*Alternativas descartadas:* `git archive` (incluiría el prefijo `local/` y requeriría reescritura de rutas); comprimir desde el Finder de macOS (añade `__MACOSX/`, se documenta explícitamente como error a evitar).

### 5. Nombre del ZIP con versión, no genérico

`local_emaillog_2026072500.zip`, usando `$plugin->version`. Moodle ignora el nombre del fichero ZIP, pero incluir la versión evita que convivan varios ZIP indistinguibles en la carpeta de descargas del administrador. Se documenta que el nombre del fichero es libre y que lo que **sí** es obligatorio es el nombre del directorio interno.

### 6. Documentar la limitación de captura en la parte alta del README

La limitación —los `email_to_user()` que tienen éxito no se registran— es la información que más probablemente genere un malentendido tras instalar el plugin ("lo he instalado y no veo el email de recuperación de contraseña"). Va en una sección propia y destacada, no enterrada al final.

## Risks / Trade-offs

- **El listado de 19 ficheros queda desactualizado si se añade o borra un fichero del plugin** → El README incluye el comando `find local/emaillog -type f | sort` como forma de regenerar/verificar el listado, y la tarea de implementación exige contrastar el listado con la salida real de ese comando en el momento de escribirlo.
- **Duplicidad entre el README de la raíz y el del plugin** → El resumen de la raíz se limita a los puntos que un evaluador necesita antes de instalar; el detalle (motivación técnica de por qué no hay hook de email en Moodle 5.x, comportamiento de las conversaciones de grupo, semántica de "Desconocido") vive solo en el README del plugin, enlazado.
- **Los números de versión y compatibilidad se copian de `version.php`** → Si se publica una versión nueva hay que tocar el README. Se acepta: la alternativa (no mencionar versión) hace el README menos útil.
- **La instalación desde ZIP requiere que el directorio de destino tenga permisos de escritura para el servidor web**; en instalaciones endurecidas la subida por interfaz falla con un mensaje poco claro → El README documenta la instalación manual como camino alternativo y menciona la comprobación de permisos.
