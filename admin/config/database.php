<?php
/**
 * Configuración de Base de Datos PLAYMI
 * Este archivo maneja todas las conexiones a la base de datos
 */

class Database {
    private static $instance = null;
    private $connection;
    private $host = 'localhost';
    private $dbname = 'playmi';
    private $username = 'root';
    private $password = '';
    
    private function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $e) {
            // Registrar error en log
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            die("Error de conexión a la base de datos. Verifique la configuración.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function testConnection() {
        try {
            $stmt = $this->connection->query("SELECT 1");
            return true;
        } catch(Exception $e) {
            return false;
        }
    }
    
    public function getLastError() {
        $errorInfo = $this->connection->errorInfo();
        return $errorInfo[2] ?? 'Error desconocido';
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
}

// Test de conexión automático
try {
    $db = Database::getInstance();
    if (!$db->testConnection()) {
        throw new Exception("No se puede conectar a la base de datos");
    }
} catch(Exception $e) {
    error_log("Database connection test failed: " . $e->getMessage());
}
?>