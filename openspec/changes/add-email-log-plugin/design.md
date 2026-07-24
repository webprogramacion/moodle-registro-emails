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

2. **Captura vía hooks de Moodle 5.x** — Se usan los hooks del core `\core\hook\email\before_email_send` y `\core\hook\email\after_email_send` (nombres a verificar contra la versión exacta del core en la implementación) registrados en `db/hooks.php`. `before` captura los datos del mensaje (PHPMailer ya montado: from, to, subject, body, altbody, reply-to, attachments); `after` (si está disponible) actualiza el estado enviado/fallido. Si el hook posterior no existe en la versión objetivo, el estado se guarda como "desconocido" y se documenta. Alternativa descartada: parchear `email_to_user()` o usar un `$CFG->noemailever` + wrapper — invasivo y frágil ante actualizaciones.

3. **Tabla propia `local_emaillog`** — Campos: `id`, `useridfrom` (INT, NULL), `emailfrom` (CHAR 255), `useridto` (INT, NULL), `emailto` (CHAR 255), `subject` (TEXT), `bodytext` (TEXT grande), `bodyhtml` (TEXT grande, NULL), `replyto` (CHAR 255, NULL), `attachments` (TEXT, NULL — nombres separados por coma o JSON), `status` (TINYINT: 0 desconocido, 1 enviado, 2 fallido), `component` (CHAR 100, NULL), `timecreated` (INT). Índices en `timecreated`, `useridto`, `useridfrom` y `emailto` para los filtros y la purga. Alternativa descartada: reutilizar los logstores de Moodle — no admiten cuerpos grandes ni consultas cómodas por email.

4. **Visor con `table_sql` + formulario de filtros con `moodleform`** — Página `index.php` (listado con paginación/orden servidor) y `view.php` (detalle). Se registra como `admin_externalpage` bajo la categoría de informes para que aparezca en Administración del sitio → Informes. El HTML del cuerpo se muestra con `format_text(..., FORMAT_HTML)` para que pase por el purificador. Alternativa descartada: report builder API — más potente pero innecesariamente complejo para v1.

5. **Retención con `admin_setting_configselect` + scheduled task** — Ajuste `local_emaillog/retention` con valores en segundos (30 días, 90 días, 6 meses, 1 año) y `0` = de por vida. Task `\local_emaillog\task\cleanup` en `db/tasks.php`, diaria a las 03:00 por defecto, que hace `DELETE FROM {local_emaillog} WHERE timecreated < :cutoff` (solo si retención > 0) usando `$DB->delete_records_select`. El administrador puede reprogramarla con la UI estándar de tareas.

6. **Privacy API completa** — `classes/privacy/provider.php` implementa `metadata\provider`, `request\plugin\provider` y `core_userlist_provider`, exportando/borrando registros por `useridfrom`/`useridto`. Obligatorio para publicar en el directorio de plugins y para RGPD.

7. **Estructura de archivos estándar:**
   ```
   local/emaillog/
   ├── version.php
   ├── settings.php
   ├── index.php            (listado)
   ├── view.php             (detalle)
   ├── db/
   │   ├── install.xml
   │   ├── hooks.php
   │   ├── tasks.php
   │   └── access.php
   ├── classes/
   │   ├── hook_callbacks.php
   │   ├── table/emaillog_table.php
   │   ├── form/filter_form.php
   │   ├── task/cleanup.php
   │   └── privacy/provider.php
   └── lang/
       ├── en/local_emaillog.php
       └── es/local_emaillog.php
   ```

## Risks / Trade-offs

- [Los nombres exactos de los hooks de email varían entre 4.4/5.x] → Verificar en `lib/classes/hook/` del core objetivo al implementar; si solo existe el hook previo, el estado post-envío queda "desconocido".
- [La tabla puede crecer mucho en sitios con alto volumen de correo] → Índice en `timecreated` + purga diaria; el default de 6 meses evita crecimiento indefinido salvo elección explícita de "de por vida".
- [El contenido de los emails contiene datos personales sensibles] → Capacidad restringida a managers, Privacy API implementada, y la retención configurable permite políticas de minimización.
- [Emails masivos (foros con miles de suscriptores) generan miles de inserciones] → Una inserción simple por email es barata; si fuera problema, se podría batchear en una mejora futura.
- [Cuerpos HTML almacenados podrían usarse como vector XSS en el visor] → Render siempre a través del purificador de Moodle (`format_text`), nunca `echo` directo.

## Migration Plan

1. Instalar el plugin copiando `local/emaillog/` y visitando la página de notificaciones (crea la tabla vía `install.xml`).
2. Configurar la retención deseada (default 6 meses).
3. Rollback: desinstalar el plugin desde la administración; Moodle elimina la tabla y los ajustes. No toca datos del core.

## Open Questions

- ¿Debe registrarse también el email de "soporte" saliente vía `email_to_user` con usuario ficticio (p. ej. invitados)? Decisión tomada: sí, guardando userid nulo/0 (cubierto en specs).
- Confirmar durante la implementación el nombre/firma exacta de los hooks de email en la rama de Moodle 5.x objetivo.
