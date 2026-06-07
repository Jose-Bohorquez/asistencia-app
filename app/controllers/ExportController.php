<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../utils/ExportHelper.php';

/**
 * Controlador para manejar todas las exportaciones
 * Centraliza la lógica de exportación con seguridad mejorada
 */
class ExportController extends BaseController {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Manejar solicitudes de exportación
     */
    public function handleRequest() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'export';
        
        try {
            switch ($action) {
                case 'export':
                    $this->export();
                    break;
                case 'asistencias':
                    $this->exportAsistencias();
                    break;
                default:
                    $this->jsonResponse(['error' => 'Acción no válida'], 400);
            }
        } catch (Exception $e) {
            $this->handleError($e, 'Error en exportación');
        }
    }
    
    /**
     * Exportar datos generales
     */
    private function export() {
        $tipo = $_GET['tipo'] ?? '';
        $formato = $_GET['formato'] ?? 'excel';
        
        // Validar tipo de exportación
        $tiposPermitidos = ['sesiones', 'usuarios', 'programas', 'cursos', 'estudiantes', 'asistencias'];
        if (!in_array($tipo, $tiposPermitidos)) {
            $this->jsonResponse(['error' => 'Tipo de exportación no válido'], 400);
            return;
        }
        
        // Validar formato
        $formatosPermitidos = ['excel', 'pdf', 'csv'];
        if (!in_array($formato, $formatosPermitidos)) {
            $this->jsonResponse(['error' => 'Formato no válido'], 400);
            return;
        }
        
        // Verificar permisos específicos
        if (!$this->hasPermission('reportes_export')) {
            $this->jsonResponse(['error' => 'No tienes permisos para exportar'], 403);
            return;
        }
        
        // Obtener datos según el tipo
        $datos = $this->obtenerDatos($tipo);
        
        if (empty($datos)) {
            $this->jsonResponse(['error' => 'No hay datos para exportar'], 404);
            return;
        }
        
        // Exportar según el formato
        $this->realizarExportacion($datos, $tipo, $formato);
    }
    
    /**
     * Exportar asistencias de una sesión específica
     */
    private function exportAsistencias() {
        $sesionId = intval($_GET['sesion_id'] ?? 0);
        $formato = $_GET['format'] ?? 'excel';
        
        if ($sesionId <= 0) {
            $this->jsonResponse(['error' => 'ID de sesión inválido'], 400);
            return;
        }
        
        // Verificar permisos específicos del curso si es profesor
        if ($this->currentUser['rol'] === 'profesor') {
            $stmt = $this->db->prepare(
                "SELECT s.id, c.profesor_id FROM sesiones s
                 INNER JOIN cursos c ON s.curso_id = c.id
                 WHERE s.id = ? AND c.profesor_id = ?
                 LIMIT 1"
            );
            $stmt->bind_param('ii', $sesionId, $this->currentUser['id']);
            $stmt->execute();
            $sesionOk = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if (!$sesionOk) {
                $this->jsonResponse(['error' => 'No tienes permisos para exportar esta sesión'], 403);
                return;
            }
        }
        
        // Obtener datos de asistencia
        $datos = $this->obtenerAsistenciasPorSesion($sesionId);
        
        if (empty($datos)) {
            $this->jsonResponse(['error' => 'No hay asistencias para exportar'], 404);
            return;
        }
        
        // Exportar
        $this->realizarExportacion($datos, 'asistencias', $formato);
    }
    
    /**
     * Obtener datos según el tipo
     */
    private function obtenerDatos($tipo) {
        switch ($tipo) {
            case 'sesiones':
                require_once __DIR__ . '/../models/Sesion.php';
                $model = new Sesion();
                $filtros = $this->currentUser['rol'] === 'profesor' 
                    ? ['profesor_id' => $this->currentUser['id']] 
                    : [];
                return $model->getAll($filtros);
                
            case 'usuarios':
                if (!$this->hasPermission('usuarios_view')) {
                    throw new Exception('Sin permisos para exportar usuarios');
                }
                require_once __DIR__ . '/../models/Usuario.php';
                $model = new Usuario();
                return $model->getAll();
                
            case 'programas':
                if (!$this->hasPermission('programas_view')) {
                    throw new Exception('Sin permisos para exportar programas');
                }
                require_once __DIR__ . '/../models/Programa.php';
                $model = new Programa();
                return $model->getAll();
                
            case 'cursos':
                if (!$this->hasPermission('cursos_view')) {
                    throw new Exception('Sin permisos para exportar cursos');
                }
                require_once __DIR__ . '/../models/Curso.php';
                $model = new Curso();
                return $this->currentUser['rol'] === 'profesor' 
                    ? $model->getByProfesor($this->currentUser['id'])
                    : $model->getAll();
                    
            case 'estudiantes':
                if (!$this->hasPermission('estudiantes_view')) {
                    throw new Exception('Sin permisos para exportar estudiantes');
                }
                require_once __DIR__ . '/../models/Estudiante.php';
                $model = new Estudiante();
                $cursoId = $_GET['curso_id'] ?? null;
                return $cursoId ? $model->getByCurso($cursoId) : $model->getAll();
                
            case 'asistencias':
                if (!$this->hasPermission('asistencias_view')) {
                    throw new Exception('Sin permisos para exportar asistencias');
                }
                require_once __DIR__ . '/../models/Asistencia.php';
                $model = new Asistencia();
                $cursoId = $_GET['curso_id'] ?? null;
                $fechaInicio = $_GET['fecha_inicio'] ?? null;
                $fechaFin = $_GET['fecha_fin'] ?? null;
                return $model->getAsistenciasByFilters($cursoId, $fechaInicio, $fechaFin);
                
            default:
                throw new Exception('Tipo de exportación no válido');
        }
    }
    
    /**
     * Obtener asistencias por sesión
     */
    private function obtenerAsistenciasPorSesion($sesionId) {
        require_once __DIR__ . '/../models/Asistencia.php';
        $model = new Asistencia();
        return $model->getBySesion($sesionId);
    }
    
    /**
     * Realizar la exportación según el formato
     */
    private function realizarExportacion($datos, $tipo, $formato) {
        try {
            switch ($formato) {
                case 'excel':
                    $this->exportarExcel($datos, $tipo);
                    break;
                case 'pdf':
                    $this->exportarPDF($datos, $tipo);
                    break;
                case 'csv':
                    $this->exportarCSV($datos, $tipo);
                    break;
                default:
                    throw new Exception('Formato no soportado');
            }
        } catch (Exception $e) {
            error_log('Error en exportación: ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Error al generar el archivo de exportación'], 500);
        }
    }
    
    /**
     * Exportar a Excel
     */
    private function exportarExcel($datos, $tipo) {
        $prepared = $this->prepararDatos($datos, $tipo);
        $titulo = $this->obtenerTituloReporte($tipo);
        
        ExportHelper::exportToExcel(
            $prepared['datos'],
            $tipo,
            $titulo,
            $prepared['encabezados']
        );
    }
    
    /**
     * Exportar a PDF
     */
    private function exportarPDF($datos, $tipo) {
        $prepared = $this->prepararDatos($datos, $tipo);
        $titulo = $this->obtenerTituloReporte($tipo);
        
        ExportHelper::exportToPDF(
            $prepared['datos'],
            $tipo,
            $titulo,
            $prepared['encabezados']
        );
    }
    
    /**
     * Exportar a CSV
     */
    private function exportarCSV($datos, $tipo) {
        $prepared = $this->prepararDatos($datos, $tipo);
        
        ExportHelper::exportToCSV(
            $prepared['datos'],
            $tipo,
            $prepared['encabezados']
        );
    }
    
    /**
     * Preparar datos según el tipo
     */
    private function prepararDatos($datos, $tipo) {
        switch ($tipo) {
            case 'sesiones':
                return ExportHelper::prepareSesionesData($datos);
            case 'usuarios':
                return ExportHelper::prepareUsuariosData($datos);
            case 'programas':
                return ExportHelper::prepareProgramasData($datos);
            case 'cursos':
                return ExportHelper::prepareCursosData($datos);
            case 'estudiantes':
                return ExportHelper::prepareEstudiantesData($datos);
            case 'asistencias':
                return ExportHelper::prepareAsistenciaData($datos);
            default:
                throw new Exception('Tipo de datos no soportado para preparación');
        }
    }
    
    /**
     * Obtener título del reporte
     */
    private function obtenerTituloReporte($tipo) {
        $titulos = [
            'sesiones' => 'Reporte de Sesiones',
            'usuarios' => 'Reporte de Usuarios',
            'programas' => 'Reporte de Programas',
            'cursos' => 'Reporte de Cursos',
            'estudiantes' => 'Reporte de Estudiantes',
            'asistencias' => 'Reporte de Asistencia'
        ];
        
        return ($titulos[$tipo] ?? 'Reporte') . ' - ' . date('d/m/Y');
    }
    
    /**
     * Verificar permisos
     */
    protected function hasPermission($permission) {
        return $this->middlewareManager->checkPermission($permission);
    }
}
