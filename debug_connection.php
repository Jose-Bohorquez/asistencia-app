<?php
// Script de depuración para verificar la conexión a la base de datos

echo "=== DEBUG DE CONEXIÓN A BASE DE DATOS ===\n";
echo "<br>";

// 1. Verificar configuración de base de datos
require_once __DIR__ . '/config/database.php';

echo "1. Creando instancia de Database...\n";
echo "<br>";

try {
    $db = new Database();
    echo "✓ Instancia de Database creada correctamente\n";
    echo "<br>";
    
    echo "2. Intentando conectar...\n";
    echo "<br>";
    
    $conn = $db->connect();
    
    if ($conn) {
        echo "✓ Conexión establecida correctamente\n";
        echo "<br>";
        
        // Verificar si la tabla usuarios existe
        $result = $conn->query("SHOW TABLES LIKE 'usuarios'");
        if ($result->num_rows > 0) {
            echo "✓ Tabla 'usuarios' encontrada\n";
            echo "<br>";
            
            // Contar usuarios
            $result = $conn->query("SELECT COUNT(*) as total FROM usuarios");
            $row = $result->fetch_assoc();
            echo "✓ Total de usuarios en la base de datos: " . $row['total'] . "\n";
            echo "<br>";
            
            // Verificar usuario superadmin
            $stmt = $conn->prepare("SELECT id, username, activo FROM usuarios WHERE username = ?");
            $username = 'superadmin';
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                echo "✓ Usuario 'superadmin' encontrado:\n";
                echo "<br>";
                echo "  - ID: " . $user['id'] . "\n";
                echo "<br>";
                echo "  - Username: " . $user['username'] . "\n";
                echo "<br>";
                echo "  - Activo: " . ($user['activo'] ? 'Sí' : 'No') . "\n";
                echo "<br>";
            } else {
                echo "✗ Usuario 'superadmin' NO encontrado\n";
                echo "<br>";
            }
            $stmt->close();
            
        } else {
            echo "✗ Tabla 'usuarios' NO encontrada\n";
            echo "<br>";
        }
        
    } else {
        echo "✗ Error: No se pudo establecer la conexión\n";
        echo "<br>";
    }
    
} catch (Exception $e) {
    echo "✗ Error de conexión: " . $e->getMessage() . "\n";
    echo "<br>";
}

echo "\n3. Probando modelo Usuario...\n";
echo "<br>";

try {
    require_once __DIR__ . '/app/models/Usuario.php';
    
    echo "✓ Modelo Usuario cargado\n";
    echo "<br>";
    
    $usuarioModel = new Usuario();
    echo "✓ Instancia de Usuario creada\n";
    echo "<br>";
    
    // Probar autenticación
    echo "4. Probando autenticación...\n";
    echo "<br>";
    
    $result = $usuarioModel->authenticate('superadmin', 'admin123');
    
    if ($result) {
        echo "✓ Autenticación exitosa:\n";
        echo "<br>";
        echo "  - ID: " . $result['id'] . "\n";
        echo "<br>";
        echo "  - Username: " . $result['username'] . "\n";
        echo "<br>";
        echo "  - Rol: " . $result['rol'] . "\n";
        echo "<br>";
    } else {
        echo "✗ Autenticación falló\n";
        echo "<br>";
    }
    
} catch (Exception $e) {
    echo "✗ Error en modelo Usuario: " . $e->getMessage() . "\n";
    echo "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    echo "<br>";
}

echo "\n=== FIN DEL DEBUG ===\n";
?>