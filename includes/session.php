<?php
/**
 * Session Management
 * Handles user sessions and security
 */
class SessionManager {
    private static $instance = null;
    
    private function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Login user and create session
     */
    public function login($user) {
        $_SESSION['user_id'] = $user->getUserId();
        $_SESSION['username'] = $user->getUsername();
        $_SESSION['role'] = $user->getRole();
        $_SESSION['full_name'] = $user->getFullName();
        $_SESSION['login_time'] = time();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
    }
    
    /**
     * Logout user and destroy session
     */
    public function logout() {
        // Clear all session variables
        $_SESSION = array();
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current user ID
     */
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user role
     */
    public function getCurrentUserRole() {
        return $_SESSION['role'] ?? null;
    }
    
    /**
     * Get current username
     */
    public function getCurrentUsername() {
        return $_SESSION['username'] ?? null;
    }
    
    /**
     * Get current user full name
     */
    public function getCurrentUserFullName() {
        return $_SESSION['full_name'] ?? null;
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole($role) {
        return $this->getCurrentUserRole() === $role;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->hasRole('admin');
    }
    
    /**
     * Check if user is student
     */
    public function isStudent() {
        return $this->hasRole('student');
    }
    
    /**
     * Require login - redirect if not logged in
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
    
    /**
     * Require specific role - redirect if not authorized
     */
    public function requireRole($role) {
        $this->requireLogin();
        
        if (!$this->hasRole($role)) {
            header('Location: unauthorized.php');
            exit();
        }
    }
    
    /**
     * Require admin role
     */
    public function requireAdmin() {
        $this->requireRole('admin');
    }
    
    /**
     * Require student role
     */
    public function requireStudent() {
        $this->requireRole('student');
    }
    
    /**
     * Set flash message
     */
    public function setFlashMessage($type, $message) {
        $_SESSION['flash'][$type] = $message;
    }
    
    /**
     * Get and clear flash message
     */
    public function getFlashMessage($type) {
        if (isset($_SESSION['flash'][$type])) {
            $message = $_SESSION['flash'][$type];
            unset($_SESSION['flash'][$type]);
            return $message;
        }
        return null;
    }
    
    /**
     * Check for flash messages
     */
    public function hasFlashMessage($type) {
        return isset($_SESSION['flash'][$type]);
    }
    
    /**
     * Set CSRF token
     */
    public function setCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get CSRF token
     */
    public function getCSRFToken() {
        return $_SESSION['csrf_token'] ?? null;
    }
    
    /**
     * Check session timeout
     */
    public function checkSessionTimeout($timeout_minutes = 30) {
        if (isset($_SESSION['login_time'])) {
            $timeout = $timeout_minutes * 60; // Convert to seconds
            if ((time() - $_SESSION['login_time']) > $timeout) {
                $this->logout();
                return false;
            }
        }
        return true;
    }
    
    /**
     * Update last activity time
     */
    public function updateLastActivity() {
        $_SESSION['last_activity'] = time();
    }
}
?>
