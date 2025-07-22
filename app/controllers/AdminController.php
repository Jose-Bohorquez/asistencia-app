<?php
class AdminController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function dashboard() {
        // Verificar si el usuario es administrador
        if (!in_array($_SESSION['user_rol'], ['super_admin', 'admin', 'profesor'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }
        
        $conn = $this->db->getConnection();
        
        // Obtener estadísticas
        $totalCursos = 0;
        $totalSesiones = 0;
        $totalEstudiantes = 0;
        if ($_SESSION['user_rol'] === 'profesor') {
            // Cursos del profesor
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cursos WHERE profesor_id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                $row = $result->fetch_assoc();
                $totalCursos = $row['total'];
            }
            $stmt->close();
            // Sesiones del profesor
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM sesiones s JOIN cursos c ON s.curso_id = c.id WHERE c.profesor_id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                $row = $result->fetch_assoc();
                $totalSesiones = $row['total'];
            }
            $stmt->close();
            // Estudiantes de los cursos del profesor
            $stmt = $conn->prepare("SELECT COUNT(DISTINCT ce.estudiante_id) as total FROM cursos c JOIN cursos_estudiantes ce ON c.id = ce.curso_id WHERE c.profesor_id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                $row = $result->fetch_assoc();
                $totalEstudiantes = $row['total'];
            }
            $stmt->close();
        } else {
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
        }
        
        // Obtener sesiones activas
        $sesionesActivas = [];
        if ($_SESSION['user_rol'] === 'profesor') {
            $query = "
                SELECT s.*, c.nombre as curso_nombre, c.programa, c.area, c.semestre, c.grupo
                FROM sesiones s
                JOIN cursos c ON s.curso_id = c.id
                WHERE s.estado = 'activa' AND c.profesor_id = ?
                ORDER BY s.fecha DESC, s.hora_inicio DESC
            ";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $sesionesActivas[] = $row;
            }
            $stmt->close();
        } else {
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
        }
        
        // Mostrar vista del dashboard
        include '../app/views/admin/dashboard.php';
    }
    
    public function cursos() {
        // Verificar permisos
        if (!in_array($_SESSION['user_rol'], ['super_admin', 'admin', 'profesor'])) {
            header('Location: index.php?page=dashboard');
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
            $programa_id = intval($_POST['programa_id'] ?? 0);
            $area = $_POST['area'] ?? '';
            $semestre = intval($_POST['semestre'] ?? 0);
            $grupo = intval($_POST['grupo'] ?? 0);
            $aula = $_POST['aula'] ?? '';
            $sede = $_POST['sede'] ?? '';
            
            if (empty($codigo) || empty($nombre) || $programa_id <= 0) {
                $error = 'Por favor, complete los campos obligatorios.';
            } else {
                // Asignar profesor_id según el rol
                $profesor_id = null;
                if ($_SESSION['user_rol'] === 'profesor') {
                    $profesor_id = $_SESSION['user_id'];
                } elseif (isset($_POST['profesor_id']) && intval($_POST['profesor_id']) > 0) {
                    $profesor_id = intval($_POST['profesor_id']);
                }
                
                if ($id > 0) {
                    // Verificar permisos para editar
                    if ($_SESSION['user_rol'] === 'profesor') {
                        $stmt = $conn->prepare("SELECT profesor_id FROM cursos WHERE id = ?");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $curso = $result->fetch_assoc();
                        $stmt->close();
                        
                        if (!$curso || $curso['profesor_id'] != $_SESSION['user_id']) {
                            $error = 'No tienes permisos para editar este curso.';
                        }
                    }
                    
                    if (empty($error)) {
                        // Verificar si el código ya existe en otro curso
                        $stmt = $conn->prepare("SELECT id FROM cursos WHERE codigo = ? AND id != ?");
                        $stmt->bind_param("si", $codigo, $id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $error = 'Ya existe un curso con ese código.';
                            $stmt->close();
                        } else {
                            $stmt->close();
                            
                            // Obtener el nombre del programa
                            $stmt = $conn->prepare("SELECT nombre FROM programas WHERE id = ?");
                            $stmt->bind_param("i", $programa_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $programa_nombre = '';
                            if ($result->num_rows > 0) {
                                $programa_row = $result->fetch_assoc();
                                $programa_nombre = $programa_row['nombre'];
                            }
                            $stmt->close();
                            
                            // Actualizar curso
                            $stmt = $conn->prepare("
                                UPDATE cursos 
                                SET codigo = ?, nombre = ?, programa_id = ?, programa = ?, area = ?, semestre = ?, grupo = ?, aula = ?, sede = ?, profesor_id = ?
                                WHERE id = ?
                            ");
                            $stmt->bind_param("ssissssssii", $codigo, $nombre, $programa_id, $programa_nombre, $area, $semestre, $grupo, $aula, $sede, $profesor_id, $id);
                            $stmt->execute();
                            $stmt->close();
                            
                            // Redirigir con mensaje de éxito para evitar duplicación
                            $_SESSION['curso_success'] = 'Curso actualizado correctamente.';
                            header('Location: index.php?page=cursos');
                            exit;
                        }
                    }
                } else {
                    // Verificar si el código ya existe
                    $stmt = $conn->prepare("SELECT id FROM cursos WHERE codigo = ?");
                    $stmt->bind_param("s", $codigo);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $error = 'Ya existe un curso con ese código.';
                        $stmt->close();
                    } else {
                        $stmt->close();
                        
                        // Obtener el nombre del programa
                        $stmt = $conn->prepare("SELECT nombre FROM programas WHERE id = ?");
                        $stmt->bind_param("i", $programa_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $programa_nombre = '';
                        if ($result->num_rows > 0) {
                            $programa_row = $result->fetch_assoc();
                            $programa_nombre = $programa_row['nombre'];
                        }
                        $stmt->close();
                        
                        // Crear nuevo curso
                        $stmt = $conn->prepare("
                            INSERT INTO cursos (codigo, nombre, programa_id, programa, area, semestre, grupo, aula, sede, profesor_id)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->bind_param("ssissssssi", $codigo, $nombre, $programa_id, $programa_nombre, $area, $semestre, $grupo, $aula, $sede, $profesor_id);
                        $stmt->execute();
                        $stmt->close();
                        
                        // Redirigir con mensaje de éxito para evitar duplicación
                        $_SESSION['curso_success'] = 'Curso creado correctamente.';
                        header('Location: index.php?page=cursos');
                        exit;
                    }
                }
            }
        }
        
        // Eliminar curso
        if (isset($_GET['delete']) && intval($_GET['delete']) > 0) {
            $id = intval($_GET['delete']);
            
            // Verificar permisos para eliminar
            if ($_SESSION['user_rol'] === 'profesor') {
                $stmt = $conn->prepare("SELECT profesor_id FROM cursos WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $curso = $result->fetch_assoc();
                $stmt->close();
                
                if (!$curso || $curso['profesor_id'] != $_SESSION['user_id']) {
                    $error = 'No tienes permisos para eliminar este curso.';
                }
            }
            
            if (empty($error)) {
                $stmt = $conn->prepare("DELETE FROM cursos WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                
                // Redirigir con mensaje de éxito para evitar duplicación
                $_SESSION['curso_success'] = 'Curso eliminado correctamente.';
                header('Location: index.php?page=cursos');
                exit;
            }
        }
        
        // Obtener lista de cursos según el rol
        $cursos = [];
        if ($_SESSION['user_rol'] === 'profesor') {
            // Los profesores solo ven sus propios cursos
            $query = "
                SELECT c.*, p.nombre as programa_nombre, u.nombre as profesor_nombre
                FROM cursos c 
                LEFT JOIN programas p ON c.programa_id = p.id
                LEFT JOIN usuarios u ON c.profesor_id = u.id
                WHERE c.profesor_id = ?
                ORDER BY c.created_at DESC
            ";
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
            $query = "
                SELECT c.*, p.nombre as programa_nombre, u.nombre as profesor_nombre
                FROM cursos c 
                LEFT JOIN programas p ON c.programa_id = p.id
                LEFT JOIN usuarios u ON c.profesor_id = u.id
                ORDER BY c.created_at DESC
            ";
            $result = $conn->query($query);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $cursos[] = $row;
                }
            }
        }
        
        // Obtener lista de programas activos
        $programas = [];
        $query = "SELECT * FROM programas WHERE activo = 1 ORDER BY nombre ASC";
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $programas[] = $row;
            }
        }
        
        // Obtener lista de profesores (solo para admins)
        $profesores = [];
        if (in_array($_SESSION['user_rol'], ['super_admin', 'admin'])) {
            $query = "SELECT id, nombre, username FROM usuarios WHERE rol = 'profesor' AND activo = 1 ORDER BY nombre ASC";
            $result = $conn->query($query);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $profesores[] = $row;
                }
            }
        }
        
        // Mostrar vista de cursos
        include '../app/views/admin/cursos.php';
    }
    
    public function sesiones() {
        // Verificar permisos
        if (!in_array($_SESSION['user_rol'], ['super_admin', 'admin', 'profesor'])) {
            header('Location: index.php?page=dashboard');
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
            $allowFinalize = false;
            if (in_array($_SESSION['user_rol'], ['super_admin', 'admin'])) {
                $allowFinalize = true;
            } elseif ($_SESSION['user_rol'] === 'profesor') {
                // Verificar si la sesión pertenece a un curso del profesor
                $stmt = $conn->prepare("SELECT c.profesor_id FROM sesiones s JOIN cursos c ON s.curso_id = c.id WHERE s.id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $row = $result->fetch_assoc()) {
                    if ($row['profesor_id'] == $_SESSION['user_id']) {
                        $allowFinalize = true;
                    }
                }
                $stmt->close();
            }
            if ($allowFinalize) {
                $stmt = $conn->prepare("UPDATE sesiones SET estado = 'finalizada', hora_fin = NOW() WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $success = 'Sesión finalizada correctamente.';
                $stmt->close();
            } else {
                $error = 'No tienes permisos para finalizar esta sesión.';
            }
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
        if (!in_array($_SESSION['user_rol'], ['super_admin', 'admin', 'profesor'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }
        
        $conn = $this->db->getConnection();
        $error = '';
        
        // Verificar si hay un ID de sesión en la URL
        $sesion_id = isset($_GET['sesion_id']) ? intval($_GET['sesion_id']) : 0;
        
        if ($sesion_id > 0) {
            // Obtener información de la sesión
            $stmt = $conn->prepare("
                SELECT s.*, c.nombre as curso_nombre, c.codigo, c.programa, c.area, c.semestre, c.grupo, c.aula, c.sede
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
                    // Validar token CSRF
                    $csrf_token = $_GET['csrf_token'] ?? '';
                    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
                        die('Token CSRF inválido.');
                    }
                    
                    // Verificar permisos de administrador
                    if (!isset($_SESSION['user_rol']) || !in_array($_SESSION['user_rol'], ['admin', 'super_admin'])) {
                        die('No tienes permisos para exportar.');
                    }
                    
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