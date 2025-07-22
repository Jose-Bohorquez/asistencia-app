<?php
/**
 * SCRIPT DE INSTALACIÓN PARA HOSTINGER
 * 
 * Este script te ayuda a configurar automáticamente el sistema
 * en tu hosting de Hostinger.
 */

// Incluir archivos necesarios
require_once 'config/database.php';
require_once 'config/email.php';

// Configuración
$ADMIN_PASSWORD = 'SuperAdmin2024!'; // Cambia esta contraseña
$ADMIN_EMAIL = 'admin@tudominio.com'; // Cambia este email

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Sistema de Asistencia</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: green; background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe8e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e8f0ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; }
        .step { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #005a87; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🎓 Instalación del Sistema de Asistencia</h1>
    <h2>Universidad del Tolima</h2>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'test_db') {
        echo "<div class='step'><h3>🔍 Probando conexión a la base de datos...</h3>";
        
        try {
            $db = new Database();
            $conn = $db->connect();
            
            if ($conn) {
                echo "<div class='success'>✅ Conexión exitosa a la base de datos</div>";
                
                // Verificar si las tablas existen
                $tables = ['usuarios', 'programas', 'cursos', 'sesiones', 'estudiantes', 'asistencias'];
                $missing_tables = [];
                
                foreach ($tables as $table) {
                    $result = $conn->query("SHOW TABLES LIKE '$table'");
                    if ($result->num_rows === 0) {
                        $missing_tables[] = $table;
                    }
                }
                
                if (empty($missing_tables)) {
                    echo "<div class='success'>✅ Todas las tablas están presentes</div>";
                } else {
                    echo "<div class='error'>❌ Faltan las siguientes tablas: " . implode(', ', $missing_tables) . "</div>";
                    echo "<div class='info'>💡 Necesitas ejecutar el archivo database_hostinger.sql en phpMyAdmin</div>";
                }
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error de conexión: " . $e->getMessage() . "</div>";
        }
        echo "</div>";
    }
    
    if ($action === 'create_admin') {
        echo "<div class='step'><h3>👤 Creando usuario administrador...</h3>";
        
        $username = $_POST['username'] ?? 'superadmin';
        $password = $_POST['password'] ?? $ADMIN_PASSWORD;
        $email = $_POST['email'] ?? $ADMIN_EMAIL;
        $nombre = $_POST['nombre'] ?? 'Super Administrador';
        
        try {
            $db = new Database();
            $conn = $db->connect();
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO usuarios (username, password, nombre, email, rol) VALUES (?, ?, ?, ?, 'super_admin') ON DUPLICATE KEY UPDATE password = VALUES(password), nombre = VALUES(nombre), email = VALUES(email)");
            $stmt->bind_param("ssss", $username, $hashed_password, $nombre, $email);
            
            if ($stmt->execute()) {
                echo "<div class='success'>✅ Usuario administrador creado/actualizado exitosamente</div>";
                echo "<div class='info'>📋 Credenciales:<br>Usuario: $username<br>Contraseña: $password<br>Email: $email</div>";
            } else {
                echo "<div class='error'>❌ Error al crear el usuario: " . $stmt->error . "</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
        echo "</div>";
    }
    
    if ($action === 'config_email') {
        echo "<div class='step'><h3>📧 Configurando email...</h3>";
        
        $smtp_host = $_POST['smtp_host'] ?? 'smtp.gmail.com';
        $smtp_port = $_POST['smtp_port'] ?? '587';
        $smtp_username = $_POST['smtp_username'] ?? '';
        $smtp_password = $_POST['smtp_password'] ?? '';
        $smtp_encryption = $_POST['smtp_encryption'] ?? 'tls';
        $email_from = $_POST['email_from'] ?? '';
        $email_from_name = $_POST['email_from_name'] ?? 'Sistema de Asistencia - Universidad del Tolima';
        
        try {
            $db = new Database();
            $conn = $db->connect();
            
            $configs = [
                'smtp_host' => $smtp_host,
                'smtp_port' => $smtp_port,
                'smtp_username' => $smtp_username,
                'smtp_password' => $smtp_password,
                'smtp_encryption' => $smtp_encryption,
                'email_from' => $email_from,
                'email_from_name' => $email_from_name
            ];
            
            foreach ($configs as $key => $value) {
                $stmt = $conn->prepare("INSERT INTO configuracion (clave, valor, descripcion) VALUES (?, ?, 'Configuración de email') ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
                $stmt->bind_param("ss", $key, $value);
                $stmt->execute();
            }
            
            echo "<div class='success'>✅ Configuración de email guardada exitosamente</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
        echo "</div>";
    }
    
    if ($action === 'test_email') {
        echo "<div class='step'><h3>📨 Probando envío de email...</h3>";
        
        $test_email = $_POST['test_email'] ?? '';
        
        if ($test_email) {
            try {
                require_once 'app/controllers/EmailController.php';
                $db = new Database();
                $emailController = new EmailController($db);
                
                // Crear un email de prueba
                $asunto = 'Prueba del Sistema de Asistencia';
                $mensaje = '<h3>¡Felicidades!</h3><p>El sistema de email está funcionando correctamente.</p><p>Sistema de Asistencia - Universidad del Tolima</p>';
                
                $result = $emailController->enviarCorreo($test_email, $asunto, $mensaje, null, null);
                
                if ($result) {
                    echo "<div class='success'>✅ Email de prueba enviado exitosamente a $test_email</div>";
                } else {
                    echo "<div class='error'>❌ Error al enviar el email de prueba</div>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='error'>❌ Debes proporcionar un email para la prueba</div>";
        }
        echo "</div>";
    }
}
?>

    <div class="step">
        <h3>📋 Paso 1: Verificar Base de Datos</h3>
        <p>Primero, asegúrate de haber ejecutado el archivo <code>database_hostinger.sql</code> en phpMyAdmin.</p>
        <form method="post">
            <input type="hidden" name="action" value="test_db">
            <button type="submit">🔍 Probar Conexión a BD</button>
        </form>
    </div>

    <div class="step">
        <h3>👤 Paso 2: Crear Usuario Administrador</h3>
        <form method="post">
            <input type="hidden" name="action" value="create_admin">
            <label>Usuario:</label>
            <input type="text" name="username" value="superadmin" required>
            
            <label>Contraseña:</label>
            <input type="password" name="password" value="<?php echo $ADMIN_PASSWORD; ?>" required>
            
            <label>Nombre:</label>
            <input type="text" name="nombre" value="Super Administrador" required>
            
            <label>Email:</label>
            <input type="email" name="email" value="<?php echo $ADMIN_EMAIL; ?>" required>
            
            <button type="submit">👤 Crear Administrador</button>
        </form>
    </div>

    <div class="step">
        <h3>📧 Paso 3: Configurar Email</h3>
        <form method="post">
            <input type="hidden" name="action" value="config_email">
            
            <label>Servidor SMTP:</label>
            <input type="text" name="smtp_host" value="smtp.gmail.com" required>
            
            <label>Puerto SMTP:</label>
            <input type="text" name="smtp_port" value="587" required>
            
            <label>Usuario SMTP (tu email):</label>
            <input type="email" name="smtp_username" placeholder="tu-email@gmail.com" required>
            
            <label>Contraseña SMTP (contraseña de aplicación):</label>
            <input type="password" name="smtp_password" placeholder="Contraseña de aplicación de Gmail" required>
            
            <label>Encriptación:</label>
            <select name="smtp_encryption" style="width: 100%; padding: 8px; margin: 5px 0;">
                <option value="tls">TLS (recomendado)</option>
                <option value="ssl">SSL</option>
            </select>
            
            <label>Email remitente:</label>
            <input type="email" name="email_from" placeholder="noreply@tudominio.com" required>
            
            <label>Nombre del remitente:</label>
            <input type="text" name="email_from_name" value="Sistema de Asistencia - Universidad del Tolima" required>
            
            <button type="submit">📧 Guardar Configuración</button>
        </form>
    </div>

    <div class="step">
        <h3>📨 Paso 4: Probar Email</h3>
        <form method="post">
            <input type="hidden" name="action" value="test_email">
            <label>Email de prueba:</label>
            <input type="email" name="test_email" placeholder="tu-email@gmail.com" required>
            <button type="submit">📨 Enviar Email de Prueba</button>
        </form>
    </div>

    <div class="info">
        <h3>📝 Instrucciones para Gmail:</h3>
        <ol>
            <li>Ve a tu cuenta de Google</li>
            <li>Activa la verificación en 2 pasos</li>
            <li>Ve a "Contraseñas de aplicación"</li>
            <li>Genera una nueva contraseña para "Correo"</li>
            <li>Usa esa contraseña en el campo "Contraseña SMTP"</li>
        </ol>
    </div>

    <div class="success">
        <h3>🎉 ¡Instalación Completada!</h3>
        <p>Una vez completados todos los pasos, puedes:</p>
        <ul>
            <li>Acceder al sistema con las credenciales del administrador</li>
            <li>Eliminar este archivo <code>install_hostinger.php</code> por seguridad</li>
            <li>Comenzar a usar el sistema de asistencia</li>
        </ul>
    </div>

</body>
</html>