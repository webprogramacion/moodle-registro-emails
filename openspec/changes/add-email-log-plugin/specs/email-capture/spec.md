# email-capture

## ADDED Requirements

### Requirement: Registro de todos los emails salientes
El plugin SHALL interceptar todo email enviado a través de la función `email_to_user()` de Moodle (mediante el hook `\core\hook\email_send` de Moodle 5.x) y SHALL crear un registro en la tabla `local_emaillog` sin impedir ni alterar el envío del email.

#### Scenario: Email enviado correctamente
- **WHEN** Moodle envía un email a un usuario (notificación de foro, mensaje del sistema, restablecimiento de contraseña, etc.)
- **THEN** se crea un registro en `local_emaillog` con los datos del email y el envío se completa con normalidad

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
El registro SHALL reflejar el resultado del envío (enviado o fallido) cuando Moodle proporcione esa información a través del hook posterior al envío; si no es determinable, el estado SHALL quedar como "desconocido".

#### Scenario: Envío fallido por SMTP
- **WHEN** el servidor SMTP rechaza el mensaje y Moodle reporta el fallo
- **THEN** el registro correspondiente marca el estado como fallido

### Requirement: Cumplimiento de la Privacy API
El plugin SHALL implementar el privacy provider de Moodle declarando la tabla `local_emaillog` como almacén de datos personales, y SHALL soportar exportación y borrado de los registros vinculados a un usuario.

#### Scenario: Solicitud de exportación de datos
- **WHEN** un usuario solicita la exportación de sus datos personales
- **THEN** los registros de emails donde figura como remitente o destinatario se incluyen en la exportación

#### Scenario: Solicitud de borrado de datos
- **WHEN** se procesa una solicitud de borrado de datos de un usuario
- **THEN** los registros vinculados a ese usuario se eliminan o anonimizan
