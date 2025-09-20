<?php
require_once 'includes/session.php';
require_once 'classes/Admin.php';
require_once 'classes/ExcuseLetterManager.php';
require_once 'classes/User.php';

$session = SessionManager::getInstance();
$session->requireAdmin();

// Initialize current admin without rendering header yet
$current_user = User::getById($session->getCurrentUserId());
$admin = $current_user; // Admin instance
$manager = new ExcuseLetterManager();

// Review action BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['excuse_id'])) {
    if (!$session->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $session->setFlashMessage('error', 'Invalid CSRF token.');
        header('Location: manage_excuse_letters.php');
        exit();
    }
    $decision = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    $remarks = trim($_POST['remarks'] ?? '');
    if ($manager->reviewExcuseLetter((int)$_POST['excuse_id'], $admin->getUserId(), $decision, $remarks)) {
        $session->setFlashMessage('success', 'Excuse letter updated.');
    } else {
        $session->setFlashMessage('error', 'Failed to update excuse letter.');
    }
    header('Location: manage_excuse_letters.php');
    exit();
}

// Filters
$filters = [
    'course_id' => isset($_GET['course_id']) && $_GET['course_id'] !== '' ? (int)$_GET['course_id'] : null,
    'year_level' => isset($_GET['year_level']) && $_GET['year_level'] !== '' ? (int)$_GET['year_level'] : null,
    'status' => $_GET['status'] ?? null,
    'start_date' => $_GET['start_date'] ?? null,
    'end_date' => $_GET['end_date'] ?? null,
];

$records = $manager->getExcuseLetters($filters);
$csrf = $session->setCSRFToken();

// Now render header and page
$page_title = 'Manage Excuse Letters';
require_once 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0"><i class="fas fa-folder-open me-2"></i>Manage Excuse Letters</h2>
        <p class="text-muted">Review, approve, or reject student submissions. Filter by program.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><strong>Filters</strong></div>
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Program/Course</label>
                <select name="course_id" class="form-select">
                    <option value="">All</option>
                    <?php $stmt = DatabaseConfig::getInstance()->getConnection()->query("SELECT course_id, course_name, course_code, year_level FROM courses ORDER BY course_name");
                    foreach ($stmt->fetchAll() as $c): ?>
                        <option value="<?php echo $c['course_id']; ?>" <?php echo ($filters['course_id'] == $c['course_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['course_name'] . ' (' . $c['course_code'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Year Level</label>
                <select name="year_level" class="form-select">
                    <option value="">All</option>
                    <?php for ($i=1; $i<=5; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($filters['year_level'] == $i) ? 'selected' : ''; ?>>Year <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <?php foreach (['pending','approved','rejected'] as $st): ?>
                        <option value="<?php echo $st; ?>" <?php echo ($filters['status'] === $st) ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">From</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($filters['start_date'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">To</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($filters['end_date'] ?? ''); ?>">
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary w-100" type="submit"><i class="fas fa-filter me-1"></i>Filter</button>
            </div>
        </form>
    </div>
    </div>

<div class="card">
    <div class="card-header"><strong>Excuse Letters</strong></div>
    <div class="card-body">
        <?php if (!empty($records)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Submitted</th>
                            <th>Student</th>
                            <th>Program</th>
                            <th>Absence Date</th>
                            <th>Status</th>
                            <th>Attachment</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $row): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['student_number']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(($row['course_name'] ?? '—') . (!empty($row['course_code']) ? ' (' . $row['course_code'] . ')' : '')); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['absence_date'])); ?></td>
                                <td>
                                    <?php $badge = $row['status'] === 'approved' ? 'success' : ($row['status'] === 'rejected' ? 'danger' : 'warning'); ?>
                                    <span class="badge bg-<?php echo $badge; ?> text-uppercase"><?php echo htmlspecialchars($row['status']); ?></span>
                                    <?php if (!empty($row['admin_remarks'])): ?>
                                        <br><small class="text-muted">Remarks: <?php echo htmlspecialchars($row['admin_remarks']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['attachment_path'])): ?>
                                        <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?php echo htmlspecialchars($row['attachment_path']); ?>">View</a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <form method="post" onsubmit="return confirm('Approve this excuse letter?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                            <input type="hidden" name="excuse_id" value="<?php echo (int)$row['excuse_id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="remarks" value="Approved by admin">
                                            <button class="btn btn-sm btn-success" <?php echo $row['status'] !== 'pending' ? 'disabled' : ''; ?>>Approve</button>
                                        </form>
                                        <form method="post" onsubmit="return confirm('Reject this excuse letter?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                            <input type="hidden" name="excuse_id" value="<?php echo (int)$row['excuse_id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="remarks" value="Rejected by admin">
                                            <button class="btn btn-sm btn-danger" <?php echo $row['status'] !== 'pending' ? 'disabled' : ''; ?>>Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                <p class="text-muted">No excuse letters found for the selected filters.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>


