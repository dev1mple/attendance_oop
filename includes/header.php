<?php
require_once 'includes/session.php';
require_once 'classes/User.php';

$session = SessionManager::getInstance();
$session->requireLogin();

// Get current user
$current_user = User::getById($session->getCurrentUserId());
$current_role = $session->getCurrentUserRole();

// Safety: if user record not found (e.g., deleted), force logout
if (!$current_user) {
    $session->logout();
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Attendance System'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #3b82f6;
            --secondary-color: #64748b;
            --accent-color: #06b6d4;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --border-color: #e2e8f0;
            --text-muted: #64748b;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--light-color);
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: var(--shadow-lg);
            position: relative;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 14px 20px;
            border-radius: 12px;
            margin: 4px 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }

        .sidebar .nav-link:hover::before {
            left: 100%;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(8px);
            box-shadow: var(--shadow-md);
        }

        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid var(--accent-color);
        }

        .main-content {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            background: white;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 16px 16px 0 0 !important;
            border: none;
            padding: 20px 24px;
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            background: linear-gradient(135deg, var(--primary-dark) 0%, #1e40af 100%);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 12px;
            padding: 10px 22px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .table thead th {
            background: var(--light-color);
            border: none;
            font-weight: 600;
            color: var(--dark-color);
            padding: 16px;
        }

        .table tbody td {
            padding: 16px;
            border-color: var(--border-color);
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: #f8fafc;
        }

        .badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .badge-present { 
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            color: white;
        }
        
        .badge-late { 
            background: linear-gradient(135deg, var(--warning-color) 0%, #d97706 100%);
            color: white;
        }
        
        .badge-absent { 
            background: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%);
            color: white;
        }

        .stats-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 16px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }

        .stats-card:hover::before {
            top: -30%;
            right: -30%;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--dark-color) !important;
        }

        .navbar {
            background: white !important;
            box-shadow: var(--shadow-sm);
            border-bottom: 1px solid var(--border-color);
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
            font-weight: 500;
        }

        .progress {
            border-radius: 10px;
            background-color: #e2e8f0;
        }

        .progress-bar {
            border-radius: 10px;
            background: linear-gradient(90deg, var(--success-color) 0%, #059669 100%);
        }

        .dropdown-menu {
            border: none;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            padding: 8px;
        }

        .dropdown-item {
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: var(--light-color);
            color: var(--primary-color);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light-color);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--secondary-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
        }

        /* Animation for page load */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .main-content > .container-fluid {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 280px;
                z-index: 1050;
                transition: left 0.3s ease;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-3">
                        <h4 class="text-center mb-4">
                            <i class="fas fa-graduation-cap me-2"></i>
                            Attendance System
                        </h4>
                        
                        <div class="text-center mb-4">
                            <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-user fa-2x text-primary"></i>
                            </div>
                            <div class="mt-2">
                                <h6 class="mb-0"><?php echo htmlspecialchars($current_user->getFullName()); ?></h6>
                                <small class="text-light"><?php echo ucfirst($current_role); ?></small>
                            </div>
                        </div>
                        
                        <nav class="nav flex-column">
                            <?php if ($current_role === 'admin'): ?>
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : ''; ?>" href="admin_dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_courses.php' ? 'active' : ''; ?>" href="manage_courses.php">
                                    <i class="fas fa-book me-2"></i>Manage Courses
                                </a>
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_students.php' ? 'active' : ''; ?>" href="manage_students.php">
                                    <i class="fas fa-users me-2"></i>Manage Students
                                </a>
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'mark_attendance.php' ? 'active' : ''; ?>" href="mark_attendance.php">
                                    <i class="fas fa-check-circle me-2"></i>Mark Attendance
                                </a>
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_excuse_letters.php' ? 'active' : ''; ?>" href="manage_excuse_letters.php">
                                    <i class="fas fa-folder-open me-2"></i>Excuse Letters
                                </a>
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'view_attendance.php' ? 'active' : ''; ?>" href="view_attendance.php">
                                    <i class="fas fa-eye me-2"></i>View Attendance
                                </a>
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                                    <i class="fas fa-chart-bar me-2"></i>Reports
                                </a>
                            <?php else: ?>
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'student_dashboard.php' ? 'active' : ''; ?>" href="student_dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'student_checkin.php' ? 'active' : ''; ?>" href="student_checkin.php">
                                    <i class="fas fa-sign-in-alt me-2"></i>Check In
                                </a>
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'submit_excuse_letter.php' ? 'active' : ''; ?>" href="submit_excuse_letter.php">
                                    <i class="fas fa-envelope-open-text me-2"></i>Excuse Letters
                                </a>
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'my_attendance.php' ? 'active' : ''; ?>" href="my_attendance.php">
                                    <i class="fas fa-calendar-check me-2"></i>My Attendance
                                </a>
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'my_profile.php' ? 'active' : ''; ?>" href="my_profile.php">
                                    <i class="fas fa-user me-2"></i>My Profile
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-0">
                <div class="main-content">
                    <!-- Top Navigation -->
                    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                        <div class="container-fluid">
                            <span class="navbar-brand mb-0 h1"><?php echo $page_title ?? 'Dashboard'; ?></span>
                            
                            <div class="navbar-nav ms-auto">
                                <div class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-user-circle me-1"></i>
                                        <?php echo htmlspecialchars($current_user->getFullName()); ?>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="my_profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </nav>
                    
                    <!-- Page Content -->
                    <div class="container-fluid p-4">
                        <?php
                        // Display flash messages
                        if ($session->hasFlashMessage('success')): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($session->getFlashMessage('success')); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($session->hasFlashMessage('error')): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($session->getFlashMessage('error')); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($session->hasFlashMessage('info')): ?>
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                <?php echo htmlspecialchars($session->getFlashMessage('info')); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
