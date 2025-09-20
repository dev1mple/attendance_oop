<?php
require_once 'User.php';

/**
 * Admin Class
 * Extends User class for admin-specific functionality
 */
class Admin extends User {
    
    public function __construct($user_data = null) {
        parent::__construct($user_data);
    }
    
    /**
     * Add new course
     */
    public function addCourse($course_data) {
        $sql = "INSERT INTO courses (course_code, course_name, year_level, description, created_by) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $course_data['course_code'],
            $course_data['course_name'],
            $course_data['year_level'],
            $course_data['description'] ?? '',
            $this->user_id
        ]);
    }
    
    /**
     * Update course information
     */
    public function updateCourse($course_id, $course_data) {
        $allowed_fields = ['course_code', 'course_name', 'year_level', 'description'];
        $update_fields = [];
        $values = [];
        
        foreach ($allowed_fields as $field) {
            if (isset($course_data[$field])) {
                $update_fields[] = "$field = ?";
                $values[] = $course_data[$field];
            }
        }
        
        if (empty($update_fields)) {
            return false;
        }
        
        $values[] = $course_id;
        $sql = "UPDATE courses SET " . implode(', ', $update_fields) . " WHERE course_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Delete course
     */
    public function deleteCourse($course_id) {
        $stmt = $this->db->prepare("DELETE FROM courses WHERE course_id = ?");
        return $stmt->execute([$course_id]);
    }
    
    /**
     * Get all courses
     */
    public function getAllCourses() {
        $stmt = $this->db->prepare("
            SELECT c.*, u.first_name, u.last_name, 
                   COUNT(s.student_id) as student_count
            FROM courses c
            LEFT JOIN users u ON c.created_by = u.user_id
            LEFT JOIN students s ON c.course_id = s.course_id
            GROUP BY c.course_id
            ORDER BY c.year_level, c.course_name
        ");
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get courses by year level
     */
    public function getCoursesByYearLevel($year_level) {
        $stmt = $this->db->prepare("
            SELECT c.*, COUNT(s.student_id) as student_count
            FROM courses c
            LEFT JOIN students s ON c.course_id = s.course_id
            WHERE c.year_level = ?
            GROUP BY c.course_id
            ORDER BY c.course_name
        ");
        $stmt->execute([$year_level]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get attendance records filtered by course and year level
     */
    public function getAttendanceRecords($filters = []) {
        $sql = "
            SELECT 
                ar.*,
                s.student_number,
                u.first_name,
                u.last_name,
                c.course_name,
                c.course_code,
                c.year_level,
                CASE 
                    WHEN ar.status = 'late' THEN 'Late'
                    WHEN ar.status = 'present' THEN 'On Time'
                    ELSE 'Absent'
                END as punctuality_status
            FROM attendance_records ar
            JOIN students s ON ar.student_id = s.student_id
            JOIN users u ON s.user_id = u.user_id
            JOIN courses c ON ar.course_id = c.course_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['course_id'])) {
            $sql .= " AND ar.course_id = ?";
            $params[] = $filters['course_id'];
        }
        
        if (!empty($filters['year_level'])) {
            $sql .= " AND c.year_level = ?";
            $params[] = $filters['year_level'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND ar.attendance_date >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND ar.attendance_date <= ?";
            $params[] = $filters['end_date'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND ar.status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY ar.attendance_date DESC, c.course_name, u.last_name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Mark attendance for a student
     */
    public function markAttendance($student_id, $course_id, $attendance_date, $time_in, $time_out = null, $status = 'present', $notes = '') {
        // Check if attendance already exists for this date
        $stmt = $this->db->prepare("
            SELECT record_id FROM attendance_records 
            WHERE student_id = ? AND attendance_date = ?
        ");
        $stmt->execute([$student_id, $attendance_date]);
        
        if ($stmt->fetch()) {
            // Update existing record
            $sql = "UPDATE attendance_records SET time_in = ?, time_out = ?, status = ?, notes = ?, marked_by = ? WHERE student_id = ? AND attendance_date = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$time_in, $time_out, $status, $notes, $this->user_id, $student_id, $attendance_date]);
        } else {
            // Insert new record
            $sql = "INSERT INTO attendance_records (student_id, course_id, attendance_date, time_in, time_out, status, notes, marked_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$student_id, $course_id, $attendance_date, $time_in, $time_out, $status, $notes, $this->user_id]);
        }
    }
    
    /**
     * Get all students
     */
    public function getAllStudents($filters = []) {
        $sql = "
            SELECT 
                s.*,
                u.first_name,
                u.last_name,
                u.email,
                c.course_name,
                c.course_code,
                c.year_level
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            JOIN courses c ON s.course_id = c.course_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['course_id'])) {
            $sql .= " AND s.course_id = ?";
            $params[] = $filters['course_id'];
        }
        
        if (!empty($filters['year_level'])) {
            $sql .= " AND c.year_level = ?";
            $params[] = $filters['year_level'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND s.status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY c.year_level, c.course_name, u.last_name, u.first_name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Add new student
     */
    public function addStudent($user_data, $student_data) {
        try {
            $this->db->beginTransaction();
            
            // Insert user
            $user_sql = "INSERT INTO users (username, password, email, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, 'student')";
            $user_stmt = $this->db->prepare($user_sql);
            $user_stmt->execute([
                $user_data['username'],
                password_hash($user_data['password'], PASSWORD_DEFAULT),
                $user_data['email'],
                $user_data['first_name'],
                $user_data['last_name']
            ]);
            
            $user_id = $this->db->lastInsertId();
            
            // Insert student
            $student_sql = "INSERT INTO students (user_id, student_number, course_id, year_level, enrollment_date) VALUES (?, ?, ?, ?, ?)";
            $student_stmt = $this->db->prepare($student_sql);
            $student_stmt->execute([
                $user_id,
                $student_data['student_number'],
                $student_data['course_id'],
                $student_data['year_level'],
                $student_data['enrollment_date']
            ]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Add new admin user
     */
    public function addAdmin($user_data) {
        try {
            // Basic required fields validation (lightweight; callers should validate too)
            $requiredKeys = ['username', 'password', 'email', 'first_name', 'last_name'];
            foreach ($requiredKeys as $key) {
                if (!isset($user_data[$key]) || $user_data[$key] === '') {
                    return false;
                }
            }

            // Enforce unique username/email at application layer prior to DB unique constraint
            $checkStmt = $this->db->prepare("SELECT 1 FROM users WHERE username = ? OR email = ? LIMIT 1");
            $checkStmt->execute([$user_data['username'], $user_data['email']]);
            if ($checkStmt->fetch()) {
                return false; // Username or email already exists
            }

            // Insert admin user
            $insertStmt = $this->db->prepare(
                "INSERT INTO users (username, password, email, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, 'admin')"
            );
            $insertStmt->execute([
                $user_data['username'],
                password_hash($user_data['password'], PASSWORD_DEFAULT),
                $user_data['email'],
                $user_data['first_name'],
                $user_data['last_name']
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get attendance statistics
     */
    public function getAttendanceStatistics($filters = []) {
        $sql = "
            SELECT 
                c.course_name,
                c.course_code,
                c.year_level,
                COUNT(ar.record_id) as total_records,
                SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                ROUND((SUM(CASE WHEN ar.status IN ('present', 'late') THEN 1 ELSE 0 END) / COUNT(ar.record_id)) * 100, 2) as attendance_percentage
            FROM attendance_records ar
            JOIN courses c ON ar.course_id = c.course_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['course_id'])) {
            $sql .= " AND ar.course_id = ?";
            $params[] = $filters['course_id'];
        }
        
        if (!empty($filters['year_level'])) {
            $sql .= " AND c.year_level = ?";
            $params[] = $filters['year_level'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND ar.attendance_date >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND ar.attendance_date <= ?";
            $params[] = $filters['end_date'];
        }
        
        $sql .= " GROUP BY c.course_id ORDER BY c.year_level, c.course_name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get dashboard data for admin
     */
    public function getDashboardData() {
        $total_students = $this->getTotalStudents();
        $total_courses = $this->getTotalCourses();
        $recent_attendance = $this->getAttendanceRecords([], 10);
        $attendance_stats = $this->getAttendanceStatistics();
        
        return [
            'total_students' => $total_students,
            'total_courses' => $total_courses,
            'recent_attendance' => $recent_attendance,
            'attendance_stats' => $attendance_stats
        ];
    }
    
    /**
     * Get total number of students
     */
    private function getTotalStudents() {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    /**
     * Get total number of courses
     */
    private function getTotalCourses() {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM courses");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    /**
     * Check if admin has permission for specific action
     */
    public function hasPermission($action) {
        $admin_permissions = [
            'view_all_attendance',
            'mark_attendance',
            'add_course',
            'edit_course',
            'delete_course',
            'add_student',
            'edit_student',
            'delete_student',
            'view_statistics',
            'manage_users'
        ];
        
        return in_array($action, $admin_permissions);
    }
}
?>
