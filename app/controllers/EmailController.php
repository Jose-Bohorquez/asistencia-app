<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Email.php';
require_once __DIR__ . '/../../config/email.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Controlador de Email
 * Maneja el envío de correos electrónicos del sistema
 */
class EmailController extends BaseController {
    private $emailModel;
    private $config;
    
    public function __construct() {
        parent::__construct();
        
        // Inicializar modelo
        $this->emailModel = new Email();
        
        // Cargar configuración de email
        $this->loadEmailConfig();
    }
    
    /**
     * Método principal para manejar las peticiones
     */
    public function handleRequest() {
        $action = $_GET['action'] ?? 'index';
        
        switch ($action) {
            case 'send_attendance':
                $this->sendAttendanceReport();
                break;
            case 'send_notification':
                $this->sendNotification();
                break;
            case 'test_config':
                $this->testEmailConfig();
                break;
            case 'config':
                $this->configureEmail();
                break;
            default:
                $this->index();
        }
    }
    
    /**
     * Página principal de configuración de email
     */
    public function index() {
        // Verificar permisos - solo super_admin puede configurar email
        if (!$this->hasPermission('email_config')) {
            $this->redirectUnauthorized();
            return;
        }
        
        try {
            $this->render('admin/email_config', [
                'page_title' => 'Configuración de Email',
                'config' => $this->config,
                'can_test' => $this->hasPermission('email_test')
            ]);
            
        } catch (Exception $e) {
            $this->handleEmailError($e, 'Error al cargar la configuración de email');
        }
    }
    
    /**
     * Enviar reporte de asistencia por correo
     */
    public function sendAttendanceReport() {
        // Verificar permisos
        if (!$this->hasPermission('reportes_email')) {
            $this->jsonResponse(['error' => 'No tienes permisos para enviar reportes por email'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }
        
        // Verificar token CSRF
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'Token de seguridad inválido'], 400);
            return;
        }
        
        $sesion_id = intval($_POST['sesion_id'] ?? 0);
        $formato = $this->sanitizeInput($_POST['formato'] ?? 'excel');
        $email_destino = $this->sanitizeInput($_POST['email'] ?? '');
        
        // Validar datos
        if ($sesion_id <= 0) {
            $this->jsonResponse(['error' => 'ID de sesión no válido'], 400);
            return;
        }
        
        if (empty($email_destino) || !filter_var($email_destino, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['error' => 'Email de destino no válido'], 400);
            return;
        }
        
        if (!in_array($formato, ['excel', 'pdf'])) {
            $this->jsonResponse(['error' => 'Formato no válido'], 400);
            return;
        }
        
        try {
            // Verificar que la sesión existe y el usuario tiene permisos
            $sesion = $this->emailModel->getSessionData($sesion_id);
            if (!$sesion) {
                $this->jsonResponse(['error' => 'Sesión no encontrada'], 404);
                return;
            }
            
            // Verificar permisos específicos para profesores
            if ($this->currentUser['rol'] === 'profesor') {
                if (!$this->emailModel->canUserAccessSession($this->currentUser['id'], $sesion_id)) {
                    $this->jsonResponse(['error' => 'No tienes permisos para esta sesión'], 403);
                    return;
                }
            }
            
            // Obtener datos de asistencia
            $asistencias = $this->emailModel->getAttendanceData($sesion_id);
            
            // Generar archivo según formato
            if ($formato === 'pdf') {
                $archivo = $this->generatePDFReport($sesion, $asistencias);
                $nombreArchivo = "asistencia_sesion_{$sesion_id}.pdf";
                $tipoMime = 'application/pdf';
            } else {
                $archivo = $this->generateExcelReport($sesion, $asistencias);
                $nombreArchivo = "asistencia_sesion_{$sesion_id}.xlsx";
                $tipoMime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            }
            
            // Enviar correo
            $resultado = $this->sendEmail(
                $email_destino,
                $this->currentUser['nombre'],
                $sesion,
                $archivo,
                $nombreArchivo,
                $tipoMime,
                $formato
            );
            
            if ($resultado['success']) {
                $this->logActivity('email_sent', $sesion_id, null, [
                    'tipo' => 'reporte_asistencia',
                    'formato' => $formato,
                    'destinatario' => $email_destino
                ]);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Reporte enviado correctamente a ' . $email_destino
                ]);
            } else {
                $this->jsonResponse(['error' => $resultado['message']], 500);
            }
            
        } catch (Exception $e) {
            $this->handleEmailError($e, 'Error al enviar el reporte por email');
        }
    }
    
    /**
     * Enviar notificación por correo
     */
    public function sendNotification() {
        // Verificar permisos
        if (!$this->hasPermission('notifications_send')) {
            $this->jsonResponse(['error' => 'No tienes permisos para enviar notificaciones'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }
        
        // Verificar token CSRF
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'Token de seguridad inválido'], 400);
            return;
        }
        
        $data = [
            'destinatarios' => $_POST['destinatarios'] ?? [],
            'asunto' => $this->sanitizeInput($_POST['asunto'] ?? ''),
            'mensaje' => $this->sanitizeInput($_POST['mensaje'] ?? ''),
            'tipo' => $this->sanitizeInput($_POST['tipo'] ?? 'general')
        ];
        
        // Validar datos
        $errors = [];
        
        if (empty($data['destinatarios']) || !is_array($data['destinatarios'])) {
            $errors[] = 'Debe seleccionar al menos un destinatario';
        }
        
        if (empty($data['asunto'])) {
            $errors[] = 'El asunto es obligatorio';
        }
        
        if (empty($data['mensaje'])) {
            $errors[] = 'El mensaje es obligatorio';
        }
        
        if (!empty($errors)) {
            $this->jsonResponse(['errors' => $errors], 400);
            return;
        }
        
        try {
            $enviados = 0;
            $errores = [];
            
            foreach ($data['destinatarios'] as $email) {
                $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
                if (!$email) {
                    $errores[] = "Email inválido: $email";
                    continue;
                }
                
                $resultado = $this->sendNotificationEmail(
                    $email,
                    $data['asunto'],
                    $data['mensaje'],
                    $data['tipo']
                );
                
                if ($resultado['success']) {
                    $enviados++;
                } else {
                    $errores[] = "Error enviando a $email: " . $resultado['message'];
                }
            }
            
            $this->logActivity('notifications_sent', null, null, [
                'tipo' => $data['tipo'],
                'destinatarios_total' => count($data['destinatarios']),
                'enviados' => $enviados,
                'errores' => count($errores)
            ]);
            
            if ($enviados > 0) {
                $message = "Se enviaron $enviados notificaciones correctamente";
                if (!empty($errores)) {
                    $message .= ". Errores: " . implode(', ', $errores);
                }
                $this->jsonResponse(['success' => true, 'message' => $message]);
            } else {
                $this->jsonResponse(['error' => 'No se pudo enviar ninguna notificación'], 500);
            }
            
        } catch (Exception $e) {
            $this->handleEmailError($e, 'Error al enviar notificaciones');
        }
    }
    
    /**
     * Probar configuración de email
     */
    public function testEmailConfig() {
        // Verificar permisos
        if (!$this->hasPermission('email_test')) {
            $this->jsonResponse(['error' => 'No tienes permisos para probar la configuración'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }
        
        $email_test = filter_var($_POST['email_test'] ?? '', FILTER_VALIDATE_EMAIL);
        if (!$email_test) {
            $this->jsonResponse(['error' => 'Email de prueba no válido'], 400);
            return;
        }
        
        try {
            $resultado = $this->sendTestEmail($email_test);
            
            if ($resultado['success']) {
                $this->logActivity('email_test', null, null, ['email_test' => $email_test]);
                $this->jsonResponse(['success' => true, 'message' => 'Email de prueba enviado correctamente']);
            } else {
                $this->jsonResponse(['error' => $resultado['message']], 500);
            }
            
        } catch (Exception $e) {
            $this->handleEmailError($e, 'Error al probar la configuración de email');
        }
    }
    
    /**
     * Configuración SMTP — solo lectura.
     * Las credenciales se gestionan mediante variables de entorno del servidor
     * (SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_ENCRYPTION, SMTP_FROM_EMAIL, SMTP_FROM_NAME)
     * o en config/env.local.php para desarrollo local.
     * No se permite modificar la configuración SMTP desde la aplicación web.
     */
    public function configureEmail() {
        if (!$this->hasPermission('email_config')) {
            $this->jsonResponse(['error' => 'No tienes permisos para ver la configuración de email'], 403);
            return;
        }
        $this->jsonResponse([
            'info' => 'La configuración SMTP se gestiona mediante variables de entorno del servidor.',
            'variables' => ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS', 'SMTP_ENCRYPTION', 'SMTP_FROM_EMAIL', 'SMTP_FROM_NAME'],
            'smtp_host_configured' => !empty($this->config['smtp_host']),
            'smtp_from_configured' => !empty($this->config['email_from']),
        ]);
    }
    
    /**
     * Cargar configuración SMTP desde variables de entorno / env.local.php.
     * Las credenciales NUNCA se leen desde la base de datos.
     */
    private function loadEmailConfig() {
        $this->config = getSmtpConfig();
    }
    
    /**
     * Generar reporte PDF
     */
    private function generatePDFReport($sesion, $asistencias) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Asistencia - <?= htmlspecialchars($sesion['curso_nombre']) ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #000; padding: 8px; text-align: left; }
                th { background-color: #f0f0f0; font-weight: bold; }
                .header { text-align: center; margin-bottom: 20px; }
                .info { margin-bottom: 15px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>UNIVERSIDAD DEL TOLIMA</h2>
                <h3>CONTROL ASISTENCIA ESTUDIANTES</h3>
            </div>
            
            <div class="info">
                <p><strong>Curso:</strong> <?= htmlspecialchars($sesion['curso_nombre']) ?></p>
                <p><strong>Código:</strong> <?= htmlspecialchars($sesion['codigo']) ?></p>
                <p><strong>Programa:</strong> <?= htmlspecialchars($sesion['programa']) ?></p>
                <p><strong>Área:</strong> <?= htmlspecialchars($sesion['area'] ?? 'N/A') ?></p>
                <p><strong>Semestre:</strong> <?= htmlspecialchars($sesion['semestre']) ?></p>
                <p><strong>Grupo:</strong> <?= htmlspecialchars($sesion['grupo']) ?></p>
                <p><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($sesion['fecha'])) ?></p>
                <p><strong>Total Asistentes:</strong> <?= count($asistencias) ?></p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Nombre Estudiante</th>
                        <th>Documento</th>
                        <th>Código</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Hora Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($asistencias as $asistencia): ?>
                    <tr>
                        <td><?= htmlspecialchars($asistencia['nombre']) ?></td>
                        <td><?= htmlspecialchars($asistencia['documento']) ?></td>
                        <td><?= htmlspecialchars($asistencia['codigo']) ?></td>
                        <td><?= htmlspecialchars($asistencia['telefono']) ?></td>
                        <td><?= htmlspecialchars($asistencia['correo']) ?></td>
                        <td><?= date('H:i:s', strtotime($asistencia['hora_registro'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Generar reporte Excel
     */
    private function generateExcelReport($sesion, $asistencias) {
        ob_start();
        
        // Generar HTML con formato Excel
        echo "\xEF\xBB\xBF"; // BOM para UTF-8
        echo '<html><head><meta charset="UTF-8"></head><body>';
        echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%; font-family: Arial, sans-serif;">';
        
        // Encabezado principal
        echo '<tr><td colspan="7" style="text-align: center; font-weight: bold; font-size: 14px; background-color: #f0f0f0; padding: 10px;">';
        echo 'PLANIFICACIÓN, DESARROLLO Y VERIFICACIÓN DE LA LABOR ACADÉMICA<br>';
        echo 'CONTROL ASISTENCIA ESTUDIANTES';
        echo '</td></tr>';
        
        // Información del curso
        echo '<tr>';
        echo '<td style="font-weight: bold;">ÁREA:</td><td>' . htmlspecialchars($sesion['area'] ?? 'N/A') . '</td>';
        echo '<td style="font-weight: bold;">PROGRAMA:</td><td>' . htmlspecialchars($sesion['programa']) . '</td>';
        echo '<td style="font-weight: bold;">CÓDIGO:</td><td colspan="2">' . htmlspecialchars($sesion['codigo']) . '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<td style="font-weight: bold;">SEMESTRE:</td><td>' . htmlspecialchars($sesion['semestre']) . '</td>';
        echo '<td style="font-weight: bold;">GRUPO:</td><td>' . htmlspecialchars($sesion['grupo']) . '</td>';
        echo '<td style="font-weight: bold;">FECHA:</td><td colspan="2">' . date('d/m/Y', strtotime($sesion['fecha'])) . '</td>';
        echo '</tr>';
        
        // Encabezados de la tabla de estudiantes
        echo '<tr style="background-color: #e0e0e0; font-weight: bold; text-align: center;">';
        echo '<th style="border: 1px solid #000; padding: 8px;">NOMBRE ESTUDIANTE</th>';
        echo '<th style="border: 1px solid #000; padding: 8px;">DOCUMENTO</th>';
        echo '<th style="border: 1px solid #000; padding: 8px;">CÓDIGO</th>';
        echo '<th style="border: 1px solid #000; padding: 8px;">TELÉFONO</th>';
        echo '<th style="border: 1px solid #000; padding: 8px;">DIRECCIÓN</th>';
        echo '<th style="border: 1px solid #000; padding: 8px;">CORREO</th>';
        echo '<th style="border: 1px solid #000; padding: 8px;">HORA REGISTRO</th>';
        echo '</tr>';
        
        // Datos de estudiantes
        foreach ($asistencias as $asistencia) {
            echo '<tr>';
            echo '<td style="border: 1px solid #000; padding: 5px;">' . htmlspecialchars($asistencia['nombre']) . '</td>';
            echo '<td style="border: 1px solid #000; padding: 5px;">' . htmlspecialchars($asistencia['documento']) . '</td>';
            echo '<td style="border: 1px solid #000; padding: 5px;">' . htmlspecialchars($asistencia['codigo']) . '</td>';
            echo '<td style="border: 1px solid #000; padding: 5px;">' . htmlspecialchars($asistencia['telefono']) . '</td>';
            echo '<td style="border: 1px solid #000; padding: 5px;">' . htmlspecialchars($asistencia['direccion']) . '</td>';
            echo '<td style="border: 1px solid #000; padding: 5px;">' . htmlspecialchars($asistencia['correo']) . '</td>';
            echo '<td style="border: 1px solid #000; padding: 5px;">' . date('H:i:s', strtotime($asistencia['hora_registro'])) . '</td>';
            echo '</tr>';
        }
        
        echo '</table></body></html>';
        return ob_get_clean();
    }
    
    /**
     * Enviar email con reporte de asistencia
     */
    private function sendEmail($email, $nombre, $sesion, $archivo, $nombreArchivo, $tipoMime, $formato) {
        try {
            $mail = new PHPMailer(true);
            
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_username'];
            $mail->Password = $this->config['smtp_password'];
            $mail->SMTPSecure = $this->config['smtp_encryption'];
            $mail->Port = (int)$this->config['smtp_port'];
            $mail->CharSet = 'UTF-8';
            
            // Configuración del remitente
            $mail->setFrom($this->config['email_from'], $this->config['email_from_name']);
            
            // Destinatario
            $mail->addAddress($email, $nombre);
            
            // Asunto del correo
            $asunto = "Asistencia - {$sesion['curso_nombre']} - " . date('d/m/Y', strtotime($sesion['fecha']));
            
            // Contenido del email
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            
            $mensaje = "<html><body>";
            $mensaje .= "<h3>Hola {$nombre},</h3>";
            $mensaje .= "<p>Se adjunta el archivo de asistencia solicitado:</p>";
            $mensaje .= "<ul>";
            $mensaje .= "<li><strong>Curso:</strong> {$sesion['curso_nombre']}</li>";
            $mensaje .= "<li><strong>Fecha:</strong> " . date('d/m/Y', strtotime($sesion['fecha'])) . "</li>";
            $mensaje .= "<li><strong>Formato:</strong> " . strtoupper($formato) . "</li>";
            $mensaje .= "</ul>";
            $mensaje .= "<p>Saludos,<br>Sistema de Gestión de Asistencia<br>Universidad del Tolima</p>";
            $mensaje .= "</body></html>";
            
            $mail->Body = $mensaje;
            
            // Adjuntar archivo
            if ($archivo && $nombreArchivo) {
                $mail->addStringAttachment($archivo, $nombreArchivo, 'base64', $tipoMime);
            }
            
            // Enviar email
            $mail->send();
            return ['success' => true, 'message' => 'Correo enviado exitosamente'];
            
        } catch (Exception $e) {
            error_log('Error enviando correo: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al enviar el correo. Verifique la configuración SMTP.'];
        }
    }

    /**
     * Enviar email de notificación
     */
    private function sendNotificationEmail($email, $asunto, $mensaje, $tipo) {
        try {
            $mail = new PHPMailer(true);
            
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_username'];
            $mail->Password = $this->config['smtp_password'];
            $mail->SMTPSecure = $this->config['smtp_encryption'];
            $mail->Port = (int)$this->config['smtp_port'];
            $mail->CharSet = 'UTF-8';
            
            // Configuración del remitente
            $mail->setFrom($this->config['email_from'], $this->config['email_from_name']);
            
            // Destinatario
            $mail->addAddress($email);
            
            // Contenido del email
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            
            $contenido = "<html><body>";
            $contenido .= "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>";
            $contenido .= "<h2 style='color: #333;'>" . htmlspecialchars($asunto) . "</h2>";
            $contenido .= "<div style='background-color: #f9f9f9; padding: 20px; border-radius: 5px;'>";
            $contenido .= nl2br(htmlspecialchars($mensaje));
            $contenido .= "</div>";
            $contenido .= "<hr style='margin: 20px 0; border: none; border-top: 1px solid #ddd;'>";
            $contenido .= "<p style='color: #666; font-size: 12px;'>";
            $contenido .= "Este mensaje fue enviado desde el Sistema de Gestión de Asistencia<br>";
            $contenido .= "Universidad del Tolima";
            $contenido .= "</p>";
            $contenido .= "</div>";
            $contenido .= "</body></html>";
            
            $mail->Body = $contenido;
            
            // Enviar email
            $mail->send();
            return ['success' => true, 'message' => 'Notificación enviada exitosamente'];
            
        } catch (Exception $e) {
            error_log('Error enviando notificación: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al enviar la notificación. Verifique la configuración SMTP.'];
        }
    }
    
    /**
     * Enviar email de prueba
     */
    private function sendTestEmail($email) {
        try {
            $mail = new PHPMailer(true);
            
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_username'];
            $mail->Password = $this->config['smtp_password'];
            $mail->SMTPSecure = $this->config['smtp_encryption'];
            $mail->Port = (int)$this->config['smtp_port'];
            $mail->CharSet = 'UTF-8';
            
            // Configuración del remitente
            $mail->setFrom($this->config['email_from'], $this->config['email_from_name']);
            
            // Destinatario
            $mail->addAddress($email);
            
            // Contenido del email
            $mail->isHTML(true);
            $mail->Subject = 'Prueba de Configuración - Sistema de Asistencia';
            
            $mensaje = "<html><body>";
            $mensaje .= "<h3>Prueba de Configuración de Email</h3>";
            $mensaje .= "<p>Este es un email de prueba para verificar que la configuración SMTP está funcionando correctamente.</p>";
            $mensaje .= "<p><strong>Fecha y hora:</strong> " . date('d/m/Y H:i:s') . "</p>";
            $mensaje .= "<p><strong>Usuario que realizó la prueba:</strong> " . htmlspecialchars($this->currentUser['nombre']) . "</p>";
            $mensaje .= "<p>Si recibiste este email, la configuración está funcionando correctamente.</p>";
            $mensaje .= "<hr>";
            $mensaje .= "<p style='color: #666; font-size: 12px;'>Sistema de Gestión de Asistencia - Universidad del Tolima</p>";
            $mensaje .= "</body></html>";
            
            $mail->Body = $mensaje;
            
            // Enviar email
            $mail->send();
            return ['success' => true, 'message' => 'Email de prueba enviado exitosamente'];
            
        } catch (Exception $e) {
            error_log('Error enviando email de prueba: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al enviar el email de prueba. Verifique la configuración SMTP.'];
        }
    }
    
    /**
     * Verificar si el usuario tiene un permiso específico
     */
    protected function hasPermission($permission) {
        return $this->middlewareManager->checkPermission($permission);
    }
    
    /**
     * Redirigir cuando no se tienen permisos
     */
    private function redirectUnauthorized() {
        $this->setFlashMessage('No tienes permisos para acceder a esta sección', 'error');
        $this->redirect('index.php?page=dashboard');
    }
    
    /**
     * Manejar errores
     */
    protected function handleEmailError($exception, $userMessage = 'Ha ocurrido un error') {
        // Log del error
        error_log($exception->getMessage());
        
        if ($this->isAjaxRequest()) {
            $this->jsonResponse(['error' => $userMessage], 500);
        } else {
            $this->setFlashMessage($userMessage, 'error');
            $this->redirect('index.php?page=dashboard');
        }
    }
}
