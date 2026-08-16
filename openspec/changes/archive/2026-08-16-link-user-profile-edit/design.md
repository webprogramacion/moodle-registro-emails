## Context

El listado del informe se construye en `\local_emaillog\table\emaillog_table`. Las columnas de remitente y destinatario las renderiza `format_participant(?string $email, ?string $name)`, que hoy devuelve el nombre escapado, un `<br>` y la dirección en `<small>` — todo texto plano. La consulta ya trae `el.useridfrom` y `el.useridto`, y ya hace `LEFT JOIN {user} uf` / `LEFT JOIN {user} ut` para poder filtrar por nombre, pero **el `userid` no llega a `format_participant()`** y el campo `deleted` no se selecciona.

La página de detalle usa `\local_emaillog\local\detail::participant(?int $userid, ?string $email)`, que sí carga el usuario con `\core_user::get_user($userid, 'id, firstname, lastname, deleted')`, ya excluye a los eliminados del enlazado y enlaza a `/user/profile.php`.

Es decir: la lógica de "¿enlazo este nombre y a dónde?" existe hoy a medias en `detail.php` y no existe en la tabla. Este cambio la unifica y la reapunta al formulario de edición.

Restricciones:

- `local/emaillog:view` está definida en contexto de sistema con `RISK_PERSONAL` y se concede al arquetipo *manager*. **No implica poder editar usuarios**: son capacidades independientes, y un rol podría tener una sin la otra.
- El formulario que permite corregir la dirección de correo de otra persona es `/user/editadvanced.php`, el mismo al que enlaza el navegador de usuarios del core (`/admin/user.php`). Exige `moodle/user:update`.
- La tabla es un `table_sql` con paginación: cualquier consulta por fila multiplicaría el coste por el tamaño de página (50).

## Goals / Non-Goals

**Goals:**

- Que desde el listado filtrado por estado "Fallido" se llegue al formulario de edición del usuario en un clic, sin perder los filtros ni la página en la que se estaba.
- Un único criterio, compartido por listado y detalle, sobre cuándo se enlaza un nombre y a dónde.
- No mostrar nunca un enlace que llevaría a un error de permisos.
- Coste constante por página: ninguna consulta adicional por fila.

**Non-Goals:**

- No se añade edición en línea del email dentro del propio informe.
- No se toca el esquema de base de datos ni la captura de emails.
- No se enlaza la dirección de correo en sí (solo el nombre); una dirección sin usuario asociado no gana enlace.
- No se añade un enlace "buscar este email en la lista de usuarios" para los registros sin `userid`.
- No se reescribe la lógica de filtros ni la consulta más allá de las dos columnas necesarias.

## Decisions

### 1. Destino: `/user/editadvanced.php?id=<id>&course=<SITEID>`

Es el formulario que usa el propio core desde el navegador de usuarios y el único que permite a un administrador corregir la dirección de correo de otra persona junto al resto de la ficha.

*Alternativa descartada:* `/user/edit.php`, que exige `moodle/user:editprofile` y, para un administrador editando a un tercero, acaba redirigiendo al formulario avanzado. Enlazar directamente al destino final evita un salto.

El parámetro `course` se pasa con `SITEID` para que el formulario devuelva al usuario al contexto de sitio al guardar.

### 2. Filtro por `moodle/user:update` en contexto de sistema, evaluado una vez por página

`\local_emaillog\local\userlink::can_edit()` hace `has_capability('moodle/user:update', \context_system::instance())` y **cachea el resultado en una propiedad estática**. La capacidad no varía entre filas de la misma página, así que evaluarla 100 veces (50 filas × 2 columnas) sería puro desperdicio.

Se comprueba en contexto de sistema, no en el contexto de cada usuario, porque es lo que exige `editadvanced.php` para editar a un tercero y porque es la comprobación más estricta: no genera falsos positivos que acabarían en una página de error.

*Alternativa descartada:* no comprobar nada y dejar que el core rechace el acceso. Se descartó porque muestra un enlace roto a quien tiene `local/emaillog:view` sin ser administrador de usuarios, que es un escenario perfectamente configurable.

### 3. Helper compartido `\local_emaillog\local\userlink`

Clase nueva con tres métodos:

```php
userlink::can_edit(): bool                 // capacidad, cacheada
userlink::edit_url(int $userid): moodle_url
userlink::render(?int $userid, string $fullname, bool $deleted, ?moodle_url $fallbackurl = null): string
```

`render()` decide y escapa: sin `userid`, con usuario eliminado o sin nombre devuelve el texto plano escapado; con permiso devuelve el enlace de edición; sin permiso usa `$fallbackurl` si se le pasa, y si no, texto plano.

Los dos llamantes obtienen sus datos de sitios distintos —la tabla desde el JOIN, el detalle desde `\core_user::get_user()`— así que el helper recibe valores ya resueltos en vez de consultar por su cuenta. Así no se introduce ninguna consulta por fila.

*Ubicación:* `classes/local/`, junto a `logger` y `detail`, que es donde vive la lógica interna del plugin.

*Alternativa descartada:* añadir el método a `detail`, cuya responsabilidad declarada es "formateo de la página de detalle"; la tabla no debería depender de una clase del detalle.

### 4. El fallback del listado es texto plano; el del detalle, el perfil de solo lectura

El detalle enlaza hoy al perfil y ese enlace sigue siendo útil para quien no puede editar usuarios, así que se conserva como fallback. El listado nunca ha tenido enlace, y añadirle uno al perfil de solo lectura para usuarios sin permiso de edición sería introducir una funcionalidad que nadie ha pedido. Cada llamante decide su fallback pasando (o no) `$fallbackurl`.

### 5. `target="_blank"` con `rel="noopener"` en ambos enlaces, incluido el fallback

El `_blank` lo pide el caso de uso: el informe filtrado (`?status=2` más rango de fechas y página N) es caro de reconstruir y no debe perderse. `rel="noopener"` impide que la pestaña destino acceda a `window.opener`.

Se aplica también al fallback al perfil de solo lectura —cambio menor respecto al comportamiento actual del detalle— porque el motivo es el mismo y tener dos comportamientos distintos en la misma tabla sería incoherente.

Accesibilidad: abrir en pestaña nueva sin avisar desorienta a quien usa lector de pantalla, así que el enlace lleva un `title` con una cadena propia que lo advierte. Se definen cadenas propias en vez de reutilizar una del core para no depender de la existencia de una cadena concreta en todas las versiones 5.x soportadas.

### 6. Campo `deleted` desde el JOIN existente, no con una consulta por fila

Se añaden `uf.deleted AS fromdeleted` y `ut.deleted AS todeleted` al SELECT de `get_fields()`. El `LEFT JOIN` ya está, así que el coste es nulo. Un usuario borrado de la tabla `{user}` (no solo marcado como `deleted`) produce `NULL` en el nombre y el registro degrada solo a la dirección, como ya ocurre hoy.

### 7. Subida de `$plugin->version` y release

Cambio de código sin cambio de esquema, pero Moodle detecta actualizaciones por `$plugin->version`, así que se sube a `2026081600` y la release a `0.2.0`. Esto obliga a actualizar la tabla de versiones y el nombre del ZIP de ejemplo en el `README.md` de la raíz, que cita ambos valores.

*Alternativa descartada:* no subir versión. Dejaría a los sitios ya instalados sin forma de saber que hay código nuevo.

## Risks / Trade-offs

- **El enlace lleva a un formulario con muchos más campos que el email** (autenticación, contraseña, nombre de usuario), y un administrador apurado puede tocar algo por error → Es el formulario que el core usa para lo mismo desde el navegador de usuarios; ofrecer otra cosa sería inventar un camino distinto al estándar de Moodle. No se mitiga en código.
- **La comprobación en contexto de sistema puede ser más estricta de lo necesario** en configuraciones donde `moodle/user:update` se asigna solo en un contexto de categoría → El resultado es un enlace de menos, nunca un error: el nombre se sigue mostrando y el detalle mantiene su enlace al perfil. Se acepta a cambio de no hacer una comprobación por fila.
- **`target="_blank"` acumula pestañas** si el administrador revisa muchos registros seguidos → Es el compromiso explícito del caso de uso; perder un informe filtrado cuesta más que cerrar una pestaña.
- **El `title` no lo leen todos los lectores de pantalla de forma fiable** → Alternativa sería texto visible "(ventana nueva)" en cada celda, que satura una tabla de 50 filas. Se opta por el `title`, que es lo que hace el core en situaciones equivalentes.
- **Divergencia entre el listado y el detalle si alguien añade una tercera vista** → Mitigado por el helper compartido: cualquier vista nueva debe pasar por `userlink::render()`.
- **La delta spec apunta a `email-log-viewer`, cuya spec principal aún no existe** (la crea `add-email-log-plugin`, activo con 18/20 tareas) → No afecta a la implementación; sí al orden de sincronización. Debe archivarse primero `add-email-log-plugin`, o sincronizar su spec antes que la de este cambio.
