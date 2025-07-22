<?php
/**
 * GENERADOR DE HASH PARA CONTRASEÑA DE SUPER ADMIN
 * 
 * Ejecuta este archivo para generar el hash de la contraseña
 * que debes usar en la base de datos de Hostinger
 */

// Contraseña que quieres usar para el super admin
$password = 'SuperAdmin2024!';

// Generar el hash
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Generador de Hash para Super Admin</h2>";
echo "<p><strong>Contraseña:</strong> " . htmlspecialchars($password) . "</p>";
echo "<p><strong>Hash generado:</strong></p>";
echo "<textarea style='width:100%; height:100px; font-family:monospace;'>" . $hash . "</textarea>";

echo "<h3>SQL para actualizar en Hostinger:</h3>";
echo "<textarea style='width:100%; height:150px; font-family:monospace;'>";
echo "UPDATE usuarios SET password = '" . $hash . "' WHERE username = 'superadmin';";
echo "</textarea>";

echo "<h3>Instrucciones:</h3>";
echo "<ol>";
echo "<li>Copia el hash generado arriba</li>";
echo "<li>Ve a phpMyAdmin en Hostinger</li>";
echo "<li>Ejecuta el SQL de actualización</li>";
echo "<li>Ya podrás iniciar sesión con usuario 'superadmin' y contraseña '" . htmlspecialchars($password) . "'</li>";
echo "</ol>";

echo "<p><em>Nota: Puedes cambiar la variable \$password en este archivo para generar hash de otras contraseñas.</em></p>";
?>