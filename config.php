<?php
/**
 * Database Configuration File
 * Attendance System Database Connection Settings
 */

// Database connection constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'attendance_system');
define('DB_USER', 'root');  // Change this to your MySQL username
define('DB_PASS', '');      // Change this to your MySQL password
define('DB_CHARSET', 'utf8mb4');

// Database connection class
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    // Singleton pattern to ensure only one database connection
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Get the PDO connection
    public function getConnection() {
        return $this->connection;
    }
    
    // Execute a query with parameters
    public function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Query execution failed: " . $e->getMessage());
        }
    }
    
    // Execute a query and return all results
    public function fetchAll($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetchAll();
    }
    
    // Execute a query and return single result
    public function fetchOne($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetch();
    }
    
    // Execute an INSERT, UPDATE, or DELETE query
    public function execute($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->rowCount();
    }
    
    // Get the last inserted ID
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    // Begin a transaction
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    // Commit a transaction
    public function commit() {
        return $this->connection->commit();
    }
    
    // Rollback a transaction
    public function rollback() {
        return $this->connection->rollback();
    }
    
    // Close the connection
    public function close() {
        $this->connection = null;
        self::$instance = null;
    }
}

// Helper function to get database instance
function getDB() {
    return Database::getInstance();
}

// Example usage:
/*
try {
    $db = getDB();
    
    // Fetch all students
    $students = $db->fetchAll("SELECT * FROM students WHERE course_id = ?", [1]);
    
    // Insert new student
    $result = $db->execute(
        "INSERT INTO students (user_id, student_number, first_name, last_name, course_id, year_level_id, enrollment_date) 
         VALUES (?, ?, ?, ?, ?, ?, ?)",
        [$userId, $studentNumber, $firstName, $lastName, $courseId, $yearLevelId, $enrollmentDate]
    );
    
    // Get last inserted ID
    $studentId = $db->lastInsertId();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
*/

// Test database connection
function testConnection() {
    try {
        $db = getDB();
        $result = $db->fetchOne("SELECT 1 as test");
        echo "Database connection successful!";
        return true;
    } catch (Exception $e) {
        echo "Database connection failed: " . $e->getMessage();
        return false;
    }
}

// Uncomment the line below to test the connection
// testConnection();
?>
