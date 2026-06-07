# Diccionario de Datos — Sistema de Gestión de Asistencia

**Versión:** 1.0  
**Fecha:** 2026-06-04  
**Base de datos:** `asistencia_db` (MySQL 8.0)

---

## Tabla: `roles`

**Descripción:** Define los roles del sistema con sus permisos asociados.

| Columna | Tipo | Nulo | PK/FK | Default | Descripción |
|---------|------|------|-------|---------|-------------|
| `id` | INT | NO | PK | AUTO_INCREMENT | Identificador único del rol |
| `nombre` | VARCHAR(50) | NO | UNIQUE | — | Nombre del rol: `super_admin`, `admin`, `profesor` |
| `descripcion` | TEXT | SÍ | — | NULL | Descripción del rol y sus responsabilidades |
| `permisos` | JSON | SÍ | — | NULL | Lista de permisos asociados al rol en formato JSON |
| `activo` | TINYINT(1) | NO | — | 1 | 1 = activo, 0 = inactivo |
| `created_at` | TIMESTAMP | NO | — | CURRENT_TIMESTAMP | Fecha de creación del registro |
| `updated_at` | TIMESTAMP | NO | — | CURRENT_TIMESTAMP ON UPDATE | Fecha de última modificación |

**Valores de `nombre`:**
- `super_admin`: Control total del sistema.
- `admin`: Gestión académica completa sin eliminación de usuarios.
- `profesor`: Gestión de sus propios cursos, sesiones y asistencias.

**Estructura del campo `permisos` (JSON):**
```json
["usuarios_view", "cursos_view", "sesiones_view", "asistencias_read", "reportes_export", ...]
```

---

## Tabla: `usuarios`

**Descripción:** Almacena los datos de todos los usuarios del sistema (administradores y profesores).

| Columna | Tipo | Nulo | PK/FK | Default | Descripción |
|---------|------|------|-------|---------|-------------|
| `id` | INT | NO | PK | AUTO_INCREMENT | Identificador único del usuario |
| `username` | VARCHAR(50) | NO | UNIQUE | — | Nombre de usuario para login. Solo letras, números, guiones y guiones bajos |
| `password` | VARCHAR(255) | NO | — | — | Contraseña hasheada con bcrypt (PHP `password_hash`, `PASSWORD_DEFAULT`) |
| `nombre` | VARCHAR(100) | NO | — | — | Nombre(s) del usuario |
| `apellido` | VARCHAR(100) | SÍ | — | NULL | Apellido(s) del usuario |
| `email` | VARCHAR(100) | SÍ | UNIQUE | NULL | Correo electrónico. Se usa para recuperación de contraseña |
| `telefono` | VARCHAR(20) | SÍ | — | NULL | Número de teléfono de contacto |
| `documento` | VARCHAR(20) | SÍ | — | NULL | Número de documento de identidad |
| `remember_token` | VARCHAR(64) | SÍ | INDEX | NULL | Hash SHA-256 del token "Recordarme". Nunca se almacena el token en claro |
| `remember_expires` | DATETIME | SÍ | — | NULL | Fecha y hora de expiración del token "Recordarme" |
| `rol` | VARCHAR(20) | NO | — | — | Rol del usuario. Referencia lógica a `roles.nombre` |
| `activo` | TINYINT(1) | NO | — | 1 | 1 = usuario activo, 0 = usuario desactivado (soft delete) |
| `ultimo_acceso` | TIMESTAMP | SÍ | — | NULL | Fecha y hora del último inicio de sesión exitoso |
| `created_at` | TIMESTAMP | NO | — | CURRENT_TIMESTAMP | Fecha de creación del usuario |
| `updated_at` | TIMESTAMP | NO | — | CURRENT_TIMESTAMP ON UPDATE | Fecha de última modificación |

**Reglas de negocio:**
- `username` debe ser único y tener entre 3 y 50 caracteres.
- `password` debe tener mínimo 8 caracteres antes de ser hasheada.
- `email` debe ser una dirección válida.
- Un usuario desactivado (`activo = 0`) no puede iniciar sesión.
- No se pueden eliminar usuarios con cursos activos asociados.

---

## Tabla: `programas`

**Descripción:** Programas académicos de la institución (carreras universitarias).

| Columna | Tipo | Nulo | PK/FK | Default | Descripción |
|---------|------|------|-------|---------|-------------|
| `id` | INT | NO | PK | AUTO_INCREMENT | Identificador único del programa |
| `codigo` | VARCHAR(20) | NO | UNIQUE | — | Código corto del programa. Ejemplo: `ING-SIST`, `ADM-EMP` |
| `nombre` | VARCHAR(150) | NO | UNIQUE | — | Nombre completo del programa académico |
| `descripcion` | TEXT | SÍ | — | NULL | Descripción del programa, objetivos, perfil del egresado |
| `nivel` | ENUM | NO | — | `pregrado` | Nivel académico: `pregrado`, `posgrado`, `especializacion`, `maestria`, `doctorado` |
| `duracion_semestres` | INT | SÍ | — | NULL | Número de semestres del programa |
| `activo` | TINYINT(1) | NO | — | 1 | 1 = activo, 0 = desactivado (soft delete) |
| `created_at` | TIMESTAMP | NO | — | CURRENT_TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | NO | — | CURRENT_TIMESTAMP ON UPDATE | Fecha de última modificación |

---

## Tabla: `estudiantes`

**Descripción:** Registro de estudiantes que asisten a los cursos. Se crean automáticamente al registrar la primera asistencia o manualmente por administradores.

| Columna | Tipo | Nulo | PK/FK | Default | Descripción |
|---------|------|------|-------|---------|-------------|
| `id` | INT | NO | PK | AUTO_INCREMENT | Identificador único del estudiante |
| `nombre` | VARCHAR(100) | NO | — | — | Nombre(s) del estudiante |
| `apellido` | VARCHAR(100) | SÍ | — | NULL | Apellido(s) del estudiante |
| `documento` | VARCHAR(20) | NO | UNIQUE | — | Número de documento de identidad (CC, TI, CE, etc.) |
| `tipo_documento` | ENUM | NO | — | `CC` | Tipo de documento: `CC` (Cédula), `TI` (Tarjeta Identidad), `CE` (Cédula Extranjería), `PP` (Pasaporte) |
| `codigo` | VARCHAR(20) | NO | UNIQUE | — | Código estudiantil asignado por la institución |
| `telefono` | VARCHAR(20) | SÍ | — | NULL | Número de contacto |
| `correo` | VARCHAR(100) | SÍ | — | NULL | Correo electrónico del estudiante |
| `programa_id` | INT | SÍ | FK → `programas.id` | NULL | Programa académico al que pertenece |
| `estado` | ENUM | NO | — | `activo` | Estado: `activo`, `inactivo`, `graduado`, `retirado` |
| `activo` | TINYINT(1) | NO | — | 1 | 1 = activo, 0 = desactivado |
| `created_at` | TIMESTAMP | NO | — | CURRENT_TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | NO | — | CURRENT_TIMESTAMP ON UPDATE | Fecha de última modificación |

---

## Tabla: `cursos`

**Descripción:** Cursos o materias que se dictan en un período académico determinado.

| Columna | Tipo | Nulo | PK/FK | Default | Descripción |
|---------|------|------|-------|---------|-------------|
| `id` | INT | NO | PK | AUTO_INCREMENT | Identificador único del curso |
| `codigo` | VARCHAR(20) | NO | — | — | Código de la asignatura. Ejemplo: `MAT-101` |
| `nombre` | VARCHAR(100) | NO | — | — | Nombre del curso/materia |
| `descripcion` | TEXT | SÍ | — | NULL | Descripción de la asignatura |
| `programa_id` | INT | SÍ | FK → `programas.id` | NULL | Programa académico al que pertenece el curso |
| `profesor_id` | INT | SÍ | FK → `usuarios.id` | NULL | Profesor asignado al curso |
| `area` | VARCHAR(100) | SÍ | — | NULL | Área de conocimiento. Ejemplo: `Ciencias Básicas`, `Ingeniería` |
| `semestre` | VARCHAR(20) | SÍ | — | NULL | Semestre académico. Ejemplo: `3`, `IV` |
| `grupo` | VARCHAR(20) | SÍ | — | NULL | Identificador del grupo. Ejemplo: `A`, `B`, `01` |
| `aula` | VARCHAR(20) | SÍ | — | NULL | Aula o salón asignado. Ejemplo: `201`, `Lab-3` |
| `sede` | VARCHAR(50) | SÍ | — | NULL | Sede donde se dicta el curso |
| `creditos` | INT | SÍ | — | NULL | Número de créditos académicos |
| `horas_semanales` | INT | SÍ | — | NULL | Horas de clase por semana |
| `cupo_maximo` | INT | SÍ | — | NULL | Máximo de estudiantes permitidos |
| `periodo_academico` | VARCHAR(20) | SÍ | — | NULL | Período académico. Ejemplo: `2024-1`, `2024-2` |
| `activo` | TINYINT(1) | NO | — | 1 | 1 = activo, 0 = desactivado |
| `created_at` | TIMESTAMP | NO | — | CURRENT_TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | NO | — | CURRENT_TIMESTAMP ON UPDATE | Fecha de última modificación |

**Restricción de unicidad:** El conjunto `(codigo, grupo, periodo_academico)` debe ser único.

---

## Tabla: `cursos_estudiantes`

**Descripción:** Tabla pivote que representa la relación de inscripción entre estudiantes y cursos.

| Columna | Tipo | Nulo | PK/FK | Default | Descripción |
|---------|------|------|-------|---------|-------------|
| `id` | INT | NO | PK | AUTO_INCREMENT | Identificador de la inscripción |
| `curso_id` | INT | NO | FK → `cursos.id` CASCADE | — | Curso al que está inscrito el estudiante |
| `estudiante_id` | INT | NO | FK → `estudiantes.id` CASCADE | — | Estudiante inscrito |
| `fecha_inscripcion` | TIMESTAMP | NO | — | CURRENT_TIMESTAMP | Fecha y hora de inscripción |
| `estado` | ENUM | NO | — | `inscrito` | Estado de la inscripción: `inscrito`, `retirado`, `aprobado`, `reprobado` |
| `nota_final` | DECIMAL(3,2) | SÍ | — | NULL | Nota final del curso (0.00 – 5.00) |
| `created_at` | TIMESTAMP | NO | — | CURRENT_TIMESTAMP | Fecha de creación |

**Restricción de unicidad:** `(curso_id, estudiante_id)` — un estudiante no puede estar inscrito dos veces en el mismo curso.  
**ON DELETE CASCADE:** Si se elimina un curso o estudiante, se eliminan sus inscripciones.

---

## Tabla: `sesiones`

**Descripción:** Sesiones de clase programadas para tomar asistencia.

| Columna | Tipo | Nulo | PK/FK | Default | Descripción |
|---------|------|------|-------|---------|-------------|
| `id` | INT | NO | PK | AUTO_INCREMENT | Identificador único de la sesión |
| `curso_id` | INT | NO | FK → `cursos.id` CASCADE | — | Curso al que pertenece la sesión |
| `fecha` | DATE | NO | — | — | Fecha de la sesión de clase |
| `hora_inicio` | TIME | NO | — | — | Hora de inicio de la clase |
| `hora_fin` | TIME | SÍ | — | NULL | Hora de finalización de la clase |
| `tema` | VARCHAR(200) | SÍ | — | NULL | Tema o contenido a tratar en la sesión |
| `tipo_sesion` | ENUM | NO | — | `teorica` | Tipo de sesión: `teorica`, `practica`, `laboratorio`, `examen`, `taller` |
| `estado` | ENUM | NO | — | `programada` | Estado actual: `programada`, `activa`, `finalizada`, `cancelada` |
| `token` | VARCHAR(64) | SÍ | UNIQUE | NULL | Token público de 64 caracteres hex generado con `bin2hex(random_bytes(32))` para acceso al formulario de asistencia |
| `token_expira` | TIMESTAMP | SÍ | — | NULL | Fecha/hora de expiración del token (si aplica) |
| `ubicacion` | VARCHAR(100) | SÍ | — | NULL | Ubicación específica de la sesión |
| `observaciones` | TEXT | SÍ | — | NULL | Observaciones generales de la sesión |
| `created_at` | TIMESTAMP | NO | — | CURRENT_TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | NO | — | CURRENT_TIMESTAMP ON UPDATE | Fecha de última modificación |

**Flujo de estado:** `programada` → `activa` → `finalizada` ó `cancelada`  
**Solo sesiones con estado = `activa` aceptan registros de asistencia.**

---

## Tabla: `asistencias`

**Descripción:** Registros de asistencia de cada estudiante a cada sesión de clase.

| Columna | Tipo | Nulo | PK/FK | Default | Descripción |
|---------|------|------|-------|---------|-------------|
| `id` | INT | NO | PK | AUTO_INCREMENT | Identificador único del registro |
| `sesion_id` | INT | NO | FK → `sesiones.id` CASCADE | — | Sesión a la que corresponde la asistencia |
| `estudiante_id` | INT | NO | FK → `estudiantes.id` CASCADE | — | Estudiante que registró la asistencia |
| `hora_registro` | TIMESTAMP | NO | — | CURRENT_TIMESTAMP | Fecha y hora exacta del registro de asistencia |
| `estado_asistencia` | ENUM | NO | — | `presente` | Estado: `presente`, `tardanza`, `ausente`, `justificado` |
| `minutos_tardanza` | INT | SÍ | — | 0 | Minutos de tardanza si aplica |
| `firma` | LONGTEXT | SÍ | — | NULL | Imagen de la firma digital en formato Base64 (data:image/png;base64,...) |
| `ip_registro` | VARCHAR(45) | SÍ | — | NULL | Dirección IP del dispositivo desde donde se registró. Soporta IPv6 (máx 45 chars) |
| `user_agent` | TEXT | SÍ | — | NULL | User-Agent del navegador/dispositivo |
| `observaciones` | TEXT | SÍ | — | NULL | Observaciones o justificación del estado |
| `created_at` | TIMESTAMP | NO | — | CURRENT_TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | NO | — | CURRENT_TIMESTAMP ON UPDATE | Fecha de última modificación |

**Restricción de unicidad:** `(sesion_id, estudiante_id)` — un estudiante no puede registrarse más de una vez por sesión.  
**ON DELETE CASCADE:** Si se elimina una sesión o estudiante, se eliminan sus asistencias.

---

## Tabla: `logs_sistema`

**Descripción:** Registro de auditoría de todas las acciones relevantes realizadas en el sistema.

| Columna | Tipo | Nulo | PK/FK | Default | Descripción |
|---------|------|------|-------|---------|-------------|
| `id` | INT | NO | PK | AUTO_INCREMENT | Identificador único del log |
| `usuario_id` | INT | SÍ | FK → `usuarios.id` SET NULL | NULL | Usuario que realizó la acción. NULL si el usuario fue eliminado |
| `accion` | VARCHAR(100) | NO | — | — | Acción realizada. Ejemplos: `login`, `login_failed`, `create`, `update`, `delete`, `export` |
| `tabla_afectada` | VARCHAR(50) | SÍ | — | NULL | Tabla de la BD que fue afectada por la acción |
| `registro_id` | INT | SÍ | — | NULL | ID del registro afectado en la tabla |
| `datos_anteriores` | JSON | SÍ | — | NULL | Estado del registro antes del cambio (para operaciones update/delete) |
| `datos_nuevos` | JSON | SÍ | — | NULL | Estado del registro después del cambio (para operaciones create/update) |
| `ip_address` | VARCHAR(45) | SÍ | — | NULL | Dirección IP desde donde se realizó la acción |
| `user_agent` | TEXT | SÍ | — | NULL | User-Agent del navegador |
| `created_at` | TIMESTAMP | NO | — | CURRENT_TIMESTAMP | Fecha y hora de la acción |

**Acciones registradas comunes:**

| Acción | Descripción |
|--------|-------------|
| `login` | Inicio de sesión exitoso |
| `login_failed` | Intento de login fallido |
| `logout` | Cierre de sesión |
| `password_reset_requested` | Solicitud de recuperación de contraseña |
| `password_reset_completed` | Contraseña restablecida |
| `create` | Creación de cualquier registro |
| `update` | Modificación de cualquier registro |
| `delete` | Eliminación o desactivación de registro |
| `asistencia_registrada` | Registro de asistencia de estudiante |
| `usuario_created` | Usuario nuevo creado |
| `usuario_updated` | Usuario modificado |
| `usuario_status_changed` | Estado de usuario cambiado |
| `session_regenerated` | ID de sesión PHP regenerado |

---

## Glosario de Términos

| Término | Definición |
|---------|------------|
| **Sesión de clase** | Encuentro académico programado entre un profesor y sus estudiantes para un curso específico. Diferente a la sesión PHP. |
| **Token de sesión** | Código único generado para cada sesión de clase que permite a los estudiantes acceder al formulario de asistencia sin autenticarse. |
| **Firma digital** | Imagen de la firma manuscrita del estudiante capturada mediante el pad táctil o ratón, almacenada en Base64. No tiene validez legal como firma electrónica certificada. |
| **Soft Delete** | Técnica que desactiva un registro (`activo = 0`) en lugar de eliminarlo físicamente, preservando el historial y la integridad referencial. |
| **bcrypt** | Algoritmo de hash unidireccional usado para almacenar contraseñas de forma segura. PHP usa `PASSWORD_DEFAULT` que apunta a bcrypt. |
| **CSRF Token** | Token único por sesión PHP que se incluye en los formularios para prevenir ataques Cross-Site Request Forgery. |
| **RBAC** | Role-Based Access Control. Sistema de control de acceso basado en el rol del usuario para determinar qué acciones puede realizar. |
| **Prepared Statement** | Consulta SQL parametrizada que previene inyecciones SQL separando los datos del código SQL. |
| **Rate Limiting** | Límite máximo de operaciones en un período de tiempo. Ejemplo: máximo 10 exportaciones por hora. |
| **IP bloqueada** | Dirección IP que es rechazada temporalmente tras múltiples intentos fallidos de autenticación. |
| **Remember Token** | Token aleatorio criptográficamente seguro guardado en cookie y en BD para autenticar automáticamente al usuario en visitas futuras. |
| **Período académico** | Identificador del semestre o período en que se dicta un curso. Ejemplo: `2024-1`, `2024-2`. |
| **User-Agent** | Cadena de texto enviada por el navegador que identifica el tipo de dispositivo, sistema operativo y versión del navegador. |
