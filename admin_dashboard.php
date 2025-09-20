<?php
$page_title = 'Admin Dashboard';
require_once 'includes/header.php';
require_once 'classes/Admin.php';

$session = SessionManager::getInstance();
$session->requireAdmin();

$admin = $current_user; // use logged-in admin instance
$dashboard_data = $admin->getDashboardData();
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
        </h2>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($current_user->getFullName()); ?>!</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-users fa-2x mb-2"></i>
                <h3 class="mb-1"><?php echo $dashboard_data['total_students']; ?></h3>
                <p class="mb-0">Total Students</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-book fa-2x mb-2"></i>
                <h3 class="mb-1"><?php echo $dashboard_data['total_courses']; ?></h3>
                <p class="mb-0">Total Courses</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-calendar-check fa-2x mb-2"></i>
                <h3 class="mb-1"><?php echo count($dashboard_data['recent_attendance']); ?></h3>
                <p class="mb-0">Recent Records</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-chart-line fa-2x mb-2"></i>
                <h3 class="mb-1"><?php echo count($dashboard_data['attendance_stats']); ?></h3>
                <p class="mb-0">Active Courses</p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="mark_attendance.php" class="btn btn-primary w-100">
                            <i class="fas fa-check-circle me-2"></i>Mark Attendance
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="manage_courses.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-book me-2"></i>Manage Courses
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="manage_students.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-users me-2"></i>Manage Students
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="reports.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-chart-bar me-2"></i>View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Attendance Records -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Recent Attendance Records</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($dashboard_data['recent_attendance'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Time In</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dashboard_data['recent_attendance'] as $record): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($record['course_name']); ?></td>
                                        <td><?php echo $record['time_in'] ? date('H:i', strtotime($record['time_in'])) : '-'; ?></td>
                                        <td>
                                            <?php
                                            $badge_class = '';
                                            switch ($record['status']) {
                                                case 'present':
                                                    $badge_class = 'badge-present';
                                                    break;
                                                case 'late':
                                                    $badge_class = 'badge-late';
                                                    break;
                                                case 'absent':
                                                    $badge_class = 'badge-absent';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo $record['punctuality_status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No recent attendance records found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Course Statistics -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Course Attendance Statistics</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($dashboard_data['attendance_stats'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Year Level</th>
                                    <th>Total Records</th>
                                    <th>Present</th>
                                    <th>Late</th>
                                    <th>Absent</th>
                                    <th>Attendance %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dashboard_data['attendance_stats'] as $stat): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($stat['course_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($stat['course_code']); ?></small>
                                        </td>
                                        <td><?php echo $stat['year_level']; ?></td>
                                        <td><?php echo $stat['total_records']; ?></td>
                                        <td><span class="badge badge-present"><?php echo $stat['present_count']; ?></span></td>
                                        <td><span class="badge badge-late"><?php echo $stat['late_count']; ?></span></td>
                                        <td><span class="badge badge-absent"><?php echo $stat['absent_count']; ?></span></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: <?php echo $stat['attendance_percentage']; ?>%">
                                                    <?php echo $stat['attendance_percentage']; ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No attendance statistics available.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
