<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // Check if we're running in Docker
        if (file_exists('/.dockerenv') || getenv('DOCKER_CONTAINER')) {
            // Docker environment - use service name as host
            $this->host = 'db'; // This is the service name in docker-compose.yml
            $this->db_name = 'asistencia_db';
            $this->username = 'root'; // Use root as defined in docker-compose.yml
            $this->password = 'root'; // Use the password from docker-compose.yml
        } else {
            // Local development credentials (for XAMPP)
            $this->host = 'localhost';
            $this->db_name = 'asistencia_db';
            $this->username = 'root'; // XAMPP default is root
            $this->password = ''; // XAMPP default is empty password
        }
        
        // Check if we're in production environment
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'dev-and-test.online') !== false) {
            // Hostinger database credentials
            $this->host = 'localhost';
            $this->db_name = 'u682531786_asistencia';
            $this->username = 'u682531786_asistencia';
            $this->password = 'Asistencia2024*';
        }
        
        // Debug info
        error_log("Database config - Host: {$this->host}, DB: {$this->db_name}, User: {$this->username}");
    }

    public function connect() {
        $this->conn = null;

        try {
            // Disable strict mode temporarily for troubleshooting
            mysqli_report(MYSQLI_REPORT_OFF);
            
            // Try to connect using default settings
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            
            // Check for connection errors
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            // Set charset
            $this->conn->set_charset("utf8");
            
            // Re-enable strict mode
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
        } catch(Exception $e) {
            // Print detailed error message
            echo "<div style='background-color: #ffdddd; border: 1px solid #ff0000; padding: 10px; margin: 10px;'>";
            echo "<h3>Database Connection Error</h3>";
            echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
            echo "<p><strong>Host:</strong> " . $this->host . "</p>";
            echo "<p><strong>Database:</strong> " . $this->db_name . "</p>";
            echo "<p><strong>Username:</strong> " . $this->username . "</p>";
            echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
            echo "<p><strong>MySQL Client Info:</strong> " . mysqli_get_client_info() . "</p>";
            echo "</div>";
            die(); // Stop execution
        }

        return $this->conn;
    }
    
    public function getConnection() {
        // If connection already exists and is valid, return it
        if ($this->conn && !$this->conn->connect_error) {
            return $this->conn;
        }
        
        // Otherwise create a new connection
        return $this->connect();
    }
}
?>