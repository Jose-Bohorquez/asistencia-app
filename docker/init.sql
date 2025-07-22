CREATE DATABASE IF NOT EXISTS asistencia_db;
USE asistencia_db;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    rol ENUM('admin', 'profesor') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    programa VARCHAR(100) NOT NULL,
    area VARCHAR(100) NOT NULL,
    semestre VARCHAR(20) NOT NULL,
    grupo VARCHAR(20) NOT NULL,
    aula VARCHAR(20),
    sede VARCHAR(50),
    profesor_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profesor_id) REFERENCES usuarios(id)
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

-- Insertar usuario administrador por defecto
INSERT INTO usuarios (username, password, nombre, rol) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin');
-- La contraseña es 'password'