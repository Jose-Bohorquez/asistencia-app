<?php
// Load required controllers
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/AdminController.php';
require_once __DIR__ . '/AsistenciaController.php';
require_once __DIR__ . '/UsuariosController.php';
require_once __DIR__ . '/ProgramasController.php';
require_once __DIR__ . '/SesionesController.php';

class AppController {
    protected $db;
    protected $auth;
    
    public function __construct() {
        // Initialize database connection with absolute path for debugging
        $dbPath = __DIR__ . '/../../config/database.php';
        if (!file_exists($dbPath)) {
            die("Database file not found at: " . $dbPath);
        }
        require_once $dbPath;
        $this->db = new Database();
        
        // Initialize authentication controller
        require_once __DIR__ . '/AuthController.php';
        $this->auth = new AuthController($this->db);
        
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    
    public function start() {
        // Enable error reporting for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Get the requested page from URL
        $page = isset($_GET['page']) ? $_GET['page'] : 'home';
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id']) && $page != 'login' && $page != 'asistencia') {
            // Redirect to login page
            header('Location: index.php?page=login');
            exit;
        }
        
        // Route to appropriate controller based on page
        switch ($page) {
            case 'login':
                $this->auth->login();
                break;
            case 'logout':
                $this->auth->logout();
                break;
            case 'dashboard':
                $admin = new AdminController($this->db);
                $admin->dashboard();
                break;
            case 'cursos':
                $admin = new AdminController($this->db);
                $admin->cursos();
                break;
            case 'sesiones':
                $sesiones = new SesionesController($this->db);
                $sesiones->index();
                break;
            case 'usuarios':
                $usuarios = new UsuariosController($this->db);
                $usuarios->index();
                break;
            case 'programas':
                $programas = new ProgramasController($this->db);
                $programas->index();
                break;
            case 'asistencia':
                $asistencia = new AsistenciaController($this->db);
                $asistencia->registrarAsistencia();
                break;
            case 'exportar':
                if (isset($_GET['sesion_id'])) {
                    $sesion_id = intval($_GET['sesion_id']);
                    $format = $_GET['format'] ?? 'excel'; // Default to excel if not specified
                    $csrf_token = $_GET['csrf_token'] ?? '';

                    // Validar permisos
                    if (!isset($_SESSION['user_rol']) || !in_array($_SESSION['user_rol'], ['admin', 'super_admin', 'profesor'])) {
                        die('No tienes permisos para exportar.');
                    }

                    // Generate CSRF token if not exists
                    if (!isset($_SESSION['csrf_token'])) {
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    }

                    // Obtener datos de asistencia desde la base de datos
                    $conn = $this->db->connect();
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

                    if ($format === 'pdf' || $format === 'print') {
                        // Redirigir a vista optimizada para PDF/impresión
                        header('Location: index.php?page=exportar&sesion_id=' . $sesion_id . '&print=1');
                        exit;
                    } elseif ($format === 'excel') {
                        // Obtener datos de la sesión
                        $stmt = $conn->prepare("
                            SELECT s.*, c.nombre as curso_nombre, c.codigo, c.programa, c.area, c.semestre, c.grupo, c.aula, c.sede
                            FROM sesiones s
                            JOIN cursos c ON s.curso_id = c.id
                            WHERE s.id = ?
                        ");
                        $stmt->bind_param("i", $sesion_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $sesion = $result->fetch_assoc();
                        
                        // Lógica para exportar a Excel (HTML table con formato)
                        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
                        header('Content-Disposition: attachment; filename="asistencia_sesion_'.$sesion_id.'.xls"');
                        echo "\xEF\xBB\xBF"; // BOM para UTF-8
                        
                        echo '<html><head><meta charset="UTF-8"></head><body>';
                        echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%; font-family: Arial, sans-serif;">';
                        
                        // Encabezado principal
                        echo '<tr><td colspan="7" style="text-align: center; font-weight: bold; font-size: 14px; background-color: #f0f0f0; padding: 10px;">';
                        echo 'PLANIFICACIÓN, DESARROLLO Y VERIFICACIÓN DE LA LABOR ACADÉMICA<br>';
                        echo 'CONTROL ASISTENCIA ESTUDIANTES';
                        echo '</td></tr>';
                        
                        // Información del curso
                        echo '<tr>';
                        echo '<td style="font-weight: bold;">ÁREA:</td><td>' . htmlspecialchars($sesion['area'] ?? 'xxxx') . '</td>';
                        echo '<td style="font-weight: bold;">PROGRAMA:</td><td>' . htmlspecialchars($sesion['programa']) . '</td>';
                        echo '<td style="font-weight: bold;">CÓDIGO:</td><td colspan="2">' . htmlspecialchars($sesion['codigo']) . '</td>';
                        echo '</tr>';
                        
                        echo '<tr>';
                        echo '<td style="font-weight: bold;">SEMESTRE:</td><td>' . htmlspecialchars($sesion['semestre']) . '</td>';
                        echo '<td style="font-weight: bold;">GRUPO:</td><td>' . htmlspecialchars($sesion['grupo']) . '</td>';
                        echo '<td style="font-weight: bold;">AULA No.:</td><td>' . htmlspecialchars($sesion['aula']) . '</td>';
                        echo '<td style="font-weight: bold;">SEDE:</td><td>' . htmlspecialchars($sesion['sede']) . '</td>';
                        echo '<td style="font-weight: bold;">FECHA:</td><td>' . date('d/m/Y', strtotime($sesion['fecha'])) . '</td>';
                        echo '</tr>';
                        
                        // Encabezados de la tabla de estudiantes
                         echo '<tr style="background-color: #e0e0e0; font-weight: bold; text-align: center;">';
                         echo '<th style="border: 1px solid #000; padding: 8px; width: 18%;">NOMBRE ESTUDIANTE</th>';
                         echo '<th style="border: 1px solid #000; padding: 8px; width: 10%;">DOCUMENTO IDENTIFICACIÓN</th>';
                         echo '<th style="border: 1px solid #000; padding: 8px; width: 10%;">CÓDIGO</th>';
                         echo '<th style="border: 1px solid #000; padding: 8px; width: 12%;">TELÉFONO</th>';
                         echo '<th style="border: 1px solid #000; padding: 8px; width: 18%;">DIRECCIÓN</th>';
                         echo '<th style="border: 1px solid #000; padding: 8px; width: 18%;">CORREO ELECTRÓNICO</th>';
                         echo '<th style="border: 1px solid #000; padding: 8px; width: 14%;">FIRMA</th>';
                         echo '</tr>';
                        
                        // Datos de estudiantes
                        foreach ($asistencias as $asistencia) {
                            echo '<tr>';
                            echo '<td style="border: 1px solid #000; padding: 5px;">' . htmlspecialchars($asistencia['nombre']) . '</td>';
                            echo '<td style="border: 1px solid #000; padding: 5px;">' . htmlspecialchars($asistencia['documento']) . '</td>';
                            echo '<td style="border: 1px solid #000; padding: 5px;">' . htmlspecialchars($asistencia['codigo']) . '</td>';
                            echo '<td style="border: 1px solid #000; padding: 5px;">' . htmlspecialchars($asistencia['telefono']) . '</td>';
                            echo '<td style="border: 1px solid #000; padding: 5px;">' . htmlspecialchars($asistencia['direccion']) . '</td>';
                            echo '<td style="border: 1px solid #000; padding: 5px;">' . htmlspecialchars($asistencia['correo']) . '</td>';
                            echo '<td style="border: 1px solid #000; padding: 5px; width: 100px;"></td>';
                            echo '</tr>';
                        }
                        
                        // Agregar filas vacías para completar el formato
                        for ($i = 0; $i < 10; $i++) {
                            echo '<tr>';
                            echo '<td style="border: 1px solid #000; padding: 5px; height: 25px;"></td>';
                            echo '<td style="border: 1px solid #000; padding: 5px;"></td>';
                            echo '<td style="border: 1px solid #000; padding: 5px;"></td>';
                            echo '<td style="border: 1px solid #000; padding: 5px;"></td>';
                            echo '<td style="border: 1px solid #000; padding: 5px;"></td>';
                            echo '<td style="border: 1px solid #000; padding: 5px;"></td>';
                            echo '<td style="border: 1px solid #000; padding: 5px;"></td>';
                            echo '</tr>';
                        }
                        
                        echo '</table></body></html>';
                        exit;
                    }
                } else {
                    $admin = new AdminController($this->db);
                    $admin->exportarAsistencia();
                }
                break;
            default:
                // Default to home page or dashboard if logged in
                if (isset($_SESSION['user_id'])) {
                    header('Location: index.php?page=dashboard');
                } else {
                    header('Location: index.php?page=login');
                }
                exit;
        }
    }
    
    } // <-- Este es el cierre correcto de la clase AppController
    
    ?>