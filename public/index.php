<?php
// Activar buffer de salida inmediatamente: evita "headers already sent"
// si algún archivo incluido tiene un byte accidental antes de <?php
ob_start();

// ─── Cabeceras HTTP de seguridad ──────────────────────────────────────────────
// Deben enviarse antes de cualquier output
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
// CSP básica: permite recursos propios + CDNs conocidos usados por la app
header(
    "Content-Security-Policy: " .
    "default-src 'self'; " .
    "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
    "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; " .
    "font-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net data:; " .
    "img-src 'self' data: blob:; " .
    "connect-src 'self'; " .
    "frame-ancestors 'none';"
);

// ─── Configuración segura de sesión ───────────────────────────────────────────
// (debe hacerse ANTES de session_start, que ocurre en config.php)
ini_set('session.cookie_httponly', '1');    // Impide acceso JS a la cookie de sesión
ini_set('session.cookie_samesite', 'Lax'); // Mitiga CSRF cross-site
ini_set('session.use_strict_mode', '1');   // Rechaza IDs de sesión no iniciados por el servidor
ini_set('session.use_only_cookies', '1');  // Nunca pasar session ID en URL

// Activar Secure solo en HTTPS
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || ($_SERVER['SERVER_PORT'] ?? 80) == 443
           || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
ini_set('session.cookie_secure', $isHttps ? '1' : '0');

// ─── Cargar configuración y lanzar la app ────────────────────────────────────
require_once '../config/config.php';

require_once '../app/controllers/AppController.php';

$app = new AppController();
$app->start();
