<?php
require_once __DIR__ . '/../../config/email.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

/**
 * Servicio centralizado de envío de correos transaccionales.
 * Lee la configuración SMTP desde variables de entorno (nunca desde BD).
 */
class MailService {

    private array $config;

    public function __construct() {
        $this->config = getSmtpConfig();
    }

    /**
     * Envía el correo de activación de cuenta.
     */
    public function enviarActivacion(string $destinatario, string $nombre, string $tokenReal): bool {
        $config = $this->config;

        // Leer APP_URL desde env o env.local.php
        $localEnv = [];
        $localEnvPath = __DIR__ . '/../../config/env.local.php';
        if (file_exists($localEnvPath)) {
            $localEnv = require $localEnvPath;
        }
        $appUrl = getenv('APP_URL') ?: ($localEnv['APP_URL'] ?? 'http://localhost:8080/public');
        $appName = getenv('APP_NAME') ?: ($localEnv['APP_NAME'] ?? 'Sistema de Asistencia');

        $enlace = rtrim($appUrl, '/') . '/index.php?page=activacion&token=' . urlencode($tokenReal);

        $asunto  = "Activa tu cuenta en {$appName}";
        $cuerpo  = $this->plantillaActivacion($nombre, $enlace, $appName);

        return $this->enviar($destinatario, $nombre, $asunto, $cuerpo);
    }

    /**
     * Envía el correo de recuperación de contraseña.
     */
    public function enviarResetPassword(string $destinatario, string $nombre, string $tokenReal): bool {
        $localEnv = [];
        $localEnvPath = __DIR__ . '/../../config/env.local.php';
        if (file_exists($localEnvPath)) {
            $localEnv = require $localEnvPath;
        }
        $appUrl  = getenv('APP_URL') ?: ($localEnv['APP_URL'] ?? 'http://localhost:8080/public');
        $appName = getenv('APP_NAME') ?: ($localEnv['APP_NAME'] ?? 'Sistema de Asistencia');

        $enlace  = rtrim($appUrl, '/') . '/index.php?page=reset-password&token=' . urlencode($tokenReal);
        $asunto  = "Recupera tu contraseña en {$appName}";
        $cuerpo  = $this->plantillaReset($nombre, $enlace, $appName);

        return $this->enviar($destinatario, $nombre, $asunto, $cuerpo);
    }

    /**
     * Envío genérico con PHPMailer.
     */
    public function enviar(string $destinatario, string $nombreDest, string $asunto, string $cuerpoHtml): bool {
        if (empty($this->config['smtp_username'])) {
            error_log('MailService: SMTP no configurado. Define SMTP_USER en variables de entorno.');
            return false;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host        = $this->config['smtp_host'];
            $mail->SMTPAuth    = true;
            $mail->Username    = $this->config['smtp_username'];
            $mail->Password    = $this->config['smtp_password'];
            $mail->SMTPSecure  = $this->config['smtp_encryption'];
            $mail->Port        = (int)$this->config['smtp_port'];
            $mail->CharSet     = 'UTF-8';

            $mail->setFrom($this->config['email_from'], $this->config['email_from_name']);
            $mail->addAddress($destinatario, $nombreDest);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $cuerpoHtml;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>'], "\n", $cuerpoHtml));

            $mail->send();
            return true;

        } catch (MailerException $e) {
            error_log('MailService error: ' . $e->getMessage());
            return false;
        }
    }

    // -----------------------------------------------------------------------
    // Plantillas HTML
    // -----------------------------------------------------------------------

    private function plantillaActivacion(string $nombre, string $enlace, string $appName): string {
        $enlaceEsc = htmlspecialchars($enlace);
        $nombreEsc = htmlspecialchars($nombre);
        $appEsc    = htmlspecialchars($appName);
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#333;">
  <div style="background:#1d4ed8;padding:24px;text-align:center;border-radius:8px 8px 0 0;">
    <h1 style="color:#fff;margin:0;font-size:20px;">{$appEsc}</h1>
  </div>
  <div style="background:#f9fafb;padding:32px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 8px 8px;">
    <h2 style="color:#1d4ed8;">Activa tu cuenta</h2>
    <p>Hola <strong>{$nombreEsc}</strong>,</p>
    <p>Un administrador ha creado tu cuenta en <strong>{$appEsc}</strong>. Para completar tu registro y definir tu contraseña, haz clic en el botón de abajo:</p>
    <div style="text-align:center;margin:32px 0;">
      <a href="{$enlaceEsc}" style="background:#1d4ed8;color:#fff;padding:14px 28px;text-decoration:none;border-radius:6px;font-weight:bold;font-size:15px;">
        Activar mi cuenta
      </a>
    </div>
    <p style="color:#6b7280;font-size:13px;">
      Este enlace expira en <strong>48 horas</strong>. Si no puedes hacer clic en el botón, copia y pega esta URL en tu navegador:<br>
      <a href="{$enlaceEsc}" style="color:#1d4ed8;word-break:break-all;">{$enlaceEsc}</a>
    </p>
    <p style="color:#6b7280;font-size:12px;border-top:1px solid #e5e7eb;padding-top:16px;margin-top:24px;">
      Si no esperabas este correo, puedes ignorarlo de forma segura.<br>
      &copy; {$appEsc}
    </p>
  </div>
</body>
</html>
HTML;
    }

    private function plantillaReset(string $nombre, string $enlace, string $appName): string {
        $enlaceEsc = htmlspecialchars($enlace);
        $nombreEsc = htmlspecialchars($nombre);
        $appEsc    = htmlspecialchars($appName);
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#333;">
  <div style="background:#1d4ed8;padding:24px;text-align:center;border-radius:8px 8px 0 0;">
    <h1 style="color:#fff;margin:0;font-size:20px;">{$appEsc}</h1>
  </div>
  <div style="background:#f9fafb;padding:32px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 8px 8px;">
    <h2 style="color:#1d4ed8;">Recuperación de contraseña</h2>
    <p>Hola <strong>{$nombreEsc}</strong>,</p>
    <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta. Haz clic en el botón de abajo:</p>
    <div style="text-align:center;margin:32px 0;">
      <a href="{$enlaceEsc}" style="background:#1d4ed8;color:#fff;padding:14px 28px;text-decoration:none;border-radius:6px;font-weight:bold;font-size:15px;">
        Restablecer contraseña
      </a>
    </div>
    <p style="color:#6b7280;font-size:13px;">
      Este enlace expira en <strong>1 hora</strong>. Si no solicitaste este cambio, ignora este correo.<br>
      <a href="{$enlaceEsc}" style="color:#1d4ed8;word-break:break-all;">{$enlaceEsc}</a>
    </p>
    <p style="color:#6b7280;font-size:12px;border-top:1px solid #e5e7eb;padding-top:16px;margin-top:24px;">
      &copy; {$appEsc}
    </p>
  </div>
</body>
</html>
HTML;
    }
}
