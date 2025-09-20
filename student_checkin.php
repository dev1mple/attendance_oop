<?php
$page_title = 'Student Check-In';
require_once 'includes/header.php';
require_once 'classes/Student.php';
require_once 'classes/AttendanceManager.php';

$session = SessionManager::getInstance();
$session->requireStudent();

$student = $current_user; // use logged-in student instance loaded in header
$attendance = new AttendanceManager();

$today = date('Y-m-d');
$now = date('H:i:s');

$success = '';
$error = '';

// Load student's course
$course_id = $student->getCourseId();
if (!$course_id) {
    $error = 'Your account is not linked to a course. Please contact admin.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$time_in = $_POST['time_in'] ?? date('H:i:s');
	$notes = trim($_POST['notes'] ?? '');

	try {
		// Determine status automatically
		$status = $attendance->determineAttendanceStatus($course_id, $time_in, $today);
		$ok = $attendance->markSingleAttendance($student->getStudentId(), $course_id, $today, $time_in, null, $status, $notes, $session->getCurrentUserId());
		if ($ok) {
			$success = 'Check-in recorded successfully!';
		} else {
			$error = 'Failed to record check-in.';
		}
	} catch (Exception $e) {
		$error = 'Failed to record check-in.';
	}
}

// Fetch today's record if exists
require_once 'config/database.php';
$db = DatabaseConfig::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM attendance_records WHERE student_id = ? AND attendance_date = ?");
$stmt->execute([$student->getStudentId(), $today]);
$todayRecord = $stmt->fetch();
?>

<div class="row mb-4">
	<div class="col-12">
		<h2 class="mb-0">
			<i class="fas fa-sign-in-alt me-2"></i>Check In
		</h2>
		<p class="text-muted">File your attendance for today</p>
	</div>
</div>

<?php if ($success): ?>
	<div class="alert alert-success alert-dismissible fade show" role="alert">
		<i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
		<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
	</div>
<?php endif; ?>
<?php if ($error): ?>
	<div class="alert alert-danger alert-dismissible fade show" role="alert">
		<i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
		<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
	</div>
<?php endif; ?>

<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<h5 class="mb-0"><i class="fas fa-clock me-2"></i>Today's Attendance</h5>
			</div>
			<div class="card-body">
				<?php if ($todayRecord): ?>
					<p class="mb-2"><strong>Date:</strong> <?php echo date('M d, Y', strtotime($today)); ?></p>
					<p class="mb-2"><strong>Status:</strong> <span class="badge <?php echo $todayRecord['status']==='late'?'badge-late':'badge-present'; ?>"><?php echo ucfirst($todayRecord['status']); ?></span></p>
					<p class="mb-4"><strong>Time In:</strong> <?php echo $todayRecord['time_in'] ? date('H:i', strtotime($todayRecord['time_in'])) : '-'; ?></p>
					<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Your attendance for today is already recorded.</div>
				<?php else: ?>
					<form method="POST" action="">
						<div class="row g-3">
							<div class="col-md-4">
								<label class="form-label">Date</label>
								<input type="text" class="form-control" value="<?php echo date('M d, Y', strtotime($today)); ?>" readonly>
							</div>
							<div class="col-md-4">
								<label class="form-label">Time In</label>
								<input type="time" name="time_in" class="form-control" value="<?php echo htmlspecialchars($now); ?>">
							</div>
							<div class="col-md-4">
								<label class="form-label">Notes (optional)</label>
								<input type="text" name="notes" class="form-control" placeholder="Any remarks">
							</div>
						</div>
						<div class="text-center mt-3">
							<button type="submit" class="btn btn-primary">
								<i class="fas fa-check me-2"></i>Submit Check-In
							</button>
						</div>
					</form>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<?php require_once 'includes/footer.php'; ?>
