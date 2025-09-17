<?php
require_once __DIR__ . '/BaseModel.php';

/**
 * Modelo Asistencia
 * Maneja el registro de asistencia de estudiantes a las sesiones
 */
class Asistencia extends BaseModel {
    protected $table = 'asistencias';
    protected $fillable = ['sesion_id', 'estudiante_id', 'presente', 'hora_registro', 'ip_address', 'user_agent', 'observaciones'];
    
    /**
     * Registrar asistencia
     */
    public function registrar($data) {
        // Validar datos requeridos
        $errors = $this->validate($data, [
            'sesion_id' => 'required',
            'estudiante_id' => 'required',
            'presente' => 'required'
        ]);
        
        if (!empty($errors)) {
            return ['errors' => $errors];
        }
        
        // Verificar que la sesión existe y está activa
        $sesionModel = new Sesion();
        $sesion = $sesionModel->find($data['sesion_id']);
        
        if (!$sesion) {
            return ['errors' => ['sesion' => 'Sesión no encontrada']];
        }
        
        if ($sesion['estado'] !== 'activa') {
            return ['errors' => ['sesion' => 'La sesión no está activa para tomar asistencia']];
        }
        
        // Verificar que el estudiante existe
        if (!$this->estudianteExists($data['estudiante_id'])) {
            return ['errors' => ['estudiante' => 'Estudiante no encontrado']];
        }
        
        // Verificar que el estudiante está inscrito en el curso
        if (!$this->estudianteInscritoEnCurso($data['estudiante_id'], $sesion['curso_id'])) {
            return ['errors' => ['inscripcion' => 'El estudiante no está inscrito en este curso']];
        }
        
        // Verificar si ya existe un registro de asistencia
        if ($this->asistenciaExists($data['sesion_id'], $data['estudiante_id'])) {
            return ['errors' => ['duplicado' => 'Ya existe un registro de asistencia para este estudiante en esta sesión']];
        }
        
        // Agregar datos adicionales
        $data['hora_registro'] = date('Y-m-d H:i:s');
        $data['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? null;
        $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $asistenciaId = parent::create($data);
        
        if ($asistenciaId) {
            $this->logActivity('registrar_asistencia', $asistenciaId, null, $data);
        }
        
        return $asistenciaId;
    }
    
    /**
     * Actualizar asistencia
     */
    public function actualizar($id, $data) {
        $oldData = $this->find($id);
        
        if (!$oldData) {
            return ['errors' => ['asistencia' => 'Registro de asistencia no encontrado']];
        }
        
        // Solo permitir actualizar ciertos campos
        $allowedFields = ['presente', 'observaciones'];
        $filteredData = array_intersect_key($data, array_flip($allowedFields));
        
        if (empty($filteredData)) {
            return ['errors' => ['datos' => 'No hay datos válidos para actualizar']];
        }
        
        $success = parent::update($id, $filteredData);
        
        if ($success) {
            $this->logActivity('actualizar_asistencia', $id, $oldData, $filteredData);
        }
        
        return $success;
    }
    
    /**
     * Obtener asistencias de una sesión
     */
    public function getBySesion($sesionId) {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare("
            SELECT 
                a.*,
                e.nombre as estudiante_nombre,
                e.documento as estudiante_documento,
                e.codigo as estudiante_codigo,
                e.email as estudiante_email
            FROM asistencias a
            INNER JOIN estudiantes e ON a.estudiante_id = e.id
            WHERE a.sesion_id = ?
            ORDER BY e.nombre
        ");
        
        $stmt->bind_param("i", $sesionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $asistencias = [];
        while ($row = $result->fetch_assoc()) {
            $asistencias[] = $row;
        }
        
        $stmt->close();
        return $asistencias;
    }
    
    /**
     * Obtener asistencias de un estudiante
     */
    public function getByEstudiante($estudianteId, $cursoId = null) {
        $conn = $this->getConnection();
        
        $sql = "
            SELECT 
                a.*,
                s.fecha,
                s.hora_inicio,
                s.descripcion as sesion_descripcion,
                c.nombre as curso_nombre,
                c.codigo as curso_codigo
            FROM asistencias a
            INNER JOIN sesiones s ON a.sesion_id = s.id
            INNER JOIN cursos c ON s.curso_id = c.id
            WHERE a.estudiante_id = ?
        ";
        
        $params = [$estudianteId];
        $types = "i";
        
        if ($cursoId) {
            $sql .= " AND c.id = ?";
            $params[] = $cursoId;
            $types .= "i";
        }
        
        $sql .= " ORDER BY s.fecha DESC, s.hora_inicio DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $asistencias = [];
        while ($row = $result->fetch_assoc()) {
            $asistencias[] = $row;
        }
        
        $stmt->close();
        return $asistencias;
    }
    
    /**
     * Obtener reporte de asistencia por curso
     */
    public function getReporteByCurso($cursoId, $fechaInicio = null, $fechaFin = null) {
        $conn = $this->getConnection();
        
        $sql = "
            SELECT 
                e.id as estudiante_id,
                e.nombre as estudiante_nombre,
                e.documento as estudiante_documento,
                e.codigo as estudiante_codigo,
                COUNT(s.id) as total_sesiones,
                COUNT(a.id) as total_asistencias,
                SUM(CASE WHEN a.presente = 1 THEN 1 ELSE 0 END) as asistencias_presentes,
                SUM(CASE WHEN a.presente = 0 THEN 1 ELSE 0 END) as asistencias_ausentes,
                ROUND((SUM(CASE WHEN a.presente = 1 THEN 1 ELSE 0 END) / COUNT(s.id)) * 100, 2) as porcentaje_asistencia
            FROM estudiantes e
            INNER JOIN cursos_estudiantes ce ON e.id = ce.estudiante_id
            LEFT JOIN sesiones s ON ce.curso_id = s.curso_id AND s.estado = 'finalizada'
            LEFT JOIN asistencias a ON s.id = a.sesion_id AND e.id = a.estudiante_id
            WHERE ce.curso_id = ?
        ";
        
        $params = [$cursoId];
        $types = "i";
        
        if ($fechaInicio) {
            $sql .= " AND (s.fecha IS NULL OR s.fecha >= ?)";
            $params[] = $fechaInicio;
            $types .= "s";
        }
        
        if ($fechaFin) {
            $sql .= " AND (s.fecha IS NULL OR s.fecha <= ?)";
            $params[] = $fechaFin;
            $types .= "s";
        }
        
        $sql .= " GROUP BY e.id, e.nombre, e.documento, e.codigo ORDER BY e.nombre";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reporte = [];
        while ($row = $result->fetch_assoc()) {
            $reporte[] = $row;
        }
        
        $stmt->close();
        return $reporte;
    }
    
    /**
     * Obtener estadísticas de asistencia
     */
    public function getEstadisticas($cursoId = null, $profesorId = null, $fechaInicio = null, $fechaFin = null) {
        $conn = $this->getConnection();
        $stats = [];
        
        $whereClause = "WHERE s.estado = 'finalizada'";
        $params = [];
        $types = "";
        
        if ($cursoId) {
            $whereClause .= " AND s.curso_id = ?";
            $params[] = $cursoId;
            $types .= "i";
        }
        
        if ($profesorId) {
            $whereClause .= " AND c.profesor_id = ?";
            $params[] = $profesorId;
            $types .= "i";
        }
        
        if ($fechaInicio) {
            $whereClause .= " AND s.fecha >= ?";
            $params[] = $fechaInicio;
            $types .= "s";
        }
        
        if ($fechaFin) {
            $whereClause .= " AND s.fecha <= ?";
            $params[] = $fechaFin;
            $types .= "s";
        }
        
        // Total de registros de asistencia
        $sql = "
            SELECT COUNT(*) as total 
            FROM asistencias a 
            INNER JOIN sesiones s ON a.sesion_id = s.id 
            INNER JOIN cursos c ON s.curso_id = c.id 
            {$whereClause}
        ";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_registros'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // Asistencias por estado (presente/ausente)
        $sql = "
            SELECT 
                a.presente,
                COUNT(*) as total
            FROM asistencias a 
            INNER JOIN sesiones s ON a.sesion_id = s.id 
            INNER JOIN cursos c ON s.curso_id = c.id 
            {$whereClause}
            GROUP BY a.presente
        ";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats['presentes'] = 0;
        $stats['ausentes'] = 0;
        
        while ($row = $result->fetch_assoc()) {
            if ($row['presente'] == 1) {
                $stats['presentes'] = $row['total'];
            } else {
                $stats['ausentes'] = $row['total'];
            }
        }
        $stmt->close();
        
        // Calcular porcentaje
        $total = $stats['presentes'] + $stats['ausentes'];
        $stats['porcentaje_asistencia'] = $total > 0 ? round(($stats['presentes'] / $total) * 100, 2) : 0;
        
        // Promedio de asistencia por sesión
        $sql = "
            SELECT 
                AVG(asistencias_por_sesion.total) as promedio
            FROM (
                SELECT 
                    s.id,
                    COUNT(a.id) as total
                FROM sesiones s
                INNER JOIN cursos c ON s.curso_id = c.id
                LEFT JOIN asistencias a ON s.id = a.sesion_id
                {$whereClause}
                GROUP BY s.id
            ) as asistencias_por_sesion
        ";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['promedio_por_sesion'] = round($result->fetch_assoc()['promedio'] ?? 0, 2);
        $stmt->close();
        
        return $stats;
    }
    
    /**
     * Verificar si existe un registro de asistencia
     */
    private function asistenciaExists($sesionId, $estudianteId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT id FROM asistencias WHERE sesion_id = ? AND estudiante_id = ?");
        $stmt->bind_param("ii", $sesionId, $estudianteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    /**
     * Verificar si un estudiante existe
     */
    private function estudianteExists($estudianteId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT id FROM estudiantes WHERE id = ?");
        $stmt->bind_param("i", $estudianteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    /**
     * Verificar si un estudiante está inscrito en un curso
     */
    private function estudianteInscritoEnCurso($estudianteId, $cursoId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT id FROM cursos_estudiantes WHERE estudiante_id = ? AND curso_id = ?");
        $stmt->bind_param("ii", $estudianteId, $cursoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    /**
     * Obtener asistencia por token de sesión y documento de estudiante
     */
    public function getByTokenAndDocumento($token, $documento) {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare("
            SELECT 
                a.*,
                s.descripcion as sesion_descripcion,
                s.fecha,
                s.hora_inicio,
                c.nombre as curso_nombre,
                e.nombre as estudiante_nombre
            FROM asistencias a
            INNER JOIN sesiones s ON a.sesion_id = s.id
            INNER JOIN cursos c ON s.curso_id = c.id
            INNER JOIN estudiantes e ON a.estudiante_id = e.id
            WHERE s.token = ? AND e.documento = ?
        ");
        
        $stmt->bind_param("ss", $token, $documento);
        $stmt->execute();
        $result = $stmt->get_result();
        $asistencia = $result->fetch_assoc();
        $stmt->close();
        
        return $asistencia;
    }
    
    /**
     * Marcar asistencia masiva
     */
    public function marcarAsistenciaMasiva($sesionId, $estudiantes, $presente = true) {
        $conn = $this->getConnection();
        $conn->autocommit(false);
        
        try {
            $registrosCreados = 0;
            
            foreach ($estudiantes as $estudianteId) {
                // Verificar si ya existe el registro
                if (!$this->asistenciaExists($sesionId, $estudianteId)) {
                    $data = [
                        'sesion_id' => $sesionId,
                        'estudiante_id' => $estudianteId,
                        'presente' => $presente ? 1 : 0,
                        'hora_registro' => date('Y-m-d H:i:s'),
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                        'user_agent' => 'Sistema - Marcado Masivo',
                        'observaciones' => 'Marcado masivamente por el sistema'
                    ];
                    
                    if ($this->create($data)) {
                        $registrosCreados++;
                    }
                }
            }
            
            $conn->commit();
            $conn->autocommit(true);
            
            $this->logActivity('marcar_asistencia_masiva', $sesionId, null, [
                'total_estudiantes' => count($estudiantes),
                'registros_creados' => $registrosCreados,
                'presente' => $presente
            ]);
            
            return $registrosCreados;
            
        } catch (Exception $e) {
            $conn->rollback();
            $conn->autocommit(true);
            return false;
        }
    }
    
    /**
     * Exportar asistencias a array para Excel/PDF
     */
    public function exportarAsistencias($cursoId, $fechaInicio = null, $fechaFin = null) {
        $conn = $this->getConnection();
        
        $sql = "
            SELECT 
                s.fecha,
                s.hora_inicio,
                s.descripcion as sesion,
                e.nombre as estudiante,
                e.documento,
                e.codigo as codigo_estudiante,
                CASE WHEN a.presente = 1 THEN 'Presente' ELSE 'Ausente' END as asistencia,
                a.hora_registro,
                a.observaciones
            FROM sesiones s
            INNER JOIN cursos c ON s.curso_id = c.id
            LEFT JOIN asistencias a ON s.id = a.sesion_id
            LEFT JOIN estudiantes e ON a.estudiante_id = e.id
            WHERE s.curso_id = ? AND s.estado = 'finalizada'
        ";
        
        $params = [$cursoId];
        $types = "i";
        
        if ($fechaInicio) {
            $sql .= " AND s.fecha >= ?";
            $params[] = $fechaInicio;
            $types .= "s";
        }
        
        if ($fechaFin) {
            $sql .= " AND s.fecha <= ?";
            $params[] = $fechaFin;
            $types .= "s";
        }
        
        $sql .= " ORDER BY s.fecha, s.hora_inicio, e.nombre";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $datos = [];
        while ($row = $result->fetch_assoc()) {
            $datos[] = $row;
        }
        
        $stmt->close();
        return $datos;
    }
    
    /**
     * Obtener resumen de asistencia por estudiante
     */
    public function getResumenPorEstudiante($cursoId) {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare("
            SELECT 
                e.id,
                e.nombre,
                e.documento,
                e.codigo,
                COUNT(DISTINCT s.id) as total_sesiones,
                COUNT(a.id) as registros_asistencia,
                SUM(CASE WHEN a.presente = 1 THEN 1 ELSE 0 END) as presentes,
                SUM(CASE WHEN a.presente = 0 THEN 1 ELSE 0 END) as ausentes,
                ROUND(
                    (SUM(CASE WHEN a.presente = 1 THEN 1 ELSE 0 END) / COUNT(DISTINCT s.id)) * 100, 
                    2
                ) as porcentaje_asistencia
            FROM estudiantes e
            INNER JOIN cursos_estudiantes ce ON e.id = ce.estudiante_id
            LEFT JOIN sesiones s ON ce.curso_id = s.curso_id AND s.estado = 'finalizada'
            LEFT JOIN asistencias a ON s.id = a.sesion_id AND e.id = a.estudiante_id
            WHERE ce.curso_id = ?
            GROUP BY e.id, e.nombre, e.documento, e.codigo
            ORDER BY e.nombre
        ");
        
        $stmt->bind_param("i", $cursoId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $resumen = [];
        while ($row = $result->fetch_assoc()) {
            $resumen[] = $row;
        }
        
        $stmt->close();
        return $resumen;
    }
}