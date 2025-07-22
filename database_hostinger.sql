-- =====================================================
-- DDL COMPLETO PARA HOSTINGER - SISTEMA DE ASISTENCIA
-- Universidad del Tolima
-- =====================================================

-- Crear base de datos (opcional, puede que ya exista en Hostinger)
-- CREATE DATABASE IF NOT EXISTS asistencia_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE asistencia_db;

-- =====================================================
-- TABLA DE USUARIOS
-- =====================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    rol ENUM('super_admin', 'admin', 'profesor') NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA DE PROGRAMAS ACADÉMICOS
-- =====================================================
CREATE TABLE IF NOT EXISTS programas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL UNIQUE,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA DE CURSOS
-- =====================================================
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
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profesor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (programa_id) REFERENCES programas(id) ON DELETE RESTRICT,
    INDEX idx_codigo (codigo),
    INDEX idx_profesor (profesor_id),
    INDEX idx_programa (programa_id),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA DE SESIONES DE CLASE
-- =====================================================
CREATE TABLE IF NOT EXISTS sesiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME,
    estado ENUM('activa', 'finalizada', 'cancelada') DEFAULT 'activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    INDEX idx_curso (curso_id),
    INDEX idx_fecha (fecha),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA DE ESTUDIANTES
-- =====================================================
CREATE TABLE IF NOT EXISTS estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    documento VARCHAR(20) NOT NULL UNIQUE,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    telefono VARCHAR(20),
    direccion VARCHAR(150),
    correo VARCHAR(100),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_documento (documento),
    INDEX idx_codigo (codigo),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA DE RELACIÓN CURSOS-ESTUDIANTES
-- =====================================================
CREATE TABLE IF NOT EXISTS cursos_estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    estudiante_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_curso_estudiante (curso_id, estudiante_id),
    INDEX idx_curso (curso_id),
    INDEX idx_estudiante (estudiante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA DE ASISTENCIAS
-- =====================================================
CREATE TABLE IF NOT EXISTS asistencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sesion_id INT NOT NULL,
    estudiante_id INT NOT NULL,
    hora_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    firma LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sesion_id) REFERENCES sesiones(id) ON DELETE CASCADE,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_sesion_estudiante (sesion_id, estudiante_id),
    INDEX idx_sesion (sesion_id),
    INDEX idx_estudiante (estudiante_id),
    INDEX idx_hora_registro (hora_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA DE CONFIGURACIÓN DEL SISTEMA
-- =====================================================
CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    descripcion VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Insertar programas académicos
INSERT INTO programas (nombre, codigo) VALUES 
('PREGRADO INGENIERIA DE SISTEMAS', 'PGIS'),
('PREGRADO INGENIERIA INDUSTRIAL', 'PGII'),
('PREGRADO ADMINISTRACION DE EMPRESAS', 'PGAE'),
('PREGRADO CONTADURIA PUBLICA', 'PGCP'),
('PREGRADO DERECHO', 'PGD'),
('PREGRADO MEDICINA', 'PGM'),
('PREGRADO ENFERMERIA', 'PGE'),
('MAESTRIA EN INGENIERIA DE SOFTWARE', 'MIS'),
('ESPECIALIZACION EN GERENCIA DE PROYECTOS', 'EGP'),
('DOCTORADO EN EDUCACION', 'DE')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

-- Insertar usuario super admin
-- Usuario: superadmin
-- Contraseña: SuperAdmin2024!
-- Hash generado con password_hash('SuperAdmin2024!', PASSWORD_DEFAULT)
INSERT INTO usuarios (username, password, nombre, email, rol) VALUES 
('superadmin', '$2y$10$YourHashedPasswordHere', 'Super Administrador', 'admin@tudominio.com', 'super_admin')
ON DUPLICATE KEY UPDATE 
    password=VALUES(password), 
    nombre=VALUES(nombre), 
    email=VALUES(email), 
    rol=VALUES(rol);

-- Configuración inicial del sistema
INSERT INTO configuracion (clave, valor, descripcion) VALUES 
('universidad_nombre', 'Universidad del Tolima', 'Nombre de la universidad'),
('universidad_logo', '/assets/img/logo-ut.png', 'Ruta del logo de la universidad'),
('smtp_host', 'smtp.gmail.com', 'Servidor SMTP para envío de correos'),
('smtp_port', '587', 'Puerto SMTP'),
('smtp_username', 'tu-email@gmail.com', 'Usuario SMTP'),
('smtp_password', 'tu-app-password', 'Contraseña de aplicación SMTP'),
('smtp_encryption', 'tls', 'Tipo de encriptación SMTP'),
('email_from', 'noreply@universidad.edu', 'Email remitente del sistema'),
('email_from_name', 'Sistema de Asistencia - Universidad del Tolima', 'Nombre del remitente')
ON DUPLICATE KEY UPDATE valor=VALUES(valor);

-- =====================================================
-- INSTRUCCIONES DE INSTALACIÓN
-- =====================================================
/*
PARA INSTALAR EN HOSTINGER:

1. Accede a tu panel de Hostinger
2. Ve a "Bases de datos" > "phpMyAdmin"
3. Selecciona tu base de datos
4. Ve a la pestaña "SQL"
5. Copia y pega todo este código
6. Haz clic en "Continuar"

7. IMPORTANTE: Después de ejecutar el SQL, debes:
   a) Cambiar el email 'admin@tudominio.com' por tu email real
   b) Generar un hash real para la contraseña del super admin
   c) Configurar los datos SMTP en la tabla 'configuracion'

PARA GENERAR EL HASH DE LA CONTRASEÑA:
Ejecuta este código PHP:
<?php
echo password_hash('TuContraseñaSegura', PASSWORD_DEFAULT);
?>

Luego actualiza el usuario con:
UPDATE usuarios SET password = 'el_hash_generado' WHERE username = 'superadmin';

CREDENCIALES INICIALES:
Usuario: superadmin
Contraseña: (la que definas y hashees)
*/