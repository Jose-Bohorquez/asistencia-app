<?php
require_once __DIR__ . '/BaseModel.php';

/**
 * Modelo Usuario
 * Maneja la autenticación, roles y permisos de usuarios
 */
class Usuario extends BaseModel {
    protected $table = 'usuarios';
    protected $fillable = ['username', 'password', 'nombre', 'email', 'rol', 'activo', 'ultimo_acceso'];
    protected $hidden = ['password'];
    
    // Roles disponibles
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';
    const ROLE_PROFESOR = 'profesor';
    
    /**
     * Autenticar usuario
     */
    public function authenticate($username, $password) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE username = ? AND activo = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if ($user && password_verify($password, $user['password'])) {
            // Actualizar último acceso
            $this->updateLastAccess($user['id']);
            
            // Registrar login en logs
            $this->logActivity('login', $user['id']);
            
            // Ocultar password antes de retornar
            return $this->hideFields($user);
        }
        
        return false;
    }
    
    /**
     * Crear usuario con password hasheado
     */
    public function create($data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // Validar datos
        $errors = $this->validate($data, [
            'username' => 'required|min:3|max:50',
            'password' => 'required|min:6',
            'nombre' => 'required|max:100',
            'email' => 'required|email|max:100',
            'rol' => 'required'
        ]);
        
        if (!empty($errors)) {
            return ['errors' => $errors];
        }
        
        // Verificar que el username no exista
        if ($this->usernameExists($data['username'])) {
            return ['errors' => ['username' => 'El nombre de usuario ya existe']];
        }
        
        // Verificar que el email no exista
        if ($this->emailExists($data['email'])) {
            return ['errors' => ['email' => 'El email ya está registrado']];
        }
        
        $userId = parent::create($data);
        
        if ($userId) {
            $this->logActivity('create', $userId, null, $data);
        }
        
        return $userId;
    }
    
    /**
     * Actualizar usuario
     */
    public function update($id, $data) {
        // Obtener datos anteriores para el log
        $oldData = $this->find($id);
        
        // Si se está actualizando la password, hashearla
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            // Si no se proporciona password, no la actualizar
            unset($data['password']);
        }
        
        // Validar datos
        $rules = [
            'nombre' => 'required|max:100',
            'email' => 'required|email|max:100',
            'rol' => 'required'
        ];
        
        if (isset($data['username'])) {
            $rules['username'] = 'required|min:3|max:50';
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
            $rules['password'] = 'required|min:6';
        }
        
        $errors = $this->validate($data, $rules);
        
        if (!empty($errors)) {
            return ['errors' => $errors];
        }
        
        // Verificar username único (si se está actualizando)
        if (isset($data['username']) && $this->usernameExists($data['username'], $id)) {
            return ['errors' => ['username' => 'El nombre de usuario ya existe']];
        }
        
        // Verificar email único (si se está actualizando)
        if (isset($data['email']) && $this->emailExists($data['email'], $id)) {
            return ['errors' => ['email' => 'El email ya está registrado']];
        }
        
        $success = parent::update($id, $data);
        
        if ($success) {
            $this->logActivity('update', $id, $oldData, $data);
        }
        
        return $success;
    }
    
    /**
     * Eliminar usuario (soft delete)
     */
    public function delete($id) {
        $oldData = $this->find($id);
        
        // En lugar de eliminar, desactivar el usuario
        $success = $this->update($id, ['activo' => 0]);
        
        if ($success) {
            $this->logActivity('delete', $id, $oldData);
        }
        
        return $success;
    }
    
    /**
     * Verificar si un username existe
     */
    public function usernameExists($username, $excludeId = null) {
        $conn = $this->getConnection();
        $sql = "SELECT id FROM usuarios WHERE username = ?";
        
        if ($excludeId) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $conn->prepare($sql);
        
        if ($excludeId) {
            $stmt->bind_param("si", $username, $excludeId);
        } else {
            $stmt->bind_param("s", $username);
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
        $sql = "SELECT id FROM usuarios WHERE email = ?";
        
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
     * Obtener usuarios por rol
     */
    public function getByRole($role) {
        return $this->all(['rol' => $role, 'activo' => 1]);
    }
    
    /**
     * Obtener profesores activos
     */
    public function getProfesores() {
        return $this->getByRole(self::ROLE_PROFESOR);
    }
    
    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function hasRole($userId, $role) {
        $user = $this->find($userId);
        return $user && $user['rol'] === $role;
    }
    
    /**
     * Verificar si el usuario puede realizar una acción
     */
    public function canPerform($userId, $action) {
        $user = $this->find($userId);
        if (!$user || !$user['activo']) {
            return false;
        }
        
        $role = $user['rol'];
        
        // Super admin puede hacer todo
        if ($role === self::ROLE_SUPER_ADMIN) {
            return true;
        }
        
        // Definir permisos por rol
        $permissions = [
            self::ROLE_ADMIN => [
                'view_all', 'create', 'update', 'view_reports', 'export_data'
            ],
            self::ROLE_PROFESOR => [
                'view_own', 'create_sessions', 'view_attendance', 'export_own_data'
            ]
        ];
        
        return isset($permissions[$role]) && in_array($action, $permissions[$role]);
    }
    
    /**
     * Actualizar último acceso
     */
    private function updateLastAccess($userId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Cambiar password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->find($userId);
        
        if (!$user) {
            return ['errors' => ['user' => 'Usuario no encontrado']];
        }
        
        // Verificar password actual
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();
        $stmt->close();
        
        if (!password_verify($currentPassword, $userData['password'])) {
            return ['errors' => ['current_password' => 'La contraseña actual es incorrecta']];
        }
        
        // Validar nueva password
        $errors = $this->validate(['password' => $newPassword], ['password' => 'required|min:6']);
        if (!empty($errors)) {
            return ['errors' => $errors];
        }
        
        // Actualizar password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $success = parent::update($userId, ['password' => $hashedPassword]);
        
        if ($success) {
            $this->logActivity('change_password', $userId);
        }
        
        return $success;
    }
    
    /**
     * Obtener estadísticas de usuarios
     */
    public function getStats() {
        $conn = $this->getConnection();
        
        $stats = [];
        
        // Total usuarios activos
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_activos'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // Usuarios por rol
        $stmt = $conn->prepare("SELECT rol, COUNT(*) as total FROM usuarios WHERE activo = 1 GROUP BY rol");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $stats['por_rol'][$row['rol']] = $row['total'];
        }
        $stmt->close();
        
        // Usuarios conectados en las últimas 24 horas
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE ultimo_acceso >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND activo = 1");
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['activos_24h'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        return $stats;
    }
    
    /**
     * Buscar usuarios
     */
    public function search($term, $role = null) {
        $conn = $this->getConnection();
        
        $sql = "SELECT * FROM usuarios WHERE activo = 1 AND (username LIKE ? OR nombre LIKE ? OR email LIKE ?)";
        $params = ["%{$term}%", "%{$term}%", "%{$term}%"];
        $types = "sss";
        
        if ($role) {
            $sql .= " AND rol = ?";
            $params[] = $role;
            $types .= "s";
        }
        
        $sql .= " ORDER BY nombre";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $this->hideFields($row);
        }
        
        $stmt->close();
        return $users;
    }
}