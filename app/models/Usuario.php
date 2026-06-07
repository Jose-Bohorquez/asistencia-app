<?php
require_once __DIR__ . '/BaseModel.php';

/**
 * Modelo Usuario
 * Maneja la autenticación, roles y permisos de usuarios
 */
class Usuario extends BaseModel {
    protected $table = 'usuarios';
    protected $fillable = ['username', 'password', 'nombre', 'email', 'telefono', 'rol', 'activo', 'estado_cuenta', 'foto_perfil', 'fecha_nacimiento', 'documento', 'notif_email', 'ultimo_acceso'];
    protected $hidden = ['password'];
    
    // Roles disponibles
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';
    const ROLE_PROFESOR = 'profesor';
    
    /**
     * Autenticar usuario.
     * Devuelve el array del usuario (sin password) en éxito,
     * o ['errors' => [...]] en fallo — consistente con el resto de métodos.
     */
    public function authenticate($username, $password) {
        // Verificar bloqueo por IP antes de consultar BD
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if ($this->isIpBlocked($ip)) {
            return ['errors' => ['Demasiados intentos fallidos. Cuenta bloqueada temporalmente. Intenta en 15 minutos.']];
        }

        $conn = $this->getConnection();
        // Busca por username o email, en cualquier estado (para dar errores precisos)
        $stmt = $conn->prepare(
            "SELECT * FROM usuarios WHERE (username = ? OR email = ?) AND activo = 1 LIMIT 1"
        );
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Verificar estado de cuenta
            $estadoCuenta = $user['estado_cuenta'] ?? 'activo';
            if ($estadoCuenta === 'pendiente_activacion') {
                return ['errors' => ['pendiente_activacion' => 'Su cuenta aún no ha sido activada. Revise su correo electrónico para completar el registro.']];
            }
            if ($estadoCuenta === 'inactivo') {
                return ['errors' => ['Su cuenta ha sido desactivada. Contacte al administrador.']];
            }

            // Login exitoso: resetear contador de intentos fallidos
            $this->clearFailedAttempts($ip);
            $this->updateLastAccess($user['id']);
            $this->logActivity('login', $user['id']);
            return $this->hideFields($user);
        }

        // Login fallido: registrar intento
        $this->recordFailedAttempt($ip, $username);
        return ['errors' => ['Usuario o contraseña incorrectos.']];
    }

    /**
     * Crear usuario en estado pendiente_activacion (sin contraseña).
     * Se usa en el flujo de pre-registro: el admin crea el usuario y el sistema
     * envía un correo de activación para que el usuario defina su contraseña.
     */
    public function createPendiente(array $data): int|array {
        // Validar campos mínimos
        $errors = $this->validate($data, [
            'nombre' => 'required|max:100',
            'email'  => 'required|email|max:100',
            'rol'    => 'required',
        ]);
        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        if ($this->emailExists($data['email'])) {
            return ['errors' => ['email' => 'El email ya está registrado en el sistema.']];
        }

        // Generar username provisional desde email si no se proporcionó
        if (empty($data['username'])) {
            $data['username'] = strtolower(explode('@', $data['email'])[0]) . '_' . substr(md5(uniqid()), 0, 4);
        }
        if ($this->usernameExists($data['username'])) {
            $data['username'] .= '_' . substr(md5(uniqid()), 0, 4);
        }

        $data['password']      = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT); // temporal
        $data['estado_cuenta'] = 'pendiente_activacion';
        $data['activo']        = 1;

        $userId = parent::create($data);
        if ($userId) {
            $this->logActivity('create_pendiente', $userId, null, ['email' => $data['email'], 'rol' => $data['rol']]);
        }
        return $userId;
    }

    /**
     * Activar cuenta: establece contraseña y datos de perfil, cambia estado a 'activo'.
     */
    public function activarCuenta(int $userId, array $data): bool|array {
        if (empty($data['password']) || strlen($data['password']) < 8) {
            return ['errors' => ['password' => 'La contraseña debe tener al menos 8 caracteres.']];
        }
        if (empty($data['nombre'])) {
            return ['errors' => ['nombre' => 'El nombre es obligatorio.']];
        }

        $updateData = [
            'password'      => password_hash($data['password'], PASSWORD_DEFAULT),
            'nombre'        => $data['nombre'],
            'estado_cuenta' => 'activo',
        ];
        if (!empty($data['telefono'])) {
            $updateData['telefono'] = $data['telefono'];
        }
        if (!empty($data['username'])) {
            if (!$this->usernameExists($data['username'], $userId)) {
                $updateData['username'] = $data['username'];
            }
        }

        $ok = parent::update($userId, $updateData);
        if ($ok) {
            $this->logActivity('cuenta_activada', $userId);
        }
        return $ok;
    }

    /**
     * Busca un usuario por email exacto (incluye inactivos).
     */
    public function findByEmail(string $email): ?array {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Cuenta de roles disponibles para crear según el actor.
     */
    public function getRolesPermitidosParaCrear(string $rolActor): array {
        if ($rolActor === self::ROLE_SUPER_ADMIN) {
            return [
                'super_admin' => 'Super Administrador',
                'admin'       => 'Administrador',
                'profesor'    => 'Profesor',
            ];
        }
        // El admin solo puede crear admin y profesor
        return [
            'admin'   => 'Administrador',
            'profesor' => 'Profesor',
        ];
    }

    // -----------------------------------------------------------------------
    // Protección contra fuerza bruta (IP-based, almacenado en archivos tmp)
    // -----------------------------------------------------------------------
    private const MAX_ATTEMPTS  = 5;
    private const LOCKOUT_TIME  = 900; // 15 minutos en segundos

    private function getLockoutFile($ip) {
        return sys_get_temp_dir() . '/login_block_' . md5($ip) . '.json';
    }

    private function isIpBlocked($ip) {
        $file = $this->getLockoutFile($ip);
        if (!file_exists($file)) {
            return false;
        }
        $data = json_decode(file_get_contents($file), true);
        if (!$data) {
            return false;
        }
        // Limpiar entradas antiguas
        $now = time();
        $data['attempts'] = array_filter($data['attempts'] ?? [], function($ts) use ($now) {
            return ($now - $ts) < self::LOCKOUT_TIME;
        });
        if (count($data['attempts']) < self::MAX_ATTEMPTS) {
            return false;
        }
        return true;
    }

    private function recordFailedAttempt($ip, $username) {
        $file = $this->getLockoutFile($ip);
        $now  = time();
        $data = ['attempts' => []];
        if (file_exists($file)) {
            $existing = json_decode(file_get_contents($file), true);
            if ($existing) {
                $data = $existing;
            }
        }
        // Conservar solo intentos dentro de la ventana de tiempo
        $data['attempts'] = array_values(array_filter($data['attempts'] ?? [], function($ts) use ($now) {
            return ($now - $ts) < self::LOCKOUT_TIME;
        }));
        $data['attempts'][] = $now;
        $data['last_username'] = $username;
        file_put_contents($file, json_encode($data), LOCK_EX);
        error_log("Failed login attempt from IP {$ip} for username '{$username}'. Total: " . count($data['attempts']));
    }

    private function clearFailedAttempts($ip) {
        $file = $this->getLockoutFile($ip);
        if (file_exists($file)) {
            unlink($file);
        }
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
            'password' => 'required|min:8',
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
        
        // Solo valida los campos presentes en $data (permite actualizaciones parciales
        // como ['activo'=>0] sin exigir nombre, email y rol).
        $allRules = [
            'nombre' => 'required|max:100',
            'email'  => 'required|email|max:100',
            'rol'    => 'required',
        ];
        $rules = array_intersect_key($allRules, $data);

        if (isset($data['username'])) {
            $rules['username'] = 'required|min:3|max:50';
        }
        if (isset($data['password']) && !empty($data['password'])) {
            $rules['password'] = 'required|min:8';
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
        
        // Soft-delete: desactivar. Usa parent::update() para evitar
        // validación de campos requeridos (nombre, email, rol) en update parcial.
        $success = parent::update($id, ['activo' => 0]);
        
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
        $errors = $this->validate(['password' => $newPassword], ['password' => 'required|min:8']);
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
    
    // -----------------------------------------------------------------------
    // Remember-me token management
    // -----------------------------------------------------------------------

    /**
     * Guardar token "recordarme" en la base de datos.
     * Requiere columna remember_token VARCHAR(64) y remember_expires DATETIME en usuarios.
     */
    public function saveRememberToken($userId, $token, $expires) {
        $conn = $this->getConnection();
        $hashedToken = hash('sha256', $token);
        $stmt = $conn->prepare(
            "UPDATE usuarios SET remember_token = ?, remember_expires = ? WHERE id = ? AND activo = 1"
        );
        $stmt->bind_param("ssi", $hashedToken, $expires, $userId);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Obtener usuario por token "recordarme".
     * El token en cookie se compara hasheado contra lo almacenado.
     */
    public function getUserByRememberToken($token) {
        $conn = $this->getConnection();
        $hashedToken = hash('sha256', $token);
        $stmt = $conn->prepare(
            "SELECT * FROM usuarios
             WHERE remember_token = ?
               AND remember_expires > NOW()
               AND activo = 1
             LIMIT 1"
        );
        $stmt->bind_param("s", $hashedToken);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user ? $this->hideFields($user) : null;
    }

    /**
     * Limpiar token "recordarme" de la base de datos.
     */
    public function clearRememberToken($token) {
        $conn = $this->getConnection();
        $hashedToken = hash('sha256', $token);
        $stmt = $conn->prepare(
            "UPDATE usuarios SET remember_token = NULL, remember_expires = NULL WHERE remember_token = ?"
        );
        $stmt->bind_param("s", $hashedToken);
        $stmt->execute();
        $stmt->close();
    }

    // -----------------------------------------------------------------------

    /**
     * Usuarios paginados con filtros
     */
    public function getPaginated($page, $perPage, $filters = []) {
        $conn   = $this->getConnection();
        $sql    = "SELECT * FROM usuarios WHERE 1=1";
        $params = [];
        $types  = '';

        if (!empty($filters['search'])) {
            $sql .= ' AND (username LIKE ? OR nombre LIKE ? OR email LIKE ?)';
            $term = '%' . $filters['search'] . '%';
            $params[] = $term; $params[] = $term; $params[] = $term;
            $types   .= 'sss';
        }
        if (isset($filters['rol']) && $filters['rol'] !== '') {
            $sql .= ' AND rol = ?';
            $params[] = $filters['rol'];
            $types   .= 's';
        }
        if (isset($filters['activo']) && $filters['activo'] !== '') {
            $sql .= ' AND activo = ?';
            $params[] = (int)$filters['activo'];
            $types   .= 'i';
        }

        // Count
        $countSql = 'SELECT COUNT(*) as total FROM usuarios WHERE 1=1' . substr($sql, strlen("SELECT * FROM usuarios WHERE 1=1"));
        $stmt = $conn->prepare($countSql);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();

        // Page
        $offset   = ($page - 1) * $perPage;
        $sql     .= ' ORDER BY nombre LIMIT ? OFFSET ?';
        $params[] = $perPage;
        $params[] = $offset;
        $types   .= 'ii';

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $data   = [];
        while ($row = $result->fetch_assoc()) $data[] = $this->hideFields($row);
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
     * Retorna los roles disponibles del sistema
     */
    public function getRoles() {
        return [
            'super_admin' => 'Super Administrador',
            'admin'       => 'Administrador',
            'profesor'    => 'Profesor',
        ];
    }

    /**
     * Cuenta los cursos activos asociados a un usuario (profesor)
     */
    public function countAssociatedCourses($userId) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cursos WHERE profesor_id = ? AND activo = 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['total'] ?? 0);
    }

    /**
     * Retorna todos los usuarios activos para exportación
     */
    public function exportar() {
        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            "SELECT id, username, nombre, email, rol, activo, created_at, ultimo_acceso
             FROM usuarios WHERE activo = 1 ORDER BY nombre"
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $data   = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        $stmt->close();
        return $data;
    }

    /**
     * Busca un usuario por username exacto
     */
    public function findByUsername($username) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? $this->hideFields($row) : null;
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

    // -----------------------------------------------------------------------
    // Recuperación de contraseña
    // -----------------------------------------------------------------------

    /**
     * Solicita un reset de contraseña para el email dado.
     * Genera un token SHA-256, lo almacena y envía el correo.
     * Siempre devuelve éxito para no revelar si el email está registrado.
     */
    public function requestPasswordReset(string $email): array {
        require_once __DIR__ . '/TokenActivacion.php';
        require_once __DIR__ . '/../utils/MailService.php';

        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            "SELECT id, nombre, email, activo, estado_cuenta FROM usuarios WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // No revelar si el email existe o no (anti-enumeración)
        if (!$usuario || !$usuario['activo'] || $usuario['estado_cuenta'] === 'pendiente_activacion') {
            return ['success' => true];
        }

        $tokenModel = new TokenActivacion();
        $tokenReal  = $tokenModel->generarToken((int)$usuario['id'], TokenActivacion::TIPO_RESET_PASSWORD);

        $mailer = new MailService();
        $mailer->enviarResetPassword($usuario['email'], $usuario['nombre'], $tokenReal);

        return ['success' => true];
    }

    /**
     * Verifica si un token de reset es válido.
     * Devuelve el array del usuario si es válido, null si no.
     */
    public function verifyResetToken(string $token): ?array {
        if (!preg_match('/^[0-9a-f]{64}$/', $token)) {
            return null;
        }
        require_once __DIR__ . '/TokenActivacion.php';

        $tokenModel = new TokenActivacion();
        $registro   = $tokenModel->validarToken($token, TokenActivacion::TIPO_RESET_PASSWORD);
        if (!$registro) {
            return null;
        }

        $usuario = $this->find((int)$registro['usuario_id']);
        return ($usuario && $usuario['activo']) ? $usuario : null;
    }

    /**
     * Aplica la nueva contraseña si el token es válido e invalida el token.
     */
    public function resetPassword(string $token, string $nuevaPassword): array {
        require_once __DIR__ . '/TokenActivacion.php';

        $tokenModel = new TokenActivacion();
        $registro   = $tokenModel->validarToken($token, TokenActivacion::TIPO_RESET_PASSWORD);
        if (!$registro) {
            return ['errors' => ['El enlace ha expirado o ya fue utilizado. Solicita uno nuevo.']];
        }

        $hash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
        $conn = $this->getConnection();
        $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->bind_param('si', $hash, $registro['usuario_id']);
        $ok   = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            return ['errors' => ['Error al actualizar la contraseña. Inténtalo de nuevo.']];
        }

        $tokenModel->marcarUsado((int)$registro['id']);

        return ['success' => true];
    }
}