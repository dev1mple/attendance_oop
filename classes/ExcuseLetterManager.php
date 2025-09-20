<?php
require_once 'config/database.php';

class ExcuseLetterManager {
    private $db;

    public function __construct() {
        $this->db = DatabaseConfig::getInstance()->getConnection();
    }

    // Student: submit new excuse letter
    public function submitExcuseLetter($student_id, $course_id, $absence_date, $reason, $attachment_path = null) {
        $sql = "INSERT INTO excuse_letters (student_id, course_id, absence_date, reason, attachment_path) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$student_id, $course_id ?: null, $absence_date, $reason, $attachment_path]);
    }

    // Student: list own submissions with statuses
    public function getStudentExcuseLetters($student_id) {
        $stmt = $this->db->prepare("\n            SELECT el.*, c.course_name, c.course_code\n            FROM excuse_letters el\n            LEFT JOIN courses c ON el.course_id = c.course_id\n            WHERE el.student_id = ?\n            ORDER BY el.created_at DESC\n        ");
        $stmt->execute([$student_id]);
        return $stmt->fetchAll();
    }

    // Admin: list/filter by course/program(year level or course), status, date range
    public function getExcuseLetters($filters = []) {
        $sql = "\n            SELECT el.*, s.student_number, u.first_name, u.last_name, c.course_name, c.course_code, c.year_level\n            FROM excuse_letters el\n            JOIN students s ON el.student_id = s.student_id\n            JOIN users u ON s.user_id = u.user_id\n            LEFT JOIN courses c ON el.course_id = c.course_id\n            WHERE 1=1\n        ";
        $params = [];

        if (!empty($filters['course_id'])) {
            $sql .= " AND el.course_id = ?";
            $params[] = $filters['course_id'];
        }

        if (!empty($filters['year_level'])) {
            $sql .= " AND c.year_level = ?";
            $params[] = $filters['year_level'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND el.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND el.absence_date >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND el.absence_date <= ?";
            $params[] = $filters['end_date'];
        }

        $sql .= " ORDER BY el.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Admin: approve or reject
    public function reviewExcuseLetter($excuse_id, $admin_user_id, $decision, $remarks = null) {
        if (!in_array($decision, ['approved', 'rejected'])) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE excuse_letters SET status = ?, admin_reviewed_by = ?, admin_remarks = ? WHERE excuse_id = ?");
        return $stmt->execute([$decision, $admin_user_id, $remarks, $excuse_id]);
    }

    public function getById($excuse_id) {
        $stmt = $this->db->prepare("\n            SELECT el.*, s.student_number, u.first_name, u.last_name, c.course_name, c.course_code, c.year_level\n            FROM excuse_letters el\n            JOIN students s ON el.student_id = s.student_id\n            JOIN users u ON s.user_id = u.user_id\n            LEFT JOIN courses c ON el.course_id = c.course_id\n            WHERE el.excuse_id = ?\n        ");
        $stmt->execute([$excuse_id]);
        return $stmt->fetch();
    }
}
?>


