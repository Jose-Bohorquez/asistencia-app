<?php
require_once __DIR__ . '/../middleware/MiddlewareManager.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Controlador Base
 * Proporciona funcionalidad común para todos los controladores
 */
abstract class BaseController {
    protected $db;
    protected $middlewareManager;
    protected $currentUser;
    protected $viewData = [];
    
    public function __construct() {
        // Inicializar conexión a base de datos
        $database = new Database();
        $this->db = $database->connect();
        
        // Inicializar middleware manager
        $this->middlewareManager = new MiddlewareManager($this->db);
        
        // Obtener información del usuario actual
        $this->currentUser = $this->getCurrentUser();
        
        // Datos comunes para todas las vistas
        $this->viewData = [
            'current_user' => $this->currentUser,
            'csrf_token' => $this->generateCSRFToken(),
            'flash_message' => $this->getFlashMessage(),
            'permissions' => $this->getUserPermissions(),
            'nav_menu' => MiddlewareManager::generateNavMenu($this->db)
        ];
    }
    
    /**
     * Obtener información del usuario actual
     */
    protected function getCurrentUser() {
        if (!isset($_SESSION['user_id'])) {
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
     * Generar token CSRF
     */
    protected function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validar token CSRF
     */
    protected function validateCSRF($token = null) {
        if (!$token) {
            $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
        }
        
        if (!$token || !isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Alias para validateCSRF (compatibilidad)
     */
    protected function verifyCSRFToken($token = null) {
        return $this->validateCSRF($token);
    }
    
    /**
     * Obtener mensaje flash
     */
    protected function getFlashMessage() {
        $message = null;
        
        if (isset($_SESSION['flash_message'])) {
            $message = [
                'text' => $_SESSION['flash_message'],
                'type' => $_SESSION['flash_type'] ?? 'info'
            ];
            
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
        }
        
        return $message;
    }
    
    /**
     * Establecer mensaje flash
     */
    protected function setFlashMessage($message, $type = 'success') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    
    /**
     * Obtener permisos del usuario
     */
    protected function getUserPermissions() {
        return MiddlewareManager::getPermissionsInfo($this->db);
    }
    
    /**
     * Verificar si el usuario tiene permiso
     */
    protected function hasPermission($permission) {
        if (!$this->currentUser) {
            return false;
        }
        
        // Super admin tiene todos los permisos
        if ($this->currentUser['rol'] === 'super_admin') {
            return true;
        }
        
        $userPermissions = $this->currentUser['permisos'] ?? [];
        return in_array($permission, $userPermissions);
    }
    
    /**
     * Verificar si el usuario tiene alguno de los roles
     */
    protected function hasRole($roles) {
        if (!$this->currentUser) {
            return false;
        }
        
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        return in_array($this->currentUser['rol'], $roles);
    }
    
    /**
     * Redirigir con mensaje
     */
    protected function redirect($url, $message = null, $type = 'success') {
        if ($message) {
            $this->setFlashMessage($message, $type);
        }
        
        header("Location: $url");
        exit();
    }
    
    /**
     * Respuesta JSON
     */
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
    
    /**
     * Verificar si es petición AJAX
     */
    protected function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Renderizar vista
     */
    protected function render($view, $data = []) {
        // Combinar datos de la vista con datos comunes
        $viewData = array_merge($this->viewData, $data);
        
        // Extraer variables para la vista
        extract($viewData);
        
        // Incluir header
        include __DIR__ . '/../views/layouts/header.php';
        
        // Incluir vista específica
        $viewFile = __DIR__ . "/../views/{$view}.php";
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            throw new Exception("Vista no encontrada: {$view}");
        }
        
        // Incluir footer
        include __DIR__ . '/../views/layouts/footer.php';
    }
    
    /**
     * Renderizar vista parcial (sin layout)
     */
    protected function renderPartial($view, $data = []) {
        $viewData = array_merge($this->viewData, $data);
        extract($viewData);
        
        $viewFile = __DIR__ . "/../views/{$view}.php";
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            throw new Exception("Vista no encontrada: {$view}");
        }
    }
    
    /**
     * Validar datos de entrada
     */
    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $fieldRules = explode('|', $rule);
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $fieldRule) {
                $ruleParts = explode(':', $fieldRule);
                $ruleName = $ruleParts[0];
                $ruleValue = $ruleParts[1] ?? null;
                
                switch ($ruleName) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field] = "El campo {$field} es requerido";
                        }
                        break;
                        
                    case 'email':
                        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "El campo {$field} debe ser un email válido";
                        }
                        break;
                        
                    case 'min':
                        if ($value && strlen($value) < $ruleValue) {
                            $errors[$field] = "El campo {$field} debe tener al menos {$ruleValue} caracteres";
                        }
                        break;
                        
                    case 'max':
                        if ($value && strlen($value) > $ruleValue) {
                            $errors[$field] = "El campo {$field} no puede tener más de {$ruleValue} caracteres";
                        }
                        break;
                        
                    case 'numeric':
                        if ($value && !is_numeric($value)) {
                            $errors[$field] = "El campo {$field} debe ser numérico";
                        }
                        break;
                        
                    case 'unique':
                        if ($value && $this->isValueUnique($ruleValue, $field, $value)) {
                            $errors[$field] = "El valor del campo {$field} ya existe";
                        }
                        break;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Verificar si un valor es único en la base de datos
     */
    protected function isValueUnique($table, $field, $value, $excludeId = null) {
        $sql = "SELECT id FROM {$table} WHERE {$field} = ?";
        
        if ($excludeId) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $this->db->prepare($sql);
        
        if ($excludeId) {
            $stmt->bind_param("si", $value, $excludeId);
        } else {
            $stmt->bind_param("s", $value);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
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
     * Paginación
     */
    protected function paginate($query, $params = [], $page = 1, $perPage = 10) {
        $page = max(1, intval($page));
        $offset = ($page - 1) * $perPage;
        
        // Contar total de registros
        $countQuery = preg_replace('/SELECT .* FROM/', 'SELECT COUNT(*) as total FROM', $query);
        $countQuery = preg_replace('/ORDER BY .*/', '', $countQuery);
        
        $stmt = $this->db->prepare($countQuery);
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // Obtener registros paginados
        $paginatedQuery = $query . " LIMIT {$offset}, {$perPage}";
        $stmt = $this->db->prepare($paginatedQuery);
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_prev' => $page > 1,
                'has_next' => $page < ceil($total / $perPage)
            ]
        ];
    }
    
    /**
     * Registrar actividad en logs
     */
    protected function logActivity($action, $table, $recordId = null, $details = []) {
        try {
            $userId = $this->currentUser['id'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $logData = [
                'usuario_id' => $userId,
                'accion' => $action,
                'tabla_afectada' => $table,
                'registro_id' => $recordId,
                'detalles' => json_encode(array_merge($details, [
                    'ip' => $ip,
                    'user_agent' => $userAgent,
                    'url' => $_SERVER['REQUEST_URI'] ?? ''
                ])),
                'fecha_hora' => date('Y-m-d H:i:s')
            ];
            
            $stmt = $this->db->prepare("
                INSERT INTO logs_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, fecha_hora) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "ississ", 
                $logData['usuario_id'],
                $logData['accion'],
                $logData['tabla_afectada'],
                $logData['registro_id'],
                $logData['detalles'],
                $logData['fecha_hora']
            );
            
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
    
    /**
     * Manejar errores de forma consistente
     */
    protected function handleError($message, $code = 500, $redirect = null) {
        if ($this->isAjaxRequest()) {
            $this->jsonResponse([
                'success' => false,
                'message' => $message,
                'code' => $code
            ], $code);
        }
        
        if ($redirect) {
            $this->redirect($redirect, $message, 'error');
        }
        
        throw new Exception($message, $code);
    }
    
    /**
     * Obtener filtros según el rol del usuario
     */
    protected function getRoleFilters($resource) {
        if (!$this->currentUser) {
            return [];
        }
        
        $userRole = $this->currentUser['rol'];
        $userId = $this->currentUser['id'];
        
        // Super admin y admin ven todo
        if (in_array($userRole, ['super_admin', 'admin'])) {
            return [];
        }
        
        // Profesores solo ven sus recursos
        if ($userRole === 'profesor') {
            switch ($resource) {
                case 'cursos':
                    return ['profesor_id' => $userId];
                case 'sesiones':
                    return ['c.profesor_id' => $userId];
                case 'asistencias':
                    return ['c.profesor_id' => $userId];
                case 'estudiantes':
                    return ['c.profesor_id' => $userId];
                default:
                    return [];
            }
        }
        
        return [];
    }
    
    /**
     * Aplicar middleware antes de ejecutar acción
     */
    protected function applyMiddleware($middlewares) {
        if (!is_array($middlewares)) {
            $middlewares = [$middlewares];
        }
        
        foreach ($middlewares as $middleware) {
            MiddlewareManager::apply($middleware, $this->db);
        }
    }
    
    /**
     * Método que debe implementar cada controlador para definir sus rutas
     */
    abstract public function handleRequest();
}