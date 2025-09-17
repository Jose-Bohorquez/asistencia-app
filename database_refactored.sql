-- =====================================================
-- SISTEMA DE ASISTENCIA - BASE DE DATOS REFACTORIZADA
-- =====================================================

-- Configuración inicial
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS `asistencia_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `asistencia_db`;

-- =====================================================
-- TABLA: roles
-- =====================================================
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `permisos` JSON DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar roles por defecto
INSERT INTO `roles` (`nombre`, `descripcion`, `permisos`) VALUES
('super_admin', 'Super Administrador - Acceso total al sistema', '{"usuarios": ["create", "read", "update", "delete"], "cursos": ["create", "read", "update", "delete"], "sesiones": ["create", "read", "update", "delete"], "asistencias": ["create", "read", "update", "delete"], "programas": ["create", "read", "update", "delete"], "estudiantes": ["create", "read", "update", "delete"], "reportes": ["read", "export"]}'),
('admin', 'Administrador - Acceso completo excepto eliminación', '{"usuarios": ["create", "read", "update"], "cursos": ["create", "read", "update"], "sesiones": ["create", "read", "update"], "asistencias": ["create", "read", "update"], "programas": ["create", "read", "update"], "estudiantes": ["create", "read", "update"], "reportes": ["read", "export"]}'),
('profesor', 'Profesor - Gestión de sus cursos y sesiones', '{"cursos": ["read", "update"], "sesiones": ["create", "read", "update"], "asistencias": ["create", "read", "update"], "estudiantes": ["read"], "reportes": ["read", "export"]}');

-- =====================================================
-- TABLA: usuarios (mejorada)
-- =====================================================
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `documento` varchar(20) DEFAULT NULL,
  `rol_id` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `intentos_fallidos` int(11) DEFAULT 0,
  `bloqueado_hasta` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `rol_id` (`rol_id`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: programas (mejorada)
-- =====================================================
DROP TABLE IF EXISTS `programas`;
CREATE TABLE `programas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `nivel` enum('pregrado','posgrado','especializacion','maestria','doctorado') DEFAULT 'pregrado',
  `duracion_semestres` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: estudiantes (mejorada)
-- =====================================================
DROP TABLE IF EXISTS `estudiantes`;
CREATE TABLE `estudiantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `documento` varchar(20) NOT NULL,
  `tipo_documento` enum('CC','TI','CE','PP') DEFAULT 'CC',
  `codigo_estudiante` varchar(20) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `programa_id` int(11) DEFAULT NULL,
  `semestre_actual` int(11) DEFAULT NULL,
  `estado` enum('activo','inactivo','graduado','retirado') DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `documento` (`documento`),
  UNIQUE KEY `codigo_estudiante` (`codigo_estudiante`),
  KEY `programa_id` (`programa_id`),
  CONSTRAINT `estudiantes_ibfk_1` FOREIGN KEY (`programa_id`) REFERENCES `programas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: cursos (mejorada)
-- =====================================================
DROP TABLE IF EXISTS `cursos`;
CREATE TABLE `cursos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `programa_id` int(11) DEFAULT NULL,
  `area` varchar(100) NOT NULL,
  `semestre` varchar(20) NOT NULL,
  `grupo` varchar(20) NOT NULL,
  `aula` varchar(20) DEFAULT NULL,
  `sede` varchar(50) DEFAULT NULL,
  `creditos` int(11) DEFAULT NULL,
  `horas_semanales` int(11) DEFAULT NULL,
  `profesor_id` int(11) DEFAULT NULL,
  `cupo_maximo` int(11) DEFAULT NULL,
  `periodo_academico` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_grupo_periodo` (`codigo`, `grupo`, `periodo_academico`),
  KEY `profesor_id` (`profesor_id`),
  KEY `programa_id` (`programa_id`),
  CONSTRAINT `cursos_ibfk_1` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `cursos_ibfk_2` FOREIGN KEY (`programa_id`) REFERENCES `programas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: cursos_estudiantes (mejorada)
-- =====================================================
DROP TABLE IF EXISTS `cursos_estudiantes`;
CREATE TABLE `cursos_estudiantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `fecha_inscripcion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('inscrito','retirado','aprobado','reprobado') DEFAULT 'inscrito',
  `nota_final` decimal(3,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `curso_estudiante` (`curso_id`, `estudiante_id`),
  KEY `estudiante_id` (`estudiante_id`),
  CONSTRAINT `cursos_estudiantes_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cursos_estudiantes_ibfk_2` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: sesiones (mejorada)
-- =====================================================
DROP TABLE IF EXISTS `sesiones`;
CREATE TABLE `sesiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time DEFAULT NULL,
  `tema` varchar(200) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo_sesion` enum('teorica','practica','laboratorio','examen','taller') DEFAULT 'teorica',
  `estado` enum('programada','activa','finalizada','cancelada') DEFAULT 'programada',
  `token` varchar(32) DEFAULT NULL,
  `token_expira` timestamp NULL DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `curso_id` (`curso_id`),
  KEY `fecha_estado` (`fecha`, `estado`),
  CONSTRAINT `sesiones_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: asistencias (mejorada)
-- =====================================================
DROP TABLE IF EXISTS `asistencias`;
CREATE TABLE `asistencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sesion_id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `hora_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `estado_asistencia` enum('presente','tardanza','ausente','justificado') DEFAULT 'presente',
  `minutos_tardanza` int(11) DEFAULT 0,
  `firma` longtext DEFAULT NULL,
  `ip_registro` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sesion_estudiante` (`sesion_id`, `estudiante_id`),
  KEY `estudiante_id` (`estudiante_id`),
  KEY `hora_registro` (`hora_registro`),
  CONSTRAINT `asistencias_ibfk_1` FOREIGN KEY (`sesion_id`) REFERENCES `sesiones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asistencias_ibfk_2` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: logs_sistema (nueva)
-- =====================================================
DROP TABLE IF EXISTS `logs_sistema`;
CREATE TABLE `logs_sistema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `datos_anteriores` JSON DEFAULT NULL,
  `datos_nuevos` JSON DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `accion_fecha` (`accion`, `created_at`),
  CONSTRAINT `logs_sistema_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERTAR DATOS DE PRUEBA
-- =====================================================

-- Insertar programas de ejemplo
INSERT INTO `programas` (`nombre`, `codigo`, `descripcion`, `nivel`) VALUES
('Ingeniería de Sistemas', 'PGIS', 'Programa de pregrado en Ingeniería de Sistemas', 'pregrado'),
('Ingeniería Industrial', 'PGII', 'Programa de pregrado en Ingeniería Industrial', 'pregrado'),
('Administración de Empresas', 'PGAE', 'Programa de pregrado en Administración de Empresas', 'pregrado'),
('Contaduría Pública', 'PGCP', 'Programa de pregrado en Contaduría Pública', 'pregrado'),
('Derecho', 'PGD', 'Programa de pregrado en Derecho', 'pregrado'),
('Maestría en Ingeniería de Software', 'MIS', 'Programa de maestría en Ingeniería de Software', 'maestria'),
('Especialización en Gerencia de Proyectos', 'EGP', 'Especialización en Gerencia de Proyectos', 'especializacion');

-- Insertar usuarios de ejemplo (password: admin123)
INSERT INTO `usuarios` (`username`, `password`, `nombre`, `apellido`, `email`, `rol_id`) VALUES
('superadmin', '$2y$12$PZjWt4JVL.X2NakARaotMu12JpIgs1MBWGCYWs/cNLwPeBrjupUJK', 'Super', 'Administrador', 'superadmin@universidad.edu', 1),
('admin', '$2y$12$PZjWt4JVL.X2NakARaotMu12JpIgs1MBWGCYWs/cNLwPeBrjupUJK', 'Administrador', 'Sistema', 'admin@universidad.edu', 2),
('profesor1', '$2y$12$PZjWt4JVL.X2NakARaotMu12JpIgs1MBWGCYWs/cNLwPeBrjupUJK', 'Juan Carlos', 'Pérez', 'profesor1@universidad.edu', 3),
('profesor2', '$2y$12$PZjWt4JVL.X2NakARaotMu12JpIgs1MBWGCYWs/cNLwPeBrjupUJK', 'María Elena', 'García', 'profesor2@universidad.edu', 3);

-- Finalizar transacción
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

-- Vista para usuarios con roles
CREATE OR REPLACE VIEW `v_usuarios_roles` AS
SELECT 
    u.id,
    u.username,
    u.nombre,
    u.apellido,
    u.email,
    u.telefono,
    u.documento,
    u.activo,
    u.ultimo_acceso,
    r.nombre as rol_nombre,
    r.descripcion as rol_descripcion,
    r.permisos as rol_permisos
FROM usuarios u
JOIN roles r ON u.rol_id = r.id;

-- Vista para cursos con información completa
CREATE OR REPLACE VIEW `v_cursos_completos` AS
SELECT 
    c.id,
    c.codigo,
    c.nombre,
    c.descripcion,
    c.area,
    c.semestre,
    c.grupo,
    c.aula,
    c.sede,
    c.creditos,
    c.horas_semanales,
    c.cupo_maximo,
    c.periodo_academico,
    c.activo,
    p.nombre as programa_nombre,
    p.codigo as programa_codigo,
    CONCAT(u.nombre, ' ', u.apellido) as profesor_nombre,
    u.email as profesor_email
FROM cursos c
LEFT JOIN programas p ON c.programa_id = p.id
LEFT JOIN usuarios u ON c.profesor_id = u.id;

-- Vista para asistencias con información completa
CREATE OR REPLACE VIEW `v_asistencias_completas` AS
SELECT 
    a.id,
    a.hora_registro,
    a.estado_asistencia,
    a.minutos_tardanza,
    a.observaciones,
    s.fecha as sesion_fecha,
    s.hora_inicio as sesion_hora_inicio,
    s.tema as sesion_tema,
    c.nombre as curso_nombre,
    c.codigo as curso_codigo,
    CONCAT(e.nombre, ' ', e.apellido) as estudiante_nombre,
    e.documento as estudiante_documento,
    e.codigo_estudiante,
    CONCAT(u.nombre, ' ', u.apellido) as profesor_nombre
FROM asistencias a
JOIN sesiones s ON a.sesion_id = s.id
JOIN cursos c ON s.curso_id = c.id
JOIN estudiantes e ON a.estudiante_id = e.id
JOIN usuarios u ON c.profesor_id = u.id;