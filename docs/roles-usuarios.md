# Roles, Usuarios y Acceso — Sistema de Gestión de Asistencia

**Versión:** 1.0 | **Fecha:** 2026-06-04

---

## ¿Cómo accedo al sistema?

```
URL local (Docker):  http://localhost:8080/public/
URL local (XAMPP):   http://localhost/asistencia-app/public/
```

Ingresa tu **usuario** y **contraseña** en el formulario de login.

---

## Usuarios de prueba (cargados en asistencia_db.sql)

| Usuario | Contraseña | Rol | Qué puede hacer |
|---------|-----------|-----|-----------------|
| `superadmin` | `admin123` | Super Administrador | **Todo** sin restricciones |
| `admin` | `admin123` | Administrador | Gestión académica completa (sin eliminar) |
| `profesor1` | `admin123` | Profesor | Solo sus propios cursos y sesiones |

> Cambia estas contraseñas inmediatamente en un entorno real.

---

## Los 3 Roles del Sistema

### SUPER ADMINISTRADOR
El rol con más poder. Pensado para el responsable técnico o director del sistema.

**Puede hacer TODO, incluyendo:**
- Crear, editar y **eliminar** usuarios
- Crear, editar y **eliminar** programas, cursos, sesiones y asistencias
- Ver los **logs de auditoría** del sistema
- Configurar el servidor de correo SMTP
- **Impersonar** a otros usuarios (entrar como si fuera ese usuario)
- Ver estadísticas globales de toda la institución

---

### ADMINISTRADOR
Para el personal académico administrativo. Tiene casi todo el poder, excepto eliminar y gestionar usuarios avanzado.

**Puede:**
- Ver y editar usuarios (pero **no crear ni eliminar**)
- Crear y editar programas, cursos, sesiones, asistencias
- Ver reportes de toda la institución
- Exportar cualquier dato (Excel, PDF, CSV)
- Enviar reportes por correo

**No puede:**
- Eliminar programas, cursos, sesiones ni asistencias
- Crear o eliminar usuarios
- Ver logs del sistema
- Configurar el servidor de correo

---

### PROFESOR
Para los docentes. Solo ve y gestiona lo que es suyo.

**Puede:**
- Ver **sus propios cursos** (solo los que él dicta)
- Crear y gestionar **sus propias sesiones** de clase
- Ver y exportar las **asistencias de sus cursos**
- Enviar reportes por correo de sus sesiones
- Ver el dashboard con sus estadísticas propias

**No puede:**
- Ver cursos de otros profesores
- Acceder al módulo de Usuarios
- Acceder al módulo de Programas
- Eliminar nada

---

## Tabla completa de permisos

| Acción | super_admin | admin | profesor |
|--------|:-----------:|:-----:|:--------:|
| **USUARIOS** | | | |
| Ver lista | ✅ | ✅ | ❌ |
| Crear | ✅ | ❌ | ❌ |
| Editar | ✅ | ✅ | ❌ |
| Eliminar / desactivar | ✅ | ✅ (desactivar) | ❌ |
| **PROGRAMAS** | | | |
| Ver | ✅ | ✅ | ❌ |
| Crear / Editar | ✅ | ✅ | ❌ |
| Eliminar | ✅ | ❌ | ❌ |
| **CURSOS** | | | |
| Ver todos | ✅ | ✅ | ❌ |
| Ver los propios | ✅ | ✅ | ✅ |
| Crear / Editar | ✅ | ✅ | ✅ (propios) |
| Eliminar | ✅ | ❌ | ❌ |
| Inscribir estudiantes | ✅ | ✅ | ✅ (propios) |
| **SESIONES** | | | |
| Ver todas | ✅ | ✅ | ❌ |
| Ver las propias | ✅ | ✅ | ✅ |
| Crear | ✅ | ✅ | ✅ (propios) |
| Editar | ✅ | ✅ | ✅ (propios) |
| Activar / Finalizar | ✅ | ✅ | ✅ (propios) |
| Eliminar | ✅ | ❌ | ❌ |
| **ASISTENCIAS** | | | |
| Ver todas | ✅ | ✅ | ❌ |
| Ver las propias | ✅ | ✅ | ✅ |
| Editar | ✅ | ✅ | ✅ (propios) |
| Eliminar | ✅ | ❌ | ❌ |
| **REPORTES Y EXPORTACIÓN** | | | |
| Exportar todo | ✅ | ✅ | ❌ |
| Exportar solo lo propio | ✅ | ✅ | ✅ |
| Enviar por correo | ✅ | ✅ | ✅ |
| **SISTEMA** | | | |
| Ver logs de auditoría | ✅ | ❌ | ❌ |
| Configurar SMTP | ✅ | ❌ | ❌ |
| Dashboard global | ✅ | ✅ | ❌ |
| Dashboard propio | ✅ | ✅ | ✅ |

---

## Restricciones especiales

```
Cualquier rol:
  └─ No puede desactivarse ni eliminarse a sí mismo

admin:
  └─ No puede crear usuarios (solo super_admin puede crear)

profesor:
  └─ Al entrar a "Cursos" solo ve los cursos donde figura como profesor asignado
  └─ Al entrar a "Sesiones" solo ve las sesiones de sus cursos
  └─ El menú de Usuarios y Programas no aparece en su navegación
```

---

## ¿Qué ve cada rol al entrar?

### super_admin y admin — Menú completo
```
[Dashboard]  [Cursos]  [Sesiones]  [Usuarios]  [Programas]
                                       ↑              ↑
                               Solo admin+      Solo admin+
```

### profesor — Menú reducido
```
[Dashboard]  [Cursos]  [Sesiones]
                          ↑
               Solo sus sesiones
```

---

## ¿Cómo registra asistencia un estudiante?

Los estudiantes **NO tienen usuario en el sistema**. El flujo es:

```
1. Profesor activa una sesión de clase
         ↓
2. El sistema genera un enlace único con token:
   http://localhost:8080/public/?page=asistencia&token=a3f9b2e1...
         ↓
3. El profesor comparte el enlace (WhatsApp, proyector, QR, etc.)
         ↓
4. El estudiante abre el enlace desde su celular o PC
   (SIN necesidad de login, sin cuenta en el sistema)
         ↓
5. El estudiante completa el formulario:
   - Nombre completo
   - Número de documento (cédula, TI, etc.)
   - Código estudiantil
   - Firma digital en pantalla
         ↓
6. El sistema registra la asistencia con:
   firma + IP + hora exacta + user-agent
```

**Restricciones del formulario público:**
- Solo acepta registros si la sesión está en estado **"activa"**
- Solo acepta registros dentro del horario: 15 min antes del inicio hasta 30 min después del fin
- Un estudiante no puede registrarse dos veces en la misma sesión

---

## Seguridad de acceso

- Tras **5 intentos fallidos** de login desde la misma IP → bloqueo de **15 minutos**
- Las sesiones expiran automáticamente por inactividad:
  - Profesor: **1 hora**
  - Admin: **1.5 horas**
  - Super Admin: **2 horas**
- La opción **"Recordarme"** guarda una cookie segura con vigencia de **30 días**
- Las contraseñas se almacenan con **bcrypt** — nunca en texto plano
