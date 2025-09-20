<?php
$page_title = 'My Profile';
require_once 'includes/header.php';
require_once 'classes/User.php';

$session = SessionManager::getInstance();
$session->requireLogin();

$current_user = User::getById($session->getCurrentUserId());
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $profile_data = [
                    'email' => $_POST['email'],
                    'first_name' => $_POST['first_name'],
                    'last_name' => $_POST['last_name']
                ];
                
                if ($current_user->updateProfile($profile_data)) {
                    $success_message = 'Profile updated successfully!';
                    // Refresh user data
                    $current_user = User::getById($session->getCurrentUserId());
                } else {
                    $error_message = 'Failed to update profile. Please try again.';
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if ($new_password !== $confirm_password) {
                    $error_message = 'New passwords do not match.';
                } elseif (strlen($new_password) < 6) {
                    $error_message = 'New password must be at least 6 characters long.';
                } else {
                    if ($current_user->changePassword($current_password, $new_password)) {
                        $success_message = 'Password changed successfully!';
                    } else {
                        $error_message = 'Current password is incorrect.';
                    }
                }
                break;
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="fas fa-user me-2"></i>My Profile
        </h2>
        <p class="text-muted">Manage your account information and settings</p>
    </div>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Profile Information -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Profile Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="csrf_token" value="<?php echo $session->getCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($current_user->getUsername()); ?>" readonly>
                        <div class="form-text">Username cannot be changed</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($current_user->getEmail()); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($current_user->getFirstName()); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($current_user->getLastName()); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <input type="text" class="form-control" id="role" value="<?php echo ucfirst($current_user->getRole()); ?>" readonly>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Change Password -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="csrf_token" value="<?php echo $session->getCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                        <div class="form-text">Password must be at least 6 characters long</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key me-2"></i>Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Student Information (if user is a student) -->
<?php if ($current_user->getRole() === 'student'): ?>
    <?php
    // Get student information
    require_once 'config/database.php';
    $db = DatabaseConfig::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT s.*, c.course_name, c.course_code 
        FROM students s 
        JOIN courses c ON s.course_id = c.course_id 
        WHERE s.user_id = ?
    ");
    $stmt->execute([$current_user->getUserId()]);
    $student_info = $stmt->fetch();
    ?>
    
    <?php if ($student_info): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Student Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <p><strong>Student Number:</strong><br><?php echo htmlspecialchars($student_info['student_number']); ?></p>
                        </div>
                        <div class="col-md-3">
                            <p><strong>Course:</strong><br><?php echo htmlspecialchars($student_info['course_name']); ?></p>
                        </div>
                        <div class="col-md-3">
                            <p><strong>Course Code:</strong><br><?php echo htmlspecialchars($student_info['course_code']); ?></p>
                        </div>
                        <div class="col-md-3">
                            <p><strong>Year Level:</strong><br><?php echo $student_info['year_level']; ?>th Year</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Enrollment Date:</strong><br><?php echo date('M d, Y', strtotime($student_info['enrollment_date'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong><br>
                                <span class="badge bg-success"><?php echo ucfirst($student_info['status']); ?></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
