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
            case 'imprimir':
                $this->imprimir();
                break;
            case 'detalle':
                $this->detalle();
                break;
            case 'asistencia_json':
                $this->asistenciaJson();
                break;
            default:
                $this->index();
        }
    }
    
    /**
     * Verifica que el usuario actual puede operar sobre una sesión específica.
     * Admins y super_admins acceden a cualquier sesión.
     * Profesores solo pueden acceder a sus propias sesiones (via cursos.profesor_id).
     */
    private function canAccessSession(int $sesionId): bool {
        if (in_array($this->currentUser['rol'], ['super_admin', 'admin'])) {
            return true;
        }
        if ($this->currentUser['rol'] !== 'profesor') {
            return false;
        }
        $conn = $this->db;
        $stmt = $conn->prepare(
            "SELECT s.id FROM sesiones s
             INNER JOIN cursos c ON s.curso_id = c.id
             WHERE s.id = ? AND c.profesor_id = ?
             LIMIT 1"
        );
        $stmt->bind_param('ii', $sesionId, $this->currentUser['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $ok = $result->num_rows > 0;
        $stmt->close();
        return $ok;
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
            // Nota: $_GET['page'] es la ruta; el número de página va en 'p'
            $page = max(1, intval($_GET['p'] ?? 1));
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
            
        } catch (\Throwable $e) {
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
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['errors' => $result['errors']], 400);
            } else {
                $this->setFlashMessage(implode(', ', (array)$result['errors']), 'error');
                $this->redirect('index.php?page=sesiones');
            }
        } else {
            $this->logActivity('sesion_created', 'sesiones', $result['id'] ?? null, $result['data'] ?? []);
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => true, 'message' => 'Sesión creada correctamente', 'sesion_id' => $result['id']]);
            } else {
                $this->setFlashMessage('Sesión creada correctamente', 'success');
                $this->redirect('index.php?page=sesiones');
            }
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
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['errors' => $result['errors']], 400);
            } else {
                $this->setFlashMessage(implode(', ', (array)$result['errors']), 'error');
                $this->redirect('index.php?page=sesiones');
            }
        } else {
            $this->logActivity('sesion_updated', 'sesiones', $id, $result['data'] ?? []);
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => true, 'message' => 'Sesión actualizada correctamente']);
            } else {
                $this->setFlashMessage('Sesión actualizada correctamente', 'success');
                $this->redirect('index.php?page=sesiones');
            }
        }
    }
    
    /**
     * Eliminar sesión
     */
    public function delete() {
        if (!$this->hasPermission('sesiones_delete')) {
            $this->jsonResponse(['error' => 'No tienes permisos para eliminar sesiones'], 403);
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
                $this->redirect('index.php?page=sesiones');
            }
            return;
        }

        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonResponse(['error' => 'ID de sesión no válido'], 400);
            return;
        }

        // Motivo de eliminación obligatorio (mínimo 10, máximo 500 caracteres)
        $motivo = trim($_POST['motivo'] ?? '');
        if (strlen($motivo) < 10) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['error' => 'Debe indicar el motivo de la eliminación (mínimo 10 caracteres).'], 400);
            } else {
                $this->setFlashMessage('Debe indicar el motivo de la eliminación (mínimo 10 caracteres).', 'error');
                $this->redirect('index.php?page=sesiones');
            }
            return;
        }
        if (strlen($motivo) > 500) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['error' => 'El motivo no puede superar los 500 caracteres.'], 400);
            } else {
                $this->setFlashMessage('El motivo no puede superar los 500 caracteres.', 'error');
                $this->redirect('index.php?page=sesiones');
            }
            return;
        }

        // Verificar que la sesión existe
        $sesion = $this->sesionModel->find($id);
        if (!$sesion) {
            $this->jsonResponse(['error' => 'Sesión no encontrada'], 404);
            return;
        }

        // Verificar que el profesor solo elimine sus propias sesiones
        if (!$this->canAccessSession($id)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['error' => 'No tienes permisos para acceder a esta sesión'], 403);
            } else {
                $this->setFlashMessage('No tienes permisos para acceder a esta sesión', 'error');
                $this->redirect('index.php?page=sesiones');
            }
            return;
        }

        $forceDelete = ($_POST['force_delete'] ?? '0') === '1';

        try {
            // Verificar si la sesión tiene asistencias registradas
            $asistenciasCount = $this->sesionModel->countAttendances($id);

            if ($asistenciasCount > 0 && !$forceDelete) {
                $msg = 'La sesión tiene ' . $asistenciasCount . ' registro(s) de asistencia. Confirma nuevamente desde la interfaz para eliminarla.';
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['error' => $msg, 'has_attendances' => true, 'count' => $asistenciasCount], 409);
                } else {
                    $this->setFlashMessage($msg, 'error');
                    $this->redirect('index.php?page=sesiones');
                }
                return;
            }

            // Eliminar sesión (con registro de asistencias si forzado)
            $result = $this->sesionModel->delete($id);

            if (isset($result['errors'])) {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['errors' => $result['errors']], 400);
                } else {
                    $this->setFlashMessage(implode(', ', (array)$result['errors']), 'error');
                    $this->redirect('index.php?page=sesiones');
                }
            } else {
                $logData = [
                    'sesion_eliminada'    => $sesion['fecha'] . ' - ' . $sesion['hora_inicio'],
                    'motivo'             => $motivo,
                    'eliminado_por'      => $this->currentUser['id'] ?? null,
                ];
                if ($forceDelete && $asistenciasCount > 0) {
                    $logData['asistencias_eliminadas'] = $asistenciasCount;
                    $logData['eliminacion_forzada'] = true;
                }
                $this->logActivity('sesion_deleted', 'sesiones', $id, $logData);
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['success' => true, 'message' => 'Sesión eliminada correctamente']);
                } else {
                    $this->setFlashMessage('Sesión eliminada correctamente', 'success');
                    $this->redirect('index.php?page=sesiones');
                }
            }
            
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => 'Error al eliminar la sesión'], 500);
        }
    }
    
    /**
     * Activar sesión
     */
    public function activate() {
        // Verificar permisos
        if (!$this->hasPermission('sesiones_activate')) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['error' => 'No tienes permisos para activar sesiones'], 403);
            } else {
                $this->setFlashMessage('No tienes permisos para activar sesiones', 'error');
                $this->redirect('index.php?page=sesiones');
            }
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

        // Verificar que el profesor solo active sus propias sesiones
        if (!$this->canAccessSession($id)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['error' => 'No tienes permisos para acceder a esta sesión'], 403);
            } else {
                $this->setFlashMessage('No tienes permisos para acceder a esta sesión', 'error');
                $this->redirect('index.php?page=sesiones');
            }
            return;
        }

        try {
            $result = $this->sesionModel->updateStatus($id, 'activa');
            if (is_array($result) && isset($result['errors'])) {
                $msg = implode(', ', (array)$result['errors']);
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['errors' => $result['errors']], 400);
                } else {
                    $this->setFlashMessage($msg, 'error');
                    $this->redirect('index.php?page=sesiones');
                }
            } elseif ($result === false) {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['error' => 'No se pudo activar la sesión'], 500);
                } else {
                    $this->setFlashMessage('No se pudo activar la sesión. Intenta de nuevo.', 'error');
                    $this->redirect('index.php?page=sesiones');
                }
            } else {
                $this->logActivity('sesion_activated', 'sesiones', $id);
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['success' => true, 'message' => 'Sesión activada correctamente']);
                } else {
                    $this->setFlashMessage('Sesión activada correctamente', 'success');
                    $this->redirect('index.php?page=sesiones');
                }
            }
        } catch (\Throwable $e) {
            $this->handleSesionesError($e, 'Error al activar la sesión');
        }
    }
    
    /**
     * Desactivar/Finalizar sesión
     */
    public function deactivate() {
        // Verificar permisos
        if (!$this->hasPermission('sesiones_activate')) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['error' => 'No tienes permisos para finalizar sesiones'], 403);
            } else {
                $this->setFlashMessage('No tienes permisos para finalizar sesiones', 'error');
                $this->redirect('index.php?page=sesiones');
            }
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

        // Verificar que el profesor solo finalice sus propias sesiones
        if (!$this->canAccessSession($id)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['error' => 'No tienes permisos para acceder a esta sesión'], 403);
            } else {
                $this->setFlashMessage('No tienes permisos para acceder a esta sesión', 'error');
                $this->redirect('index.php?page=sesiones');
            }
            return;
        }

        try {
            $result = $this->sesionModel->updateStatus($id, 'finalizada');
            if (is_array($result) && isset($result['errors'])) {
                $msg = implode(', ', (array)$result['errors']);
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['errors' => $result['errors']], 400);
                } else {
                    $this->setFlashMessage($msg, 'error');
                    $this->redirect('index.php?page=sesiones');
                }
            } elseif ($result === false) {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['error' => 'No se pudo finalizar la sesión'], 500);
                } else {
                    $this->setFlashMessage('No se pudo finalizar la sesión. Intenta de nuevo.', 'error');
                    $this->redirect('index.php?page=sesiones');
                }
            } else {
                $this->logActivity('sesion_deactivated', 'sesiones', $id);
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['success' => true, 'message' => 'Sesión finalizada correctamente']);
                } else {
                    $this->setFlashMessage('Sesión finalizada correctamente', 'success');
                    $this->redirect('index.php?page=sesiones');
                }
            }
        } catch (\Throwable $e) {
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
            
        } catch (\Throwable $e) {
            $this->handleSesionesError($e, 'Error al exportar sesiones');
        }
    }
    
    /**
     * Vista de impresión de sesión con lista de asistencia y firmas
     */
    public function imprimir() {
        if (!$this->hasPermission('sesiones_read')) {
            $this->redirectUnauthorized();
            return;
        }

        $id = intval($_GET['sesion_id'] ?? 0);
        if ($id <= 0) {
            $this->setFlashMessage('ID de sesión no válido', 'error');
            $this->redirect('index.php?page=sesiones');
            return;
        }

        try {
            $sesion = $this->sesionModel->getWithCursoInfo($id);
            if (!$sesion) {
                $this->setFlashMessage('Sesión no encontrada', 'error');
                $this->redirect('index.php?page=sesiones');
                return;
            }

            // Verificar que el profesor solo imprima sus propias sesiones
            if ($this->currentUser['rol'] === 'profesor') {
                require_once __DIR__ . '/../models/Curso.php';
                $cursoModel = new Curso();
                $curso = $cursoModel->find($sesion['curso_id']);
                if (!$curso || $curso['profesor_id'] != $this->currentUser['id']) {
                    $this->redirectUnauthorized();
                    return;
                }
            }

            require_once __DIR__ . '/../models/Asistencia.php';
            $asistenciaModel = new Asistencia();
            $asistencias = $asistenciaModel->getBySesion($id);

            $this->render('admin/sesion_imprimir', [
                'page_title' => 'Lista de asistencia',
                'sesion'     => $sesion,
                'asistencias' => $asistencias,
            ]);

        } catch (\Throwable $e) {
            $this->handleSesionesError($e, 'Error al cargar la sesión para impresión');
        }
    }

    /**
     * Vista de detalle / monitoreo en vivo de una sesion
     */
    public function detalle() {
        if (!$this->hasPermission('sesiones_read')) {
            $this->redirectUnauthorized();
            return;
        }

        $id = intval($_GET['sesion_id'] ?? 0);
        if ($id <= 0) {
            $this->setFlashMessage('ID de sesion no valido', 'error');
            $this->redirect('index.php?page=sesiones');
            return;
        }

        try {
            $sesion = $this->sesionModel->getWithCursoInfo($id);
            if (!$sesion) {
                $this->setFlashMessage('Sesion no encontrada', 'error');
                $this->redirect('index.php?page=sesiones');
                return;
            }

            // Profesores solo ven sus propias sesiones
            if ($this->currentUser['rol'] === 'profesor') {
                require_once __DIR__ . '/../models/Curso.php';
                $cursoModel = new Curso();
                $curso = $cursoModel->find($sesion['curso_id']);
                if (!$curso || $curso['profesor_id'] != $this->currentUser['id']) {
                    $this->redirectUnauthorized();
                    return;
                }
            }

            require_once __DIR__ . '/../models/Asistencia.php';
            $asistenciaModel = new Asistencia();
            $asistencias = $asistenciaModel->getBySesion($id);

            $this->render('admin/sesion_detalle', [
                'page_title' => 'Detalle de sesion',
                'sesion'     => $sesion,
                'asistencias' => $asistencias,
                'can_activate' => $this->hasPermission('sesiones_activate'),
            ]);

        } catch (\Throwable $e) {
            $this->handleSesionesError($e, 'Error al cargar el detalle de la sesion');
        }
    }

    /**
     * Endpoint JSON para polling de asistencias en vivo
     * GET ?page=sesiones&action=asistencia_json&sesion_id=X
     */
    public function asistenciaJson() {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->hasPermission('sesiones_read')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permiso']);
            return;
        }

        $id = intval($_GET['sesion_id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID invalido']);
            return;
        }

        try {
            $sesion = $this->sesionModel->find($id);
            if (!$sesion) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'Sesion no encontrada']);
                return;
            }

            // Profesores solo ven sus propias sesiones
            if ($this->currentUser['rol'] === 'profesor') {
                if (!$this->sesionModel->canUserManageSession($this->currentUser['id'], $id)) {
                    http_response_code(403);
                    echo json_encode(['ok' => false, 'error' => 'Sin permiso']);
                    return;
                }
            }

            require_once __DIR__ . '/../models/Asistencia.php';
            $asistenciaModel = new Asistencia();
            $asistencias = $asistenciaModel->getBySesion($id);

            $conFirma = 0;
            $lista = [];
            foreach ($asistencias as $a) {
                $tieneFirma = !empty($a['firma_path']) || (!empty($a['firma']) && str_starts_with($a['firma'], 'data:image/'));
                if ($tieneFirma) {
                    $conFirma++;
                }
                $hora = '';
                if (!empty($a['hora_registro'])) {
                    $hora = date('H:i', strtotime($a['hora_registro']));
                }
                $lista[] = [
                    'nombre'      => $a['estudiante_nombre'] ?? '',
                    'documento'   => $a['estudiante_documento'] ?? '',
                    'hora'        => $hora,
                    'tiene_firma' => $tieneFirma,
                ];
            }

            echo json_encode([
                'ok'         => true,
                'total'      => count($asistencias),
                'con_firma'  => $conFirma,
                'estado'     => $sesion['estado'],
                'timestamp'  => date('Y-m-d H:i:s'),
                'asistencias' => $lista,
            ]);

        } catch (\Throwable $e) {
            error_log('SesionesController::asistenciaJson error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Error interno']);
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
        $aula = trim($this->sanitizeInput($_POST['aula'] ?? ''));
        $sede = trim($this->sanitizeInput($_POST['sede'] ?? ''));
        $data = [
            'curso_id'    => intval($_POST['curso_id'] ?? 0),
            'fecha'       => $this->sanitizeInput($_POST['fecha'] ?? ''),
            'hora_inicio' => $this->sanitizeInput($_POST['hora_inicio'] ?? ''),
            'hora_fin'    => $this->sanitizeInput($_POST['hora_fin'] ?? ''),
            'estado'      => $this->sanitizeInput($_POST['estado'] ?? 'activa'),
            'aula'        => $aula !== '' ? substr($aula, 0, 30) : null,
            'sede'        => $sede !== '' ? substr($sede, 0, 50) : null,
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
            $data['token'] = bin2hex(random_bytes(32));
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
        } catch (\Throwable $e) {
            error_log('procesarSesionForm error: ' . $e->getMessage());
            return ['errors' => ['Error al procesar la sesión. Intente nuevamente.']];
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
        $this->redirect('index.php?page=dashboard');
    }
    
    /**
     * Manejar errores
     */
    protected function handleSesionesError(\Throwable $exception, $userMessage = 'Ha ocurrido un error') {
        error_log('SesionesController: ' . $exception->getMessage());
        if ($this->isAjaxRequest()) {
            $this->jsonResponse(['error' => $userMessage], 500);
        } else {
            $this->setFlashMessage($userMessage, 'error');
            $this->redirect('index.php?page=sesiones');
        }
    }
}
