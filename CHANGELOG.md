# Registro de cambios

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).

## [0.2.0] — 2026-08-16

### Añadido

- Los nombres de remitente y destinatario del listado y de la vista de detalle enlazan al
  formulario de edición del perfil (`/user/editadvanced.php`), en una pestaña nueva, para
  poder corregir una dirección mal escrita sin perder los filtros del informe.
- El enlace solo aparece para quien tiene la capacidad `moodle/user:update`; sin ella, el
  listado muestra el nombre en texto plano y el detalle conserva su enlace al perfil de
  solo lectura. Nunca se enlazan los registros sin usuario asociado ni los usuarios
  eliminados.

### Cambiado

- El plugin pasa a ocupar la raíz del repositorio, siguiendo la convención de los plugins
  de Moodle: ahora se puede instalar con `git clone <repo> local/emaillog`.
- El ZIP de instalación se construye con `git archive` y se publica automáticamente como
  release de GitHub al empujar un tag `v*`.

## [0.1.0] — 2026-07-25

### Añadido

- Registro de los emails salientes capturados por el callback `pre_processor_message_send`
  de la Message API y por el evento `\core\event\email_failed`.
- Informe en **Administración del sitio → Informes → Registro de emails**, con filtros por
  rango de fechas, remitente, destinatario, asunto y estado, y vista de detalle por email.
- Política de retención configurable (30 días, 90 días, 6 meses, 1 año o de por vida) y
  tarea programada diaria que purga los registros más antiguos.
- Capacidad `local/emaillog:view` e implementación de la Privacy API.
- Cadenas de idioma en inglés y español.
