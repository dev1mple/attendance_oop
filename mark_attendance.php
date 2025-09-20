<?php
$page_title = 'Mark Attendance';
require_once 'includes/header.php';
require_once 'classes/Admin.php';
require_once 'classes/AttendanceManager.php';

$session = SessionManager::getInstance();
$session->requireAdmin();

$admin = $current_user; // use logged-in admin instance
$attendance_manager = new AttendanceManager();

// Get filter parameters
$course_id = $_GET['course_id'] ?? null;
$attendance_date = $_GET['attendance_date'] ?? date('Y-m-d');

// Get all courses for filter
$courses = $admin->getAllCourses();

// Get students for selected course
$students = [];
if ($course_id) {
    $students = $admin->getAllStudents(['course_id' => $course_id]);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $course_id && $attendance_date) {
    $attendance_data = [];
    
    foreach ($_POST['attendance'] as $student_id => $data) {
        $attendance_data[$student_id] = [
            'time_in' => $data['time_in'] ?? null,
            'time_out' => $data['time_out'] ?? null,
            'status' => $data['status'] ?? 'absent',
            'notes' => $data['notes'] ?? ''
        ];
    }
    
    if ($attendance_manager->markBulkAttendance($course_id, $attendance_date, $attendance_data, $session->getCurrentUserId())) {
        $session->setFlashMessage('success', 'Attendance marked successfully!');
        header('Location: mark_attendance.php?course_id=' . $course_id . '&attendance_date=' . $attendance_date);
        exit();
    } else {
        $session->setFlashMessage('error', 'Failed to mark attendance. Please try again.');
    }
}

// Get existing attendance records for the selected date
$existing_records = [];
if ($course_id && $attendance_date) {
    require_once 'config/database.php';
    $db = DatabaseConfig::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT ar.*, s.student_id, s.student_number, u.first_name, u.last_name
        FROM attendance_records ar
        JOIN students s ON ar.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        WHERE ar.course_id = ? AND ar.attendance_date = ?
    ");
    $stmt->execute([$course_id, $attendance_date]);
    $existing_records = $stmt->fetchAll();
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="fas fa-check-circle me-2"></i>Mark Attendance
        </h2>
        <p class="text-muted">Mark attendance for students in a specific course and date</p>
    </div>
</div>

<!-- Filter Form -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Select Course and Date</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="course_id" class="form-label">Course</label>
                            <select class="form-select" id="course_id" name="course_id" required>
                                <option value="">Select a course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['course_id']; ?>" 
                                            <?php echo $course_id == $course['course_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ') - Year ' . $course['year_level']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="attendance_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="attendance_date" name="attendance_date" 
                                   value="<?php echo htmlspecialchars($attendance_date); ?>" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Load
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($course_id && $attendance_date && !empty($students)): ?>
<!-- Attendance Form -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>Mark Attendance
                    <small class="text-muted">
                        - <?php echo htmlspecialchars($courses[array_search($course_id, array_column($courses, 'course_id'))]['course_name']); ?>
                        - <?php echo date('M d, Y', strtotime($attendance_date)); ?>
                    </small>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $session->getCSRFToken(); ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Student Number</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <?php
                                    // Find existing record for this student
                                    $existing_record = null;
                                    foreach ($existing_records as $record) {
                                        if ($record['student_id'] == $student['student_id']) {
                                            $existing_record = $record;
                                            break;
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                                        <td>
                                            <input type="time" class="form-control form-control-sm" 
                                                   name="attendance[<?php echo $student['student_id']; ?>][time_in]"
                                                   value="<?php echo $existing_record['time_in'] ?? ''; ?>">
                                        </td>
                                        <td>
                                            <input type="time" class="form-control form-control-sm" 
                                                   name="attendance[<?php echo $student['student_id']; ?>][time_out]"
                                                   value="<?php echo $existing_record['time_out'] ?? ''; ?>">
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" 
                                                    name="attendance[<?php echo $student['student_id']; ?>][status]">
                                                <option value="present" <?php echo ($existing_record['status'] ?? '') === 'present' ? 'selected' : ''; ?>>Present</option>
                                                <option value="late" <?php echo ($existing_record['status'] ?? '') === 'late' ? 'selected' : ''; ?>>Late</option>
                                                <option value="absent" <?php echo ($existing_record['status'] ?? '') === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" 
                                                   name="attendance[<?php echo $student['student_id']; ?>][notes]"
                                                   value="<?php echo htmlspecialchars($existing_record['notes'] ?? ''); ?>"
                                                   placeholder="Optional notes">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Save Attendance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php elseif ($course_id && $attendance_date && empty($students)): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-users-slash fa-4x text-muted mb-4"></i>
                <h4 class="text-muted">No Students Found</h4>
                <p class="text-muted">No students are enrolled in the selected course.</p>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-calendar-check fa-4x text-muted mb-4"></i>
                <h4 class="text-muted">Select Course and Date</h4>
                <p class="text-muted">Please select a course and date to mark attendance.</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
