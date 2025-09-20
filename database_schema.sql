
CREATE DATABASE IF NOT EXISTS sakura2;
USE sakura2;

CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role ENUM('student', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS courses (
    course_id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    year_level INT NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    student_number VARCHAR(20) UNIQUE NOT NULL,
    course_id INT NOT NULL,
    year_level INT NOT NULL,
    enrollment_date DATE NOT NULL,
    status ENUM('active', 'inactive', 'graduated') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS attendance_records (
    record_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    attendance_date DATE NOT NULL,  
    time_in TIME,
    time_out TIME,
    status ENUM('present', 'absent', 'late') NOT NULL,
    notes TEXT,
    marked_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(user_id) ON DELETE SET NULL,
    UNIQUE KEY unique_student_date (student_id, attendance_date)
);


CREATE TABLE IF NOT EXISTS class_schedules (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
);

INSERT INTO users (username, password, email, first_name, last_name, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@attendance.com', 'System', 'Administrator', 'admin');

INSERT INTO courses (course_code, course_name, year_level, description, created_by) 
VALUES 
('CS401', 'Computer Science 4th Year', 4, 'Fourth year Computer Science program', 1),
('IT401', 'Information Technology 4th Year', 4, 'Fourth year Information Technology program', 1),
('CE401', 'Computer Engineering 4th Year', 4, 'Fourth year Computer Engineering program', 1);


INSERT INTO users (username, password, email, first_name, last_name, role) 
VALUES 
('student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student1@email.com', 'John', 'Doe', 'student'),
('student2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student2@email.com', 'Jane', 'Smith', 'student'),
('student3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student3@email.com', 'Mike', 'Johnson', 'student');

INSERT INTO students (user_id, student_number, course_id, year_level, enrollment_date) 
VALUES 
(2, 'CS2024001', 1, 4, '2024-01-15'),
(3, 'IT2024002', 2, 4, '2024-01-15'),
(4, 'CE2024003', 3, 4, '2024-01-15');


INSERT INTO class_schedules (course_id, day_of_week, start_time, end_time, room) 
VALUES 
(1, 'Monday', '08:00:00', '10:00:00', 'Room 101'),
(1, 'Wednesday', '08:00:00', '10:00:00', 'Room 101'),
(1, 'Friday', '08:00:00', '10:00:00', 'Room 101'),
(2, 'Tuesday', '10:00:00', '12:00:00', 'Room 102'),
(2, 'Thursday', '10:00:00', '12:00:00', 'Room 102'),
(3, 'Monday', '14:00:00', '16:00:00', 'Room 103'),
(3, 'Wednesday', '14:00:00', '16:00:00', 'Room 103');


INSERT INTO attendance_records (student_id, course_id, attendance_date, time_in, time_out, status, marked_by) 
VALUES 
(1, 1, '2024-01-15', '08:05:00', '09:55:00', 'late', 1),
(1, 1, '2024-01-17', '07:55:00', '09:55:00', 'present', 1),
(1, 1, '2024-01-19', '08:00:00', '09:55:00', 'present', 1),
(2, 2, '2024-01-16', '10:05:00', '11:55:00', 'late', 1),
(2, 2, '2024-01-18', '09:55:00', '11:55:00', 'present', 1),
(3, 3, '2024-01-15', '14:00:00', '15:55:00', 'present', 1),
(3, 3, '2024-01-17', '14:10:00', '15:55:00', 'late', 1);


-- Create indexes if not already present (explain kay sir ano to)
SET @exists := (SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND INDEX_NAME = 'idx_students_course');
SET @sql := IF(@exists = 0, 'CREATE INDEX idx_students_course ON students(course_id);', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @exists := (SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND INDEX_NAME = 'idx_students_year');
SET @sql := IF(@exists = 0, 'CREATE INDEX idx_students_year ON students(year_level);', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @exists := (SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance_records' AND INDEX_NAME = 'idx_attendance_date');
SET @sql := IF(@exists = 0, 'CREATE INDEX idx_attendance_date ON attendance_records(attendance_date);', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @exists := (SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance_records' AND INDEX_NAME = 'idx_attendance_student');
SET @sql := IF(@exists = 0, 'CREATE INDEX idx_attendance_student ON attendance_records(student_id);', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @exists := (SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance_records' AND INDEX_NAME = 'idx_attendance_course');
SET @sql := IF(@exists = 0, 'CREATE INDEX idx_attendance_course ON attendance_records(course_id);', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @exists := (SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses' AND INDEX_NAME = 'idx_courses_year');
SET @sql := IF(@exists = 0, 'CREATE INDEX idx_courses_year ON courses(year_level);', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Excuse Letters Module
CREATE TABLE IF NOT EXISTS excuse_letters (
    excuse_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT NULL,
    absence_date DATE NOT NULL,
    reason TEXT NOT NULL,
    attachment_path VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_reviewed_by INT NULL,
    admin_remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE SET NULL,
    FOREIGN KEY (admin_reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_excuse_student (student_id),
    INDEX idx_excuse_status (status),
    INDEX idx_excuse_absence_date (absence_date)
);
