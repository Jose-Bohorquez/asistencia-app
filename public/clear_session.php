<?php
/**
 * Página para limpiar completamente sesiones y cookies
 * Útil para hacer pruebas limpias del sistema de autenticación
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<!DOCTYPE html>\n";
echo "<html lang='es'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "    <title>Limpiar Sesión - Sistema de Asistencia</title>\n";
echo "    <style>\n";
echo "        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f5f5f5; }\n";
echo "        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }\n";
echo "        .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }\n";
echo "        .info { color: #17a2b8; background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; }\n";
echo "        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }\n";
echo "        .btn:hover { background: #0056b3; }\n";
echo "        .btn-danger { background: #dc3545; }\n";
echo "        .btn-danger:hover { background: #c82333; }\n";
echo "        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";
echo "    <div class='container'>\n";
echo "        <h1>🧹 Limpiar Sesión y Cookies</h1>\n";

// Mostrar estado actual de la sesión
echo "        <div class='info'>\n";
echo "            <h3>📊 Estado Actual de la Sesión:</h3>\n";
if (!empty($_SESSION)) {
    echo "            <pre>" . htmlspecialchars(print_r($_SESSION, true)) . "</pre>\n";
} else {
    echo "            <p>✅ No hay datos de sesión activos</p>\n";
}
echo "        </div>\n";

// Mostrar cookies existentes
echo "        <div class='info'>\n";
echo "            <h3>🍪 Cookies Existentes:</h3>\n";
if (!empty($_COOKIE)) {
    echo "            <pre>" . htmlspecialchars(print_r($_COOKIE, true)) . "</pre>\n";
} else {
    echo "            <p>✅ No hay cookies activas</p>\n";
}
echo "        </div>\n";

// Procesar limpieza si se solicita
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    echo "        <div class='success'>\n";
    echo "            <h3>🧽 Limpiando sesión y cookies...</h3>\n";
    
    // Destruir sesión
    $_SESSION = array();
    
    // Eliminar cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir sesión
    session_destroy();
    
    // Eliminar cookies específicas de la aplicación
    $cookiesToDelete = ['remember_token', 'user_preferences', 'last_login'];
    foreach ($cookiesToDelete as $cookie) {
        if (isset($_COOKIE[$cookie])) {
            setcookie($cookie, '', time() - 3600, '/');
            setcookie($cookie, '', time() - 3600, '/public/');
            setcookie($cookie, '', time() - 3600, '/asistencia-app/');
        }
    }
    
    echo "            <p>✅ Sesión destruida completamente</p>\n";
    echo "            <p>✅ Cookies eliminadas</p>\n";
    echo "            <p>✅ Estado de autenticación limpiado</p>\n";
    echo "        </div>\n";
    
    // JavaScript para limpiar localStorage y sessionStorage
    echo "        <script>\n";
    echo "            // Limpiar almacenamiento local\n";
    echo "            if (typeof(Storage) !== 'undefined') {\n";
    echo "                localStorage.clear();\n";
    echo "                sessionStorage.clear();\n";
    echo "                console.log('✅ LocalStorage y SessionStorage limpiados');\n";
    echo "            }\n";
    echo "        </script>\n";
    
    echo "        <div class='info'>\n";
    echo "            <p><strong>🎯 Limpieza completada!</strong> Ahora puedes hacer pruebas limpias del sistema.</p>\n";
    echo "        </div>\n";
}

echo "        <div style='margin-top: 30px;'>\n";
echo "            <h3>🔧 Acciones Disponibles:</h3>\n";
echo "            <a href='?action=clear' class='btn btn-danger'>🧹 Limpiar Todo</a>\n";
echo "            <a href='index.php?page=login' class='btn'>🔑 Ir al Login</a>\n";
echo "            <a href='index.php?page=dashboard' class='btn'>📊 Probar Dashboard</a>\n";
echo "            <a href='clear_session.php' class='btn'>🔄 Recargar Estado</a>\n";
echo "        </div>\n";

echo "        <div style='margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 5px;'>\n";
echo "            <h4>📝 Instrucciones para Pruebas:</h4>\n";
echo "            <ol>\n";
echo "                <li>Haz clic en <strong>\"Limpiar Todo\"</strong> para eliminar sesiones y cookies</li>\n";
echo "                <li>Intenta acceder al <strong>Dashboard</strong> directamente (debería redirigir al login)</li>\n";
echo "                <li>Haz login normalmente</li>\n";
echo "                <li>Verifica que el dashboard funcione correctamente</li>\n";
echo "            </ol>\n";
echo "        </div>\n";

echo "    </div>\n";
echo "</body>\n";
echo "</html>\n";
?>