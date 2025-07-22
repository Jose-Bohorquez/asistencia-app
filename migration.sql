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