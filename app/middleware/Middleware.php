<?php

/**
 * Clase base para middleware
 * Proporciona funcionalidad común para todos los middlewares
 */
abstract class Middleware {
    protected $db;
    
    public function __construct($db = null) {
        $this->db = $db;
    }
    
    /**
     * Método principal que debe implementar cada middleware
     */
    abstract public function handle($request = null, $next = null);
    
    /**
     * Redirigir a una página específica
     */
    protected function redirect($url, $message = null, $type = 'error') {
        if ($message) {
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = $type;
        }
        
        header("Location: $url");
        exit();
    }
    
    /**
     * Devolver respuesta JSON
     */
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
    
    /**
     * Verificar si la petición es AJAX
     */
    protected function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Obtener la IP del cliente
     */
    protected function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Registrar actividad de seguridad
     */
    protected function logSecurityEvent($event, $details = []) {
        if (!$this->db) {
            return;
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        $ip = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $url = $_SERVER['REQUEST_URI'] ?? '';
        
        $logData = [
            'usuario_id' => $userId,
            'accion' => $event,
            'tabla_afectada' => 'security',
            'detalles' => json_encode(array_merge($details, [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'url' => $url
            ])),
            'fecha_hora' => date('Y-m-d H:i:s')
        ];
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO logs_sistema (usuario_id, accion, tabla_afectada, detalles, fecha_hora) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "issss", 
                $logData['usuario_id'],
                $logData['accion'],
                $logData['tabla_afectada'],
                $logData['detalles'],
                $logData['fecha_hora']
            );
            
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            // Log error but don't break the application
            error_log("Error logging security event: " . $e->getMessage());
        }
    }
    
    /**
     * Validar token CSRF — solo acepta el token desde POST o cabecera HTTP
     */
    protected function validateCSRF($token = null) {
        if (!$token) {
            // Solo leer de POST o cabecera X-CSRF-Token; NUNCA desde GET
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        }

        if (!$token || !isset($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generar token CSRF
     */
    protected function generateCSRF() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verificar límite de intentos
     */
    protected function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 300) {
        $cacheKey = "rate_limit_" . md5($key);
        
        if (!isset($_SESSION[$cacheKey])) {
            $_SESSION[$cacheKey] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }
        
        $data = $_SESSION[$cacheKey];
        
        // Resetear si ha pasado el tiempo
        if (time() - $data['first_attempt'] > $timeWindow) {
            $_SESSION[$cacheKey] = [
                'attempts' => 1,
                'first_attempt' => time()
            ];
            return true;
        }
        
        // Verificar límite
        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }
        
        // Incrementar intentos
        $_SESSION[$cacheKey]['attempts']++;
        return true;
    }
    
    /**
     * Limpiar datos de entrada
     */
    protected function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validar formato de email
     */
    protected function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Verificar si el usuario está autenticado
     */
    protected function isAuthenticated() {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['user_rol']) && 
               !empty($_SESSION['user_id']);
    }
    
    /**
     * Obtener información del usuario actual
     */
    protected function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'nombre' => $_SESSION['user_nombre'] ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'rol' => $_SESSION['user_rol'] ?? '',
            'permisos' => $_SESSION['user_permisos'] ?? []
        ];
    }
    
    /**
     * Verificar si el usuario tiene un rol específico
     */
    protected function hasRole($role) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $userRole = $_SESSION['user_rol'];
        
        // Super admin tiene acceso a todo
        if ($userRole === 'super_admin') {
            return true;
        }
        
        return $userRole === $role;
    }
    
    /**
     * Verificar si el usuario tiene alguno de los roles especificados
     */
    protected function hasAnyRole($roles) {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verificar si el usuario tiene un permiso específico
     */
    protected function hasPermission($permission) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        // Super admin tiene todos los permisos
        if ($_SESSION['user_rol'] === 'super_admin') {
            return true;
        }
        
        $userPermissions = $_SESSION['user_permisos'] ?? [];
        return in_array($permission, $userPermissions);
    }
    
    /**
     * Verificar múltiples permisos (AND)
     */
    protected function hasAllPermissions($permissions) {
        if (!is_array($permissions)) {
            $permissions = [$permissions];
        }
        
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Verificar si tiene alguno de los permisos (OR)
     */
    protected function hasAnyPermission($permissions) {
        if (!is_array($permissions)) {
            $permissions = [$permissions];
        }
        
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verificar si el usuario puede acceder a un recurso específico
     */
    protected function canAccess($resource, $action = 'read') {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        // Super admin puede hacer todo
        if ($_SESSION['user_rol'] === 'super_admin') {
            return true;
        }
        
        // Verificar permisos específicos
        $permission = $resource . '_' . $action;
        return $this->hasPermission($permission);
    }
    
    /**
     * Obtener mensaje de error personalizado
     */
    protected function getErrorMessage($type = 'access_denied') {
        $messages = [
            'access_denied' => 'No tienes permisos para acceder a este recurso.',
            'not_authenticated' => 'Debes iniciar sesión para continuar.',
            'invalid_token' => 'Token de seguridad inválido.',
            'rate_limit' => 'Demasiados intentos. Intenta nuevamente más tarde.',
            'invalid_request' => 'Solicitud inválida.',
            'session_expired' => 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.'
        ];
        
        return $messages[$type] ?? 'Error de acceso.';
    }
}