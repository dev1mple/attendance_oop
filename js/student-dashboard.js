// Student Dashboard Manager class following OOP principles
class StudentDashboardManager {
    constructor() {
        this.currentSection = 'dashboard';
        this.studentData = null;
        this.attendanceRecords = [];
        this.excuseLetters = [];
        this.init();
    }

    init() {
        this.checkAuth();
        this.loadStudentData();
        this.loadAttendanceRecords();
        this.loadExcuseLetters();
        this.bindEvents();
        this.updateDashboard();
        this.setCurrentDate();
    }

    // Check if user is authenticated as student
    checkAuth() {
        const userData = JSON.parse(localStorage.getItem('currentUser') || '{}');
        if (userData.role !== 'student') {
            window.location.href = 'index.html';
        }
    }

    // Load student data from localStorage
    loadStudentData() {
        const userData = JSON.parse(localStorage.getItem('currentUser') || '{}');
        if (userData.role === 'student') {
            this.studentData = {
                id: userData.studentNumber || userData.username || 'ST001',
                firstName: userData.firstName || '',
                lastName: userData.lastName || '',
                name: `${userData.firstName || ''} ${userData.lastName || ''}`.trim() || userData.username || 'Student',
                course: userData.course || 'BSCS',
                yearLevel: userData.yearLevel || '2nd Year',
                email: userData.email || '',
                phone: localStorage.getItem('studentPhone') || ''
            };
        }
        this.updateStudentInfo();
    }

    // Load attendance records from localStorage
    loadAttendanceRecords() {
        const storedRecords = localStorage.getItem('attendanceRecords');
        if (storedRecords) {
            this.attendanceRecords = JSON.parse(storedRecords);
        } else {
            // Create default attendance records for the student
            this.attendanceRecords = [
                { id: 1, studentId: this.studentData?.id, date: '2024-01-15', status: 'present', isLate: false, course: this.studentData?.course, yearLevel: this.studentData?.yearLevel, remarks: '' },
                { id: 2, studentId: this.studentData?.id, date: '2024-01-16', status: 'present', isLate: true, course: this.studentData?.course, yearLevel: this.studentData?.yearLevel, remarks: 'Traffic delay' },
                { id: 3, studentId: this.studentData?.id, date: '2024-01-17', status: 'absent', isLate: false, course: this.studentData?.course, yearLevel: this.studentData?.yearLevel, remarks: 'Sick leave' }
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
            // Create default excuse letters for the student
            this.excuseLetters = [
                { 
                    id: 1, 
                    studentId: this.studentData?.id, 
                    subject: 'Medical Leave', 
                    reason: 'Doctor appointment for regular checkup', 
                    excuseDate: '2024-01-20', 
                    status: 'approved', 
                    course: this.studentData?.course, 
                    yearLevel: this.studentData?.yearLevel,
                    submittedAt: '2024-01-19T10:00:00Z',
                    reviewedAt: '2024-01-19T14:30:00Z',
                    adminRemarks: 'Approved - Valid medical reason'
                },
                { 
                    id: 2, 
                    studentId: this.studentData?.id, 
                    subject: 'Family Emergency', 
                    reason: 'Attending to family member in hospital', 
                    excuseDate: '2024-01-25', 
                    status: 'pending', 
                    course: this.studentData?.course, 
                    yearLevel: this.studentData?.yearLevel,
                    submittedAt: '2024-01-24T15:30:00Z',
                    reviewedAt: null,
                    adminRemarks: null
                }
            ];
            localStorage.setItem('excuseLetters', JSON.stringify(this.excuseLetters));
        }
    }

    // Bind event listeners
    bindEvents() {
        // Attendance form submission
        const attendanceForm = document.getElementById('attendanceForm');
        if (attendanceForm) {
            attendanceForm.addEventListener('submit', (e) => this.handleAttendanceSubmission(e));
        }

        // Attendance status change
        const statusInputs = document.querySelectorAll('input[name="attendanceStatus"]');
        statusInputs.forEach(input => {
            input.addEventListener('change', () => this.toggleLateSection());
        });

        // Profile form submission
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', (e) => this.handleProfileUpdate(e));
        }

        // Filter change events
        document.getElementById('historyMonthFilter')?.addEventListener('change', () => this.filterAttendanceHistory());
        document.getElementById('historyStatusFilter')?.addEventListener('change', () => this.filterAttendanceHistory());
        document.getElementById('historyYearFilter')?.addEventListener('change', () => this.filterAttendanceHistory());
    }

    // Update student information display
    updateStudentInfo() {
        if (!this.studentData) return;

        document.getElementById('studentName').textContent = this.studentData.name;
        document.getElementById('studentCourse').textContent = this.studentData.course;
        document.getElementById('studentYear').textContent = this.studentData.yearLevel;
        document.getElementById('studentId').textContent = this.studentData.id;

        // Update profile form fields
        this.updateProfileForm();
    }

    // Update profile form with current data
    updateProfileForm() {
        if (!this.studentData) return;

        // Load additional profile data from localStorage
        const profileData = this.getProfileData();
        
        document.getElementById('profileFirstName').value = this.studentData.firstName || this.studentData.name.split(' ')[0] || '';
        document.getElementById('profileLastName').value = this.studentData.lastName || this.studentData.name.split(' ').slice(1).join(' ') || '';
        document.getElementById('profileStudentNumber').value = this.studentData.id || '';
        document.getElementById('profileEmail').value = profileData.email || this.studentData.email || '';
        document.getElementById('profileCourse').value = this.studentData.course || '';
        document.getElementById('profileYearLevel').value = this.studentData.yearLevel || '';
        document.getElementById('profilePhone').value = profileData.phone || this.studentData.phone || '';
        document.getElementById('profileAddress').value = profileData.address || '';
        document.getElementById('profileBio').value = profileData.bio || '';

        // Initially disable all fields
        this.setProfileFieldsReadOnly(true);
    }

    // Get profile data from localStorage
    getProfileData() {
        const profileData = localStorage.getItem('studentProfile');
        return profileData ? JSON.parse(profileData) : {};
    }

    // Save profile data to localStorage
    saveProfileData(profileData) {
        localStorage.setItem('studentProfile', JSON.stringify(profileData));
    }

    // Set profile fields to read-only or editable
    setProfileFieldsReadOnly(readOnly) {
        const profileFields = [
            'profileFirstName', 'profileLastName', 'profileStudentNumber',
            'profileEmail', 'profileCourse', 'profileYearLevel',
            'profilePhone', 'profileAddress', 'profileBio'
        ];

        profileFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.readOnly = readOnly;
                field.disabled = readOnly;
            }
        });

        // Show/hide action buttons
        const profileActions = document.getElementById('profileActions');
        const editProfileBtn = document.getElementById('editProfileBtn');
        
        if (profileActions) {
            profileActions.style.display = readOnly ? 'none' : 'flex';
        }
        
        if (editProfileBtn) {
            editProfileBtn.style.display = readOnly ? 'inline-block' : 'none';
        }
    }

    // Set current date in attendance form
    setCurrentDate() {
        const today = new Date().toISOString().split('T')[0];
        const dateInput = document.getElementById('attendanceDate');
        if (dateInput) {
            dateInput.value = today;
        }
    }

    // Toggle late section visibility
    toggleLateSection() {
        const lateSection = document.getElementById('lateSection');
        const presentRadio = document.getElementById('statusPresent');
        
        if (lateSection) {
            lateSection.style.display = presentRadio.checked ? 'block' : 'none';
        }
    }

    // Handle attendance form submission
    handleAttendanceSubmission(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const status = formData.get('attendanceStatus');
        const isLate = document.getElementById('isLate').checked;
        const remarks = document.getElementById('remarks').value.trim();
        const date = document.getElementById('attendanceDate').value;

        // Check if attendance already exists for today
        const existingRecord = this.attendanceRecords.find(record => 
            record.studentId === this.studentData.id && record.date === date
        );

        if (existingRecord) {
            this.showAlert('Attendance for today has already been recorded!', 'warning');
            return;
        }

        // Create new attendance record
        const newRecord = {
            id: Date.now(),
            studentId: this.studentData.id,
            date: date,
            status: status,
            isLate: status === 'present' ? isLate : false,
            course: this.studentData.course,
            yearLevel: this.studentData.yearLevel,
            remarks: remarks,
            timestamp: new Date().toISOString()
        };

        // Add to records
        this.attendanceRecords.push(newRecord);
        localStorage.setItem('attendanceRecords', JSON.stringify(this.attendanceRecords));

        // Dispatch custom event for admin dashboard refresh
        window.dispatchEvent(new CustomEvent('attendanceUpdated'));

        // Update dashboard
        this.updateDashboard();
        
        // Reset form
        event.target.reset();
        this.setCurrentDate();
        this.toggleLateSection();
        
        this.showAlert('Attendance recorded successfully!', 'success');
    }

    // Handle profile update
    handleProfileUpdate(event) {
        event.preventDefault();
        
        // Get all profile form values
        const firstName = document.getElementById('profileFirstName').value.trim();
        const lastName = document.getElementById('profileLastName').value.trim();
        const studentNumber = document.getElementById('profileStudentNumber').value.trim();
        const email = document.getElementById('profileEmail').value.trim();
        const course = document.getElementById('profileCourse').value;
        const yearLevel = document.getElementById('profileYearLevel').value;
        const phone = document.getElementById('profilePhone').value.trim();
        const address = document.getElementById('profileAddress').value.trim();
        const bio = document.getElementById('profileBio').value.trim();

        // Validation
        if (!firstName || !lastName || !studentNumber || !email || !course || !yearLevel) {
            this.showAlert('Please fill in all required fields (*)', 'danger');
            return;
        }

        // Update student data
        this.studentData.firstName = firstName;
        this.studentData.lastName = lastName;
        this.studentData.id = studentNumber;
        this.studentData.email = email;
        this.studentData.course = course;
        this.studentData.yearLevel = yearLevel;
        this.studentData.name = `${firstName} ${lastName}`;

        // Save profile data to localStorage
        const profileData = {
            firstName,
            lastName,
            studentNumber,
            email,
            course,
            yearLevel,
            phone,
            address,
            bio,
            updatedAt: new Date().toISOString()
        };
        this.saveProfileData(profileData);

        // Update current user session
        const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
        currentUser.firstName = firstName;
        currentUser.lastName = lastName;
        currentUser.studentNumber = studentNumber;
        currentUser.email = email;
        currentUser.course = course;
        currentUser.yearLevel = yearLevel;
        localStorage.setItem('currentUser', JSON.stringify(currentUser));

        // Update dashboard display
        this.updateStudentInfo();
        this.updateDashboard();

        // Set fields back to read-only
        this.setProfileFieldsReadOnly(true);

        this.showAlert('Profile updated successfully!', 'success');
    }

    // Update dashboard statistics
    updateDashboard() {
        if (!this.studentData) return;

        // Filter records for current student
        const studentRecords = this.attendanceRecords.filter(record => 
            record.studentId === this.studentData.id
        );

        // Calculate statistics
        const totalDays = studentRecords.length;
        const presentDays = studentRecords.filter(record => record.status === 'present').length;
        const lateDays = studentRecords.filter(record => record.status === 'present' && record.isLate).length;
        const absentDays = studentRecords.filter(record => record.status === 'absent').length;
        const attendanceRate = totalDays > 0 ? Math.round((presentDays / totalDays) * 100) : 0;

        // Update display
        document.getElementById('presentDays').textContent = presentDays;
        document.getElementById('lateDays').textContent = lateDays;
        document.getElementById('absentDays').textContent = absentDays;
        document.getElementById('attendanceRate').textContent = `${attendanceRate}%`;

        // Update today's status
        this.updateTodayStatus();
    }

    // Update today's attendance status
    updateTodayStatus() {
        const today = new Date().toISOString().split('T')[0];
        const todayRecord = this.attendanceRecords.find(record => 
            record.studentId === this.studentData.id && record.date === today
        );

        const todayStatus = document.getElementById('todayStatus');
        if (!todayStatus) return;

        if (todayRecord) {
            const statusBadge = this.getStatusBadge(todayRecord.status, todayRecord.isLate);
            const lateIndicator = todayRecord.isLate ? ' (Late)' : '';
            const remarks = todayRecord.remarks ? `<br><small class="text-muted">Remarks: ${todayRecord.remarks}</small>` : '';
            
            todayStatus.innerHTML = `
                <div class="text-center">
                    <h4 class="mb-3">Today's Attendance: ${statusBadge}</h4>
                    <p class="mb-2"><strong>Date:</strong> ${todayRecord.date}${lateIndicator}</p>
                    <p class="mb-2"><strong>Time Recorded:</strong> ${new Date(todayRecord.timestamp).toLocaleTimeString()}</p>
                    ${remarks}
                </div>
            `;
        } else {
            todayStatus.innerHTML = `
                <div class="text-center">
                    <h4 class="text-muted mb-3">No attendance recorded for today</h4>
                    <p class="text-muted">Please mark your attendance using the "Mark Attendance" section.</p>
                </div>
            `;
        }
    }

    // Get status badge HTML
    getStatusBadge(status, isLate) {
        if (status === 'present') {
            if (isLate) {
                return '<span class="badge badge-late">Present (Late)</span>';
            } else {
                return '<span class="badge badge-present">Present</span>';
            }
        } else {
            return '<span class="badge badge-absent">Absent</span>';
        }
    }

    // Filter attendance history
    filterAttendanceHistory() {
        const monthFilter = document.getElementById('historyMonthFilter').value;
        const statusFilter = document.getElementById('historyStatusFilter').value;
        const yearFilter = document.getElementById('historyYearFilter').value;

        let filteredRecords = this.attendanceRecords.filter(record => 
            record.studentId === this.studentData.id
        );

        if (monthFilter) {
            filteredRecords = filteredRecords.filter(record => {
                const recordMonth = new Date(record.date).getMonth() + 1;
                return recordMonth === parseInt(monthFilter);
            });
        }

        if (statusFilter) {
            filteredRecords = filteredRecords.filter(record => record.status === statusFilter);
        }

        if (yearFilter) {
            filteredRecords = filteredRecords.filter(record => {
                const recordYear = new Date(record.date).getFullYear();
                return recordYear === parseInt(yearFilter);
            });
        }

        this.displayAttendanceHistory(filteredRecords);
    }

    // Display attendance history table
    displayAttendanceHistory(records) {
        const historyTable = document.getElementById('attendanceHistoryTable');
        if (!historyTable) return;

        if (records.length === 0) {
            historyTable.innerHTML = '<p class="text-muted">No attendance records found for the selected filters.</p>';
            return;
        }

        // Sort records by date (newest first)
        records.sort((a, b) => new Date(b.date) - new Date(a.date));

        let tableHTML = '<div class="table-responsive"><table class="table table-hover">';
        tableHTML += '<thead><tr><th>Date</th><th>Status</th><th>Late</th><th>Time Recorded</th><th>Remarks</th></tr></thead><tbody>';
        
        records.forEach(record => {
            const statusBadge = this.getStatusBadge(record.status, record.isLate);
            const lateIndicator = record.isLate ? '<i class="fas fa-clock text-warning"></i> Yes' : '<i class="fas fa-check text-success"></i> No';
            const remarks = record.remarks || '-';
            
            tableHTML += `
                <tr>
                    <td>${record.date}</td>
                    <td>${statusBadge}</td>
                    <td>${lateIndicator}</td>
                    <td>${new Date(record.timestamp).toLocaleTimeString()}</td>
                    <td>${remarks}</td>
                </tr>
            `;
        });
        
        tableHTML += '</tbody></table></div>';
        historyTable.innerHTML = tableHTML;
    }

    // Populate year filter dropdown
    populateYearFilter() {
        const yearFilter = document.getElementById('historyYearFilter');
        if (!yearFilter) return;

        const currentYear = new Date().getFullYear();
        yearFilter.innerHTML = '<option value="">All Years</option>';
        
        for (let year = currentYear; year >= currentYear - 5; year--) {
            yearFilter.innerHTML += `<option value="${year}">${year}</option>`;
        }
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

    // Display excuse letters list
    displayExcuseLetters() {
        const excuseLettersList = document.getElementById('excuseLettersList');
        if (!excuseLettersList) return;

        // Filter excuse letters for current student
        const studentExcuseLetters = this.excuseLetters.filter(letter => 
            letter.studentId === this.studentData.id
        );

        if (studentExcuseLetters.length === 0) {
            excuseLettersList.innerHTML = '<p class="text-muted">No excuse letters submitted yet.</p>';
            return;
        }

        // Sort by submission date (newest first)
        studentExcuseLetters.sort((a, b) => new Date(b.submittedAt) - new Date(a.submittedAt));

        let tableHTML = '<div class="table-responsive"><table class="table table-hover">';
        tableHTML += '<thead><tr><th>Subject</th><th>Date of Absence</th><th>Status</th><th>Submitted</th><th>Actions</th></tr></thead><tbody>';
        
        studentExcuseLetters.forEach(letter => {
            const statusBadge = this.getExcuseStatusBadge(letter.status);
            const submittedDate = new Date(letter.submittedAt).toLocaleDateString();
            
            tableHTML += `
                <tr>
                    <td>${letter.subject}</td>
                    <td>${letter.excuseDate}</td>
                    <td>${statusBadge}</td>
                    <td>${submittedDate}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="viewExcuseLetter(${letter.id})">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </td>
                </tr>
            `;
        });
        
        tableHTML += '</tbody></table></div>';
        excuseLettersList.innerHTML = tableHTML;
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

    // Submit new excuse letter
    submitExcuseLetter() {
        const subject = document.getElementById('excuseSubject').value.trim();
        const excuseDate = document.getElementById('excuseDate').value;
        const reason = document.getElementById('excuseReason').value.trim();
        const course = document.getElementById('excuseCourse').value;
        const yearLevel = document.getElementById('excuseYearLevel').value;

        // Validation
        if (!subject || !excuseDate || !reason || !course || !yearLevel) {
            this.showAlert('Please fill in all required fields', 'danger');
            return;
        }

        // Check if excuse letter already exists for the same date
        const existingLetter = this.excuseLetters.find(letter => 
            letter.studentId === this.studentData.id && 
            letter.excuseDate === excuseDate
        );

        if (existingLetter) {
            this.showAlert('An excuse letter for this date already exists!', 'warning');
            return;
        }

        // Create new excuse letter
        const newExcuseLetter = {
            id: Date.now(),
            studentId: this.studentData.id,
            subject: subject,
            reason: reason,
            excuseDate: excuseDate,
            status: 'pending',
            course: course,
            yearLevel: yearLevel,
            submittedAt: new Date().toISOString(),
            reviewedAt: null,
            adminRemarks: null
        };

        // Add to excuse letters
        this.excuseLetters.push(newExcuseLetter);
        localStorage.setItem('excuseLetters', JSON.stringify(this.excuseLetters));

        // Dispatch custom event for admin dashboard refresh
        window.dispatchEvent(new CustomEvent('excuseLetterUpdated'));

        // Update display
        this.displayExcuseLetters();
        
        // Close modal and reset form
        const modal = bootstrap.Modal.getInstance(document.getElementById('submitExcuseModal'));
        modal.hide();
        document.getElementById('excuseLetterForm').reset();
        
        this.showAlert('Excuse letter submitted successfully!', 'success');
    }

    // View excuse letter details
    viewExcuseLetter(excuseId) {
        const letter = this.excuseLetters.find(l => l.id === excuseId);
        if (!letter) return;

        const modal = new bootstrap.Modal(document.getElementById('excuseReviewModal'));
        const content = document.getElementById('excuseReviewContent');
        
        content.innerHTML = `
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
        `;
        
        modal.show();
    }
}

// Navigation functions
function showDashboard() {
    showSection('dashboardSection');
    updateActiveNav('dashboard');
    studentManager.updateDashboard();
}

function showAttendanceMarking() {
    showSection('attendanceMarkingSection');
    updateActiveNav('attendance');
    studentManager.setCurrentDate();
}

function showAttendanceHistory() {
    showSection('attendanceHistorySection');
    updateActiveNav('history');
    studentManager.populateYearFilter();
    studentManager.filterAttendanceHistory();
}

function showExcuseLetters() {
    showSection('excuseLettersSection');
    updateActiveNav('excuseLetters');
    studentManager.displayExcuseLetters();
}

function showProfile() {
    showSection('profileSection');
    updateActiveNav('profile');
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

// Profile editing functions
function toggleProfileEdit() {
    studentManager.setProfileFieldsReadOnly(false);
}

function cancelProfileEdit() {
    // Reload profile data and set fields back to read-only
    studentManager.updateProfileForm();
}

// Global functions for excuse letter management
function submitExcuseLetter() {
    studentManager.submitExcuseLetter();
}

function viewExcuseLetter(excuseId) {
    studentManager.viewExcuseLetter(excuseId);
}

// Logout function
function logout() {
    localStorage.removeItem('currentUser');
    window.location.href = 'index.html';
}

// Initialize student dashboard manager when DOM is loaded
let studentManager;
document.addEventListener('DOMContentLoaded', () => {
    studentManager = new StudentDashboardManager();
});
