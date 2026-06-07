<?php
// Zona horaria de la aplicación (Colombia UTC-5)
date_default_timezone_set('America/Bogota');

// Cargar env.local.php si existe (no versionado, para dev local y producción sin vars de entorno)
$_cfgEnv = [];
$_cfgEnvPath = __DIR__ . '/env.local.php';
if (file_exists($_cfgEnvPath)) {
    $_cfgEnv = require $_cfgEnvPath;
}

// Detectar si estamos dentro de un contenedor Docker como último fallback
$_inDocker = file_exists('/.dockerenv') || (getenv('DOCKER_CONTAINER') !== false && getenv('DOCKER_CONTAINER') !== '');

// ─── Base de datos ────────────────────────────────────────────────────────────
// Prioridad: variable de entorno > env.local.php > Docker > XAMPP/dev local
define('DB_HOST', getenv('DB_HOST') !== false ? getenv('DB_HOST') : ($_cfgEnv['DB_HOST'] ?? ($_inDocker ? 'db'        : 'localhost')));
define('DB_NAME', getenv('DB_NAME') !== false ? getenv('DB_NAME') : ($_cfgEnv['DB_NAME'] ?? 'asistencia_db'));
define('DB_USER', getenv('DB_USER') !== false ? getenv('DB_USER') : ($_cfgEnv['DB_USER'] ?? ($_inDocker ? 'developer' : 'root')));
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($_cfgEnv['DB_PASS'] ?? ($_inDocker ? 'developer' : '')));

// ─── Aplicación ───────────────────────────────────────────────────────────────
// En producción definir APP_URL y APP_NAME en env.local.php o como variables de entorno
define('APP_NAME', getenv('APP_NAME') !== false ? getenv('APP_NAME') : ($_cfgEnv['APP_NAME'] ?? 'Sistema de Control de Asistencia'));
define('APP_URL',  getenv('APP_URL')  !== false ? getenv('APP_URL')  : ($_cfgEnv['APP_URL']  ?? 'http://localhost:8080/public'));

// Limpiar variables temporales del scope global
unset($_cfgEnv, $_cfgEnvPath, $_inDocker);

// Configuración de sesiones
session_start();
