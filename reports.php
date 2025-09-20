<?php
$page_title = 'Reports';
require_once 'includes/header.php';
require_once 'classes/Admin.php';

$session = SessionManager::getInstance();
$session->requireAdmin();

$admin = $current_user; // use logged-in admin

// Get filter parameters
$filters = [
    'course_id' => $_GET['course_id'] ?? null,
    'year_level' => $_GET['year_level'] ?? null,
    'start_date' => $_GET['start_date'] ?? null,
    'end_date' => $_GET['end_date'] ?? null
];

// Get all courses for filter
$courses = $admin->getAllCourses();

// Get attendance statistics
$attendance_stats = $admin->getAttendanceStatistics($filters);
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="fas fa-chart-bar me-2"></i>Attendance Reports
        </h2>
        <p class="text-muted">View attendance statistics and generate reports</p>
    </div>
</div>

<!-- Filter Form -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Reports</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="course_id" class="form-label">Course</label>
                            <select class="form-select" id="course_id" name="course_id">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['course_id']; ?>" 
                                            <?php echo $filters['course_id'] == $course['course_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="year_level" class="form-label">Year Level</label>
                            <select class="form-select" id="year_level" name="year_level">
                                <option value="">All Years</option>
                                <option value="1" <?php echo $filters['year_level'] == '1' ? 'selected' : ''; ?>>1st Year</option>
                                <option value="2" <?php echo $filters['year_level'] == '2' ? 'selected' : ''; ?>>2nd Year</option>
                                <option value="3" <?php echo $filters['year_level'] == '3' ? 'selected' : ''; ?>>3rd Year</option>
                                <option value="4" <?php echo $filters['year_level'] == '4' ? 'selected' : ''; ?>>4th Year</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo htmlspecialchars($filters['start_date']); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo htmlspecialchars($filters['end_date']); ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Generate
                            </button>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <a href="reports.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<?php if (!empty($attendance_stats)): ?>
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-calendar-check fa-2x mb-2"></i>
                <h3 class="mb-1"><?php echo array_sum(array_column($attendance_stats, 'total_records')); ?></h3>
                <p class="mb-0">Total Records</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <h3 class="mb-1"><?php echo array_sum(array_column($attendance_stats, 'present_count')); ?></h3>
                <p class="mb-0">Present</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-2x mb-2"></i>
                <h3 class="mb-1"><?php echo array_sum(array_column($attendance_stats, 'late_count')); ?></h3>
                <p class="mb-0">Late</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-times-circle fa-2x mb-2"></i>
                <h3 class="mb-1"><?php echo array_sum(array_column($attendance_stats, 'absent_count')); ?></h3>
                <p class="mb-0">Absent</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Course Statistics Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>Course Attendance Statistics
                    <?php if (array_filter($filters)): ?>
                        <small class="text-muted">(Filtered Results)</small>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($attendance_stats)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Year Level</th>
                                    <th>Total Records</th>
                                    <th>Present</th>
                                    <th>Late</th>
                                    <th>Absent</th>
                                    <th>Attendance %</th>
                                    <th>Progress Bar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance_stats as $stat): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($stat['course_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($stat['course_code']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $stat['year_level']; ?>th Year</span>
                                        </td>
                                        <td><strong><?php echo $stat['total_records']; ?></strong></td>
                                        <td><span class="badge badge-present"><?php echo $stat['present_count']; ?></span></td>
                                        <td><span class="badge badge-late"><?php echo $stat['late_count']; ?></span></td>
                                        <td><span class="badge badge-absent"><?php echo $stat['absent_count']; ?></span></td>
                                        <td>
                                            <strong><?php echo $stat['attendance_percentage']; ?>%</strong>
                                        </td>
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
                    <div class="text-center py-5">
                        <i class="fas fa-chart-pie fa-4x text-muted mb-4"></i>
                        <h4 class="text-muted">No Statistics Available</h4>
                        <p class="text-muted">
                            <?php if (array_filter($filters)): ?>
                                No attendance statistics found for the selected filters.
                            <?php else: ?>
                                No attendance statistics available. Mark some attendance to see reports here.
                            <?php endif; ?>
                        </p>
                        <?php if (array_filter($filters)): ?>
                            <a href="reports.php" class="btn btn-primary">
                                <i class="fas fa-chart-bar me-2"></i>View All Statistics
                            </a>
                        <?php else: ?>
                            <a href="mark_attendance.php" class="btn btn-primary">
                                <i class="fas fa-check-circle me-2"></i>Mark Attendance
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
