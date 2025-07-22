<?php
class ProgramasController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function index() {
        // Verificar permisos - solo super_admin y admin pueden gestionar programas
        if (!in_array($_SESSION['user_rol'], ['super_admin', 'admin'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }
        
        $error = '';
        $success = '';
        
        // Procesar formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = intval($_POST['id'] ?? 0);
            $codigo = trim($_POST['codigo'] ?? '');
            $nombre = trim($_POST['nombre'] ?? '');
            $activo = intval($_POST['activo'] ?? 1);
            
            if (empty($codigo) || empty($nombre)) {
                $error = 'Por favor, complete los campos obligatorios.';
            } else {
                if ($id > 0) {
                    // Verificar si el código ya existe en otro programa
                    $conn = $this->db->getConnection();
                    $stmt = $conn->prepare("SELECT id FROM programas WHERE codigo = ? AND id != ?");
                    $stmt->bind_param("si", $codigo, $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $error = 'Ya existe un programa con ese código.';
                        $stmt->close();
                    } else {
                        $stmt->close();
                        // Actualizar programa
                        $stmt = $conn->prepare("
                            UPDATE programas 
                            SET nombre = ?, codigo = ?, activo = ?
                            WHERE id = ?
                        ");
                        $stmt->bind_param("ssii", $codigo, $nombre, $activo, $id);
                        $stmt->execute();
                        $stmt->close();
                        
                        // Redirigir con mensaje de éxito
                        $_SESSION['programa_success'] = 'Programa actualizado correctamente.';
                        header('Location: index.php?page=programas');
                        exit;
                    }
                } else {
                    // Verificar si el código ya existe
                    $conn = $this->db->getConnection();
                    $stmt = $conn->prepare("SELECT id FROM programas WHERE codigo = ?");
                    $stmt->bind_param("s", $codigo);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $error = 'Ya existe un programa con ese código.';
                        $stmt->close();
                    } else {
                        $stmt->close();
                        // Crear nuevo programa
                        $stmt = $conn->prepare("
                            INSERT INTO programas (nombre, codigo, activo)
                            VALUES (?, ?, ?)
                        ");
                        $stmt->bind_param("ssi", $codigo, $nombre, $activo);
                        $stmt->execute();
                        $stmt->close();
                        
                        // Redirigir con mensaje de éxito
                        $_SESSION['programa_success'] = 'Programa creado correctamente.';
                        header('Location: index.php?page=programas');
                        exit;
                    }
                }
            }
        }
        
        // Eliminar programa
        if (isset($_GET['delete']) && intval($_GET['delete']) > 0) {
            $id = intval($_GET['delete']);
            
            // Verificar si el programa tiene cursos asociados
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cursos WHERE programa_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['total'] > 0) {
                $error = 'No se puede eliminar el programa porque tiene cursos asociados.';
            } else {
                $stmt = $conn->prepare("DELETE FROM programas WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                
                // Redirigir con mensaje de éxito
                $_SESSION['programa_success'] = 'Programa eliminado correctamente.';
                header('Location: index.php?page=programas');
                exit;
            }
        }
        
        // Obtener lista de programas
        $programas = [];
        $query = "SELECT * FROM programas ORDER BY created_at DESC";
        
        $conn = $this->db->getConnection();
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $programas[] = $row;
            }
        }
        
        // Mostrar vista
        include '../app/views/admin/programas.php';
    }
}
?>