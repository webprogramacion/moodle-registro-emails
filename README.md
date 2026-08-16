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
| Licencia | [GNU GPL v3 o posterior](LICENSE) |
| Idiomas | Inglés y español |

Estos valores salen de [version.php](version.php). El historial está en
[CHANGELOG.md](CHANGELOG.md).

## Instalación

### Opción A — Descargar el ZIP de la release

1. Descarga el ZIP de la [última release](../../releases/latest).
2. **Administración del sitio → Extensiones → Instalar módulos externos**.
3. Arrastra el ZIP y elige el tipo de plugin **Plugin local (local)**.

Requiere que el directorio de destino tenga permisos de escritura para el usuario del
servidor web. Si Moodle avisa de que no puede escribir, usa la opción B o la C.

### Opción B — Clonar el repositorio en su sitio

La ruta de destino depende de la versión de Moodle, porque a partir de 5.1 el core vive
bajo `public/`:

```bash
# Moodle 5.0
git clone https://github.com/webprogramacion/moodle-registro-emails.git <moodleroot>/local/emaillog

# Moodle 5.1 y 5.2
git clone https://github.com/webprogramacion/moodle-registro-emails.git <moodleroot>/public/local/emaillog
```

Actualizar es después un `git pull` en ese directorio.

### Opción C — Descomprimir a mano

Descomprime el ZIP dentro del directorio `local/` que corresponda, de forma que
`version.php` quede en `.../local/emaillog/version.php`.

### Después de instalar (las tres opciones)

1. Visita la **página de notificaciones** de administración para que se ejecute la
   instalación de la base de datos (crea la tabla `local_emaillog`).
2. Ajusta la retención en **Administración del sitio → Extensiones → Plugins locales →
   Registro de emails** (por defecto, 6 meses).
3. Consulta el registro en **Administración del sitio → Informes → Registro de emails**.
4. Opcionalmente, reprograma la tarea de limpieza en **Administración del sitio → Servidor
   → Tareas programadas**.

## Qué hace

- **Captura de emails salientes.** Se engancha al callback `pre_processor_message_send` de
  la Message API (en [lib.php](lib.php)) y al evento `\core\event\email_failed` (en
  [classes/observer.php](classes/observer.php)). Cada email observado se guarda en la tabla
  `local_emaillog` con remitente, destinatario, fecha, asunto, cuerpo en texto y en HTML,
  Reply-To, adjuntos, estado, error del mailer y componente de origen.
- **Visor con filtros.** En **Administración del sitio → Informes → Registro de emails**:
  listado paginado con filtros por rango de fechas, remitente, destinatario, asunto y
  estado, y una página de detalle por email. El HTML almacenado se muestra siempre a través
  del purificador de Moodle, nunca en crudo.
- **Corrección rápida de direcciones.** Los nombres del listado y del detalle enlazan al
  formulario de edición del perfil en una pestaña nueva, para arreglar un email mal escrito
  sin perder los filtros. El enlace solo se muestra a quien tiene `moodle/user:update`.
- **Retención y purga automática.** Ajuste **Mantener registros** con valores de 30 días,
  90 días, 6 meses (por defecto), 1 año o "De por vida". La tarea programada
  `\local_emaillog\task\cleanup` corre a diario a las 03:00 y borra lo más antiguo que el
  periodo configurado; "De por vida" desactiva el borrado.
- **Control de acceso.** Capacidad propia `local/emaillog:view` (`RISK_PERSONAL`, contexto
  de sistema), concedida por defecto solo al arquetipo *manager*. Ver el registro expone el
  contenido íntegro de los correos.
- **Privacy API.** El plugin declara sus datos personales y atiende exportación y borrado.
  Ante una solicitud de borrado, se eliminan los registros dirigidos al usuario y se
  conservan los que solo envió, anonimizando su identidad, para no romper la auditoría del
  destinatario.

## Limitaciones importantes (léelo antes de instalar)

Moodle 5.x **no ofrece ningún punto de extensión en el camino de éxito de
`email_to_user()`**: no hay hook de email en `lib/classes/hook/`, `email_to_user()` no
despacha nada y `get_mailer()` instancia `moodle_phpmailer` desde una ruta fija. Las únicas
señales que da el core son el callback `pre_processor_message_send` de la mensajería y el
evento `\core\event\email_failed`, y el plugin usa ambas. Consecuencias:

- **Sí se registra** todo lo que pasa por la Message API con el procesador `email`:
  notificaciones de foro, feedback de tareas, insignias, eventos de calendario, mensajes
  privados y, en general, cualquier cosa que pase por `message_send()`.
- **Sí se registra** todo envío **fallido**, incluidos los fallos de llamadas directas a
  `email_to_user()`, junto con el error que reporta el mailer.
- **No se registran** las llamadas directas a `email_to_user()` que **tienen éxito**. En la
  práctica: restablecimiento de contraseña, confirmación de alta y formulario de soporte.
  Solo aparecen en el registro si fallan.
- El estado **"Desconocido"** significa *no se detectó ningún fallo*, no "entregado": el
  core nunca informa de un envío correcto.
- Los mensajes de conversaciones de grupo los agrupa el procesador `message_email` y los
  envía después una tarea programada; el registro se crea cuando el mensaje se encola, no
  cuando el email sale realmente.
- El registro nunca puede bloquear un envío: toda escritura en base de datos va protegida y
  un fallo se reporta con `debugging()` y nada más.

## Privacidad y retención

El contenido de los emails puede incluir datos personales, así que conviene un periodo de
retención corto. Para lanzar la purga a mano:

```bash
php admin/cli/scheduled_task.php --execute='\local_emaillog\task\cleanup'
```

## Estructura del repositorio

El plugin ocupa la raíz del repositorio, siguiendo la convención de los plugins de Moodle:
por eso se puede clonar directamente en `local/emaillog`.

```
.
├── version.php          Componente, versión, requisito de Moodle, madurez y release.
├── lib.php              Callback pre_processor_message_send(): captura los emails que
│                        salen por el procesador «email» de la mensajería.
├── settings.php         Ajuste de retención y alta de la página de informe.
├── index.php            Página de listado: filtros, paginación y tabla de emails.
├── view.php             Página de detalle de un email, con el cuerpo HTML purificado.
├── classes/
│   ├── observer.php             Observer de \core\event\email_failed.
│   ├── form/filter_form.php     Formulario de filtros y paso de filtros a la URL.
│   ├── local/logger.php         Construcción y guardado de los registros.
│   ├── local/detail.php         Formateo de las filas de la página de detalle.
│   ├── local/userlink.php       Decide si el nombre de un usuario se enlaza y a dónde.
│   ├── privacy/provider.php     Privacy API.
│   ├── table/emaillog_table.php Tabla paginada del listado.
│   └── task/cleanup.php         Tarea programada de purga.
├── db/
│   ├── access.php       Capacidad local/emaillog:view.
│   ├── events.php       Registro del observer.
│   ├── install.xml      Tabla local_emaillog: campos, clave primaria e índices.
│   └── tasks.php        Programación de la tarea cleanup: diaria a las 03:00.
├── lang/en/, lang/es/   Cadenas de idioma.
│
│  — Lo siguiente NO viaja dentro del ZIP (ver .gitattributes) —
├── .github/workflows/   Automatización de releases.
├── openspec/            Artefactos de planificación (propuestas, specs, tareas).
└── .claude/ .agent/ .codex/   Configuración de agentes.
```

## Publicar una versión

El ZIP de instalación **no se construye a mano**: lo genera
[.github/workflows/release.yml](.github/workflows/release.yml) al empujar un tag de versión.

```bash
# 1. Actualiza $plugin->version y $plugin->release en version.php, y el CHANGELOG.
# 2. Commitea y empuja.
# 3. Etiqueta con la release que declara version.php, precedida de "v".
git tag v0.2.0
git push origin v0.2.0
```

El workflow entonces:

1. **Comprueba que el tag coincide** con `$plugin->release`. Si no cuadran, falla y no
   publica nada — así no puede salir una release mal etiquetada.
2. **Construye el ZIP** con `git archive --prefix=emaillog/`, que produce exactamente lo que
   exige el validador de Moodle: un único directorio raíz llamado `emaillog/`. Las rutas
   marcadas con `export-ignore` en [.gitattributes](.gitattributes) quedan fuera, y al
   construirse desde el tag el ZIP es reproducible y nunca arrastra ficheros sin versionar
   como `.DS_Store`.
3. **Verifica la estructura**: un solo directorio raíz, presencia de `emaillog/version.php`,
   ausencia de material de desarrollo y de `__MACOSX/`.
4. **Publica la release** con el ZIP adjunto. Si la madurez es `MATURITY_ALPHA` o
   `MATURITY_BETA`, la marca como *prerelease*.

Para generar el mismo ZIP en local, sin pasar por GitHub:

```bash
git archive --format=zip --prefix=emaillog/ -o local_emaillog_2026081600.zip HEAD
unzip -l local_emaillog_2026081600.zip
```

Moodle no valida el nombre del fichero ZIP; lo que **sí** es obligatorio es que el
directorio interno se llame `emaillog`, sin el nivel `local/`.

## Licencia

GNU GPL v3 o posterior. Ver [LICENSE](LICENSE).
