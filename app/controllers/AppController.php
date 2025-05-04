<?php
// Load required controllers
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/AdminController.php';
require_once __DIR__ . '/AsistenciaController.php';

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
                $admin = new AdminController($this->db);
                $admin->sesiones();
                break;
            case 'asistencia':
                $asistencia = new AsistenciaController($this->db);
                $asistencia->registrarAsistencia();
                break;
            case 'exportar':
                if (isset($_GET['sesion_id']) && isset($_GET['format'])) {
                    $sesion_id = intval($_GET['sesion_id']);
                    $format = $_GET['format'];
                    $csrf_token = $_GET['csrf_token'] ?? '';

                    // Validar CSRF y permisos aquí...
                    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
                        die('Token CSRF inválido.');
                    }
                    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
                        die('No tienes permisos para exportar.');
                    }

                    // Aquí deberías obtener los datos de la sesión y asistencias desde la base de datos
                    // Ejemplo:
                    // $sesion = ...; $asistencias = ...;

                    if ($format === 'pdf') {
                        // Lógica para exportar a PDF usando FPDF
                        require_once __DIR__ . '/../../vendor/fpdf/fpdf.php'; // Ajusta la ruta si es necesario

                        $pdf = new \FPDF();
                        $pdf->AddPage();
                        $pdf->SetFont('Arial', 'B', 14);
                        $pdf->Cell(0, 10, 'Asistencia - Sesión ' . $sesion_id, 0, 1, 'C');
                        $pdf->SetFont('Arial', '', 10);

                        // Cabecera de la tabla
                        $pdf->Cell(40, 8, 'Nombre', 1);
                        $pdf->Cell(30, 8, 'Documento', 1);
                        $pdf->Cell(20, 8, 'Código', 1);
                        $pdf->Cell(25, 8, 'Teléfono', 1);
                        $pdf->Cell(40, 8, 'Dirección', 1);
                        $pdf->Cell(35, 8, 'Correo', 1);
                        $pdf->Ln();

                        // Filas de la tabla
                        foreach ($asistencias as $asistencia) {
                            $pdf->Cell(40, 8, $asistencia['nombre'], 1);
                            $pdf->Cell(30, 8, $asistencia['documento'], 1);
                            $pdf->Cell(20, 8, $asistencia['codigo'], 1);
                            $pdf->Cell(25, 8, $asistencia['telefono'], 1);
                            $pdf->Cell(40, 8, $asistencia['direccion'], 1);
                            $pdf->Cell(35, 8, $asistencia['correo'], 1);
                            $pdf->Ln();
                        }

                        $pdf->Output('D', 'asistencia_sesion_' . $sesion_id . '.pdf');
                        exit;
                    } elseif ($format === 'excel') {
                        // Lógica para exportar a Excel (HTML table)
                        header('Content-Type: application/vnd.ms-excel');
                        header('Content-Disposition: attachment; filename="asistencia_sesion_'.$sesion_id.'.xls"');
                        echo '<table border="1">';
                        echo '<tr>
                            <th>Nombre</th>
                            <th>Documento</th>
                            <th>Código</th>
                            <th>Teléfono</th>
                            <th>Dirección</th>
                            <th>Correo</th>
                        </tr>';
                        foreach ($asistencias as $asistencia) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($asistencia['nombre']) . '</td>';
                            echo '<td>' . htmlspecialchars($asistencia['documento']) . '</td>';
                            echo '<td>' . htmlspecialchars($asistencia['codigo']) . '</td>';
                            echo '<td>' . htmlspecialchars($asistencia['telefono']) . '</td>';
                            echo '<td>' . htmlspecialchars($asistencia['direccion']) . '</td>';
                            echo '<td>' . htmlspecialchars($asistencia['correo']) . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
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