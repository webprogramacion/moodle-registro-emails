# Design: add-email-log-plugin

## Context

Moodle 5.x incorpora el sistema de hooks (`\core\hook\*`) que reemplaza a los callbacks legacy. El core dispara hooks alrededor del envío de correo en `email_to_user()`, lo que permite a un plugin observar todos los emails salientes sin parchear el core. El plugin será nuevo, autocontenido, de tipo `local` (`local_emaillog`), sin dependencias externas. El directorio de trabajo actual está vacío: el repositorio contendrá directamente el código del plugin.

## Goals / Non-Goals

**Goals:**
- Registrar todos los emails enviados vía `email_to_user()` con metadatos completos y contenido.
- Informe de administración con listado, filtros y detalle.
- Retención configurable (30 días / 90 días / 6 meses / 1 año / de por vida) con purga diaria automática.
- Cumplir estándares de plugins Moodle: Privacy API, capacidades, cadenas de idioma (es + en), XMLDB, GPL.

**Non-Goals:**
- No registra mensajería interna de Moodle (Web/Push del message API) — solo el canal email.
- No reenvía ni reintenta emails fallidos; es solo auditoría.
- No captura emails enviados por librerías ajenas que no pasen por `email_to_user()`.
- Sin exportación CSV/Excel del listado en esta primera versión (posible mejora futura).

## Decisions

1. **Tipo de plugin: `local`** — Se necesita interceptar un evento global del sistema y añadir páginas de administración; un plugin `local` es el tipo canónico para esto. Alternativa descartada: `report` (serviría para el visor, pero no es apropiado como contenedor del hook y la tarea; un único plugin `local` con el informe dentro simplifica instalación y mantenimiento).

2. **Captura vía callback de la Message API + observer de `email_failed`** — *Decisión revisada durante la implementación (tarea 2.1).*

   **Hallazgo verificado contra el código fuente del core (ramas `MOODLE_500_STABLE`, `MOODLE_501_STABLE`, `MOODLE_502_STABLE`):** los hooks `\core\hook\email\before_email_send` / `after_email_send` **no existen en ninguna versión 5.x**. `lib/classes/hook/` solo contiene `access/`, `navigation/`, `output/`, `task/`, `after_config.php` y `di_configuration.php`. La función `email_to_user()` (en `lib/moodlelib.php` en 5.0, `public/lib/moodlelib.php` en 5.1+) **no despacha ningún hook**: su único punto de extensión es el evento `\core\event\email_failed`, disparado exclusivamente cuando `$mail->send()` devuelve `false`. Además `get_mailer()` instancia `moodle_phpmailer` con una ruta fija (`require_once($CFG->libdir.'/phpmailer/moodle_phpmailer.php')`) y no está en el contenedor de DI, por lo que el mailer no es sustituible.

   En consecuencia se usan los dos únicos puntos de extensión reales:

   - **`local_emaillog_pre_processor_message_send($processorname, $eventdata)`** en `lib.php`. El core lo invoca en `lib/classes/message/manager.php::call_processors()` mediante `get_plugins_with_function('pre_processor_message_send')`, una vez por cada procesador de salida. Filtrando `$processorname === 'email'` se captura todo lo que Moodle envía por correo a través de `message_send()` (foros, tareas, insignias, calendario, mensajes privados, etc.). El registro se inserta con estado "desconocido" porque el callback se ejecuta *antes* del envío.
   - **Observer de `\core\event\email_failed`** en `db/events.php`. Captura *cualquier* envío fallido, incluidos los de llamadas directas a `email_to_user()`. Si encuentra un registro reciente pendiente que corresponda al mismo destinatario y asunto, le cambia el estado a "fallido" y le añade el `errorinfo`; si no lo encuentra (envío directo), inserta un registro nuevo con estado "fallido".

   **Limitación conocida y documentada:** las llamadas directas a `email_to_user()` que **tienen éxito** no se registran, porque el core no expone nada en ese camino. Esto afecta principalmente al restablecimiento de contraseña, la confirmación de alta de usuario y el formulario de soporte. Sus **fallos** sí quedan registrados vía `email_failed`.

   Alternativas descartadas: parchear `email_to_user()` en el core (rompe el non-goal de no modificar el core; queda como parche opcional documentado si en el futuro se necesita cobertura total) y limitar el plugin a auditar solo fallos (reduciría demasiado el alcance del proposal).

3. **Tabla propia `local_emaillog`** — Campos: `id`, `useridfrom` (INT, NULL), `emailfrom` (CHAR 255), `useridto` (INT, NULL), `emailto` (CHAR 255), `subject` (TEXT), `bodytext` (TEXT grande), `bodyhtml` (TEXT grande, NULL), `replyto` (CHAR 255, NULL), `attachments` (TEXT, NULL — nombres separados por coma o JSON), `status` (TINYINT: 0 desconocido, 1 enviado, 2 fallido), `component` (CHAR 100, NULL), `errorinfo` (TEXT, NULL — añadido durante la implementación para guardar el `errorinfo` de `\core\event\email_failed`), `timecreated` (INT). Índices en `timecreated`, `useridto`, `useridfrom` y `emailto` para los filtros y la purga. Alternativa descartada: reutilizar los logstores de Moodle — no admiten cuerpos grandes ni consultas cómodas por email.

   El campo `subject` es de tipo `text`, por lo que la columna del listado **no es ordenable** (evita el problema de ordenar por columnas TEXT en algunos motores) y los filtros sobre él usan `sql_compare_text()` + `sql_like()`.

4. **Visor con `table_sql` + formulario de filtros con `moodleform`** — Página `index.php` (listado con paginación/orden servidor) y `view.php` (detalle). Se registra como `admin_externalpage` bajo la categoría de informes para que aparezca en Administración del sitio → Informes. El HTML del cuerpo se muestra con `format_text(..., FORMAT_HTML)` para que pase por el purificador. Alternativa descartada: report builder API — más potente pero innecesariamente complejo para v1.

5. **Retención con `admin_setting_configselect` + scheduled task** — Ajuste `local_emaillog/retention` con valores en segundos (30 días, 90 días, 6 meses, 1 año) y `0` = de por vida. Task `\local_emaillog\task\cleanup` en `db/tasks.php`, diaria a las 03:00 por defecto, que hace `DELETE FROM {local_emaillog} WHERE timecreated < :cutoff` (solo si retención > 0) usando `$DB->delete_records_select`. El administrador puede reprogramarla con la UI estándar de tareas.

6. **Privacy API completa** — `classes/privacy/provider.php` implementa `metadata\provider`, `request\plugin\provider` y `core_userlist_provider`, exportando/borrando registros por `useridfrom`/`useridto`. Obligatorio para publicar en el directorio de plugins y para RGPD.

7. **Estructura de archivos estándar:**
   ```
   local/emaillog/
   ├── version.php
   ├── settings.php
   ├── lib.php              (callback pre_processor_message_send)
   ├── index.php            (listado)
   ├── view.php             (detalle)
   ├── db/
   │   ├── install.xml
   │   ├── events.php       (observer de \core\event\email_failed)
   │   ├── tasks.php
   │   └── access.php
   ├── classes/
   │   ├── observer.php
   │   ├── local/logger.php
   │   ├── table/emaillog_table.php
   │   ├── form/filter_form.php
   │   ├── task/cleanup.php
   │   └── privacy/provider.php
   └── lang/
       ├── en/local_emaillog.php
       └── es/local_emaillog.php
   ```

   `db/hooks.php` y `classes/hook_callbacks.php` desaparecen del diseño: no hay hooks de email a los que suscribirse (ver decisión 2).

## Risks / Trade-offs

- [No existen hooks de email en el core 5.x] → Verificado y resuelto en la decisión 2: callback `pre_processor_message_send` + observer de `email_failed`. Coste: los envíos directos con éxito vía `email_to_user()` (restablecer contraseña, confirmación de alta) no se registran.
- [El callback `pre_processor_message_send` es un callback legacy de `lib.php`] → Sigue vigente en 5.2 (`get_plugins_with_function('pre_processor_message_send')` se invoca sin el flag `$migratedtohook`, es decir, no está marcado como deprecado). Si en el futuro se migra a hook, habrá que cambiar `lib.php` por `db/hooks.php`.
- [El estado por defecto es "desconocido"] → El callback se ejecuta antes del envío y el core no notifica el éxito. Solo el fallo es observable, así que "desconocido" significa en la práctica "no se detectó ningún fallo".
- [Los mensajes de conversaciones de grupo se envían en diferido] → El procesador `message_email` acumula los mensajes de grupo en `message_email_messages` y los envía después con una tarea; el registro se crea en el momento de la llamada, no en el del envío real.
- [La tabla puede crecer mucho en sitios con alto volumen de correo] → Índice en `timecreated` + purga diaria; el default de 6 meses evita crecimiento indefinido salvo elección explícita de "de por vida".
- [El contenido de los emails contiene datos personales sensibles] → Capacidad restringida a managers, Privacy API implementada, y la retención configurable permite políticas de minimización.
- [Emails masivos (foros con miles de suscriptores) generan miles de inserciones] → Una inserción simple por email es barata; si fuera problema, se podría batchear en una mejora futura.
- [Cuerpos HTML almacenados podrían usarse como vector XSS en el visor] → Render siempre a través del purificador de Moodle (`format_text`), nunca `echo` directo.

## Migration Plan

1. Instalar el plugin copiando `local/emaillog/` en el directorio `local/` del sitio (en Moodle 5.1+ el core vive bajo `public/`, por lo que la ruta es `public/local/emaillog/`) y visitando la página de notificaciones (crea la tabla vía `install.xml`).
2. Configurar la retención deseada (default 6 meses).
3. Rollback: desinstalar el plugin desde la administración; Moodle elimina la tabla y los ajustes. No toca datos del core.

## Open Questions

- ¿Debe registrarse también el email de "soporte" saliente vía `email_to_user` con usuario ficticio (p. ej. invitados)? Decisión tomada: sí, guardando userid nulo/0 (cubierto en specs). Nota: los usuarios ficticios del core (`core_user::get_noreply_user()`, `get_support_user()`) tienen id negativo (-10, -20), que se normaliza a `NULL`.
- ~~Confirmar durante la implementación el nombre/firma exacta de los hooks de email en la rama de Moodle 5.x objetivo.~~ **Resuelto:** no existen hooks de email en 5.0/5.1/5.2 (ver decisión 2).
- Versión mínima soportada: Moodle 5.0 (`$plugin->requires = 2025041400`), verificado compatible con 5.0, 5.1 y 5.2.
