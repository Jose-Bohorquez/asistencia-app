<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Usuario.php';

/**
 * Controlador de Usuarios
 * Maneja la gestión de usuarios del sistema
 */
class UsuariosController extends BaseController {
    private $usuarioModel;
    
    public function __construct() {
        parent::__construct();
        
        // Inicializar modelo
        $this->usuarioModel = new Usuario();
    }
    
    /**
     * Método principal para manejar las peticiones
     */
    public function handleRequest() {
        $action = $_GET['action'] ?? 'index';
        
        switch ($action) {
            case 'index':
                $this->index();
                break;
            case 'create':
                $this->create();
                break;
            case 'edit':
                $this->edit();
                break;
            case 'delete':
                $this->delete();
                break;
            case 'toggle-status':
                $this->toggleStatus();
                break;
            case 'export':
                $this->export();
                break;
            default:
                $this->index();
        }
    }
    
    /**
     * Listar usuarios
     */
    public function index() {
        // Verificar permisos - solo super_admin y admin pueden ver usuarios
        if (!$this->hasPermission('usuarios_read')) {
            $this->redirectUnauthorized();
            return;
        }
        
        try {
            // Obtener parámetros de filtrado y paginación
            $page = intval($_GET['page'] ?? 1);
            $search = $this->sanitizeInput($_GET['search'] ?? '');
            $rol = $this->sanitizeInput($_GET['rol'] ?? '');
            $activo = $_GET['activo'] ?? '';
            
            // Construir filtros
            $filters = [];
            if (!empty($search)) {
                $filters['search'] = $search;
            }
            if (!empty($rol)) {
                $filters['rol'] = $rol;
            }
            if ($activo !== '') {
                $filters['activo'] = intval($activo);
            }
            
            // Obtener usuarios paginados
            $result = $this->usuarioModel->getPaginated($page, 10, $filters);
            $roles = $this->usuarioModel->getRoles();
            
            $this->render('admin/usuarios', [
                'page_title' => 'Gestión de Usuarios',
                'usuarios' => $result['data'],
                'pagination' => $result['pagination'],
                'roles' => $roles,
                'filters' => $filters,
                'can_create' => $this->hasPermission('usuarios_create'),
                'can_edit' => $this->hasPermission('usuarios_update'),
                'can_delete' => $this->hasPermission('usuarios_delete')
            ]);
            
        } catch (Exception $e) {
            $this->handleUsuariosError($e, 'Error al cargar los usuarios');
        }
    }
    
    /**
     * Crear nuevo usuario
     */
    public function create() {
        // Verificar permisos - solo super_admin puede crear usuarios
        if (!$this->hasPermission('usuarios_create')) {
            $this->jsonResponse(['error' => 'No tienes permisos para crear usuarios'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }
        
        $result = $this->processUserForm();
        
        if (isset($result['errors'])) {
            $this->jsonResponse(['errors' => $result['errors']], 400);
        } else {
            $this->logActivity('usuario_created', $result['id'], null, $result['data']);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Usuario creado correctamente',
                'usuario_id' => $result['id']
            ]);
        }
    }
    
    /**
     * Editar usuario existente
     */
    public function edit() {
        // Verificar permisos - solo super_admin puede editar usuarios
        if (!$this->hasPermission('usuarios_update')) {
            $this->jsonResponse(['error' => 'No tienes permisos para editar usuarios'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->setFlashMessage('ID de usuario no válido', 'error');
            $this->redirect('index.php?page=usuarios');
            return;
        }
        
        // Verificar que el usuario existe
        $usuario = $this->usuarioModel->find($id);
        if (!$usuario) {
            $this->setFlashMessage('Usuario no encontrado', 'error');
            $this->redirect('index.php?page=usuarios');
            return;
        }
        
        // No permitir que se edite a sí mismo en ciertos casos
        if ($id == $this->currentUser['id'] && isset($_POST['activo']) && !$_POST['activo']) {
            $this->jsonResponse(['error' => 'No puedes desactivar tu propio usuario'], 400);
            return;
        }
        
        $result = $this->processUserForm($id);
        
        if (isset($result['errors'])) {
            $this->jsonResponse(['errors' => $result['errors']], 400);
        } else {
            $this->logActivity('usuario_updated', $id, null, $result['data']);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Usuario actualizado correctamente'
            ]);
        }
    }
    
    /**
     * Eliminar usuario
     */
    public function delete() {
        // Verificar permisos - solo super_admin puede eliminar usuarios
        if (!$this->hasPermission('usuarios_delete')) {
            $this->jsonResponse(['error' => 'No tienes permisos para eliminar usuarios'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonResponse(['error' => 'ID de usuario no válido'], 400);
            return;
        }
        
        // No permitir que se elimine a sí mismo
        if ($id == $this->currentUser['id']) {
            $this->jsonResponse(['error' => 'No puedes eliminar tu propio usuario'], 400);
            return;
        }
        
        // Verificar que el usuario existe
        $usuario = $this->usuarioModel->find($id);
        if (!$usuario) {
            $this->jsonResponse(['error' => 'Usuario no encontrado'], 404);
            return;
        }
        
        try {
            // Verificar si el usuario tiene datos asociados
            $cursosAsociados = $this->usuarioModel->countAssociatedCourses($id);
            
            if ($cursosAsociados > 0) {
                $this->jsonResponse([
                    'error' => 'No se puede eliminar el usuario porque tiene ' . $cursosAsociados . ' curso(s) asociado(s)'
                ], 400);
                return;
            }
            
            // Eliminar usuario
            $result = $this->usuarioModel->delete($id);
            
            if (isset($result['errors'])) {
                $this->jsonResponse(['errors' => $result['errors']], 400);
            } else {
                $this->logActivity('usuario_deleted', $id, null, ['usuario_eliminado' => $usuario['username']]);
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Usuario eliminado correctamente'
                ]);
            }
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Error al eliminar el usuario'], 500);
        }
    }
    
    /**
     * Cambiar estado activo/inactivo del usuario
     */
    public function toggleStatus() {
        // Verificar permisos
        if (!$this->hasPermission('usuarios_update')) {
            $this->jsonResponse(['error' => 'No tienes permisos para modificar usuarios'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonResponse(['error' => 'ID de usuario no válido'], 400);
            return;
        }
        
        // No permitir que se desactive a sí mismo
        if ($id == $this->currentUser['id']) {
            $this->jsonResponse(['error' => 'No puedes cambiar el estado de tu propio usuario'], 400);
            return;
        }
        
        // Verificar que el usuario existe
        $usuario = $this->usuarioModel->find($id);
        if (!$usuario) {
            $this->jsonResponse(['error' => 'Usuario no encontrado'], 404);
            return;
        }
        
        try {
            $nuevoEstado = $usuario['activo'] ? 0 : 1;
            $result = $this->usuarioModel->update($id, ['activo' => $nuevoEstado]);
            
            if (isset($result['errors'])) {
                $this->jsonResponse(['errors' => $result['errors']], 400);
            } else {
                $this->logActivity('usuario_status_changed', $id, null, [
                    'estado_anterior' => $usuario['activo'],
                    'estado_nuevo' => $nuevoEstado
                ]);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Estado del usuario actualizado correctamente',
                    'nuevo_estado' => $nuevoEstado
                ]);
            }
            
        } catch (Exception $e) {
            $this->handleUsuariosError($e, 'Error al cambiar el estado del usuario');
        }
    }
    
    /**
     * Exportar usuarios
     */
    public function export() {
        // Verificar permisos
        if (!$this->hasPermission('reportes_export')) {
            $this->jsonResponse(['error' => 'No tienes permisos para exportar'], 403);
            return;
        }
        
        $formato = $_GET['formato'] ?? 'excel';
        
        try {
            $usuarios = $this->usuarioModel->exportar();
            
            if ($formato === 'excel') {
                $this->exportToExcel($usuarios, 'usuarios');
            } else {
                $this->exportToPDF($usuarios, 'usuarios');
            }
            
        } catch (Exception $e) {
            $this->handleUsuariosError($e, 'Error al exportar usuarios');
        }
    }
    
    /**
     * Procesar formulario de usuario (crear/editar)
     */
    private function processUserForm($id = 0) {
        // Verificar token CSRF
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            return ['errors' => ['Token de seguridad inválido']];
        }
        
        // Validar y sanitizar datos
        $data = [
            'username' => $this->sanitizeInput($_POST['username'] ?? ''),
            'nombre' => $this->sanitizeInput($_POST['nombre'] ?? ''),
            'email' => $this->sanitizeInput($_POST['email'] ?? ''),
            'rol' => $this->sanitizeInput($_POST['rol'] ?? ''),
            'activo' => intval($_POST['activo'] ?? 1)
        ];
        
        // Validar password solo si se proporciona
        $password = $_POST['password'] ?? '';
        if (!empty($password)) {
            $data['password'] = $password;
        }
        
        // Validar datos
        $errors = [];
        
        if (empty($data['username'])) {
            $errors[] = 'El nombre de usuario es obligatorio';
        } elseif (strlen($data['username']) > 50) {
            $errors[] = 'El nombre de usuario no puede tener más de 50 caracteres';
        }
        
        if (empty($data['nombre'])) {
            $errors[] = 'El nombre es obligatorio';
        } elseif (strlen($data['nombre']) > 100) {
            $errors[] = 'El nombre no puede tener más de 100 caracteres';
        }
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El email no es válido';
        }
        
        if (empty($data['rol'])) {
            $errors[] = 'El rol es obligatorio';
        }
        
        // Validar password para nuevos usuarios
        if ($id === 0 && empty($password)) {
            $errors[] = 'La contraseña es obligatoria para nuevos usuarios';
        }
        
        if (!empty($password) && strlen($password) < 6) {
            $errors[] = 'La contraseña debe tener al menos 6 caracteres';
        }
        
        // Verificar unicidad del username
        if (empty($errors)) {
            $existingUser = $this->usuarioModel->findByUsername($data['username']);
            if ($existingUser && ($id === 0 || $existingUser['id'] != $id)) {
                $errors[] = 'Ya existe un usuario con ese nombre de usuario';
            }
        }
        
        if (!empty($errors)) {
            return ['errors' => $errors];
        }
        
        // Crear o actualizar usuario
        try {
            if ($id > 0) {
                $result = $this->usuarioModel->update($id, $data);
                return isset($result['errors']) ? $result : ['success' => true, 'data' => $data];
            } else {
                $result = $this->usuarioModel->create($data);
                return isset($result['errors']) ? $result : ['success' => true, 'id' => $result, 'data' => $data];
            }
        } catch (Exception $e) {
            return ['errors' => ['Error al procesar el usuario: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Exportar a Excel
     */
    private function exportToExcel($datos, $tipo) {
        require_once __DIR__ . '/../utils/ExportHelper.php';
        
        $prepared = ExportHelper::prepareUsuariosData($datos);
        ExportHelper::exportToExcel(
            $prepared['datos'], 
            'usuarios', 
            'Reporte de Usuarios - ' . date('d/m/Y'), 
            $prepared['encabezados']
        );
    }
    
    /**
     * Exportar a PDF
     */
    private function exportToPDF($datos, $tipo) {
        require_once __DIR__ . '/../utils/ExportHelper.php';
        
        $prepared = ExportHelper::prepareUsuariosData($datos);
        ExportHelper::exportToPDF(
            $prepared['datos'], 
            'usuarios', 
            'Reporte de Usuarios - ' . date('d/m/Y'), 
            $prepared['encabezados']
        );
    }
    
    /**
     * Exportar a CSV (temporal)
     */
    private function exportToCSV($datos, $tipo) {
        if (empty($datos)) {
            throw new Exception('No hay datos para exportar');
        }
        
        $output = fopen('php://output', 'w');
        
        // Escribir encabezados
        fputcsv($output, array_keys($datos[0]));
        
        // Escribir datos
        foreach ($datos as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
    }
    
    /**
     * Verificar si el usuario tiene un permiso específico
     */
    protected function hasPermission($permission) {
        return $this->middlewareManager->checkPermission($permission);
    }
    
    /**
     * Redirigir cuando no se tienen permisos
     */
    private function redirectUnauthorized() {
        $this->setFlashMessage('No tienes permisos para acceder a esta sección', 'error');
        $this->redirect('index.php?page=login&error=permissions');
    }
    
    /**
     * Manejar errores
     */
    protected function handleUsuariosError($exception, $userMessage = 'Ha ocurrido un error') {
        // Log del error
        error_log($exception->getMessage());
        
        if ($this->isAjaxRequest()) {
            $this->jsonResponse(['error' => $userMessage], 500);
        } else {
            $this->setFlashMessage($userMessage, 'error');
            $this->redirect('index.php?page=usuarios');
        }
    }
}
?>