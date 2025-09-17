<?php
// Script para verificar y generar hash de contraseña

$password = 'admin123';
$storedHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "=== VERIFICACIÓN DE CONTRASEÑA ===\n";
echo "Contraseña: $password\n";
echo "Hash almacenado: $storedHash\n";
echo "\n";

// Verificar si el hash actual es válido
if (password_verify($password, $storedHash)) {
    echo "✅ El hash almacenado es VÁLIDO para la contraseña '$password'\n";
} else {
    echo "❌ El hash almacenado NO es válido para la contraseña '$password'\n";
    echo "\n";
    echo "Generando nuevo hash...\n";
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    echo "Nuevo hash: $newHash\n";
    echo "\n";
    echo "SQL para actualizar:\n";
    echo "UPDATE usuarios SET password = '$newHash' WHERE username = 'superadmin';\n";
}

echo "\n=== VERIFICACIÓN ADICIONAL ===\n";

// Probar con diferentes contraseñas comunes
$testPasswords = ['admin123', 'password', '123456', 'admin', 'superadmin'];

foreach ($testPasswords as $testPass) {
    if (password_verify($testPass, $storedHash)) {
        echo "✅ El hash corresponde a la contraseña: '$testPass'\n";
        break;
    }
}

echo "\nScript completado.\n";
?>