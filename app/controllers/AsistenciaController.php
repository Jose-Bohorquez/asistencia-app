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
        $tokenRaw = trim($_GET['sesion_id'] ?? $_GET['token'] ?? '');

        // PRG: mostrar confirmación después de registro exitoso
        if (!empty($_GET['ok']) && $_GET['ok'] === '1') {
            $sesion = $tokenRaw ? $this->getSesionInfo($tokenRaw) : null;
            $this->render('asistencia/registro_ok', [
                'page_title' => 'Asistencia registrada',
                'sesion'     => $sesion,
            ]);
            return;
        }

        $error      = '';
        $estadoInfo = ''; // Mensaje informativo (no bloquea la vista)
        $sesion     = null;

        if ($tokenRaw === '') {
            $error = 'enlace_invalido';
        } else {
            try {
                $sesion = $this->getSesionInfo($tokenRaw);

                if (!$sesion) {
                    $error = 'no_existe';
                } elseif ($sesion['estado'] === 'finalizada') {
                    $error = 'finalizada';
                } elseif ($sesion['estado'] === 'cancelada') {
                    $error = 'cancelada';
                }
                // estado='activa' → el formulario se muestra siempre.
                // La hora es referencial; el docente controla el ciclo con estado.
            } catch (Exception $e) {
                $error = 'error_sistema';
            }
        }

        // Procesar formulario solo si la sesión está activa
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sesion && $sesion['estado'] === 'activa') {
            $result = $this->procesarRegistroAsistencia($sesion);

            if (isset($result['error'])) {
                $error = $result['error'];
            } else {
                $this->redirect(
                    'index.php?page=asistencia&sesion_id=' . intval($sesion['id']) . '&ok=1'
                );
                return;
            }
        }

        $this->render('asistencia/registro', [
            'page_title' => 'Registrar Asistencia',
            'sesion'     => $sesion,
            'error'      => $error,
            'csrf_token' => $this->generateCSRFToken(),
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
        
        // Permitir registro 1 hora antes y hasta 2 horas después del fin
        $inicioPermitido = clone $fechaSesion;
        $inicioPermitido->modify('-1 hour');
        
        $finPermitido = $horaFin ? $horaFin : (clone $fechaSesion)->modify('+2 hours');
        $finPermitido->modify('+2 hours');
        
        return $now >= $inicioPermitido && $now <= $finPermitido;
    }
    
    /**
     * Procesar registro de asistencia
     */
    private function procesarRegistroAsistencia($sesion) {
        // CSRF
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            return ['error' => 'Token de seguridad inválido. Recarga la página e inténtalo de nuevo.'];
        }

        // La fuente de verdad del ciclo de vida es el estado de la sesión,
        // que el docente gestiona. No bloqueamos por hora; solo verificamos estado.
        if (!$sesion || $sesion['estado'] !== 'activa') {
            return ['error' => 'La sesión ya no está activa. Consulta con el docente.'];
        }

        // Sanitizar campos de texto
        $documento = $this->sanitizeInput($_POST['documento']  ?? '');
        $nombre    = $this->sanitizeInput($_POST['nombre']     ?? '');
        $codigo    = $this->sanitizeInput($_POST['codigo']     ?? '');
        $telefono  = $this->sanitizeInput($_POST['telefono']   ?? '');
        $direccion = $this->sanitizeInput($_POST['direccion']  ?? '');
        // El campo del form se llama 'correo'; lo mapeamos a 'email' (clave del modelo)
        $email     = $this->sanitizeInput($_POST['correo']     ?? '');

        if (empty($documento) || empty($nombre)) {
            return ['error' => 'Por favor, complete los campos obligatorios (documento y nombre).'];
        }

        // Validar firma (obligatoria)
        $firma = $_POST['firma'] ?? '';
        if (empty($firma)) {
            return ['error' => 'La firma es obligatoria. Por favor, firme en el recuadro antes de enviar.'];
        }
        if (!str_starts_with($firma, 'data:image/png;base64,')) {
            return ['error' => 'El formato de la firma no es válido.'];
        }
        $firmaLen = strlen($firma);
        if ($firmaLen < 1000 || $firmaLen > 700000) {
            return ['error' => 'La firma parece estar vacía o es demasiado grande. Vuelva a firmar.'];
        }
        $firmaHash = hash('sha256', $firma);

        // Guardar firma como archivo PNG en disco (no como base64 en BD)
        $firmaBase64Data = str_replace('data:image/png;base64,', '', $firma);
        $firmaBinary     = base64_decode($firmaBase64Data);
        $uploadDir       = dirname(__DIR__, 2) . '/public/uploads/firmas/' . date('Y/m');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $firmaFilename = 'firma_' . time() . '_' . bin2hex(random_bytes(4)) . '.png';
        $firmaRelPath  = 'uploads/firmas/' . date('Y/m') . '/' . $firmaFilename;
        $firmaAbsPath  = $uploadDir . '/' . $firmaFilename;
        if (file_put_contents($firmaAbsPath, $firmaBinary) === false) {
            return ['error' => 'No se pudo guardar la firma. Intente nuevamente.'];
        }

        try {
            // Buscar o crear estudiante
            $estudiante = $this->estudianteModel->findByDocumento($documento);

            if (!$estudiante) {
                $estudianteData = [
                    'documento' => $documento,
                    'nombre'    => $nombre,
                    'codigo'    => $codigo,
                    'telefono'  => $telefono,
                    'direccion' => $direccion,
                    'email'     => $email,
                    'activo'    => 1,
                ];
                $result = $this->estudianteModel->create($estudianteData);
                if (isset($result['errors'])) {
                    return ['error' => 'Error al registrar el estudiante. Intente nuevamente.'];
                }
                $estudiante_id = $result;
            } else {
                $estudiante_id = $estudiante['id'];
                // Actualizar datos si cambiaron (no tocar email si el form lo dejó vacío)
                $updateData = [];
                if ($estudiante['nombre']    !== $nombre)    $updateData['nombre']    = $nombre;
                if ($estudiante['telefono'] !== $telefono)  $updateData['telefono']  = $telefono;
                if (!empty($direccion) && $estudiante['direccion'] !== $direccion) $updateData['direccion'] = $direccion;
                if (!empty($email) && $estudiante['email'] !== $email) $updateData['email'] = $email;
                if (!empty($codigo) && $estudiante['codigo'] !== $codigo) $updateData['codigo'] = $codigo;
                if (!empty($updateData)) {
                    $this->estudianteModel->update($estudiante_id, $updateData);
                }
            }

            // Verificar si ya registró asistencia (unicidad por sesión + estudiante)
            if ($this->asistenciaModel->yaRegistroAsistencia($sesion['id'], $estudiante_id)) {
                return ['error' => 'Ya has registrado tu asistencia para esta sesión.'];
            }

            // Registrar asistencia — firma guardada en disco, no en BD
            $asistenciaData = [
                'sesion_id'     => $sesion['id'],
                'estudiante_id' => $estudiante_id,
                'hora_registro' => date('Y-m-d H:i:s'),
                'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent'    => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'firma'         => null,
                'firma_path'    => $firmaRelPath,
                'firma_hash'    => $firmaHash,
            ];

            $result = $this->asistenciaModel->create($asistenciaData);
            if (isset($result['errors'])) {
                return ['error' => 'Error al guardar la asistencia. Intente nuevamente.'];
            }

            // Inscribir al curso si aún no está inscrito
            if (!$this->estudianteModel->estaInscritoEnCurso($estudiante_id, $sesion['curso_id'])) {
                $this->estudianteModel->inscribirEnCurso($estudiante_id, $sesion['curso_id']);
            }

            $this->logActivity('asistencia_registrada', $result, $estudiante_id, [
                'sesion_id' => $sesion['id'],
                'curso_id'  => $sesion['curso_id'],
            ]);

            return ['success' => true];

        } catch (Exception $e) {
            error_log('procesarRegistroAsistencia error: ' . $e->getMessage());
            return ['error' => 'Error al procesar el registro. Intente nuevamente.'];
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
            'page' => max(1, intval($_GET['p'] ?? 1)),
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
        $this->redirect('index.php?page=dashboard');
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