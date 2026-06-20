<?php
require_once __DIR__ . '/BaseModel.php';

/**
 * Modelo Sesion
 * Maneja las sesiones de clase y la generación de tokens para asistencia
 */
class Sesion extends BaseModel {
    protected $table = 'sesiones';
    protected $fillable = ['curso_id', 'fecha', 'hora_inicio', 'hora_fin', 'descripcion', 'estado', 'token', 'duracion_minutos', 'aula', 'sede', 'tema', 'tipo_sesion', 'ubicacion', 'observaciones'];
    
    // Estados de sesión — deben coincidir con el ENUM en la BD:
    // enum('activa','finalizada','cancelada')
    // 'programada' NO existe en la BD; las sesiones se crean directamente como 'activa'.
    const ESTADO_ACTIVA     = 'activa';
    const ESTADO_FINALIZADA = 'finalizada';
    const ESTADO_CANCELADA  = 'cancelada';
    
    /**
     * Crear sesión con validaciones
     */
    public function create($data) {
        // Validar datos
        $errors = $this->validate($data, [
            'curso_id' => 'required',
            'fecha' => 'required',
            'hora_inicio' => 'required',
            'descripcion' => 'max:255'
        ]);
        
        if (!empty($errors)) {
            return ['errors' => $errors];
        }
        
        // Verificar que el curso existe
        if (!$this->cursoExists($data['curso_id'])) {
            return ['errors' => ['curso_id' => 'El curso seleccionado no existe']];
        }
        
        // Generar token único
        $data['token'] = $this->generateUniqueToken();
        
        // Estado inicial: 'activa' (único estado válido al crear según el ENUM de BD)
        if (!isset($data['estado']) || !in_array($data['estado'], [self::ESTADO_ACTIVA, self::ESTADO_FINALIZADA, self::ESTADO_CANCELADA])) {
            $data['estado'] = self::ESTADO_ACTIVA;
        }
        
        // Calcular hora_fin si se proporciona duración
        if (isset($data['duracion_minutos']) && !isset($data['hora_fin'])) {
            $horaInicio = new DateTime($data['fecha'] . ' ' . $data['hora_inicio']);
            $horaInicio->add(new DateInterval('PT' . $data['duracion_minutos'] . 'M'));
            $data['hora_fin'] = $horaInicio->format('H:i:s');
        }
        
        $sesionId = parent::create($data);
        
        if ($sesionId) {
            $this->logActivity('create', $sesionId, null, $data);
        }
        
        return $sesionId;
    }
    
    /**
     * Actualizar sesión
     */
    public function update($id, $data) {
        $oldData = $this->find($id);

        // Solo valida los campos presentes en $data (permite actualizaciones parciales
        // como ['estado'=>'activa'] sin exigir curso_id, fecha, hora_inicio).
        $allRules = [
            'curso_id'    => 'required',
            'fecha'       => 'required',
            'hora_inicio' => 'required',
            'descripcion' => 'max:255',
        ];
        $rules  = array_intersect_key($allRules, $data);
        $errors = $this->validate($data, $rules);

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        // Solo verifica existencia del curso cuando 'curso_id' viene en la actualización
        if (isset($data['curso_id']) && !$this->cursoExists($data['curso_id'])) {
            return ['errors' => ['curso_id' => 'El curso seleccionado no existe']];
        }

        // Recalcular hora_fin si se cambia duración
        if (isset($data['duracion_minutos'])) {
            $horaInicio = new DateTime($data['fecha'] . ' ' . $data['hora_inicio']);
            $horaInicio->add(new DateInterval('PT' . $data['duracion_minutos'] . 'M'));
            $data['hora_fin'] = $horaInicio->format('H:i:s');
        }
        
        $success = parent::update($id, $data);
        
        if ($success) {
            $this->logActivity('update', $id, $oldData, $data);
        }
        
        return $success;
    }
    
    /**
     * Obtener sesiones con información relacionada
     */
    public function getAllWithRelations($profesorId = null, $cursoId = null) {
        $conn = $this->getConnection();
        
        $sql = "
            SELECT 
                s.*,
                c.nombre as curso_nombre,
                c.codigo as curso_codigo,
                p.nombre as programa_nombre,
                u.nombre as profesor_nombre,
                (SELECT COUNT(*) FROM asistencias a WHERE a.sesion_id = s.id) as total_asistencias
            FROM sesiones s
            INNER JOIN cursos c ON s.curso_id = c.id
            INNER JOIN programas p ON c.programa_id = p.id
            INNER JOIN usuarios u ON c.profesor_id = u.id
            WHERE 1=1
        ";
        
        $params = [];
        $types = "";
        
        if ($profesorId) {
            $sql .= " AND c.profesor_id = ?";
            $params[] = $profesorId;
            $types .= "i";
        }
        
        if ($cursoId) {
            $sql .= " AND s.curso_id = ?";
            $params[] = $cursoId;
            $types .= "i";
        }
        
        $sql .= " ORDER BY s.fecha DESC, s.hora_inicio DESC";
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sesiones = [];
        while ($row = $result->fetch_assoc()) {
            $sesiones[] = $row;
        }
        
        $stmt->close();
        return $sesiones;
    }
    
    /**
     * Obtener sesión con información completa
     */
    public function getWithRelations($id) {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare("
            SELECT 
                s.*,
                c.nombre as curso_nombre,
                c.codigo as curso_codigo,
                c.descripcion as curso_descripcion,
                p.nombre as programa_nombre,
                u.nombre as profesor_nombre,
                u.email as profesor_email
            FROM sesiones s
            INNER JOIN cursos c ON s.curso_id = c.id
            INNER JOIN programas p ON c.programa_id = p.id
            INNER JOIN usuarios u ON c.profesor_id = u.id
            WHERE s.id = ?
        ");
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sesion = $result->fetch_assoc();
        $stmt->close();
        
        return $sesion;
    }
    
    /**
     * Obtener sesión por token
     */
    public function getByToken($token) {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare("
            SELECT 
                s.*,
                c.nombre as curso_nombre,
                c.codigo as curso_codigo,
                p.nombre as programa_nombre,
                u.nombre as profesor_nombre
            FROM sesiones s
            INNER JOIN cursos c ON s.curso_id = c.id
            INNER JOIN programas p ON c.programa_id = p.id
            INNER JOIN usuarios u ON c.profesor_id = u.id
            WHERE s.token = ?
        ");
        
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $sesion = $result->fetch_assoc();
        $stmt->close();
        
        return $sesion;
    }
    
    /**
     * Activar sesión
     */
    public function activar($id) {
        $sesion = $this->find($id);

        if (!$sesion) {
            return ['errors' => ['sesion' => 'Sesión no encontrada']];
        }

        if ($sesion['estado'] === self::ESTADO_ACTIVA) {
            return ['errors' => ['estado' => 'La sesión ya está activa']];
        }

        $success = parent::update($id, ['estado' => self::ESTADO_ACTIVA]);

        if ($success) {
            $this->logActivity('activar', $id);
        }

        return $success;
    }
    
    /**
     * Finalizar sesión
     */
    public function finalizar($id) {
        $sesion = $this->find($id);
        
        if (!$sesion) {
            return ['errors' => ['sesion' => 'Sesión no encontrada']];
        }
        
        if ($sesion['estado'] === self::ESTADO_FINALIZADA) {
            return ['errors' => ['estado' => 'La sesión ya está finalizada']];
        }
        
        $success = parent::update($id, [
            'estado'   => self::ESTADO_FINALIZADA,
            'hora_fin' => date('H:i:s'),
        ]);

        if ($success) {
            $this->logActivity('finalizar', $id);
        }
        
        return $success;
    }
    
    /**
     * Cancelar sesión
     */
    public function cancelar($id, $motivo = null) {
        $sesion = $this->find($id);
        
        if (!$sesion) {
            return ['errors' => ['sesion' => 'Sesión no encontrada']];
        }
        
        if ($sesion['estado'] === self::ESTADO_FINALIZADA) {
            return ['errors' => ['estado' => 'No se puede cancelar una sesión finalizada']];
        }
        
        $updateData = ['estado' => self::ESTADO_CANCELADA];
        
        if ($motivo) {
            $updateData['descripcion'] = $sesion['descripcion'] . ' [CANCELADA: ' . $motivo . ']';
        }
        
        $success = parent::update($id, $updateData);

        if ($success) {
            $this->logActivity('cancelar', $id, null, ['motivo' => $motivo]);
        }
        
        return $success;
    }
    
    /**
     * Verificar si una sesión está disponible para tomar asistencia
     */
    public function isAvailableForAttendance($token) {
        $sesion = $this->getByToken($token);
        
        if (!$sesion) {
            return ['available' => false, 'message' => 'Sesión no encontrada'];
        }
        
        if ($sesion['estado'] !== self::ESTADO_ACTIVA) {
            return ['available' => false, 'message' => 'La sesión no está activa'];
        }
        
        // Verificar si la sesión está en el tiempo permitido
        $now = new DateTime();
        $fechaSesion = new DateTime($sesion['fecha'] . ' ' . $sesion['hora_inicio']);
        
        // Permitir tomar asistencia 15 minutos antes y hasta el final de la sesión
        $inicioPermitido = clone $fechaSesion;
        $inicioPermitido->sub(new DateInterval('PT15M'));
        
        $finPermitido = new DateTime($sesion['fecha'] . ' ' . $sesion['hora_fin']);
        
        if ($now < $inicioPermitido) {
            return ['available' => false, 'message' => 'La sesión aún no ha comenzado'];
        }
        
        if ($now > $finPermitido) {
            return ['available' => false, 'message' => 'El tiempo para tomar asistencia ha expirado'];
        }
        
        return ['available' => true, 'sesion' => $sesion];
    }
    
    /**
     * Generar token único
     */
    private function generateUniqueToken() {
        do {
            $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales
        } while ($this->tokenExists($token));
        
        return $token;
    }
    
    /**
     * Verificar si un token existe
     */
    private function tokenExists($token) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT id FROM sesiones WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    /**
     * Verificar si un curso existe
     */
    private function cursoExists($cursoId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT id FROM cursos WHERE id = ? AND activo = 1");
        $stmt->bind_param("i", $cursoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    /**
     * Obtener sesiones activas
     */
    public function getActivas($profesorId = null) {
        try {
            $conn = $this->getConnection();
            $sql = "
                SELECT s.*, c.nombre as curso_nombre, c.codigo as curso_codigo,
                       u.nombre as profesor_nombre,
                       c.aula as curso_aula, c.sede as curso_sede,
                       COALESCE(NULLIF(s.aula,''), c.aula) as aula_display,
                       COALESCE(NULLIF(s.sede,''), c.sede) as sede_display,
                       (SELECT COUNT(*) FROM asistencias a WHERE a.sesion_id = s.id) as total_asistencias
                FROM sesiones s
                INNER JOIN cursos c ON s.curso_id = c.id
                LEFT JOIN usuarios u ON c.profesor_id = u.id
                WHERE s.estado = ?
            ";
            $params = [self::ESTADO_ACTIVA];
            $types  = 's';

            if ($profesorId) {
                $sql   .= " AND c.profesor_id = ?";
                $params[] = $profesorId;
                $types   .= 'i';
            }

            $sql .= " ORDER BY s.fecha DESC, s.hora_inicio DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $sesiones = [];
            while ($row = $result->fetch_assoc()) $sesiones[] = $row;
            $stmt->close();
            return $sesiones;
        } catch (Exception $e) {
            error_log('Sesion::getActivas error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener sesiones de hoy
     */
    public function getHoy($profesorId = null) {
        $conn = $this->getConnection();
        
        $sql = "
            SELECT 
                s.*,
                c.nombre as curso_nombre,
                c.codigo as curso_codigo,
                u.nombre as profesor_nombre
            FROM sesiones s
            INNER JOIN cursos c ON s.curso_id = c.id
            INNER JOIN usuarios u ON c.profesor_id = u.id
            WHERE DATE(s.fecha) = CURDATE()
        ";
        
        $params = [];
        $types = "";
        
        if ($profesorId) {
            $sql .= " AND c.profesor_id = ?";
            $params[] = $profesorId;
            $types .= "i";
        }
        
        $sql .= " ORDER BY s.hora_inicio";
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sesiones = [];
        while ($row = $result->fetch_assoc()) {
            $sesiones[] = $row;
        }
        
        $stmt->close();
        return $sesiones;
    }
    
    /**
     * Obtener estadísticas de sesiones
     */
    public function getStats($profesorId = null, $cursoId = null) {
        $conn = $this->getConnection();
        $stats = [];
        
        $whereClause = "WHERE 1=1";
        $params = [];
        $types = "";
        
        if ($profesorId) {
            $whereClause .= " AND c.profesor_id = ?";
            $params[] = $profesorId;
            $types .= "i";
        }
        
        if ($cursoId) {
            $whereClause .= " AND s.curso_id = ?";
            $params[] = $cursoId;
            $types .= "i";
        }
        
        // Total sesiones
        $sql = "SELECT COUNT(*) as total FROM sesiones s INNER JOIN cursos c ON s.curso_id = c.id {$whereClause}";
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_sesiones'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // Sesiones por estado
        $sql = "SELECT s.estado, COUNT(*) as total FROM sesiones s INNER JOIN cursos c ON s.curso_id = c.id {$whereClause} GROUP BY s.estado";
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats['por_estado'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['por_estado'][$row['estado']] = $row['total'];
        }
        
        $stmt->close();
        
        // Sesiones de esta semana
        $sql = "SELECT COUNT(*) as total FROM sesiones s INNER JOIN cursos c ON s.curso_id = c.id {$whereClause} AND WEEK(s.fecha) = WEEK(NOW()) AND YEAR(s.fecha) = YEAR(NOW())";
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['esta_semana'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        return $stats;
    }
    
    /**
     * Regenerar token de sesión
     */
    public function regenerateToken($id) {
        $newToken = $this->generateUniqueToken();
        $success = parent::update($id, ['token' => $newToken]);

        if ($success) {
            $this->logActivity('regenerate_token', $id);
            return $newToken;
        }
        
        return false;
    }
    
    /**
     * Buscar sesiones
     */
    public function search($term, $profesorId = null) {
        $conn = $this->getConnection();
        
        $sql = "
            SELECT 
                s.*,
                c.nombre as curso_nombre,
                c.codigo as curso_codigo,
                u.nombre as profesor_nombre
            FROM sesiones s
            INNER JOIN cursos c ON s.curso_id = c.id
            INNER JOIN usuarios u ON c.profesor_id = u.id
            WHERE (s.descripcion LIKE ? OR c.nombre LIKE ? OR c.codigo LIKE ?)
        ";
        
        $params = ["%{$term}%", "%{$term}%", "%{$term}%"];
        $types = "sss";
        
        if ($profesorId) {
            $sql .= " AND c.profesor_id = ?";
            $params[] = $profesorId;
            $types .= "i";
        }
        
        $sql .= " ORDER BY s.fecha DESC, s.hora_inicio DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sesiones = [];
        while ($row = $result->fetch_assoc()) {
            $sesiones[] = $row;
        }

        $stmt->close();
        return $sesiones;
    }

    public function countByProfesor($profesorId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total
            FROM sesiones s
            INNER JOIN cursos c ON s.curso_id = c.id
            WHERE c.profesor_id = ?
        ");
        $stmt->bind_param("i", $profesorId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['total'] ?? 0);
    }

    public function getActivasByProfesor($profesorId) {
        return $this->getActivas($profesorId);
    }

    /**
     * Sesiones paginadas con filtros
     */
    public function getPaginated($page, $perPage, $filters = []) {
        $profesorId = $filters['profesor_id'] ?? null;
        $cursoId    = $filters['curso_id'] ?? null;

        $conn = $this->getConnection();
        $sql = "
            SELECT s.*, c.nombre as curso_nombre, c.codigo as curso_codigo,
                   p.nombre as programa_nombre, u.nombre as profesor_nombre,
                   (SELECT COUNT(*) FROM asistencias a WHERE a.sesion_id = s.id) as total_asistencias
            FROM sesiones s
            INNER JOIN cursos c ON s.curso_id = c.id
            INNER JOIN programas p ON c.programa_id = p.id
            INNER JOIN usuarios u ON c.profesor_id = u.id
            WHERE 1=1
        ";
        $params = [];
        $types  = '';

        if ($profesorId) {
            $sql .= ' AND c.profesor_id = ?';
            $params[] = $profesorId;
            $types   .= 'i';
        }
        if ($cursoId) {
            $sql .= ' AND s.curso_id = ?';
            $params[] = $cursoId;
            $types   .= 'i';
        }
        if (!empty($filters['estado'])) {
            $sql .= ' AND s.estado = ?';
            $params[] = $filters['estado'];
            $types   .= 's';
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND (c.nombre LIKE ? OR c.codigo LIKE ? OR s.descripcion LIKE ?)';
            $term = '%' . $filters['search'] . '%';
            $params[] = $term; $params[] = $term; $params[] = $term;
            $types   .= 'sss';
        }
        if (!empty($filters['fecha_desde'])) {
            $sql .= ' AND s.fecha >= ?';
            $params[] = $filters['fecha_desde'];
            $types   .= 's';
        }
        if (!empty($filters['fecha_hasta'])) {
            $sql .= ' AND s.fecha <= ?';
            $params[] = $filters['fecha_hasta'];
            $types   .= 's';
        }

        // Count total
        $countSql = preg_replace('/SELECT .* FROM/', 'SELECT COUNT(*) as total FROM', $sql, 1);
        $stmt = $conn->prepare($countSql);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();

        // Paged results
        $sql .= ' ORDER BY s.fecha DESC, s.hora_inicio DESC LIMIT ? OFFSET ?';
        $offset = ($page - 1) * $perPage;
        $params[] = $perPage;
        $params[] = $offset;
        $types   .= 'ii';

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        $stmt->close();

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'total_pages'  => $total > 0 ? (int)ceil($total / $perPage) : 1,
            ],
        ];
    }

    /**
     * Cursos disponibles para un usuario según su rol
     */
    public function getCursosForUser($userId, $rol) {
        require_once __DIR__ . '/Curso.php';
        $cursoModel = new Curso();
        if (in_array($rol, ['super_admin', 'admin'])) {
            return $cursoModel->getAllWithRelations();
        }
        return $cursoModel->getByProfesor($userId);
    }

    /**
     * Verifica si un usuario puede gestionar una sesión específica
     */
    public function canUserManageSession($userId, $sesionId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("
            SELECT s.id FROM sesiones s
            INNER JOIN cursos c ON s.curso_id = c.id
            WHERE s.id = ? AND c.profesor_id = ?
        ");
        $stmt->bind_param('ii', $sesionId, $userId);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    /**
     * Cuenta asistencias registradas en una sesión
     */
    public function countAttendances($sesionId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM asistencias WHERE sesion_id = ?");
        $stmt->bind_param('i', $sesionId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['total'] ?? 0);
    }

    /**
     * Actualiza el estado de una sesión delegando a activar/finalizar/cancelar
     */
    public function updateStatus($id, $estado) {
        switch ($estado) {
            case self::ESTADO_ACTIVA:
                return $this->activar($id);
            case self::ESTADO_FINALIZADA:
                return $this->finalizar($id);
            case self::ESTADO_CANCELADA:
                return $this->cancelar($id);
            default:
                return parent::update($id, ['estado' => $estado]);
        }
    }

    /**
     * Verifica si un usuario (profesor) es dueño del curso dado
     */
    public function canUserManageCourse($userId, $cursoId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT id FROM cursos WHERE id = ? AND profesor_id = ? AND activo = 1");
        $stmt->bind_param('ii', $cursoId, $userId);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    /**
     * Exportar sesiones con filtros opcionales
     */
    public function exportar($filters = []) {
        return $this->getAllWithRelations(
            $filters['profesor_id'] ?? null,
            $filters['curso_id']    ?? null
        );
    }

    public function getWithCursoInfo($tokenOrId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("
            SELECT s.*,
                   c.nombre as curso_nombre, c.codigo as curso_codigo,
                   c.area as curso_area,
                   COALESCE(NULLIF(s.aula,''), c.aula) as aula,
                   COALESCE(NULLIF(s.sede,''), c.sede) as sede,
                   c.semestre, c.grupo, c.programa as curso_programa,
                   c.aula as curso_aula, c.sede as curso_sede,
                   p.nombre as programa_nombre,
                   u.nombre as profesor_nombre,
                   u.email as profesor_email,
                   u.telefono as profesor_telefono,
                   u.documento as profesor_documento
            FROM sesiones s
            INNER JOIN cursos c ON s.curso_id = c.id
            LEFT JOIN programas p ON c.programa_id = p.id
            LEFT JOIN usuarios u ON c.profesor_id = u.id
            WHERE s.token = ? OR s.id = ?
            LIMIT 1
        ");
        $id = is_numeric($tokenOrId) ? (int)$tokenOrId : 0;
        $stmt->bind_param("si", $tokenOrId, $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row;
    }

    /**
     * Elimina la sesión junto con todas sus asistencias (FK RESTRICT no tiene CASCADE).
     * Usa una transacción: primero borra asistencias, luego la sesión.
     */
    public function delete($id) {
        $conn = $this->getConnection();
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("DELETE FROM asistencias WHERE sesion_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM sesiones WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            $conn->commit();
            return $affected > 0;
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
}