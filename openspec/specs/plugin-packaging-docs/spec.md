# plugin-packaging-docs Specification

## Purpose

Definir la documentación de empaquetado e instalación del plugin `local_emaillog`: el `README.md` de la raíz del repositorio como puerta de entrada del proyecto, el contenido exacto del ZIP de instalación, el procedimiento reproducible para generarlo y verificarlo, y las instrucciones de instalación en Moodle por ambos caminos (subida del ZIP e instalación manual).

## Requirements

### Requirement: README en la raíz del repositorio

El repositorio SHALL incluir un fichero `README.md` en su raíz, escrito en español, que sirva como puerta de entrada del proyecto y describa el plugin `local_emaillog`.

#### Scenario: El README existe y se renderiza como portada

- **WHEN** una persona abre el repositorio en GitHub o lista la raíz del proyecto
- **THEN** encuentra un fichero `README.md` en la raíz
- **AND** su primer encabezado identifica el plugin por su nombre de componente `local_emaillog` y describe en una frase para qué sirve

#### Scenario: Distinción entre el plugin y el andamiaje de planificación

- **WHEN** la persona lee la sección de estructura del repositorio
- **THEN** el README indica que el plugin distribuible es exclusivamente `local/emaillog/`
- **AND** indica que `openspec/`, `.claude/`, `.agent/` y `.codex/` son material de planificación y configuración de agentes que no forma parte del plugin

### Requirement: Resumen funcional del plugin

El `README.md` de la raíz SHALL resumir la información que un administrador necesita antes de instalar el plugin, sin obligarle a abrir otros ficheros.

#### Scenario: Versión y compatibilidad declaradas

- **WHEN** la persona busca si el plugin es compatible con su Moodle
- **THEN** el README indica la release del plugin (`0.1.0`), su `version` (`2026072500`), su madurez (ALPHA) y el requisito `requires = 2025041400`
- **AND** indica que está probado en Moodle 5.0, 5.1 y 5.2
- **AND** esos valores coinciden con los declarados en `local/emaillog/version.php`

#### Scenario: Funcionalidades principales enumeradas

- **WHEN** la persona lee la sección de funcionalidades
- **THEN** el README describe la captura de emails salientes, el visor con filtros en **Administración del sitio → Informes → Email log**, la política de retención con purga programada diaria, la capacidad `local/emaillog:view` y la implementación de la Privacy API

#### Scenario: La limitación de captura es visible antes de instalar

- **WHEN** la persona lee el README de arriba abajo
- **THEN** encuentra, en una sección propia y antes de las instrucciones de instalación, la advertencia de que las llamadas directas a `email_to_user()` que **tienen éxito** no se registran (restablecimiento de contraseña, confirmación de alta, formulario de soporte)
- **AND** que sus fallos sí se registran
- **AND** que el estado "Desconocido" significa "no se detectó ningún fallo", no "entregado"

#### Scenario: Enlace a la documentación detallada

- **WHEN** la persona quiere el detalle técnico completo
- **THEN** el README enlaza a `local/emaillog/README.md` como documentación funcional extendida

### Requirement: Especificación del contenido del ZIP de instalación

El `README.md` de la raíz SHALL especificar de forma completa y verificable qué contiene el ZIP de instalación del plugin.

#### Scenario: Estructura obligatoria del ZIP

- **WHEN** la persona lee la sección de empaquetado
- **THEN** el README indica que el ZIP debe contener **un único directorio en su raíz**, llamado exactamente `emaillog`
- **AND** explica que ese nombre debe coincidir con `$plugin->component` sin el prefijo `local_`
- **AND** advierte de que el ZIP no debe contener el nivel `local/` que sí existe en el repositorio

#### Scenario: Listado exacto de ficheros incluidos

- **WHEN** la persona quiere verificar que un ZIP está completo
- **THEN** el README lista, en forma de árbol, los 19 ficheros que debe contener el ZIP bajo `emaillog/`: `README.md`, `index.php`, `lib.php`, `settings.php`, `version.php`, `view.php`, `classes/observer.php`, `classes/form/filter_form.php`, `classes/local/detail.php`, `classes/local/logger.php`, `classes/privacy/provider.php`, `classes/table/emaillog_table.php`, `classes/task/cleanup.php`, `db/access.php`, `db/events.php`, `db/install.xml`, `db/tasks.php`, `lang/en/local_emaillog.php` y `lang/es/local_emaillog.php`
- **AND** cada fichero va acompañado de una explicación breve de su función
- **AND** el listado coincide exactamente con la salida de `find local/emaillog -type f | sort`

#### Scenario: Exclusiones declaradas

- **WHEN** la persona prepara el ZIP
- **THEN** el README indica explícitamente que no deben incluirse `.git/`, `openspec/`, `.claude/`, `.agent/`, `.codex/`, ficheros `.DS_Store` ni el directorio `__MACOSX/`
- **AND** advierte de que comprimir desde el Finder de macOS introduce `__MACOSX/` y `.DS_Store`

### Requirement: Procedimiento reproducible de generación y verificación del ZIP

El `README.md` de la raíz SHALL incluir comandos ejecutables que produzcan un ZIP válido y permitan comprobarlo antes de subirlo a Moodle.

#### Scenario: Comando de empaquetado

- **WHEN** la persona ejecuta el comando de empaquetado documentado desde la raíz del repositorio
- **THEN** el comando se ejecuta desde `local/` comprimiendo la ruta relativa `emaillog`, excluyendo `.DS_Store` y `__MACOSX/`
- **AND** produce un fichero ZIP cuyo único directorio raíz es `emaillog/`

#### Scenario: Comando de verificación

- **WHEN** la persona quiere comprobar el ZIP antes de subirlo
- **THEN** el README documenta un comando (`unzip -l`) para listar su contenido
- **AND** indica qué debe observarse en la salida: un solo directorio raíz `emaillog/`, la presencia de `emaillog/version.php` y la ausencia de `__MACOSX/`

#### Scenario: Nombre del fichero ZIP

- **WHEN** la persona nombra el fichero resultante
- **THEN** el README propone `local_emaillog_2026072500.zip` incluyendo el número de versión
- **AND** aclara que Moodle no valida el nombre del fichero ZIP, solo el del directorio que contiene

### Requirement: Instrucciones de instalación por ambos caminos

El `README.md` de la raíz SHALL documentar la instalación mediante subida del ZIP y la instalación manual, con los pasos posteriores comunes.

#### Scenario: Instalación desde la interfaz de Moodle

- **WHEN** la persona instala el plugin subiendo el ZIP
- **THEN** el README indica la ruta **Administración del sitio → Extensiones → Instalar módulos externos**
- **AND** indica que debe seleccionarse el tipo de plugin "Plugin local (local)"
- **AND** menciona que el directorio de destino debe tener permisos de escritura para el servidor web, y que si no los tiene debe usarse la instalación manual

#### Scenario: Instalación manual según la versión de Moodle

- **WHEN** la persona descomprime el plugin a mano en el servidor
- **THEN** el README indica que en Moodle 5.0 el destino es `<moodleroot>/local/emaillog`
- **AND** que en Moodle 5.1 y 5.2, donde el core vive bajo `public/`, el destino es `<moodleroot>/public/local/emaillog`

#### Scenario: Pasos posteriores a la instalación

- **WHEN** los ficheros ya están en su sitio por cualquiera de los dos caminos
- **THEN** el README indica que hay que visitar la página de notificaciones para ejecutar la instalación de la base de datos
- **AND** que la retención se ajusta en **Administración del sitio → Extensiones → Plugins locales → Email log**
- **AND** que el registro se consulta en **Administración del sitio → Informes → Email log**
