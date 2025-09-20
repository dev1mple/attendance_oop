<?php
require_once 'includes/session.php';
require_once 'classes/Student.php';
require_once 'classes/ExcuseLetterManager.php';
require_once 'classes/User.php';

$session = SessionManager::getInstance();
$session->requireStudent();

// Initialize current user/student without rendering header yet
$current_user = User::getById($session->getCurrentUserId());
$student = $current_user; // should be Student instance
$manager = new ExcuseLetterManager();

// Handle submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$session->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $session->setFlashMessage('error', 'Invalid CSRF token.');
        header('Location: submit_excuse_letter.php');
        exit();
    }

    $absence_date = $_POST['absence_date'] ?? '';
    $course_id = $_POST['course_id'] !== '' ? (int)$_POST['course_id'] : null;
    $reason = trim($_POST['reason'] ?? '');
    $attachment_path = null;

    if (!$absence_date || !$reason) {
        $session->setFlashMessage('error', 'Please provide date and reason.');
        header('Location: submit_excuse_letter.php');
        exit();
    }

    // Handle file upload if provided
    if (!empty($_FILES['attachment']['name'])) {
        $upload_dir = __DIR__ . '/uploads/excuses';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0755, true);
        }
        $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $safe_name = 'excuse_' . $student->getStudentId() . '_' . time() . '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext);
        $target = $upload_dir . '/' . $safe_name;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target)) {
            $attachment_path = 'uploads/excuses/' . $safe_name;
        }
    }

    if ($manager->submitExcuseLetter($student->getStudentId(), $course_id, $absence_date, $reason, $attachment_path)) {
        $session->setFlashMessage('success', 'Excuse letter submitted successfully.');
    } else {
        $session->setFlashMessage('error', 'Failed to submit excuse letter.');
    }
    header('Location: submit_excuse_letter.php');
    exit();
}

// Fetch student's submissions
$submissions = $manager->getStudentExcuseLetters($student->getStudentId());
$csrf = $session->setCSRFToken();

// Now render header and page
$page_title = 'Excuse Letters';
require_once 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0"><i class="fas fa-envelope-open-text me-2"></i>Excuse Letters</h2>
        <p class="text-muted">Submit an excuse letter and track its status.</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card">
            <div class="card-header"><strong>New Excuse Letter</strong></div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                    <div class="mb-3">
                        <label class="form-label">Absence Date</label>
                        <input type="date" name="absence_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Course/Program (optional)</label>
                        <select name="course_id" class="form-select">
                            <option value="">Select course (optional)</option>
                            <?php
                            // basic course dropdown (all courses)
                            $stmt = DatabaseConfig::getInstance()->getConnection()->prepare("SELECT c.course_id, c.course_name, c.course_code FROM courses c ORDER BY c.course_name");
                            $stmt->execute();
                            foreach ($stmt->fetchAll() as $c): ?>
                                <option value="<?php echo $c['course_id']; ?>"><?php echo htmlspecialchars($c['course_name'] . ' - ' . $c['course_code']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Attachment (optional)</label>
                        <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Accepted: PDF, JPG, PNG</small>
                    </div>
                    <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane me-2"></i>Submit</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><strong>My Submissions</strong></div>
            <div class="card-body">
                <?php if (!empty($submissions)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Submitted</th>
                                    <th>Absence Date</th>
                                    <th>Course</th>
                                    <th>Status</th>
                                    <th>Attachment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($submissions as $s): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($s['created_at'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($s['absence_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($s['course_name'] ?? '—'); ?></td>
                                        <td>
                                            <?php
                                            $badge = 'secondary';
                                            if ($s['status'] === 'approved') $badge = 'success';
                                            else if ($s['status'] === 'rejected') $badge = 'danger';
                                            else if ($s['status'] === 'pending') $badge = 'warning';
                                            ?>
                                            <span class="badge bg-<?php echo $badge; ?> text-uppercase"><?php echo htmlspecialchars($s['status']); ?></span>
                                            <?php if (!empty($s['admin_remarks'])): ?>
                                                <br><small class="text-muted">Remarks: <?php echo htmlspecialchars($s['admin_remarks']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($s['attachment_path'])): ?>
                                                <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?php echo htmlspecialchars($s['attachment_path']); ?>">View</a>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No submissions yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>


