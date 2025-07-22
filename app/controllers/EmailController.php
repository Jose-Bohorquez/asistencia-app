<?php
// Incluir PHPMailer
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailController {
    private $db;
    private $config;
    
    public function __construct($db) {
        $this->db = $db;
        $this->loadEmailConfig();
    }
    
    private function loadEmailConfig() {
        // Cargar configuración de email desde la base de datos
        $conn = $this->db->connect();
        $stmt = $conn->prepare("SELECT clave, valor FROM configuracion WHERE clave LIKE 'smtp_%' OR clave LIKE 'email_%'");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $this->config = [];
        while ($row = $result->fetch_assoc()) {
            $this->config[$row['clave']] = $row['valor'];
        }
        
        // Valores por defecto si no están en la BD
        $defaults = [
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => '587',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
            'email_from' => 'noreply@universidad.edu',
            'email_from_name' => 'Sistema de Asistencia - Universidad del Tolima'
        ];
        
        foreach ($defaults as $key => $value) {
            if (!isset($this->config[$key])) {
                $this->config[$key] = $value;
            }
        }
    }
    
    public function enviarAsistenciaPorCorreo($sesion_id, $formato, $user_id) {
        try {
            // Obtener información del usuario
            $conn = $this->db->connect();
            $stmt = $conn->prepare("SELECT email, nombre FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $usuario = $result->fetch_assoc();
            $stmt->close();
            
            if (!$usuario || empty($usuario['email'])) {
                return ['success' => false, 'message' => 'No se encontró el correo del usuario'];
            }
            
            // Obtener datos de la sesión
            $stmt = $conn->prepare("
                SELECT s.*, c.nombre as curso_nombre, c.codigo, c.programa, c.area, c.semestre, c.grupo, c.aula, c.sede
                FROM sesiones s
                JOIN cursos c ON s.curso_id = c.id
                WHERE s.id = ?
            ");
            $stmt->bind_param("i", $sesion_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $sesion = $result->fetch_assoc();
            $stmt->close();
            
            if (!$sesion) {
                return ['success' => false, 'message' => 'Sesión no encontrada'];
            }
            
            // Verificar permisos (profesor solo puede ver sus propias sesiones)
            if ($_SESSION['user_rol'] === 'profesor') {
                $stmt = $conn->prepare("SELECT profesor_id FROM cursos WHERE id = ?");
                $stmt->bind_param("i", $sesion['curso_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $curso = $result->fetch_assoc();
                $stmt->close();
                
                if (!$curso || $curso['profesor_id'] != $user_id) {
                    return ['success' => false, 'message' => 'No tienes permisos para esta sesión'];
                }
            }
            
            // Obtener datos de asistencia
            $stmt = $conn->prepare("
                SELECT a.*, e.nombre, e.documento, e.codigo, e.telefono, e.direccion, e.correo
                FROM asistencias a
                JOIN estudiantes e ON a.estudiante_id = e.id
                WHERE a.sesion_id = ?
                ORDER BY a.hora_registro
            ");
            $stmt->bind_param("i", $sesion_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $asistencias = [];
            while ($row = $result->fetch_assoc()) {
                $asistencias[] = $row;
            }
            $stmt->close();
            
            // Generar archivo según formato
            if ($formato === 'pdf') {
                $archivo = $this->generarPDF($sesion, $asistencias);
                $nombreArchivo = "asistencia_sesion_{$sesion_id}.pdf";
                $tipoMime = 'application/pdf';
            } else {
                $archivo = $this->generarExcel($sesion, $asistencias);
                $nombreArchivo = "asistencia_sesion_{$sesion_id}.xls";
                $tipoMime = 'application/vnd.ms-excel';
            }
            
            // Enviar correo
            $resultado = $this->enviarCorreo(
                $usuario['email'],
                $usuario['nombre'],
                $sesion,
                $archivo,
                $nombreArchivo,
                $tipoMime,
                $formato
            );
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log('Error en enviarAsistenciaPorCorreo: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }
    
    private function generarPDF($sesion, $asistencias) {
        // Para simplicidad, generamos HTML que se puede convertir a PDF
        // En un entorno de producción, usarías una librería como TCPDF o DOMPDF
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
    
    private function generarExcel($sesion, $asistencias) {
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
    
    private function enviarCorreo($email, $nombre, $sesion, $archivo, $nombreArchivo, $tipoMime, $formato) {
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
            return ['success' => false, 'message' => 'Error al enviar el correo: ' . $e->getMessage()];
        }
    }
}
?>