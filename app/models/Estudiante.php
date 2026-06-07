<?php
require_once __DIR__ . '/BaseModel.php';

/**
 * Modelo Estudiante
 * Maneja la gestión de estudiantes y su inscripción en cursos
 */
class Estudiante extends BaseModel {
    protected $table = 'estudiantes';
    protected $fillable = ['nombre', 'documento', 'codigo', 'email', 'telefono', 'direccion', 'fecha_nacimiento', 'activo'];
    
    /**
     * Crear estudiante con validaciones
     */
    public function create($data) {
        // Validar datos: email es opcional (estudiantes pueden no tenerlo al momento del registro)
        $errors = $this->validate($data, [
            'nombre'    => 'required|max:100',
            'documento' => 'required|max:20',
        ]);

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        // Verificar que el documento no exista
        if ($this->documentoExists($data['documento'])) {
            return ['errors' => ['documento' => 'El documento ya está registrado']];
        }

        // Verificar código único solo si se proporcionó
        if (!empty($data['codigo']) && $this->codigoExists($data['codigo'])) {
            return ['errors' => ['codigo' => 'El código de estudiante ya existe']];
        }

        // Verificar email único solo si se proporcionó
        if (!empty($data['email']) && $this->emailExists($data['email'])) {
            return ['errors' => ['email' => 'El email ya está registrado']];
        }
        
        // Establecer activo por defecto
        if (!isset($data['activo'])) {
            $data['activo'] = 1;
        }
        
        $estudianteId = parent::create($data);
        
        if ($estudianteId) {
            $this->logActivity('create', $estudianteId, null, $data);
        }
        
        return $estudianteId;
    }
    
    /**
     * Actualizar estudiante
     */
    public function update($id, $data) {
        $oldData = $this->find($id);
        
        // Validar datos
        $errors = $this->validate($data, [
            'nombre' => 'required|max:100',
            'documento' => 'required|max:20',
            'codigo' => 'required|max:20',
            'email' => 'required|email|max:100'
        ]);
        
        if (!empty($errors)) {
            return ['errors' => $errors];
        }
        
        // Verificar documento único
        if ($this->documentoExists($data['documento'], $id)) {
            return ['errors' => ['documento' => 'El documento ya está registrado']];
        }
        
        // Verificar código único
        if ($this->codigoExists($data['codigo'], $id)) {
            return ['errors' => ['codigo' => 'El código de estudiante ya existe']];
        }
        
        // Verificar email único
        if ($this->emailExists($data['email'], $id)) {
            return ['errors' => ['email' => 'El email ya está registrado']];
        }
        
        $success = parent::update($id, $data);
        
        if ($success) {
            $this->logActivity('update', $id, $oldData, $data);
        }
        
        return $success;
    }
    
    /**
     * Eliminar estudiante (soft delete)
     */
    public function delete($id) {
        $oldData = $this->find($id);
        
        // En lugar de eliminar, desactivar el estudiante
        $success = $this->update($id, ['activo' => 0]);
        
        if ($success) {
            $this->logActivity('delete', $id, $oldData);
        }
        
        return $success;
    }
    
    /**
     * Obtener estudiante por documento
     */
    public function getByDocumento($documento) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT * FROM estudiantes WHERE documento = ? AND activo = 1");
        $stmt->bind_param("s", $documento);
        $stmt->execute();
        $result = $stmt->get_result();
        $estudiante = $result->fetch_assoc();
        $stmt->close();
        
        return $estudiante;
    }
    
    /**
     * Obtener estudiante por código
     */
    public function getByCodigo($codigo) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT * FROM estudiantes WHERE codigo = ? AND activo = 1");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $result = $stmt->get_result();
        $estudiante = $result->fetch_assoc();
        $stmt->close();
        
        return $estudiante;
    }
    
    /**
     * Obtener cursos en los que está inscrito un estudiante
     */
    public function getCursos($estudianteId) {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare("
            SELECT 
                c.*,
                p.nombre as programa_nombre,
                u.nombre as profesor_nombre,
                ce.fecha_inscripcion
            FROM cursos c
            INNER JOIN cursos_estudiantes ce ON c.id = ce.curso_id
            INNER JOIN programas p ON c.programa_id = p.id
            INNER JOIN usuarios u ON c.profesor_id = u.id
            WHERE ce.estudiante_id = ? AND c.activo = 1
            ORDER BY c.nombre
        ");
        
        $stmt->bind_param("i", $estudianteId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $cursos = [];
        while ($row = $result->fetch_assoc()) {
            $cursos[] = $row;
        }
        
        $stmt->close();
        return $cursos;
    }
    
    /**
     * Obtener historial de asistencia de un estudiante
     */
    public function getHistorialAsistencia($estudianteId, $cursoId = null) {
        $conn = $this->getConnection();
        
        $sql = "
            SELECT 
                s.fecha,
                s.hora_inicio,
                s.descripcion as sesion_descripcion,
                c.nombre as curso_nombre,
                c.codigo as curso_codigo,
                a.presente,
                a.hora_registro,
                a.observaciones
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
        
        $historial = [];
        while ($row = $result->fetch_assoc()) {
            $historial[] = $row;
        }
        
        $stmt->close();
        return $historial;
    }
    
    /**
     * Obtener estadísticas de asistencia de un estudiante
     */
    public function getEstadisticasAsistencia($estudianteId, $cursoId = null) {
        $conn = $this->getConnection();
        
        $whereClause = "WHERE a.estudiante_id = ?";
        $params = [$estudianteId];
        $types = "i";
        
        if ($cursoId) {
            $whereClause .= " AND c.id = ?";
            $params[] = $cursoId;
            $types .= "i";
        }
        
        // Estadísticas generales
        $sql = "
            SELECT 
                COUNT(*) as total_registros,
                SUM(CASE WHEN a.presente = 1 THEN 1 ELSE 0 END) as presentes,
                SUM(CASE WHEN a.presente = 0 THEN 1 ELSE 0 END) as ausentes,
                ROUND(
                    (SUM(CASE WHEN a.presente = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 
                    2
                ) as porcentaje_asistencia
            FROM asistencias a
            INNER JOIN sesiones s ON a.sesion_id = s.id
            INNER JOIN cursos c ON s.curso_id = c.id
            {$whereClause}
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
        
        // Estadísticas por curso (si no se especifica un curso)
        if (!$cursoId) {
            $sql = "
                SELECT 
                    c.nombre as curso_nombre,
                    c.codigo as curso_codigo,
                    COUNT(*) as total_registros,
                    SUM(CASE WHEN a.presente = 1 THEN 1 ELSE 0 END) as presentes,
                    ROUND(
                        (SUM(CASE WHEN a.presente = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 
                        2
                    ) as porcentaje_asistencia
                FROM asistencias a
                INNER JOIN sesiones s ON a.sesion_id = s.id
                INNER JOIN cursos c ON s.curso_id = c.id
                WHERE a.estudiante_id = ?
                GROUP BY c.id, c.nombre, c.codigo
                ORDER BY c.nombre
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $estudianteId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $stats['por_curso'] = [];
            while ($row = $result->fetch_assoc()) {
                $stats['por_curso'][] = $row;
            }
            
            $stmt->close();
        }
        
        return $stats;
    }
    
    /**
     * Verificar si un documento existe
     */
    public function documentoExists($documento, $excludeId = null) {
        $conn = $this->getConnection();
        $sql = "SELECT id FROM estudiantes WHERE documento = ?";
        
        if ($excludeId) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $conn->prepare($sql);
        
        if ($excludeId) {
            $stmt->bind_param("si", $documento, $excludeId);
        } else {
            $stmt->bind_param("s", $documento);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    /**
     * Verificar si un código existe
     */
    public function codigoExists($codigo, $excludeId = null) {
        $conn = $this->getConnection();
        $sql = "SELECT id FROM estudiantes WHERE codigo = ?";
        
        if ($excludeId) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $conn->prepare($sql);
        
        if ($excludeId) {
            $stmt->bind_param("si", $codigo, $excludeId);
        } else {
            $stmt->bind_param("s", $codigo);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    /**
     * Verificar si un email existe
     */
    public function emailExists($email, $excludeId = null) {
        $conn = $this->getConnection();
        $sql = "SELECT id FROM estudiantes WHERE email = ?";
        
        if ($excludeId) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $conn->prepare($sql);
        
        if ($excludeId) {
            $stmt->bind_param("si", $email, $excludeId);
        } else {
            $stmt->bind_param("s", $email);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    /**
     * Buscar estudiantes
     */
    public function search($term) {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare("
            SELECT * FROM estudiantes 
            WHERE activo = 1 AND (
                nombre LIKE ? OR 
                documento LIKE ? OR 
                codigo LIKE ? OR 
                email LIKE ?
            )
            ORDER BY nombre
        ");
        
        $searchTerm = "%{$term}%";
        $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $estudiantes = [];
        while ($row = $result->fetch_assoc()) {
            $estudiantes[] = $row;
        }
        
        $stmt->close();
        return $estudiantes;
    }
    
    /**
     * Obtener estudiantes activos
     */
    public function getActivos() {
        return $this->all(['activo' => 1], 'nombre');
    }
    
    /**
     * Obtener estudiantes por curso
     */
    public function getByCurso($cursoId) {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare("
            SELECT 
                e.*,
                ce.fecha_inscripcion
            FROM estudiantes e
            INNER JOIN cursos_estudiantes ce ON e.id = ce.estudiante_id
            WHERE ce.curso_id = ? AND e.activo = 1
            ORDER BY e.nombre
        ");
        
        $stmt->bind_param("i", $cursoId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $estudiantes = [];
        while ($row = $result->fetch_assoc()) {
            $estudiantes[] = $row;
        }
        
        $stmt->close();
        return $estudiantes;
    }
    
    /**
     * Inscribir estudiante en curso
     */
    public function inscribirEnCurso($estudianteId, $cursoId) {
        // Verificar que el estudiante existe
        if (!$this->find($estudianteId)) {
            return ['errors' => ['estudiante' => 'Estudiante no encontrado']];
        }
        
        // Verificar que el curso existe
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT id FROM cursos WHERE id = ? AND activo = 1");
        $stmt->bind_param("i", $cursoId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return ['errors' => ['curso' => 'Curso no encontrado']];
        }
        $stmt->close();
        
        // Verificar que no esté ya inscrito
        $stmt = $conn->prepare("SELECT id FROM cursos_estudiantes WHERE estudiante_id = ? AND curso_id = ?");
        $stmt->bind_param("ii", $estudianteId, $cursoId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            return ['errors' => ['inscripcion' => 'El estudiante ya está inscrito en este curso']];
        }
        $stmt->close();
        
        // Inscribir estudiante
        $stmt = $conn->prepare("INSERT INTO cursos_estudiantes (estudiante_id, curso_id, fecha_inscripcion) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $estudianteId, $cursoId);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            $this->logActivity('inscribir_curso', $estudianteId, null, ['curso_id' => $cursoId]);
        }
        
        return $success;
    }
    
    /**
     * Desinscribir estudiante de curso
     */
    public function desinscribirDeCurso($estudianteId, $cursoId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("DELETE FROM cursos_estudiantes WHERE estudiante_id = ? AND curso_id = ?");
        $stmt->bind_param("ii", $estudianteId, $cursoId);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            $this->logActivity('desinscribir_curso', $estudianteId, null, ['curso_id' => $cursoId]);
        }
        
        return $success;
    }
    
    /**
     * Obtener estadísticas generales de estudiantes
     */
    public function getStats() {
        $conn = $this->getConnection();
        $stats = [];
        
        // Total estudiantes activos
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM estudiantes WHERE activo = 1");
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_activos'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // Estudiantes inscritos en cursos
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT e.id) as total 
            FROM estudiantes e 
            INNER JOIN cursos_estudiantes ce ON e.id = ce.estudiante_id 
            INNER JOIN cursos c ON ce.curso_id = c.id 
            WHERE e.activo = 1 AND c.activo = 1
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['inscritos_en_cursos'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // Promedio de cursos por estudiante
        $stmt = $conn->prepare("
            SELECT AVG(cursos_por_estudiante.total) as promedio
            FROM (
                SELECT COUNT(*) as total
                FROM cursos_estudiantes ce
                INNER JOIN estudiantes e ON ce.estudiante_id = e.id
                INNER JOIN cursos c ON ce.curso_id = c.id
                WHERE e.activo = 1 AND c.activo = 1
                GROUP BY e.id
            ) as cursos_por_estudiante
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['promedio_cursos'] = round($result->fetch_assoc()['promedio'] ?? 0, 2);
        $stmt->close();
        
        // Estudiantes con mejor asistencia (top 5)
        $stmt = $conn->prepare("
            SELECT 
                e.nombre,
                e.codigo,
                ROUND(
                    (SUM(CASE WHEN a.presente = 1 THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 
                    2
                ) as porcentaje_asistencia
            FROM estudiantes e
            INNER JOIN asistencias a ON e.id = a.estudiante_id
            WHERE e.activo = 1
            GROUP BY e.id, e.nombre, e.codigo
            HAVING COUNT(a.id) >= 5
            ORDER BY porcentaje_asistencia DESC
            LIMIT 5
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats['mejor_asistencia'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['mejor_asistencia'][] = $row;
        }
        
        $stmt->close();
        return $stats;
    }
    
    /**
     * Importar estudiantes desde array
     */
    public function importarEstudiantes($estudiantes) {
        $conn = $this->getConnection();
        $conn->autocommit(false);
        
        $importados = 0;
        $errores = [];
        
        try {
            foreach ($estudiantes as $index => $estudiante) {
                $resultado = $this->create($estudiante);
                
                if (is_array($resultado) && isset($resultado['errors'])) {
                    $errores[$index] = $resultado['errors'];
                } elseif ($resultado) {
                    $importados++;
                }
            }
            
            if (empty($errores)) {
                $conn->commit();
            } else {
                $conn->rollback();
            }
            
            $conn->autocommit(true);
            
            return [
                'importados' => $importados,
                'errores' => $errores,
                'total' => count($estudiantes)
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            $conn->autocommit(true);
            
            return [
                'importados' => 0,
                'errores' => ['general' => 'Error en la importación: ' . $e->getMessage()],
                'total' => count($estudiantes)
            ];
        }
    }

    public function countByProfesor($profesorId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT ce.estudiante_id) as total
            FROM cursos_estudiantes ce
            INNER JOIN cursos c ON ce.curso_id = c.id
            WHERE c.profesor_id = ? AND c.activo = 1
        ");
        $stmt->bind_param("i", $profesorId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['total'] ?? 0);
    }

    public function findByDocumento($documento) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT * FROM estudiantes WHERE documento = ? LIMIT 1");
        $stmt->bind_param("s", $documento);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row;
    }

    public function estaInscritoEnCurso($estudianteId, $cursoId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT id FROM cursos_estudiantes WHERE estudiante_id = ? AND curso_id = ?");
        $stmt->bind_param("ii", $estudianteId, $cursoId);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    public function getAll($conditions = []) {
        return $this->all($conditions, 'nombre');
    }
}