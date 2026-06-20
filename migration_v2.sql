-- ================================================================
-- SGAA v2.0 — Migration SQL  (MySQL 8.0 compatible, idempotente)
-- ================================================================

USE asistencia_db;

-- ============================================================
-- FASE 1: Correcciones críticas de diseño
-- ============================================================

-- 1a. firma_path en asistencias
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA='asistencia_db' AND TABLE_NAME='asistencias' AND COLUMN_NAME='firma_path');
SET @sql = IF(@col=0,
    "ALTER TABLE asistencias ADD COLUMN firma_path VARCHAR(255) NULL COMMENT 'Ruta relativa al PNG en public/uploads/firmas/' AFTER firma",
    'SELECT "firma_path ya existe" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 1b. Catálogo de estados de asistencia
CREATE TABLE IF NOT EXISTS estados_asistencia (
    id          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(50)  NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL,
    activo      TINYINT(1)   DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO estados_asistencia (nombre, descripcion) VALUES
('presente',    'Estudiante asistió a la sesión'),
('tardanza',    'Estudiante llegó tarde'),
('ausente',     'Estudiante no asistió'),
('justificado', 'Ausencia justificada con soporte documental');

-- 1c. estado_id FK en asistencias
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA='asistencia_db' AND TABLE_NAME='asistencias' AND COLUMN_NAME='estado_id');
SET @sql = IF(@col=0,
    'ALTER TABLE asistencias ADD COLUMN estado_id TINYINT UNSIGNED NULL AFTER estado_asistencia',
    'SELECT "estado_id ya existe" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA='asistencia_db' AND TABLE_NAME='asistencias' AND CONSTRAINT_NAME='fk_asistencias_estado');
SET @sql = IF(@fk=0,
    'ALTER TABLE asistencias ADD CONSTRAINT fk_asistencias_estado FOREIGN KEY (estado_id) REFERENCES estados_asistencia(id)',
    'SELECT "FK ya existe" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE asistencias a
INNER JOIN estados_asistencia ea ON ea.nombre = a.estado_asistencia
SET a.estado_id = ea.id
WHERE a.estado_id IS NULL;

-- 1d. Tabla tokens_asistencia
CREATE TABLE IF NOT EXISTS tokens_asistencia (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    sesion_id        INT         NOT NULL,
    token            VARCHAR(64) NOT NULL UNIQUE,
    creado_por       INT         NULL,
    fecha_creacion   TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME    NULL,
    estado           ENUM('activo','usado','expirado') DEFAULT 'activo',
    usos             INT         DEFAULT 0,
    max_usos         INT         NULL COMMENT 'NULL = ilimitado',
    FOREIGN KEY (sesion_id)  REFERENCES sesiones(id) ON DELETE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO tokens_asistencia (sesion_id, token, estado)
SELECT id, token, IF(estado='activa','activo','expirado')
FROM sesiones WHERE token IS NOT NULL;

-- ============================================================
-- FASE 2: Entidades faltantes esenciales
-- ============================================================

-- 2a. Facultades
CREATE TABLE IF NOT EXISTS facultades (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(150) NOT NULL UNIQUE,
    codigo     VARCHAR(20)  NOT NULL UNIQUE,
    decano     VARCHAR(100) NULL,
    email      VARCHAR(100) NULL,
    activo     TINYINT(1)   DEFAULT 1,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO facultades (nombre, codigo) VALUES
('FACULTAD DE INGENIERÍA',          'FING'),
('FACULTAD DE CIENCIAS ECONÓMICAS', 'FECO'),
('FACULTAD DE CIENCIAS JURÍDICAS',  'FDER');

-- 2b. facultad_id en programas
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA='asistencia_db' AND TABLE_NAME='programas' AND COLUMN_NAME='facultad_id');
SET @sql = IF(@col=0,
    'ALTER TABLE programas ADD COLUMN facultad_id INT NULL AFTER codigo',
    'SELECT "facultad_id ya existe" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA='asistencia_db' AND TABLE_NAME='programas' AND CONSTRAINT_NAME='fk_programas_facultad');
SET @sql = IF(@fk=0,
    'ALTER TABLE programas ADD CONSTRAINT fk_programas_facultad FOREIGN KEY (facultad_id) REFERENCES facultades(id)',
    'SELECT "FK ya existe" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE programas SET facultad_id=(SELECT id FROM facultades WHERE codigo='FING')
WHERE codigo IN ('PGIS','PGII','MIS') AND facultad_id IS NULL;
UPDATE programas SET facultad_id=(SELECT id FROM facultades WHERE codigo='FECO')
WHERE codigo IN ('PGAE','PGCP','EGP') AND facultad_id IS NULL;
UPDATE programas SET facultad_id=(SELECT id FROM facultades WHERE codigo='FDER')
WHERE codigo='PGD' AND facultad_id IS NULL;

-- 2c. Períodos académicos
CREATE TABLE IF NOT EXISTS periodos_academicos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(50) NOT NULL UNIQUE,
    anio         YEAR        NOT NULL,
    semestre     TINYINT     NOT NULL COMMENT '1 o 2',
    fecha_inicio DATE        NULL,
    fecha_fin    DATE        NULL,
    activo       TINYINT(1)  DEFAULT 0,
    created_at   TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    CHECK (semestre IN (1,2))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO periodos_academicos (nombre, anio, semestre, fecha_inicio, fecha_fin, activo) VALUES
('2025-1', 2025, 1, '2025-01-27', '2025-06-14', 0),
('2025-2', 2025, 2, '2025-07-21', '2025-11-29', 0),
('2026-1', 2026, 1, '2026-01-26', '2026-06-13', 1);

-- 2d. Agregar periodo_id a cursos_estudiantes
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA='asistencia_db' AND TABLE_NAME='cursos_estudiantes' AND COLUMN_NAME='periodo_id');
SET @sql = IF(@col=0,
    'ALTER TABLE cursos_estudiantes ADD COLUMN periodo_id INT NULL AFTER nota_final',
    'SELECT "periodo_id ya existe" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA='asistencia_db' AND TABLE_NAME='cursos_estudiantes' AND CONSTRAINT_NAME='fk_ce_periodo');
SET @sql = IF(@fk=0,
    'ALTER TABLE cursos_estudiantes ADD CONSTRAINT fk_ce_periodo FOREIGN KEY (periodo_id) REFERENCES periodos_academicos(id)',
    'SELECT "FK ya existe" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE cursos_estudiantes
SET periodo_id=(SELECT id FROM periodos_academicos WHERE activo=1 LIMIT 1)
WHERE periodo_id IS NULL;

-- 2e. Renombrar cursos_estudiantes → matriculas
SET @tbl = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA='asistencia_db' AND TABLE_NAME='cursos_estudiantes');
SET @sql = IF(@tbl>0,
    'RENAME TABLE cursos_estudiantes TO matriculas',
    'SELECT "Tabla ya renombrada" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Actualizar unique key para incluir periodo_id
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA='asistencia_db' AND TABLE_NAME='matriculas' AND INDEX_NAME='curso_id');
SET @sql = IF(@idx>0,
    'ALTER TABLE matriculas DROP INDEX `curso_id`, ADD UNIQUE KEY `uk_matricula` (`curso_id`,`estudiante_id`,`periodo_id`)',
    'SELECT "uk_matricula ya configurado" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2f. Docentes (perfil académico de profesores)
CREATE TABLE IF NOT EXISTS docentes (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id   INT          NOT NULL UNIQUE,
    titulo       VARCHAR(100) NULL,
    especialidad VARCHAR(150) NULL,
    departamento VARCHAR(100) NULL,
    escalafon    VARCHAR(50)  NULL,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO docentes (usuario_id)
SELECT id FROM usuarios WHERE rol='profesor';

-- 2g. Horarios de clase
CREATE TABLE IF NOT EXISTS horarios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    curso_id    INT         NOT NULL,
    dia_semana  TINYINT     NOT NULL COMMENT '1=Lunes … 7=Domingo',
    hora_inicio TIME        NOT NULL,
    hora_fin    TIME        NOT NULL,
    aula        VARCHAR(30) NULL,
    sede        VARCHAR(50) NULL,
    activo      TINYINT(1)  DEFAULT 1,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    CHECK (dia_semana BETWEEN 1 AND 7)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- FASE 3: Roles RBAC
-- ============================================================

CREATE TABLE IF NOT EXISTS roles (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(50)  NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL,
    activo      TINYINT(1)   DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO roles (nombre, descripcion) VALUES
('super_admin', 'Acceso total al sistema'),
('admin',       'Administración general sin logs ni backup'),
('profesor',    'Gestión de sus propios cursos y sesiones'),
('coordinador', 'Coordinación de programas académicos'),
('secretaria',  'Consulta y apoyo administrativo');

CREATE TABLE IF NOT EXISTS permisos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    codigo      VARCHAR(80)  NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL,
    modulo      VARCHAR(50)  NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO permisos (codigo, descripcion, modulo) VALUES
('usuarios.ver',          'Ver listado de usuarios',         'usuarios'),
('usuarios.crear',        'Crear usuarios',                  'usuarios'),
('usuarios.editar',       'Editar usuarios',                 'usuarios'),
('usuarios.eliminar',     'Eliminar usuarios',               'usuarios'),
('cursos.ver',            'Ver cursos',                      'cursos'),
('cursos.crear',          'Crear cursos',                    'cursos'),
('cursos.editar',         'Editar cursos',                   'cursos'),
('cursos.eliminar',       'Eliminar cursos',                 'cursos'),
('sesiones.ver',          'Ver sesiones',                    'sesiones'),
('sesiones.crear',        'Crear sesiones',                  'sesiones'),
('sesiones.gestionar',    'Activar/finalizar/cancelar',      'sesiones'),
('asistencias.ver',       'Ver registros',                   'asistencias'),
('asistencias.registrar', 'Registrar asistencias',           'asistencias'),
('reportes.ver',          'Ver reportes',                    'reportes'),
('reportes.exportar',     'Exportar a Excel/PDF',            'reportes'),
('sistema.logs',          'Ver logs del sistema',            'sistema'),
('sistema.backup',        'Backup y restauración',           'sistema'),
('sistema.config',        'Configuración del sistema',       'sistema');

CREATE TABLE IF NOT EXISTS rol_permisos (
    rol_id     INT NOT NULL,
    permiso_id INT NOT NULL,
    PRIMARY KEY (rol_id, permiso_id),
    FOREIGN KEY (rol_id)     REFERENCES roles(id)    ON DELETE CASCADE,
    FOREIGN KEY (permiso_id) REFERENCES permisos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO rol_permisos (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r, permisos p WHERE r.nombre='super_admin';

INSERT IGNORE INTO rol_permisos (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p
    ON p.codigo NOT IN ('sistema.logs','sistema.backup','sistema.config')
WHERE r.nombre='admin';

INSERT IGNORE INTO rol_permisos (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p
    ON p.codigo IN ('cursos.ver','sesiones.ver','sesiones.crear','sesiones.gestionar',
                    'asistencias.ver','asistencias.registrar','reportes.ver','reportes.exportar')
WHERE r.nombre='profesor';

CREATE TABLE IF NOT EXISTS usuario_roles (
    usuario_id   INT NOT NULL,
    rol_id       INT NOT NULL,
    asignado_en  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    asignado_por INT NULL,
    PRIMARY KEY (usuario_id, rol_id),
    FOREIGN KEY (usuario_id)   REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (rol_id)       REFERENCES roles(id)    ON DELETE CASCADE,
    FOREIGN KEY (asignado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO usuario_roles (usuario_id, rol_id)
SELECT u.id, r.id FROM usuarios u JOIN roles r ON r.nombre=u.rol;

-- ============================================================
-- FASE 4: Auditoría real
-- ============================================================

CREATE TABLE IF NOT EXISTS auditoria (
    id               BIGINT AUTO_INCREMENT PRIMARY KEY,
    usuario_id       INT          NULL,
    accion           VARCHAR(100) NOT NULL,
    tabla_afectada   VARCHAR(60)  NULL,
    registro_id      INT          NULL,
    datos_anteriores JSON         NULL,
    datos_nuevos     JSON         NULL,
    ip               VARCHAR(45)  NULL,
    user_agent       TEXT         NULL,
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_usuario (usuario_id),
    INDEX idx_audit_tabla   (tabla_afectada, registro_id),
    INDEX idx_audit_fecha   (created_at),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- FASE 5: Índices de rendimiento
-- ============================================================

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA='asistencia_db' AND TABLE_NAME='asistencias' AND INDEX_NAME='idx_asistencias_created');
SET @sql = IF(@idx=0,'CREATE INDEX idx_asistencias_created ON asistencias(created_at)','SELECT "idx ok" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA='asistencia_db' AND TABLE_NAME='sesiones' AND INDEX_NAME='idx_sesiones_fecha');
SET @sql = IF(@idx=0,'CREATE INDEX idx_sesiones_fecha ON sesiones(fecha)','SELECT "idx ok" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA='asistencia_db' AND TABLE_NAME='matriculas' AND INDEX_NAME='idx_matriculas_periodo');
SET @sql = IF(@idx=0,'CREATE INDEX idx_matriculas_periodo ON matriculas(periodo_id)','SELECT "idx ok" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ============================================================
-- Verificación final
-- ============================================================
SELECT 'MIGRACIÓN v2.0 COMPLETADA' AS resultado;
SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA='asistencia_db' ORDER BY TABLE_NAME;
