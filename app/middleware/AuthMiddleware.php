<?php
require_once __DIR__ . '/Middleware.php';

/**
 * Middleware de Autenticación
 * Verifica que el usuario esté autenticado antes de acceder a recursos protegidos
 */
class AuthMiddleware extends Middleware {
    
    /**
     * Manejar la verificación de autenticación
     */
    public function handle($request = null, $next = null) {
        // Verificar si el usuario está autenticado
        if (!$this->isAuthenticated()) {
            $this->logSecurityEvent('unauthorized_access_attempt', [
                'reason' => 'not_authenticated',
                'requested_url' => $_SERVER['REQUEST_URI'] ?? ''
            ]);
            
            // Si es una petición AJAX, devolver JSON
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $this->getErrorMessage('not_authenticated'),
                    'redirect' => '/public/index.php?page=login'
                ], 401);
            }
            
            // Guardar la URL solicitada para redirigir después del login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
            
            // Redirigir al login
            $this->redirect('/public/index.php?page=login', $this->getErrorMessage('not_authenticated'));
        }
        
        // Verificar si la sesión es válida
        if (!$this->isValidSession()) {
            $this->logSecurityEvent('invalid_session_detected', [
                'user_id' => $_SESSION['user_id'] ?? null,
                'reason' => 'session_validation_failed'
            ]);
            
            $this->destroySession();
            
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $this->getErrorMessage('session_expired'),
                    'redirect' => '/public/index.php?page=login'
                ], 401);
            }
            
            $this->redirect('/public/index.php?page=login', $this->getErrorMessage('session_expired'));
        }
        
        // Actualizar última actividad
        $this->updateLastActivity();
        
        // Verificar timeout de sesión
        if ($this->isSessionExpired()) {
            $this->logSecurityEvent('session_timeout', [
                'user_id' => $_SESSION['user_id'],
                'last_activity' => $_SESSION['last_activity'] ?? 'unknown'
            ]);
            
            $this->destroySession();
            
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $this->getErrorMessage('session_expired'),
                    'redirect' => '/public/index.php?page=login'
                ], 401);
            }
            
            $this->redirect('/public/index.php?page=login', 'Tu sesión ha expirado por inactividad.');
        }
        
        // Regenerar ID de sesión periódicamente para seguridad
        $this->regenerateSessionId();
        
        // Continuar con la siguiente función si existe
        if ($next && is_callable($next)) {
            return $next($request);
        }
        
        return true;
    }
    
    /**
     * Verificar si la sesión es válida
     */
    private function isValidSession() {
        // Verificar que existan las variables de sesión requeridas
        $requiredFields = ['user_id', 'user_rol', 'session_token'];
        
        foreach ($requiredFields as $field) {
            if (!isset($_SESSION[$field]) || empty($_SESSION[$field])) {
                return false;
            }
        }
        
        // Verificar que el usuario existe en la base de datos
        if ($this->db) {
            return $this->verifyUserInDatabase();
        }
        
        return true;
    }
    
    /**
     * Verificar que el usuario existe y está activo en la base de datos
     */
    private function verifyUserInDatabase() {
        try {
            $stmt = $this->db->prepare("
                SELECT id, activo, ultimo_acceso 
                FROM usuarios 
                WHERE id = ? AND activo = 1
            ");
            
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $stmt->close();
                return false;
            }
            
            $user = $result->fetch_assoc();
            $stmt->close();
            
            // Actualizar último acceso
            $this->updateLastAccess($_SESSION['user_id']);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error verifying user in database: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar último acceso del usuario
     */
    private function updateLastAccess($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE usuarios 
                SET ultimo_acceso = NOW() 
                WHERE id = ?
            ");
            
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
            
        } catch (Exception $e) {
            error_log("Error updating last access: " . $e->getMessage());
        }
    }
    
    /**
     * Verificar si la sesión ha expirado por inactividad
     */
    private function isSessionExpired() {
        $timeout = 3600; // 1 hora por defecto
        
        // Configurar timeout según el rol
        switch ($_SESSION['user_rol']) {
            case 'super_admin':
                $timeout = 7200; // 2 horas
                break;
            case 'admin':
                $timeout = 5400; // 1.5 horas
                break;
            case 'profesor':
                $timeout = 3600; // 1 hora
                break;
        }
        
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return false;
        }
        
        return (time() - $_SESSION['last_activity']) > $timeout;
    }
    
    /**
     * Actualizar última actividad
     */
    private function updateLastActivity() {
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Regenerar ID de sesión periódicamente
     */
    private function regenerateSessionId() {
        // Regenerar cada 30 minutos
        $regenerateInterval = 1800;
        
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
            return;
        }
        
        if ((time() - $_SESSION['last_regeneration']) > $regenerateInterval) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
            
            $this->logSecurityEvent('session_regenerated', [
                'user_id' => $_SESSION['user_id'],
                'old_session_id' => session_id()
            ]);
        }
    }
    
    /**
     * Destruir sesión completamente
     */
    private function destroySession() {
        // Limpiar todas las variables de sesión
        $_SESSION = [];
        
        // Destruir la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
        
        // Iniciar nueva sesión
        session_start();
    }
    
    /**
     * Verificar autenticación para rutas específicas
     */
    public static function requireAuth($allowedRoles = []) {
        $middleware = new self();
        
        // Verificar autenticación básica
        $middleware->handle();
        
        // Si se especifican roles, verificar que el usuario tenga uno de ellos
        if (!empty($allowedRoles) && !$middleware->hasAnyRole($allowedRoles)) {
            $middleware->logSecurityEvent('role_access_denied', [
                'user_id' => $_SESSION['user_id'],
                'user_role' => $_SESSION['user_rol'],
                'required_roles' => $allowedRoles,
                'requested_url' => $_SERVER['REQUEST_URI'] ?? ''
            ]);
            
            if ($middleware->isAjaxRequest()) {
                $middleware->jsonResponse([
                    'success' => false,
                    'message' => $middleware->getErrorMessage('access_denied')
                ], 403);
            }
            
            $middleware->redirect('/public/index.php?page=dashboard', $middleware->getErrorMessage('access_denied'));
        }
        
        return true;
    }
    
    /**
     * Verificar autenticación para AJAX
     */
    public static function requireAuthAjax($allowedRoles = []) {
        $middleware = new self();
        
        if (!$middleware->isAuthenticated()) {
            $middleware->jsonResponse([
                'success' => false,
                'message' => $middleware->getErrorMessage('not_authenticated'),
                'redirect' => '/public/index.php?page=login'
            ], 401);
        }
        
        if (!empty($allowedRoles) && !$middleware->hasAnyRole($allowedRoles)) {
            $middleware->jsonResponse([
                'success' => false,
                'message' => $middleware->getErrorMessage('access_denied')
            ], 403);
        }
        
        return true;
    }
    
    /**
     * Middleware para páginas que requieren estar deslogueado (como login)
     */
    public static function requireGuest() {
        $middleware = new self();
        
        if ($middleware->isAuthenticated()) {
            // Si ya está autenticado, redirigir al dashboard
            $middleware->redirect('/public/index.php?page=dashboard');
        }
        
        return true;
    }
    
    /**
     * Obtener información de la sesión actual
     */
    public function getSessionInfo() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return [
            'user_id' => $_SESSION['user_id'],
            'user_nombre' => $_SESSION['user_nombre'] ?? '',
            'user_email' => $_SESSION['user_email'] ?? '',
            'user_rol' => $_SESSION['user_rol'],
            'last_activity' => $_SESSION['last_activity'] ?? null,
            'session_start' => $_SESSION['session_start'] ?? null,
            'ip_address' => $this->getClientIP()
        ];
    }
    
    /**
     * Verificar si el usuario puede impersonar a otro
     */
    public function canImpersonate() {
        return $this->hasRole('super_admin');
    }
    
    /**
     * Iniciar impersonación de usuario
     */
    public function startImpersonation($targetUserId) {
        if (!$this->canImpersonate()) {
            return false;
        }
        
        // Guardar datos del usuario original
        $_SESSION['impersonating'] = true;
        $_SESSION['original_user_id'] = $_SESSION['user_id'];
        $_SESSION['original_user_data'] = [
            'nombre' => $_SESSION['user_nombre'],
            'email' => $_SESSION['user_email'],
            'rol' => $_SESSION['user_rol']
        ];
        
        // Cargar datos del usuario objetivo
        if ($this->db) {
            $stmt = $this->db->prepare("
                SELECT u.*, r.nombre as rol_nombre, r.permisos 
                FROM usuarios u 
                INNER JOIN roles r ON u.rol_id = r.id 
                WHERE u.id = ? AND u.activo = 1
            ");
            
            $stmt->bind_param("i", $targetUserId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nombre'] = $user['nombre'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_rol'] = $user['rol_nombre'];
                $_SESSION['user_permisos'] = json_decode($user['permisos'], true) ?? [];
                
                $this->logSecurityEvent('impersonation_started', [
                    'original_user_id' => $_SESSION['original_user_id'],
                    'target_user_id' => $targetUserId
                ]);
                
                $stmt->close();
                return true;
            }
            
            $stmt->close();
        }
        
        return false;
    }
    
    /**
     * Terminar impersonación
     */
    public function stopImpersonation() {
        if (!isset($_SESSION['impersonating']) || !$_SESSION['impersonating']) {
            return false;
        }
        
        $targetUserId = $_SESSION['user_id'];
        
        // Restaurar datos del usuario original
        $_SESSION['user_id'] = $_SESSION['original_user_id'];
        $_SESSION['user_nombre'] = $_SESSION['original_user_data']['nombre'];
        $_SESSION['user_email'] = $_SESSION['original_user_data']['email'];
        $_SESSION['user_rol'] = $_SESSION['original_user_data']['rol'];
        
        // Limpiar datos de impersonación
        unset($_SESSION['impersonating']);
        unset($_SESSION['original_user_id']);
        unset($_SESSION['original_user_data']);
        
        $this->logSecurityEvent('impersonation_stopped', [
            'original_user_id' => $_SESSION['user_id'],
            'target_user_id' => $targetUserId
        ]);
        
        return true;
    }
}