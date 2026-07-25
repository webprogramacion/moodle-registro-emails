# Tasks: add-email-log-plugin

## 1. Esqueleto del plugin

- [x] 1.1 Crear estructura `local/emaillog/` con `version.php` (component `local_emaillog`, requires Moodle 5.x, maturity, GPL header)
- [x] 1.2 Crear cadenas de idioma `lang/en/local_emaillog.php` y `lang/es/local_emaillog.php` (pluginname, columnas, filtros, ajustes, task, privacy)
- [x] 1.3 Definir la tabla en `db/install.xml` con los campos y los índices (`timecreated`, `useridfrom`, `useridto`, `emailto`) según design.md
- [x] 1.4 Crear `db/access.php` con la capacidad `local/emaillog:view` (contexto sistema, asignada a manager por defecto)

## 2. Captura de emails (email-capture)

- [x] 2.1 Verificar en el core de Moodle 5.x los hooks de email disponibles (`before_email_send` / `after_email_send`) y su firma exacta — **Resultado: no existen en 5.0/5.1/5.2.** `email_to_user()` no despacha ningún hook y `get_mailer()` no es sustituible. Únicos puntos de extensión: el callback `pre_processor_message_send` de la Message API y el evento `\core\event\email_failed`. Ver `design.md`, decisión 2.
- [x] 2.2 Crear `lib.php` con `local_emaillog_pre_processor_message_send($processorname, $eventdata)` (filtrando el procesador `email`) y `db/events.php` con el observer de `\core\event\email_failed`
- [x] 2.3 Implementar `classes/local/logger.php`: extraer remitente (email + userid), destinatario (email + userid, replicando el override `message_processor_email_email`), asunto, cuerpo texto, cuerpo HTML, Reply-To, nombres de adjuntos y componente; insertar el registro envuelto en try/catch para que un fallo de BD nunca bloquee el envío
- [x] 2.4 Implementar `classes/observer.php`: desde `\core\event\email_failed`, marcar como fallido el registro pendiente correlacionado (destinatario + asunto + ventana temporal) guardando el `errorinfo`, o insertar uno nuevo si el envío fue directo; el resto queda con estado "desconocido"

## 3. Visor de administración (email-log-viewer)

- [x] 3.1 Crear `settings.php`: página de ajustes del plugin y `admin_externalpage` "Registro de emails" bajo Informes
- [x] 3.2 Implementar `classes/form/filter_form.php` con filtros: rango de fechas, destinatario, remitente, asunto, estado
- [x] 3.3 Implementar `classes/table/emaillog_table.php` (extiende `table_sql`): columnas fecha/hora, remitente, destinatario, asunto, estado; orden por defecto `timecreated DESC`; enlace al detalle
- [x] 3.4 Crear `index.php`: require capability `local/emaillog:view`, formulario de filtros + tabla paginada, mensaje de "sin resultados"
- [x] 3.5 Crear `view.php`: vista de detalle con todos los campos; renderizar el cuerpo HTML con `format_text()` saneado

## 4. Retención y purga (log-retention)

- [x] 4.1 Añadir en `settings.php` el select `local_emaillog/retention` con opciones 30 días / 90 días / 6 meses / 1 año / de por vida (default 6 meses, valores en segundos, 0 = de por vida)
- [x] 4.2 Implementar `classes/task/cleanup.php`: si retención > 0, borrar registros con `timecreated < time() - retención` y trazar el número de registros eliminados con `mtrace()`
- [x] 4.3 Registrar la tarea en `db/tasks.php` con ejecución diaria a las 03:00

## 5. Privacidad y calidad

- [x] 5.1 Implementar `classes/privacy/provider.php` (metadata, exportación y borrado por `useridfrom`/`useridto`, userlist provider)
- [x] 5.2 Revisar el código con los estándares de Moodle (moodle-cs / phpcs) y corregir avisos — `php -l` limpio en los 16 archivos PHP; phpcs 3.13.2 con el subconjunto de reglas integradas del ruleset real de moodle-cs (35 refs: PSR12, Generic, Squiz, PEAR, PSR2) limpio tras corregir 7 avisos de `PSR12.Classes.OpeningBraceSpace`; longitud de línea dentro de 132/180 (los archivos de `lang/` están exentos por el propio sniff de Moodle); boilerplate GPL y docblocks verificados contra el código de los sniffs. **Pendiente:** las ~11 reglas propias `moodle.*` / `Universal` / `NormalizedArrays` necesitan composer (no disponible en esta máquina) para instalar moodle-cs con sus dependencias.
- [ ] 5.3 Prueba manual en un Moodle 5.x: enviar un email de prueba, verificar que aparece en el informe con todos los campos, probar filtros y detalle
- [ ] 5.4 Probar la purga: crear registros antiguos, configurar retención 30 días, ejecutar la task con `admin/cli/scheduled_task.php` y comprobar el borrado; verificar que "de por vida" no borra nada
