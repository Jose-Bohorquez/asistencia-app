<?php
/**
 * Configuración SMTP del sistema.
 * Las credenciales se leen EXCLUSIVAMENTE desde variables de entorno del servidor
 * o desde config/env.local.php (nunca desde la base de datos).
 *
 * Para desarrollo local: copiar env.example.php → env.local.php y completar.
 * Para Docker:           definir variables de entorno en docker-compose.yml.
 * Para producción:       definir variables de entorno en el servidor (cPanel, .htaccess, etc.).
 */

function getSmtpConfig(): array {
    // Leer env.local.php si existe (desarrollo local)
    $localEnv = [];
    $localEnvPath = __DIR__ . '/env.local.php';
    if (file_exists($localEnvPath)) {
        $localEnv = require $localEnvPath;
    }

    $get = function(string $key, string $default = '') use ($localEnv): string {
        // 1) Variable de entorno del sistema (Docker / producción)
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }
        // 2) env.local.php (desarrollo)
        if (isset($localEnv[$key]) && $localEnv[$key] !== '') {
            return (string)$localEnv[$key];
        }
        return $default;
    };

    return [
        'smtp_host'       => $get('SMTP_HOST',       'smtp.gmail.com'),
        'smtp_port'       => (int)$get('SMTP_PORT',  '587'),
        'smtp_username'   => $get('SMTP_USER',        ''),
        'smtp_password'   => $get('SMTP_PASS',        ''),
        'smtp_encryption' => $get('SMTP_ENCRYPTION',  'tls'),
        'email_from'      => $get('SMTP_FROM_EMAIL',  ''),
        'email_from_name' => $get('SMTP_FROM_NAME',   'Sistema de Asistencia'),
    ];
}
