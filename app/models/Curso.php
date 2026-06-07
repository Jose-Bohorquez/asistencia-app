<?php
require_once __DIR__ . '/BaseModel.php';

/**
 * Modelo Curso
 * Maneja la gestión de cursos, su relación con programas y profesores
 */
class Curso extends BaseModel {
    protected $table = 'cursos';
    // Todos los campos editables según la tabla real de la BD
    protected $fillable = [
        'nombre', 'codigo', 'descripcion', 'programa_id', 'programa',
        'profesor_id', 'creditos', 'semestre', 'grupo', 'area',
        'aula', 'sede', 'periodo_academico', 'activo',
    ];
    
    /**
     * Crear curso con validaciones
     */
    public function create($data) {
        // Solo valida campos realmente requeridos por el formulario
        $errors = $this->validate($data, [
            'nombre'      => 'required|max:100',
            'codigo'      => 'required|max:20',
            'programa_id' => 'required',
            'profesor_id' => 'required',
        ]);

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        if ($this->codigoExists($data['codigo'])) {
            return ['errors' => ['codigo' => 'El código del curso ya existe']];
        }

        if (!$this->programaExists($data['programa_id'])) {
            return ['errors' => ['programa_id' => 'El programa seleccionado no existe']];
        }

        if (!$this->profesorExists($data['profesor_id'])) {
            return ['errors' => ['profesor_id' => 'El profesor seleccionado no existe o no tiene permisos']];
        }

        // Campo 'programa' (NOT NULL en BD): auto-poblar con el nombre del programa
        if (!isset($data['programa']) || $data['programa'] === '') {
            $conn = $this->getConnection();
            $stmt = $conn->prepare("SELECT nombre FROM programas WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $data['programa_id']);
            $stmt->execute();
            $prow = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $data['programa'] = $prow['nombre'] ?? '';
        }

        $cursoId = parent::create($data);

        if ($cursoId) {
            $this->logActivity('create', $cursoId, null, $data);
        }

        return $cursoId;
    }
    
    /**
     * Actualizar curso
     */
    public function update($id, $data) {
        $oldData = $this->find($id);
        
        // Solo valida campos presentes en $data (partial-update safe)
        $allRules = [
            'nombre'      => 'required|max:100',
            'codigo'      => 'required|max:20',
            'programa_id' => 'required',
            'profesor_id' => 'required',
        ];
        $rules  = array_intersect_key($allRules, $data);
        $errors = $this->validate($data, $rules);

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        if (isset($data['codigo']) && $this->codigoExists($data['codigo'], $id)) {
            return ['errors' => ['codigo' => 'El código del curso ya existe']];
        }

        if (isset($data['programa_id']) && !$this->programaExists($data['programa_id'])) {
            return ['errors' => ['programa_id' => 'El programa seleccionado no existe']];
        }

        if (isset($data['profesor_id']) && !$this->profesorExists($data['profesor_id'])) {
            return ['errors' => ['profesor_id' => 'El profesor seleccionado no existe o no tiene permisos']];
        }

        // Si cambia programa_id, sincronizar campo denormalizado 'programa'
        if (isset($data['programa_id']) && (!isset($data['programa']) || $data['programa'] === '')) {
            $conn = $this->getConnection();
            $stmt = $conn->prepare("SELECT nombre FROM programas WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $data['programa_id']);
            $stmt->execute();
            $prow = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $data['programa'] = $prow['nombre'] ?? '';
        }

        $success = parent::update($id, $data);
        
        if ($success) {
            $this->logActivity('update', $id, $oldData, $data);
        }
        
        return $success;
    }
    
    /**
     * Obtener cursos con información relacionada
     */
    public function getAllWithRelations($profesorId = null) {
        $conn = $this->getConnection();
        
        $sql = "
            SELECT
                c.*,
                p.nombre as programa_nombre,
                u.nombre as profesor_nombre,
                u.email as profesor_email,
                (SELECT COUNT(*) FROM cursos_estudiantes ce WHERE ce.curso_id = c.id) as total_estudiantes,
                (SELECT COUNT(*) FROM sesiones s WHERE s.curso_id = c.id) as total_sesiones
            FROM cursos c
            LEFT JOIN programas p ON c.programa_id = p.id
            LEFT JOIN usuarios u ON c.profesor_id = u.id
            WHERE c.activo = 1
        ";
        
        $params = [];
        $types = "";
        
        if ($profesorId) {
            $sql .= " AND c.profesor_id = ?";
            $params[] = $profesorId;
            $types .= "i";
        }
        
        $sql .= " ORDER BY c.nombre";
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
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
     * Obtener curso con información completa
     */
    public function getWithRelations($id) {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare("
            SELECT
                c.*,
                p.nombre as programa_nombre,
                p.codigo as programa_codigo,
                u.nombre as profesor_nombre,
                u.email as profesor_email,
                u.username as profesor_username
            FROM cursos c
            LEFT JOIN programas p ON c.programa_id = p.id
            LEFT JOIN usuarios u ON c.profesor_id = u.id
            WHERE c.id = ?
        ");
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $curso = $result->fetch_assoc();
        $stmt->close();
        
        return $curso;
    }
    
    /**
     * Obtener cursos por profesor
     */
    public function getByProfesor($profesorId) {
        return $this->getAllWithRelations($profesorId);
    }
    
    /**
     * Obtener cursos por programa
     */
    public function getByPrograma($programaId) {
        return $this->all(['programa_id' => $programaId, 'activo' => 1]);
    }
    
    /**
     * Verificar si un código de curso existe
     */
    public function codigoExists($codigo, $excludeId = null) {
        $conn = $this->getConnection();
        $sql = "SELECT id FROM cursos WHERE codigo = ?";
        
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
     * Verificar si un programa existe
     */
    private function programaExists($programaId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT id FROM programas WHERE id = ?");
        $stmt->bind_param("i", $programaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    /**
     * Verificar si un profesor existe y tiene el rol correcto
     */
    private function profesorExists($profesorId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ? AND rol = 'profesor' AND activo = 1");
        $stmt->bind_param("i", $profesorId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    /**
     * Inscribir estudiante en curso
     */
    public function inscribirEstudiante($cursoId, $estudianteId) {
        // Verificar que el curso existe
        if (!$this->find($cursoId)) {
            return ['errors' => ['curso' => 'El curso no existe']];
        }
        
        // Verificar que el estudiante existe
        if (!$this->estudianteExists($estudianteId)) {
            return ['errors' => ['estudiante' => 'El estudiante no existe']];
        }
        
        // Verificar que no esté ya inscrito
        if ($this->estudianteInscrito($cursoId, $estudianteId)) {
            return ['errors' => ['inscripcion' => 'El estudiante ya está inscrito en este curso']];
        }
        
        $conn = $this->getConnection();
        $stmt = $conn->prepare("INSERT INTO cursos_estudiantes (curso_id, estudiante_id, fecha_inscripcion) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $cursoId, $estudianteId);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            $this->logActivity('inscribir_estudiante', $cursoId, null, ['estudiante_id' => $estudianteId]);
        }
        
        return $success;
    }
    
    /**
     * Desinscribir estudiante del curso
     */
    public function desinscribirEstudiante($cursoId, $estudianteId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("DELETE FROM cursos_estudiantes WHERE curso_id = ? AND estudiante_id = ?");
        $stmt->bind_param("ii", $cursoId, $estudianteId);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            $this->logActivity('desinscribir_estudiante', $cursoId, null, ['estudiante_id' => $estudianteId]);
        }
        
        return $success;
    }
    
    /**
     * Obtener estudiantes inscritos en un curso
     */
    public function getEstudiantes($cursoId) {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare("
            SELECT 
                e.*,
                ce.fecha_inscripcion
            FROM estudiantes e
            INNER JOIN cursos_estudiantes ce ON e.id = ce.estudiante_id
            WHERE ce.curso_id = ?
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
    private function estudianteInscrito($cursoId, $estudianteId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT id FROM cursos_estudiantes WHERE curso_id = ? AND estudiante_id = ?");
        $stmt->bind_param("ii", $cursoId, $estudianteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    /**
     * Obtener estadísticas del curso
     */
    public function getStats($cursoId = null, $profesorId = null) {
        $conn = $this->getConnection();
        $stats = [];
        
        // Si se especifica un curso
        if ($cursoId) {
            // Total estudiantes inscritos
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cursos_estudiantes WHERE curso_id = ?");
            $stmt->bind_param("i", $cursoId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['total_estudiantes'] = $result->fetch_assoc()['total'];
            $stmt->close();
            
            // Total sesiones
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM sesiones WHERE curso_id = ?");
            $stmt->bind_param("i", $cursoId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['total_sesiones'] = $result->fetch_assoc()['total'];
            $stmt->close();
            
            return $stats;
        }
        
        // Estadísticas generales
        $whereClause = "WHERE c.activo = 1";
        $params = [];
        $types = "";
        
        if ($profesorId) {
            $whereClause .= " AND c.profesor_id = ?";
            $params[] = $profesorId;
            $types .= "i";
        }
        
        // Total cursos
        $sql = "SELECT COUNT(*) as total FROM cursos c {$whereClause}";
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_cursos'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // Cursos por programa
        $sql = "
            SELECT p.nombre, COUNT(*) as total 
            FROM cursos c 
            INNER JOIN programas p ON c.programa_id = p.id 
            {$whereClause}
            GROUP BY p.id, p.nombre
        ";
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats['por_programa'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['por_programa'][$row['nombre']] = $row['total'];
        }
        
        $stmt->close();
        return $stats;
    }
    
    /**
     * Buscar cursos
     */
    public function search($term, $profesorId = null) {
        $conn = $this->getConnection();
        
        $sql = "
            SELECT 
                c.*,
                p.nombre as programa_nombre,
                u.nombre as profesor_nombre
            FROM cursos c
            LEFT JOIN programas p ON c.programa_id = p.id
            LEFT JOIN usuarios u ON c.profesor_id = u.id
            WHERE c.activo = 1 AND (c.nombre LIKE ? OR c.codigo LIKE ? OR c.descripcion LIKE ?)
        ";
        
        $params = ["%{$term}%", "%{$term}%", "%{$term}%"];
        $types = "sss";
        
        if ($profesorId) {
            $sql .= " AND c.profesor_id = ?";
            $params[] = $profesorId;
            $types .= "i";
        }
        
        $sql .= " ORDER BY c.nombre";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $cursos = [];
        while ($row = $result->fetch_assoc()) {
            $cursos[] = $row;
        }

        $stmt->close();
        return $cursos;
    }

    public function countByProfesor($profesorId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cursos WHERE profesor_id = ? AND activo = 1");
        $stmt->bind_param("i", $profesorId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['total'] ?? 0);
    }

    public function getAll($conditions = []) {
        return $this->all($conditions, 'nombre');
    }

    /** Exportar todos los cursos con relaciones */
    public function exportar() {
        return $this->getAllWithRelations();
    }

    /** Exportar cursos de un profesor específico */
    public function exportarByProfesor($profesorId) {
        return $this->getAllWithRelations($profesorId);
    }

    public function countByPrograma($programaId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cursos WHERE programa_id = ? AND activo = 1");
        $stmt->bind_param('i', $programaId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['total'] ?? 0);
    }
}