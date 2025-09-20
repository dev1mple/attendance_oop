<?php
$page_title = 'View Attendance';
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
    'end_date' => $_GET['end_date'] ?? null,
    'status' => $_GET['status'] ?? null
];

// Get all courses for filter
$courses = $admin->getAllCourses();

// Get attendance records
$attendance_records = $admin->getAttendanceRecords($filters);
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="fas fa-eye me-2"></i>View Attendance Records
        </h2>
        <p class="text-muted">View and filter attendance records by course and year level</p>
    </div>
</div>

<!-- Filter Form -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Records</h5>
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
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="present" <?php echo $filters['status'] == 'present' ? 'selected' : ''; ?>>Present</option>
                                <option value="late" <?php echo $filters['status'] == 'late' ? 'selected' : ''; ?>>Late</option>
                                <option value="absent" <?php echo $filters['status'] == 'absent' ? 'selected' : ''; ?>>Absent</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <a href="view_attendance.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-1"></i>Clear Filters
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Records Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Attendance Records
                    <?php if (array_filter($filters)): ?>
                        <small class="text-muted">(Filtered Results)</small>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($attendance_records)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Student Number</th>
                                    <th>Course</th>
                                    <th>Year Level</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Status</th>
                                    <th>Punctuality</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance_records as $record): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></strong><br>
                                            <small class="text-muted"><?php echo date('l', strtotime($record['attendance_date'])); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($record['student_number']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($record['course_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($record['course_code']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $record['year_level']; ?>th Year</span>
                                        </td>
                                        <td>
                                            <?php if ($record['time_in']): ?>
                                                <span class="badge bg-light text-dark">
                                                    <?php echo date('H:i', strtotime($record['time_in'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($record['time_out']): ?>
                                                <span class="badge bg-light text-dark">
                                                    <?php echo date('H:i', strtotime($record['time_out'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
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
                                            <span class="badge <?php echo $record['status'] === 'late' ? 'badge-late' : ($record['status'] === 'absent' ? 'badge-absent' : 'badge-present'); ?>">
                                                <?php echo $record['punctuality_status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($record['notes']): ?>
                                                <span class="text-muted" title="<?php echo htmlspecialchars($record['notes']); ?>">
                                                    <i class="fas fa-sticky-note"></i>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
                        <h4 class="text-muted">No Attendance Records Found</h4>
                        <p class="text-muted">
                            <?php if (array_filter($filters)): ?>
                                No attendance records found for the selected filters.
                            <?php else: ?>
                                No attendance records found. Mark some attendance to see records here.
                            <?php endif; ?>
                        </p>
                        <?php if (array_filter($filters)): ?>
                            <a href="view_attendance.php" class="btn btn-primary">
                                <i class="fas fa-eye me-2"></i>View All Records
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
