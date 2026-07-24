# log-retention

## ADDED Requirements

### Requirement: Configuración del periodo de retención
El plugin SHALL ofrecer en su página de configuración (Administración del sitio → Extensiones → Extensiones locales → Registro de emails) un ajuste "Mantener registros" con las opciones: 30 días, 90 días, 6 meses, 1 año y De por vida. El valor por defecto SHALL ser 6 meses.

#### Scenario: Cambiar la retención
- **WHEN** el administrador selecciona "30 días" y guarda la configuración
- **THEN** el ajuste queda persistido y la próxima purga usa ese periodo

#### Scenario: Retención de por vida
- **WHEN** el administrador selecciona "De por vida"
- **THEN** la tarea de purga no elimina ningún registro

### Requirement: Purga automática programada
Una scheduled task SHALL ejecutarse diariamente (por defecto de madrugada) y SHALL eliminar los registros cuyo timestamp sea anterior al periodo de retención configurado. La tarea SHALL registrar en su salida cuántos registros eliminó.

#### Scenario: Purga con registros antiguos
- **WHEN** la retención es 30 días y existen registros de hace 45 días
- **THEN** la ejecución de la tarea elimina esos registros y conserva los de los últimos 30 días

#### Scenario: Purga sin registros antiguos
- **WHEN** no existe ningún registro más antiguo que el periodo configurado
- **THEN** la tarea termina sin eliminar nada e informa de 0 registros purgados

#### Scenario: Administrador ajusta la programación
- **WHEN** el administrador edita la scheduled task desde Administración del sitio → Servidor → Tareas programadas
- **THEN** puede cambiar la frecuencia/hora de ejecución con la interfaz estándar de Moodle
