<?php
require_once 'config/database.php';

/**
 * Attendance Manager Class
 * Handles attendance-related operations and business logic
 */
class AttendanceManager {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseConfig::getInstance()->getConnection();
    }
    
    /**
     * Mark attendance for multiple students
     */
    public function markBulkAttendance($course_id, $attendance_date, $attendance_data, $marked_by) {
        try {
            $this->db->beginTransaction();
            
            foreach ($attendance_data as $student_id => $data) {
                $this->markSingleAttendance(
                    $student_id,
                    $course_id,
                    $attendance_date,
                    $data['time_in'] ?? null,
                    $data['time_out'] ?? null,
                    $data['status'] ?? 'absent',
                    $data['notes'] ?? '',
                    $marked_by
                );
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    /**
     * Mark attendance for a single student
     */
    public function markSingleAttendance($student_id, $course_id, $attendance_date, $time_in, $time_out, $status, $notes, $marked_by) {
        if (!$status && $time_in) {
            // Auto determine status if not explicitly provided
            $status = $this->determineAttendanceStatus($course_id, $time_in, $attendance_date);
        }
        // Check if attendance already exists
        $stmt = $this->db->prepare("
            SELECT record_id FROM attendance_records 
            WHERE student_id = ? AND attendance_date = ?
        ");
        $stmt->execute([$student_id, $attendance_date]);
        
        if ($stmt->fetch()) {
            // Update existing record
            $sql = "UPDATE attendance_records SET 
                    time_in = ?, time_out = ?, status = ?, notes = ?, marked_by = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE student_id = ? AND attendance_date = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$time_in, $time_out, $status, $notes, $marked_by, $student_id, $attendance_date]);
        } else {
            // Insert new record
            $sql = "INSERT INTO attendance_records 
                    (student_id, course_id, attendance_date, time_in, time_out, status, notes, marked_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$student_id, $course_id, $attendance_date, $time_in, $time_out, $status, $notes, $marked_by]);
        }
    }
    
    /**
     * Determine if student is late based on class schedule
     */
    public function determineAttendanceStatus($course_id, $attendance_time, $attendance_date) {
        $stmt = $this->db->prepare("
            SELECT start_time 
            FROM class_schedules 
            WHERE course_id = ? 
            AND day_of_week = DAYNAME(?)
        ");
        $stmt->execute([$course_id, $attendance_date]);
        $schedule = $stmt->fetch();
        
        if (!$schedule) {
            return 'present'; // No schedule found, assume present
        }
        
        $class_start = strtotime($schedule['start_time']);
        $attendance_time_stamp = strtotime($attendance_time);
        
        // Consider late if more than 5 minutes after class start
        $late_threshold = 300; // 5 minutes in seconds
        
        if (($attendance_time_stamp - $class_start) > $late_threshold) {
            return 'late';
        }
        
        return 'present';
    }
    
    /**
     * Get attendance summary for a course
     */
    public function getCourseAttendanceSummary($course_id, $start_date = null, $end_date = null) {
        $sql = "
            SELECT 
                s.student_id,
                s.student_number,
                u.first_name,
                u.last_name,
                COUNT(ar.record_id) as total_records,
                SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                ROUND((SUM(CASE WHEN ar.status IN ('present', 'late') THEN 1 ELSE 0 END) / COUNT(ar.record_id)) * 100, 2) as attendance_percentage
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN attendance_records ar ON s.student_id = ar.student_id AND ar.course_id = ?
        ";
        
        $params = [$course_id];
        
        if ($start_date) {
            $sql .= " AND ar.attendance_date >= ?";
            $params[] = $start_date;
        }
        
        if ($end_date) {
            $sql .= " AND ar.attendance_date <= ?";
            $params[] = $end_date;
        }
        
        $sql .= " WHERE s.course_id = ? GROUP BY s.student_id ORDER BY u.last_name, u.first_name";
        $params[] = $course_id;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get students who haven't been marked for a specific date
     */
    public function getUnmarkedStudents($course_id, $attendance_date) {
        $stmt = $this->db->prepare("
            SELECT s.student_id, s.student_number, u.first_name, u.last_name
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN attendance_records ar ON s.student_id = ar.student_id AND ar.attendance_date = ?
            WHERE s.course_id = ? AND ar.record_id IS NULL
            ORDER BY u.last_name, u.first_name
        ");
        $stmt->execute([$attendance_date, $course_id]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Generate attendance report
     */
    public function generateAttendanceReport($filters = []) {
        $sql = "
            SELECT 
                c.course_name,
                c.course_code,
                c.year_level,
                s.student_number,
                u.first_name,
                u.last_name,
                ar.attendance_date,
                ar.time_in,
                ar.time_out,
                ar.status,
                CASE 
                    WHEN ar.status = 'late' THEN 'Late'
                    WHEN ar.status = 'present' THEN 'On Time'
                    ELSE 'Absent'
                END as punctuality_status,
                ar.notes
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
        
        $sql .= " ORDER BY c.year_level, c.course_name, u.last_name, u.first_name, ar.attendance_date";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get attendance trends over time
     */
    public function getAttendanceTrends($course_id, $start_date, $end_date) {
        $stmt = $this->db->prepare("
            SELECT 
                ar.attendance_date,
                COUNT(ar.record_id) as total_attendance,
                SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                ROUND((SUM(CASE WHEN ar.status IN ('present', 'late') THEN 1 ELSE 0 END) / COUNT(ar.record_id)) * 100, 2) as attendance_percentage
            FROM attendance_records ar
            WHERE ar.course_id = ? 
            AND ar.attendance_date BETWEEN ? AND ?
            GROUP BY ar.attendance_date
            ORDER BY ar.attendance_date
        ");
        $stmt->execute([$course_id, $start_date, $end_date]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Export attendance data to CSV format
     */
    public function exportToCSV($data, $filename = 'attendance_report.csv') {
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            
            // Add data rows
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        
        // Set headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
    }
}
?>
