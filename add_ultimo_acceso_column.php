<?php
// Script para agregar la columna ultimo_acceso a la tabla usuarios

require_once __DIR__ . '/config/database.php';

echo "=== AGREGANDO COLUMNA ULTIMO_ACCESO ===\n";
echo "<br>";

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Verificar si la columna ya existe
    $result = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'ultimo_acceso'");
    
    if ($result->num_rows > 0) {
        echo "La columna 'ultimo_acceso' ya existe\n";
        echo "<br>";
    } else {
        echo "Agregando columna 'ultimo_acceso'...\n";
        echo "<br>";
        
        $sql = "ALTER TABLE usuarios ADD COLUMN ultimo_acceso TIMESTAMP NULL DEFAULT NULL";
        $result = $conn->query($sql);
        
        if ($result) {
            echo "✓ Columna 'ultimo_acceso' agregada exitosamente\n";
            echo "<br>";
        } else {
            echo "✗ Error al agregar la columna: " . $conn->error . "\n";
            echo "<br>";
        }
    }
    
    // Verificar la estructura actualizada
    echo "\nVerificando estructura actualizada...\n";
    echo "<br>";
    
    $result = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'ultimo_acceso'");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "✓ Columna 'ultimo_acceso' confirmada: " . $row['Type'] . "\n";
        echo "<br>";
    } else {
        echo "✗ Columna 'ultimo_acceso' aún no existe\n";
        echo "<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "<br>";
}

echo "\n=== FIN ===\n";
?>