# Tasks: add-email-log-plugin

## 1. Esqueleto del plugin

- [ ] 1.1 Crear estructura `local/emaillog/` con `version.php` (component `local_emaillog`, requires Moodle 5.x, maturity, GPL header)
- [ ] 1.2 Crear cadenas de idioma `lang/en/local_emaillog.php` y `lang/es/local_emaillog.php` (pluginname, columnas, filtros, ajustes, task, privacy)
- [ ] 1.3 Definir la tabla en `db/install.xml` con los campos y los índices (`timecreated`, `useridfrom`, `useridto`, `emailto`) según design.md
- [ ] 1.4 Crear `db/access.php` con la capacidad `local/emaillog:view` (contexto sistema, asignada a manager por defecto)

## 2. Captura de emails (email-capture)

- [ ] 2.1 Verificar en el core de Moodle 5.x los hooks de email disponibles (`before_email_send` / `after_email_send`) y su firma exacta
- [ ] 2.2 Crear `db/hooks.php` registrando los callbacks
- [ ] 2.3 Implementar `classes/hook_callbacks.php`: extraer remitente (email + userid), destinatario (email + userid), asunto, cuerpo texto, cuerpo HTML, Reply-To, nombres de adjuntos y componente; insertar el registro envuelto en try/catch para que un fallo de BD nunca bloquee el envío
- [ ] 2.4 Actualizar el estado (enviado/fallido) desde el hook posterior si existe; en caso contrario dejar estado "desconocido"

## 3. Visor de administración (email-log-viewer)

- [ ] 3.1 Crear `settings.php`: página de ajustes del plugin y `admin_externalpage` "Registro de emails" bajo Informes
- [ ] 3.2 Implementar `classes/form/filter_form.php` con filtros: rango de fechas, destinatario, remitente, asunto, estado
- [ ] 3.3 Implementar `classes/table/emaillog_table.php` (extiende `table_sql`): columnas fecha/hora, remitente, destinatario, asunto, estado; orden por defecto `timecreated DESC`; enlace al detalle
- [ ] 3.4 Crear `index.php`: require capability `local/emaillog:view`, formulario de filtros + tabla paginada, mensaje de "sin resultados"
- [ ] 3.5 Crear `view.php`: vista de detalle con todos los campos; renderizar el cuerpo HTML con `format_text()` saneado

## 4. Retención y purga (log-retention)

- [ ] 4.1 Añadir en `settings.php` el select `local_emaillog/retention` con opciones 30 días / 90 días / 6 meses / 1 año / de por vida (default 6 meses, valores en segundos, 0 = de por vida)
- [ ] 4.2 Implementar `classes/task/cleanup.php`: si retención > 0, borrar registros con `timecreated < time() - retención` y trazar el número de registros eliminados con `mtrace()`
- [ ] 4.3 Registrar la tarea en `db/tasks.php` con ejecución diaria a las 03:00

## 5. Privacidad y calidad

- [ ] 5.1 Implementar `classes/privacy/provider.php` (metadata, exportación y borrado por `useridfrom`/`useridto`, userlist provider)
- [ ] 5.2 Revisar el código con los estándares de Moodle (moodle-cs / phpcs) y corregir avisos
- [ ] 5.3 Prueba manual en un Moodle 5.x: enviar un email de prueba, verificar que aparece en el informe con todos los campos, probar filtros y detalle
- [ ] 5.4 Probar la purga: crear registros antiguos, configurar retención 30 días, ejecutar la task con `admin/cli/scheduled_task.php` y comprobar el borrado; verificar que "de por vida" no borra nada
