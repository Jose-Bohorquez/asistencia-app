<?php
/**
 * Clase base para todos los modelos
 * Proporciona funcionalidades comunes como conexión a BD y operaciones CRUD básicas
 */
class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    
    public function __construct($database = null) {
        if ($database) {
            $this->db = $database;
        } else {
            require_once __DIR__ . '/../../config/database.php';
            $this->db = new Database();
        }
    }
    
    /**
     * Obtener conexión a la base de datos
     */
    protected function getConnection() {
        return $this->db->connect();
    }
    
    /**
     * Encontrar un registro por ID
     */
    public function find($id) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }
    
    /**
     * Obtener todos los registros
     */
    public function all($conditions = [], $orderBy = null, $limit = null) {
        $conn = $this->getConnection();
        $sql = "SELECT * FROM {$this->table}";
        
        // Agregar condiciones WHERE
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = ?";
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        // Agregar ORDER BY
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        // Agregar LIMIT
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        $stmt = $conn->prepare($sql);
        
        // Bind parameters si hay condiciones
        if (!empty($conditions)) {
            $types = str_repeat('s', count($conditions));
            $stmt->bind_param($types, ...array_values($conditions));
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $this->hideFields($row);
        }
        $stmt->close();
        return $data;
    }
    
    /**
     * Crear un nuevo registro
     */
    public function create($data) {
        $conn = $this->getConnection();
        
        // Filtrar solo campos permitidos
        $filteredData = $this->filterFillable($data);
        
        if (empty($filteredData)) {
            return false;
        }
        
        $fields = array_keys($filteredData);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        $sql = "INSERT INTO {$this->table} (" . implode(',', $fields) . ") VALUES ({$placeholders})";
        $stmt = $conn->prepare($sql);
        
        $types = $this->getBindTypes($filteredData);
        $stmt->bind_param($types, ...array_values($filteredData));
        
        $success = $stmt->execute();
        $insertId = $success ? $conn->insert_id : false;
        $stmt->close();
        
        return $insertId;
    }
    
    /**
     * Actualizar un registro
     */
    public function update($id, $data) {
        $conn = $this->getConnection();
        
        // Filtrar solo campos permitidos
        $filteredData = $this->filterFillable($data);
        
        if (empty($filteredData)) {
            return false;
        }
        
        $setClause = [];
        foreach (array_keys($filteredData) as $field) {
            $setClause[] = "{$field} = ?";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(',', $setClause) . " WHERE {$this->primaryKey} = ?";
        $stmt = $conn->prepare($sql);
        
        $values = array_values($filteredData);
        $values[] = $id;
        
        $types = $this->getBindTypes($filteredData) . 'i';
        $stmt->bind_param($types, ...$values);
        
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Eliminar un registro
     */
    public function delete($id) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Contar registros
     */
    public function count($conditions = []) {
        $conn = $this->getConnection();
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = ?";
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($conditions)) {
            $types = str_repeat('s', count($conditions));
            $stmt->bind_param($types, ...array_values($conditions));
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        
        return $data['total'] ?? 0;
    }
    
    /**
     * Ejecutar consulta personalizada
     */
    public function query($sql, $params = []) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $this->hideFields($row);
            }
        }
        
        $stmt->close();
        return $data;
    }
    
    /**
     * Filtrar campos permitidos
     */
    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Ocultar campos sensibles
     */
    protected function hideFields($data) {
        if (empty($this->hidden) || !is_array($data)) {
            return $data;
        }
        
        foreach ($this->hidden as $field) {
            unset($data[$field]);
        }
        
        return $data;
    }
    
    /**
     * Obtener tipos de datos para bind_param
     */
    protected function getBindTypes($data) {
        $types = '';
        foreach ($data as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        return $types;
    }
    
    /**
     * Validar datos antes de guardar
     */
    protected function validate($data, $rules = []) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field] = "El campo {$field} es requerido";
            }
            
            if (strpos($rule, 'email') !== false && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "El campo {$field} debe ser un email válido";
            }
            
            if (preg_match('/max:(\d+)/', $rule, $matches) && !empty($value) && strlen($value) > $matches[1]) {
                $errors[$field] = "El campo {$field} no puede tener más de {$matches[1]} caracteres";
            }
            
            if (preg_match('/min:(\d+)/', $rule, $matches) && !empty($value) && strlen($value) < $matches[1]) {
                $errors[$field] = "El campo {$field} debe tener al menos {$matches[1]} caracteres";
            }
        }
        
        return $errors;
    }
    
    /**
     * Registrar actividad en logs
     */
    protected function logActivity($action, $recordId = null, $oldData = null, $newData = null) {
        if (!isset($_SESSION['user_id'])) {
            return;
        }
        
        $conn = $this->getConnection();
        $stmt = $conn->prepare("
            INSERT INTO logs_sistema (usuario_id, accion, tabla_afectada, registro_id, datos_anteriores, datos_nuevos, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $userId = $_SESSION['user_id'];
        $table = $this->table;
        $oldDataJson = $oldData ? json_encode($oldData) : null;
        $newDataJson = $newData ? json_encode($newData) : null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt->bind_param("ississss", $userId, $action, $table, $recordId, $oldDataJson, $newDataJson, $ipAddress, $userAgent);
        $stmt->execute();
        $stmt->close();
    }
}