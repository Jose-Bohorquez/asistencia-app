<?php
// Script para depurar el estado de la sesión
session_start();

echo "<h2>Debug: Estado de la Sesión</h2>";
echo "<h3>Variables de Sesión:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Verificaciones de Autenticación:</h3>";
echo "user_id existe: " . (isset($_SESSION['user_id']) ? 'SÍ' : 'NO') . "<br>";
echo "user_id no está vacío: " . (!empty($_SESSION['user_id']) ? 'SÍ' : 'NO') . "<br>";
echo "session_token existe: " . (isset($_SESSION['session_token']) ? 'SÍ' : 'NO') . "<br>";
echo "last_activity existe: " . (isset($_SESSION['last_activity']) ? 'SÍ' : 'NO') . "<br>";

if (isset($_SESSION['last_activity'])) {
    $timeout = 3600; // 1 hora
    $timeLeft = $timeout - (time() - $_SESSION['last_activity']);
    echo "Tiempo restante de sesión: " . $timeLeft . " segundos<br>";
    echo "Sesión expirada: " . ($timeLeft <= 0 ? 'SÍ' : 'NO') . "<br>";
}

echo "<h3>Método isAuthenticated() simulado:</h3>";
$isAuth = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
echo "Resultado: " . ($isAuth ? 'AUTENTICADO' : 'NO AUTENTICADO') . "<br>";

echo "<h3>Información del Servidor:</h3>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'N/A') . "<br>";
echo "page parameter: " . ($_GET['page'] ?? 'N/A') . "<br>";

echo "<h3>Cookies:</h3>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";
?>