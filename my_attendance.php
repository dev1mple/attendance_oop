<?php
$page_title = 'My Attendance';
require_once 'includes/header.php';
require_once 'classes/Student.php';

$session = SessionManager::getInstance();
$session->requireStudent();

$student = new Student();

// Get filter parameters
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

// Get attendance history
$attendance_records = $student->getAttendanceHistory($start_date, $end_date);
$stats = $student->getAttendanceStats();
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="fas fa-calendar-check me-2"></i>My Attendance Records
        </h2>
        <p class="text-muted">View your attendance history and punctuality status</p>
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
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo htmlspecialchars($start_date); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo htmlspecialchars($end_date); ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-2"></i>Filter
                            </button>
                            <a href="my_attendance.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Summary -->
<?php if ($stats['total_records'] > 0): ?>
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-calendar-check fa-2x mb-2"></i>
                <h3 class="mb-1"><?php echo $stats['total_records']; ?></h3>
                <p class="mb-0">Total Records</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <h3 class="mb-1"><?php echo $stats['present_count']; ?></h3>
                <p class="mb-0">On Time</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-2x mb-2"></i>
                <h3 class="mb-1"><?php echo $stats['late_count']; ?></h3>
                <p class="mb-0">Late</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-percentage fa-2x mb-2"></i>
                <h3 class="mb-1"><?php echo $stats['attendance_percentage']; ?>%</h3>
                <p class="mb-0">Attendance Rate</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Attendance Records Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Attendance Records
                    <?php if ($start_date || $end_date): ?>
                        <small class="text-muted">
                            (Filtered: <?php echo $start_date ? date('M d, Y', strtotime($start_date)) : 'Start'; ?> - 
                            <?php echo $end_date ? date('M d, Y', strtotime($end_date)) : 'End'; ?>)
                        </small>
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
                                    <th>Course</th>
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
                                        <td>
                                            <strong><?php echo htmlspecialchars($record['course_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($record['course_code']); ?></small>
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
                                            <?php if ($record['status'] === 'absent'): ?>
                                                <span class="badge badge-absent">Absent</span>
                                            <?php else: ?>
                                                <span class="badge <?php echo $record['status'] === 'late' ? 'badge-late' : 'badge-present'; ?>">
                                                    <?php echo $record['punctuality_status']; ?>
                                                </span>
                                            <?php endif; ?>
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
                            <?php if ($start_date || $end_date): ?>
                                No attendance records found for the selected date range.
                            <?php else: ?>
                                You don't have any attendance records yet. Your attendance will appear here once it's been marked by your instructor.
                            <?php endif; ?>
                        </p>
                        <?php if ($start_date || $end_date): ?>
                            <a href="my_attendance.php" class="btn btn-primary">
                                <i class="fas fa-eye me-2"></i>View All Records
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
