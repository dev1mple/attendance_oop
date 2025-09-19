-- =====================================================
-- SIMPLIFIED ATTENDANCE SYSTEM DATABASE SCHEMA
-- =====================================================

CREATE DATABASE IF NOT EXISTS attendance_system;
USE attendance_system;

-- =====================================================
-- USERS TABLE (Base table for authentication)
-- =====================================================
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- COURSES TABLE (Academic programs)
-- =====================================================
CREATE TABLE courses (
    course_id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- YEAR_LEVELS TABLE (Academic year levels)
-- =====================================================
CREATE TABLE year_levels (
    year_level_id INT PRIMARY KEY AUTO_INCREMENT,
    year_name VARCHAR(20) NOT NULL UNIQUE,
    year_number INT NOT NULL UNIQUE
);

-- =====================================================
-- STUDENTS TABLE (Student information)
-- =====================================================
CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    student_number VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    course_id INT NOT NULL,
    year_level_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    contact_number VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    FOREIGN KEY (year_level_id) REFERENCES year_levels(year_level_id)
);

-- =====================================================
-- ATTENDANCE_RECORDS TABLE (Main attendance data)
-- =====================================================
CREATE TABLE attendance_records (
    attendance_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late') NOT NULL,
    is_late BOOLEAN DEFAULT FALSE,
    late_minutes INT DEFAULT 0,
    remarks TEXT,
    recorded_by INT NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(user_id),
    UNIQUE KEY unique_attendance (student_id, attendance_date)
);

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default year levels
INSERT INTO year_levels (year_name, year_number) VALUES
('1st Year', 1),
('2nd Year', 2),
('3rd Year', 3),
('4th Year', 4);

-- Insert default courses
INSERT INTO courses (course_code, course_name, description) VALUES
('BSCS', 'Bachelor of Science in Computer Science', 'Computer Science program'),
('BSIT', 'Bachelor of Science in Information Technology', 'Information Technology program'),
('BSCpE', 'Bachelor of Science in Computer Engineering', 'Computer Engineering program');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password_hash, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@attendance.com', 'admin');

-- Insert default student user (password: student123)
INSERT INTO users (username, password_hash, email, role) VALUES
('student', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student@attendance.com', 'student');

-- Insert default student record
INSERT INTO students (user_id, student_number, first_name, last_name, course_id, year_level_id, enrollment_date) VALUES
(2, 'ST001', 'John', 'Doe', 1, 2, '2023-06-01');

-- =====================================================
-- EXCUSE_LETTERS TABLE (Excuse letter submissions)
-- =====================================================
CREATE TABLE excuse_letters (
    excuse_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    year_level_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    reason TEXT NOT NULL,
    excuse_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_remarks TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by INT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    FOREIGN KEY (year_level_id) REFERENCES year_levels(year_level_id),
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id)
);

-- =====================================================
-- CREATE INDEXES FOR PERFORMANCE
-- =====================================================

CREATE INDEX idx_username ON users(username);
CREATE INDEX idx_role ON users(role);
CREATE INDEX idx_student_course_year ON students(course_id, year_level_id);
CREATE INDEX idx_attendance_date ON attendance_records(attendance_date);
CREATE INDEX idx_attendance_student_date ON attendance_records(student_id, attendance_date);
CREATE INDEX idx_excuse_student ON excuse_letters(student_id);
CREATE INDEX idx_excuse_course ON excuse_letters(course_id);
CREATE INDEX idx_excuse_status ON excuse_letters(status);
CREATE INDEX idx_excuse_date ON excuse_letters(excuse_date);

-- =====================================================
-- END OF SCHEMA
-- =====================================================
