<?php
require_once __DIR__ . '/AuthMiddleware.php';
require_once __DIR__ . '/RoleMiddleware.php';

/**
 * Gestor de Middlewares
 * Facilita la aplicación y gestión de múltiples middlewares
 */
class MiddlewareManager {
    private $middlewares = [];
    private $db;
    
    public function __construct($db = null) {
        $this->db = $db;
    }
    
    /**
     * Agregar middleware a la pila
     */
    public function add($middleware) {
        if (is_string($middleware)) {
            $middleware = $this->createMiddleware($middleware);
        }
        
        if ($middleware instanceof Middleware) {
            $this->middlewares[] = $middleware;
        }
        
        return $this;
    }
    
    /**
     * Crear instancia de middleware por nombre
     */
    private function createMiddleware($name) {
        switch ($name) {
            case 'auth':
                return new AuthMiddleware($this->db);
            case 'role':
                return new RoleMiddleware($this->db);
            default:
                throw new Exception("Middleware '{$name}' no encontrado");
        }
    }
    
    /**
     * Ejecutar todos los middlewares
     */
    public function handle($request = null) {
        $next = function($request) {
            return $request;
        };
        
        // Ejecutar middlewares en orden inverso para crear la cadena
        for ($i = count($this->middlewares) - 1; $i >= 0; $i--) {
            $middleware = $this->middlewares[$i];
            $next = function($request) use ($middleware, $next) {
                return $middleware->handle($request, $next);
            };
        }
        
        return $next($request);
    }
    
    /**
     * Aplicar middlewares específicos para una ruta
     */
    public static function apply($middlewares, $db = null) {
        $manager = new self($db);
        
        if (!is_array($middlewares)) {
            $middlewares = [$middlewares];
        }
        
        foreach ($middlewares as $middleware) {
            $manager->add($middleware);
        }
        
        return $manager->handle();
    }
    
    /**
     * Verificar si el usuario actual tiene un permiso específico
     */
    public function checkPermission($permission) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Super admin tiene todos los permisos
        if (($_SESSION['user_rol'] ?? '') === 'super_admin') {
            return true;
        }
        
        $roleMiddleware = new RoleMiddleware($this->db);
        return $roleMiddleware->hasPermissionForAction($permission);
    }
    
    /**
     * Middleware para rutas que requieren autenticación
     */
    public static function auth($roles = [], $db = null) {
        $authMiddleware = new AuthMiddleware($db);
        $authMiddleware->handle();
        
        if (!empty($roles)) {
            $roleMiddleware = new RoleMiddleware($db);
            
            if (!$roleMiddleware->hasAnyRole($roles)) {
                $roleMiddleware->logSecurityEvent('role_access_denied', [
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'user_role' => $_SESSION['user_rol'] ?? null,
                    'required_roles' => $roles,
                    'url' => $_SERVER['REQUEST_URI'] ?? ''
                ]);
                
                if ($roleMiddleware->isAjaxRequest()) {
                    $roleMiddleware->jsonResponse([
                        'success' => false,
                        'message' => 'No tienes permisos para acceder a este recurso'
                    ], 403);
                }
                
                $roleMiddleware->redirect('/public/index.php?page=dashboard', 
                                        'No tienes permisos para acceder a este recurso');
            }
        }
        
        return true;
    }
    
    /**
     * Middleware para rutas que requieren estar deslogueado
     */
    public static function guest($db = null) {
        return AuthMiddleware::requireGuest();
    }
    
    /**
     * Middleware para verificar permisos específicos
     */
    public static function permission($permission, $db = null) {
        return RoleMiddleware::requirePermission($permission);
    }
    
    /**
     * Middleware para verificar CSRF
     */
    public static function csrf($db = null) {
        return RoleMiddleware::requireCSRF();
    }
    
    /**
     * Middleware combinado: auth + role + csrf
     */
    public static function secure($roles = [], $db = null) {
        // Verificar CSRF para POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::csrf($db);
        }
        
        // Verificar autenticación y roles
        return self::auth($roles, $db);
    }
    
    /**
     * Middleware para AJAX que requiere autenticación
     */
    public static function ajaxAuth($roles = [], $db = null) {
        return AuthMiddleware::requireAuthAjax($roles);
    }
    
    /**
     * Aplicar middleware según la configuración de rutas
     */
    public static function applyForRoute($page, $action = null, $db = null) {
        // Configuración de middlewares por ruta
        $routeMiddlewares = [
            // Rutas públicas (solo guest)
            'login' => ['guest'],
            
            // Rutas que requieren autenticación básica
            'dashboard' => ['auth'],
            'perfil' => ['auth'],
            
            // Rutas de administración (super_admin y admin)
            'usuarios' => ['auth:super_admin,admin'],
            'programas' => ['auth:super_admin,admin'],
            'logs' => ['auth:super_admin'],
            'configuracion' => ['auth:super_admin'],
            
            // Rutas de gestión académica (todos los roles autenticados)
            'cursos' => ['auth:super_admin,admin,profesor'],
            'estudiantes' => ['auth:super_admin,admin,profesor'],
            'sesiones' => ['auth:super_admin,admin,profesor'],
            'asistencia' => ['auth:super_admin,admin,profesor'],
            'reportes' => ['auth:super_admin,admin,profesor'],
            'exportar' => ['auth:super_admin,admin,profesor'],
        ];
        
        $middlewares = $routeMiddlewares[$page] ?? ['auth'];
        
        foreach ($middlewares as $middleware) {
            if (strpos($middleware, ':') !== false) {
                list($middlewareName, $rolesString) = explode(':', $middleware, 2);
                $roles = explode(',', $rolesString);
                
                switch ($middlewareName) {
                    case 'auth':
                        self::auth($roles, $db);
                        break;
                    case 'permission':
                        self::permission($rolesString, $db);
                        break;
                }
            } else {
                switch ($middleware) {
                    case 'auth':
                        self::auth([], $db);
                        break;
                    case 'guest':
                        self::guest($db);
                        break;
                    case 'csrf':
                        self::csrf($db);
                        break;
                }
            }
        }
        
        // Aplicar CSRF para acciones que modifican datos
        $csrfActions = ['create', 'update', 'delete', 'store', 'destroy'];
        if ($action && in_array($action, $csrfActions)) {
            self::csrf($db);
        }
        
        return true;
    }
    
    /**
     * Verificar si el usuario actual puede acceder a una página
     */
    public static function canAccessPage($page, $db = null) {
        try {
            self::applyForRoute($page, null, $db);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtener páginas accesibles para el usuario actual
     */
    public static function getAccessiblePages($db = null) {
        if (!isset($_SESSION['user_id'])) {
            return ['login'];
        }
        
        $userRole = $_SESSION['user_rol'];
        $pages = [];
        
        // Páginas básicas para todos los usuarios autenticados
        $pages[] = 'dashboard';
        $pages[] = 'perfil';
        
        // Páginas según el rol
        switch ($userRole) {
            case 'super_admin':
                $pages = array_merge($pages, [
                    'usuarios', 'programas', 'cursos', 'estudiantes', 
                    'sesiones', 'asistencia', 'reportes', 'exportar',
                    'logs', 'configuracion'
                ]);
                break;
                
            case 'admin':
                $pages = array_merge($pages, [
                    'usuarios', 'programas', 'cursos', 'estudiantes',
                    'sesiones', 'asistencia', 'reportes', 'exportar'
                ]);
                break;
                
            case 'profesor':
                $pages = array_merge($pages, [
                    'cursos', 'estudiantes', 'sesiones', 
                    'asistencia', 'reportes', 'exportar'
                ]);
                break;
        }
        
        return $pages;
    }
    
    /**
     * Generar menú de navegación según permisos
     */
    public static function generateNavMenu($db = null) {
        if (!isset($_SESSION['user_id'])) {
            return [];
        }
        
        $userRole = $_SESSION['user_rol'];
        $menu = [];
        
        // Dashboard siempre disponible
        $menu[] = [
            'title' => 'Dashboard',
            'url' => '/public/index.php?page=dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'active' => ($_GET['page'] ?? '') === 'dashboard'
        ];
        
        // Menú según rol
        if (in_array($userRole, ['super_admin', 'admin'])) {
            $menu[] = [
                'title' => 'Usuarios',
                'url' => '/public/index.php?page=usuarios',
                'icon' => 'fas fa-users',
                'active' => ($_GET['page'] ?? '') === 'usuarios'
            ];
            
            $menu[] = [
                'title' => 'Programas',
                'url' => '/public/index.php?page=programas',
                'icon' => 'fas fa-graduation-cap',
                'active' => ($_GET['page'] ?? '') === 'programas'
            ];
        }
        
        // Menú académico para todos los roles autenticados
        $menu[] = [
            'title' => 'Cursos',
            'url' => '/public/index.php?page=cursos',
            'icon' => 'fas fa-book',
            'active' => ($_GET['page'] ?? '') === 'cursos'
        ];
        
        $menu[] = [
            'title' => 'Estudiantes',
            'url' => '/public/index.php?page=estudiantes',
            'icon' => 'fas fa-user-graduate',
            'active' => ($_GET['page'] ?? '') === 'estudiantes'
        ];
        
        $menu[] = [
            'title' => 'Sesiones',
            'url' => '/public/index.php?page=sesiones',
            'icon' => 'fas fa-calendar-alt',
            'active' => ($_GET['page'] ?? '') === 'sesiones'
        ];
        
        $menu[] = [
            'title' => 'Reportes',
            'url' => '/public/index.php?page=reportes',
            'icon' => 'fas fa-chart-bar',
            'active' => ($_GET['page'] ?? '') === 'reportes'
        ];
        
        // Menú de administración solo para super_admin
        if ($userRole === 'super_admin') {
            $menu[] = [
                'title' => 'Logs del Sistema',
                'url' => '/public/index.php?page=logs',
                'icon' => 'fas fa-list-alt',
                'active' => ($_GET['page'] ?? '') === 'logs'
            ];
            
            $menu[] = [
                'title' => 'Configuración',
                'url' => '/public/index.php?page=configuracion',
                'icon' => 'fas fa-cog',
                'active' => ($_GET['page'] ?? '') === 'configuracion'
            ];
        }
        
        return $menu;
    }
    
    /**
     * Verificar si una acción específica está permitida
     */
    public static function isActionAllowed($page, $action, $db = null) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $userRole = $_SESSION['user_rol'];
        
        // Super admin puede hacer todo
        if ($userRole === 'super_admin') {
            return true;
        }
        
        // Admin no puede eliminar
        if ($userRole === 'admin' && in_array($action, ['delete', 'destroy', 'eliminar'])) {
            return false;
        }
        
        // Profesor solo puede manejar sus recursos
        if ($userRole === 'profesor') {
            $allowedPages = ['cursos', 'estudiantes', 'sesiones', 'asistencia', 'reportes'];
            if (!in_array($page, $allowedPages)) {
                return false;
            }
            
            // No puede eliminar
            if (in_array($action, ['delete', 'destroy', 'eliminar'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Obtener información de permisos para el frontend
     */
    public static function getPermissionsInfo($db = null) {
        if (!isset($_SESSION['user_id'])) {
            return [];
        }
        
        $roleMiddleware = new RoleMiddleware($db);
        
        return [
            'user_role' => $_SESSION['user_rol'],
            'permissions' => $roleMiddleware->getUserPermissions(),
            'accessible_pages' => self::getAccessiblePages($db),
            'can_delete' => $_SESSION['user_rol'] === 'super_admin',
            'can_create_users' => in_array($_SESSION['user_rol'], ['super_admin', 'admin']),
            'can_manage_programs' => in_array($_SESSION['user_rol'], ['super_admin', 'admin']),
            'is_professor' => $_SESSION['user_rol'] === 'profesor'
        ];
    }
}