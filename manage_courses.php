<?php
$page_title = 'Manage Courses';
require_once 'includes/header.php';
require_once 'classes/Admin.php';

$session = SessionManager::getInstance();
$session->requireAdmin();

$admin = $current_user; // use logged-in admin instance so created_by is set

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $course_data = [
                    'course_code' => $_POST['course_code'],
                    'course_name' => $_POST['course_name'],
                    'year_level' => $_POST['year_level'],
                    'description' => $_POST['description']
                ];
                
                if ($admin->addCourse($course_data)) {
                    $session->setFlashMessage('success', 'Course added successfully!');
                } else {
                    $session->setFlashMessage('error', 'Failed to add course. Please try again.');
                }
                break;
                
            case 'edit':
                $course_id = $_POST['course_id'];
                $course_data = [
                    'course_code' => $_POST['course_code'],
                    'course_name' => $_POST['course_name'],
                    'year_level' => $_POST['year_level'],
                    'description' => $_POST['description']
                ];
                
                if ($admin->updateCourse($course_id, $course_data)) {
                    $session->setFlashMessage('success', 'Course updated successfully!');
                } else {
                    $session->setFlashMessage('error', 'Failed to update course. Please try again.');
                }
                break;
                
            case 'delete':
                $course_id = $_POST['course_id'];
                if ($admin->deleteCourse($course_id)) {
                    $session->setFlashMessage('success', 'Course deleted successfully!');
                } else {
                    $session->setFlashMessage('error', 'Failed to delete course. Please try again.');
                }
                break;
        }
        
        header('Location: manage_courses.php');
        exit();
    }
}

// Get all courses
$courses = $admin->getAllCourses();
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="fas fa-book me-2"></i>Manage Courses
        </h2>
        <p class="text-muted">Add, edit, and manage courses in the system</p>
    </div>
</div>

<!-- Add Course Form -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Add New Course</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?php echo $session->getCSRFToken(); ?>">
                    
                    <div class="row">
                        <div class="col-md-3">
                            <label for="course_code" class="form-label">Course Code</label>
                            <input type="text" class="form-control" id="course_code" name="course_code" required>
                        </div>
                        <div class="col-md-4">
                            <label for="course_name" class="form-label">Course Name</label>
                            <input type="text" class="form-control" id="course_name" name="course_name" required>
                        </div>
                        <div class="col-md-2">
                            <label for="year_level" class="form-label">Year Level</label>
                            <select class="form-select" id="year_level" name="year_level" required>
                                <option value="">Select Year</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="description" name="description" placeholder="Optional description">
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Course
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Courses List -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Existing Courses</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($courses)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Year Level</th>
                                    <th>Students</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $course['year_level']; ?>th Year</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $course['student_count']; ?> students</span>
                                        </td>
                                        <td><?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editCourse(<?php echo htmlspecialchars(json_encode($course)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger btn-delete" onclick="deleteCourse(<?php echo $course['course_id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-book fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No courses found. Add your first course above.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="course_id" id="edit_course_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $session->getCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="edit_course_code" class="form-label">Course Code</label>
                        <input type="text" class="form-control" id="edit_course_code" name="course_code" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_course_name" class="form-label">Course Name</label>
                        <input type="text" class="form-control" id="edit_course_name" name="course_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_year_level" class="form-label">Year Level</label>
                        <select class="form-select" id="edit_year_level" name="year_level" required>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="edit_description" name="description">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Course Modal -->
<div class="modal fade" id="deleteCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="course_id" id="delete_course_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $session->getCSRFToken(); ?>">
                    
                    <p>Are you sure you want to delete this course? This action cannot be undone.</p>
                    <p><strong>Note:</strong> All students enrolled in this course will also be deleted.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCourse(course) {
    document.getElementById('edit_course_id').value = course.course_id;
    document.getElementById('edit_course_code').value = course.course_code;
    document.getElementById('edit_course_name').value = course.course_name;
    document.getElementById('edit_year_level').value = course.year_level;
    document.getElementById('edit_description').value = course.description || '';
    
    new bootstrap.Modal(document.getElementById('editCourseModal')).show();
}

function deleteCourse(courseId) {
    document.getElementById('delete_course_id').value = courseId;
    new bootstrap.Modal(document.getElementById('deleteCourseModal')).show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
