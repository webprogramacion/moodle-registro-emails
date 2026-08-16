# Registro de emails para Moodle (`local_emaillog`)

Plugin local para Moodle 5.x que registra los emails que envía la plataforma y permite al
administrador consultarlos, para que "no me llegó el correo" se pueda investigar de verdad.
Los registros antiguos se purgan solos según un periodo de retención configurable.

## Compatibilidad y versión

| Dato | Valor |
| --- | --- |
| Componente | `local_emaillog` |
| Tipo de plugin | Plugin local (`local`) |
| Release | `0.2.0` |
| Versión (`$plugin->version`) | `2026081600` |
| Requiere (`$plugin->requires`) | `2025041400` (Moodle 5.0) |
| Madurez | `MATURITY_ALPHA` |
| Probado en | Moodle 5.0, 5.1 y 5.2 |
| Licencia | GNU GPL v3 o posterior |
| Idiomas | Inglés y español (58 cadenas cada uno) |

Estos valores salen de [local/emaillog/version.php](local/emaillog/version.php); si actualizas
el plugin, actualiza también esta tabla.

## Qué hace

- **Captura de emails salientes.** Se engancha al callback `pre_processor_message_send` de la
  Message API (en [lib.php](local/emaillog/lib.php)) y al evento `\core\event\email_failed`
  (en [classes/observer.php](local/emaillog/classes/observer.php)). Cada email observado se
  guarda en la tabla `local_emaillog` con remitente, destinatario, fecha, asunto, cuerpo en
  texto y en HTML, Reply-To, adjuntos, estado, error del mailer y componente de origen.
- **Visor con filtros.** En **Administración del sitio → Informes → Registro de emails**:
  listado paginado con filtros por rango de fechas, remitente, destinatario, asunto y estado,
  y una página de detalle por email. El HTML almacenado se muestra siempre a través del
  purificador de Moodle, nunca en crudo.
- **Retención y purga automática.** Ajuste **Mantener registros** con valores de 30 días,
  90 días, 6 meses (por defecto), 1 año o "De por vida". La tarea programada
  `\local_emaillog\task\cleanup` corre a diario a las 03:00 y borra lo más antiguo que el
  periodo configurado; "De por vida" desactiva el borrado.
- **Control de acceso.** Capacidad propia `local/emaillog:view` (`RISK_PERSONAL`, contexto de
  sistema), concedida por defecto solo al arquetipo *manager*. Ver el registro expone el
  contenido íntegro de los correos.
- **Privacy API.** El plugin declara sus datos personales y atiende exportación y borrado.
  Ante una solicitud de borrado, se eliminan los registros dirigidos al usuario y se conservan
  los que solo envió, anonimizando su identidad, para no romper la auditoría del destinatario.

## Limitaciones importantes (léelo antes de instalar)

Moodle 5.x **no ofrece ningún punto de extensión en el camino de éxito de `email_to_user()`**:
no hay hook de email en `lib/classes/hook/`, `email_to_user()` no despacha nada y `get_mailer()`
instancia `moodle_phpmailer` desde una ruta fija. Las únicas señales que da el core son el
callback `pre_processor_message_send` de la mensajería y el evento `\core\event\email_failed`,
y el plugin usa ambas. Consecuencias:

- **Sí se registra** todo lo que pasa por la Message API con el procesador `email`:
  notificaciones de foro, feedback de tareas, insignias, eventos de calendario, mensajes
  privados y, en general, cualquier cosa que pase por `message_send()`.
- **Sí se registra** todo envío **fallido**, incluidos los fallos de llamadas directas a
  `email_to_user()`, junto con el error que reporta el mailer.
- **No se registran** las llamadas directas a `email_to_user()` que **tienen éxito**. En la
  práctica: restablecimiento de contraseña, confirmación de alta y formulario de soporte. Solo
  aparecen en el registro si fallan.
- El estado **"Desconocido"** significa *no se detectó ningún fallo*, no "entregado": el core
  nunca informa de un envío correcto.
- Los mensajes de conversaciones de grupo los agrupa el procesador `message_email` y los envía
  después una tarea programada; el registro se crea cuando el mensaje se encola, no cuando el
  email sale realmente.
- El registro nunca puede bloquear un envío: toda escritura en base de datos va protegida y un
  fallo se reporta con `debugging()` y nada más.

El detalle técnico completo está en [local/emaillog/README.md](local/emaillog/README.md).

## Estructura del repositorio

```
.
├── local/emaillog/     <- EL PLUGIN. Es lo único que se distribuye e instala.
├── openspec/           <- Artefactos de planificación OpenSpec (propuestas, specs, tareas).
├── .claude/ .agent/ .codex/   <- Configuración de agentes y skills.
└── README.md           <- Este fichero.
```

Solo `local/emaillog/` forma parte del plugin. Todo lo demás es material de desarrollo y
**no debe acabar dentro del ZIP** de instalación.

## Empaquetado: contenido exacto del ZIP

### Estructura obligatoria

Moodle valida el ZIP antes de instalarlo (`\core\update\validator`) y exige que contenga
**exactamente un directorio en su raíz**, con `version.php` dentro, y cuyo nombre coincida con
el del plugin derivado de `$plugin->component` quitando el prefijo del tipo:

```
local_emaillog  ->  el directorio raíz del ZIP debe llamarse  emaillog
```

El nivel `local/` que existe en este repositorio refleja la ruta de destino dentro de Moodle,
así que **no debe ir dentro del ZIP**. Estructura correcta e incorrecta:

```
✅ correcto            ❌ incorrecto           ❌ incorrecto
mi.zip                 mi.zip                  mi.zip
└── emaillog/          └── local/              ├── emaillog/
    ├── version.php        └── emaillog/       └── __MACOSX/
    └── ...                    └── ...
```

### Los 20 ficheros que debe contener

```
emaillog/
├── README.md                              Documentación funcional del plugin.
├── version.php                            Componente, versión, requisito de Moodle, madurez y release.
├── lib.php                                Callback local_emaillog_pre_processor_message_send(): captura los
│                                          emails que salen por el procesador «email» de la mensajería.
├── settings.php                           Ajuste de retención en Plugins locales y alta de la página de
│                                          informe bajo Administración del sitio → Informes.
├── index.php                              Página de listado: filtros, paginación y tabla de emails.
├── view.php                               Página de detalle de un email, con el cuerpo HTML purificado.
├── classes/
│   ├── observer.php                       Observer de \core\event\email_failed: marca como fallido el
│   │                                      registro pendiente o crea uno nuevo si el envío fue directo.
│   ├── form/
│   │   └── filter_form.php                Formulario de filtros (fechas, remitente, destinatario, asunto,
│   │                                      estado) y paso de esos filtros a la URL.
│   ├── local/
│   │   ├── logger.php                     Construcción y guardado de los registros; constantes de estado
│   │   │                                  (0 desconocido, 1 enviado, 2 fallido).
│   │   ├── detail.php                     Formateo de las filas de la página de detalle.
│   │   └── userlink.php                   Decide si el nombre de un usuario se enlaza y a dónde: al
│   │                                      formulario de edición de perfil cuando hay permiso para
│   │                                      editar usuarios, en una pestaña nueva.
│   ├── privacy/
│   │   └── provider.php                   Privacy API: metadatos, exportación y borrado de datos personales.
│   ├── table/
│   │   └── emaillog_table.php             Tabla paginada y ordenable (table_sql) del listado.
│   └── task/
│       └── cleanup.php                    Tarea programada que borra los registros más antiguos que el
│                                          periodo de retención.
├── db/
│   ├── access.php                         Capacidad local/emaillog:view (RISK_PERSONAL, solo manager).
│   ├── events.php                         Registro del observer de \core\event\email_failed.
│   ├── install.xml                        Tabla local_emaillog: campos, clave primaria e índices
│   │                                      (timecreated, useridfrom, useridto, emailto).
│   └── tasks.php                          Programación de la tarea cleanup: diaria a las 03:00.
└── lang/
    ├── en/local_emaillog.php              Cadenas en inglés (58).
    └── es/local_emaillog.php              Cadenas en español (58).
```

Para regenerar o verificar este listado:

```bash
find local/emaillog -type f | sort
```

### Qué NO debe incluirse

- `.git/` y cualquier metadato de control de versiones.
- `openspec/`, `.claude/`, `.agent/`, `.codex/` — planificación y configuración de agentes.
- `.DS_Store` y el directorio `__MACOSX/`.

⚠️ **No comprimas la carpeta desde el Finder de macOS** ("Comprimir emaillog"): añade
`__MACOSX/` y ficheros `.DS_Store`, y el ZIP resultante deja de tener un único directorio raíz.
Usa el comando de abajo.

### Generar el ZIP

Desde la raíz del repositorio:

```bash
cd local && zip -r ../local_emaillog_2026081600.zip emaillog -x '*.DS_Store' -x '__MACOSX/*'
```

Ejecutar `zip` desde `local/` con la ruta relativa `emaillog` produce directamente la
estructura que Moodle exige, sin copiar nada a un directorio temporal.

El nombre del fichero ZIP es libre —Moodle no lo valida—, pero incluir el número de versión
(`2026081600`) evita confundir descargas. Lo que **sí** es obligatorio es el nombre del
directorio interno: `emaillog`.

### Verificar el ZIP antes de subirlo

```bash
unzip -l local_emaillog_2026081600.zip
```

En la salida hay que comprobar tres cosas:

1. Todas las rutas empiezan por `emaillog/` — un único directorio raíz.
2. Aparece `emaillog/version.php`.
3. No aparece `__MACOSX/` ni ningún `.DS_Store`.

## Instalación

### Opción A — Subir el ZIP desde Moodle

1. **Administración del sitio → Extensiones → Instalar módulos externos**.
2. Arrastra el ZIP y selecciona el tipo de plugin **Plugin local (local)**.
3. Continúa con la validación y confirma la instalación.

Requiere que el directorio de destino tenga permisos de escritura para el usuario del servidor
web. Si Moodle avisa de que no puede escribir, usa la opción B.

### Opción B — Descomprimir a mano en el servidor

La ruta de destino depende de la versión de Moodle:

```
# Moodle 5.0
<moodleroot>/local/emaillog

# Moodle 5.1 y 5.2, donde el core vive bajo public/
<moodleroot>/public/local/emaillog
```

Descomprime el ZIP dentro del directorio `local/` correspondiente, de forma que `version.php`
quede en `.../local/emaillog/version.php`.

### Después de instalar (ambas opciones)

1. Visita la **página de notificaciones** de administración para que se ejecute la instalación
   de la base de datos (crea la tabla `local_emaillog`).
2. Ajusta la retención en **Administración del sitio → Extensiones → Plugins locales →
   Registro de emails** (por defecto, 6 meses).
3. Consulta el registro en **Administración del sitio → Informes → Registro de emails**.
4. Opcionalmente, reprograma la tarea de limpieza en **Administración del sitio → Servidor →
   Tareas programadas**.

Para lanzar la purga a mano:

```bash
php admin/cli/scheduled_task.php --execute='\local_emaillog\task\cleanup'
```

## Privacidad

El contenido de los emails puede incluir datos personales, así que conviene un periodo de
retención corto. El plugin implementa la Privacy API de Moodle: ante una solicitud de borrado,
elimina los registros dirigidos al usuario y conserva, con la identidad del remitente
eliminada, los que solo envió, para preservar la auditoría del destinatario.

## Licencia

GNU GPL v3 o posterior.
