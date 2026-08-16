# plugin-packaging-docs Specification

## Purpose

Definir la documentación de empaquetado, distribución e instalación del plugin `local_emaillog`: el `README.md` de la raíz del repositorio como puerta de entrada del proyecto, el contenido exacto del ZIP de instalación, el procedimiento automatizado que lo genera y lo publica como release de GitHub, y las instrucciones de instalación en Moodle por los distintos caminos.

## Requirements

### Requirement: README en la raíz del repositorio

El repositorio SHALL incluir un fichero `README.md` en su raíz, escrito en español, que sirva como puerta de entrada del proyecto y describa el plugin `local_emaillog`. Dado que el plugin ocupa la raíz del repositorio, este README SHALL ser además la documentación que viaja dentro del ZIP de instalación.

#### Scenario: El README existe y se renderiza como portada

- **WHEN** una persona abre el repositorio en GitHub o lista la raíz del proyecto
- **THEN** encuentra un fichero `README.md` en la raíz
- **AND** su primer encabezado identifica el plugin por su nombre de componente `local_emaillog` y describe en una frase para qué sirve

#### Scenario: Distinción entre el plugin y el andamiaje de desarrollo

- **WHEN** la persona lee la sección de estructura del repositorio
- **THEN** el README indica que el plugin ocupa la raíz del repositorio, siguiendo la convención de los plugins de Moodle
- **AND** señala explícitamente qué rutas NO viajan dentro del ZIP: `.github/`, `openspec/`, `.claude/`, `.agent/` y `.codex/`

### Requirement: Resumen funcional del plugin

El `README.md` de la raíz SHALL resumir la información que un administrador necesita antes de instalar el plugin, sin obligarle a abrir otros ficheros.

#### Scenario: Versión y compatibilidad declaradas

- **WHEN** la persona busca si el plugin es compatible con su Moodle
- **THEN** el README indica la release del plugin, su `version`, su madurez y el requisito `requires`
- **AND** indica en qué versiones de Moodle está probado
- **AND** esos valores coinciden con los declarados en `version.php`

#### Scenario: Funcionalidades principales enumeradas

- **WHEN** la persona lee la sección de funcionalidades
- **THEN** el README describe la captura de emails salientes, el visor con filtros en **Administración del sitio → Informes → Registro de emails**, el enlace al formulario de edición de perfil para corregir direcciones, la política de retención con purga programada diaria, la capacidad `local/emaillog:view` y la implementación de la Privacy API

#### Scenario: La limitación de captura es visible

- **WHEN** la persona lee el README de arriba abajo
- **THEN** encuentra, en una sección propia y destacada, la advertencia de que las llamadas directas a `email_to_user()` que **tienen éxito** no se registran (restablecimiento de contraseña, confirmación de alta, formulario de soporte)
- **AND** que sus fallos sí se registran
- **AND** que el estado "Desconocido" significa "no se detectó ningún fallo", no "entregado"

#### Scenario: Historial de versiones accesible

- **WHEN** la persona quiere saber qué cambió entre versiones
- **THEN** el README enlaza a `CHANGELOG.md`

### Requirement: Especificación del contenido del ZIP de instalación

El `README.md` de la raíz SHALL especificar de forma completa y verificable qué contiene el ZIP de instalación del plugin y qué queda fuera.

#### Scenario: Estructura obligatoria del ZIP

- **WHEN** la persona lee la sección de empaquetado
- **THEN** el README indica que el ZIP debe contener **un único directorio en su raíz**, llamado exactamente `emaillog`
- **AND** aclara que ese nombre debe coincidir con `$plugin->component` sin el prefijo `local_`, y que el ZIP no debe contener ningún nivel `local/`

#### Scenario: Estructura del plugin documentada

- **WHEN** la persona quiere saber qué hace cada fichero del plugin
- **THEN** el README incluye un árbol de la estructura del repositorio con una descripción por fichero o directorio
- **AND** el árbol distingue visualmente los ficheros del plugin de los que quedan excluidos del ZIP

#### Scenario: Las exclusiones son declarativas, no manuales

- **WHEN** se quiere saber qué queda fuera del ZIP
- **THEN** la lista de exclusiones vive en `.gitattributes` mediante `export-ignore`, como única fuente de verdad
- **AND** el README remite a ese fichero en lugar de duplicar la lista

### Requirement: Generación reproducible del ZIP

El ZIP de instalación SHALL generarse con `git archive --prefix=emaillog/`, de forma que su contenido quede determinado por el contenido versionado y por las marcas `export-ignore` de `.gitattributes`.

#### Scenario: Comando documentado para uso local

- **WHEN** la persona quiere construir el ZIP en su máquina sin pasar por GitHub
- **THEN** el README documenta un comando `git archive --format=zip --prefix=emaillog/` y un `unzip -l` para inspeccionarlo

#### Scenario: El ZIP nunca arrastra ficheros sin versionar

- **WHEN** el directorio de trabajo contiene ficheros sin versionar como `.DS_Store` o ZIPs de pruebas anteriores
- **THEN** el ZIP generado no los incluye, porque `git archive` solo lee del árbol de git

#### Scenario: Nombre del fichero ZIP

- **WHEN** se nombra el fichero resultante
- **THEN** el nombre incluye el número de `$plugin->version`, con el formato `local_emaillog_<version>.zip`
- **AND** el README aclara que Moodle no valida el nombre del fichero ZIP, solo el del directorio que contiene

### Requirement: Publicación automática de releases en GitHub

El repositorio SHALL publicar una release de GitHub con el ZIP adjunto cada vez que se empuje un tag de versión, sin intervención manual en la construcción del paquete.

#### Scenario: Publicación al empujar un tag

- **WHEN** se empuja un tag que empieza por `v`
- **THEN** un workflow de GitHub Actions construye el ZIP y crea la release con el ZIP como adjunto
- **AND** la descripción de la release incluye instrucciones de instalación

#### Scenario: El tag debe coincidir con version.php

- **WHEN** el tag empujado no corresponde a `v` seguido de `$plugin->release` tal y como está en `version.php`
- **THEN** el workflow falla con un mensaje que indica ambos valores
- **AND** no se publica ninguna release

#### Scenario: Verificación de la estructura antes de publicar

- **WHEN** el workflow ha construido el ZIP
- **THEN** comprueba que tiene un único directorio raíz llamado `emaillog`, que contiene `emaillog/version.php`, y que no contiene material de desarrollo, `__MACOSX/` ni `.DS_Store`
- **AND** falla sin publicar si alguna comprobación no se cumple

#### Scenario: Las versiones no estables se marcan como prerelease

- **WHEN** `$plugin->maturity` es `MATURITY_ALPHA` o `MATURITY_BETA`
- **THEN** la release se publica marcada como *prerelease*

### Requirement: Instrucciones de instalación

El `README.md` de la raíz SHALL documentar los caminos de instalación disponibles, con los pasos posteriores comunes.

#### Scenario: Instalación desde la interfaz de Moodle

- **WHEN** la persona instala el plugin subiendo el ZIP de una release
- **THEN** el README indica la ruta **Administración del sitio → Extensiones → Instalar módulos externos**
- **AND** indica que debe seleccionarse el tipo de plugin "Plugin local (local)"
- **AND** menciona que el directorio de destino debe tener permisos de escritura para el servidor web, y qué alternativa usar si no los tiene

#### Scenario: Instalación clonando el repositorio

- **WHEN** la persona prefiere instalar y actualizar con git
- **THEN** el README documenta clonar el repositorio directamente en el directorio de destino del plugin
- **AND** indica que actualizar es después un `git pull`

#### Scenario: La ruta de destino depende de la versión de Moodle

- **WHEN** se indica dónde deben quedar los ficheros en el servidor
- **THEN** el README indica que en Moodle 5.0 el destino es `<moodleroot>/local/emaillog`
- **AND** que en Moodle 5.1 y 5.2, donde el core vive bajo `public/`, el destino es `<moodleroot>/public/local/emaillog`

#### Scenario: Pasos posteriores a la instalación

- **WHEN** los ficheros ya están en su sitio por cualquiera de los caminos
- **THEN** el README indica que hay que visitar la página de notificaciones para ejecutar la instalación de la base de datos
- **AND** que la retención se ajusta en **Administración del sitio → Extensiones → Plugins locales → Registro de emails**
- **AND** que el registro se consulta en **Administración del sitio → Informes → Registro de emails**
