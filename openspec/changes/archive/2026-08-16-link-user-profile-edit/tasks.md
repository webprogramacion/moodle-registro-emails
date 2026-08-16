## 1. Helper compartido

- [x] 1.1 Crear `local/emaillog/classes/local/userlink.php` con la clase `\local_emaillog\local\userlink`, cabecera GPL y bloque `@package`/`@copyright`/`@license` como el resto del plugin
- [x] 1.2 Implementar `can_edit(): bool` con `has_capability('moodle/user:update', \context_system::instance())`, cacheando el resultado en una propiedad estática para no reevaluarlo por fila
- [x] 1.3 Implementar `edit_url(int $userid): \moodle_url` apuntando a `/user/editadvanced.php` con `id` y `course => SITEID`
- [x] 1.4 Implementar `render(?int $userid, string $fullname, bool $deleted, ?\moodle_url $fallbackurl = null): string`: texto plano escapado si no hay `userid`, el usuario está eliminado o el nombre está vacío; enlace de edición si `can_edit()`; `$fallbackurl` si se ha pasado; texto plano en cualquier otro caso
- [x] 1.5 Añadir a los enlaces los atributos `target="_blank"`, `rel="noopener"` y un `title` construido con la cadena de idioma correspondiente

## 2. Cadenas de idioma

- [x] 2.1 Añadir a `lang/en/local_emaillog.php` las cadenas del `title` para el enlace de edición y para el fallback al perfil, ambas con marcador `{$a}` para el nombre y aviso de ventana nueva
- [x] 2.2 Añadir las mismas claves a `lang/es/local_emaillog.php`, respetando el orden alfabético del fichero
- [x] 2.3 Comprobar que ambos ficheros siguen teniendo el mismo número de claves y que ninguna clave nueva falta en uno de los dos

## 3. Listado

- [x] 3.1 En `emaillog_table::get_fields()`, añadir `uf.deleted AS fromdeleted` y `ut.deleted AS todeleted` al SELECT
- [x] 3.2 Cambiar la firma de `format_participant()` para que reciba también el `userid` y la marca de eliminado, y delegar el renderizado del nombre en `userlink::render()` sin `$fallbackurl`
- [x] 3.3 Actualizar `col_emailfrom()` y `col_emailto()` para pasar `useridfrom`/`fromdeleted` y `useridto`/`todeleted`
- [x] 3.4 Verificar que la maquetación actual se conserva: nombre enlazado, salto de línea y dirección en `<small>`, y que la dirección se sigue escapando con `s()`

## 4. Vista de detalle

- [x] 4.1 En `detail::participant()`, sustituir la construcción manual del enlace a `/user/profile.php` por una llamada a `userlink::render()` pasando `/user/profile.php` como `$fallbackurl`
- [x] 4.2 Comprobar que se mantiene el comportamiento actual para usuarios eliminados y para registros sin `userid`, y que la dirección sigue mostrándose en `<small>` entre `&lt;` y `&gt;`

## 5. Versión y documentación

- [x] 5.1 Subir `$plugin->version` a `2026081600` y `$plugin->release` a `'0.2.0'` en `version.php`, dejando `$plugin->requires` sin tocar
- [x] 5.2 Actualizar la tabla de versiones del `README.md` de la raíz (release y versión)
- [x] 5.3 Actualizar en el `README.md` de la raíz el nombre del ZIP de ejemplo en el comando de empaquetado y en el de verificación
- [x] 5.4 Añadir `classes/local/userlink.php` al árbol de los ficheros del ZIP en el `README.md` de la raíz, con su línea de descripción, y actualizar el recuento de 19 a 20 ficheros allí donde aparezca

## 6. Verificación

- [x] 6.1 Comprobar sintaxis de los ficheros PHP tocados con `php -l`
- [x] 6.2 Revisar que no queda ninguna concatenación de nombre de usuario sin escapar con `s()` en las rutas nuevas
- [x] 6.3 Contrastar el árbol del README con `find local/emaillog -type f | sort` (deben salir 20 ficheros)
- [x] 6.4 Repasar contra la spec los cuatro casos de degradación: sin `moodle/user:update`, sin `userid`, usuario eliminado y usuario borrado de la tabla `{user}`
- [x] 6.5 Confirmar que no se ha añadido ninguna consulta dentro de un bucle de filas ni ninguna llamada a `has_capability()` por fila
