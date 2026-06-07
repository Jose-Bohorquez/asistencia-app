<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        $this->loadConfig();
    }

    private function loadConfig() {
        // 1. Variables de entorno del sistema (recomendado para producción)
        $envHost = getenv('DB_HOST');
        if ($envHost !== false) {
            $this->host     = $envHost;
            $this->db_name  = getenv('DB_NAME') ?: 'asistencia_db';
            $this->username = getenv('DB_USER') ?: 'root';
            $this->password = getenv('DB_PASS') ?: '';
            return;
        }

        // 2. Archivo de configuración local (no versionado)
        $envFile = __DIR__ . '/env.local.php';
        if (file_exists($envFile)) {
            $env = include $envFile;
            $this->host     = $env['DB_HOST']     ?? 'localhost';
            $this->db_name  = $env['DB_NAME']     ?? 'asistencia_db';
            $this->username = $env['DB_USER']     ?? 'root';
            $this->password = $env['DB_PASS']     ?? '';
            return;
        }

        // 3. Detección de Docker como fallback de desarrollo
        if (file_exists('/.dockerenv') || getenv('DOCKER_CONTAINER')) {
            $this->host     = 'db';
            $this->db_name  = 'asistencia_db';
            $this->username = 'developer';
            $this->password = 'developer';
            return;
        }

        // 4. Desarrollo local XAMPP/LAMP por defecto
        $this->host     = 'localhost';
        $this->db_name  = 'asistencia_db';
        $this->username = 'root';
        $this->password = '';
    }

    public function connect() {
        $this->conn = null;

        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            $this->renderConnectionError();
        }

        return $this->conn;
    }

    private function renderConnectionError() {
        http_response_code(503);
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Error de servicio</title></head><body>';
        echo '<h2>Servicio temporalmente no disponible</h2>';
        echo '<p>Por favor intente nuevamente en unos minutos. Si el problema persiste, contacte al administrador.</p>';
        echo '</body></html>';
        die();
    }

    public function getConnection() {
        if ($this->conn && !$this->conn->connect_error) {
            return $this->conn;
        }
        return $this->connect();
    }
}
