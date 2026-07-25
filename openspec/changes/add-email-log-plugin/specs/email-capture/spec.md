# email-capture

## ADDED Requirements

### Requirement: Registro de los emails salientes de la Message API
El plugin SHALL interceptar todo email que Moodle envíe a través de la Message API (`message_send()` con el procesador de salida `email`), mediante el callback `pre_processor_message_send`, y SHALL crear un registro en la tabla `local_emaillog` sin impedir ni alterar el envío del email.

Moodle 5.x no expone ningún punto de extensión en el camino de éxito de `email_to_user()`, por lo que los emails enviados con llamadas directas a esa función que se completan correctamente NO se registran. Sus fallos SÍ se registran (ver el requisito de estado del envío). Esta limitación SHALL documentarse en la interfaz de administración y en el README.

#### Scenario: Notificación enviada correctamente
- **WHEN** Moodle envía por email una notificación a un usuario (mensaje de foro, entrega de tarea, insignia, aviso de calendario, mensaje privado)
- **THEN** se crea un registro en `local_emaillog` con los datos del email y el envío se completa con normalidad

#### Scenario: Email directo con éxito
- **WHEN** Moodle envía un email con una llamada directa a `email_to_user()` que se completa correctamente (p. ej. restablecimiento de contraseña)
- **THEN** no se crea ningún registro, y la interfaz advierte al administrador de que ese canal no se puede auditar en Moodle 5.x

#### Scenario: Fallo al guardar el registro
- **WHEN** la inserción del registro en base de datos falla por cualquier motivo
- **THEN** el email se envía igualmente y el error se anota en el log de depuración de Moodle, sin lanzar excepción al usuario

### Requirement: Datos almacenados por registro
Cada registro SHALL almacenar: email y userid del remitente, email y userid del destinatario, timestamp de envío, asunto, cuerpo en texto plano, cuerpo HTML (si existe), dirección Reply-To (si existe), nombres de los adjuntos (si existen) y el componente/origen de Moodle que disparó el envío cuando sea determinable.

#### Scenario: Email con todos los campos
- **WHEN** se envía un email con cuerpo HTML, Reply-To y un adjunto
- **THEN** el registro contiene remitente, destinatario, timestamp, asunto, cuerpo texto, cuerpo HTML, Reply-To y el nombre del adjunto

#### Scenario: Email mínimo
- **WHEN** se envía un email solo con cuerpo de texto plano y sin adjuntos
- **THEN** el registro se guarda con los campos opcionales (HTML, Reply-To, adjuntos) vacíos o nulos

#### Scenario: Remitente no-usuario
- **WHEN** el remitente es el usuario de soporte/noreply del sitio (sin userid real)
- **THEN** el registro guarda el email del remitente y userid 0 o nulo

### Requirement: Registro del estado del envío
El plugin SHALL observar el evento `\core\event\email_failed` y SHALL marcar como "fallido" el registro correspondiente, guardando el mensaje de error devuelto por el mailer. Si el evento corresponde a un email que no está en la tabla (envío directo vía `email_to_user()`), el plugin SHALL crear un registro nuevo con estado "fallido". Los registros para los que no se observa ningún fallo SHALL quedar con estado "desconocido", ya que Moodle 5.x no notifica los envíos correctos.

#### Scenario: Envío fallido por SMTP de una notificación
- **WHEN** el servidor SMTP rechaza una notificación ya registrada y Moodle dispara `\core\event\email_failed`
- **THEN** el registro existente pasa a estado fallido y guarda el error del mailer

#### Scenario: Envío directo fallido
- **WHEN** falla una llamada directa a `email_to_user()` que no tiene registro previo (p. ej. restablecimiento de contraseña)
- **THEN** se crea un registro nuevo con estado fallido, remitente, destinatario, asunto, cuerpo de texto y el error del mailer

#### Scenario: Envío sin fallos observados
- **WHEN** una notificación se registra y no se dispara ningún evento de fallo
- **THEN** el registro queda con estado "desconocido", que la interfaz explica como "no se detectó ningún fallo"

### Requirement: Cumplimiento de la Privacy API
El plugin SHALL implementar el privacy provider de Moodle declarando la tabla `local_emaillog` como almacén de datos personales, y SHALL soportar exportación y borrado de los registros vinculados a un usuario.

#### Scenario: Solicitud de exportación de datos
- **WHEN** un usuario solicita la exportación de sus datos personales
- **THEN** los registros de emails donde figura como remitente o destinatario se incluyen en la exportación

#### Scenario: Solicitud de borrado de datos
- **WHEN** se procesa una solicitud de borrado de datos de un usuario
- **THEN** los registros vinculados a ese usuario se eliminan o anonimizan
