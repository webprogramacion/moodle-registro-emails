# email-log-viewer Specification

## Purpose

Definir el informe de consulta del registro de emails del plugin `local_emaillog`: cómo se listan y se detallan los mensajes enviados por el sitio y qué acciones puede realizar el administrador desde ese informe.

Nota: esta especificación está incompleta a propósito. Los requisitos base de la capacidad (acceso protegido por capacidad, listado paginado, filtros de búsqueda y vista de detalle) se incorporarán cuando se archive el cambio `add-email-log-plugin`.

## Requirements

### Requirement: Enlace al formulario de edición del usuario

Los nombres de remitente y destinatario mostrados en el listado y en la vista de detalle SHALL enlazar al formulario de edición del perfil del usuario (`/user/editadvanced.php`), para que el administrador pueda corregir la dirección de correo sin abandonar el informe. El enlace SHALL abrirse en una ventana o pestaña nueva con `target="_blank"` y `rel="noopener"`, de modo que los filtros y la paginación en curso no se pierdan.

#### Scenario: Corregir el email de un destinatario desde el listado de fallidos

- **WHEN** un administrador con la capacidad `moodle/user:update` filtra el listado por estado "Fallido" y pulsa sobre el nombre del destinatario
- **THEN** se abre en una pestaña nueva el formulario de edición del perfil de ese usuario
- **AND** la pestaña original conserva el listado con sus filtros y su página intactos

#### Scenario: El remitente también enlaza

- **WHEN** un administrador con la capacidad `moodle/user:update` ve una fila cuyo remitente es un usuario real de la plataforma
- **THEN** el nombre del remitente enlaza al formulario de edición de ese usuario, con el mismo comportamiento que el destinatario

#### Scenario: El enlace se anuncia como ventana nueva

- **WHEN** se renderiza cualquiera de estos enlaces
- **THEN** el enlace incluye un texto accesible que indica de qué usuario es el perfil y que se abre en una ventana nueva

### Requirement: Degradación del enlace según permisos y estado del usuario

El enlace de edición SHALL renderizarse únicamente cuando quien consulta el informe tiene la capacidad `moodle/user:update` y el registro corresponde a un usuario real y no eliminado. La capacidad `local/emaillog:view` por sí sola NO SHALL habilitar el enlace de edición.

#### Scenario: Usuario con acceso al informe pero sin permiso para editar usuarios

- **WHEN** alguien con `local/emaillog:view` pero sin `moodle/user:update` abre el listado
- **THEN** los nombres se muestran como texto plano, sin enlace de edición
- **AND** en la vista de detalle el nombre conserva su enlace al perfil de solo lectura

#### Scenario: Registro sin usuario asociado

- **WHEN** un registro tiene el identificador de usuario vacío, como ocurre con el pseudo-usuario *noreply* del sitio
- **THEN** se muestra únicamente la dirección de correo almacenada, sin enlace

#### Scenario: Usuario eliminado

- **WHEN** el usuario al que corresponde el registro está marcado como eliminado
- **THEN** su nombre se muestra sin enlace, en el listado y en el detalle

#### Scenario: Usuario borrado de la base de datos

- **WHEN** el identificador de usuario almacenado ya no corresponde a ningún registro de la tabla de usuarios
- **THEN** la fila muestra la dirección de correo almacenada, sin nombre y sin enlace

### Requirement: Coste constante del enlazado

La construcción de los enlaces NO SHALL introducir consultas adicionales a la base de datos por cada fila del listado. Los datos necesarios (identificador de usuario, nombre y marca de eliminado) SHALL obtenerse de la consulta ya existente del listado, y la comprobación de capacidad SHALL evaluarse una sola vez por página.

#### Scenario: Listado con una página completa de registros

- **WHEN** se renderiza una página del listado con 50 registros
- **THEN** el número de consultas a la base de datos es el mismo que antes de introducir los enlaces
- **AND** la capacidad `moodle/user:update` se comprueba una única vez para toda la página

### Requirement: Criterio único compartido entre las vistas

La decisión de si un nombre se enlaza y hacia dónde SHALL estar implementada en un único punto compartido por el listado y por la vista de detalle, de forma que ambas vistas no puedan aplicar criterios distintos.

#### Scenario: Cambio del criterio de enlazado

- **WHEN** se modifica la condición bajo la que un nombre se enlaza
- **THEN** el cambio afecta por igual al listado y a la vista de detalle, sin necesidad de editarlos por separado
