<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Spanish strings for local_emaillog.
 *
 * @package    local_emaillog
 * @copyright  2026 Damaso Velazquez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Registro de emails';
$string['reportname'] = 'Registro de emails';

// Capacidades.
$string['emaillog:view'] = 'Ver el registro de emails salientes';

// Columnas del listado.
$string['colactions'] = 'Acciones';
$string['colfrom'] = 'Remitente';
$string['colstatus'] = 'Estado';
$string['colsubject'] = 'Asunto';
$string['coltime'] = 'Fecha/hora';
$string['colto'] = 'Destinatario';

// Estados.
$string['statusfailed'] = 'Fallido';
$string['statussent'] = 'Enviado';
$string['statusunknown'] = 'Desconocido';
$string['statusunknownhelp'] = '"Desconocido" significa que no se detectó ningún fallo en el envío. Moodle no informa de los envíos correctos, por lo que este es el estado normal de un email entregado.';

// Filtros.
$string['datefrom'] = 'Desde la fecha';
$string['dateto'] = 'Hasta la fecha';
$string['filterfrom'] = 'Remitente (email o nombre)';
$string['filterheading'] = 'Filtros';
$string['filterstatus'] = 'Estado';
$string['filtersubject'] = 'El asunto contiene';
$string['filterto'] = 'Destinatario (email o nombre)';
$string['statusany'] = 'Cualquier estado';

// Páginas de listado y detalle.
$string['attachments'] = 'Adjuntos';
$string['backtolist'] = 'Volver al registro de emails';
$string['bodyhtml'] = 'Contenido HTML';
$string['bodytext'] = 'Contenido en texto plano';
$string['component'] = 'Origen';
$string['detailtitle'] = 'Detalle del email';
$string['errorinfo'] = 'Error del mailer';
$string['noresults'] = 'Ningún email coincide con los filtros aplicados.';
$string['notrecorded'] = 'No registrado';
$string['replyto'] = 'Reply-To';
$string['viewdetail'] = 'Ver detalle';

// Aviso de cobertura.
$string['coveragenotice'] = 'Este registro guarda los emails que Moodle envía a través del sistema de mensajería (notificaciones de foro, tareas, insignias, eventos de calendario, mensajes privados). Moodle 5.x no ofrece ninguna forma de observar las llamadas directas a <code>email_to_user()</code> que tienen éxito, por lo que los emails de restablecimiento de contraseña, confirmación de alta y formulario de soporte solo se registran cuando fallan.';

// Ajustes.
$string['retention'] = 'Mantener registros';
$string['retention1year'] = '1 año';
$string['retention30days'] = '30 días';
$string['retention6months'] = '6 meses';
$string['retention90days'] = '90 días';
$string['retentiondesc'] = 'Cuánto tiempo se conservan los registros de emails. La tarea programada de limpieza borra todo lo más antiguo que este periodo. Elige "De por vida" para desactivar el borrado automático; ten en cuenta que el cuerpo de los emails puede contener datos personales, por lo que normalmente conviene un periodo corto.';
$string['retentionforever'] = 'De por vida';

// Tarea programada.
$string['taskcleanup'] = 'Borrar registros antiguos del registro de emails';
$string['taskcleanupdeleted'] = 'local_emaillog: se han borrado {$a} registro(s) antiguo(s).';
$string['taskcleanupforever'] = 'local_emaillog: la retención está configurada como "De por vida", no hay nada que borrar.';

// Privacidad.
$string['privacy:metadata:local_emaillog'] = 'Datos de los emails enviados por el sitio, conservados para que los administradores puedan auditar el correo saliente.';
$string['privacy:metadata:local_emaillog:attachments'] = 'Los nombres de los archivos adjuntos al email.';
$string['privacy:metadata:local_emaillog:bodyhtml'] = 'El contenido HTML del email.';
$string['privacy:metadata:local_emaillog:bodytext'] = 'El contenido en texto plano del email.';
$string['privacy:metadata:local_emaillog:emailfrom'] = 'La dirección de email desde la que se envió el mensaje.';
$string['privacy:metadata:local_emaillog:emailto'] = 'La dirección de email a la que se envió el mensaje.';
$string['privacy:metadata:local_emaillog:replyto'] = 'La dirección Reply-To del email.';
$string['privacy:metadata:local_emaillog:status'] = 'Si el email se reportó como fallido.';
$string['privacy:metadata:local_emaillog:subject'] = 'El asunto del email.';
$string['privacy:metadata:local_emaillog:timecreated'] = 'La fecha y hora en que se registró el email.';
$string['privacy:metadata:local_emaillog:useridfrom'] = 'El ID del usuario desde el que se envió el mensaje.';
$string['privacy:metadata:local_emaillog:useridto'] = 'El ID del usuario al que se envió el mensaje.';
$string['privacy:path'] = 'Registro de emails';
