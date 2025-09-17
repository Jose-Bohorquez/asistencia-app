<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Programa.php';
require_once __DIR__ . '/../models/Curso.php';

/**
 * Controlador de Programas
 * Maneja la gestión de programas académicos
 */
class ProgramasController extends BaseController {
    private $programaModel;
    private $cursoModel;
    
    public function __construct() {
        parent::__construct();
        
        // Inicializar modelos
        $this->programaModel = new Programa();
        $this->cursoModel = new Curso();
    }
    
    /**
     * Método principal para manejar las peticiones
     */
    public function handleRequest() {
        // Verificar permisos - solo super_admin y admin pueden gestionar programas
        if (!$this->hasPermission('programas_read')) {
            $this->redirectUnauthorized();
            return;
        }
        
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
            case 'toggle_status':
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
     * Mostrar lista de programas
     */
    public function index() {
        try {
            $filtros = $this->getFiltros();
            $programas = $this->programaModel->getWithFilters($filtros);
            $estadisticas = $this->getEstadisticas();
            
            $this->render('admin/programas', [
                'page_title' => 'Gestión de Programas',
                'programas' => $programas,
                'filtros' => $filtros,
                'estadisticas' => $estadisticas,
                'can_create' => $this->hasPermission('programas_create'),
                'can_edit' => $this->hasPermission('programas_update'),
                'can_delete' => $this->hasPermission('programas_delete'),
                'can_export' => $this->hasPermission('programas_export'),
                'csrf_token' => $this->generateCSRFToken()
            ]);
            
        } catch (Exception $e) {
            $this->handleProgramasError($e, 'Error al cargar los programas');
        }
    }
    
    /**
     * Crear nuevo programa
     */
    public function create() {
        // Verificar permisos
        if (!$this->hasPermission('programas_create')) {
            $this->jsonResponse(['error' => 'No tienes permisos para crear programas'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->procesarFormulario();
            
            if (isset($result['errors'])) {
                $this->jsonResponse(['errors' => $result['errors']], 400);
            } else {
                $this->logActivity('programa_creado', $result, null, [
                    'programa_id' => $result
                ]);
                
                $this->setFlashMessage('Programa creado correctamente', 'success');
                $this->jsonResponse(['success' => true, 'id' => $result]);
            }
        } else {
            $this->render('admin/programa_form', [
                'page_title' => 'Crear Programa',
                'programa' => null,
                'csrf_token' => $this->generateCSRFToken()
            ]);
        }
    }
    
    /**
     * Editar programa existente
     */
    public function edit() {
        // Verificar permisos
        if (!$this->hasPermission('programas_update')) {
            $this->jsonResponse(['error' => 'No tienes permisos para editar programas'], 403);
            return;
        }
        
        $id = intval($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            $this->setFlashMessage('ID de programa no válido', 'error');
            $this->redirect('index.php?page=programas');
            return;
        }
        
        try {
            $programa = $this->programaModel->find($id);
            
            if (!$programa) {
                $this->setFlashMessage('Programa no encontrado', 'error');
                $this->redirect('index.php?page=programas');
                return;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = $this->procesarFormulario($id);
                
                if (isset($result['errors'])) {
                    $this->jsonResponse(['errors' => $result['errors']], 400);
                } else {
                    $this->logActivity('programa_actualizado', $id, null, [
                        'programa_id' => $id,
                        'cambios' => $result['cambios'] ?? []
                    ]);
                    
                    $this->setFlashMessage('Programa actualizado correctamente', 'success');
                    $this->jsonResponse(['success' => true]);
                }
            } else {
                $this->render('admin/programa_form', [
                    'page_title' => 'Editar Programa',
                    'programa' => $programa,
                    'csrf_token' => $this->generateCSRFToken()
                ]);
            }
            
        } catch (Exception $e) {
            $this->handleProgramasError($e, 'Error al procesar el programa');
        }
    }
    
    /**
     * Eliminar programa
     */
    public function delete() {
        // Verificar permisos
        if (!$this->hasPermission('programas_delete')) {
            $this->jsonResponse(['error' => 'No tienes permisos para eliminar programas'], 403);
            return;
        }
        
        $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $this->jsonResponse(['error' => 'ID de programa no válido'], 400);
            return;
        }
        
        try {
            $programa = $this->programaModel->find($id);
            
            if (!$programa) {
                $this->jsonResponse(['error' => 'Programa no encontrado'], 404);
                return;
            }
            
            // Verificar si el programa tiene cursos asociados
            $cursosAsociados = $this->cursoModel->countByPrograma($id);
            
            if ($cursosAsociados > 0) {
                $this->jsonResponse([
                    'error' => 'No se puede eliminar el programa porque tiene ' . $cursosAsociados . ' curso(s) asociado(s)'
                ], 409);
                return;
            }
            
            // Eliminar programa
            $result = $this->programaModel->delete($id);
            
            if ($result) {
                $this->logActivity('programa_eliminado', $id, null, [
                    'programa_nombre' => $programa['nombre'],
                    'programa_codigo' => $programa['codigo']
                ]);
                
                $this->setFlashMessage('Programa eliminado correctamente', 'success');
                $this->jsonResponse(['success' => true]);
            } else {
                $this->jsonResponse(['error' => 'Error al eliminar el programa'], 500);
            }
            
        } catch (Exception $e) {
            $this->handleProgramasError($e, 'Error al eliminar el programa');
        }
    }
    
    /**
     * Cambiar estado activo/inactivo del programa
     */
    public function toggleStatus() {
        // Verificar permisos
        if (!$this->hasPermission('programas_update')) {
            $this->jsonResponse(['error' => 'No tienes permisos para modificar programas'], 403);
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $this->jsonResponse(['error' => 'ID de programa no válido'], 400);
            return;
        }
        
        try {
            $programa = $this->programaModel->find($id);
            
            if (!$programa) {
                $this->jsonResponse(['error' => 'Programa no encontrado'], 404);
                return;
            }
            
            $nuevoEstado = $programa['activo'] ? 0 : 1;
            $result = $this->programaModel->update($id, ['activo' => $nuevoEstado]);
            
            if (isset($result['errors'])) {
                $this->jsonResponse(['errors' => $result['errors']], 400);
            } else {
                $this->logActivity('programa_estado_cambiado', $id, null, [
                    'programa_id' => $id,
                    'estado_anterior' => $programa['activo'],
                    'estado_nuevo' => $nuevoEstado
                ]);
                
                $mensaje = $nuevoEstado ? 'Programa activado' : 'Programa desactivado';
                $this->jsonResponse([
                    'success' => true,
                    'nuevo_estado' => $nuevoEstado,
                    'mensaje' => $mensaje
                ]);
            }
            
        } catch (Exception $e) {
            $this->handleProgramasError($e, 'Error al cambiar el estado del programa');
        }
    }
    
    /**
     * Exportar programas
     */
    public function export() {
        // Verificar permisos
        if (!$this->hasPermission('programas_export')) {
            $this->jsonResponse(['error' => 'No tienes permisos para exportar'], 403);
            return;
        }
        
        try {
            $filtros = $this->getExportFiltros();
            $formato = $_GET['formato'] ?? 'excel';
            
            $datos = $this->programaModel->exportar($filtros);
            
            if ($formato === 'excel') {
                $this->exportToExcel($datos, 'programas');
            } else {
                $this->exportToPDF($datos, 'programas');
            }
            
        } catch (Exception $e) {
            $this->handleProgramasError($e, 'Error al exportar programas');
        }
    }
    
    /**
     * Procesar formulario de programa
     */
    private function procesarFormulario($id = null) {
        // Verificar token CSRF
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            return ['errors' => ['Token de seguridad inválido']];
        }
        
        // Validar y sanitizar datos
        $data = [
            'codigo' => $this->sanitizeInput($_POST['codigo'] ?? ''),
            'nombre' => $this->sanitizeInput($_POST['nombre'] ?? ''),
            'descripcion' => $this->sanitizeInput($_POST['descripcion'] ?? ''),
            'activo' => intval($_POST['activo'] ?? 1)
        ];
        
        // Validaciones
        $errors = [];
        
        if (empty($data['codigo'])) {
            $errors[] = 'El código es obligatorio';
        } elseif (strlen($data['codigo']) > 10) {
            $errors[] = 'El código no puede tener más de 10 caracteres';
        }
        
        if (empty($data['nombre'])) {
            $errors[] = 'El nombre es obligatorio';
        } elseif (strlen($data['nombre']) > 100) {
            $errors[] = 'El nombre no puede tener más de 100 caracteres';
        }
        
        if (strlen($data['descripcion']) > 500) {
            $errors[] = 'La descripción no puede tener más de 500 caracteres';
        }
        
        // Verificar código único
        if (empty($errors)) {
            $existeCodigo = $this->programaModel->existeCodigo($data['codigo'], $id);
            if ($existeCodigo) {
                $errors[] = 'Ya existe un programa con ese código';
            }
        }
        
        if (!empty($errors)) {
            return ['errors' => $errors];
        }
        
        // Crear o actualizar
        if ($id) {
            $result = $this->programaModel->update($id, $data);
            return isset($result['errors']) ? $result : ['success' => true, 'cambios' => $data];
        } else {
            return $this->programaModel->create($data);
        }
    }
    
    /**
     * Obtener filtros para la lista
     */
    private function getFiltros() {
        return [
            'buscar' => $_GET['buscar'] ?? '',
            'activo' => $_GET['activo'] ?? '',
            'orden' => $_GET['orden'] ?? 'nombre',
            'direccion' => $_GET['direccion'] ?? 'ASC',
            'page' => intval($_GET['page'] ?? 1),
            'per_page' => 20
        ];
    }
    
    /**
     * Obtener estadísticas de programas
     */
    private function getEstadisticas() {
        return [
            'total' => $this->programaModel->count(),
            'activos' => $this->programaModel->count(['activo' => 1]),
            'inactivos' => $this->programaModel->count(['activo' => 0]),
            'con_cursos' => $this->programaModel->countConCursos()
        ];
    }
    
    /**
     * Obtener filtros para exportación
     */
    private function getExportFiltros() {
        return [
            'buscar' => $_GET['buscar'] ?? '',
            'activo' => $_GET['activo'] ?? '',
            'incluir_cursos' => $_GET['incluir_cursos'] ?? false
        ];
    }
    
    /**
     * Exportar a Excel
     */
    private function exportToExcel($datos, $tipo) {
        require_once __DIR__ . '/../utils/ExportHelper.php';
        
        $prepared = ExportHelper::prepareProgramasData($datos);
        ExportHelper::exportToExcel(
            $prepared['datos'], 
            'programas', 
            'Reporte de Programas - ' . date('d/m/Y'), 
            $prepared['encabezados']
        );
    }
    
    /**
     * Exportar a PDF
     */
    private function exportToPDF($datos, $tipo) {
        require_once __DIR__ . '/../utils/ExportHelper.php';
        
        $prepared = ExportHelper::prepareProgramasData($datos);
        ExportHelper::exportToPDF(
            $prepared['datos'], 
            'programas', 
            'Reporte de Programas - ' . date('d/m/Y'), 
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
    protected function handleProgramasError($exception, $userMessage = 'Ha ocurrido un error') {
        // Log del error
        error_log($exception->getMessage());
        
        if ($this->isAjaxRequest()) {
            $this->jsonResponse(['error' => $userMessage], 500);
        } else {
            $this->setFlashMessage($userMessage, 'error');
            $this->redirect('index.php?page=programas');
        }
    }
}