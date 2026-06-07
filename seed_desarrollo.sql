-- ============================================================
-- SEED DE DESARROLLO — asistencia-app
-- Universidad del Tolima — Ingeniería de Sistemas
-- Generado: 2026-06-06
-- Propósito: Base limpia, coherente y reproducible para
--            entorno de desarrollo/pruebas.
--
-- ORDEN DE EJECUCIÓN:
--   1. Deshabilitar FK checks
--   2. Vaciar tablas en orden (hijos primero)
--   3. Resetear AUTO_INCREMENT
--   4. Insertar datos mínimos
--   5. Rehabilitar FK checks
--
-- NO BORRA: logs_sistema, migration history (no existe tabla
--   de migrations en este proyecto), tokens_activacion,
--   configuracion_sistema (se upsert).
-- ============================================================

USE asistencia_db;

-- CRÍTICO: declarar charset UTF-8 ANTES de cualquier INSERT
-- Sin esto, el cliente mysql usa latin1 por defecto y corrompe tildes/eñes
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET CHARACTER SET utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. VACIAR TABLAS DE DATOS OPERATIVOS
-- ------------------------------------------------------------
TRUNCATE TABLE asistencias;
TRUNCATE TABLE cursos_estudiantes;
TRUNCATE TABLE sesiones;
TRUNCATE TABLE estudiantes;
TRUNCATE TABLE tokens_activacion;
TRUNCATE TABLE logs_sistema;

-- Vaciar cursos y programas completo (se recargan abajo)
TRUNCATE TABLE cursos;
TRUNCATE TABLE programas;

-- Vaciar usuarios (se recargan abajo)
TRUNCATE TABLE usuarios;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 2. RESETEAR AUTO_INCREMENT
-- ------------------------------------------------------------
ALTER TABLE asistencias           AUTO_INCREMENT = 1;
ALTER TABLE cursos                AUTO_INCREMENT = 1;
ALTER TABLE cursos_estudiantes    AUTO_INCREMENT = 1;
ALTER TABLE estudiantes           AUTO_INCREMENT = 1;
ALTER TABLE logs_sistema          AUTO_INCREMENT = 1;
ALTER TABLE programas             AUTO_INCREMENT = 1;
ALTER TABLE sesiones              AUTO_INCREMENT = 1;
ALTER TABLE tokens_activacion     AUTO_INCREMENT = 1;
ALTER TABLE usuarios              AUTO_INCREMENT = 1;

-- ------------------------------------------------------------
-- 3. CONFIGURACIÓN DEL SISTEMA
-- Las SMTP credentials van en env.local.php, NUNCA aquí.
-- ------------------------------------------------------------
INSERT INTO configuracion_sistema (clave, valor, descripcion) VALUES
    ('app_nombre',          'Sistema de Asistencia — INGSIS',   'Nombre de la institución'),
    ('app_url',             'http://localhost:8080/public',      'URL base de la aplicación'),
    ('gracia_minutos_post', '30',                                'Minutos de gracia tras el fin de sesión'),
    ('gracia_minutos_pre',  '15',                                'Minutos antes de inicio en que se abre el formulario')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

-- ------------------------------------------------------------
-- 4. PROGRAMA
-- Solo se carga el programa real de esta implementación.
-- ------------------------------------------------------------
INSERT INTO programas (id, nombre, codigo, activo, created_at) VALUES
    (1, 'INGENIERÍA DE SISTEMAS', 'INGSIS', 1, NOW());

-- ------------------------------------------------------------
-- 5. USUARIOS DE PRUEBA (6 usuarios)
--
-- Contraseñas (bcrypt $2y$10$):
--   superadmin1 / superadmin2 → "SuperAdmin2024!"
--   admin1 / admin2           → "Admin2024!"
--   profesor1 / profesor2     → "Profesor2024!"
--
-- Todos con estado_cuenta='activo' para poder hacer login
-- sin pasar por el flujo de activación.
-- ------------------------------------------------------------

-- Super Admins  (password: SuperAdmin2024!)
INSERT INTO usuarios (id, username, password, nombre, email, rol, activo, estado_cuenta, created_at) VALUES
(1, 'superadmin1', '$2y$10$TCYAt3tpUuP9JUrOMLu4z.EmbFuZcNCshUMXKOojMgL.qNl1g75Vq',
    'Carlos Rodríguez', 'superadmin1@ingsis.edu.co', 'super_admin', 1, 'activo', NOW()),
(2, 'superadmin2', '$2y$10$TCYAt3tpUuP9JUrOMLu4z.EmbFuZcNCshUMXKOojMgL.qNl1g75Vq',
    'María González', 'superadmin2@ingsis.edu.co', 'super_admin', 1, 'activo', NOW());

-- Administradores  (password: Admin2024!)
INSERT INTO usuarios (id, username, password, nombre, email, rol, activo, estado_cuenta, created_at) VALUES
(3, 'admin1', '$2y$10$mJdI2gLmkiC1wszj.VrzzOI2GHer/ZdWSG1jU5.xexumkdU3XlDdm',
    'Pedro Martínez', 'admin1@ingsis.edu.co', 'admin', 1, 'activo', NOW()),
(4, 'admin2', '$2y$10$mJdI2gLmkiC1wszj.VrzzOI2GHer/ZdWSG1jU5.xexumkdU3XlDdm',
    'Laura Sánchez', 'admin2@ingsis.edu.co', 'admin', 1, 'activo', NOW());

-- Profesores  (password: Profesor2024!)
INSERT INTO usuarios (id, username, password, nombre, email, rol, activo, estado_cuenta, created_at) VALUES
(5, 'profesor1', '$2y$10$8A/gjdIJ3qRcAlWGbtEuD.RxQrkIpuYJYjMN/7xMLqzE0wncqxqRG',
    'José Bohorquez', 'jose.bohorquez@ingsis.edu.co', 'profesor', 1, 'activo', NOW()),
(6, 'profesor2', '$2y$10$8A/gjdIJ3qRcAlWGbtEuD.RxQrkIpuYJYjMN/7xMLqzE0wncqxqRG',
    'Ana Torres', 'ana.torres@ingsis.edu.co', 'profesor', 1, 'activo', NOW());

-- ------------------------------------------------------------
-- 6. CURSOS REALES POR SEMESTRE
-- Programa: INGENIERÍA DE SISTEMAS (id=1)
-- Codificación: S{semestre_romano}G{grupo}_{secuencia}
-- area = "Semestre X" para jerarquía visual
-- profesor_id = NULL (asignado manualmente luego o por profesor
-- al registrar su sesión)
-- ------------------------------------------------------------

-- ── SEMESTRE I ──────────────────────────────────────────────
INSERT INTO cursos (codigo, nombre, programa_id, programa, area, semestre, grupo, activo) VALUES
('S1G1-01', 'SEMINARIO PERMANENTE DE AUTOFORMACIÓN',           1, 'INGENIERÍA DE SISTEMAS', 'Semestre I',   'I',   '1', 1),
('S1G1-02', 'PRE-CÁLCULO',                                     1, 'INGENIERÍA DE SISTEMAS', 'Semestre I',   'I',   '1', 1),
('S1G1-03', 'LECTURA Y ESCRITURA EN LA UNIVERSIDAD',           1, 'INGENIERÍA DE SISTEMAS', 'Semestre I',   'I',   '1', 1),
('S1G1-04', 'ÁLGEBRA LINEAL',                                  1, 'INGENIERÍA DE SISTEMAS', 'Semestre I',   'I',   '1', 1),
('S1G1-05', 'INTRODUCCIÓN A LOS SISTEMAS',                     1, 'INGENIERÍA DE SISTEMAS', 'Semestre I',   'I',   '1', 1),
('S1G1-06', 'LÓGICA DE SISTEMAS',                              1, 'INGENIERÍA DE SISTEMAS', 'Semestre I',   'I',   '1', 1);

-- ── SEMESTRE II ─────────────────────────────────────────────
INSERT INTO cursos (codigo, nombre, programa_id, programa, area, semestre, grupo, activo) VALUES
('S2G1-01', 'ÉTICA PROFESIONAL',                               1, 'INGENIERÍA DE SISTEMAS', 'Semestre II',  'II',  '1', 1),
('S2G1-02', 'CÁLCULO I',                                       1, 'INGENIERÍA DE SISTEMAS', 'Semestre II',  'II',  '1', 1),
('S2G1-03', 'ESTADÍSTICA I',                                   1, 'INGENIERÍA DE SISTEMAS', 'Semestre II',  'II',  '1', 1),
('S2G1-04', 'TEORÍA DE SISTEMAS',                              1, 'INGENIERÍA DE SISTEMAS', 'Semestre II',  'II',  '1', 1),
('S2G1-05', 'INGLÉS I',                                        1, 'INGENIERÍA DE SISTEMAS', 'Semestre II',  'II',  '1', 1),
('S2G1-06', 'ELEMENTOS DE PROGRAMACIÓN ORIENTADA A OBJETOS',   1, 'INGENIERÍA DE SISTEMAS', 'Semestre II',  'II',  '1', 1);

-- ── SEMESTRE III ────────────────────────────────────────────
INSERT INTO cursos (codigo, nombre, programa_id, programa, area, semestre, grupo, activo) VALUES
('S3G1-01', 'GESTIÓN DE INFORMACIÓN',                          1, 'INGENIERÍA DE SISTEMAS', 'Semestre III', 'III', '1', 1),
('S3G1-02', 'METODOLOGÍA DE DISEÑO DE SOFTWARE',               1, 'INGENIERÍA DE SISTEMAS', 'Semestre III', 'III', '1', 1),
('S3G1-03', 'CÁLCULO II',                                      1, 'INGENIERÍA DE SISTEMAS', 'Semestre III', 'III', '1', 1),
('S3G1-04', 'ESTADÍSTICA II',                                  1, 'INGENIERÍA DE SISTEMAS', 'Semestre III', 'III', '1', 1),
('S3G1-05', 'INGLÉS II',                                       1, 'INGENIERÍA DE SISTEMAS', 'Semestre III', 'III', '1', 1),
('S3G1-06', 'APLICACIÓN DE PROGRAMACIÓN ORIENTADA A OBJETOS',  1, 'INGENIERÍA DE SISTEMAS', 'Semestre III', 'III', '1', 1);

-- ── SEMESTRE IV ─────────────────────────────────────────────
INSERT INTO cursos (codigo, nombre, programa_id, programa, area, semestre, grupo, activo) VALUES
('S4G1-01', 'CONSTITUCIÓN POLÍTICA',                           1, 'INGENIERÍA DE SISTEMAS', 'Semestre IV',  'IV',  '1', 1),
('S4G1-02', 'INGENIERÍA DE SOFTWARE',                          1, 'INGENIERÍA DE SISTEMAS', 'Semestre IV',  'IV',  '1', 1),
('S4G1-03', 'SISTEMAS OPERATIVOS',                             1, 'INGENIERÍA DE SISTEMAS', 'Semestre IV',  'IV',  '1', 1),
('S4G1-04', 'INGLÉS III',                                      1, 'INGENIERÍA DE SISTEMAS', 'Semestre IV',  'IV',  '1', 1),
('S4G1-05', 'PROFUNDIZACIÓN DE LA POO I',                      1, 'INGENIERÍA DE SISTEMAS', 'Semestre IV',  'IV',  '1', 1),
('S4G1-06', 'FÍSICA I',                                        1, 'INGENIERÍA DE SISTEMAS', 'Semestre IV',  'IV',  '1', 1),
('S4G1-07', 'CÁLCULO III',                                     1, 'INGENIERÍA DE SISTEMAS', 'Semestre IV',  'IV',  '1', 1);

-- ── SEMESTRE V — PENDIENTE ──────────────────────────────────
-- No se insertaron cursos de Semestre V.
-- El usuario no proporcionó la lista de cursos de ese semestre.
-- Acción: insertar manualmente cuando se disponga de la información.

-- ── SEMESTRE VI ─────────────────────────────────────────────
INSERT INTO cursos (codigo, nombre, programa_id, programa, area, semestre, grupo, activo) VALUES
('S6G1-01', 'ELECTIVA HUMANIDADES — EXPRESIÓN CULTURAL',       1, 'INGENIERÍA DE SISTEMAS', 'Semestre VI',  'VI',  '1', 1),
('S6G1-02', 'REDES NEURONALES',                                1, 'INGENIERÍA DE SISTEMAS', 'Semestre VI',  'VI',  '1', 1),
('S6G1-03', 'ECUACIONES DIFERENCIALES',                        1, 'INGENIERÍA DE SISTEMAS', 'Semestre VI',  'VI',  '1', 1),
('S6G1-04', 'MINERÍA DE DATOS',                                1, 'INGENIERÍA DE SISTEMAS', 'Semestre VI',  'VI',  '1', 1),
('S6G1-05', 'ELECTIVA HUMANIDADES — RECREACIÓN Y DEPORTE',     1, 'INGENIERÍA DE SISTEMAS', 'Semestre VI',  'VI',  '1', 1),
('S6G1-06', 'PROBABILIDAD II',                                 1, 'INGENIERÍA DE SISTEMAS', 'Semestre VI',  'VI',  '1', 1),
('S6G1-07', 'COMUNICACIÓN DEL INGLÉS',                         1, 'INGENIERÍA DE SISTEMAS', 'Semestre VI',  'VI',  '1', 1);

-- ── SEMESTRE VII ────────────────────────────────────────────
INSERT INTO cursos (codigo, nombre, programa_id, programa, area, semestre, grupo, activo) VALUES
('S7G1-01', 'MATEMÁTICAS DISCRETAS II',                        1, 'INGENIERÍA DE SISTEMAS', 'Semestre VII', 'VII', '1', 1),
('S7G1-02', 'MODELOS DE CONOCIMIENTO',                         1, 'INGENIERÍA DE SISTEMAS', 'Semestre VII', 'VII', '1', 1),
('S7G1-03', 'PROGRAMACIÓN DE SISTEMAS INTELIGENTES',           1, 'INGENIERÍA DE SISTEMAS', 'Semestre VII', 'VII', '1', 1),
('S7G1-04', 'AUDITORÍA Y LEGISLACIÓN INFORMÁTICA',             1, 'INGENIERÍA DE SISTEMAS', 'Semestre VII', 'VII', '1', 1),
('S7G1-05', 'MÉTODOS NUMÉRICOS',                               1, 'INGENIERÍA DE SISTEMAS', 'Semestre VII', 'VII', '1', 1),
('S7G1-06', 'INVESTIGACIÓN DE OPERACIONES',                    1, 'INGENIERÍA DE SISTEMAS', 'Semestre VII', 'VII', '1', 1),
('S7G2-01', 'PROGRAMACIÓN DE SISTEMAS INTELIGENTES — Grupo 2', 1, 'INGENIERÍA DE SISTEMAS', 'Semestre VII', 'VII', '2', 1),
('S7G2-02', 'AUDITORÍA Y LEGISLACIÓN INFORMÁTICA — Grupo 2',   1, 'INGENIERÍA DE SISTEMAS', 'Semestre VII', 'VII', '2', 1),
('S7G2-03', 'MODELOS DE CONOCIMIENTO — Grupo 2',               1, 'INGENIERÍA DE SISTEMAS', 'Semestre VII', 'VII', '2', 1),
('S7G2-04', 'MÉTODOS NUMÉRICOS — Grupo 2',                     1, 'INGENIERÍA DE SISTEMAS', 'Semestre VII', 'VII', '2', 1),
('S7G2-05', 'INVESTIGACIÓN DE OPERACIONES — Grupo 2',          1, 'INGENIERÍA DE SISTEMAS', 'Semestre VII', 'VII', '2', 1),
('S7G2-06', 'MATEMÁTICAS DISCRETAS II — Grupo 2',              1, 'INGENIERÍA DE SISTEMAS', 'Semestre VII', 'VII', '2', 1);

-- ── SEMESTRE VIII ───────────────────────────────────────────
-- NOTA: "Curso de Inteligencia de Negocios" no tiene URL ni
-- estructura formal provista → se omite. Pendiente de confirmación.
INSERT INTO cursos (codigo, nombre, programa_id, programa, area, semestre, grupo, activo) VALUES
('S8G1-01', 'ECONOMÍA Y PRINCIPIOS FINANCIEROS',               1, 'INGENIERÍA DE SISTEMAS', 'Semestre VIII','VIII','1', 1),
('S8G1-02', 'ELECTIVA PROFESIONAL I',                          1, 'INGENIERÍA DE SISTEMAS', 'Semestre VIII','VIII','1', 1),
('S8G1-03', 'MODELOS Y SIMULACIÓN',                            1, 'INGENIERÍA DE SISTEMAS', 'Semestre VIII','VIII','1', 1),
('S8G1-04', 'ELECTIVA PROFESIONAL II — GESTIÓN DE SI',         1, 'INGENIERÍA DE SISTEMAS', 'Semestre VIII','VIII','1', 1),
('S8G1-05', 'ELECTIVA PROFESIONAL II — SISTEMAS DE CONTROL',   1, 'INGENIERÍA DE SISTEMAS', 'Semestre VIII','VIII','1', 1),
('S8G1-06', 'SEGURIDAD DE LA INFORMACIÓN',                     1, 'INGENIERÍA DE SISTEMAS', 'Semestre VIII','VIII','1', 1),
('S8G1-07', 'ELECTIVA PROFESIONAL II — WEB SEMÁNTICA',         1, 'INGENIERÍA DE SISTEMAS', 'Semestre VIII','VIII','1', 1),
('S8G1-08', 'INGENIERÍA DE NEGOCIOS',                          1, 'INGENIERÍA DE SISTEMAS', 'Semestre VIII','VIII','1', 1),
('S8G2-01', 'SEGURIDAD DE LA INFORMACIÓN — Grupo 2',           1, 'INGENIERÍA DE SISTEMAS', 'Semestre VIII','VIII','2', 1),
('S8G2-02', 'ELECTIVA PROFESIONAL I — Grupo 2',                1, 'INGENIERÍA DE SISTEMAS', 'Semestre VIII','VIII','2', 1),
('S8G2-03', 'ECONOMÍA Y PRINCIPIOS FINANCIEROS — Grupo 2',     1, 'INGENIERÍA DE SISTEMAS', 'Semestre VIII','VIII','2', 1),
('S8G2-04', 'MODELOS Y SIMULACIÓN — Grupo 2',                  1, 'INGENIERÍA DE SISTEMAS', 'Semestre VIII','VIII','2', 1),
('S8G2-05', 'INGENIERÍA DE NEGOCIOS — Grupo 2',                1, 'INGENIERÍA DE SISTEMAS', 'Semestre VIII','VIII','2', 1),
('S8G2-06', 'ELECTIVA PROFESIONAL II — WEB SEMÁNTICA — Grupo 2',1,'INGENIERÍA DE SISTEMAS', 'Semestre VIII','VIII','2', 1);

-- ── SEMESTRE IX ─────────────────────────────────────────────
INSERT INTO cursos (codigo, nombre, programa_id, programa, area, semestre, grupo, activo) VALUES
('S9G1-01', 'OPCIÓN DE GRADO (Seminario de Profundización)',   1, 'INGENIERÍA DE SISTEMAS', 'Semestre IX',  'IX',  '1', 1),
('S9G1-02', 'ELECTIVA PROFESIONAL III — INFORMÁTICA FORENSE', 1, 'INGENIERÍA DE SISTEMAS', 'Semestre IX',  'IX',  '1', 1),
('S9G1-03', 'ADMINISTRACIÓN DEL TALENTO HUMANO',               1, 'INGENIERÍA DE SISTEMAS', 'Semestre IX',  'IX',  '1', 1),
('S9G1-04', 'PRÁCTICA EMPRESARIAL',                            1, 'INGENIERÍA DE SISTEMAS', 'Semestre IX',  'IX',  '1', 1),
('S9G1-05', 'GERENCIA DE PROYECTOS',                           1, 'INGENIERÍA DE SISTEMAS', 'Semestre IX',  'IX',  '1', 1);

-- ── ASIGNACIÓN DE PROFESORES ────────────────────────────────
-- IDs impares → profesor1 (id=5), IDs pares → profesor2 (id=6)
-- NOTA: los INSERT no incluyen profesor_id, se asigna aquí.
-- Esto garantiza que INNER JOIN usuarios u ON c.profesor_id=u.id
-- funcione en getPaginated() y todas las vistas de sesiones.
UPDATE cursos SET profesor_id = 5 WHERE id % 2 = 1;
UPDATE cursos SET profesor_id = 6 WHERE id % 2 = 0;

-- ============================================================
-- RESUMEN
-- Usuarios:  6 (2 super_admin, 2 admin, 2 profesor)
-- Programas: 1 (INGENIERÍA DE SISTEMAS)
-- Cursos:   63 distribuidos en semestres I–IV, VI–IX (VIII en 2 grupos)
--           Semestre V: PENDIENTE (no se proporcionaron datos)
--           profesor1 (id=5): cursos con id impar
--           profesor2 (id=6): cursos con id par
--
-- CREDENCIALES DE PRUEBA:
--   superadmin1 / SuperAdmin2024!
--   superadmin2 / SuperAdmin2024!
--   admin1      / Admin2024!
--   admin2      / Admin2024!
--   profesor1   / Profesor2024!
--   profesor2   / Profesor2024!
-- ============================================================
