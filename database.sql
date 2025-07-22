CREATE DATABASE IF NOT EXISTS asistencia_db;
USE asistencia_db;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    rol ENUM('super_admin', 'admin', 'profesor') NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS programas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL UNIQUE,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    programa_id INT NOT NULL,
    area VARCHAR(100) NOT NULL,
    semestre INT NOT NULL,
    grupo INT NOT NULL,
    aula VARCHAR(20),
    sede VARCHAR(50),
    profesor_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profesor_id) REFERENCES usuarios(id),
    FOREIGN KEY (programa_id) REFERENCES programas(id)
);

CREATE TABLE IF NOT EXISTS sesiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME,
    estado ENUM('activa', 'finalizada', 'cancelada') DEFAULT 'activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id) REFERENCES cursos(id)
);

CREATE TABLE IF NOT EXISTS estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    documento VARCHAR(20) NOT NULL,
    codigo VARCHAR(20) NOT NULL,
    telefono VARCHAR(20),
    direccion VARCHAR(150),
    correo VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cursos_estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    estudiante_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    UNIQUE KEY (curso_id, estudiante_id)
);

CREATE TABLE IF NOT EXISTS asistencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sesion_id INT NOT NULL,
    estudiante_id INT NOT NULL,
    hora_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    firma LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sesion_id) REFERENCES sesiones(id),
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id),
    UNIQUE KEY (sesion_id, estudiante_id)
);

-- Datos iniciales
-- Insertar programas académicos
INSERT INTO programas (nombre, codigo) VALUES 
('PREGRADO INGENIERIA DE SISTEMAS', 'PGIS'),
('PREGRADO INGENIERIA INDUSTRIAL', 'PGII'),
('PREGRADO ADMINISTRACION DE EMPRESAS', 'PGAE'),
('PREGRADO CONTADURIA PUBLICA', 'PGCP'),
('PREGRADO DERECHO', 'PGD'),
('MAESTRIA EN INGENIERIA DE SOFTWARE', 'MIS'),
('ESPECIALIZACION EN GERENCIA DE PROYECTOS', 'EGP')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

-- Insertar usuario super admin (password: admin123)
INSERT INTO usuarios (username, password, nombre, email, rol) VALUES 
('superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrador', 'superadmin@universidad.edu', 'super_admin')
ON DUPLICATE KEY UPDATE password=VALUES(password), nombre=VALUES(nombre), email=VALUES(email), rol=VALUES(rol);

-- Insertar usuario admin de ejemplo (password: admin123)
INSERT INTO usuarios (username, password, nombre, email, rol) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin@universidad.edu', 'admin')
ON DUPLICATE KEY UPDATE password=VALUES(password), nombre=VALUES(nombre), email=VALUES(email), rol=VALUES(rol);

-- Insertar profesor de ejemplo (password: profesor123)
INSERT INTO usuarios (username, password, nombre, email, rol) VALUES 
('profesor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Profesor Ejemplo', 'profesor1@universidad.edu', 'profesor')
ON DUPLICATE KEY UPDATE password=VALUES(password), nombre=VALUES(nombre), email=VALUES(email), rol=VALUES(rol);