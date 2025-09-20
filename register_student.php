<?php
$page_title = 'Register Student';
require_once 'includes/session.php';
require_once 'classes/Admin.php';

$session = SessionManager::getInstance();

// Allow either: not logged in (self-register as student) OR admin creating a student
$is_admin = $session->isLoggedIn() && $session->isAdmin();

$admin = $is_admin ? new Admin() : null;

require_once 'config/database.php';
$db = DatabaseConfig::getInstance()->getConnection();
$courses = [];
$stmt = $db->prepare("SELECT course_id, course_name, course_code, year_level FROM courses ORDER BY year_level, course_name");
$stmt->execute();
$courses = $stmt->fetchAll();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$username = trim($_POST['username'] ?? '');
	$password = $_POST['password'] ?? '';
	$email = trim($_POST['email'] ?? '');
	$first_name = trim($_POST['first_name'] ?? '');
	$last_name = trim($_POST['last_name'] ?? '');
	$student_number = trim($_POST['student_number'] ?? '');
	$course_id = (int)($_POST['course_id'] ?? 0);
	$year_level = (int)($_POST['year_level'] ?? 0);
	$enrollment_date = $_POST['enrollment_date'] ?? date('Y-m-d');

	if (!$username || !$password || !$email || !$first_name || !$last_name || !$student_number || !$course_id || !$year_level) {
		$error = 'Please fill in all required fields.';
	} else {
		try {
			if ($is_admin) {
				if ($admin->addStudent([
					'username' => $username,
					'password' => $password,
					'email' => $email,
					'first_name' => $first_name,
					'last_name' => $last_name
				], [
					'student_number' => $student_number,
					'course_id' => $course_id,
					'year_level' => $year_level,
					'enrollment_date' => $enrollment_date
				])) {
				$success = 'Student registered successfully!';
				$_POST = [];
			} else {
				$error = 'Failed to register student (username/email may already exist).';
			}
			} else {
				// Self-register as student (no admin session)
				$db->beginTransaction();
				$u = $db->prepare("INSERT INTO users (username, password, email, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, 'student')");
				$u->execute([$username, password_hash($password, PASSWORD_DEFAULT), $email, $first_name, $last_name]);
				$user_id = $db->lastInsertId();
				$s = $db->prepare("INSERT INTO students (user_id, student_number, course_id, year_level, enrollment_date) VALUES (?, ?, ?, ?, ?)");
				$s->execute([$user_id, $student_number, $course_id, $year_level, $enrollment_date]);
				$db->commit();
				$success = 'Registration successful. You can now log in.';
				$_POST = [];
			}
		} catch (Exception $e) {
			if ($db->inTransaction()) { $db->rollBack(); }
			$error = 'Registration failed.';
		}
	}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Register Student</title>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
			background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 50%, var(--accent-color) 100%);
			min-height: 100vh;
			display: flex;
			align-items: center;
			position: relative;
			overflow: hidden;
		}

		body::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
			opacity: 0.3;
		}

		.register-card {
			background: rgba(255, 255, 255, 0.98);
			backdrop-filter: blur(20px);
			border-radius: 24px;
			box-shadow: var(--shadow-xl);
			border: 1px solid rgba(255, 255, 255, 0.2);
			position: relative;
			overflow: hidden;
		}

		.register-card::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			height: 4px;
			background: linear-gradient(90deg, var(--primary-color) 0%, var(--accent-color) 50%, var(--success-color) 100%);
		}

		.card-header {
			background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
			color: white;
			border-radius: 24px 24px 0 0;
			padding: 2rem;
			text-align: center;
			position: relative;
			overflow: hidden;
		}

		.card-header::before {
			content: '';
			position: absolute;
			top: -50%;
			right: -50%;
			width: 100%;
			height: 100%;
			background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
		}

		.card-header h5 {
			font-weight: 700;
			font-size: 1.5rem;
			margin: 0;
			position: relative;
			z-index: 1;
		}

		.card-header i {
			font-size: 2.5rem;
			margin-bottom: 1rem;
			position: relative;
			z-index: 1;
			display: block;
		}

		.form-control, .form-select {
			border-radius: 12px;
			border: 2px solid var(--border-color);
			padding: 14px 18px;
			font-size: 1rem;
			transition: all 0.3s ease;
			background: white;
		}

		.form-control:focus, .form-select:focus {
			border-color: var(--primary-color);
			box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
			background: white;
		}

		.form-label {
			font-weight: 600;
			color: var(--dark-color);
			margin-bottom: 8px;
		}

		.btn-primary {
			background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
			border: none;
			border-radius: 12px;
			padding: 14px 24px;
			font-weight: 600;
			font-size: 1rem;
			transition: all 0.3s ease;
			box-shadow: var(--shadow-sm);
		}

		.btn-primary:hover {
			transform: translateY(-2px);
			box-shadow: var(--shadow-lg);
			background: linear-gradient(135deg, var(--primary-dark) 0%, #1e40af 100%);
		}

		.btn-outline-secondary {
			border: 2px solid var(--secondary-color);
			color: var(--secondary-color);
			border-radius: 12px;
			padding: 12px 20px;
			font-weight: 600;
			transition: all 0.3s ease;
			background: white;
		}

		.btn-outline-secondary:hover {
			background: var(--secondary-color);
			transform: translateY(-2px);
			box-shadow: var(--shadow-md);
		}

		.alert {
			border: none;
			border-radius: 12px;
			padding: 16px 20px;
			font-weight: 500;
		}

		.alert-success {
			background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
			color: var(--success-color);
			border-left: 4px solid var(--success-color);
		}

		.alert-danger {
			background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
			color: var(--danger-color);
			border-left: 4px solid var(--danger-color);
		}

		.card-body {
			padding: 2.5rem;
		}

		/* Animation for register card */
		@keyframes slideInUp {
			from {
				opacity: 0;
				transform: translateY(30px);
			}
			to {
				opacity: 1;
				transform: translateY(0);
			}
		}

		.register-card {
			animation: slideInUp 0.6s ease-out;
		}

		/* Floating elements animation */
		@keyframes float {
			0%, 100% {
				transform: translateY(0px);
			}
			50% {
				transform: translateY(-10px);
			}
		}

		.card-header i {
			animation: float 3s ease-in-out infinite;
		}
	</style>
</head>
<body>
	<div class="container py-5">
		<div class="row justify-content-center">
			<div class="col-lg-7">
				<div class="card register-card">
					<div class="card-header">
						<i class="fas fa-user-plus"></i>
						<h5>Register as Student</h5>
					</div>
					<div class="card-body">
						<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
						<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

						<form method="POST" action="">
							<div class="row g-3">
								<div class="col-md-6">
									<label class="form-label"><i class="fas fa-user me-2"></i>Username</label>
									<input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
								</div>
								<div class="col-md-6">
									<label class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
									<input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
								</div>
								<div class="col-md-6">
									<label class="form-label"><i class="fas fa-id-card me-2"></i>First Name</label>
									<input type="text" name="first_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
								</div>
								<div class="col-md-6">
									<label class="form-label"><i class="fas fa-id-card me-2"></i>Last Name</label>
									<input type="text" name="last_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
								</div>
								<div class="col-md-6">
									<label class="form-label"><i class="fas fa-lock me-2"></i>Password</label>
									<input type="password" name="password" class="form-control" required>
								</div>
								<div class="col-md-6">
									<label class="form-label"><i class="fas fa-id-badge me-2"></i>Student Number</label>
									<input type="text" name="student_number" class="form-control" required value="<?php echo htmlspecialchars($_POST['student_number'] ?? ''); ?>">
								</div>
								<div class="col-md-6">
									<label class="form-label"><i class="fas fa-graduation-cap me-2"></i>Course</label>
									<select name="course_id" class="form-select" required>
										<option value="">Select Course</option>
										<?php foreach ($courses as $c): ?>
											<option value="<?php echo $c['course_id']; ?>" <?php echo (($_POST['course_id'] ?? '') == $c['course_id']) ? 'selected' : ''; ?>>
												<?php echo htmlspecialchars($c['course_name'] . ' (' . $c['course_code'] . ') - Year ' . $c['year_level']); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="col-md-6">
									<label class="form-label"><i class="fas fa-calendar-alt me-2"></i>Year Level</label>
									<select name="year_level" class="form-select" required>
										<option value="">Select Year</option>
										<?php for ($y=1; $y<=4; $y++): ?>
											<option value="<?php echo $y; ?>" <?php echo (($_POST['year_level'] ?? '') == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
										<?php endfor; ?>
									</select>
								</div>
								<div class="col-md-6">
									<label class="form-label"><i class="fas fa-calendar me-2"></i>Enrollment Date</label>
									<input type="date" name="enrollment_date" class="form-control" value="<?php echo htmlspecialchars($_POST['enrollment_date'] ?? date('Y-m-d')); ?>">
								</div>
							</div>
							<div class="text-center mt-4">
								<button type="submit" class="btn btn-primary me-3">
									<i class="fas fa-user-plus me-2"></i>Register
								</button>
								<a href="login.php" class="btn btn-outline-secondary">
									<i class="fas fa-arrow-left me-2"></i>Back to Login
								</a>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
