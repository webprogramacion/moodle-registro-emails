# Proposal: link-user-profile-edit

## Why

El caso de uso real del registro de emails es diagnosticar entregas fallidas: el administrador filtra por estado "Fallido" (`/local/emaillog/index.php?status=2`) y, casi siempre, la causa es una dirección de correo mal escrita en la ficha del usuario. Hoy el listado muestra el nombre del destinatario como texto plano, así que corregir esa dirección obliga a abandonar el informe, ir a **Administración del sitio → Usuarios → Examinar lista de usuarios**, buscar a la persona y editarla — y al volver se ha perdido el filtro. Enlazar el nombre directamente al formulario de edición convierte una tarea de varios pasos en un clic.

La página de detalle (`view.php`) sí enlaza el nombre, pero al perfil de **solo lectura** (`/user/profile.php`), que no permite corregir nada. Las dos páginas quedan además con criterios distintos para lo mismo.

## What Changes

- En el **listado**, el nombre del remitente y el del destinatario pasan a ser un enlace al formulario de edición de perfil (`/user/editadvanced.php`), que es el que permite modificar la dirección de correo.
- En la **página de detalle**, el enlace del nombre deja de apuntar al perfil de solo lectura y apunta al mismo formulario de edición, con el mismo criterio que el listado.
- Los enlaces se abren en una pestaña nueva (`target="_blank"`, con `rel="noopener"`), para no perder los filtros ni la paginación del informe.
- El enlace **solo se renderiza si quien mira tiene la capacidad `moodle/user:update`**. La capacidad del plugin, `local/emaillog:view`, no implica poder editar usuarios: sin permiso de edición, el listado sigue mostrando el nombre como texto plano y el detalle conserva su enlace actual al perfil de solo lectura.
- Nunca se enlazan los registros sin usuario asociado (`useridfrom`/`useridto` a NULL, caso típico del pseudo-usuario *noreply*) ni los usuarios eliminados; en ambos casos se muestra el nombre o la dirección sin enlace.
- Se extrae un helper compartido que decide y construye el enlace, para que listado y detalle no puedan divergir.
- Cadenas nuevas en inglés y español para el texto accesible del enlace, que advierte de que se abre en una ventana nueva.

## Capabilities

### New Capabilities

<!-- Ninguna: no se introduce ninguna capacidad nueva. -->

### Modified Capabilities
- `email-log-viewer`: se añade un requisito nuevo sobre el enlazado de los nombres de usuario hacia el formulario de edición de perfil, con su condición de capacidad y su comportamiento de degradación. Los requisitos existentes (acceso protegido, listado paginado, filtros, vista de detalle) no cambian, por lo que la delta usa `## ADDED Requirements`.

## Impact

- `local/emaillog/classes/table/emaillog_table.php`: `format_participant()` hoy solo recibe email y nombre — habrá que propagarle el `userid` (ya presente en la consulta como `useridfrom`/`useridto`) y añadir `uf.deleted`/`ut.deleted` al SELECT para no enlazar usuarios eliminados.
- `local/emaillog/classes/local/detail.php`: `participant()` cambia la URL de destino y delega la decisión en el helper compartido.
- Fichero nuevo con el helper compartido bajo `local/emaillog/classes/local/`.
- `local/emaillog/lang/en/local_emaillog.php` y `lang/es/local_emaillog.php`: cadenas nuevas.
- **Sin cambios en la base de datos**: `db/install.xml` no se toca y no hace falta subir `$plugin->version` por motivos de esquema (solo por versionado de la release, si se decide publicarla).
- Consulta del listado: dos columnas más en el SELECT sobre un `LEFT JOIN` que ya existe; sin JOIN nuevo ni consulta adicional por fila. La comprobación de `moodle/user:update` se hace una vez por página, no una vez por fila.
- Seguridad: el enlace apunta a un formulario del core que aplica sus propias comprobaciones de permiso; el filtro por capacidad en el plugin evita mostrar enlaces que acabarían en un error de acceso. `rel="noopener"` evita que la pestaña abierta pueda manipular la de origen.
- Dependencia de planificación: la spec principal `openspec/specs/email-log-viewer/spec.md` **todavía no existe** — la crea el cambio `add-email-log-plugin`, aún activo con 18/20 tareas. Esta delta debe sincronizarse después de aquel, o la sincronización no encontrará la capacidad base.
- El README de la raíz describe el visor en términos generales y no enumera el comportamiento de los enlaces, así que no requiere actualización.
