# Proposal: add-email-log-plugin

## Why

Moodle no ofrece de serie una forma de auditar los correos que envía la plataforma: cuando un usuario dice "no me llegó el email", el administrador no tiene forma de comprobar qué se envió, a quién ni cuándo. Se necesita un plugin para Moodle 5.x que registre todos los emails salientes y permita al administrador consultarlos, con purga automática configurable de registros antiguos para controlar el tamaño de la base de datos y cumplir con retención de datos (RGPD).

## What Changes

- Nuevo plugin local `local_emaillog` (tipo `local`, compatible con Moodle 5.0/5.1/5.2) que intercepta los emails salientes mediante el callback `pre_processor_message_send` de la Message API y el observer del evento `\core\event\email_failed`, y los guarda en una tabla propia. (Se comprobó durante la implementación que los hooks de email `\core\hook\email\*` no existen en ninguna versión 5.x; ver `design.md`, decisión 2.)
- Cada registro guarda: remitente (email y userid), destinatario (email y userid), fecha/hora (timestamp), asunto, contenido (texto y HTML), Reply-To, adjuntos (nombres), estado del envío (desconocido/fallido), error del mailer y componente/origen de Moodle que disparó el envío cuando sea determinable.
- Nueva página de administración (Administración del sitio → Informes) con listado paginado, filtros (rango de fechas, destinatario, remitente, asunto, estado) y vista de detalle de cada email.
- Página de configuración con la política de retención: 30 días, 90 días, 6 meses, 1 año o "de por vida" (sin borrado).
- Tarea programada (scheduled task) que purga diariamente los registros más antiguos que el periodo de retención configurado.
- Implementación de la Privacy API de Moodle (exportación/borrado de datos por usuario) al almacenar datos personales.
- Capacidad propia `local/emaillog:view` para controlar quién puede ver el registro (por defecto, solo gestores/administradores).

## Capabilities

### New Capabilities
- `email-capture`: interceptación y almacenamiento de todos los emails salientes de Moodle con sus metadatos y contenido.
- `email-log-viewer`: interfaz de administración para listar, filtrar y ver en detalle los emails registrados, protegida por capacidad.
- `log-retention`: configuración de la política de retención y purga automática programada de registros antiguos.

### Modified Capabilities

<!-- Ninguna: es un plugin nuevo, no hay specs existentes que cambien. -->

## Impact

- Código nuevo autocontenido en `local/emaillog/` (no se modifica el core de Moodle).
- Base de datos: nueva tabla `local_emaillog` (definida en `db/install.xml`).
- Captura: callback `local_emaillog_pre_processor_message_send()` en `lib.php` y observer de `\core\event\email_failed` en `db/events.php`.
- Limitación: los emails enviados con llamadas directas a `email_to_user()` que se completan con éxito (restablecer contraseña, confirmación de alta, formulario de soporte) no se registran, porque Moodle 5.x no expone ningún punto de extensión en ese camino; sus fallos sí se registran.
- Tareas: nueva scheduled task registrada en `db/tasks.php`.
- Administración: nuevas entradas en `settings.php` (configuración + enlace al informe).
- Privacidad: provider de la Privacy API; el contenido de los emails puede incluir datos personales, la retención configurable mitiga el riesgo.
- Rendimiento: una inserción adicional en BD por cada email enviado (impacto marginal); el tamaño de la tabla se controla con la purga.
