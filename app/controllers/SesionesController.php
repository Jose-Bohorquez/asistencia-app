
<?php
class SesionesController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function index() {
        // Verificar permisos - solo super_admin, admin y profesor pueden gestionar sesiones
        if (!in_array($_SESSION['user_rol'], ['super_admin', 'admin', 'profesor'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }
        
        $conn = $this->db->getConnection();
        $error = '';
        $success = '';
        
        // Activar sesión
        if (isset($_GET['activate']) && in_array($_SESSION['user_rol'], ['super_admin', 'admin'])) {
            $id = intval($_GET['activate']);
            $stmt = $conn->prepare("UPDATE sesiones SET estado = 'activa' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['sesion_success'] = 'Sesión activada correctamente.';
            header('Location: index.php?page=sesiones');
            exit;
        }
        
        // Desactivar sesión
        if (isset($_GET['deactivate']) && in_array($_SESSION['user_rol'], ['super_admin', 'admin'])) {
            $id = intval($_GET['deactivate']);
            $stmt = $conn->prepare("UPDATE sesiones SET estado = 'finalizada' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['sesion_success'] = 'Sesión finalizada correctamente.';
            header('Location: index.php?page=sesiones');
            exit;
        }
        
        // Eliminar sesión
        if (isset($_GET['delete']) && in_array($_SESSION['user_rol'], ['super_admin', 'admin'])) {
            $id = intval($_GET['delete']);
            $stmt = $conn->prepare("DELETE FROM sesiones WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['sesion_success'] = 'Sesión eliminada correctamente.';
            header('Location: index.php?page=sesiones');
            exit;
        }
        
        // Procesar formulario de creación/edición de sesión
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_SESSION['user_rol'], ['super_admin', 'admin', 'profesor'])) {
            $id = intval($_POST['id'] ?? 0);
            $curso_id = intval($_POST['curso_id'] ?? 0);
            $fecha = $_POST['fecha'] ?? '';
            $hora_inicio = $_POST['hora_inicio'] ?? '';
            $hora_fin = $_POST['hora_fin'] ?? null;
            $estado = $_POST['estado'] ?? 'programada';
            
            if (empty($curso_id) || empty($fecha) || empty($hora_inicio)) {
                $error = 'Por favor, complete los campos obligatorios.';
            } else {
                // Verificar permisos para el curso si es profesor
                if ($_SESSION['user_rol'] === 'profesor') {
                    $stmt = $conn->prepare("SELECT profesor_id FROM cursos WHERE id = ?");
                    $stmt->bind_param("i", $curso_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $curso = $result->fetch_assoc();
                    $stmt->close();
                    
                    if (!$curso || $curso['profesor_id'] != $_SESSION['user_id']) {
                        $error = 'No tienes permisos para crear sesiones en este curso.';
                    }
                }
                
                if (empty($error)) {
                    if ($id > 0) {
                        // Actualizar sesión
                        $stmt = $conn->prepare("
                            UPDATE sesiones 
                            SET curso_id = ?, fecha = ?, hora_inicio = ?, hora_fin = ?, estado = ?
                            WHERE id = ?
                        ");
                        $stmt->bind_param("issssi", $curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $id);
                        $stmt->execute();
                        $stmt->close();
                        
                        $_SESSION['sesion_success'] = 'Sesión actualizada correctamente.';
                    } else {
                        // Crear nueva sesión
                        $token = bin2hex(random_bytes(16));
                        $stmt = $conn->prepare("
                            INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->bind_param("isssss", $curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token);
                        $stmt->execute();
                        $stmt->close();
                        
                        $_SESSION['sesion_success'] = 'Sesión creada correctamente.';
                    }
                    
                    header('Location: index.php?page=sesiones');
                    exit;
                }
            }
        }
        
        // Obtener lista de sesiones según el rol
        $sesiones = [];
        if ($_SESSION['user_rol'] === 'profesor') {
            // Los profesores solo ven sesiones de sus cursos
            $query = "
                SELECT s.*, c.nombre as curso_nombre, c.codigo as curso_codigo
                FROM sesiones s 
                LEFT JOIN cursos c ON s.curso_id = c.id
                WHERE c.profesor_id = ?
                ORDER BY s.fecha DESC, s.hora_inicio DESC
            ";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $sesiones[] = $row;
            }
            $stmt->close();
        } else {
            // Admins y super_admins ven todas las sesiones
            $query = "
                SELECT s.*, c.nombre as curso_nombre, c.codigo as curso_codigo, u.nombre as profesor_nombre
                FROM sesiones s 
                LEFT JOIN cursos c ON s.curso_id = c.id
                LEFT JOIN usuarios u ON c.profesor_id = u.id
                ORDER BY s.fecha DESC, s.hora_inicio DESC
            ";
            $result = $conn->query($query);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $sesiones[] = $row;
                }
            }
        }
        
        // Obtener lista de cursos para el formulario
        $cursos = [];
        if ($_SESSION['user_rol'] === 'profesor') {
            // Los profesores solo ven sus cursos
            $query = "SELECT id, nombre, codigo FROM cursos WHERE profesor_id = ? ORDER BY nombre ASC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $cursos[] = $row;
            }
            $stmt->close();
        } else {
            // Admins y super_admins ven todos los cursos
            $query = "SELECT id, nombre, codigo FROM cursos ORDER BY nombre ASC";
            $result = $conn->query($query);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $cursos[] = $row;
                }
            }
        }
        
        // Mostrar vista
        include '../app/views/admin/sesiones.php';
    }
}
?>