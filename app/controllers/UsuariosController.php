<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/TokenActivacion.php';
require_once __DIR__ . '/../utils/MailService.php';

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
            // Nota: $_GET['page'] es la ruta (ej: 'usuarios'); el número de página va en 'p'
            $page   = max(1, intval($_GET['p'] ?? 1));
            $search = $this->sanitizeInput($_GET['search'] ?? '');
            $rol    = $this->sanitizeInput($_GET['rol'] ?? '');
            // Por defecto solo activos; el admin puede ver inactivos con ?activo=0
            $activo = $_GET['activo'] ?? '1';

            $filters = [];
            if (!empty($search)) {
                $filters['search'] = $search;
            }
            if (!empty($rol)) {
                $filters['rol'] = $rol;
            }
            // activo='' → todos; activo=1 → activos; activo=0 → inactivos
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
        if (!$this->hasPermission('usuarios_create')) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['error' => 'No tienes permisos para crear usuarios'], 403);
            } else {
                $this->setFlashMessage('No tienes permisos para crear usuarios', 'error');
                $this->redirect('index.php?page=usuarios');
            }
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }
        
        $result = $this->processUserForm();

        if (isset($result['errors'])) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['errors' => $result['errors']], 400);
            } else {
                $this->setFlashMessage(implode(', ', (array)$result['errors']), 'error');
                $this->redirect('index.php?page=usuarios');
            }
        } else {
            $this->logActivity('usuario_created', 'usuarios', $result['id'] ?? null, $result['data'] ?? []);
            $msg = $result['email_enviado'] ?? false
                ? 'Usuario creado. Se ha enviado un correo de activación.'
                : 'Usuario creado. No se pudo enviar el correo de activación (revisa la configuración SMTP).';
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => true, 'message' => $msg, 'usuario_id' => $result['id']]);
            } else {
                $this->setFlashMessage($msg, $result['email_enviado'] ?? false ? 'success' : 'warning');
                $this->redirect('index.php?page=usuarios');
            }
        }
    }
    
    /**
     * Editar usuario existente
     */
    public function edit() {
        if (!$this->hasPermission('usuarios_update')) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['error' => 'No tienes permisos para editar usuarios'], 403);
            } else {
                $this->setFlashMessage('No tienes permisos para editar usuarios', 'error');
                $this->redirect('index.php?page=usuarios');
            }
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
        
        // No permitir desactivar su propio usuario
        if ($id == $this->currentUser['id'] && isset($_POST['activo']) && !$_POST['activo']) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['error' => 'No puedes desactivar tu propio usuario'], 400);
            } else {
                $this->setFlashMessage('No puedes desactivar tu propio usuario', 'error');
                $this->redirect('index.php?page=usuarios');
            }
            return;
        }
        
        $result = $this->processUserForm($id);

        if (isset($result['errors'])) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['errors' => $result['errors']], 400);
            } else {
                $this->setFlashMessage(implode(', ', (array)$result['errors']), 'error');
                $this->redirect('index.php?page=usuarios');
            }
        } else {
            $this->logActivity('usuario_updated', 'usuarios', $id, $result['data'] ?? []);
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => true, 'message' => 'Usuario actualizado correctamente']);
            } else {
                $this->setFlashMessage('Usuario actualizado correctamente', 'success');
                $this->redirect('index.php?page=usuarios');
            }
        }
    }
    
    /**
     * Eliminar usuario
     */
    public function delete() {
        if (!$this->hasPermission('usuarios_delete')) {
            $this->jsonResponse(['error' => 'No tienes permisos para eliminar usuarios'], 403);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }

        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['error' => 'Token de seguridad inválido'], 403);
            } else {
                $this->setFlashMessage('Token de seguridad inválido', 'error');
                $this->redirect('index.php?page=usuarios');
            }
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->setFlashMessage('ID de usuario no válido', 'error');
            $this->redirect('index.php?page=usuarios');
            return;
        }

        if ($id == $this->currentUser['id']) {
            $this->setFlashMessage('No puedes eliminar tu propio usuario', 'error');
            $this->redirect('index.php?page=usuarios');
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
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['errors' => $result['errors']], 400);
                } else {
                    $this->setFlashMessage(implode(', ', (array)$result['errors']), 'error');
                    $this->redirect('index.php?page=usuarios');
                }
            } else {
                $this->logActivity('usuario_deleted', 'usuarios', $id, ['usuario_eliminado' => $usuario['username']]);
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['success' => true, 'message' => 'Usuario eliminado correctamente']);
                } else {
                    $this->setFlashMessage('Usuario eliminado correctamente', 'success');
                    $this->redirect('index.php?page=usuarios');
                }
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

        $esNuevo = ($id === 0);

        // Sanitizar campos
        $data = [
            'nombre' => $this->sanitizeInput($_POST['nombre'] ?? ''),
            'email'  => $this->sanitizeInput($_POST['email'] ?? ''),
            'rol'    => $this->sanitizeInput($_POST['rol'] ?? ''),
            'activo' => intval($_POST['activo'] ?? 1),
        ];
        $username = $this->sanitizeInput($_POST['username'] ?? '');
        if ($username !== '') {
            $data['username'] = $username;
        }

        // Para edición: contraseña es opcional
        if (!$esNuevo) {
            $password = $_POST['password'] ?? '';
            if (!empty($password)) {
                $data['password'] = $password;
            }
        }

        $errors = [];

        // Nombre siempre obligatorio
        if (empty($data['nombre'])) {
            $errors[] = 'El nombre es obligatorio';
        } elseif (strlen($data['nombre']) > 100) {
            $errors[] = 'El nombre no puede tener más de 100 caracteres';
        }

        // Email obligatorio para nuevos usuarios (se usa para el enlace de activación)
        if ($esNuevo && empty($data['email'])) {
            $errors[] = 'El correo electrónico es obligatorio para crear un usuario';
        }
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo electrónico no tiene un formato válido';
        }

        if (empty($data['rol'])) {
            $errors[] = 'El rol es obligatorio';
        }

        // Para edición: validar contraseña si fue provista
        if (!$esNuevo && isset($data['password']) && strlen($data['password']) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres';
        }

        // Validar rol permitido según quien crea/edita (mínimo privilegio)
        if (!empty($data['rol'])) {
            $actorRol = $this->currentUser['rol'] ?? '';
            $rolesPermitidos = $this->usuarioModel->getRolesPermitidosParaCrear($actorRol);
            if (!array_key_exists($data['rol'], $rolesPermitidos)) {
                $errors[] = 'No tienes permisos para asignar el rol "' . htmlspecialchars($data['rol']) . '"';
            }
        }

        // Verificar unicidad del username si fue enviado
        if (empty($errors) && !empty($data['username'])) {
            $existingUser = $this->usuarioModel->findByUsername($data['username']);
            if ($existingUser && ($esNuevo || $existingUser['id'] != $id)) {
                $errors[] = 'Ya existe un usuario con ese nombre de usuario';
            }
        }

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        try {
            if (!$esNuevo) {
                // Edición: flujo existente
                $result = $this->usuarioModel->update($id, $data);
                return isset($result['errors']) ? $result : ['success' => true, 'data' => $data];
            }

            // Creación: pre-registro con activación por correo
            $newId = $this->usuarioModel->createPendiente($data);
            if (is_array($newId)) {
                // createPendiente devuelve array con 'errors' si falla
                return $newId;
            }

            // Generar token y enviar correo
            $tokenModel  = new TokenActivacion();
            $tokenReal   = $tokenModel->generarToken($newId, TokenActivacion::TIPO_ACTIVACION);

            $mailService  = new MailService();
            $emailEnviado = $mailService->enviarActivacion($data['email'], $data['nombre'], $tokenReal);

            if (!$emailEnviado) {
                error_log("UsuariosController: no se pudo enviar correo de activación a {$data['email']} (usuario ID {$newId})");
            }

            return [
                'success'       => true,
                'id'            => $newId,
                'data'          => $data,
                'email_enviado' => $emailEnviado,
            ];

        } catch (Exception $e) {
            error_log('processUserForm error: ' . $e->getMessage());
            return ['errors' => ['Error al procesar el usuario. Intente nuevamente.']];
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
        $this->redirect('index.php?page=dashboard');
    }
    
    /**
     * Manejar errores
     */
    protected function handleUsuariosError($exception, $userMessage = 'Ha ocurrido un error') {
        error_log('UsuariosController: ' . $exception->getMessage());
        if ($this->isAjaxRequest()) {
            $this->jsonResponse(['error' => $userMessage], 500);
        } else {
            $this->setFlashMessage($userMessage, 'error');
            $this->redirect('index.php?page=dashboard');
        }
    }
}
