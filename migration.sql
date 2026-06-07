-- Migración para actualizar la estructura de la base de datos
USE asistencia_db;

-- Agregar columna email a usuarios
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'asistencia_db' AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'email') = 0,
    'ALTER TABLE usuarios ADD COLUMN email VARCHAR(100) AFTER nombre',
    'SELECT "Column email already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar columna activo a usuarios
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'asistencia_db' AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'activo') = 0,
    'ALTER TABLE usuarios ADD COLUMN activo BOOLEAN DEFAULT TRUE AFTER rol',
    'SELECT "Column activo already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar columna updated_at a usuarios
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'asistencia_db' AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'updated_at') = 0,
    'ALTER TABLE usuarios ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
    'SELECT "Column updated_at already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Modificar el enum de rol para incluir super_admin
ALTER TABLE usuarios MODIFY COLUMN rol ENUM('super_admin', 'admin', 'profesor') NOT NULL;

-- Crear tabla programas si no existe
CREATE TABLE IF NOT EXISTS programas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL UNIQUE,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar programas de ejemplo
INSERT INTO programas (nombre, codigo) VALUES 
('PREGRADO INGENIERIA DE SISTEMAS', 'PGIS'),
('PREGRADO CONTADURIA PUBLICA', 'PGCP'),
('PREGRADO DERECHO', 'PGD'),
('MAESTRIA EN INGENIERIA DE SOFTWARE', 'MIS'),
('ESPECIALIZACION EN GERENCIA DE PROYECTOS', 'EGP')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

-- Actualizar usuarios existentes con email
UPDATE usuarios SET email = CONCAT(username, '@universidad.edu') WHERE email IS NULL OR email = '';

-- Insertar usuario super admin (password: admin123)
INSERT INTO usuarios (username, password, nombre, email, rol) VALUES 
('superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrador', 'superadmin@universidad.edu', 'super_admin')
ON DUPLICATE KEY UPDATE password=VALUES(password), nombre=VALUES(nombre), email=VALUES(email), rol=VALUES(rol);

-- Insertar profesor de ejemplo (password: profesor123)
INSERT INTO usuarios (username, password, nombre, email, rol) VALUES 
('profesor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Profesor Ejemplo', 'profesor1@universidad.edu', 'profesor')
ON DUPLICATE KEY UPDATE password=VALUES(password), nombre=VALUES(nombre), email=VALUES(email), rol=VALUES(rol);
-- ============================================================
-- Migración: Seguridad - 2026-06-04
-- Columnas para remember-me token (autenticación persistente)
-- NOTA: MySQL 8.0 no soporta ADD COLUMN IF NOT EXISTS.
--       Usar procedimiento condicional para idempotencia.
-- ============================================================
DROP PROCEDURE IF EXISTS _add_col_if_missing;
DELIMITER //
CREATE PROCEDURE _add_col_if_missing()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='remember_token') THEN
    ALTER TABLE usuarios ADD COLUMN remember_token VARCHAR(64) NULL DEFAULT NULL AFTER email;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='remember_expires') THEN
    ALTER TABLE usuarios ADD COLUMN remember_expires DATETIME NULL DEFAULT NULL AFTER remember_token;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND INDEX_NAME='idx_remember_token') THEN
    ALTER TABLE usuarios ADD INDEX idx_remember_token (remember_token);
  END IF;

  -- ============================================================
  -- Migración: Pre-registro, Firmas y Foto de perfil - 2026-06-06
  -- ============================================================

  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='estado_cuenta') THEN
    ALTER TABLE usuarios ADD COLUMN estado_cuenta ENUM('pendiente_activacion','activo','inactivo') NOT NULL DEFAULT 'activo' AFTER activo;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='foto_perfil') THEN
    ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL DEFAULT NULL AFTER estado_cuenta;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='asistencias' AND COLUMN_NAME='firma_hash') THEN
    ALTER TABLE asistencias ADD COLUMN firma_hash CHAR(64) NULL DEFAULT NULL AFTER firma;
  END IF;
END //
DELIMITER ;
CALL _add_col_if_missing();
DROP PROCEDURE IF EXISTS _add_col_if_missing;

-- Marcar usuarios ya existentes como activos (idempotente)
UPDATE usuarios SET estado_cuenta = 'activo' WHERE estado_cuenta IS NULL OR estado_cuenta = '';

-- 4. Tabla tokens_activacion (activación de cuenta y reset de contraseña)
CREATE TABLE IF NOT EXISTS tokens_activacion (
    id            INT             NOT NULL AUTO_INCREMENT,
    usuario_id    INT             NOT NULL,
    token         CHAR(64)        NOT NULL,
    tipo          ENUM('activacion','reset_password') NOT NULL DEFAULT 'activacion',
    usado         TINYINT(1)      NOT NULL DEFAULT 0,
    expirado      TINYINT(1)      NOT NULL DEFAULT 0,
    expires_at    DATETIME        NOT NULL,
    used_at       DATETIME        NULL DEFAULT NULL,
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token (token),
    INDEX idx_usuario_tipo (usuario_id, tipo),
    CONSTRAINT fk_token_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabla configuracion_sistema (parámetros no sensibles de la aplicación)
CREATE TABLE IF NOT EXISTS configuracion_sistema (
    id          INT             NOT NULL AUTO_INCREMENT,
    clave       VARCHAR(100)    NOT NULL,
    valor       TEXT            NULL,
    descripcion VARCHAR(255)    NULL,
    updated_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valores por defecto de configuración
INSERT INTO configuracion_sistema (clave, valor, descripcion) VALUES
    ('app_nombre',           'Sistema de Asistencia',         'Nombre de la institución'),
    ('app_url',              'http://localhost:8080/public',   'URL base de la aplicación'),
    ('gracia_minutos_post',  '30',                            'Minutos de gracia tras la hora de fin de sesión'),
    ('gracia_minutos_pre',   '15',                            'Minutos antes de la hora de inicio en que se abre el formulario')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

-- ============================================================
-- Migración: Perfil extendido + sede/aula en sesiones - 2026-06-06
-- ============================================================
DROP PROCEDURE IF EXISTS _extend_perfil_sesiones;
DELIMITER //
CREATE PROCEDURE _extend_perfil_sesiones()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='telefono') THEN
    ALTER TABLE usuarios ADD COLUMN telefono VARCHAR(30) NULL DEFAULT NULL AFTER email;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='fecha_nacimiento') THEN
    ALTER TABLE usuarios ADD COLUMN fecha_nacimiento DATE NULL DEFAULT NULL AFTER foto_perfil;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='documento') THEN
    ALTER TABLE usuarios ADD COLUMN documento VARCHAR(30) NULL DEFAULT NULL AFTER fecha_nacimiento;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='notif_email') THEN
    ALTER TABLE usuarios ADD COLUMN notif_email TINYINT(1) NOT NULL DEFAULT 1 AFTER documento;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sesiones' AND COLUMN_NAME='aula') THEN
    ALTER TABLE sesiones ADD COLUMN aula VARCHAR(30) NULL DEFAULT NULL AFTER ubicacion;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sesiones' AND COLUMN_NAME='sede') THEN
    ALTER TABLE sesiones ADD COLUMN sede VARCHAR(50) NULL DEFAULT NULL AFTER aula;
  END IF;
END //
DELIMITER ;
CALL _extend_perfil_sesiones();
DROP PROCEDURE IF EXISTS _extend_perfil_sesiones;
