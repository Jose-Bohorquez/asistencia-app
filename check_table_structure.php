<?php
// Script para verificar la estructura de la tabla usuarios

require_once __DIR__ . '/config/database.php';

echo "=== ESTRUCTURA DE LA TABLA USUARIOS ===\n";
echo "<br>";

try {
    $db = new Database();
    $conn = $db->connect();
    
    $result = $conn->query('DESCRIBE usuarios');
    
    echo "Columnas encontradas:\n";
    echo "<br>";
    
    while($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        echo "<br>";
    }
    
    echo "\n=== VERIFICANDO COLUMNAS REQUERIDAS ===\n";
    echo "<br>";
    
    // Verificar si existe la columna ultimo_acceso
    $result = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'ultimo_acceso'");
    if ($result->num_rows > 0) {
        echo "✓ Columna 'ultimo_acceso' existe\n";
        echo "<br>";
    } else {
        echo "✗ Columna 'ultimo_acceso' NO existe\n";
        echo "<br>";
    }
    
    // Verificar otras columnas importantes
    $columnas_requeridas = ['id', 'username', 'password', 'nombre', 'email', 'rol', 'activo'];
    
    foreach ($columnas_requeridas as $columna) {
        $result = $conn->query("SHOW COLUMNS FROM usuarios LIKE '$columna'");
        if ($result->num_rows > 0) {
            echo "✓ Columna '$columna' existe\n";
            echo "<br>";
        } else {
            echo "✗ Columna '$columna' NO existe\n";
            echo "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "<br>";
}

echo "\n=== FIN DE LA VERIFICACIÓN ===\n";
?>