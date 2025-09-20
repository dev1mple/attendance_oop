<?php
// Test script to debug login issues
require_once 'config/database.php';
require_once 'classes/User.php';

echo "<h2>Login Debug Test</h2>";

try {
    // Test database connection
    echo "<h3>1. Testing Database Connection:</h3>";
    $db = DatabaseConfig::getInstance()->getConnection();
    echo "✅ Database connection successful<br>";
    
    // Test if users table exists and has data
    echo "<h3>2. Testing Users Table:</h3>";
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "Users in database: " . $result['count'] . "<br>";
    
    if ($result['count'] > 0) {
        // Show all users
        $stmt = $db->prepare("SELECT user_id, username, email, role FROM users");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        echo "<h4>Users in database:</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['user_id'] . "</td>";
            echo "<td>" . $user['username'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test password verification
        echo "<h3>3. Testing Password Verification:</h3>";
        $test_username = 'admin';
        $test_password = 'password';
        
        $stmt = $db->prepare("SELECT password FROM users WHERE username = ?");
        $stmt->execute([$test_username]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "Found user: $test_username<br>";
            echo "Stored hash: " . $user['password'] . "<br>";
            
            if (password_verify($test_password, $user['password'])) {
                echo "✅ Password verification successful<br>";
            } else {
                echo "❌ Password verification failed<br>";
                echo "Trying to create new hash...<br>";
                $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
                echo "New hash: $new_hash<br>";
                
                // Update the password
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = ?");
                $stmt->execute([$new_hash, $test_username]);
                echo "✅ Password updated in database<br>";
            }
        } else {
            echo "❌ User '$test_username' not found<br>";
        }
        
        // Test User class authentication
        echo "<h3>4. Testing User Class Authentication:</h3>";
        $user = User::authenticate($test_username, $test_password);
        if ($user) {
            echo "✅ User authentication successful<br>";
            echo "User role: " . $user->getRole() . "<br>";
        } else {
            echo "❌ User authentication failed<br>";
        }
        
    } else {
        echo "❌ No users found in database. Please import the schema.<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='login.php'>Back to Login</a>";
?>
