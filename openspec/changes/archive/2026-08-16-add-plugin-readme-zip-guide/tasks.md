## 1. Recopilar datos verificados

- [x] 1.1 Ejecutar `find local/emaillog -type f | sort` y guardar la salida como fuente del listado de ficheros del ZIP (19 ficheros esperados)
- [x] 1.2 Leer `local/emaillog/version.php` y anotar `component`, `version`, `requires`, `maturity` y `release` para citarlos literalmente en el README
- [x] 1.3 Abrir cada fichero del plugin lo justo para redactar su línea de descripción en el árbol del ZIP (qué hace `lib.php`, `index.php`, `view.php`, cada clase, cada fichero de `db/`)
- [x] 1.4 Releer `local/emaillog/README.md` para condensar sin contradecirlo: captura, limitación de `email_to_user()`, retención, privacidad

## 2. Escribir el README de la raíz

- [x] 2.1 Crear `README.md` en la raíz con título e introducción de una frase que identifique `local_emaillog`
- [x] 2.2 Añadir la sección de compatibilidad y versión con los valores anotados en 1.2
- [x] 2.3 Añadir la sección de funcionalidades: captura, visor con filtros en Informes, retención con purga diaria, capacidad `local/emaillog:view`, Privacy API
- [x] 2.4 Añadir la sección destacada de limitaciones (los `email_to_user()` con éxito no se registran; sus fallos sí; el estado "Desconocido" significa "sin fallo detectado"), colocada antes de las instrucciones de instalación
- [x] 2.5 Añadir la sección de estructura del repositorio distinguiendo `local/emaillog/` (distribuible) de `openspec/`, `.claude/`, `.agent/` y `.codex/` (no distribuible)

## 3. Escribir la sección de empaquetado ZIP

- [x] 3.1 Documentar la estructura obligatoria: un único directorio raíz llamado `emaillog`, sin el nivel `local/`, y por qué (validador de Moodle y `$plugin->component`)
- [x] 3.2 Escribir el árbol con los 19 ficheros de 1.1, cada uno con su descripción breve de 1.3
- [x] 3.3 Documentar las exclusiones: `.git/`, `openspec/`, `.claude/`, `.agent/`, `.codex/`, `.DS_Store`, `__MACOSX/`, con la advertencia sobre comprimir desde el Finder de macOS
- [x] 3.4 Documentar el comando de empaquetado (`cd local && zip -r ../local_emaillog_2026072500.zip emaillog -x '*.DS_Store' -x '__MACOSX/*'`) y la convención de nombre del fichero
- [x] 3.5 Documentar el comando de verificación `unzip -l` y qué comprobar en su salida: un solo directorio raíz, presencia de `emaillog/version.php`, ausencia de `__MACOSX/`

## 4. Escribir la sección de instalación

- [x] 4.1 Documentar la instalación desde la interfaz: **Administración del sitio → Extensiones → Instalar módulos externos**, tipo "Plugin local (local)", con la nota sobre permisos de escritura
- [x] 4.2 Documentar la instalación manual con las dos rutas de destino según versión (`local/emaillog` en 5.0, `public/local/emaillog` en 5.1 y 5.2)
- [x] 4.3 Documentar los pasos posteriores comunes: página de notificaciones, ajuste de retención en Plugins locales → Email log, consulta del registro en Informes → Email log
- [x] 4.4 Añadir el enlace a `local/emaillog/README.md` y la nota de licencia (GNU GPL v3 o posterior)

## 5. Verificar

- [x] 5.1 Contrastar el árbol del README con una nueva ejecución de `find local/emaillog -type f | sort`: deben coincidir fichero a fichero
- [x] 5.2 Ejecutar realmente el comando de empaquetado de 3.4 en el directorio de trabajo temporal y comprobar con `unzip -l` que la estructura es la documentada; borrar el ZIP de prueba después
- [x] 5.3 Comprobar que las cifras de versión del README coinciden con `local/emaillog/version.php`
- [x] 5.4 Revisar que todos los enlaces relativos del README resuelven y que no se ha modificado ningún fichero bajo `local/emaillog/`
