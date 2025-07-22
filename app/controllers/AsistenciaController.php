<?php
class AsistenciaController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function registrarAsistencia() {
        $error = '';
        $success = '';
        $sesion = null;
        
        // Verificar si hay un ID de sesión o token en la URL
        $sesion_id = isset($_GET['sesion_id']) ? intval($_GET['sesion_id']) : 0;
        $token = isset($_GET['token']) ? $_GET['token'] : '';
        
        $conn = $this->db->getConnection();
        
        if ($sesion_id > 0) {
            // Obtener información de la sesión por ID
            $stmt = $conn->prepare("
                SELECT s.*, c.nombre as curso_nombre, c.programa, c.area, c.semestre, c.grupo, c.aula, c.sede
                FROM sesiones s
                JOIN cursos c ON s.curso_id = c.id
                WHERE s.id = ? AND s.estado = 'activa'
            ");
            $stmt->bind_param("i", $sesion_id);
            $stmt->execute();
            $result = $stmt->get_result();
        } elseif (!empty($token)) {
            // Obtener información de la sesión por token
            $stmt = $conn->prepare("
                SELECT s.*, c.nombre as curso_nombre, c.programa, c.area, c.semestre, c.grupo, c.aula, c.sede
                FROM sesiones s
                JOIN cursos c ON s.curso_id = c.id
                WHERE s.token = ? AND s.estado = 'activa'
            ");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $error = 'Sesión no válida.';
            include '../app/views/asistencia/registro.php';
            return;
        }
        
        if ($result) {
            
            if ($result->num_rows === 1) {
                $sesion = $result->fetch_assoc();
                $sesion_id = $sesion['id']; // Asegurar que tenemos el ID para el resto del código
                
                // Verificar si el usuario ya registró asistencia (por documento)
                $ya_registrado = false;
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $documento = $_POST['documento'] ?? '';
                    if (!empty($documento)) {
                        $stmt_check = $conn->prepare("SELECT id FROM estudiantes WHERE documento = ?");
                        $stmt_check->bind_param("s", $documento);
                        $stmt_check->execute();
                        $result_check = $stmt_check->get_result();
                        if ($result_check->num_rows > 0) {
                            $estudiante = $result_check->fetch_assoc();
                            $estudiante_id = $estudiante['id'];
                            $stmt_asistencia = $conn->prepare("SELECT id FROM asistencias WHERE sesion_id = ? AND estudiante_id = ?");
                            $stmt_asistencia->bind_param("ii", $sesion_id, $estudiante_id);
                            $stmt_asistencia->execute();
                            $result_asistencia = $stmt_asistencia->get_result();
                            if ($result_asistencia->num_rows > 0) {
                                $ya_registrado = true;
                            }
                            $stmt_asistencia->close();
                        }
                        $stmt_check->close();
                    }
                }
                
                // Si ya está registrado, mostrar mensaje y no mostrar formulario
                if ($ya_registrado) {
                    $error = 'Ya has registrado tu asistencia para esta sesión.';
                } else {
                    // Procesar el formulario de asistencia
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $nombre = $_POST['nombre'] ?? '';
                        $documento = $_POST['documento'] ?? '';
                        $codigo = $_POST['codigo'] ?? '';
                        $telefono = $_POST['telefono'] ?? '';
                        $direccion = $_POST['direccion'] ?? '';
                        $correo = $_POST['correo'] ?? '';
                        $firma = $_POST['firma'] ?? '';
                        
                        if (empty($nombre) || empty($documento) || empty($codigo)) {
                            $error = 'Por favor, complete los campos obligatorios.';
                        } else {
                            // Verificar si el estudiante ya existe
                            $stmt = $conn->prepare("SELECT id FROM estudiantes WHERE documento = ?");
                            $stmt->bind_param("s", $documento);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                // Actualizar estudiante
                                $estudiante = $result->fetch_assoc();
                                $estudiante_id = $estudiante['id'];
                                
                                $stmt = $conn->prepare("
                                    UPDATE estudiantes 
                                    SET nombre = ?, codigo = ?, telefono = ?, direccion = ?, correo = ?
                                    WHERE id = ?
                                ");
                                $stmt->bind_param("sssssi", $nombre, $codigo, $telefono, $direccion, $correo, $estudiante_id);
                                $stmt->execute();
                            } else {
                                // Crear nuevo estudiante
                                $stmt = $conn->prepare("
                                    INSERT INTO estudiantes (nombre, documento, codigo, telefono, direccion, correo)
                                    VALUES (?, ?, ?, ?, ?, ?)
                                ");
                                $stmt->bind_param("ssssss", $nombre, $documento, $codigo, $telefono, $direccion, $correo);
                                $stmt->execute();
                                $estudiante_id = $conn->insert_id;
                            }
                            
                            // Registrar asistencia
                            try {
                                $stmt = $conn->prepare("
                                    INSERT INTO asistencias (sesion_id, estudiante_id, firma)
                                    VALUES (?, ?, ?)
                                ");
                                $stmt->bind_param("iis", $sesion_id, $estudiante_id, $firma);
                                $stmt->execute();
                                $success = 'Asistencia registrada correctamente.';
                            } catch (Exception $e) {
                                if ($conn->errno === 1062) { // Error de duplicado
                                    $error = 'Ya has registrado tu asistencia para esta sesión.';
                                } else {
                                    $error = 'Error al registrar la asistencia: ' . $e->getMessage();
                                }
                            }
                        }
                    }
                }
            } else {
                $error = 'La sesión no existe o no está activa.';
            }
            
            $stmt->close();
        } else {
            $error = 'Sesión no válida.';
        }
        
        // Mostrar vista de asistencia
        include '../app/views/asistencia/registro.php';
    }
}