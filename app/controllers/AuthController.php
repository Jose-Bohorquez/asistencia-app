<?php
class AuthController {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function login() {
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                $error = 'Por favor ingrese usuario y contraseña';
            } else {
            
            $conn = $this->db->connect();
            $stmt = $conn->prepare("SELECT id, username, password, nombre, email, rol, activo FROM usuarios WHERE username = ? AND activo = 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    // Iniciar sesión
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nombre'] = $user['nombre'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_rol'] = $user['rol'];
                    $_SESSION['rol'] = $user['rol']; // Mantener compatibilidad
                    
                    header('Location: index.php?page=dashboard');
                    exit;
                } else {
                    $error = 'Contraseña incorrecta.';
                }
            } else {
                $error = 'Usuario no encontrado o inactivo.';
            }
            
            $stmt->close();
            }
        }
        
        // Mostrar vista de login
        include '../app/views/auth/login.php';
    }
    
    public function logout() {
        // Destruir sesión
        session_unset();
        session_destroy();
        
        header('Location: index.php?page=login');
        exit;
    }
}