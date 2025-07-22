<?php
/**
 * CONFIGURACIÓN DE EMAIL PARA HOSTINGER
 * 
 * Este archivo contiene la configuración de email que debes ajustar
 * según tu proveedor de hosting y servicio de correo.
 */

class EmailConfig {
    
    /**
     * Configuración para Gmail (recomendado)
     * Necesitas generar una "Contraseña de aplicación" en tu cuenta de Gmail
     */
    public static function getGmailConfig() {
        return [
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls', // o 'ssl' para puerto 465
            'smtp_username' => 'tu-email@gmail.com', // Tu email de Gmail
            'smtp_password' => 'tu-app-password', // Contraseña de aplicación de Gmail
            'email_from' => 'tu-email@gmail.com',
            'email_from_name' => 'Sistema de Asistencia - Universidad del Tolima'
        ];
    }
    
    /**
     * Configuración para Hostinger Email
     * Si tienes un dominio propio en Hostinger
     */
    public static function getHostingerConfig() {
        return [
            'smtp_host' => 'smtp.hostinger.com',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_username' => 'noreply@tudominio.com', // Email de tu dominio
            'smtp_password' => 'tu-password', // Contraseña del email
            'email_from' => 'noreply@tudominio.com',
            'email_from_name' => 'Sistema de Asistencia - Universidad del Tolima'
        ];
    }
    
    /**
     * Configuración para Outlook/Hotmail
     */
    public static function getOutlookConfig() {
        return [
            'smtp_host' => 'smtp-mail.outlook.com',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_username' => 'tu-email@outlook.com', // o @hotmail.com
            'smtp_password' => 'tu-password',
            'email_from' => 'tu-email@outlook.com',
            'email_from_name' => 'Sistema de Asistencia - Universidad del Tolima'
        ];
    }
    
    /**
     * Obtener la configuración activa
     * Cambia el método según tu proveedor
     */
    public static function getActiveConfig() {
        // Cambia este método según tu proveedor:
        // return self::getGmailConfig();
        // return self::getHostingerConfig();
        // return self::getOutlookConfig();
        
        return self::getGmailConfig(); // Por defecto Gmail
    }
    
    /**
     * Insertar configuración en la base de datos
     */
    public static function insertConfigToDatabase($db) {
        $config = self::getActiveConfig();
        $conn = $db->connect();
        
        foreach ($config as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO configuracion (clave, valor, descripcion) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
            $description = self::getConfigDescription($key);
            $stmt->bind_param("sss", $key, $value, $description);
            $stmt->execute();
        }
        
        return true;
    }
    
    private static function getConfigDescription($key) {
        $descriptions = [
            'smtp_host' => 'Servidor SMTP para envío de correos',
            'smtp_port' => 'Puerto del servidor SMTP',
            'smtp_encryption' => 'Tipo de encriptación SMTP (tls/ssl)',
            'smtp_username' => 'Usuario para autenticación SMTP',
            'smtp_password' => 'Contraseña para autenticación SMTP',
            'email_from' => 'Dirección de correo remitente',
            'email_from_name' => 'Nombre del remitente'
        ];
        
        return $descriptions[$key] ?? 'Configuración de email';
    }
}

/**
 * INSTRUCCIONES DE CONFIGURACIÓN:
 * 
 * 1. PARA GMAIL:
 *    - Ve a tu cuenta de Google
 *    - Activa la verificación en 2 pasos
 *    - Genera una "Contraseña de aplicación"
 *    - Usa esa contraseña en 'smtp_password'
 * 
 * 2. PARA HOSTINGER:
 *    - Crea una cuenta de email en tu panel de Hostinger
 *    - Usa las credenciales de esa cuenta
 * 
 * 3. PARA OUTLOOK:
 *    - Usa tu email y contraseña normal
 *    - Asegúrate de tener habilitado SMTP
 * 
 * 4. ACTUALIZAR EN BASE DE DATOS:
 *    - Ejecuta este código después de instalar:
 *    
 *    require_once 'config/email.php';
 *    require_once 'config/database.php';
 *    $db = new Database();
 *    EmailConfig::insertConfigToDatabase($db);
 */
?>