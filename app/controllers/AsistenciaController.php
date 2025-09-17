<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Asistencia.php';
require_once __DIR__ . '/../models/Sesion.php';
require_once __DIR__ . '/../models/Estudiante.php';
require_once __DIR__ . '/../models/Curso.php';

/**
 * Controlador de Asistencias
 * Maneja el registro y gestión de asistencias de estudiantes
 */
class AsistenciaController extends BaseController {
    private $asistenciaModel;
    private $sesionModel;
    private $estudianteModel;
    private $cursoModel;
    
    public function __construct() {
        parent::__construct();
        
        // Inicializar modelos
        $this->asistenciaModel = new Asistencia();
        $this->sesionModel = new Sesion();
        $this->estudianteModel = new Estudiante();
        $this->cursoModel = new Curso();
    }
    
    /**
     * Método principal para manejar las peticiones
     */
    public function handleRequest() {
        $action = $_GET['action'] ?? 'registrar';
        
        switch ($action) {
            case 'registrar':
                $this->registrarAsistencia();
                break;
            case 'listar':
                $this->listarAsistencias();
                break;
            case 'exportar':
                $this->exportarAsistencias();
                break;
            case 'estadisticas':
                $this->estadisticasAsistencia();
                break;
            case 'validar_estudiante':
                $this->validarEstudiante();
                break;
            default:
                $this->registrarAsistencia();
        }
    }
    
    /**
     * Registrar asistencia de estudiante
     */
    public function registrarAsistencia() {
        $error = '';
        $success = '';
        $sesion = null;
        
        // Obtener ID de sesión desde URL o token
        $sesion_id = $_GET['sesion_id'] ?? $_GET['token'] ?? null;
        
        if (!$sesion_id) {
            $error = 'Sesión no válida o no especificada.';
        } else {
            try {
                // Obtener información de la sesión
                $sesion = $this->getSesionInfo($sesion_id);
                
                if (!$sesion) {
                    $error = 'La sesión no existe o no está activa.';
                } elseif ($sesion['estado'] !== 'activa') {
                    $error = 'Esta sesión ya ha finalizado.';
                } else {
                    // Verificar si la sesión está dentro del horario permitido
                    if (!$this->isSessionTimeValid($sesion)) {
                        $error = 'La sesión no está disponible en este momento.';
                    }
                }
                
            } catch (Exception $e) {
                $error = 'Error al obtener información de la sesión.';
            }
        }
        
        // Procesar formulario de registro
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
            $result = $this->procesarRegistroAsistencia($sesion);
            
            if (isset($result['error'])) {
                $error = $result['error'];
            } else {
                $success = $result['success'];
            }
        }
        
        // Renderizar vista
        $this->render('asistencia/registrar', [
            'page_title' => 'Registrar Asistencia',
            'sesion' => $sesion,
            'error' => $error,
            'success' => $success,
            'csrf_token' => $this->generateCSRFToken()
        ]);
    }
    
    /**
     * Listar asistencias (solo para usuarios autenticados)
     */
    public function listarAsistencias() {
        // Verificar permisos
        if (!$this->hasPermission('asistencias_read')) {
            $this->redirectUnauthorized();
            return;
        }
        
        try {
            $filtros = $this->getListaFiltros();
            $asistencias = $this->getAsistenciasByRole($filtros);
            $cursos = $this->getCursosForFilter();
            $estadisticas = $this->getEstadisticasResumen($filtros);
            
            $this->render('asistencia/listar', [
                'page_title' => 'Lista de Asistencias',
                'asistencias' => $asistencias,
                'cursos' => $cursos,
                'filtros' => $filtros,
                'estadisticas' => $estadisticas,
                'can_export' => $this->hasPermission('asistencias_export'),
                'can_edit' => $this->hasPermission('asistencias_update'),
                'can_delete' => $this->hasPermission('asistencias_delete')
            ]);
            
        } catch (Exception $e) {
            $this->handleAsistenciaError($e, 'Error al cargar las asistencias');
        }
    }
    
    /**
     * Exportar asistencias
     */
    public function exportarAsistencias() {
        // Verificar permisos
        if (!$this->hasPermission('asistencias_export')) {
            $this->jsonResponse(['error' => 'No tienes permisos para exportar'], 403);
            return;
        }
        
        try {
            $filtros = $this->getExportFiltros();
            $formato = $_GET['formato'] ?? 'excel';
            
            // Verificar permisos específicos del curso si es profesor
            if ($this->currentUser['rol'] === 'profesor' && isset($filtros['curso_id'])) {
                $curso = $this->cursoModel->find($filtros['curso_id']);
                if (!$curso || $curso['profesor_id'] != $this->currentUser['id']) {
                    throw new Exception('No tienes permisos para exportar este curso');
                }
            }
            
            $datos = $this->asistenciaModel->exportar($filtros);
            
            if ($formato === 'excel') {
                $this->exportToExcel($datos, 'asistencias');
            } else {
                $this->exportToPDF($datos, 'asistencias');
            }
            
        } catch (Exception $e) {
            $this->handleAsistenciaError($e, 'Error al exportar asistencias');
        }
    }
    
    /**
     * Mostrar estadísticas de asistencia
     */
    public function estadisticasAsistencia() {
        // Verificar permisos
        if (!$this->hasPermission('estadisticas_read')) {
            $this->redirectUnauthorized();
            return;
        }
        
        try {
            $filtros = $this->getEstadisticasFiltros();
            $estadisticas = $this->getEstadisticasDetalladas($filtros);
            $cursos = $this->getCursosForFilter();
            
            $this->render('asistencia/estadisticas', [
                'page_title' => 'Estadísticas de Asistencia',
                'estadisticas' => $estadisticas,
                'cursos' => $cursos,
                'filtros' => $filtros
            ]);
            
        } catch (Exception $e) {
            $this->handleAsistenciaError($e, 'Error al cargar las estadísticas');
        }
    }
    
    /**
     * Validar estudiante (AJAX)
     */
    public function validarEstudiante() {
        if (!$this->isAjaxRequest()) {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }
        
        $documento = $_POST['documento'] ?? '';
        $sesion_id = $_POST['sesion_id'] ?? '';
        
        if (empty($documento) || empty($sesion_id)) {
            $this->jsonResponse(['error' => 'Datos incompletos'], 400);
            return;
        }
        
        try {
            // Buscar estudiante
            $estudiante = $this->estudianteModel->findByDocumento($documento);
            
            if (!$estudiante) {
                $this->jsonResponse(['error' => 'Estudiante no encontrado'], 404);
                return;
            }
            
            // Verificar si ya registró asistencia
            $yaRegistrado = $this->asistenciaModel->yaRegistroAsistencia($sesion_id, $estudiante['id']);
            
            if ($yaRegistrado) {
                $this->jsonResponse(['error' => 'Ya has registrado tu asistencia para esta sesión'], 409);
                return;
            }
            
            // Verificar si está inscrito en el curso
            $sesion = $this->sesionModel->find($sesion_id);
            if (!$sesion) {
                $this->jsonResponse(['error' => 'Sesión no válida'], 404);
                return;
            }
            
            $inscrito = $this->estudianteModel->estaInscritoEnCurso($estudiante['id'], $sesion['curso_id']);
            
            if (!$inscrito) {
                $this->jsonResponse(['error' => 'No estás inscrito en este curso'], 403);
                return;
            }
            
            $this->jsonResponse([
                'success' => true,
                'estudiante' => [
                    'id' => $estudiante['id'],
                    'nombre' => $estudiante['nombre'],
                    'documento' => $estudiante['documento'],
                    'codigo' => $estudiante['codigo']
                ]
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Error al validar estudiante'], 500);
        }
    }
    
    /**
     * Obtener información de la sesión
     */
    private function getSesionInfo($sesion_id) {
        return $this->sesionModel->getWithCursoInfo($sesion_id);
    }
    
    /**
     * Verificar si la sesión está en horario válido
     */
    private function isSessionTimeValid($sesion) {
        $now = new DateTime();
        $fechaSesion = new DateTime($sesion['fecha'] . ' ' . $sesion['hora_inicio']);
        $horaFin = $sesion['hora_fin'] ? new DateTime($sesion['fecha'] . ' ' . $sesion['hora_fin']) : null;
        
        // Permitir registro 15 minutos antes y hasta 30 minutos después del inicio
        $inicioPermitido = clone $fechaSesion;
        $inicioPermitido->modify('-15 minutes');
        
        $finPermitido = $horaFin ? $horaFin : (clone $fechaSesion)->modify('+2 hours');
        $finPermitido->modify('+30 minutes');
        
        return $now >= $inicioPermitido && $now <= $finPermitido;
    }
    
    /**
     * Procesar registro de asistencia
     */
    private function procesarRegistroAsistencia($sesion) {
        // Verificar token CSRF
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            return ['error' => 'Token de seguridad inválido'];
        }
        
        // Validar y sanitizar datos
        $documento = $this->sanitizeInput($_POST['documento'] ?? '');
        $nombre = $this->sanitizeInput($_POST['nombre'] ?? '');
        $codigo = $this->sanitizeInput($_POST['codigo'] ?? '');
        $telefono = $this->sanitizeInput($_POST['telefono'] ?? '');
        $correo = $this->sanitizeInput($_POST['correo'] ?? '');
        
        if (empty($documento) || empty($nombre)) {
            return ['error' => 'Por favor, complete los campos obligatorios'];
        }
        
        try {
            // Buscar o crear estudiante
            $estudiante = $this->estudianteModel->findByDocumento($documento);
            
            if (!$estudiante) {
                // Crear nuevo estudiante
                $estudianteData = [
                    'documento' => $documento,
                    'nombre' => $nombre,
                    'codigo' => $codigo,
                    'telefono' => $telefono,
                    'correo' => $correo,
                    'activo' => 1
                ];
                
                $result = $this->estudianteModel->create($estudianteData);
                
                if (isset($result['errors'])) {
                    return ['error' => 'Error al registrar estudiante: ' . implode(', ', $result['errors'])];
                }
                
                $estudiante_id = $result;
            } else {
                $estudiante_id = $estudiante['id'];
                
                // Actualizar información si es necesaria
                if ($estudiante['nombre'] !== $nombre || $estudiante['telefono'] !== $telefono || $estudiante['correo'] !== $correo) {
                    $updateData = [
                        'nombre' => $nombre,
                        'telefono' => $telefono,
                        'correo' => $correo
                    ];
                    
                    if (!empty($codigo) && $estudiante['codigo'] !== $codigo) {
                        $updateData['codigo'] = $codigo;
                    }
                    
                    $this->estudianteModel->update($estudiante_id, $updateData);
                }
            }
            
            // Verificar si ya registró asistencia
            if ($this->asistenciaModel->yaRegistroAsistencia($sesion['id'], $estudiante_id)) {
                return ['error' => 'Ya has registrado tu asistencia para esta sesión'];
            }
            
            // Registrar asistencia
            $asistenciaData = [
                'sesion_id' => $sesion['id'],
                'estudiante_id' => $estudiante_id,
                'hora_registro' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];
            
            $result = $this->asistenciaModel->create($asistenciaData);
            
            if (isset($result['errors'])) {
                return ['error' => 'Error al registrar asistencia: ' . implode(', ', $result['errors'])];
            }
            
            // Inscribir estudiante al curso si no está inscrito
            if (!$this->estudianteModel->estaInscritoEnCurso($estudiante_id, $sesion['curso_id'])) {
                $this->estudianteModel->inscribirEnCurso($estudiante_id, $sesion['curso_id']);
            }
            
            // Log de actividad
            $this->logActivity('asistencia_registrada', $result, $estudiante_id, [
                'sesion_id' => $sesion['id'],
                'curso_id' => $sesion['curso_id']
            ]);
            
            return ['success' => 'Asistencia registrada correctamente. ¡Bienvenido/a ' . $nombre . '!'];
            
        } catch (Exception $e) {
            return ['error' => 'Error al procesar el registro: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener filtros para la lista
     */
    private function getListaFiltros() {
        return [
            'curso_id' => $_GET['curso_id'] ?? null,
            'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
            'fecha_fin' => $_GET['fecha_fin'] ?? null,
            'estudiante' => $_GET['estudiante'] ?? null,
            'page' => intval($_GET['page'] ?? 1),
            'per_page' => 20
        ];
    }
    
    /**
     * Obtener asistencias según el rol del usuario
     */
    private function getAsistenciasByRole($filtros) {
        if ($this->currentUser['rol'] === 'profesor') {
            $filtros['profesor_id'] = $this->currentUser['id'];
        }
        
        return $this->asistenciaModel->getWithFilters($filtros);
    }
    
    /**
     * Obtener cursos para filtro
     */
    private function getCursosForFilter() {
        if ($this->currentUser['rol'] === 'profesor') {
            return $this->cursoModel->getByProfesor($this->currentUser['id']);
        } else {
            return $this->cursoModel->getAll(['activo' => 1]);
        }
    }
    
    /**
     * Obtener estadísticas resumen
     */
    private function getEstadisticasResumen($filtros) {
        if ($this->currentUser['rol'] === 'profesor') {
            $filtros['profesor_id'] = $this->currentUser['id'];
        }
        
        return $this->asistenciaModel->getEstadisticasResumen($filtros);
    }
    
    /**
     * Obtener filtros para exportación
     */
    private function getExportFiltros() {
        return [
            'curso_id' => $_GET['curso_id'] ?? null,
            'sesion_id' => $_GET['sesion_id'] ?? null,
            'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
            'fecha_fin' => $_GET['fecha_fin'] ?? null,
            'profesor_id' => $this->currentUser['rol'] === 'profesor' ? $this->currentUser['id'] : null
        ];
    }
    
    /**
     * Obtener filtros para estadísticas
     */
    private function getEstadisticasFiltros() {
        return [
            'curso_id' => $_GET['curso_id'] ?? null,
            'periodo' => $_GET['periodo'] ?? 'mes',
            'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
            'fecha_fin' => $_GET['fecha_fin'] ?? null
        ];
    }
    
    /**
     * Obtener estadísticas detalladas
     */
    private function getEstadisticasDetalladas($filtros) {
        if ($this->currentUser['rol'] === 'profesor') {
            $filtros['profesor_id'] = $this->currentUser['id'];
        }
        
        return $this->asistenciaModel->getEstadisticasDetalladas($filtros);
    }
    
    /**
     * Exportar a Excel
     */
    private function exportToExcel($datos, $tipo) {
        require_once __DIR__ . '/../utils/ExportHelper.php';
        
        $prepared = ExportHelper::prepareAsistenciaData($datos);
        ExportHelper::exportToExcel(
            $prepared['datos'], 
            'asistencia', 
            'Reporte de Asistencia - ' . date('d/m/Y'), 
            $prepared['encabezados']
        );
    }
    
    /**
     * Exportar a PDF
     */
    private function exportToPDF($datos, $tipo) {
        require_once __DIR__ . '/../utils/ExportHelper.php';
        
        $prepared = ExportHelper::prepareAsistenciaData($datos);
        ExportHelper::exportToPDF(
            $prepared['datos'], 
            'asistencia', 
            'Reporte de Asistencia - ' . date('d/m/Y'), 
            $prepared['encabezados']
        );
    }
    
    /**
     * Exportar a CSV
     */
    private function exportToCSV($datos, $tipo) {
        if (empty($datos)) {
            throw new Exception('No hay datos para exportar');
        }
        
        $output = fopen('php://output', 'w');
        
        // Escribir BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
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
    protected function handleAsistenciaError($exception, $userMessage = 'Ha ocurrido un error') {
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