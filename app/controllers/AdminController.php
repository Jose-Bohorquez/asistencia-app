<?php
class AdminController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function dashboard() {
        // Verificar si el usuario es administrador
        if ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'profesor') {
            header('Location: index.php');
            exit;
        }
        
        $conn = $this->db->getConnection();
        
        // Obtener estadísticas
        $totalCursos = 0;
        $totalSesiones = 0;
        $totalEstudiantes = 0;
        
        $result = $conn->query("SELECT COUNT(*) as total FROM cursos");
        if ($result) {
            $row = $result->fetch_assoc();
            $totalCursos = $row['total'];
        }
        
        $result = $conn->query("SELECT COUNT(*) as total FROM sesiones");
        if ($result) {
            $row = $result->fetch_assoc();
            $totalSesiones = $row['total'];
        }
        
        $result = $conn->query("SELECT COUNT(*) as total FROM estudiantes");
        if ($result) {
            $row = $result->fetch_assoc();
            $totalEstudiantes = $row['total'];
        }
        
        // Obtener sesiones activas
        $sesionesActivas = [];
        $query = "
            SELECT s.*, c.nombre as curso_nombre, c.programa, c.area, c.semestre, c.grupo
            FROM sesiones s
            JOIN cursos c ON s.curso_id = c.id
            WHERE s.estado = 'activa'
            ORDER BY s.fecha DESC, s.hora_inicio DESC
        ";
        
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $sesionesActivas[] = $row;
            }
        }
        
        // Mostrar vista del dashboard
        include '../app/views/admin/dashboard.php';
    }
    
    public function cursos() {
        // Verificar si el usuario es administrador
        if ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'profesor') {
            header('Location: index.php');
            exit;
        }
        
        $conn = $this->db->getConnection();
        $error = '';
        $success = '';
        
        // Procesar formulario de creación/edición de curso
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $codigo = $_POST['codigo'] ?? '';
            $nombre = $_POST['nombre'] ?? '';
            $programa = $_POST['programa'] ?? '';
            $area = $_POST['area'] ?? '';
            $semestre = $_POST['semestre'] ?? '';
            $grupo = $_POST['grupo'] ?? '';
            $aula = $_POST['aula'] ?? '';
            $sede = $_POST['sede'] ?? '';
            
            if (empty($codigo) || empty($nombre) || empty($programa)) {
                $error = 'Por favor, complete los campos obligatorios.';
            } else {
                if ($id > 0) {
                    // Actualizar curso
                    $stmt = $conn->prepare("
                        UPDATE cursos 
                        SET codigo = ?, nombre = ?, programa = ?, area = ?, semestre = ?, grupo = ?, aula = ?, sede = ?
                        WHERE id = ?
                    ");
                    $stmt->bind_param("ssssssssi", $codigo, $nombre, $programa, $area, $semestre, $grupo, $aula, $sede, $id);
                    $stmt->execute();
                    $success = 'Curso actualizado correctamente.';
                } else {
                    // Crear nuevo curso
                    $profesor_id = $_SESSION['user_id'];
                    $stmt = $conn->prepare("
                        INSERT INTO cursos (codigo, nombre, programa, area, semestre, grupo, aula, sede, profesor_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("ssssssssi", $codigo, $nombre, $programa, $area, $semestre, $grupo, $aula, $sede, $profesor_id);
                    $stmt->execute();
                    $success = 'Curso creado correctamente.';
                }
                $stmt->close();
            }
        }
        
        // Eliminar curso
        if (isset($_GET['delete']) && intval($_GET['delete']) > 0) {
            $id = intval($_GET['delete']);
            $stmt = $conn->prepare("DELETE FROM cursos WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $success = 'Curso eliminado correctamente.';
            $stmt->close();
        }
        
        // Obtener lista de cursos
        $cursos = [];
        $query = "SELECT * FROM cursos ORDER BY created_at DESC";
        
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cursos[] = $row;
            }
        }
        
        // Mostrar vista de cursos
        include '../app/views/admin/cursos.php';
    }
    
    public function sesiones() {
        // Verificar si el usuario es administrador
        if ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'profesor') {
            header('Location: index.php');
            exit;
        }
        
        $conn = $this->db->getConnection();
        $error = '';
        $success = '';
        
        // Procesar formulario de creación/edición de sesión
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $curso_id = $_POST['curso_id'] ?? 0;
            $fecha = $_POST['fecha'] ?? '';
            $hora_inicio = $_POST['hora_inicio'] ?? '';
            $hora_fin = $_POST['hora_fin'] ?? null;
            $estado = $_POST['estado'] ?? 'activa';
            
            if (empty($curso_id) || empty($fecha) || empty($hora_inicio)) {
                $error = 'Por favor, complete los campos obligatorios.';
            } else {
                if ($id > 0) {
                    // Actualizar sesión
                    $stmt = $conn->prepare("
                        UPDATE sesiones 
                        SET curso_id = ?, fecha = ?, hora_inicio = ?, hora_fin = ?, estado = ?
                        WHERE id = ?
                    ");
                    $stmt->bind_param("issssi", $curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $id);
                    $stmt->execute();
                    $success = 'Sesión actualizada correctamente.';
                } else {
                    // Crear nueva sesión
                    $stmt = $conn->prepare("
                        INSERT INTO sesiones (curso_id, fecha, hora_inicio, estado)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->bind_param("isss", $curso_id, $fecha, $hora_inicio, $estado);
                    $stmt->execute();
                    $success = 'Sesión creada correctamente.';
                }
                $stmt->close();
            }
        }
        
        // Cambiar estado de sesión
        if (isset($_GET['activate']) && intval($_GET['activate']) > 0) {
            $id = intval($_GET['activate']);
            $stmt = $conn->prepare("UPDATE sesiones SET estado = 'activa' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $success = 'Sesión activada correctamente.';
            $stmt->close();
        }
        
        if (isset($_GET['deactivate']) && intval($_GET['deactivate']) > 0) {
            $id = intval($_GET['deactivate']);
            $stmt = $conn->prepare("UPDATE sesiones SET estado = 'finalizada', hora_fin = NOW() WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $success = 'Sesión finalizada correctamente.';
            $stmt->close();
        }
        
        // Obtener lista de cursos para el formulario
        $cursos = [];
        $query = "SELECT id, codigo, nombre FROM cursos ORDER BY nombre";
        
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cursos[] = $row;
            }
        }
        
        // Obtener lista de sesiones
        $sesiones = [];
        $query = "
            SELECT s.*, c.nombre as curso_nombre, c.programa, c.semestre, c.grupo
            FROM sesiones s
            JOIN cursos c ON s.curso_id = c.id
            ORDER BY s.fecha DESC, s.hora_inicio DESC
        ";
        
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $sesiones[] = $row;
            }
        }
        
        // Mostrar vista de sesiones
        include '../app/views/admin/sesiones.php';
    }
    
    public function exportarAsistencia() {
        // Verificar si el usuario es administrador
        if ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'profesor') {
            header('Location: index.php');
            exit;
        }
        
        $conn = $this->db->getConnection();
        $error = '';
        
        // Verificar si hay un ID de sesión en la URL
        $sesion_id = isset($_GET['sesion_id']) ? intval($_GET['sesion_id']) : 0;
        
        if ($sesion_id > 0) {
            // Obtener información de la sesión
            $stmt = $conn->prepare("
                SELECT s.*, c.nombre as curso_nombre, c.programa, c.area, c.semestre, c.grupo, c.aula, c.sede
                FROM sesiones s
                JOIN cursos c ON s.curso_id = c.id
                WHERE s.id = ?
            ");
            $stmt->bind_param("i", $sesion_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $sesion = $result->fetch_assoc();
                
                // Obtener lista de asistencias
                $asistencias = [];
                $stmt = $conn->prepare("
                    SELECT a.*, e.nombre, e.documento, e.codigo, e.telefono, e.direccion, e.correo
                    FROM asistencias a
                    JOIN estudiantes e ON a.estudiante_id = e.id
                    WHERE a.sesion_id = ?
                    ORDER BY a.hora_registro
                ");
                $stmt->bind_param("i", $sesion_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $asistencias[] = $row;
                }
                
                // Generar PDF o mostrar vista para exportar
                if (isset($_GET['format']) && $_GET['format'] === 'pdf') {
                    // Aquí implementarías la generación del PDF
                    // Por ahora, simplemente redirigimos a la vista HTML
                    header('Location: index.php?page=exportar&sesion_id=' . $sesion_id);
                    exit;
                }
                
                // Mostrar vista de exportación
                include '../app/views/admin/exportar.php';
            } else {
                $error = 'La sesión no existe.';
                include '../app/views/admin/error.php';
            }
            
            $stmt->close();
        } else {
            $error = 'Sesión no válida.';
            include '../app/views/admin/error.php';
        }
    }
}