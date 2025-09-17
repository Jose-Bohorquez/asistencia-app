<?php
require_once __DIR__ . '/BaseModel.php';

/**
 * Modelo Programa
 * Maneja la gestión de programas académicos
 */
class Programa extends BaseModel {
    protected $table = 'programas';
    protected $fillable = ['nombre', 'codigo', 'tipo', 'descripcion', 'activo'];
    
    // Tipos de programa
    const TIPO_PREGRADO = 'pregrado';
    const TIPO_POSGRADO = 'posgrado';
    const TIPO_TECNICO = 'tecnico';
    const TIPO_TECNOLOGICO = 'tecnologico';
    
    /**
     * Crear programa con validaciones
     */
    public function create($data) {
        // Validar datos
        $errors = $this->validate($data, [
            'nombre' => 'required|max:100',
            'codigo' => 'required|max:20',
            'tipo' => 'required'
        ]);
        
        if (!empty($errors)) {
            return ['errors' => $errors];
        }
        
        // Verificar que el código no exista
        if ($this->codigoExists($data['codigo'])) {
            return ['errors' => ['codigo' => 'El código del programa ya existe']];
        }
        
        // Validar tipo de programa
        $tiposValidos = [self::TIPO_PREGRADO, self::TIPO_POSGRADO, self::TIPO_TECNICO, self::TIPO_TECNOLOGICO];
        if (!in_array($data['tipo'], $tiposValidos)) {
            return ['errors' => ['tipo' => 'Tipo de programa no válido']];
        }
        
        // Establecer activo por defecto
        if (!isset($data['activo'])) {
            $data['activo'] = 1;
        }
        
        $programaId = parent::create($data);
        
        if ($programaId) {
            $this->logActivity('create', $programaId, null, $data);
        }
        
        return $programaId;
    }
    
    /**
     * Actualizar programa
     */
    public function update($id, $data) {
        $oldData = $this->find($id);
        
        // Validar datos
        $errors = $this->validate($data, [
            'nombre' => 'required|max:100',
            'codigo' => 'required|max:20',
            'tipo' => 'required'
        ]);
        
        if (!empty($errors)) {
            return ['errors' => $errors];
        }
        
        // Verificar código único
        if ($this->codigoExists($data['codigo'], $id)) {
            return ['errors' => ['codigo' => 'El código del programa ya existe']];
        }
        
        // Validar tipo de programa
        $tiposValidos = [self::TIPO_PREGRADO, self::TIPO_POSGRADO, self::TIPO_TECNICO, self::TIPO_TECNOLOGICO];
        if (!in_array($data['tipo'], $tiposValidos)) {
            return ['errors' => ['tipo' => 'Tipo de programa no válido']];
        }
        
        $success = parent::update($id, $data);
        
        if ($success) {
            $this->logActivity('update', $id, $oldData, $data);
        }
        
        return $success;
    }
    
    /**
     * Eliminar programa (soft delete)
     */
    public function delete($id) {
        $oldData = $this->find($id);
        
        // Verificar que no tenga cursos activos
        if ($this->tieneCursosActivos($id)) {
            return ['errors' => ['cursos' => 'No se puede eliminar el programa porque tiene cursos activos']];
        }
        
        // En lugar de eliminar, desactivar el programa
        $success = $this->update($id, ['activo' => 0]);
        
        if ($success) {
            $this->logActivity('delete', $id, $oldData);
        }
        
        return $success;
    }
    
    /**
     * Obtener programas activos
     */
    public function getActivos() {
        return $this->all(['activo' => 1], 'nombre');
    }
    
    /**
     * Obtener programas por tipo
     */
    public function getByTipo($tipo) {
        return $this->all(['tipo' => $tipo, 'activo' => 1], 'nombre');
    }
    
    /**
     * Obtener programa con estadísticas
     */
    public function getWithStats($id) {
        $programa = $this->find($id);
        
        if (!$programa) {
            return null;
        }
        
        $conn = $this->getConnection();
        
        // Total de cursos
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cursos WHERE programa_id = ? AND activo = 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $programa['total_cursos'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // Total de estudiantes inscritos
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT ce.estudiante_id) as total
            FROM cursos c
            INNER JOIN cursos_estudiantes ce ON c.id = ce.curso_id
            INNER JOIN estudiantes e ON ce.estudiante_id = e.id
            WHERE c.programa_id = ? AND c.activo = 1 AND e.activo = 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $programa['total_estudiantes'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // Total de profesores
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT c.profesor_id) as total
            FROM cursos c
            INNER JOIN usuarios u ON c.profesor_id = u.id
            WHERE c.programa_id = ? AND c.activo = 1 AND u.activo = 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $programa['total_profesores'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // Total de sesiones
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total
            FROM sesiones s
            INNER JOIN cursos c ON s.curso_id = c.id
            WHERE c.programa_id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $programa['total_sesiones'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        return $programa;
    }
    
    /**
     * Obtener cursos de un programa
     */
    public function getCursos($programaId) {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare("
            SELECT 
                c.*,
                u.nombre as profesor_nombre,
                u.email as profesor_email,
                (SELECT COUNT(*) FROM cursos_estudiantes ce WHERE ce.curso_id = c.id) as total_estudiantes
            FROM cursos c
            INNER JOIN usuarios u ON c.profesor_id = u.id
            WHERE c.programa_id = ? AND c.activo = 1
            ORDER BY c.nombre
        ");
        
        $stmt->bind_param("i", $programaId);
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
     * Verificar si un código existe
     */
    public function codigoExists($codigo, $excludeId = null) {
        $conn = $this->getConnection();
        $sql = "SELECT id FROM programas WHERE codigo = ?";
        
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
     * Verificar si tiene cursos activos
     */
    private function tieneCursosActivos($programaId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT id FROM cursos WHERE programa_id = ? AND activo = 1 LIMIT 1");
        $stmt->bind_param("i", $programaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tiene = $result->num_rows > 0;
        $stmt->close();
        
        return $tiene;
    }
    
    /**
     * Buscar programas
     */
    public function search($term) {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare("
            SELECT * FROM programas 
            WHERE activo = 1 AND (
                nombre LIKE ? OR 
                codigo LIKE ? OR 
                descripcion LIKE ?
            )
            ORDER BY nombre
        ");
        
        $searchTerm = "%{$term}%";
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $programas = [];
        while ($row = $result->fetch_assoc()) {
            $programas[] = $row;
        }
        
        $stmt->close();
        return $programas;
    }
    
    /**
     * Obtener estadísticas generales de programas
     */
    public function getStats() {
        $conn = $this->getConnection();
        $stats = [];
        
        // Total programas activos
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM programas WHERE activo = 1");
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_activos'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // Programas por tipo
        $stmt = $conn->prepare("SELECT tipo, COUNT(*) as total FROM programas WHERE activo = 1 GROUP BY tipo");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats['por_tipo'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['por_tipo'][$row['tipo']] = $row['total'];
        }
        $stmt->close();
        
        // Programa con más cursos
        $stmt = $conn->prepare("
            SELECT 
                p.nombre,
                p.codigo,
                COUNT(c.id) as total_cursos
            FROM programas p
            LEFT JOIN cursos c ON p.id = c.programa_id AND c.activo = 1
            WHERE p.activo = 1
            GROUP BY p.id, p.nombre, p.codigo
            ORDER BY total_cursos DESC
            LIMIT 5
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats['con_mas_cursos'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['con_mas_cursos'][] = $row;
        }
        $stmt->close();
        
        // Programa con más estudiantes
        $stmt = $conn->prepare("
            SELECT 
                p.nombre,
                p.codigo,
                COUNT(DISTINCT ce.estudiante_id) as total_estudiantes
            FROM programas p
            LEFT JOIN cursos c ON p.id = c.programa_id AND c.activo = 1
            LEFT JOIN cursos_estudiantes ce ON c.id = ce.curso_id
            LEFT JOIN estudiantes e ON ce.estudiante_id = e.id AND e.activo = 1
            WHERE p.activo = 1
            GROUP BY p.id, p.nombre, p.codigo
            ORDER BY total_estudiantes DESC
            LIMIT 5
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats['con_mas_estudiantes'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['con_mas_estudiantes'][] = $row;
        }
        $stmt->close();
        
        return $stats;
    }
    
    /**
     * Obtener reporte de asistencia por programa
     */
    public function getReporteAsistencia($programaId, $fechaInicio = null, $fechaFin = null) {
        $conn = $this->getConnection();
        
        $sql = "
            SELECT 
                c.nombre as curso_nombre,
                c.codigo as curso_codigo,
                COUNT(DISTINCT s.id) as total_sesiones,
                COUNT(DISTINCT ce.estudiante_id) as total_estudiantes,
                COUNT(a.id) as total_registros_asistencia,
                SUM(CASE WHEN a.presente = 1 THEN 1 ELSE 0 END) as total_presentes,
                ROUND(
                    (SUM(CASE WHEN a.presente = 1 THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 
                    2
                ) as porcentaje_asistencia
            FROM cursos c
            LEFT JOIN cursos_estudiantes ce ON c.id = ce.curso_id
            LEFT JOIN estudiantes e ON ce.estudiante_id = e.id AND e.activo = 1
            LEFT JOIN sesiones s ON c.id = s.curso_id AND s.estado = 'finalizada'
            LEFT JOIN asistencias a ON s.id = a.sesion_id AND e.id = a.estudiante_id
            WHERE c.programa_id = ? AND c.activo = 1
        ";
        
        $params = [$programaId];
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
        
        $sql .= " GROUP BY c.id, c.nombre, c.codigo ORDER BY c.nombre";
        
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
     * Obtener todos los tipos de programa disponibles
     */
    public static function getTipos() {
        return [
            self::TIPO_PREGRADO => 'Pregrado',
            self::TIPO_POSGRADO => 'Posgrado',
            self::TIPO_TECNICO => 'Técnico',
            self::TIPO_TECNOLOGICO => 'Tecnológico'
        ];
    }
    
    /**
     * Obtener nombre del tipo de programa
     */
    public static function getNombreTipo($tipo) {
        $tipos = self::getTipos();
        return $tipos[$tipo] ?? $tipo;
    }
    
    /**
     * Exportar programas a array para Excel/PDF
     */
    public function exportarProgramas() {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare("
            SELECT 
                p.codigo,
                p.nombre,
                p.tipo,
                p.descripcion,
                COUNT(DISTINCT c.id) as total_cursos,
                COUNT(DISTINCT ce.estudiante_id) as total_estudiantes,
                COUNT(DISTINCT c.profesor_id) as total_profesores,
                CASE WHEN p.activo = 1 THEN 'Activo' ELSE 'Inactivo' END as estado
            FROM programas p
            LEFT JOIN cursos c ON p.id = c.programa_id AND c.activo = 1
            LEFT JOIN cursos_estudiantes ce ON c.id = ce.curso_id
            LEFT JOIN estudiantes e ON ce.estudiante_id = e.id AND e.activo = 1
            GROUP BY p.id, p.codigo, p.nombre, p.tipo, p.descripcion, p.activo
            ORDER BY p.nombre
        ");
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $datos = [];
        while ($row = $result->fetch_assoc()) {
            $row['tipo'] = self::getNombreTipo($row['tipo']);
            $datos[] = $row;
        }
        
        $stmt->close();
        return $datos;
    }
    
    /**
     * Duplicar programa
     */
    public function duplicar($id, $nuevoNombre, $nuevoCodigo) {
        $programa = $this->find($id);
        
        if (!$programa) {
            return ['errors' => ['programa' => 'Programa no encontrado']];
        }
        
        // Verificar que el nuevo código no exista
        if ($this->codigoExists($nuevoCodigo)) {
            return ['errors' => ['codigo' => 'El código del programa ya existe']];
        }
        
        // Crear nuevo programa
        $nuevoPrograma = [
            'nombre' => $nuevoNombre,
            'codigo' => $nuevoCodigo,
            'tipo' => $programa['tipo'],
            'descripcion' => $programa['descripcion'] . ' (Copia)',
            'activo' => 1
        ];
        
        $nuevoProgramaId = $this->create($nuevoPrograma);
        
        if ($nuevoProgramaId && !is_array($nuevoProgramaId)) {
            $this->logActivity('duplicar', $nuevoProgramaId, null, ['programa_original_id' => $id]);
        }
        
        return $nuevoProgramaId;
    }
}