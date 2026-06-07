<?php
/**
 * Plantilla de configuración de entorno.
 * Copiar este archivo como config/env.local.php y completar los valores reales.
 * NUNCA versionar env.local.php (está en .gitignore).
 */
return [
    // Base de datos
    'DB_HOST'     => 'localhost',
    'DB_NAME'     => 'asistencia_db',
    'DB_USER'     => 'root',
    'DB_PASS'     => '',

    // SMTP — credenciales de correo electrónico
    'SMTP_HOST'       => 'smtp.gmail.com',
    'SMTP_PORT'       => '587',
    'SMTP_USER'       => 'tu-email@gmail.com',
    'SMTP_PASS'       => 'tu-app-password',
    'SMTP_ENCRYPTION' => 'tls',          // 'tls' o 'ssl'
    'SMTP_FROM_EMAIL' => 'tu-email@gmail.com',
    'SMTP_FROM_NAME'  => 'Sistema de Asistencia',

    // Aplicación
    'APP_URL'         => 'http://localhost:8080/public',
    'APP_NAME'        => 'Sistema de Asistencia',
];
