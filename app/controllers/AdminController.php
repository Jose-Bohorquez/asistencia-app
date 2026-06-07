<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Curso.php';
require_once __DIR__ . '/../models/Programa.php';
require_once __DIR__ . '/../models/Estudiante.php';
require_once __DIR__ . '/../models/Sesion.php';
require_once __DIR__ . '/../models/Asistencia.php';

/**
 * Controlador de Administración
 * Maneja el dashboard y funciones administrativas generales
 */
class AdminController extends BaseController {
    private $usuarioModel;
    private $cursoModel;
    private $programaModel;
    private $estudianteModel;
    private $sesionModel;
    private $asistenciaModel;
    
    public function __construct() {
        parent::__construct();
        
        // Inicializar modelos
        $this->usuarioModel = new Usuario();
        $this->cursoModel = new Curso();
        $this->programaModel = new Programa();
        $this->estudianteModel = new Estudiante();
        $this->sesionModel = new Sesion();
        $this->asistenciaModel = new Asistencia();
    }
    
    /**
     * Método principal para manejar las peticiones
     */
    public function handleRequest() {
        $action = $_GET['action'] ?? 'dashboard';
        
        switch ($action) {
            case 'dashboard':
                $this->dashboard();
                break;
            case 'cursos':
                $this->cursos();
                break;
            case 'exportar':
                $this->exportar();
                break;
            default:
                $this->dashboard();
        }
    }
    
    /**
     * Mostrar dashboard principal
     */
    public function dashboard() {
        // Verificar permisos básicos de acceso
        if (!$this->hasPermission('dashboard_access')) {
            $this->redirectUnauthorized();
            return;
        }
        
        try {
            $stats = $this->getDashboardStats();
            $sesionesActivas = $this->getSesionesActivas() ?? [];
            $recentActivity = $this->getRecentActivity();
            
            $this->render('admin/dashboard', [
                'page_title'        => 'Dashboard',
                'stats'             => $stats,
                // Variables que usa la vista directamente
                'totalCursos'       => $stats['total_cursos']      ?? 0,
                'totalSesiones'     => $stats['total_sesiones']    ?? 0,
                'totalEstudiantes'  => $stats['total_estudiantes'] ?? 0,
                'totalUsuarios'     => $stats['total_usuarios']    ?? 0,
                'asistenciaPromedio'=> $stats['asistencia_promedio'] ?? 0,
                'sesionesActivas'   => $sesionesActivas,
                'sesiones_activas'  => $sesionesActivas,
                'recent_activity'   => $recentActivity
            ]);
            
        } catch (Exception $e) {
            $this->handleAdminError($e, 'Error al cargar el dashboard');
        }
    }
    
    /**
     * Gestión de cursos
     */
    public function cursos() {
        // Verificar permisos
        if (!$this->hasPermission('cursos_read')) {
            $this->redirectUnauthorized();
            return;
        }
        
        $error = '';
        $success = '';
        
        // Procesar formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Detectar si es eliminación
            if (!empty($_POST['_action']) && $_POST['_action'] === 'delete') {
                if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                    $this->setFlashMessage('Token de seguridad inválido', 'error');
                    $this->redirect('index.php?page=cursos');
                    return;
                }
                if (!$this->hasPermission('cursos_delete')) {
                    $this->setFlashMessage('No tienes permisos para eliminar cursos', 'error');
                    $this->redirect('index.php?page=cursos');
                    return;
                }
                $deleteId = intval($_POST['delete_id'] ?? 0);
                if ($deleteId > 0) {
                    try {
                        // Verificar que no tenga sesiones asociadas
                        $sesiones = $this->sesionModel->countByProfesor(0); // placeholder
                        $conn = $this->db;
                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM sesiones WHERE curso_id = ?");
                        $stmt->bind_param('i', $deleteId);
                        $stmt->execute();
                        $sesCount = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
                        $stmt->close();

                        if ($sesCount > 0) {
                            $this->setFlashMessage("No se puede eliminar: el curso tiene {$sesCount} sesión(es) asociada(s)", 'error');
                        } else {
                            $delResult = $this->cursoModel->delete($deleteId);
                            if ($delResult && !is_array($delResult)) {
                                $this->logActivity('curso_deleted', 'cursos', $deleteId);
                                $this->setFlashMessage('Curso eliminado correctamente', 'success');
                            } else {
                                $msg = is_array($delResult) && isset($delResult['errors'])
                                    ? implode(', ', $delResult['errors'])
                                    : 'Error al eliminar el curso';
                                $this->setFlashMessage($msg, 'error');
                            }
                        }
                    } catch (Exception $e) {
                        error_log('AdminController curso delete: ' . $e->getMessage());
                        $this->setFlashMessage('Error al eliminar el curso', 'error');
                    }
                }
                $this->redirect('index.php?page=cursos');
                return;
            }

            // Crear / Editar
            $result = $this->processCursoForm();
            if (isset($result['error'])) {
                $error = $result['error'];
            } else {
                $this->setFlashMessage($result['success'], 'success');
                $this->redirect('index.php?page=cursos');
                return;
            }
        }
        
        try {
            // Obtener cursos según el rol
            $cursos = $this->getCursosByRole();
            $programas = $this->programaModel->getAll(['activo' => 1]);
            $profesores = $this->usuarioModel->getByRole('profesor');
            
            $this->render('admin/cursos', [
                'page_title' => 'Gestión de Cursos',
                'cursos' => $cursos,
                'programas' => $programas,
                'profesores' => $profesores,
                'error' => $error,
                'success' => $success,
                'can_create' => $this->hasPermission('cursos_create'),
                'can_edit' => $this->hasPermission('cursos_update'),
                'can_delete' => $this->hasPermission('cursos_delete')
            ]);
            
        } catch (Exception $e) {
            $this->handleAdminError($e, 'Error al cargar los cursos');
        }
    }
    
    /**
     * Exportar datos
     */
    public function exportar() {
        // Verificar permisos
        if (!$this->hasPermission('reportes_export')) {
            $this->jsonResponse(['error' => 'No tienes permisos para exportar'], 403);
            return;
        }
        
        $tipo = $_GET['tipo'] ?? '';
        $formato = $_GET['formato'] ?? 'excel';
        
        try {
            switch ($tipo) {
                case 'asistencias':
                    $this->exportarAsistencias($formato);
                    break;
                case 'estudiantes':
                    $this->exportarEstudiantes($formato);
                    break;
                case 'cursos':
                    $this->exportarCursos($formato);
                    break;
                default:
                    throw new Exception('Tipo de exportación no válido');
            }
        } catch (Exception $e) {
            $this->handleAdminError($e, 'Error al exportar datos');
        }
    }
    
    /**
     * Obtener estadísticas del dashboard
     */
    private function getDashboardStats() {
        $stats = [];
        
        if ($this->currentUser['rol'] === 'profesor') {
            // Estadísticas específicas del profesor
            $stats['total_cursos'] = $this->cursoModel->countByProfesor($this->currentUser['id']);
            $stats['total_sesiones'] = $this->sesionModel->countByProfesor($this->currentUser['id']);
            $stats['total_estudiantes'] = $this->estudianteModel->countByProfesor($this->currentUser['id']);
            $stats['asistencia_promedio'] = $this->asistenciaModel->getPromedioByProfesor($this->currentUser['id']);
        } else {
            // Estadísticas generales para admin y super_admin
            $stats['total_cursos'] = $this->cursoModel->count(['activo' => 1]);
            $stats['total_sesiones'] = $this->sesionModel->count();
            $stats['total_estudiantes'] = $this->estudianteModel->count(['activo' => 1]);
            $stats['total_usuarios'] = $this->usuarioModel->count(['activo' => 1]);
            $stats['asistencia_promedio'] = $this->asistenciaModel->getPromedioGeneral();
        }
        
        return $stats;
    }
    
    /**
     * Obtener sesiones activas
     */
    private function getSesionesActivas() {
        if ($this->currentUser['rol'] === 'profesor') {
            return $this->sesionModel->getActivasByProfesor($this->currentUser['id']);
        } else {
            return $this->sesionModel->getActivas();
        }
    }
    
    /**
     * Obtener actividad reciente
     */
    private function getRecentActivity() {
        // Implementar según necesidades específicas
        return [];
    }
    
    /**
     * Procesar formulario de curso
     */
    private function processCursoForm() {
        // Verificar token CSRF
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            return ['error' => 'Token de seguridad inválido'];
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $action = $id > 0 ? 'update' : 'create';
        
        // Verificar permisos específicos
        if (!$this->hasPermission("cursos_{$action}")) {
            return ['error' => 'No tienes permisos para esta acción'];
        }
        
        // Validar y sanitizar datos
        $data = [
            'codigo'     => $this->sanitizeInput($_POST['codigo'] ?? ''),
            'nombre'     => $this->sanitizeInput($_POST['nombre'] ?? ''),
            'programa_id'=> intval($_POST['programa_id'] ?? 0),
            'area'       => $this->sanitizeInput($_POST['area'] ?? ''),
            'semestre'   => $this->sanitizeInput($_POST['semestre'] ?? ''),
            'grupo'      => $this->sanitizeInput($_POST['grupo'] ?? ''),
            'aula'       => $this->sanitizeInput($_POST['aula'] ?? ''),
            'sede'       => $this->sanitizeInput($_POST['sede'] ?? '')
        ];
        
        // Asignar profesor según el rol
        if ($this->currentUser['rol'] === 'profesor') {
            $data['profesor_id'] = $this->currentUser['id'];
        } elseif (isset($_POST['profesor_id']) && intval($_POST['profesor_id']) > 0) {
            $data['profesor_id'] = intval($_POST['profesor_id']);
        }
        
        try {
            if ($id > 0) {
                // Verificar permisos de edición específicos
                if ($this->currentUser['rol'] === 'profesor') {
                    $curso = $this->cursoModel->find($id);
                    if (!$curso || $curso['profesor_id'] != $this->currentUser['id']) {
                        return ['error' => 'No tienes permisos para editar este curso'];
                    }
                }
                
                $result = $this->cursoModel->update($id, $data);
                
                if (isset($result['errors'])) {
                    return ['error' => implode(', ', $result['errors'])];
                }
                
                $this->logActivity('curso_updated', 'cursos', $id, $data);
                return ['success' => 'Curso actualizado correctamente'];
                
            } else {
                $result = $this->cursoModel->create($data);
                
                if (isset($result['errors'])) {
                    return ['error' => implode(', ', $result['errors'])];
                }
                
                $this->logActivity('curso_created', 'cursos', $result, $data);
                return ['success' => 'Curso creado correctamente'];
            }
            
        } catch (Exception $e) {
            error_log('procesarCursoForm error: ' . $e->getMessage());
            return ['error' => 'Error al procesar el curso. Intente nuevamente.'];
        }
    }
    
    /**
     * Obtener cursos según el rol del usuario
     */
    private function getCursosByRole() {
        if ($this->currentUser['rol'] === 'profesor') {
            return $this->cursoModel->getByProfesor($this->currentUser['id']);
        } else {
            return $this->cursoModel->getAllWithRelations();
        }
    }
    
    /**
     * Exportar asistencias
     */
    private function exportarAsistencias($formato) {
        $cursoId = $_GET['curso_id'] ?? null;
        $fechaInicio = $_GET['fecha_inicio'] ?? null;
        $fechaFin = $_GET['fecha_fin'] ?? null;
        
        // Verificar permisos específicos del curso si es profesor
        if ($this->currentUser['rol'] === 'profesor' && $cursoId) {
            $curso = $this->cursoModel->find($cursoId);
            if (!$curso || $curso['profesor_id'] != $this->currentUser['id']) {
                throw new Exception('No tienes permisos para exportar este curso');
            }
        }
        
        $datos = $this->asistenciaModel->exportarAsistencias($cursoId, $fechaInicio, $fechaFin);
        
        if ($formato === 'excel') {
            $this->exportToExcel($datos, 'asistencias');
        } else {
            $this->exportToPDF($datos, 'asistencias');
        }
    }
    
    /**
     * Exportar estudiantes
     */
    private function exportarEstudiantes($formato) {
        $cursoId = $_GET['curso_id'] ?? null;
        
        // Verificar permisos específicos del curso si es profesor
        if ($this->currentUser['rol'] === 'profesor' && $cursoId) {
            $curso = $this->cursoModel->find($cursoId);
            if (!$curso || $curso['profesor_id'] != $this->currentUser['id']) {
                throw new Exception('No tienes permisos para exportar este curso');
            }
        }
        
        $datos = $this->estudianteModel->exportar($cursoId);
        
        if ($formato === 'excel') {
            $this->exportToExcel($datos, 'estudiantes');
        } else {
            $this->exportToPDF($datos, 'estudiantes');
        }
    }
    
    /**
     * Exportar cursos
     */
    private function exportarCursos($formato) {
        if ($this->currentUser['rol'] === 'profesor') {
            $datos = $this->cursoModel->exportarByProfesor($this->currentUser['id']);
        } else {
            $datos = $this->cursoModel->exportar();
        }
        
        if ($formato === 'excel') {
            $this->exportToExcel($datos, 'cursos');
        } else {
            $this->exportToPDF($datos, 'cursos');
        }
    }
    
    /**
     * Exportar a Excel
     */
    private function exportToExcel($datos, $tipo) {
        require_once __DIR__ . '/../utils/ExportHelper.php';
        
        switch ($tipo) {
            case 'asistencias':
                $prepared = ExportHelper::prepareAsistenciaData($datos);
                ExportHelper::exportToExcel(
                    $prepared['datos'], 
                    'asistencia', 
                    'Reporte de Asistencia - ' . date('d/m/Y'), 
                    $prepared['encabezados']
                );
                break;
            case 'estudiantes':
                $prepared = ExportHelper::prepareEstudiantesData($datos);
                ExportHelper::exportToExcel(
                    $prepared['datos'], 
                    'estudiantes', 
                    'Reporte de Estudiantes - ' . date('d/m/Y'), 
                    $prepared['encabezados']
                );
                break;
            case 'cursos':
                $prepared = ExportHelper::prepareCursosData($datos);
                ExportHelper::exportToExcel(
                    $prepared['datos'], 
                    'cursos', 
                    'Reporte de Cursos - ' . date('d/m/Y'), 
                    $prepared['encabezados']
                );
                break;
        }
    }
    
    /**
     * Exportar a PDF
     */
    private function exportToPDF($datos, $tipo) {
        require_once __DIR__ . '/../utils/ExportHelper.php';
        
        switch ($tipo) {
            case 'asistencias':
                $prepared = ExportHelper::prepareAsistenciaData($datos);
                ExportHelper::exportToPDF(
                    $prepared['datos'], 
                    'asistencia', 
                    'Reporte de Asistencia - ' . date('d/m/Y'), 
                    $prepared['encabezados']
                );
                break;
            case 'estudiantes':
                $prepared = ExportHelper::prepareEstudiantesData($datos);
                ExportHelper::exportToPDF(
                    $prepared['datos'], 
                    'estudiantes', 
                    'Reporte de Estudiantes - ' . date('d/m/Y'), 
                    $prepared['encabezados']
                );
                break;
            case 'cursos':
                $prepared = ExportHelper::prepareCursosData($datos);
                ExportHelper::exportToPDF(
                    $prepared['datos'], 
                    'cursos', 
                    'Reporte de Cursos - ' . date('d/m/Y'), 
                    $prepared['encabezados']
                );
                break;
        }
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
     * Manejar errores específicos del AdminController
     */
    protected function handleAdminError($exception, $userMessage = 'Ha ocurrido un error') {
        // Log del error
        error_log($exception->getMessage());
        
        if ($this->isAjaxRequest()) {
            $this->jsonResponse(['error' => $userMessage], 500);
        } else {
            $this->setFlashMessage($userMessage, 'error');
            $this->redirect('index.php?page=dashboard');
        }
    }
}