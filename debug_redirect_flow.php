<?php
// Debug script para rastrear el flujo de redirección
session_start();

echo "<h2>Debug del flujo de redirección</h2>";
echo "<h3>Estado de la sesión:</h3>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

echo "<h3>Parámetros GET:</h3>";
echo "<pre>";
var_dump($_GET);
echo "</pre>";

echo "<h3>Variables del servidor:</h3>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'No definido') . "<br>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'No definido') . "<br>";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'No definido') . "<br>";

// Simular verificación de autenticación
$isAuthenticated = isset($_SESSION['user_id']) && 
                   isset($_SESSION['user_rol']) && 
                   !empty($_SESSION['user_id']);

echo "<h3>Estado de autenticación:</h3>";
echo "isAuthenticated: " . ($isAuthenticated ? 'SÍ' : 'NO') . "<br>";

// Simular el flujo del Router
echo "<h3>Simulación del flujo del Router:</h3>";
$page = $_GET['page'] ?? 'dashboard';
echo "Página solicitada: $page<br>";

if ($page === 'login') {
    echo "Ruta: login (auth => false)<br>";
    if ($isAuthenticated) {
        echo "Usuario autenticado -> REDIRECCIÓN A DASHBOARD<br>";
    } else {
        echo "Usuario no autenticado -> MOSTRAR LOGIN<br>";
    }
} elseif ($page === 'dashboard') {
    echo "Ruta: dashboard (auth => true)<br>";
    if (!$isAuthenticated) {
        echo "Usuario no autenticado -> REDIRECCIÓN A LOGIN<br>";
    } else {
        echo "Usuario autenticado -> MOSTRAR DASHBOARD<br>";
    }
}

echo "<h3>Enlaces de prueba:</h3>";
echo "<a href='?page=login'>Ir a Login</a><br>";
echo "<a href='?page=dashboard'>Ir a Dashboard</a><br>";
echo "<a href='debug_session_state.php'>Ver estado de sesión</a><br>";
echo "<a href='index.php'>Ir a index.php</a><br>";

// Botón para limpiar sesión
if (isset($_GET['clear_session'])) {
    session_destroy();
    session_start();
    echo "<p style='color: green;'>Sesión limpiada</p>";
}
echo "<br><a href='?clear_session=1' style='color: red;'>Limpiar sesión</a>";
?>