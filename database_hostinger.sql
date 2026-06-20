-- =============================================================================
-- Sistema de Control de Asistencia — Universidad del Tolima
-- Schema completo para producción (Hostinger / MySQL 8)
-- Generado desde la base de datos de desarrollo — 2026-06-20
-- =============================================================================
-- USO: importar en phpMyAdmin > pestaña SQL > Continuar
--      O desde CLI: mysql -u usuario -p nombre_bd < database_hostinger.sql
-- =============================================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- =============================================================================
-- TABLAS (orden inverso a FKs para que los DROP no fallen)
-- =============================================================================

DROP TABLE IF EXISTS `asistencias`;
DROP TABLE IF EXISTS `tokens_asistencia`;
DROP TABLE IF EXISTS `tokens_activacion`;
DROP TABLE IF EXISTS `matriculas`;
DROP TABLE IF EXISTS `horarios`;
DROP TABLE IF EXISTS `sesiones`;
DROP TABLE IF EXISTS `cursos`;
DROP TABLE IF EXISTS `docentes`;
DROP TABLE IF EXISTS `usuario_roles`;
DROP TABLE IF EXISTS `rol_permisos`;
DROP TABLE IF EXISTS `permisos`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `estudiantes`;
DROP TABLE IF EXISTS `programas`;
DROP TABLE IF EXISTS `facultades`;
DROP TABLE IF EXISTS `periodos_academicos`;
DROP TABLE IF EXISTS `estados_asistencia`;
DROP TABLE IF EXISTS `configuracion_sistema`;
DROP TABLE IF EXISTS `logs_sistema`;
DROP TABLE IF EXISTS `auditoria`;

-- ─── Tablas sin dependencias ──────────────────────────────────────────────────

CREATE TABLE `estados_asistencia` (
  `id` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `facultades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `decano` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `periodos_academicos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `anio` year NOT NULL,
  `semestre` tinyint NOT NULL COMMENT '1 o 2',
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`),
  CONSTRAINT `periodos_academicos_chk_1` CHECK ((`semestre` in (1,2)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `permisos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(80) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `modulo` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `estudiantes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `documento` varchar(20) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `activo` tinyint(1) DEFAULT '1',
  `email` varchar(100) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `configuracion_sistema` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Usuarios ─────────────────────────────────────────────────────────────────

CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `rol` enum('super_admin','admin','profesor') NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `estado_cuenta` enum('pendiente_activacion','activo','inactivo') NOT NULL DEFAULT 'activo',
  `foto_perfil` varchar(255) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `remember_expires` datetime DEFAULT NULL,
  `documento` varchar(30) DEFAULT NULL,
  `notif_email` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_remember_token` (`remember_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ─── Tablas con FK a usuarios ─────────────────────────────────────────────────

CREATE TABLE `docentes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `titulo` varchar(100) DEFAULT NULL,
  `especialidad` varchar(150) DEFAULT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `escalafon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `docentes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `usuario_roles` (
  `usuario_id` int NOT NULL,
  `rol_id` int NOT NULL,
  `asignado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `asignado_por` int DEFAULT NULL,
  PRIMARY KEY (`usuario_id`,`rol_id`),
  KEY `rol_id` (`rol_id`),
  KEY `asignado_por` (`asignado_por`),
  CONSTRAINT `usuario_roles_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `usuario_roles_ibfk_2` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `usuario_roles_ibfk_3` FOREIGN KEY (`asignado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `rol_permisos` (
  `rol_id` int NOT NULL,
  `permiso_id` int NOT NULL,
  PRIMARY KEY (`rol_id`,`permiso_id`),
  KEY `permiso_id` (`permiso_id`),
  CONSTRAINT `rol_permisos_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rol_permisos_ibfk_2` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `tokens_activacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `token` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('activacion','reset_password') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activacion',
  `usado` tinyint(1) NOT NULL DEFAULT '0',
  `expirado` tinyint(1) NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token` (`token`),
  KEY `idx_usuario_tipo` (`usuario_id`,`tipo`),
  CONSTRAINT `fk_token_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `logs_sistema` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `accion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tabla_afectada` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registro_id` int DEFAULT NULL,
  `datos_anteriores` json DEFAULT NULL,
  `datos_nuevos` json DEFAULT NULL,
  `detalles` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `fecha_hora` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_accion_fecha` (`accion`,`created_at`),
  CONSTRAINT `logs_sistema_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `auditoria` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `tabla_afectada` varchar(60) DEFAULT NULL,
  `registro_id` int DEFAULT NULL,
  `datos_anteriores` json DEFAULT NULL,
  `datos_nuevos` json DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_usuario` (`usuario_id`),
  KEY `idx_audit_tabla` (`tabla_afectada`,`registro_id`),
  KEY `idx_audit_fecha` (`created_at`),
  CONSTRAINT `auditoria_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ─── Programas y cursos ───────────────────────────────────────────────────────

CREATE TABLE `programas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `facultad_id` int DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `fk_programas_facultad` (`facultad_id`),
  CONSTRAINT `fk_programas_facultad` FOREIGN KEY (`facultad_id`) REFERENCES `facultades` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `cursos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `programa_id` int DEFAULT NULL,
  `programa` varchar(100) NOT NULL,
  `area` varchar(100) NOT NULL,
  `semestre` varchar(20) NOT NULL,
  `grupo` varchar(20) NOT NULL,
  `aula` varchar(20) DEFAULT NULL,
  `sede` varchar(50) DEFAULT NULL,
  `profesor_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `activo` tinyint(1) DEFAULT '1',
  `descripcion` text,
  `creditos` int DEFAULT NULL,
  `periodo_academico` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `profesor_id` (`profesor_id`),
  CONSTRAINT `cursos_ibfk_1` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `horarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `curso_id` int NOT NULL,
  `dia_semana` tinyint NOT NULL COMMENT '1=Lunes … 7=Domingo',
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `aula` varchar(30) DEFAULT NULL,
  `sede` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `curso_id` (`curso_id`),
  CONSTRAINT `horarios_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `horarios_chk_1` CHECK ((`dia_semana` between 1 and 7))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `matriculas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `curso_id` int NOT NULL,
  `estudiante_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_inscripcion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('inscrito','retirado','aprobado','reprobado') DEFAULT 'inscrito',
  `nota_final` decimal(3,2) DEFAULT NULL,
  `periodo_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_matricula` (`curso_id`,`estudiante_id`,`periodo_id`),
  KEY `estudiante_id` (`estudiante_id`),
  KEY `idx_matriculas_periodo` (`periodo_id`),
  CONSTRAINT `fk_ce_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `periodos_academicos` (`id`),
  CONSTRAINT `matriculas_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `matriculas_ibfk_2` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ─── Sesiones y asistencias ───────────────────────────────────────────────────

CREATE TABLE `sesiones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `curso_id` int NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time DEFAULT NULL,
  `estado` enum('activa','finalizada','cancelada') DEFAULT 'activa',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `token` varchar(32) DEFAULT NULL,
  `tema` varchar(200) DEFAULT NULL,
  `descripcion` text,
  `duracion_minutos` int DEFAULT NULL,
  `tipo_sesion` enum('teorica','practica','laboratorio','examen','taller') DEFAULT 'teorica',
  `ubicacion` varchar(100) DEFAULT NULL,
  `aula` varchar(30) DEFAULT NULL,
  `sede` varchar(50) DEFAULT NULL,
  `observaciones` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `curso_id` (`curso_id`),
  KEY `idx_sesiones_fecha` (`fecha`),
  CONSTRAINT `sesiones_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `tokens_asistencia` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sesion_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `creado_por` int DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` datetime DEFAULT NULL,
  `estado` enum('activo','usado','expirado') DEFAULT 'activo',
  `usos` int DEFAULT '0',
  `max_usos` int DEFAULT NULL COMMENT 'NULL = ilimitado',
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `sesion_id` (`sesion_id`),
  KEY `creado_por` (`creado_por`),
  CONSTRAINT `tokens_asistencia_ibfk_1` FOREIGN KEY (`sesion_id`) REFERENCES `sesiones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tokens_asistencia_ibfk_2` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `asistencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sesion_id` int NOT NULL,
  `estudiante_id` int NOT NULL,
  `hora_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `firma` longtext,
  `firma_path` varchar(255) DEFAULT NULL COMMENT 'Ruta relativa al PNG en public/uploads/firmas/',
  `firma_hash` char(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `estado_asistencia` enum('presente','tardanza','ausente','justificado') DEFAULT 'presente',
  `estado_id` tinyint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `observaciones` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sesion_id` (`sesion_id`,`estudiante_id`),
  KEY `estudiante_id` (`estudiante_id`),
  KEY `fk_asistencias_estado` (`estado_id`),
  KEY `idx_asistencias_created` (`created_at`),
  CONSTRAINT `asistencias_ibfk_1` FOREIGN KEY (`sesion_id`) REFERENCES `sesiones` (`id`),
  CONSTRAINT `asistencias_ibfk_2` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`),
  CONSTRAINT `fk_asistencias_estado` FOREIGN KEY (`estado_id`) REFERENCES `estados_asistencia` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =============================================================================
-- DATOS SEMILLA (seed — requeridos para que la app funcione)
-- =============================================================================

-- Roles del sistema
INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `activo`) VALUES
(1, 'super_admin', 'Acceso total al sistema', 1),
(2, 'admin', 'Administración general sin logs ni backup', 1),
(3, 'profesor', 'Gestión de sus propios cursos y sesiones', 1),
(4, 'coordinador', 'Coordinación de programas académicos', 1),
(5, 'secretaria', 'Consulta y apoyo administrativo', 1);

-- Permisos del sistema
INSERT INTO `permisos` (`id`, `codigo`, `descripcion`, `modulo`) VALUES
(1,  'usuarios.ver',       'Ver listado de usuarios',             'usuarios'),
(2,  'usuarios.crear',     'Crear usuarios',                      'usuarios'),
(3,  'usuarios.editar',    'Editar usuarios',                     'usuarios'),
(4,  'usuarios.eliminar',  'Eliminar usuarios',                   'usuarios'),
(5,  'cursos.ver',         'Ver cursos',                          'cursos'),
(6,  'cursos.crear',       'Crear cursos',                        'cursos'),
(7,  'cursos.editar',      'Editar cursos',                       'cursos'),
(8,  'cursos.eliminar',    'Eliminar cursos',                     'cursos'),
(9,  'sesiones.ver',       'Ver sesiones',                        'sesiones'),
(10, 'sesiones.crear',     'Crear sesiones',                      'sesiones'),
(11, 'sesiones.gestionar', 'Activar/finalizar/cancelar',          'sesiones'),
(12, 'asistencias.ver',    'Ver registros',                       'asistencias'),
(13, 'asistencias.registrar', 'Registrar asistencias',            'asistencias'),
(14, 'reportes.ver',       'Ver reportes',                        'reportes'),
(15, 'reportes.exportar',  'Exportar a Excel/PDF',                'reportes'),
(16, 'sistema.logs',       'Ver logs del sistema',                'sistema'),
(17, 'sistema.backup',     'Backup y restauración',               'sistema'),
(18, 'sistema.config',     'Configuración del sistema',           'sistema');

-- Asignación de permisos a roles
INSERT INTO `rol_permisos` (`rol_id`, `permiso_id`) VALUES
-- super_admin tiene todo
(1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),
(1,9),(1,10),(1,11),(1,12),(1,13),(1,14),(1,15),(1,16),(1,17),(1,18),
-- admin (sin logs/backup/config)
(2,1),(2,2),(2,3),(2,4),(2,5),(2,6),(2,7),(2,8),
(2,9),(2,10),(2,11),(2,12),(2,13),(2,14),(2,15),
-- profesor (sus propios recursos)
(3,5),(3,9),(3,10),(3,11),(3,12),(3,13),(3,14),(3,15);

-- Estados de asistencia
INSERT INTO `estados_asistencia` (`id`, `nombre`, `descripcion`, `activo`) VALUES
(1, 'presente',    'Estudiante asistió a la sesión',              1),
(2, 'tardanza',    'Estudiante llegó tarde',                      1),
(3, 'ausente',     'Estudiante no asistió',                       1),
(4, 'justificado', 'Ausencia justificada con soporte documental', 1);

-- Configuración inicial (ajustar app_url en producción)
INSERT INTO `configuracion_sistema` (`clave`, `valor`, `descripcion`) VALUES
('app_nombre',        'Sistema de Asistencia — Universidad del Tolima', 'Nombre de la institución'),
('app_url',           'https://tudominio.com',                          'URL base — cambiar antes de usar'),
('gracia_minutos_post', '30', 'Minutos de gracia tras el fin de sesión'),
('gracia_minutos_pre',  '15', 'Minutos antes de inicio en que se abre el formulario');

-- =============================================================================
-- USUARIO SUPERADMIN INICIAL
-- Contraseña por defecto: SuperAdmin2024!
-- CAMBIAR INMEDIATAMENTE después del primer login.
-- Hash: password_hash('SuperAdmin2024!', PASSWORD_DEFAULT)
-- =============================================================================
INSERT INTO `usuarios` (`username`, `password`, `nombre`, `email`, `rol`, `activo`, `estado_cuenta`) VALUES
('superadmin',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Super Administrador',
 'admin@tudominio.com',
 'super_admin',
 1,
 'activo');

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- =============================================================================
-- FIN DEL SCRIPT
-- Después de importar:
--   1. Ir a https://tudominio.com/install_hostinger.php para configurar
--   2. O actualizar manualmente: UPDATE configuracion_sistema SET valor='https://tudominio.com' WHERE clave='app_url';
--   3. Cambiar contraseña del superadmin en el primer login
--   4. Eliminar install_hostinger.php, generar_hash.php, verificar_password.php del servidor
-- =============================================================================
