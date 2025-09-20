<?php
require_once 'config/database.php';

/**
 * Base User Class
 * Abstract base class for all user types following OOP principles
 */
abstract class User {
    protected $user_id;
    protected $username;
    protected $email;
    protected $first_name;
    protected $last_name;
    protected $role;
    protected $db;
    
    public function __construct($user_data = null) {
        $this->db = DatabaseConfig::getInstance()->getConnection();
        
        if ($user_data) {
            $this->loadUserData($user_data);
        }
    }
    
    /**
     * Load user data from array
     */
    protected function loadUserData($data) {
        $this->user_id = $data['user_id'] ?? null;
        $this->username = $data['username'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->first_name = $data['first_name'] ?? null;
        $this->last_name = $data['last_name'] ?? null;
        $this->role = $data['role'] ?? null;
    }
    
    /**
     * Authenticate user login
     */
    public static function authenticate($usernameOrEmail, $password) {
        $db = DatabaseConfig::getInstance()->getConnection();
        
        // Allow login via username OR email (case-insensitive on email)
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR LOWER(email) = LOWER(?) LIMIT 1");
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            return self::createUserInstance($user);
        }
        
        return false;
    }
    
    /**
     * Factory method to create appropriate user instance
     */
    public static function createUserInstance($user_data) {
        switch ($user_data['role']) {
            case 'student':
                require_once 'classes/Student.php';
                return new Student($user_data);
            case 'admin':
                require_once 'classes/Admin.php';
                return new Admin($user_data);
            default:
                throw new Exception("Invalid user role");
        }
    }
    
    /**
     * Get user by ID
     */
    public static function getById($user_id) {
        $db = DatabaseConfig::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            return self::createUserInstance($user);
        }
        
        return null;
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($data) {
        $allowed_fields = ['email', 'first_name', 'last_name'];
        $update_fields = [];
        $values = [];
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($update_fields)) {
            return false;
        }
        
        $values[] = $this->user_id;
        $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Change password
     */
    public function changePassword($current_password, $new_password) {
        // Verify current password
        $stmt = $this->db->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$this->user_id]);
        $user = $stmt->fetch();
        
        if (!password_verify($current_password, $user['password'])) {
            return false;
        }
        
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        return $stmt->execute([$hashed_password, $this->user_id]);
    }
    
    // Getters
    public function getUserId() { return $this->user_id; }
    public function getUsername() { return $this->username; }
    public function getEmail() { return $this->email; }
    public function getFirstName() { return $this->first_name; }
    public function getLastName() { return $this->last_name; }
    public function getRole() { return $this->role; }
    public function getFullName() { return $this->first_name . ' ' . $this->last_name; }
    
    // Abstract methods to be implemented by subclasses
    abstract public function getDashboardData();
    abstract public function hasPermission($action);
}
?>
