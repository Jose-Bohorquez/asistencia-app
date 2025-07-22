<?php
class UsuariosController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function index() {
        // Verificar permisos - solo super_admin y admin pueden ver usuarios
        if (!in_array($_SESSION['user_rol'], ['super_admin', 'admin'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }
        
        // Obtener conexión a la base de datos
        $conn = $this->db->getConnection();
        
        $error = '';
        $success = '';
        
        // Procesar formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Solo super_admin puede crear/editar usuarios
            if ($_SESSION['user_rol'] !== 'super_admin') {
                $error = 'No tiene permisos para realizar esta acción.';
            } else {
                $id = intval($_POST['id'] ?? 0);
                $username = trim($_POST['username'] ?? '');
                $nombre = trim($_POST['nombre'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $rol = trim($_POST['rol'] ?? '');
                $password = trim($_POST['password'] ?? '');
                $activo = intval($_POST['activo'] ?? 1);
                
                if (empty($username) || empty($nombre) || empty($rol)) {
                    $error = 'Por favor, complete los campos obligatorios.';
                } elseif ($id === 0 && empty($password)) {
                    $error = 'La contraseña es obligatoria para nuevos usuarios.';
                } else {
                    if ($id > 0) {
                        // Verificar si el username ya existe en otro usuario
                        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
                        $stmt->bind_param("si", $username, $id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $error = 'Ya existe un usuario con ese nombre de usuario.';
                            $stmt->close();
                        } else {
                            $stmt->close();
                            // Actualizar usuario
                            if (!empty($password)) {
                                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                                $stmt = $conn->prepare("
                                    UPDATE usuarios 
                                    SET username = ?, nombre = ?, email = ?, rol = ?, password = ?, activo = ?
                                    WHERE id = ?
                                ");
                                $stmt->bind_param("sssssii", $username, $nombre, $email, $rol, $hashedPassword, $activo, $id);
                            } else {
                                $stmt = $conn->prepare("
                                    UPDATE usuarios 
                                    SET username = ?, nombre = ?, email = ?, rol = ?, activo = ?
                                    WHERE id = ?
                                ");
                                $stmt->bind_param("sssiii", $username, $nombre, $email, $rol, $activo, $id);
                            }
                            $stmt->execute();
                            $stmt->close();
                            
                            // Redirigir con mensaje de éxito
                            $_SESSION['usuario_success'] = 'Usuario actualizado correctamente.';
                            header('Location: index.php?page=usuarios');
                            exit;
                        }
                    } else {
                        // Verificar si el username ya existe
                        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
                        $stmt->bind_param("s", $username);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $error = 'Ya existe un usuario con ese nombre de usuario.';
                            $stmt->close();
                        } else {
                            $stmt->close();
                            // Crear nuevo usuario
                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $conn->prepare("
                                INSERT INTO usuarios (username, nombre, email, rol, password, activo)
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->bind_param("sssssi", $username, $nombre, $email, $rol, $hashedPassword, $activo);
                            $stmt->execute();
                            $stmt->close();
                            
                            // Redirigir con mensaje de éxito
                            $_SESSION['usuario_success'] = 'Usuario creado correctamente.';
                            header('Location: index.php?page=usuarios');
                            exit;
                        }
                    }
                }
            }
        }
        
        // Eliminar usuario
        if (isset($_GET['delete']) && intval($_GET['delete']) > 0) {
            // Solo super_admin puede eliminar usuarios
            if ($_SESSION['user_rol'] !== 'super_admin') {
                $error = 'No tiene permisos para realizar esta acción.';
            } else {
                $id = intval($_GET['delete']);
                
                // No permitir que se elimine a sí mismo
                if ($id == $_SESSION['user_id']) {
                    $error = 'No puede eliminar su propio usuario.';
                } else {
                    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Redirigir con mensaje de éxito
                    $_SESSION['usuario_success'] = 'Usuario eliminado correctamente.';
                    header('Location: index.php?page=usuarios');
                    exit;
                }
            }
        }
        
        // Obtener lista de usuarios
        $usuarios = [];
        $query = "SELECT * FROM usuarios ORDER BY created_at DESC";
        
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $usuarios[] = $row;
            }
        }
        
        // Mostrar vista
        include __DIR__ . '/../views/admin/usuarios.php';
    }
}
?>