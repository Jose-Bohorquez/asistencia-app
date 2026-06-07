<?php
require_once __DIR__ . '/Middleware.php';

/**
 * Middleware de Roles y Permisos
 * Verifica que el usuario tenga los permisos necesarios para acceder a recursos específicos
 */
class RoleMiddleware extends Middleware {
    
    // Definición de permisos por rol
    private $rolePermissions = [
        'super_admin' => [
            // Acceso total a todo
            'dashboard_access',
            'usuarios_create', 'usuarios_read', 'usuarios_update', 'usuarios_delete',
            'programas_create', 'programas_read', 'programas_update', 'programas_delete',
            'cursos_create', 'cursos_read', 'cursos_update', 'cursos_delete',
            'estudiantes_create', 'estudiantes_read', 'estudiantes_update', 'estudiantes_delete',
            'sesiones_create', 'sesiones_read', 'sesiones_update', 'sesiones_delete',
            'asistencias_create', 'asistencias_read', 'asistencias_update', 'asistencias_delete',
            'reportes_read', 'reportes_export',
            'logs_read', 'configuracion_update',
            'email_config', 'email_test', 'email_send',
            'reportes_email', 'notifications_send',
            'usuarios_create', 'usuarios_toggle',
            'perfil_update', 'foto_perfil_update',
            'sesiones_activate', 'sesiones_delete',
            'asistencias_export', 'estadisticas_read',
            'programas_export'
        ],
        'admin' => [
            'dashboard_access',
            'usuarios_read', 'usuarios_update', 'usuarios_toggle',
            'programas_create', 'programas_read', 'programas_update', 'programas_export',
            'cursos_create', 'cursos_read', 'cursos_update',
            'estudiantes_create', 'estudiantes_read', 'estudiantes_update',
            'sesiones_create', 'sesiones_read', 'sesiones_update', 'sesiones_activate', 'sesiones_delete',
            'asistencias_create', 'asistencias_read', 'asistencias_update', 'asistencias_export',
            'reportes_read', 'reportes_export', 'reportes_email',
            'estadisticas_read',
            'email_send',
            'perfil_update', 'foto_perfil_update'
        ],
        'profesor' => [
            'dashboard_access',
            'cursos_read_own', 'cursos_update_own',
            'estudiantes_read_own', 'estudiantes_update_own',
            'sesiones_create_own', 'sesiones_read_own', 'sesiones_update_own',
            'sesiones_activate_own', 'sesiones_delete_own',
            'asistencias_create_own', 'asistencias_read_own', 'asistencias_update_own', 'asistencias_export',
            'reportes_read_own', 'reportes_export_own', 'reportes_email',
            'estadisticas_read',
            'perfil_update', 'foto_perfil_update'
        ]
    ];
    
    /**
     * Manejar la verificación de permisos
     */
    public function handle($request = null, $next = null) {
        // Primero verificar que esté autenticado
        if (!$this->isAuthenticated()) {
            $this->handleUnauthorized('not_authenticated');
        }
        
        // Obtener información de la petición
        $resource = $this->getRequestedResource();
        $action = $this->getRequestedAction();
        $permission = $resource . '_' . $action;
        
        // Verificar permisos
        if (!$this->hasPermissionForAction($permission)) {
            $this->logSecurityEvent('permission_denied', [
                'user_id' => $_SESSION['user_id'],
                'user_role' => $_SESSION['user_rol'],
                'requested_permission' => $permission,
                'resource' => $resource,
                'action' => $action
            ]);
            
            $this->handleUnauthorized('access_denied');
        }
        
        // Verificar permisos específicos para recursos propios (profesores)
        if ($this->isOwnResourcePermission($permission)) {
            if (!$this->canAccessOwnResource($resource)) {
                $this->handleUnauthorized('access_denied_own_resource');
            }
        }
        
        // Continuar con la siguiente función si existe
        if ($next && is_callable($next)) {
            return $next($request);
        }
        
        return true;
    }
    
    /**
     * Obtener el recurso solicitado desde la URL
     */
    private function getRequestedResource() {
        $page = $_GET['page'] ?? 'dashboard';
        $action = $_GET['action'] ?? 'index';
        
        // Mapear páginas a recursos
        $resourceMap = [
            'usuarios' => 'usuarios',
            'programas' => 'programas',
            'cursos' => 'cursos',
            'estudiantes' => 'estudiantes',
            'sesiones' => 'sesiones',
            'asistencia' => 'asistencias',
            'reportes' => 'reportes',
            'exportar' => 'reportes',
            'logs' => 'logs',
            'configuracion' => 'configuracion'
        ];
        
        return $resourceMap[$page] ?? 'dashboard';
    }
    
    /**
     * Obtener la acción solicitada
     */
    private function getRequestedAction() {
        $action = $_GET['action'] ?? 'read';
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Mapear métodos HTTP y acciones a permisos
        if ($method === 'POST') {
            if (in_array($action, ['create', 'store', 'nuevo'])) {
                return 'create';
            }
            if (in_array($action, ['update', 'edit', 'editar'])) {
                return 'update';
            }
            if (in_array($action, ['delete', 'destroy', 'eliminar'])) {
                return 'delete';
            }
        }
        
        if ($method === 'GET') {
            if (in_array($action, ['create', 'nuevo'])) {
                return 'create';
            }
            if (in_array($action, ['edit', 'editar'])) {
                return 'update';
            }
            if (in_array($action, ['delete', 'eliminar'])) {
                return 'delete';
            }
            if (in_array($action, ['export', 'exportar'])) {
                return 'export';
            }
        }
        
        return 'read';
    }
    
    /**
     * Verificar si el usuario tiene permiso para una acción específica
     */
    public function hasPermissionForAction($permission) {
        $userRole = $_SESSION['user_rol'];
        
        // Super admin tiene acceso a todo
        if ($userRole === 'super_admin') {
            return true;
        }
        
        // Verificar permisos del rol
        $rolePermissions = $this->rolePermissions[$userRole] ?? [];
        
        // Verificar permiso exacto
        if (in_array($permission, $rolePermissions)) {
            return true;
        }
        
        // Para profesores, verificar si existe la variante _own del permiso
        if ($userRole === 'profesor') {
            $ownPermission = str_replace(
                ['_create',     '_read',     '_update',     '_delete',     '_activate'],
                ['_create_own', '_read_own', '_update_own', '_delete_own', '_activate_own'],
                $permission
            );
            if ($ownPermission !== $permission && in_array($ownPermission, $rolePermissions)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verificar si es un permiso de recurso propio
     */
    private function isOwnResourcePermission($permission) {
        return strpos($permission, '_own') !== false;
    }
    
    /**
     * Verificar si puede acceder a su propio recurso
     */
    private function canAccessOwnResource($resource) {
        $userId = $_SESSION['user_id'];
        $resourceId = $_GET['id'] ?? null;
        
        if (!$resourceId || !$this->db) {
            return true; // Permitir acceso a listados generales
        }
        
        switch ($resource) {
            case 'cursos':
                return $this->isOwnCourse($userId, $resourceId);
            case 'sesiones':
                return $this->isOwnSession($userId, $resourceId);
            case 'asistencias':
                return $this->isOwnAttendance($userId, $resourceId);
            case 'estudiantes':
                return $this->isOwnStudent($userId, $resourceId);
            default:
                return true;
        }
    }
    
    /**
     * Verificar si el curso pertenece al profesor
     */
    private function isOwnCourse($profesorId, $cursoId) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM cursos WHERE id = ? AND profesor_id = ?");
            $stmt->bind_param("ii", $cursoId, $profesorId);
            $stmt->execute();
            $result = $stmt->get_result();
            $isOwn = $result->num_rows > 0;
            $stmt->close();
            return $isOwn;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Verificar si la sesión pertenece al profesor
     */
    private function isOwnSession($profesorId, $sesionId) {
        try {
            $stmt = $this->db->prepare("
                SELECT s.id 
                FROM sesiones s 
                INNER JOIN cursos c ON s.curso_id = c.id 
                WHERE s.id = ? AND c.profesor_id = ?
            ");
            $stmt->bind_param("ii", $sesionId, $profesorId);
            $stmt->execute();
            $result = $stmt->get_result();
            $isOwn = $result->num_rows > 0;
            $stmt->close();
            return $isOwn;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Verificar si la asistencia pertenece a una sesión del profesor
     */
    private function isOwnAttendance($profesorId, $asistenciaId) {
        try {
            $stmt = $this->db->prepare("
                SELECT a.id 
                FROM asistencias a 
                INNER JOIN sesiones s ON a.sesion_id = s.id 
                INNER JOIN cursos c ON s.curso_id = c.id 
                WHERE a.id = ? AND c.profesor_id = ?
            ");
            $stmt->bind_param("ii", $asistenciaId, $profesorId);
            $stmt->execute();
            $result = $stmt->get_result();
            $isOwn = $result->num_rows > 0;
            $stmt->close();
            return $isOwn;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Verificar si el estudiante está en un curso del profesor
     */
    private function isOwnStudent($profesorId, $estudianteId) {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT e.id 
                FROM estudiantes e 
                INNER JOIN cursos_estudiantes ce ON e.id = ce.estudiante_id 
                INNER JOIN cursos c ON ce.curso_id = c.id 
                WHERE e.id = ? AND c.profesor_id = ?
            ");
            $stmt->bind_param("ii", $estudianteId, $profesorId);
            $stmt->execute();
            $result = $stmt->get_result();
            $isOwn = $result->num_rows > 0;
            $stmt->close();
            return $isOwn;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Manejar acceso no autorizado
     */
    private function handleUnauthorized($reason) {
        $message = $this->getErrorMessage($reason);
        
        if ($this->isAjaxRequest()) {
            $statusCode = ($reason === 'not_authenticated') ? 401 : 403;
            $this->jsonResponse([
                'success' => false,
                'message' => $message,
                'redirect' => ($reason === 'not_authenticated') ? '/public/index.php?page=login' : null
            ], $statusCode);
        }
        
        $redirectUrl = ($reason === 'not_authenticated') ? 
                      '/public/index.php?page=login' : 
                      '/public/index.php?page=dashboard';
        
        $this->redirect($redirectUrl, $message);
    }
    
    /**
     * Verificar permisos específicos para una acción
     */
    public static function requirePermission($permission, $resourceId = null) {
        $middleware = new self();
        $middleware->db = new Database();
        $middleware->db = $middleware->db->connect();
        
        if (!$middleware->hasPermissionForAction($permission)) {
            $middleware->logSecurityEvent('permission_check_failed', [
                'user_id' => $_SESSION['user_id'] ?? null,
                'required_permission' => $permission,
                'resource_id' => $resourceId
            ]);
            
            $middleware->handleUnauthorized('access_denied');
        }
        
        return true;
    }
    
    /**
     * Verificar múltiples permisos (AND)
     */
    public static function requireAllPermissions($permissions) {
        foreach ($permissions as $permission) {
            self::requirePermission($permission);
        }
        return true;
    }
    
    /**
     * Verificar si tiene alguno de los permisos (OR)
     */
    public static function requireAnyPermission($permissions) {
        $middleware = new self();
        
        foreach ($permissions as $permission) {
            if ($middleware->hasPermissionForAction($permission)) {
                return true;
            }
        }
        
        $middleware->handleUnauthorized('access_denied');
    }
    
    /**
     * Obtener permisos del usuario actual
     */
    public function getUserPermissions() {
        $userRole = $_SESSION['user_rol'] ?? null;
        
        if (!$userRole) {
            return [];
        }
        
        return $this->rolePermissions[$userRole] ?? [];
    }
    
    /**
     * Verificar si el usuario puede realizar una acción específica
     */
    public function can($action, $resource = null, $resourceId = null) {
        if ($resource) {
            $permission = $resource . '_' . $action;
        } else {
            $permission = $action;
        }
        
        if (!$this->hasPermissionForAction($permission)) {
            return false;
        }
        
        // Verificar permisos de recursos propios si es necesario
        if ($this->isOwnResourcePermission($permission) && $resourceId) {
            return $this->canAccessOwnResource($resource);
        }
        
        return true;
    }
    
    /**
     * Obtener filtros de consulta según el rol del usuario
     */
    public function getQueryFilters($resource) {
        $userRole = $_SESSION['user_rol'];
        $userId = $_SESSION['user_id'];
        
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
                    return ['curso.profesor_id' => $userId];
                case 'asistencias':
                    return ['curso.profesor_id' => $userId];
                case 'estudiantes':
                    return ['curso.profesor_id' => $userId];
                default:
                    return [];
            }
        }
        
        return [];
    }
    
    /**
     * Middleware para verificar permisos CSRF
     */
    public static function requireCSRF() {
        $middleware = new self();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$middleware->validateCSRF()) {
                $middleware->logSecurityEvent('csrf_validation_failed', [
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'url' => $_SERVER['REQUEST_URI'] ?? ''
                ]);
                
                if ($middleware->isAjaxRequest()) {
                    $middleware->jsonResponse([
                        'success' => false,
                        'message' => $middleware->getErrorMessage('invalid_token')
                    ], 403);
                }
                
                $middleware->redirect($_SERVER['HTTP_REFERER'] ?? '/public/index.php', 
                                    $middleware->getErrorMessage('invalid_token'));
            }
        }
        
        return true;
    }
    
    /**
     * Obtener mensajes de error personalizados
     */
    protected function getErrorMessage($type = 'access_denied') {
        $messages = array_merge(parent::getErrorMessage(''), [
            'access_denied_own_resource' => 'No tienes permisos para acceder a este recurso específico.',
            'permission_denied' => 'No tienes los permisos necesarios para realizar esta acción.',
            'role_required' => 'Tu rol no tiene acceso a esta funcionalidad.'
        ]);
        
        return $messages[$type] ?? parent::getErrorMessage($type);
    }
}