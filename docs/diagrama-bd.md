# Diagrama de Base de Datos — Sistema de Gestión de Asistencia

**Versión:** 1.0  
**Fecha:** 2026-06-04  
**Base de datos:** `asistencia_db` (MySQL 8.0)

---

## Diagrama Entidad-Relación (ERD)

> Renderizable en cualquier editor compatible con Mermaid (VS Code + extensión, GitHub, GitLab, Notion, etc.)

```mermaid
erDiagram
    roles {
        INT id PK "AUTO_INCREMENT"
        VARCHAR_50 nombre UK "super_admin|admin|profesor"
        TEXT descripcion
        JSON permisos
        TINYINT activo "DEFAULT 1"
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    usuarios {
        INT id PK "AUTO_INCREMENT"
        VARCHAR_50 username UK "NOT NULL"
        VARCHAR_255 password "bcrypt hash"
        VARCHAR_100 nombre "NOT NULL"
        VARCHAR_100 apellido
        VARCHAR_100 email UK
        VARCHAR_20 telefono
        VARCHAR_20 documento
        VARCHAR_64 remember_token
        DATETIME remember_expires
        VARCHAR_10 rol "FK → roles.nombre"
        TINYINT activo "DEFAULT 1"
        TIMESTAMP ultimo_acceso
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    programas {
        INT id PK "AUTO_INCREMENT"
        VARCHAR_20 codigo UK "NOT NULL"
        VARCHAR_150 nombre UK "NOT NULL"
        TEXT descripcion
        ENUM nivel "pregrado|posgrado|especializacion|maestria|doctorado"
        INT duracion_semestres
        TINYINT activo "DEFAULT 1"
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    estudiantes {
        INT id PK "AUTO_INCREMENT"
        VARCHAR_100 nombre "NOT NULL"
        VARCHAR_100 apellido
        VARCHAR_20 documento UK "NOT NULL"
        ENUM tipo_documento "CC|TI|CE|PP"
        VARCHAR_20 codigo UK "NOT NULL"
        VARCHAR_20 telefono
        VARCHAR_100 correo
        INT programa_id FK
        ENUM estado "activo|inactivo|graduado|retirado"
        TINYINT activo "DEFAULT 1"
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    cursos {
        INT id PK "AUTO_INCREMENT"
        VARCHAR_20 codigo "NOT NULL"
        VARCHAR_100 nombre "NOT NULL"
        TEXT descripcion
        INT programa_id FK
        INT profesor_id FK
        VARCHAR_100 area
        VARCHAR_20 semestre
        VARCHAR_20 grupo
        VARCHAR_20 aula
        VARCHAR_50 sede
        INT creditos
        INT horas_semanales
        INT cupo_maximo
        VARCHAR_20 periodo_academico
        TINYINT activo "DEFAULT 1"
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    cursos_estudiantes {
        INT id PK
        INT curso_id FK "NOT NULL"
        INT estudiante_id FK "NOT NULL"
        TIMESTAMP fecha_inscripcion
        ENUM estado "inscrito|retirado|aprobado|reprobado"
        DECIMAL nota_final
        TIMESTAMP created_at
    }

    sesiones {
        INT id PK "AUTO_INCREMENT"
        INT curso_id FK "NOT NULL"
        DATE fecha "NOT NULL"
        TIME hora_inicio "NOT NULL"
        TIME hora_fin
        VARCHAR_200 tema
        ENUM tipo_sesion "teorica|practica|laboratorio|examen|taller"
        ENUM estado "programada|activa|finalizada|cancelada"
        VARCHAR_64 token UK "Token público para asistencia"
        TIMESTAMP token_expira
        VARCHAR_100 ubicacion
        TEXT observaciones
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    asistencias {
        INT id PK "AUTO_INCREMENT"
        INT sesion_id FK "NOT NULL"
        INT estudiante_id FK "NOT NULL"
        TIMESTAMP hora_registro
        ENUM estado_asistencia "presente|tardanza|ausente|justificado"
        INT minutos_tardanza "DEFAULT 0"
        LONGTEXT firma "Base64 PNG"
        VARCHAR_45 ip_registro
        TEXT user_agent
        TEXT observaciones
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    logs_sistema {
        INT id PK "AUTO_INCREMENT"
        INT usuario_id FK "NULL → SET NULL on delete"
        VARCHAR_100 accion "NOT NULL"
        VARCHAR_50 tabla_afectada
        INT registro_id
        JSON datos_anteriores
        JSON datos_nuevos
        VARCHAR_45 ip_address
        TEXT user_agent
        TIMESTAMP created_at
    }

    roles ||--o{ usuarios : "tiene"
    programas ||--o{ estudiantes : "pertenece_a"
    programas ||--o{ cursos : "pertenece_a"
    usuarios ||--o{ cursos : "enseña (profesor_id)"
    cursos ||--o{ cursos_estudiantes : "tiene"
    estudiantes ||--o{ cursos_estudiantes : "inscrito_en"
    cursos ||--o{ sesiones : "tiene"
    sesiones ||--o{ asistencias : "registra"
    estudiantes ||--o{ asistencias : "registrada_para"
    usuarios ||--o{ logs_sistema : "genera"
```

---

## Descripción de Relaciones

| Relación | Cardinalidad | Descripción |
|----------|-------------|-------------|
| `roles` → `usuarios` | 1:N | Un rol puede tener múltiples usuarios |
| `programas` → `cursos` | 1:N | Un programa puede tener múltiples cursos |
| `programas` → `estudiantes` | 1:N | Un programa puede tener múltiples estudiantes |
| `usuarios` → `cursos` | 1:N | Un profesor (usuario) puede tener múltiples cursos asignados |
| `cursos` ↔ `estudiantes` | N:M | Un curso tiene muchos estudiantes y un estudiante puede estar en muchos cursos (tabla pivote: `cursos_estudiantes`) |
| `cursos` → `sesiones` | 1:N | Un curso puede tener múltiples sesiones de clase |
| `sesiones` → `asistencias` | 1:N | Una sesión puede tener múltiples registros de asistencia |
| `estudiantes` → `asistencias` | 1:N | Un estudiante puede tener múltiples registros de asistencia |
| `usuarios` → `logs_sistema` | 1:N | Un usuario puede generar múltiples entradas de log |

---

## Índices Definidos

| Tabla | Columna(s) | Tipo | Propósito |
|-------|-----------|------|-----------|
| `usuarios` | `username` | UNIQUE | Búsqueda y unicidad de login |
| `usuarios` | `email` | UNIQUE | Unicidad de email |
| `usuarios` | `remember_token` | INDEX | Autenticación por cookie |
| `programas` | `codigo` | UNIQUE | Unicidad del código de programa |
| `programas` | `nombre` | UNIQUE | Unicidad del nombre de programa |
| `estudiantes` | `documento` | UNIQUE | Identificación del estudiante |
| `estudiantes` | `codigo` | UNIQUE | Código único de estudiante |
| `cursos` | `(codigo, grupo, periodo_academico)` | UNIQUE | Unicidad de sección de curso |
| `cursos` | `profesor_id` | INDEX | Búsqueda de cursos por profesor |
| `cursos` | `programa_id` | INDEX | Búsqueda de cursos por programa |
| `cursos_estudiantes` | `(curso_id, estudiante_id)` | UNIQUE | Previene inscripción duplicada |
| `sesiones` | `token` | UNIQUE | Acceso por token de sesión |
| `sesiones` | `(fecha, estado)` | INDEX | Búsqueda de sesiones activas por fecha |
| `asistencias` | `(sesion_id, estudiante_id)` | UNIQUE | Previene asistencia duplicada |
| `logs_sistema` | `(accion, created_at)` | INDEX | Consulta de logs por acción |

---

## Diagrama de Flujo de Datos Simplificado

```
ESTUDIANTE                          SISTEMA                         BD
    │                                  │                              │
    │── GET /asistencia?token=XXX ───►│                              │
    │                                  │── SELECT sesiones ──────────►│
    │                                  │◄── sesion activa ────────────│
    │◄── Formulario de registro ───────│                              │
    │                                  │                              │
    │── POST datos + firma ──────────►│                              │
    │                                  │── SELECT estudiantes ───────►│
    │                                  │── INSERT/UPDATE estudiante ─►│
    │                                  │── INSERT asistencia ─────────►│
    │                                  │── INSERT cursos_estudiantes ─►│
    │◄── Confirmación ─────────────────│                              │

PROFESOR                            SISTEMA                         BD
    │                                  │                              │
    │── POST crear sesión ───────────►│                              │
    │                                  │── generate token (hex32) ────│
    │                                  │── INSERT sesiones ──────────►│
    │◄── enlace con token ─────────────│                              │
    │                                  │                              │
    │── GET exportar ────────────────►│                              │
    │                                  │── SELECT asistencias JOIN ──►│
    │                                  │── generate Excel/PDF ────────│
    │◄── archivo descargado ───────────│                              │
```

---

## Notas de Diseño

1. **Soft Delete:** Las tablas `usuarios`, `estudiantes`, `programas` y `cursos` usan el campo `activo` para deshabilitar registros sin eliminarlos físicamente, preservando la integridad referencial y el historial de logs.

2. **Firma Digital:** El campo `firma` en `asistencias` almacena la imagen PNG de la firma en formato Base64 (LONGTEXT). No se almacena como archivo físico para simplificar el backup y la portabilidad.

3. **Token de Sesión:** El campo `token` en `sesiones` es generado con `bin2hex(random_bytes(32))` (64 caracteres hex). Es de un solo uso por sesión y puede regenerarse invalidando el anterior.

4. **Remember Token:** El campo `remember_token` en `usuarios` almacena el hash SHA-256 del token enviado en la cookie, nunca el token en claro.

5. **Auditoría:** La tabla `logs_sistema` usa `ON DELETE SET NULL` en `usuario_id` para conservar el historial aunque el usuario sea desactivado.
