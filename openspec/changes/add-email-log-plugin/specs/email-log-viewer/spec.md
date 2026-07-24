# email-log-viewer

## ADDED Requirements

### Requirement: Acceso protegido por capacidad
La página del registro de emails SHALL requerir la capacidad `local/emaillog:view`, asignada por defecto a los roles de gestor (manager) en el contexto de sistema. El acceso SHALL estar disponible desde Administración del sitio → Informes.

#### Scenario: Administrador accede al informe
- **WHEN** un administrador del sitio abre Administración del sitio → Informes → Registro de emails
- **THEN** ve el listado de emails registrados

#### Scenario: Usuario sin permiso
- **WHEN** un usuario sin la capacidad `local/emaillog:view` intenta acceder a la URL del informe
- **THEN** Moodle deniega el acceso con el error estándar de permisos

### Requirement: Listado paginado de emails
El listado SHALL mostrar los emails registrados en una tabla paginada y ordenable, con columnas: fecha/hora, remitente, destinatario, asunto y estado. SHALL ordenarse por defecto de más reciente a más antiguo.

#### Scenario: Ver listado
- **WHEN** el administrador abre el informe con más registros que el tamaño de página
- **THEN** ve la primera página ordenada por fecha descendente y controles de paginación

### Requirement: Filtros de búsqueda
El listado SHALL permitir filtrar por rango de fechas, email/nombre del destinatario, email/nombre del remitente, texto del asunto y estado del envío. Los filtros SHALL ser combinables.

#### Scenario: Filtrar por destinatario y fechas
- **WHEN** el administrador filtra por el email de un destinatario y un rango de fechas
- **THEN** el listado muestra solo los registros que cumplen ambas condiciones

#### Scenario: Filtro sin resultados
- **WHEN** los filtros aplicados no coinciden con ningún registro
- **THEN** se muestra un mensaje de "sin resultados" en lugar de la tabla

### Requirement: Vista de detalle del email
Cada registro SHALL tener una vista de detalle que muestre todos los campos almacenados, incluido el contenido completo. El cuerpo HTML SHALL renderizarse saneado (a través de `format_text`/purificador de Moodle) para evitar ejecución de scripts.

#### Scenario: Ver detalle
- **WHEN** el administrador pulsa sobre un registro del listado
- **THEN** ve remitente, destinatario, fecha/hora, asunto, Reply-To, adjuntos, estado y el contenido completo del email

#### Scenario: Contenido HTML malicioso
- **WHEN** el cuerpo HTML de un email registrado contiene etiquetas `<script>`
- **THEN** la vista de detalle muestra el contenido saneado sin ejecutar el script
