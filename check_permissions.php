<?php
require_once 'config/config.php';
require_once 'app/models/Usuario.php';

echo "=== VERIFICACIÓN DE PERMISOS ===".PHP_EOL;

// Autenticar usuario
$usuario = new Usuario();
$user = $usuario->authenticate('superadmin', 'admin123');

if ($user) {
    echo "✓ Usuario autenticado: {$user['username']}" . PHP_EOL;
    echo "✓ Rol: {$user['rol']}" . PHP_EOL;
    
    // Verificar estructura de permisos
    echo "\n--- Estructura del usuario ---" . PHP_EOL;
    foreach ($user as $key => $value) {
        if (is_array($value)) {
            echo "$key: " . json_encode($value) . PHP_EOL;
        } else {
            echo "$key: $value" . PHP_EOL;
        }
    }
    
    // Simular verificación de permisos como en BaseController
    echo "\n--- Verificación de permisos ---" . PHP_EOL;
    
    // Super admin check
    if ($user['rol'] === 'super_admin') {
        echo "✓ Es super_admin - debería tener todos los permisos" . PHP_EOL;
    }
    
    // Verificar si tiene campo permisos
    $userPermissions = $user['permisos'] ?? [];
    echo "Permisos del usuario: " . (is_array($userPermissions) ? json_encode($userPermissions) : $userPermissions) . PHP_EOL;
    
    // Verificar permiso específico dashboard_access
    $hasDashboardAccess = false;
    if ($user['rol'] === 'super_admin') {
        $hasDashboardAccess = true;
        echo "✓ Tiene dashboard_access (por ser super_admin)" . PHP_EOL;
    } else {
        $hasDashboardAccess = in_array('dashboard_access', $userPermissions);
        echo ($hasDashboardAccess ? "✓" : "✗") . " Tiene dashboard_access en permisos" . PHP_EOL;
    }
    
} else {
    echo "✗ Error de autenticación" . PHP_EOL;
}

echo "\n=== FIN DE VERIFICACIÓN ===" . PHP_EOL;
?>