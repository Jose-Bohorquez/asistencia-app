# Requisitos del Sistema — Sistema de Gestión de Asistencia

**Versión:** 1.0  
**Fecha:** 2026-06-04  
**Sistema:** Sistema de Control de Asistencia — Universidad Tecnológica

---

## 1. Requisitos Funcionales

### RF-01 — Autenticación y Control de Acceso

| ID | Descripción | Prioridad |
|----|-------------|-----------|
| RF-01.1 | El sistema debe permitir el inicio de sesión mediante usuario y contraseña. | Alta |
| RF-01.2 | El sistema debe validar las credenciales usando hash bcrypt. | Alta |
| RF-01.3 | El sistema debe generar y validar tokens CSRF en todos los formularios POST. | Alta |
| RF-01.4 | El sistema debe bloquear temporalmente (15 min) las IPs con 5 o más intentos de login fallidos en esa ventana de tiempo. | Alta |
| RF-01.5 | El sistema debe permitir la opción "Recordarme" mediante una cookie segura con vigencia de 30 días. | Media |
| RF-01.6 | El sistema debe permitir la recuperación de contraseña mediante enlace enviado al correo registrado, con expiración de 1 hora. | Media |
| RF-01.7 | El sistema debe cerrar la sesión destruyendo completamente los datos de sesión y cookies asociadas. | Alta |
| RF-01.8 | El sistema debe cerrar automáticamente las sesiones inactivas según el rol (Profesor: 1h, Admin: 1.5h, Super Admin: 2h). | Alta |
| RF-01.9 | El sistema debe regenerar el ID de sesión periódicamente (cada 30 minutos) y al iniciar sesión. | Alta |

---

### RF-02 — Gestión de Usuarios

| ID | Descripción | Prioridad |
|----|-------------|-----------|
| RF-02.1 | El sistema debe permitir crear usuarios con los campos: username, contraseña, nombre, email, rol. | Alta |
| RF-02.2 | El sistema debe validar que el username y el email sean únicos en la base de datos. | Alta |
| RF-02.3 | El sistema debe requerir una contraseña de mínimo 8 caracteres al crear o actualizar. | Alta |
| RF-02.4 | El sistema debe permitir a los administradores editar los datos de un usuario (excepto la contraseña si no se proporciona). | Alta |
| RF-02.5 | El sistema debe implementar eliminación suave (soft delete): desactivar usuarios, no borrarlos físicamente. | Media |
| RF-02.6 | El sistema debe impedir que un usuario se desactive o elimine a sí mismo. | Alta |
| RF-02.7 | El sistema debe impedir eliminar un usuario que tenga cursos activos asociados. | Media |
| RF-02.8 | El sistema debe permitir listar usuarios con paginación y filtros por rol, estado y búsqueda por texto. | Media |
| RF-02.9 | El sistema debe registrar en logs todas las creaciones, modificaciones y cambios de estado de usuarios. | Alta |

---

### RF-03 — Gestión de Programas Académicos

| ID | Descripción | Prioridad |
|----|-------------|-----------|
| RF-03.1 | El sistema debe permitir crear programas con: código único, nombre, nivel (pregrado/posgrado/especialización/maestría/doctorado), descripción, duración en semestres. | Alta |
| RF-03.2 | El sistema debe validar que el código del programa sea único. | Alta |
| RF-03.3 | El sistema debe permitir editar y desactivar programas. | Alta |
| RF-03.4 | El sistema debe mostrar estadísticas por programa: número de cursos, estudiantes y profesores asociados. | Media |
| RF-03.5 | El sistema debe permitir exportar la lista de programas en Excel, PDF y CSV. | Media |

---

### RF-04 — Gestión de Cursos

| ID | Descripción | Prioridad |
|----|-------------|-----------|
| RF-04.1 | El sistema debe permitir crear cursos con: código, nombre, programa, área, semestre, grupo, aula, sede, créditos, horas semanales, cupo, período académico y profesor asignado. | Alta |
| RF-04.2 | El sistema debe validar que el código del curso sea único por período académico y grupo. | Alta |
| RF-04.3 | El sistema debe permitir inscribir y desinscribir estudiantes de un curso. | Alta |
| RF-04.4 | Los profesores solo deben poder ver y editar sus propios cursos. | Alta |
| RF-04.5 | El sistema debe permitir exportar el listado de cursos y sus estudiantes inscritos. | Media |

---

### RF-05 — Gestión de Sesiones de Clase

| ID | Descripción | Prioridad |
|----|-------------|-----------|
| RF-05.1 | El sistema debe permitir crear sesiones con: fecha, hora inicio/fin, tema, tipo (teórica/práctica/laboratorio/examen/taller), observaciones. | Alta |
| RF-05.2 | El sistema debe generar automáticamente un token único de 32 bytes (hex) al crear cada sesión. | Alta |
| RF-05.3 | El sistema debe gestionar el estado de la sesión: programada → activa → finalizada/cancelada. | Alta |
| RF-05.4 | El sistema debe permitir al profesor activar/finalizar/cancelar sus propias sesiones. | Alta |
| RF-05.5 | El sistema debe permitir regenerar el token de la sesión, invalidando el anterior. | Media |
| RF-05.6 | El sistema debe mostrar el enlace con el token para compartir con estudiantes. | Alta |
| RF-05.7 | Solo las sesiones en estado "activa" deben aceptar registros de asistencia. | Alta |
| RF-05.8 | El sistema debe registrar el horario de cada sesión y calcular si está dentro del rango permitido para asistencia. | Alta |

---

### RF-06 — Registro de Asistencia

| ID | Descripción | Prioridad |
|----|-------------|-----------|
| RF-06.1 | El sistema debe proporcionar un formulario público (sin login) accesible mediante el token de la sesión. | Alta |
| RF-06.2 | El sistema debe validar que el token de la sesión sea válido y que la sesión esté activa. | Alta |
| RF-06.3 | El sistema debe validar que el momento del registro esté dentro del horario permitido (15 min antes — fin de sesión + 30 min). | Alta |
| RF-06.4 | El formulario debe capturar: nombre, número de documento, código de estudiante, teléfono (opcional), correo (opcional). | Alta |
| RF-06.5 | El formulario debe incluir un pad de firma digital y validar que la firma no esté en blanco. | Alta |
| RF-06.6 | El sistema debe registrar la firma digital como imagen en base64. | Alta |
| RF-06.7 | El sistema debe prevenir registros duplicados (mismo estudiante, misma sesión). | Alta |
| RF-06.8 | El sistema debe registrar la IP y el User-Agent del dispositivo del estudiante. | Media |
| RF-06.9 | El sistema debe inscribir automáticamente al estudiante en el curso si aún no está inscrito. | Media |
| RF-06.10 | El formulario debe ser completamente responsivo y funcionar en dispositivos móviles. | Alta |

---

### RF-07 — Consulta y Exportación de Reportes

| ID | Descripción | Prioridad |
|----|-------------|-----------|
| RF-07.1 | El sistema debe permitir filtrar asistencias por curso, fecha inicio/fin y estudiante. | Alta |
| RF-07.2 | El sistema debe calcular estadísticas: total presentes/ausentes, porcentaje de asistencia por sesión y por estudiante. | Alta |
| RF-07.3 | El sistema debe exportar reportes en Excel (.xlsx), PDF y CSV. | Alta |
| RF-07.4 | El sistema debe aplicar un límite de 10 exportaciones por hora por usuario. | Media |
| RF-07.5 | Los reportes de exportación deben incluir encabezado institucional con nombre del curso, profesor, fecha y período. | Media |
| RF-07.6 | Los profesores solo deben poder exportar asistencias de sus propios cursos. | Alta |

---

### RF-08 — Envío de Correos Electrónicos

| ID | Descripción | Prioridad |
|----|-------------|-----------|
| RF-08.1 | El sistema debe permitir enviar reportes de asistencia por correo en formato PDF adjunto. | Media |
| RF-08.2 | El sistema debe soportar múltiples proveedores SMTP: Gmail, Outlook, Hostinger. | Media |
| RF-08.3 | El sistema debe permitir probar la configuración SMTP enviando un correo de prueba. | Media |
| RF-08.4 | El sistema debe aplicar un límite de 50 correos por hora por usuario. | Media |
| RF-08.5 | El sistema debe registrar en logs todos los correos enviados. | Baja |

---

### RF-09 — Dashboard y Actividad

| ID | Descripción | Prioridad |
|----|-------------|-----------|
| RF-09.1 | El dashboard debe mostrar estadísticas generales según el rol del usuario. | Alta |
| RF-09.2 | El dashboard debe mostrar las sesiones activas del día actual. | Alta |
| RF-09.3 | El dashboard debe mostrar los accesos rápidos a las funciones más usadas. | Media |
| RF-09.4 | El sistema debe registrar en logs todas las acciones relevantes (crear, editar, eliminar, login, logout, exportar). | Alta |

---

## 2. Requisitos No Funcionales

### RNF-01 — Seguridad

| ID | Descripción | Prioridad |
|----|-------------|-----------|
| RNF-01.1 | Todas las contraseñas deben almacenarse con hash bcrypt (PASSWORD_DEFAULT de PHP). | Alta |
| RNF-01.2 | Las credenciales de base de datos NO deben estar hardcodeadas en el código fuente; deben cargarse desde variables de entorno o archivo de configuración gitignoreado. | Alta |
| RNF-01.3 | Todas las consultas SQL deben usar Prepared Statements para prevenir inyección SQL. | Alta |
| RNF-01.4 | Todos los datos de entrada del usuario deben ser sanitizados antes de mostrarse en HTML (`htmlspecialchars`). | Alta |
| RNF-01.5 | El sistema debe enviar cabeceras HTTP de seguridad: `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Content-Security-Policy`. | Alta |
| RNF-01.6 | Las cookies de sesión deben tener los flags: `HttpOnly`, `SameSite=Lax`. En entornos HTTPS debe además activarse `Secure`. | Alta |
| RNF-01.7 | El acceso directo por HTTP a los directorios `app/`, `config/` y `vendor/` debe estar bloqueado mediante `.htaccess`. | Alta |
| RNF-01.8 | Los mensajes de error mostrados al usuario deben ser genéricos; el detalle técnico solo debe registrarse en `error_log`. | Alta |
| RNF-01.9 | El sistema de control de acceso basado en roles (RBAC) debe verificarse en cada solicitud, sin excepciones ni fallbacks permisivos. | Alta |
| RNF-01.10 | El token "recordarme" debe almacenarse hasheado (SHA-256) en la base de datos. | Alta |

---

### RNF-02 — Rendimiento

| ID | Descripción | Prioridad |
|----|-------------|-----------|
| RNF-02.1 | El sistema debe responder en menos de 2 segundos para operaciones normales (listar, crear, editar). | Alta |
| RNF-02.2 | La generación de reportes Excel o PDF de hasta 500 registros debe completarse en menos de 10 segundos. | Media |
| RNF-02.3 | El formulario de asistencia debe cargarse en menos de 3 segundos en conexiones móviles de 4G. | Alta |
| RNF-02.4 | Las consultas frecuentes deben estar optimizadas con índices en las columnas: `token` (sesiones), `documento` (estudiantes), `username` y `email` (usuarios). | Alta |

---

### RNF-03 — Usabilidad

| ID | Descripción | Prioridad |
|----|-------------|-----------|
| RNF-03.1 | La interfaz debe ser completamente responsiva y funcionar en dispositivos móviles, tablets y escritorio. | Alta |
| RNF-03.2 | El pad de firma digital debe adaptarse al tamaño de la pantalla del dispositivo. | Alta |
| RNF-03.3 | Los mensajes de error y éxito deben ser claros y visibles para el usuario. | Alta |
| RNF-03.4 | El formulario público de asistencia no debe requerir registro ni autenticación del estudiante. | Alta |
| RNF-03.5 | Las acciones destructivas (eliminar, cancelar) deben solicitar confirmación antes de ejecutarse. | Alta |
| RNF-03.6 | La interfaz debe soportar los navegadores: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+. | Media |

---

### RNF-04 — Disponibilidad y Mantenibilidad

| ID | Descripción | Prioridad |
|----|-------------|-----------|
| RNF-04.1 | El sistema debe estar disponible durante las horas lectivas de la institución (7am – 10pm). | Alta |
| RNF-04.2 | El sistema debe funcionar en entornos Docker para facilitar el despliegue reproducible. | Media |
| RNF-04.3 | El código debe seguir el patrón MVC para facilitar el mantenimiento y la extensión. | Alta |
| RNF-04.4 | Todos los cambios a la base de datos deben documentarse en el archivo `migration.sql`. | Media |
| RNF-04.5 | Las dependencias externas deben gestionarse con Composer. | Media |

---

### RNF-05 — Compatibilidad y Portabilidad

| ID | Descripción | Prioridad |
|----|-------------|-----------|
| RNF-05.1 | El sistema debe funcionar con PHP 8.0 o superior. | Alta |
| RNF-05.2 | El sistema debe funcionar con MySQL 5.7 o superior (recomendado 8.0). | Alta |
| RNF-05.3 | El sistema debe funcionar con Apache 2.4+ con `mod_rewrite` habilitado. | Alta |
| RNF-05.4 | El sistema debe poder desplegarse en entornos XAMPP (desarrollo local), Docker (staging) y Hostinger (producción). | Media |

---

### RNF-06 — Escalabilidad

| ID | Descripción | Prioridad |
|----|-------------|-----------|
| RNF-06.1 | La arquitectura debe permitir agregar nuevos módulos sin modificar el código existente. | Media |
| RNF-06.2 | El sistema de middleware debe ser extensible para agregar nuevas capas de seguridad o validación. | Media |
| RNF-06.3 | El sistema de roles y permisos debe permitir agregar nuevos roles y permisos sin cambios estructurales en el código. | Media |
| RNF-06.4 | Los modelos deben poder extenderse para nuevas entidades reutilizando `BaseModel`. | Media |

---

## 3. Restricciones del Sistema

| ID | Restricción |
|----|-------------|
| REST-01 | El sistema no implementa reconocimiento facial ni verificación biométrica (fuera del alcance v1.0). |
| REST-02 | No existe app móvil nativa; el sistema opera exclusivamente como aplicación web responsiva. |
| REST-03 | La firma digital no tiene validez legal por sí sola; es un mecanismo de presencia, no de firma electrónica certificada. |
| REST-04 | El sistema no se integra con plataformas externas de gestión académica (ERP, SIS) en esta versión. |
| REST-05 | El rate limiting de exportaciones y correos se implementa con archivos temporales; en producción de alta carga se recomienda Redis. |
| REST-06 | Las contraseñas de aplicación para SMTP de Gmail deben configurarse externamente por el administrador. |
