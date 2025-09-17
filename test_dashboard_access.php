<?php
require_once 'config/config.php';
require_once 'app/models/Usuario.php';
require_once 'app/middleware/MiddlewareManager.php';

echo "=== PRUEBA DE ACCESO AL DASHBOARD ===" . PHP_EOL;

// Simular login
$usuario = new Usuario();
$user = $usuario->authenticate('superadmin', 'admin123');

if ($user) {
    echo "✓ Usuario autenticado: {$user['username']}" . PHP_EOL;
    
    // Simular sesión
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_nombre'] = $user['nombre'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_rol'] = $user['rol'];
    $_SESSION['rol'] = $user['rol'];
    $_SESSION['nombre'] = $user['nombre'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['last_activity'] = time();
    $_SESSION['session_token'] = bin2hex(random_bytes(32));
    
    echo "✓ Sesión establecida" . PHP_EOL;
    
    // Probar acceso al dashboard
    echo "\n--- Probando acceso al dashboard ---" . PHP_EOL;
    
    // Crear MiddlewareManager
    $middlewareManager = new MiddlewareManager();
    
    // Verificar permiso dashboard_access
    $hasDashboardAccess = $middlewareManager->checkPermission('dashboard_access');
    echo ($hasDashboardAccess ? "✓" : "✗") . " Permiso dashboard_access: " . ($hasDashboardAccess ? 'PERMITIDO' : 'DENEGADO') . PHP_EOL;
    
    if ($hasDashboardAccess) {
        echo "\n✓ El usuario puede acceder al dashboard sin bucle de redirecciones" . PHP_EOL;
        
        // Simular carga del AdminController
        try {
            require_once 'app/controllers/AdminController.php';
            echo "✓ AdminController cargado correctamente" . PHP_EOL;
            
            // No ejecutamos el dashboard completo para evitar output HTML
            echo "✓ El dashboard debería cargar correctamente ahora" . PHP_EOL;
            
        } catch (Exception $e) {
            echo "✗ Error al cargar AdminController: " . $e->getMessage() . PHP_EOL;
        }
    } else {
        echo "\n✗ El usuario NO puede acceder al dashboard - seguiría el bucle de redirecciones" . PHP_EOL;
    }
    
} else {
    echo "✗ Error de autenticación" . PHP_EOL;
}

echo "\n=== FIN DE LA PRUEBA ===" . PHP_EOL;
?>