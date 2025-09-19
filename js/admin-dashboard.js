// Admin Dashboard Manager class following OOP principles
class AdminDashboardManager {
    constructor() {
        this.currentSection = 'dashboard';
        this.courses = [];
        this.students = [];
        this.attendanceRecords = [];
        this.excuseLetters = [];
        this.init();
    }

    init() {
        this.checkAuth();
        this.loadInitialData();
        this.bindEvents();
        this.updateDashboardStats();
    }

    // Check if user is authenticated as admin
    checkAuth() {
        const userData = JSON.parse(localStorage.getItem('currentUser') || '{}');
        if (userData.role !== 'admin') {
            window.location.href = 'index.html';
        }
    }

    // Load initial data
    loadInitialData() {
        this.loadCourses();
        this.loadStudents();
        this.loadAttendanceRecords();
        this.loadExcuseLetters();
        this.populateFilters();
    }

    // Bind event listeners
    bindEvents() {
        // Filter change events
        document.getElementById('courseFilter')?.addEventListener('change', () => this.filterAttendance());
        document.getElementById('yearFilter')?.addEventListener('change', () => this.filterAttendance());
        document.getElementById('dateFilter')?.addEventListener('change', () => this.filterAttendance());
        
        // Modal events
        const addCourseModal = document.getElementById('addCourseModal');
        if (addCourseModal) {
            addCourseModal.addEventListener('hidden.bs.modal', () => {
                this.resetCourseForm();
            });
        }

        // Listen for storage changes to refresh data
        window.addEventListener('storage', (e) => {
            if (e.key === 'attendanceRecords') {
                this.loadAttendanceRecords();
                this.updateDashboardStats();
                this.filterAttendance();
            }
        });

        // Also listen for custom events (for same-tab updates)
        window.addEventListener('attendanceUpdated', () => {
            this.loadAttendanceRecords();
            this.updateDashboardStats();
            this.filterAttendance();
        });

        // Listen for excuse letter updates
        window.addEventListener('excuseLetterUpdated', () => {
            this.loadExcuseLetters();
            this.filterExcuseLetters();
        });

        // Excuse letter filter events
        document.getElementById('excuseCourseFilter')?.addEventListener('change', () => this.filterExcuseLetters());
        document.getElementById('excuseStatusFilter')?.addEventListener('change', () => this.filterExcuseLetters());
        document.getElementById('excuseYearFilter')?.addEventListener('change', () => this.filterExcuseLetters());
        document.getElementById('excuseDateFilter')?.addEventListener('change', () => this.filterExcuseLetters());
    }

    // Load courses from localStorage or create default ones
    loadCourses() {
        const storedCourses = localStorage.getItem('courses');
        if (storedCourses) {
            this.courses = JSON.parse(storedCourses);
        } else {
            // Create default courses with acronyms
            this.courses = [
                { id: 1, name: 'BSCS', description: 'Bachelor of Science in Computer Science' },
                { id: 2, name: 'BSIT', description: 'Bachelor of Science in Information Technology' },
                { id: 3, name: 'BSCE', description: 'Bachelor of Science in Computer Engineering' }
            ];
            localStorage.setItem('courses', JSON.stringify(this.courses));
        }
        this.updateCoursesList();
    }

    // Load students from localStorage or create default ones
    loadStudents() {
        // First try to load students from registered users
        const registeredUsers = JSON.parse(localStorage.getItem('registeredUsers') || '[]');
        const studentUsers = registeredUsers.filter(user => user.role === 'student');
        
        if (studentUsers.length > 0) {
            // Convert registered users to student format
            this.students = studentUsers.map(user => ({
                id: user.studentNumber || user.username,
                name: `${user.firstName || ''} ${user.lastName || ''}`.trim() || user.username,
                course: user.course || 'Unknown',
                yearLevel: user.yearLevel || 'Unknown',
                email: user.email || '',
                studentNumber: user.studentNumber || user.username
            }));
        } else {
            // Fallback to default students if no registered users
            this.students = [
                { id: 'ST001', name: 'John Doe', course: 'BSCS', yearLevel: '2nd Year', email: 'john@example.com', studentNumber: 'ST001' },
                { id: 'ST002', name: 'Jane Smith', course: 'BSIT', yearLevel: '3rd Year', email: 'jane@example.com', studentNumber: 'ST002' },
                { id: 'ST003', name: 'Mike Johnson', course: 'BSCE', yearLevel: '1st Year', email: 'mike@example.com', studentNumber: 'ST003' }
            ];
        }
        
        localStorage.setItem('students', JSON.stringify(this.students));
        this.updateStudentsList();
    }

    // Load attendance records from localStorage or create default ones
    loadAttendanceRecords() {
        const storedRecords = localStorage.getItem('attendanceRecords');
        if (storedRecords) {
            this.attendanceRecords = JSON.parse(storedRecords);
        } else {
            // Create default attendance records with correct student IDs
            this.attendanceRecords = [
                { id: 1, studentId: 'ST001', date: '2024-01-15', status: 'present', isLate: false, course: 'BSCS', yearLevel: '2nd Year' },
                { id: 2, studentId: 'ST002', date: '2024-01-15', status: 'present', isLate: true, course: 'BSIT', yearLevel: '3rd Year' },
                { id: 3, studentId: 'ST003', date: '2024-01-15', status: 'absent', isLate: false, course: 'BSCE', yearLevel: '1st Year' }
            ];
            localStorage.setItem('attendanceRecords', JSON.stringify(this.attendanceRecords));
        }
    }

    // Load excuse letters from localStorage
    loadExcuseLetters() {
        const storedExcuseLetters = localStorage.getItem('excuseLetters');
        if (storedExcuseLetters) {
            this.excuseLetters = JSON.parse(storedExcuseLetters);
        } else {
            // Create default excuse letters
            this.excuseLetters = [
                { 
                    id: 1, 
                    studentId: 'ST001', 
                    subject: 'Medical Leave', 
                    reason: 'Doctor appointment for regular checkup', 
                    excuseDate: '2024-01-20', 
                    status: 'approved', 
                    course: 'BSCS', 
                    yearLevel: '2nd Year',
                    submittedAt: '2024-01-19T10:00:00Z',
                    reviewedAt: '2024-01-19T14:30:00Z',
                    adminRemarks: 'Approved - Valid medical reason'
                },
                { 
                    id: 2, 
                    studentId: 'ST002', 
                    subject: 'Family Emergency', 
                    reason: 'Attending to family member in hospital', 
                    excuseDate: '2024-01-25', 
                    status: 'pending', 
                    course: 'BSIT', 
                    yearLevel: '3rd Year',
                    submittedAt: '2024-01-24T15:30:00Z',
                    reviewedAt: null,
                    adminRemarks: null
                },
                { 
                    id: 3, 
                    studentId: 'ST003', 
                    subject: 'Personal Matter', 
                    reason: 'Attending important family event', 
                    excuseDate: '2024-01-30', 
                    status: 'rejected', 
                    course: 'BSCE', 
                    yearLevel: '1st Year',
                    submittedAt: '2024-01-29T09:00:00Z',
                    reviewedAt: '2024-01-29T16:00:00Z',
                    adminRemarks: 'Rejected - Not a valid reason for absence'
                }
            ];
            localStorage.setItem('excuseLetters', JSON.stringify(this.excuseLetters));
        }
    }

    // Populate filter dropdowns
    populateFilters() {
        const courseFilter = document.getElementById('courseFilter');
        const studentCourseSelect = document.getElementById('studentCourse');
        const excuseCourseFilter = document.getElementById('excuseCourseFilter');
        
        if (courseFilter) {
            courseFilter.innerHTML = '<option value="">All Courses</option>';
            
            // Get all unique courses from both admin courses and attendance records
            const allCourses = new Set();
            
            // Add admin courses
            this.courses.forEach(course => {
                allCourses.add(course.name);
            });
            
            // Add courses from attendance records
            this.attendanceRecords.forEach(record => {
                if (record.course) {
                    allCourses.add(record.course);
                }
            });
            
            // Add courses from student profiles
            this.students.forEach(student => {
                if (student.course) {
                    allCourses.add(student.course);
                }
            });
            
            // Add courses from excuse letters
            this.excuseLetters.forEach(letter => {
                if (letter.course) {
                    allCourses.add(letter.course);
                }
            });
            
            // Convert to array and sort
            const sortedCourses = Array.from(allCourses).sort();
            
            // Populate dropdown
            sortedCourses.forEach(courseName => {
                courseFilter.innerHTML += `<option value="${courseName}">${courseName}</option>`;
            });
        }

        if (studentCourseSelect) {
            studentCourseSelect.innerHTML = '<option value="">Select Course</option>';
            this.courses.forEach(course => {
                studentCourseSelect.innerHTML += `<option value="${course.name}">${course.name}</option>`;
            });
        }

        if (excuseCourseFilter) {
            excuseCourseFilter.innerHTML = '<option value="">All Courses</option>';
            
            // Get unique courses from excuse letters
            const excuseCourses = new Set();
            this.excuseLetters.forEach(letter => {
                if (letter.course) {
                    excuseCourses.add(letter.course);
                }
            });
            
            // Convert to array and sort
            const sortedExcuseCourses = Array.from(excuseCourses).sort();
            
            // Populate dropdown
            sortedExcuseCourses.forEach(courseName => {
                excuseCourseFilter.innerHTML += `<option value="${courseName}">${courseName}</option>`;
            });
        }
    }

    // Update dashboard statistics
    updateDashboardStats() {
        document.getElementById('totalStudents').textContent = this.students.length;
        document.getElementById('totalCourses').textContent = this.courses.length;
        
        const today = new Date().toISOString().split('T')[0];
        const todayRecords = this.attendanceRecords.filter(record => record.date === today);
        
        const presentToday = todayRecords.filter(record => record.status === 'present').length;
        const lateToday = todayRecords.filter(record => record.status === 'present' && record.isLate).length;
        
        document.getElementById('presentToday').textContent = presentToday;
        document.getElementById('lateToday').textContent = lateToday;
        
        this.updateRecentActivity();
    }

    // Update recent activity section
    updateRecentActivity() {
        const recentActivity = document.getElementById('recentActivity');
        if (!recentActivity) return;

        const recentRecords = this.attendanceRecords
            .sort((a, b) => new Date(b.date) - new Date(a.date))
            .slice(0, 5);

        if (recentRecords.length === 0) {
            recentActivity.innerHTML = '<p class="text-muted">No recent activity to display.</p>';
            return;
        }

        let activityHTML = '';
        recentRecords.forEach(record => {
            const student = this.students.find(s => s.id === record.studentId);
            if (student) {
                const statusBadge = this.getStatusBadge(record.status, record.isLate);
                activityHTML += `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong>${student.name}</strong> (${record.course} - ${record.yearLevel})
                            <br><small class="text-muted">${record.date}</small>
                        </div>
                        ${statusBadge}
                    </div>
                `;
            }
        });
        recentActivity.innerHTML = activityHTML;
    }

    // Get status badge HTML
    getStatusBadge(status, isLate) {
        if (status === 'present') {
            if (isLate) {
                return '<span class="badge badge-late">Late</span>';
            } else {
                return '<span class="badge badge-present">Present</span>';
            }
        } else {
            return '<span class="badge badge-absent">Absent</span>';
        }
    }

    // Update courses list display
    updateCoursesList() {
        const coursesList = document.getElementById('coursesList');
        if (!coursesList) return;

        if (this.courses.length === 0) {
            coursesList.innerHTML = '<p class="text-muted">No courses available.</p>';
            return;
        }

        let coursesHTML = '<div class="table-responsive"><table class="table table-hover">';
        coursesHTML += '<thead><tr><th>Course Name</th><th>Description</th><th>Actions</th></tr></thead><tbody>';
        
        this.courses.forEach(course => {
            coursesHTML += `
                <tr>
                    <td>${course.name}</td>
                    <td>${course.description}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-2" onclick="editCourse(${course.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCourse(${course.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        coursesHTML += '</tbody></table></div>';
        coursesList.innerHTML = coursesHTML;
    }

    // Update students list display
    updateStudentsList() {
        const studentsList = document.getElementById('studentsList');
        if (!studentsList) return;

        if (this.students.length === 0) {
            studentsList.innerHTML = '<p class="text-muted">No students available.</p>';
            return;
        }

        let studentsHTML = '<div class="table-responsive"><table class="table table-hover">';
        studentsHTML += '<thead><tr><th>Name</th><th>Course</th><th>Year Level</th><th>Actions</th></tr></thead><tbody>';
        
        this.students.forEach(student => {
            studentsHTML += `
                <tr>
                    <td>${student.name}</td>
                    <td>${student.course}</td>
                    <td>${student.yearLevel}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-2" onclick="editStudent(${student.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteStudent(${student.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        studentsHTML += '</tbody></table></div>';
        studentsList.innerHTML = studentsHTML;
    }

    // Filter attendance records
    filterAttendance() {
        const courseFilter = document.getElementById('courseFilter').value;
        const yearFilter = document.getElementById('yearFilter').value;
        const dateFilter = document.getElementById('dateFilter').value;

        console.log('Filtering with:', { courseFilter, yearFilter, dateFilter });
        console.log('Total records before filtering:', this.attendanceRecords.length);

        let filteredRecords = this.attendanceRecords;

        if (courseFilter) {
            const beforeCourseFilter = filteredRecords.length;
            filteredRecords = filteredRecords.filter(record => {
                const matches = record.course === courseFilter;
                console.log(`Record course: "${record.course}" vs filter: "${courseFilter}" - matches: ${matches}`);
                return matches;
            });
            console.log(`After course filter: ${beforeCourseFilter} -> ${filteredRecords.length} records`);
        }
        
        if (yearFilter) {
            const beforeYearFilter = filteredRecords.length;
            filteredRecords = filteredRecords.filter(record => record.yearLevel === yearFilter);
            console.log(`After year filter: ${beforeYearFilter} -> ${filteredRecords.length} records`);
        }
        
        if (dateFilter) {
            const beforeDateFilter = filteredRecords.length;
            filteredRecords = filteredRecords.filter(record => record.date === dateFilter);
            console.log(`After date filter: ${beforeDateFilter} -> ${filteredRecords.length} records`);
        }

        console.log('Final filtered records:', filteredRecords);
        this.displayAttendanceTable(filteredRecords);
    }

    // Display attendance table
    displayAttendanceTable(records) {
        const attendanceTable = document.getElementById('attendanceTable');
        if (!attendanceTable) return;

        if (records.length === 0) {
            attendanceTable.innerHTML = '<p class="text-muted">No attendance records found for the selected filters.</p>';
            return;
        }

        let tableHTML = '<div class="table-responsive"><table class="table table-hover">';
        tableHTML += '<thead><tr><th>Student</th><th>Course</th><th>Year Level</th><th>Date</th><th>Status</th><th>Late</th></tr></thead><tbody>';
        
        records.forEach(record => {
            const student = this.students.find(s => s.id === record.studentId);
            if (student) {
                const statusBadge = this.getStatusBadge(record.status, record.isLate);
                const lateIndicator = record.isLate ? '<i class="fas fa-clock text-warning"></i> Yes' : '<i class="fas fa-check text-success"></i> No';
                
                tableHTML += `
                    <tr>
                        <td>${student.name}</td>
                        <td>${record.course}</td>
                        <td>${record.yearLevel}</td>
                        <td>${record.date}</td>
                        <td>${statusBadge}</td>
                        <td>${lateIndicator}</td>
                    </tr>
                `;
            }
        });
        
        tableHTML += '</tbody></table></div>';
        attendanceTable.innerHTML = tableHTML;
    }

    // Add or edit course
    saveCourse() {
        const courseId = document.getElementById('courseId').value;
        const courseName = document.getElementById('courseName').value.trim();
        const courseDescription = document.getElementById('courseDescription').value.trim();

        if (!courseName || !courseDescription) {
            this.showAlert('Please fill in all fields', 'danger');
            return;
        }

        if (courseId) {
            // Editing existing course
            const courseIndex = this.courses.findIndex(c => c.id == courseId);
            if (courseIndex !== -1) {
                this.courses[courseIndex] = {
                    ...this.courses[courseIndex],
                    name: courseName,
                    description: courseDescription,
                    updatedAt: new Date().toISOString()
                };
                this.showAlert('Course updated successfully!', 'success');
            }
        } else {
            // Adding new course
            const newCourse = {
                id: Date.now(),
                name: courseName,
                description: courseDescription,
                createdAt: new Date().toISOString()
            };
            this.courses.push(newCourse);
            this.showAlert('Course added successfully!', 'success');
        }

        localStorage.setItem('courses', JSON.stringify(this.courses));
        
        this.updateCoursesList();
        this.populateFilters();
        this.updateDashboardStats();
        
        // Close modal and reset form
        const modal = bootstrap.Modal.getInstance(document.getElementById('addCourseModal'));
        modal.hide();
        this.resetCourseForm();
    }

    // Edit course - populate modal with course data
    editCourse(courseId) {
        const course = this.courses.find(c => c.id == courseId);
        if (!course) return;

        document.getElementById('courseId').value = course.id;
        document.getElementById('courseName').value = course.name;
        document.getElementById('courseDescription').value = course.description;
        document.getElementById('courseModalTitle').textContent = 'Edit Course';
        document.getElementById('courseSubmitBtn').textContent = 'Update Course';

        const modal = new bootstrap.Modal(document.getElementById('addCourseModal'));
        modal.show();
    }

    // Delete course
    deleteCourse(courseId) {
        if (confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
            this.courses = this.courses.filter(c => c.id != courseId);
            localStorage.setItem('courses', JSON.stringify(this.courses));
            
            this.updateCoursesList();
            this.populateFilters();
            this.updateDashboardStats();
            
            this.showAlert('Course deleted successfully!', 'success');
        }
    }

    // Reset course form
    resetCourseForm() {
        document.getElementById('courseId').value = '';
        document.getElementById('courseName').value = '';
        document.getElementById('courseDescription').value = '';
        document.getElementById('courseModalTitle').textContent = 'Add New Course';
        document.getElementById('courseSubmitBtn').textContent = 'Add Course';
    }

    // Refresh all data
    refreshData() {
        this.loadStudents();
        this.loadAttendanceRecords();
        this.populateFilters();
        this.updateDashboardStats();
        this.filterAttendance();
    }

    // Clear attendance records for testing (optional)
    clearAttendanceRecords() {
        if (confirm('Are you sure you want to clear all attendance records? This action cannot be undone.')) {
            this.attendanceRecords = [];
            localStorage.removeItem('attendanceRecords');
            this.updateDashboardStats();
            this.filterAttendance();
            this.showAlert('Attendance records cleared successfully!', 'success');
        }
    }

    // Display all data for debugging
    displayAllData() {
        console.log('=== ADMIN DASHBOARD DATA DEBUG ===');
        console.log('Courses:', this.courses);
        console.log('Students:', this.students);
        console.log('Attendance Records:', this.attendanceRecords);
        
        // Show data in an alert for easy viewing
        let debugInfo = '=== DATA DEBUG ===\n\n';
        debugInfo += `Courses (${this.courses.length}):\n`;
        this.courses.forEach(course => {
            debugInfo += `- ${course.name}\n`;
        });
        
        debugInfo += `\nStudents (${this.students.length}):\n`;
        this.students.forEach(student => {
            debugInfo += `- ${student.name} (${student.course} - ${student.yearLevel})\n`;
        });
        
        debugInfo += `\nAttendance Records (${this.attendanceRecords.length}):\n`;
        this.attendanceRecords.forEach(record => {
            const student = this.students.find(s => s.id === record.studentId);
            const studentName = student ? student.name : 'Unknown';
            debugInfo += `- ${studentName}: ${record.status} on ${record.date} (${record.course} - ${record.yearLevel})\n`;
        });
        
        alert(debugInfo);
    }

    // Test filtering with specific course
    testCourseFilter(courseName) {
        console.log(`Testing filter for course: "${courseName}"`);
        
        const matchingRecords = this.attendanceRecords.filter(record => record.course === courseName);
        console.log(`Found ${matchingRecords.length} records for course "${courseName}":`, matchingRecords);
        
        // Show results in alert
        let result = `Course Filter Test: "${courseName}"\n\n`;
        result += `Total records: ${this.attendanceRecords.length}\n`;
        result += `Matching records: ${matchingRecords.length}\n\n`;
        
        if (matchingRecords.length > 0) {
            result += 'Matching records:\n';
            matchingRecords.forEach((record, index) => {
                const student = this.students.find(s => s.id === record.studentId);
                const studentName = student ? student.name : 'Unknown';
                result += `${index + 1}. ${studentName}: ${record.status} on ${record.date}\n`;
            });
        } else {
            result += 'No matching records found.\n';
            result += '\nAvailable courses in records:\n';
            const uniqueCourses = [...new Set(this.attendanceRecords.map(r => r.course))];
            uniqueCourses.forEach(course => {
                result += `- "${course}"\n`;
            });
        }
        
        alert(result);
    }

    // Add new student
    addStudent() {
        const studentName = document.getElementById('studentName').value.trim();
        const studentCourse = document.getElementById('studentCourse').value;
        const studentYear = document.getElementById('studentYear').value;

        if (!studentName || !studentCourse || !studentYear) {
            this.showAlert('Please fill in all fields', 'danger');
            return;
        }

        const newStudent = {
            id: Date.now(),
            name: studentName,
            course: studentCourse,
            yearLevel: studentYear
        };

        this.students.push(newStudent);
        localStorage.setItem('students', JSON.stringify(this.students));
        
        this.updateStudentsList();
        this.updateDashboardStats();
        
        // Close modal and reset form
        const modal = bootstrap.Modal.getInstance(document.getElementById('addStudentModal'));
        modal.hide();
        document.getElementById('addStudentForm').reset();
        
        this.showAlert('Student added successfully!', 'success');
    }

    // Show alert message
    showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const mainContent = document.querySelector('.main-content');
        mainContent.insertBefore(alertDiv, mainContent.firstChild);
        
        // Auto-remove alert after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // Filter excuse letters
    filterExcuseLetters() {
        const courseFilter = document.getElementById('excuseCourseFilter')?.value;
        const statusFilter = document.getElementById('excuseStatusFilter')?.value;
        const yearFilter = document.getElementById('excuseYearFilter')?.value;
        const dateFilter = document.getElementById('excuseDateFilter')?.value;

        let filteredLetters = this.excuseLetters;

        if (courseFilter) {
            filteredLetters = filteredLetters.filter(letter => letter.course === courseFilter);
        }
        
        if (statusFilter) {
            filteredLetters = filteredLetters.filter(letter => letter.status === statusFilter);
        }
        
        if (yearFilter) {
            filteredLetters = filteredLetters.filter(letter => letter.yearLevel === yearFilter);
        }
        
        if (dateFilter) {
            filteredLetters = filteredLetters.filter(letter => letter.excuseDate === dateFilter);
        }

        this.displayExcuseLettersTable(filteredLetters);
    }

    // Display excuse letters table
    displayExcuseLettersTable(letters) {
        const excuseLettersTable = document.getElementById('excuseLettersTable');
        if (!excuseLettersTable) return;

        if (letters.length === 0) {
            excuseLettersTable.innerHTML = '<p class="text-muted">No excuse letters found for the selected filters.</p>';
            return;
        }

        // Sort by submission date (newest first)
        letters.sort((a, b) => new Date(b.submittedAt) - new Date(a.submittedAt));

        let tableHTML = '<div class="table-responsive"><table class="table table-hover">';
        tableHTML += '<thead><tr><th>Student</th><th>Subject</th><th>Course</th><th>Year Level</th><th>Date of Absence</th><th>Status</th><th>Submitted</th><th>Actions</th></tr></thead><tbody>';
        
        letters.forEach(letter => {
            const student = this.students.find(s => s.id === letter.studentId);
            const studentName = student ? student.name : 'Unknown Student';
            const statusBadge = this.getExcuseStatusBadge(letter.status);
            const submittedDate = new Date(letter.submittedAt).toLocaleDateString();
            
            tableHTML += `
                <tr>
                    <td>${studentName}</td>
                    <td>${letter.subject}</td>
                    <td>${letter.course}</td>
                    <td>${letter.yearLevel}</td>
                    <td>${letter.excuseDate}</td>
                    <td>${statusBadge}</td>
                    <td>${submittedDate}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-2" onclick="reviewExcuseLetterModal(${letter.id})">
                            <i class="fas fa-eye"></i> Review
                        </button>
                        ${letter.status === 'pending' ? `
                            <button class="btn btn-sm btn-success me-1" onclick="reviewExcuseLetter(${letter.id}, 'approved')">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="reviewExcuseLetter(${letter.id}, 'rejected')">
                                <i class="fas fa-times"></i>
                            </button>
                        ` : ''}
                    </td>
                </tr>
            `;
        });
        
        tableHTML += '</tbody></table></div>';
        excuseLettersTable.innerHTML = tableHTML;
    }

    // Get excuse status badge HTML
    getExcuseStatusBadge(status) {
        switch (status) {
            case 'approved':
                return '<span class="badge bg-success">Approved</span>';
            case 'rejected':
                return '<span class="badge bg-danger">Rejected</span>';
            case 'pending':
                return '<span class="badge bg-warning">Pending</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    // Review excuse letter modal
    reviewExcuseLetterModal(excuseId) {
        const letter = this.excuseLetters.find(l => l.id === excuseId);
        if (!letter) return;

        const student = this.students.find(s => s.id === letter.studentId);
        const studentName = student ? student.name : 'Unknown Student';

        const modal = new bootstrap.Modal(document.getElementById('excuseReviewModal'));
        const content = document.getElementById('excuseReviewContent');
        
        content.innerHTML = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Student:</strong> ${studentName}
                </div>
                <div class="col-md-6">
                    <strong>Student ID:</strong> ${letter.studentId}
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Subject:</strong> ${letter.subject}
                </div>
                <div class="col-md-6">
                    <strong>Date of Absence:</strong> ${letter.excuseDate}
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Course:</strong> ${letter.course}
                </div>
                <div class="col-md-6">
                    <strong>Year Level:</strong> ${letter.yearLevel}
                </div>
            </div>
            <div class="mb-3">
                <strong>Reason:</strong>
                <p class="mt-2 p-3 bg-light rounded">${letter.reason}</p>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Status:</strong> ${this.getExcuseStatusBadge(letter.status)}
                </div>
                <div class="col-md-6">
                    <strong>Submitted:</strong> ${new Date(letter.submittedAt).toLocaleString()}
                </div>
            </div>
            ${letter.reviewedAt ? `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Reviewed:</strong> ${new Date(letter.reviewedAt).toLocaleString()}
                    </div>
                </div>
            ` : ''}
            ${letter.adminRemarks ? `
                <div class="mb-3">
                    <strong>Admin Remarks:</strong>
                    <p class="mt-2 p-3 bg-light rounded">${letter.adminRemarks}</p>
                </div>
            ` : ''}
            <div class="mb-3">
                <label for="adminRemarksInput" class="form-label">Admin Remarks (Optional)</label>
                <textarea class="form-control" id="adminRemarksInput" rows="3" placeholder="Enter your remarks about this excuse letter...">${letter.adminRemarks || ''}</textarea>
            </div>
        `;
        
        // Store current excuse ID for review action
        window.currentExcuseId = excuseId;
        modal.show();
    }

    // Review excuse letter (approve/reject)
    reviewExcuseLetter(excuseId, status) {
        const letter = this.excuseLetters.find(l => l.id === excuseId);
        if (!letter) return;

        const adminRemarks = document.getElementById('adminRemarksInput')?.value.trim() || '';

        // Update excuse letter
        letter.status = status;
        letter.reviewedAt = new Date().toISOString();
        letter.adminRemarks = adminRemarks;

        // Save to localStorage
        localStorage.setItem('excuseLetters', JSON.stringify(this.excuseLetters));

        // Update display
        this.filterExcuseLetters();

        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('excuseReviewModal'));
        modal.hide();

        // Show success message
        const statusText = status === 'approved' ? 'approved' : 'rejected';
        this.showAlert(`Excuse letter ${statusText} successfully!`, 'success');

        // Dispatch custom event for student dashboard refresh
        window.dispatchEvent(new CustomEvent('excuseLetterUpdated'));
    }

    // Refresh excuse letters
    refreshExcuseLetters() {
        this.loadExcuseLetters();
        this.populateFilters();
        this.filterExcuseLetters();
    }
}

// Navigation functions
function showDashboard() {
    showSection('dashboardSection');
    updateActiveNav('dashboard');
    adminManager.updateDashboardStats();
}

function showCourseManagement() {
    showSection('courseSection');
    updateActiveNav('course');
}

function showAttendanceMonitoring() {
    showSection('attendanceSection');
    updateActiveNav('attendance');
    adminManager.refreshData();
}

function showStudentManagement() {
    showSection('studentSection');
    updateActiveNav('student');
}

function showExcuseLetterManagement() {
    showSection('excuseLetterSection');
    updateActiveNav('excuseLetter');
    adminManager.filterExcuseLetters();
}

// Helper function to show sections
function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('[id$="Section"]').forEach(section => {
        section.style.display = 'none';
    });
    
    // Show selected section
    document.getElementById(sectionId).style.display = 'block';
}

// Helper function to update active navigation
function updateActiveNav(section) {
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    
    const activeLink = document.querySelector(`[onclick*="${section}"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    }
}

// Course and Student management functions
function saveCourse() {
    adminManager.saveCourse();
}

function addStudent() {
    adminManager.addStudent();
}

function editCourse(courseId) {
    adminManager.editCourse(courseId);
}

function deleteCourse(courseId) {
    adminManager.deleteCourse(courseId);
}

function editStudent(studentId) {
    // Implementation for editing student
    console.log('Edit student:', studentId);
}

function deleteStudent(studentId) {
    if (confirm('Are you sure you want to delete this student?')) {
        adminManager.students = adminManager.students.filter(student => student.id !== studentId);
        localStorage.setItem('students', JSON.stringify(adminManager.students));
        adminManager.updateStudentsList();
        adminManager.updateDashboardStats();
        adminManager.showAlert('Student deleted successfully!', 'success');
    }
}

// Global functions for excuse letter management
function reviewExcuseLetterModal(excuseId) {
    adminManager.reviewExcuseLetterModal(excuseId);
}

function reviewExcuseLetter(excuseId, status) {
    adminManager.reviewExcuseLetter(excuseId, status);
}

// Logout function
function logout() {
    localStorage.removeItem('currentUser');
    window.location.href = 'index.html';
}

// Initialize admin dashboard manager when DOM is loaded
let adminManager;
document.addEventListener('DOMContentLoaded', () => {
    adminManager = new AdminDashboardManager();
});
