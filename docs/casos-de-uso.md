# Casos de Uso — Sistema de Gestión de Asistencia

**Versión:** 1.0  
**Fecha:** 2026-06-04  
**Sistema:** Sistema de Control de Asistencia — Universidad Tecnológica

---

## Actores del Sistema

| Actor | Descripción |
|-------|-------------|
| **Super Administrador** | Control total del sistema. Gestiona usuarios, programas, cursos, sesiones, asistencias y configuración. |
| **Administrador** | Gestión académica completa (cursos, sesiones, programas, asistencias). No puede eliminar usuarios. |
| **Profesor** | Gestiona sus propios cursos y sesiones. Registra y consulta asistencias de sus materias. |
| **Estudiante** | Actor externo. Registra su asistencia mediante formulario público usando el enlace/token de la sesión. |
| **Sistema** | Actor interno. Genera tokens, envía correos, exporta reportes automáticamente. |

---

## Diagrama de Casos de Uso (texto)

```
┌─────────────────────────────────────────────────────────────────┐
│                    Sistema de Asistencia                        │
│                                                                 │
│  ┌─────────────────────┐    ┌─────────────────────────────┐    │
│  │   AUTENTICACIÓN     │    │     GESTIÓN ACADÉMICA       │    │
│  │                     │    │                             │    │
│  │  CU-01 Iniciar      │    │  CU-05 Gestionar Programas  │    │
│  │        sesión       │    │  CU-06 Gestionar Cursos     │    │
│  │  CU-02 Cerrar       │    │  CU-07 Inscribir Estudiante │    │
│  │        sesión       │    │                             │    │
│  │  CU-03 Recordarme   │    └─────────────────────────────┘    │
│  │  CU-04 Recuperar    │                                        │
│  │        contraseña   │    ┌─────────────────────────────┐    │
│  └─────────────────────┘    │     SESIONES Y ASISTENCIA   │    │
│                             │                             │    │
│  ┌─────────────────────┐    │  CU-08 Gestionar Sesiones   │    │
│  │   USUARIOS          │    │  CU-09 Registrar Asistencia │    │
│  │                     │    │  CU-10 Consultar Asistencia │    │
│  │  CU-11 Crear        │    │  CU-11 Exportar Reporte     │    │
│  │        Usuario      │    │                             │    │
│  │  CU-12 Editar       │    └─────────────────────────────┘    │
│  │        Usuario      │                                        │
│  │  CU-13 Desactivar   │    ┌─────────────────────────────┐    │
│  │        Usuario      │    │     REPORTES Y CORREO       │    │
│  └─────────────────────┘    │                             │    │
│                             │  CU-16 Exportar Datos       │    │
│                             │  CU-17 Enviar Correo        │    │
│                             │  CU-18 Ver Dashboard        │    │
│                             │                             │    │
│                             └─────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

---

## Especificación de Casos de Uso

---

### CU-01 — Iniciar Sesión

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Iniciar Sesión |
| **Actor principal** | Todos los usuarios registrados |
| **Precondiciones** | El usuario debe estar registrado y activo en el sistema |
| **Postcondiciones** | El usuario queda autenticado y es redirigido al dashboard |

**Flujo principal:**
1. El usuario accede a la URL del sistema.
2. El sistema muestra el formulario de login.
3. El usuario ingresa su nombre de usuario y contraseña.
4. El usuario hace clic en "Iniciar Sesión".
5. El sistema verifica el token CSRF.
6. El sistema comprueba las credenciales contra la base de datos (bcrypt).
7. El sistema crea la sesión con los datos del usuario.
8. El sistema redirige al dashboard según el rol.

**Flujos alternativos:**
- **FA-01-A** (Credenciales incorrectas): El sistema incrementa el contador de intentos fallidos y muestra mensaje de error genérico.
- **FA-01-B** (IP bloqueada): Tras 5 intentos en 15 min, el sistema bloquea la IP y muestra mensaje de espera.
- **FA-01-C** (Recordarme): Si el usuario marca la opción, el sistema genera un token seguro, lo almacena en BD y coloca una cookie con 30 días de vigencia.

---

### CU-02 — Cerrar Sesión

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Cerrar Sesión |
| **Actor principal** | Usuario autenticado |
| **Precondiciones** | El usuario debe estar autenticado |
| **Postcondiciones** | La sesión queda destruida y el usuario es redirigido al login |

**Flujo principal:**
1. El usuario hace clic en su nombre → "Cerrar sesión".
2. El sistema muestra diálogo de confirmación (SweetAlert2).
3. El usuario confirma.
4. El sistema invalida el token "recordarme" si existe.
5. El sistema destruye la sesión PHP.
6. El sistema redirige al login.

---

### CU-03 — Recuperar Contraseña

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Recuperar Contraseña |
| **Actor principal** | Usuario registrado |
| **Precondiciones** | El usuario debe tener un email registrado en el sistema |

**Flujo principal:**
1. El usuario hace clic en "¿Olvidó su contraseña?".
2. El sistema muestra formulario de ingreso de email.
3. El usuario ingresa su email y envía el formulario.
4. El sistema genera un token de recuperación de un solo uso con expiración de 1 hora.
5. El sistema envía el enlace de recuperación al email registrado.
6. El sistema muestra mensaje genérico (sin confirmar si el email existe).
7. El usuario accede al enlace recibido.
8. El sistema valida el token y muestra formulario de nueva contraseña.
9. El usuario ingresa nueva contraseña (mínimo 8 caracteres).
10. El sistema hashea la contraseña y actualiza la BD.
11. El sistema invalida el token de recuperación.
12. El sistema redirige al login con mensaje de éxito.

---

### CU-04 — Gestionar Programas Académicos

| Campo | Detalle |
|-------|---------|
| **ID** | CU-04 |
| **Nombre** | Gestionar Programas Académicos |
| **Actores** | Super Administrador, Administrador |
| **Precondiciones** | El actor debe estar autenticado con rol admin o super_admin |

**Flujo principal (Crear):**
1. El actor accede a Programas → "Nuevo Programa".
2. El sistema muestra el formulario con campos: código, nombre, nivel, descripción, duración.
3. El actor completa los campos y envía el formulario.
4. El sistema valida el token CSRF.
5. El sistema verifica que el código sea único.
6. El sistema crea el programa y registra en logs.
7. El sistema muestra confirmación de éxito.

**Subflujos:**
- **Editar:** El actor selecciona un programa existente, modifica los campos y guarda.
- **Desactivar:** El actor desactiva un programa (soft delete). Solo si no tiene cursos activos.
- **Listar:** Paginación con búsqueda por nombre/código.
- **Exportar:** El actor descarga la lista en Excel, PDF o CSV.

---

### CU-05 — Gestionar Cursos

| Campo | Detalle |
|-------|---------|
| **ID** | CU-05 |
| **Nombre** | Gestionar Cursos |
| **Actores** | Super Administrador, Administrador, Profesor (solo lectura/edición de los propios) |
| **Precondiciones** | El actor debe estar autenticado. Los programas deben existir. |

**Flujo principal (Crear):**
1. El actor accede a Cursos → "Nuevo Curso".
2. El sistema muestra el formulario con: código, nombre, programa, profesor, semestre, grupo, aula, sede, créditos.
3. El actor completa los campos.
4. El sistema verifica unicidad del código por período académico.
5. El sistema crea el curso y registra en logs.

**Subflujos:**
- **Editar Curso:** El actor modifica datos del curso.
- **Inscribir Estudiantes:** Asociar estudiantes al curso.
- **Ver Sesiones del Curso:** Ver todas las sesiones asociadas.
- **Exportar:** Descargar lista de cursos en Excel/PDF.

**Restricción de rol:**
- El Profesor solo visualiza y edita sus propios cursos.
- El Admin y Super Admin ven todos los cursos.

---

### CU-06 — Gestionar Sesiones de Clase

| Campo | Detalle |
|-------|---------|
| **ID** | CU-06 |
| **Nombre** | Gestionar Sesiones de Clase |
| **Actores** | Super Administrador, Administrador, Profesor |
| **Precondiciones** | Deben existir cursos registrados |

**Flujo principal (Crear sesión):**
1. El actor selecciona un curso y accede a "Nueva Sesión".
2. El sistema muestra formulario: fecha, hora inicio/fin, tema, tipo de sesión, aula.
3. El actor completa y envía el formulario.
4. El sistema genera automáticamente un **token único** (32 bytes hex) para la sesión.
5. El sistema guarda la sesión en estado "programada".
6. El sistema muestra el token/enlace que debe compartir con los estudiantes.

**Subflujos:**
- **Activar Sesión:** El actor cambia el estado a "activa". Solo sesiones activas aceptan registros de asistencia.
- **Finalizar Sesión:** El actor cierra la sesión; no se aceptan más registros.
- **Cancelar Sesión:** El actor cancela con motivo.
- **Regenerar Token:** El actor puede generar un nuevo token invalidando el anterior.
- **Exportar Asistencias:** Descarga el reporte de la sesión en Excel/PDF.

---

### CU-07 — Registrar Asistencia (Estudiante)

| Campo | Detalle |
|-------|---------|
| **ID** | CU-07 |
| **Nombre** | Registrar Asistencia |
| **Actor principal** | Estudiante (externo, sin login) |
| **Precondiciones** | La sesión debe estar activa y dentro del horario permitido |

**Flujo principal:**
1. El profesor comparte el enlace de la sesión con el token (URL pública).
2. El estudiante accede al enlace desde su dispositivo (PC o celular).
3. El sistema valida el token: debe existir, la sesión debe estar activa y en horario permitido (15 min antes hasta 30 min después del inicio).
4. El sistema muestra el formulario de registro con los datos de la sesión.
5. El estudiante ingresa: nombre completo, número de documento, código de estudiante.
6. El estudiante dibuja su firma en el canvas digital.
7. El estudiante envía el formulario.
8. El sistema verifica el token CSRF.
9. El sistema verifica que el estudiante no haya registrado ya su asistencia en esa sesión.
10. El sistema crea o actualiza el registro del estudiante.
11. El sistema registra la asistencia con: firma, IP, user-agent, timestamp.
12. El sistema inscribe al estudiante en el curso si no estaba inscrito.
13. El sistema muestra mensaje de confirmación.

**Flujos alternativos:**
- **FA-07-A** (Sesión inactiva): El sistema muestra mensaje de error y no permite el registro.
- **FA-07-B** (Fuera de horario): El sistema muestra mensaje indicando que la sesión no está disponible.
- **FA-07-C** (Registro duplicado): El sistema muestra mensaje de que ya registró asistencia.
- **FA-07-D** (Firma vacía): El sistema valida que la firma no esté en blanco antes de enviar.

---

### CU-08 — Consultar y Exportar Asistencias

| Campo | Detalle |
|-------|---------|
| **ID** | CU-08 |
| **Nombre** | Consultar y Exportar Asistencias |
| **Actores** | Super Administrador, Administrador, Profesor |
| **Precondiciones** | El actor debe estar autenticado con permiso `reportes_export` |

**Flujo principal:**
1. El actor accede a la sección de asistencias.
2. El sistema muestra la lista de asistencias con filtros: curso, fecha inicio/fin, estudiante.
3. El actor aplica filtros según su necesidad.
4. El sistema actualiza los resultados mostrando: estudiante, sesión, hora, estado.
5. El actor hace clic en "Exportar".
6. El actor selecciona el formato: Excel, PDF o CSV.
7. El sistema verifica el rate limit (máximo 10 exportaciones/hora).
8. El sistema genera el archivo con encabezado institucional.
9. El sistema descarga el archivo al dispositivo del actor.
10. El sistema registra la exportación en logs.

**Restricción de rol:**
- El Profesor solo ve y exporta asistencias de sus propios cursos.

---

### CU-09 — Enviar Reporte de Asistencia por Correo

| Campo | Detalle |
|-------|---------|
| **ID** | CU-09 |
| **Nombre** | Enviar Reporte por Correo Electrónico |
| **Actores** | Super Administrador, Administrador, Profesor |
| **Precondiciones** | El sistema debe tener configurado el SMTP. Permiso `email_send`. |

**Flujo principal:**
1. El actor selecciona la sesión o curso y hace clic en "Enviar por correo".
2. El actor ingresa el o los destinatarios.
3. El sistema verifica el rate limit (máximo 50 emails/hora).
4. El sistema genera el reporte en PDF adjunto.
5. El sistema envía el correo via PHPMailer con SMTP.
6. El sistema muestra confirmación de envío.
7. El sistema registra el evento en logs.

---

### CU-10 — Gestionar Usuarios

| Campo | Detalle |
|-------|---------|
| **ID** | CU-10 |
| **Nombre** | Gestionar Usuarios |
| **Actores** | Super Administrador (CRUD completo), Administrador (solo crear/editar) |
| **Precondiciones** | El actor debe tener permiso `usuarios_view` |

**Flujo principal:**
1. El actor accede a Usuarios.
2. El sistema muestra lista paginada con filtros: rol, estado, búsqueda.
3. El actor crea un usuario ingresando: username, nombre, email, rol, contraseña (mín. 8 chars).
4. El sistema verifica unicidad de username y email.
5. El sistema hashea la contraseña con bcrypt.
6. El sistema crea el usuario y registra en logs.

**Subflujos:**
- **Editar:** Modificar datos del usuario. La contraseña solo se actualiza si se proporciona.
- **Desactivar/Activar:** Cambiar estado activo/inactivo (soft delete).
- **Eliminar:** Solo si el usuario no tiene cursos asociados. Solo super_admin puede eliminar definitivamente.

**Restricciones:**
- Un usuario no puede desactivarse ni eliminarse a sí mismo.
- El Admin no puede eliminar usuarios.

---

### CU-11 — Ver Dashboard

| Campo | Detalle |
|-------|---------|
| **ID** | CU-11 |
| **Nombre** | Ver Dashboard |
| **Actores** | Todos los usuarios autenticados |
| **Precondiciones** | El actor debe estar autenticado con permiso `dashboard_access` |

**Flujo principal:**
1. El actor inicia sesión o accede a `/dashboard`.
2. El sistema carga estadísticas según el rol:
   - **Admin/Super Admin:** Total cursos, sesiones, estudiantes, usuarios activos, sesiones activas hoy.
   - **Profesor:** Sus cursos activos, sus sesiones del día, total estudiantes en sus cursos.
3. El sistema muestra acciones rápidas: crear sesión, ver asistencias, exportar.
4. El sistema muestra actividad reciente (logs).

---

## Matriz de Casos de Uso por Actor

| Caso de Uso | Super Admin | Admin | Profesor | Estudiante |
|-------------|:-----------:|:-----:|:--------:|:----------:|
| CU-01 Iniciar Sesión | ✓ | ✓ | ✓ | — |
| CU-02 Cerrar Sesión | ✓ | ✓ | ✓ | — |
| CU-03 Recuperar Contraseña | ✓ | ✓ | ✓ | — |
| CU-04 Gestionar Programas | ✓ | ✓ | — | — |
| CU-05 Gestionar Cursos | ✓ | ✓ | ✓ (propios) | — |
| CU-06 Gestionar Sesiones | ✓ | ✓ | ✓ (propios) | — |
| CU-07 Registrar Asistencia | — | — | — | ✓ |
| CU-08 Consultar/Exportar Asistencias | ✓ | ✓ | ✓ (propios) | — |
| CU-09 Enviar Reporte por Correo | ✓ | ✓ | ✓ | — |
| CU-10 Gestionar Usuarios | ✓ (CRUD) | ✓ (crear/editar) | — | — |
| CU-11 Ver Dashboard | ✓ | ✓ | ✓ | — |
