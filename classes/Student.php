<?php
require_once 'User.php';

/**
 * Student Class
 * Extends User class for student-specific functionality
 */
class Student extends User {
    private $student_id;
    private $student_number;
    private $course_id;
    private $year_level;
    private $enrollment_date;
    private $status;
    
    public function __construct($user_data = null) {
        parent::__construct($user_data);
        
        if ($user_data && isset($user_data['student_id'])) {
            $this->loadStudentData($user_data);
        } else if ($this->user_id) {
            $this->loadStudentInfo();
        }
    }
    
    /**
     * Load student-specific data
     */
    private function loadStudentData($data) {
        $this->student_id = $data['student_id'] ?? null;
        $this->student_number = $data['student_number'] ?? null;
        $this->course_id = $data['course_id'] ?? null;
        $this->year_level = $data['year_level'] ?? null;
        $this->enrollment_date = $data['enrollment_date'] ?? null;
        $this->status = $data['status'] ?? null;
    }
    
    /**
     * Load student information from database
     */
    private function loadStudentInfo() {
        $stmt = $this->db->prepare("
            SELECT s.*, c.course_name, c.course_code 
            FROM students s 
            JOIN courses c ON s.course_id = c.course_id 
            WHERE s.user_id = ?
        ");
        $stmt->execute([$this->user_id]);
        $student_data = $stmt->fetch();
        
        if ($student_data) {
            $this->loadStudentData($student_data);
        }
    }
    
    /**
     * Get student's attendance history
     */
    public function getAttendanceHistory($start_date = null, $end_date = null) {
        $sql = "
            SELECT 
                ar.*,
                c.course_name,
                c.course_code,
                CASE 
                    WHEN ar.status = 'late' THEN 'Late'
                    WHEN ar.status = 'present' THEN 'On Time'
                    ELSE 'Absent'
                END as punctuality_status
            FROM attendance_records ar
            JOIN courses c ON ar.course_id = c.course_id
            WHERE ar.student_id = ?
        ";
        
        $params = [$this->student_id];
        
        if ($start_date) {
            $sql .= " AND ar.attendance_date >= ?";
            $params[] = $start_date;
        }
        
        if ($end_date) {
            $sql .= " AND ar.attendance_date <= ?";
            $params[] = $end_date;
        }
        
        $sql .= " ORDER BY ar.attendance_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get attendance statistics
     */
    public function getAttendanceStats() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_records,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                ROUND((SUM(CASE WHEN status IN ('present', 'late') THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_percentage
            FROM attendance_records 
            WHERE student_id = ?
        ");
        $stmt->execute([$this->student_id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get current course information
     */
    public function getCourseInfo() {
        if (!$this->course_id) {
            return null;
        }
        
        $stmt = $this->db->prepare("
            SELECT c.*, cs.day_of_week, cs.start_time, cs.end_time, cs.room
            FROM courses c
            LEFT JOIN class_schedules cs ON c.course_id = cs.course_id
            WHERE c.course_id = ?
        ");
        $stmt->execute([$this->course_id]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Check if student is late for a specific class
     */
    public function isLateForClass($course_id, $attendance_time) {
        $stmt = $this->db->prepare("
            SELECT start_time 
            FROM class_schedules 
            WHERE course_id = ? 
            AND day_of_week = DAYNAME(?)
        ");
        $stmt->execute([$course_id, date('Y-m-d', strtotime($attendance_time))]);
        $schedule = $stmt->fetch();
        
        if ($schedule) {
            $class_start = strtotime($schedule['start_time']);
            $attendance_time_stamp = strtotime(date('H:i:s', strtotime($attendance_time)));
            
            // Consider late if more than 5 minutes after class start
            return ($attendance_time_stamp - $class_start) > 300; // 300 seconds = 5 minutes
        }
        
        return false;
    }
    
    /**
     * Get dashboard data for student
     */
    public function getDashboardData() {
        $stats = $this->getAttendanceStats();
        $recent_attendance = $this->getAttendanceHistory(null, null, 10);
        $course_info = $this->getCourseInfo();
        
        return [
            'stats' => $stats,
            'recent_attendance' => $recent_attendance,
            'course_info' => $course_info,
            'student_info' => [
                'student_number' => $this->student_number,
                'course_name' => $course_info[0]['course_name'] ?? 'N/A',
                'year_level' => $this->year_level,
                'status' => $this->status
            ]
        ];
    }
    
    /**
     * Check if student has permission for specific action
     */
    public function hasPermission($action) {
        $student_permissions = [
            'view_own_attendance',
            'view_own_profile',
            'update_own_profile'
        ];
        
        return in_array($action, $student_permissions);
    }
    
    // Getters
    public function getStudentId() { return $this->student_id; }
    public function getStudentNumber() { return $this->student_number; }
    public function getCourseId() { return $this->course_id; }
    public function getYearLevel() { return $this->year_level; }
    public function getEnrollmentDate() { return $this->enrollment_date; }
    public function getStatus() { return $this->status; }
}
?>
