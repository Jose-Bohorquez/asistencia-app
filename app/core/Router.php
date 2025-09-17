<?php
/**
 * Sistema de rutas robusto y seguro
 * Maneja el enrutamiento de la aplicación con validación y seguridad mejorada
 */
class Router {
    private $routes = [];
    private $middlewares = [];
    private $currentUser = null;
    private $middlewareManager = null;
    
    public function __construct($middlewareManager = null) {
        $this->middlewareManager = $middlewareManager;
        $this->initializeRoutes();
    }
    
    /**
     * Registrar una ruta
     */
    public function addRoute($path, $controller, $method, $options = []) {
        $this->routes[$path] = [
            'controller' => $controller,
            'method' => $method,
            'auth' => $options['auth'] ?? true,
            'permissions' => $options['permissions'] ?? [],
            'roles' => $options['roles'] ?? [],
            'csrf' => $options['csrf'] ?? false,
            'rate_limit' => $options['rate_limit'] ?? null,
            'https_only' => $options['https_only'] ?? false
        ];
    }
    
    /**
     * Inicializar rutas de la aplicación
     */
    private function initializeRoutes() {
        // Rutas públicas
        $this->addRoute('login', 'AuthController', 'login', ['auth' => false, 'csrf' => true]);
        $this->addRoute('asistencia', 'AsistenciaController', 'handleRequest', ['auth' => false]);
        
        // Rutas autenticadas
        $this->addRoute('logout', 'AuthController', 'logout', ['csrf' => true]);
        $this->addRoute('dashboard', 'AdminController', 'dashboard');
        $this->addRoute('cursos', 'AdminController', 'cursos', ['permissions' => ['cursos_view']]);
        
        // Rutas con permisos específicos
        $this->addRoute('sesiones', 'SesionesController', 'handleRequest', [
            'permissions' => ['sesiones_view']
        ]);
        
        $this->addRoute('usuarios', 'UsuariosController', 'handleRequest', [
            'permissions' => ['usuarios_view'],
            'roles' => ['super_admin', 'admin']
        ]);
        
        $this->addRoute('programas', 'ProgramasController', 'handleRequest', [
            'permissions' => ['programas_view'],
            'roles' => ['super_admin', 'admin']
        ]);
        
        // Rutas de exportación con rate limiting
        $this->addRoute('exportar', 'ExportController', 'handleRequest', [
            'permissions' => ['reportes_export'],
            'rate_limit' => ['max' => 10, 'window' => 3600] // 10 exportaciones por hora
        ]);
        
        $this->addRoute('enviar_correo', 'EmailController', 'handleRequest', [
            'permissions' => ['email_send'],
            'csrf' => true,
            'rate_limit' => ['max' => 50, 'window' => 3600] // 50 emails por hora
        ]);
    }
    
    /**
     * Procesar la solicitud actual
     */
    public function dispatch() {
        try {
            $path = $this->getCurrentPath();
            $route = $this->findRoute($path);
            
            if (!$route) {
                return $this->handleNotFound();
            }
            
            // Aplicar middlewares de seguridad
            if (!$this->applySecurityMiddlewares($route)) {
                return false;
            }
            
            // Ejecutar la ruta
            return $this->executeRoute($route);
            
        } catch (Exception $e) {
            return $this->handleError($e);
        }
    }
    
    /**
     * Obtener la ruta actual
     */
    private function getCurrentPath() {
        $path = $_GET['page'] ?? 'home';
        
        // Sanitizar la ruta
        $path = preg_replace('/[^a-zA-Z0-9_-]/', '', $path);
        
        return $path;
    }
    
    /**
     * Encontrar una ruta registrada
     */
    private function findRoute($path) {
        return $this->routes[$path] ?? null;
    }
    
    /**
     * Aplicar middlewares de seguridad
     */
    private function applySecurityMiddlewares($route) {
        // 1. Verificar HTTPS si es requerido
        if ($route['https_only'] && !$this->isHttps()) {
            $this->redirectToHttps();
            return false;
        }
        
        // 2. Verificar autenticación
        if ($route['auth'] && !$this->isAuthenticated()) {
            $this->redirectToLogin();
            return false;
        }
        
        // 3. Verificar permisos
        if (!empty($route['permissions']) && !$this->hasPermissions($route['permissions'])) {
            $this->handleUnauthorized();
            return false;
        }
        
        // 4. Verificar roles
        if (!empty($route['roles']) && !$this->hasRole($route['roles'])) {
            $this->handleUnauthorized();
            return false;
        }
        
        // 5. Verificar CSRF token
        if ($route['csrf'] && $_SERVER['REQUEST_METHOD'] === 'POST' && !$this->validateCsrfToken()) {
            $this->handleCsrfError();
            return false;
        }
        
        // 6. Aplicar rate limiting
        if ($route['rate_limit'] && !$this->checkRateLimit($route['rate_limit'])) {
            $this->handleRateLimitExceeded();
            return false;
        }
        
        return true;
    }
    
    /**
     * Ejecutar una ruta
     */
    private function executeRoute($route) {
        $controllerName = $route['controller'];
        $methodName = $route['method'];
        
        // Verificar que el controlador existe
        if (!class_exists($controllerName)) {
            throw new Exception("Controller {$controllerName} not found");
        }
        
        $controller = new $controllerName();
        
        // Verificar que el método existe
        if (!method_exists($controller, $methodName)) {
            throw new Exception("Method {$methodName} not found in {$controllerName}");
        }
        
        // Ejecutar el método
        return $controller->$methodName();
    }
    
    /**
     * Verificar si la conexión es HTTPS
     */
    private function isHttps() {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               $_SERVER['SERVER_PORT'] == 443 ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
    
    /**
     * Verificar autenticación
     */
    private function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Verificar permisos
     */
    private function hasPermissions($permissions) {
        if (!$this->middlewareManager) {
            return true; // Fallback si no hay middleware manager
        }
        
        foreach ($permissions as $permission) {
            if (!$this->middlewareManager->checkPermission($permission)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Verificar roles
     */
    private function hasRole($roles) {
        $userRole = $_SESSION['user_rol'] ?? null;
        return in_array($userRole, $roles);
    }
    
    /**
     * Validar token CSRF
     */
    private function validateCsrfToken() {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        
        return $token && $sessionToken && hash_equals($sessionToken, $token);
    }
    
    /**
     * Verificar rate limiting
     */
    private function checkRateLimit($config) {
        $key = 'rate_limit_' . ($_SESSION['user_id'] ?? $_SERVER['REMOTE_ADDR']);
        $max = $config['max'];
        $window = $config['window'];
        
        // Implementación simple con archivos (en producción usar Redis/Memcached)
        $file = sys_get_temp_dir() . '/' . md5($key) . '.txt';
        $now = time();
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            
            // Limpiar entradas antiguas
            $data = array_filter($data, function($timestamp) use ($now, $window) {
                return ($now - $timestamp) < $window;
            });
            
            if (count($data) >= $max) {
                return false;
            }
        } else {
            $data = [];
        }
        
        // Agregar nueva entrada
        $data[] = $now;
        file_put_contents($file, json_encode($data));
        
        return true;
    }
    
    /**
     * Redirigir a HTTPS
     */
    private function redirectToHttps() {
        $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header("Location: $redirectURL");
        exit();
    }
    
    /**
     * Redirigir al login
     */
    private function redirectToLogin() {
        header('Location: index.php?page=login');
        exit();
    }
    
    /**
     * Manejar acceso no autorizado
     */
    private function handleUnauthorized() {
        if ($this->isAjaxRequest()) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes permisos para acceder a este recurso']);
        } else {
            // Redirigir al login en lugar del dashboard para evitar bucles infinitos
            header('Location: index.php?page=login&error=unauthorized');
        }
        exit();
    }
    
    /**
     * Manejar error CSRF
     */
    private function handleCsrfError() {
        if ($this->isAjaxRequest()) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF inválido']);
        } else {
            header('Location: index.php?page=login&error=csrf');
        }
        exit();
    }
    
    /**
     * Manejar rate limit excedido
     */
    private function handleRateLimitExceeded() {
        if ($this->isAjaxRequest()) {
            http_response_code(429);
            echo json_encode(['error' => 'Demasiadas solicitudes. Intenta más tarde.']);
        } else {
            header('Location: index.php?page=login&error=rate_limit');
        }
        exit();
    }
    
    /**
     * Manejar ruta no encontrada
     */
    private function handleNotFound() {
        if ($this->isAuthenticated()) {
            header('Location: index.php?page=dashboard');
        } else {
            header('Location: index.php?page=login');
        }
        exit();
    }
    
    /**
     * Manejar errores
     */
    private function handleError($exception) {
        error_log('Router Error: ' . $exception->getMessage());
        
        if ($this->isAjaxRequest()) {
            http_response_code(500);
            echo json_encode(['error' => 'Error interno del servidor']);
        } else {
            header('Location: index.php?page=login&error=server');
        }
        exit();
    }
    
    /**
     * Verificar si es una solicitud AJAX
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Generar token CSRF
     */
    public static function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
?>