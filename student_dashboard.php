<?php
$page_title = 'Student Dashboard';
require_once 'includes/header.php';
require_once 'classes/Student.php';

$session = SessionManager::getInstance();
$session->requireStudent();

$student = new Student();
$dashboard_data = $student->getDashboardData();
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="fas fa-tachometer-alt me-2"></i>Student Dashboard
        </h2>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($current_user->getFullName()); ?>!</p>
    </div>
</div>

<!-- Student Info Card -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Student Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-user fa-2x text-white"></i>
                            </div>
                            <h5><?php echo htmlspecialchars($current_user->getFullName()); ?></h5>
                            <p class="text-muted mb-0">Student</p>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Student Number:</strong> <?php echo htmlspecialchars($dashboard_data['student_info']['student_number']); ?></p>
                                <p><strong>Course:</strong> <?php echo htmlspecialchars($dashboard_data['student_info']['course_name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Year Level:</strong> <?php echo $dashboard_data['student_info']['year_level']; ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-success"><?php echo ucfirst($dashboard_data['student_info']['status']); ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Statistics -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-calendar-check fa-2x mb-2"></i>
                <h3 class="mb-1"><?php echo $dashboard_data['stats']['total_records'] ?? 0; ?></h3>
                <p class="mb-0">Total Records</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <h3 class="mb-1"><?php echo $dashboard_data['stats']['present_count'] ?? 0; ?></h3>
                <p class="mb-0">On Time</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-2x mb-2"></i>
                <h3 class="mb-1"><?php echo $dashboard_data['stats']['late_count'] ?? 0; ?></h3>
                <p class="mb-0">Late</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-percentage fa-2x mb-2"></i>
                <h3 class="mb-1"><?php echo $dashboard_data['stats']['attendance_percentage'] ?? 0; ?>%</h3>
                <p class="mb-0">Attendance Rate</p>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Progress -->
<?php if ($dashboard_data['stats']['total_records'] > 0): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Attendance Overview</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="progress mb-3" style="height: 30px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $dashboard_data['stats']['attendance_percentage']; ?>%">
                                <?php echo $dashboard_data['stats']['attendance_percentage']; ?>% Attendance Rate
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <p class="mb-1"><span class="badge bg-success me-2"><?php echo $dashboard_data['stats']['present_count']; ?></span> On Time</p>
                        <p class="mb-1"><span class="badge bg-warning me-2"><?php echo $dashboard_data['stats']['late_count']; ?></span> Late</p>
                        <p class="mb-0"><span class="badge bg-danger me-2"><?php echo $dashboard_data['stats']['absent_count']; ?></span> Absent</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent Attendance History -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Attendance History</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($dashboard_data['recent_attendance'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Course</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Status</th>
                                    <th>Punctuality</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dashboard_data['recent_attendance'] as $record): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($record['course_name']); ?></td>
                                        <td><?php echo $record['time_in'] ? date('H:i', strtotime($record['time_in'])) : '-'; ?></td>
                                        <td><?php echo $record['time_out'] ? date('H:i', strtotime($record['time_out'])) : '-'; ?></td>
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
                                                <?php echo ucfirst($record['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $record['status'] === 'late' ? 'badge-late' : 'badge-present'; ?>">
                                                <?php echo $record['punctuality_status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="my_attendance.php" class="btn btn-primary">
                            <i class="fas fa-eye me-2"></i>View All Attendance Records
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No attendance records found.</p>
                        <p class="text-muted">Your attendance will appear here once it's been marked by your instructor.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Class Schedule -->
<?php if (!empty($dashboard_data['course_info'])): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Class Schedule</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Room</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dashboard_data['course_info'] as $schedule): ?>
                                <?php if ($schedule['day_of_week']): ?>
                                    <tr>
                                        <td><?php echo $schedule['day_of_week']; ?></td>
                                        <td><?php echo date('H:i', strtotime($schedule['start_time'])) . ' - ' . date('H:i', strtotime($schedule['end_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['room'] ?? 'TBA'); ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
