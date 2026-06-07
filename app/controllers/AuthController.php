<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Usuario.php';

/**
 * Controlador de Autenticación
 * Maneja el login, logout y autenticación de usuarios
 */
class AuthController extends BaseController {
    private $usuarioModel;
    
    public function __construct() {
        parent::__construct();
        $this->usuarioModel = new Usuario();
    }
    
    /**
     * Método principal para manejar las peticiones
     */
    public function handleRequest() {
        $action = $_GET['action'] ?? 'login';
        
        switch ($action) {
            case 'login':
                $this->login();
                break;
            case 'logout':
                $this->logout();
                break;
            case 'forgot-password':
                $this->forgotPassword();
                break;
            case 'reset-password':
                $this->resetPassword();
                break;
            default:
                $this->login();
        }
    }
    
    /**
     * Mostrar formulario de login y procesar autenticación
     */
    public function login() {
        // Si ya está autenticado, redirigir al dashboard
        if ($this->currentUser) {
            $this->redirect('index.php?page=dashboard');
            return;
        }
        
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verificar token CSRF
            if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $error = 'Token de seguridad inválido.';
            } else {
                $username = $this->sanitizeInput($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $remember = isset($_POST['remember']);
                
                // Validar campos requeridos
                if (empty($username) || empty($password)) {
                    $error = 'Por favor ingrese usuario y contraseña';
                } else {
                    // Intentar autenticación
                    $result = $this->usuarioModel->authenticate($username, $password);

                    if (!$result || isset($result['errors'])) {
                        $error = isset($result['errors'])
                            ? implode(', ', $result['errors'])
                            : 'Usuario o contraseña incorrectos.';

                        // Log intento de login fallido
                        $this->logActivity('login_failed', null, null, [
                            'username' => $username,
                            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                        ]);
                    } else {
                        // Login exitoso
                        $user = $result;
                        
                        // Establecer sesión
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_nombre'] = $user['nombre'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_rol'] = $user['rol'];
                        $_SESSION['rol'] = $user['rol']; // Mantener compatibilidad
                        $_SESSION['nombre'] = $user['nombre']; // Mantener compatibilidad
                        $_SESSION['email'] = $user['email']; // Mantener compatibilidad
                        $_SESSION['foto_perfil'] = $user['foto_perfil'] ?? '';
                        $_SESSION['last_activity'] = time();
                        $_SESSION['session_token'] = bin2hex(random_bytes(32));
                        
                        // Configurar cookie "recordarme" si está marcada
                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            $expires = time() + (30 * 24 * 60 * 60); // 30 días
                            
                            // Guardar token en base de datos
                            $this->usuarioModel->saveRememberToken($user['id'], $token, date('Y-m-d H:i:s', $expires));
                            
                            // Establecer cookie
                            setcookie('remember_token', $token, $expires, '/', '', true, true);
                        }
                        
                        // Regenerar ID de sesión por seguridad
                        session_regenerate_id(true);
                        
                        // Log login exitoso
                        $this->logActivity('login_success', $user['id'], null, [
                            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                        ]);
                        
                        // Redirigir según el rol
                        $redirectUrl = $this->getRedirectUrlByRole($user['rol']);
                        $this->redirect($redirectUrl);
                        return;
                    }
                }
            }
        }
        
        // Mostrar vista de login
        $this->render('auth/login', [
            'error' => $error,
            'page_title' => 'Iniciar Sesión'
        ]);
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        if ($this->currentUser) {
            // Log logout
            $this->logActivity('logout', $this->currentUser['id']);
            
            // Limpiar token "recordarme" si existe
            if (isset($_COOKIE['remember_token'])) {
                $this->usuarioModel->clearRememberToken($_COOKIE['remember_token']);
                setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            }
        }
        
        // Destruir sesión
        session_unset();
        session_destroy();
        
        $this->setFlashMessage('Sesión cerrada correctamente', 'success');
        $this->redirect('index.php?page=login');
    }
    
    /**
     * Solicitar recuperación de contraseña
     */
    public function forgotPassword() {
        $message = '';
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $error = 'Token de seguridad inválido.';
            } else {
                $email = $this->sanitizeInput($_POST['email'] ?? '');
                
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Por favor ingrese un email válido';
                } else {
                    $result = $this->usuarioModel->requestPasswordReset($email);
                    
                    if (isset($result['errors'])) {
                        $error = implode(', ', $result['errors']);
                    } else {
                        $message = 'Si el email existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña.';
                        
                        // Log solicitud de reset
                        $this->logActivity('password_reset_requested', null, null, [
                            'email' => $email,
                            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                        ]);
                    }
                }
            }
        }
        
        $this->render('auth/forgot-password', [
            'success'    => $message,
            'error'      => $error,
            'page_title' => 'Recuperar Contraseña',
        ]);
    }
    
    /**
     * Restablecer contraseña con token
     */
    public function resetPassword() {
        $token      = $_GET['token'] ?? ($_POST['token'] ?? '');
        $message    = '';
        $error      = '';
        $user       = null;

        if (empty($token)) {
            $error = 'Token de restablecimiento inválido.';
        } else {
            $user = $this->usuarioModel->verifyResetToken($token);

            if (!$user) {
                $error = 'El enlace ha expirado o ya fue utilizado. Solicita uno nuevo.';
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                    $error = 'Token de seguridad inválido.';
                } else {
                    $password        = $_POST['password']         ?? '';
                    $confirmPassword = $_POST['password_confirm'] ?? '';

                    if (empty($password) || empty($confirmPassword)) {
                        $error = 'Por favor complete todos los campos.';
                    } elseif ($password !== $confirmPassword) {
                        $error = 'Las contraseñas no coinciden.';
                    } elseif (strlen($password) < 8) {
                        $error = 'La contraseña debe tener al menos 8 caracteres.';
                    } else {
                        $result = $this->usuarioModel->resetPassword($token, $password);

                        if (isset($result['errors'])) {
                            $error = implode(', ', $result['errors']);
                        } else {
                            $this->logActivity('password_reset_completed', $user['id']);
                            $this->setFlashMessage('Contraseña actualizada correctamente. Ya puedes iniciar sesión.', 'success');
                            $this->redirect('index.php?page=login');
                            return;
                        }
                    }
                }
            }
        }

        $this->render('auth/reset-password', [
            'tokenValido' => $user !== null,
            'tokenStr'    => $token,
            'error'       => $error,
            'page_title'  => 'Restablecer Contraseña',
        ]);
    }
    
    /**
     * Obtener URL de redirección según el rol
     */
    private function getRedirectUrlByRole($rol) {
        switch ($rol) {
            case 'super_admin':
            case 'admin':
                return 'index.php?page=dashboard';
            case 'profesor':
                return 'index.php?page=dashboard';
            default:
                return 'index.php?page=dashboard';
        }
    }
    
    /**
     * Verificar autenticación automática por cookie
     */
    public static function checkRememberToken() {
        if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
            $usuarioModel = new Usuario();
            $user = $usuarioModel->getUserByRememberToken($_COOKIE['remember_token']);
            
            if ($user) {
                // Establecer sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_nombre'] = $user['nombre'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_rol'] = $user['rol'];
                $_SESSION['rol'] = $user['rol']; // Mantener compatibilidad
                $_SESSION['nombre'] = $user['nombre']; // Mantener compatibilidad
                $_SESSION['email'] = $user['email']; // Mantener compatibilidad
                $_SESSION['foto_perfil'] = $user['foto_perfil'] ?? '';
                $_SESSION['last_activity'] = time();
                $_SESSION['session_token'] = bin2hex(random_bytes(32));

                // Regenerar ID de sesión
                session_regenerate_id(true);
                
                return true;
            } else {
                // Token inválido, eliminar cookie
                setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            }
        }
        
        return false;
    }
}