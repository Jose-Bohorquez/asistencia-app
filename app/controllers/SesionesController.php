
<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Sesion.php';

/**
 * Controlador de Sesiones
 * Maneja la gestión de sesiones de cursos
 */
class SesionesController extends BaseController {
    private $sesionModel;
    
    public function __construct() {
        parent::__construct();
        
        // Inicializar modelo
        $this->sesionModel = new Sesion();
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
            case 'activate':
                $this->activate();
                break;
            case 'deactivate':
                $this->deactivate();
                break;
            case 'export':
                $this->export();
                break;
            default:
                $this->index();
        }
    }
    
    /**
     * Listar sesiones
     */
    public function index() {
        // Verificar permisos - solo super_admin, admin y profesor pueden gestionar sesiones
        if (!$this->hasPermission('sesiones_read')) {
            $this->redirectUnauthorized();
            return;
        }
        
        try {
            // Obtener parámetros de filtrado y paginación
            $page = intval($_GET['page'] ?? 1);
            $search = $this->sanitizeInput($_GET['search'] ?? '');
            $curso_id = intval($_GET['curso_id'] ?? 0);
            $estado = $this->sanitizeInput($_GET['estado'] ?? '');
            $fecha_desde = $_GET['fecha_desde'] ?? '';
            $fecha_hasta = $_GET['fecha_hasta'] ?? '';
            
            // Construir filtros
            $filters = [];
            if (!empty($search)) {
                $filters['search'] = $search;
            }
            if ($curso_id > 0) {
                $filters['curso_id'] = $curso_id;
            }
            if (!empty($estado)) {
                $filters['estado'] = $estado;
            }
            if (!empty($fecha_desde)) {
                $filters['fecha_desde'] = $fecha_desde;
            }
            if (!empty($fecha_hasta)) {
                $filters['fecha_hasta'] = $fecha_hasta;
            }
            
            // Aplicar filtros por rol
            if ($this->currentUser['rol'] === 'profesor') {
                $filters['profesor_id'] = $this->currentUser['id'];
            }
            
            // Obtener sesiones paginadas
            $result = $this->sesionModel->getPaginated($page, 10, $filters);
            $cursos = $this->sesionModel->getCursosForUser($this->currentUser['id'], $this->currentUser['rol']);
            
            $this->render('admin/sesiones', [
                'page_title' => 'Gestión de Sesiones',
                'sesiones' => $result['data'],
                'pagination' => $result['pagination'],
                'cursos' => $cursos,
                'filters' => $filters,
                'can_create' => $this->hasPermission('sesiones_create'),
                'can_edit' => $this->hasPermission('sesiones_update'),
                'can_delete' => $this->hasPermission('sesiones_delete'),
                'can_activate' => $this->hasPermission('sesiones_activate')
            ]);
            
        } catch (Exception $e) {
            $this->handleSesionesError($e, 'Error al cargar las sesiones');
        }
    }
    
    /**
     * Crear nueva sesión
     */
    public function create() {
        // Verificar permisos
        if (!$this->hasPermission('sesiones_create')) {
            $this->jsonResponse(['error' => 'No tienes permisos para crear sesiones'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }
        
        $result = $this->processSessionForm();
        
        if (isset($result['errors'])) {
            $this->jsonResponse(['errors' => $result['errors']], 400);
        } else {
            $this->logActivity('sesion_created', $result['id'], null, $result['data']);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Sesión creada correctamente',
                'sesion_id' => $result['id']
            ]);
        }
    }
    
    /**
     * Editar sesión existente
     */
    public function edit() {
        // Verificar permisos
        if (!$this->hasPermission('sesiones_update')) {
            $this->jsonResponse(['error' => 'No tienes permisos para editar sesiones'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->setFlashMessage('ID de sesión no válido', 'error');
            $this->redirect('index.php?page=sesiones');
            return;
        }
        
        // Verificar que la sesión existe
        $sesion = $this->sesionModel->find($id);
        if (!$sesion) {
            $this->setFlashMessage('Sesión no encontrada', 'error');
            $this->redirect('index.php?page=sesiones');
            return;
        }
        
        // Verificar permisos específicos para profesores
        if ($this->currentUser['rol'] === 'profesor') {
            if (!$this->sesionModel->canUserManageSession($this->currentUser['id'], $id)) {
                $this->jsonResponse(['error' => 'No tienes permisos para editar esta sesión'], 403);
                return;
            }
        }
        
        $result = $this->processSessionForm($id);
        
        if (isset($result['errors'])) {
            $this->jsonResponse(['errors' => $result['errors']], 400);
        } else {
            $this->logActivity('sesion_updated', $id, null, $result['data']);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Sesión actualizada correctamente'
            ]);
        }
    }
    
    /**
     * Eliminar sesión
     */
    public function delete() {
        // Verificar permisos - solo super_admin y admin pueden eliminar sesiones
        if (!$this->hasPermission('sesiones_delete')) {
            $this->jsonResponse(['error' => 'No tienes permisos para eliminar sesiones'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonResponse(['error' => 'ID de sesión no válido'], 400);
            return;
        }
        
        // Verificar que la sesión existe
        $sesion = $this->sesionModel->find($id);
        if (!$sesion) {
            $this->jsonResponse(['error' => 'Sesión no encontrada'], 404);
            return;
        }
        
        try {
            // Verificar si la sesión tiene asistencias registradas
            $asistenciasCount = $this->sesionModel->countAttendances($id);
            
            if ($asistenciasCount > 0) {
                $this->jsonResponse([
                    'error' => 'No se puede eliminar la sesión porque tiene ' . $asistenciasCount . ' asistencia(s) registrada(s)'
                ], 400);
                return;
            }
            
            // Eliminar sesión
            $result = $this->sesionModel->delete($id);
            
            if (isset($result['errors'])) {
                $this->jsonResponse(['errors' => $result['errors']], 400);
            } else {
                $this->logActivity('sesion_deleted', $id, null, ['sesion_eliminada' => $sesion['fecha'] . ' - ' . $sesion['hora_inicio']]);
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Sesión eliminada correctamente'
                ]);
            }
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Error al eliminar la sesión'], 500);
        }
    }
    
    /**
     * Activar sesión
     */
    public function activate() {
        // Verificar permisos
        if (!$this->hasPermission('sesiones_activate')) {
            $this->jsonResponse(['error' => 'No tienes permisos para activar sesiones'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonResponse(['error' => 'ID de sesión no válido'], 400);
            return;
        }
        
        try {
            $result = $this->sesionModel->updateStatus($id, 'activa');
            
            if (isset($result['errors'])) {
                $this->jsonResponse(['errors' => $result['errors']], 400);
            } else {
                $this->logActivity('sesion_activated', $id);
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Sesión activada correctamente'
                ]);
            }
            
        } catch (Exception $e) {
            $this->handleSesionesError($e, 'Error al activar la sesión');
        }
    }
    
    /**
     * Desactivar/Finalizar sesión
     */
    public function deactivate() {
        // Verificar permisos
        if (!$this->hasPermission('sesiones_activate')) {
            $this->jsonResponse(['error' => 'No tienes permisos para finalizar sesiones'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonResponse(['error' => 'ID de sesión no válido'], 400);
            return;
        }
        
        try {
            $result = $this->sesionModel->updateStatus($id, 'finalizada');
            
            if (isset($result['errors'])) {
                $this->jsonResponse(['errors' => $result['errors']], 400);
            } else {
                $this->logActivity('sesion_deactivated', $id);
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Sesión finalizada correctamente'
                ]);
            }
            
        } catch (Exception $e) {
            $this->handleSesionesError($e, 'Error al finalizar la sesión');
        }
    }
    
    /**
     * Exportar sesiones
     */
    public function export() {
        // Verificar permisos
        if (!$this->hasPermission('reportes_export')) {
            $this->jsonResponse(['error' => 'No tienes permisos para exportar'], 403);
            return;
        }
        
        $formato = $_GET['formato'] ?? 'excel';
        
        try {
            // Aplicar filtros por rol
            $filters = [];
            if ($this->currentUser['rol'] === 'profesor') {
                $filters['profesor_id'] = $this->currentUser['id'];
            }
            
            $sesiones = $this->sesionModel->exportar($filters);
            
            if ($formato === 'excel') {
                $this->exportToExcel($sesiones, 'sesiones');
            } else {
                $this->exportToPDF($sesiones, 'sesiones');
            }
            
        } catch (Exception $e) {
            $this->handleSesionesError($e, 'Error al exportar sesiones');
        }
    }
    
    /**
     * Procesar formulario de sesión (crear/editar)
     */
    private function processSessionForm($id = 0) {
        // Verificar token CSRF
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            return ['errors' => ['Token de seguridad inválido']];
        }
        
        // Validar y sanitizar datos
        $data = [
            'curso_id' => intval($_POST['curso_id'] ?? 0),
            'fecha' => $this->sanitizeInput($_POST['fecha'] ?? ''),
            'hora_inicio' => $this->sanitizeInput($_POST['hora_inicio'] ?? ''),
            'hora_fin' => $this->sanitizeInput($_POST['hora_fin'] ?? ''),
            'estado' => $this->sanitizeInput($_POST['estado'] ?? 'programada')
        ];
        
        // Validar datos
        $errors = [];
        
        if ($data['curso_id'] <= 0) {
            $errors[] = 'Debe seleccionar un curso válido';
        }
        
        if (empty($data['fecha'])) {
            $errors[] = 'La fecha es obligatoria';
        } elseif (!$this->isValidDate($data['fecha'])) {
            $errors[] = 'La fecha no es válida';
        }
        
        if (empty($data['hora_inicio'])) {
            $errors[] = 'La hora de inicio es obligatoria';
        } elseif (!$this->isValidTime($data['hora_inicio'])) {
            $errors[] = 'La hora de inicio no es válida';
        }
        
        if (!empty($data['hora_fin']) && !$this->isValidTime($data['hora_fin'])) {
            $errors[] = 'La hora de fin no es válida';
        }
        
        if (!empty($data['hora_fin']) && $data['hora_fin'] <= $data['hora_inicio']) {
            $errors[] = 'La hora de fin debe ser posterior a la hora de inicio';
        }
        
        // Verificar permisos para el curso si es profesor
        if ($this->currentUser['rol'] === 'profesor' && empty($errors)) {
            if (!$this->sesionModel->canUserManageCourse($this->currentUser['id'], $data['curso_id'])) {
                $errors[] = 'No tienes permisos para crear sesiones en este curso';
            }
        }
        
        if (!empty($errors)) {
            return ['errors' => $errors];
        }
        
        // Generar token para nueva sesión
        if ($id === 0) {
            $data['token'] = bin2hex(random_bytes(16));
        }
        
        // Crear o actualizar sesión
        try {
            if ($id > 0) {
                $result = $this->sesionModel->update($id, $data);
                return isset($result['errors']) ? $result : ['success' => true, 'data' => $data];
            } else {
                $result = $this->sesionModel->create($data);
                return isset($result['errors']) ? $result : ['success' => true, 'id' => $result, 'data' => $data];
            }
        } catch (Exception $e) {
            return ['errors' => ['Error al procesar la sesión: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Validar formato de fecha
     */
    private function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Validar formato de hora
     */
    private function isValidTime($time) {
        $t = DateTime::createFromFormat('H:i', $time);
        return $t && $t->format('H:i') === $time;
    }
    
    /**
     * Exportar a Excel
     */
    private function exportToExcel($datos, $tipo) {
        require_once __DIR__ . '/../utils/ExportHelper.php';
        
        if ($tipo === 'sesiones') {
            $prepared = ExportHelper::prepareSesionesData($datos);
            ExportHelper::exportToExcel(
                $prepared['datos'], 
                'sesiones', 
                'Reporte de Sesiones - ' . date('d/m/Y'), 
                $prepared['encabezados']
            );
        } else {
            $prepared = ExportHelper::prepareAsistenciaData($datos);
            ExportHelper::exportToExcel(
                $prepared['datos'], 
                'asistencia', 
                'Reporte de Asistencia - ' . date('d/m/Y'), 
                $prepared['encabezados']
            );
        }
    }
    
    /**
     * Exportar a PDF
     */
    private function exportToPDF($datos, $tipo) {
        require_once __DIR__ . '/../utils/ExportHelper.php';
        
        if ($tipo === 'sesiones') {
            $prepared = ExportHelper::prepareSesionesData($datos);
            ExportHelper::exportToPDF(
                $prepared['datos'], 
                'sesiones', 
                'Reporte de Sesiones - ' . date('d/m/Y'), 
                $prepared['encabezados']
            );
        } else {
            $prepared = ExportHelper::prepareAsistenciaData($datos);
            ExportHelper::exportToPDF(
                $prepared['datos'], 
                'asistencia', 
                'Reporte de Asistencia - ' . date('d/m/Y'), 
                $prepared['encabezados']
            );
        }
    }
    
    /**
     * Exportar a CSV
     */
    private function exportToCSV($datos, $tipo) {
        require_once __DIR__ . '/../utils/ExportHelper.php';
        
        if ($tipo === 'sesiones') {
            $prepared = ExportHelper::prepareSesionesData($datos);
            ExportHelper::exportToCSV(
                $prepared['datos'], 
                'sesiones', 
                $prepared['encabezados']
            );
        } else {
            $prepared = ExportHelper::prepareAsistenciaData($datos);
            ExportHelper::exportToCSV(
                $prepared['datos'], 
                'asistencia', 
                $prepared['encabezados']
            );
        }
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
    protected function handleSesionesError($exception, $userMessage = 'Ha ocurrido un error') {
        // Log del error
        error_log($exception->getMessage());
        
        if ($this->isAjaxRequest()) {
            $this->jsonResponse(['error' => $userMessage], 500);
        } else {
            $this->setFlashMessage($userMessage, 'error');
            $this->redirect('index.php?page=sesiones');
        }
    }
}
?>