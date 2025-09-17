<?php
// Script de depuración para identificar el bucle de redirección
require_once 'config/database.php';
require_once 'app/core/Router.php';
require_once 'app/controllers/BaseController.php';
require_once 'app/controllers/AdminController.php';

echo "=== DEBUG REDIRECT LOOP ===\n";

// 1. Simular datos de sesión
echo "1. Simulando datos de sesión...\n";
$_SESSION = [];
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'superadmin';
$_SESSION['user_rol'] = 'super_admin';
$_SESSION['session_token'] = 'debug_token_' . time();
$_SESSION['last_activity'] = time();
$_SESSION['permissions'] = ['dashboard_access'];
echo "Usuario simulado: " . $_SESSION['username'] . "\n";
echo "Rol: " . $_SESSION['user_rol'] . "\n";
echo "Permisos: " . implode(', ', $_SESSION['permissions']) . "\n";

// 2. Verificar conexión a base de datos
echo "\n2. Verificando conexión a base de datos...\n";
$database = new Database();
$db = $database->connect();
if ($db->connect_error) {
    echo "Error de conexión: " . $db->connect_error . "\n";
} else {
    echo "Conexión exitosa\n";
    
    // Verificar usuario en base de datos
     $stmt = $db->prepare("SELECT id, username, rol FROM usuarios WHERE id = ?");
     $stmt->bind_param("i", $_SESSION['user_id']);
     $stmt->execute();
     $result = $stmt->get_result();
     
     if ($row = $result->fetch_assoc()) {
         echo "Usuario encontrado en BD: " . $row['username'] . " (" . $row['rol'] . ")\n";
     } else {
         echo "Usuario NO encontrado en BD\n";
     }
     $stmt->close();
}

// 3. Verificar archivos cargados
echo "\n3. Verificando archivos cargados...\n";
echo "Router.php cargado\n";
echo "BaseController.php cargado\n";
echo "AdminController.php cargado\n";

// 4. Probar Router
echo "\n4. Probando Router...\n";
$_GET['page'] = 'dashboard';
echo "Página solicitada: " . $_GET['page'] . "\n";

$router = new Router();
echo "Router creado\n";

// Verificar si el usuario está autenticado según el router
echo "¿Usuario autenticado según router? " . (isset($_SESSION['user_id']) ? 'SÍ' : 'NO') . "\n";

// 5. Probar AdminController directamente
echo "\n5. Probando AdminController directamente...\n";
try {
    $adminController = new AdminController();
    echo "AdminController creado\n";
    
    // Verificar permisos
    $hasPermission = $adminController->hasPermission('dashboard_access');
    echo "¿Tiene permiso dashboard_access? " . ($hasPermission ? 'SÍ' : 'NO') . "\n";
    
} catch (Exception $e) {
    echo "Error al crear AdminController: " . $e->getMessage() . "\n";
}

// 6. Simular el flujo completo
echo "\n6. Simulando flujo completo...\n";
try {
    // Simular la lógica del dashboard
    if (!isset($_SESSION['user_id'])) {
        echo "REDIRECT: Usuario no autenticado -> login\n";
    } else {
        echo "Usuario autenticado, verificando permisos...\n";
        
        if ($_SESSION['user_rol'] === 'super_admin') {
            echo "Super admin detectado, acceso permitido\n";
        } else {
            $permissions = $_SESSION['permissions'] ?? [];
            if (in_array('dashboard_access', $permissions)) {
                echo "Permiso dashboard_access encontrado, acceso permitido\n";
            } else {
                echo "REDIRECT: Sin permiso dashboard_access -> login\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Error en simulación: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
?>