// Base User class following OOP principles
class User {
    constructor(username, password, role) {
        this.username = username;
        this.password = password;
        this.role = role;
        this.isAuthenticated = false;
    }

    // Reusable authentication method
    authenticate() {
        // Simple authentication logic (in real app, this would connect to backend)
        if (this.username && this.password) {
            this.isAuthenticated = true;
            return true;
        }
        return false;
    }

    logout() {
        this.isAuthenticated = false;
        localStorage.removeItem('currentUser');
        window.location.href = 'index.html';
    }

    // Get user info from localStorage
    static getCurrentUser() {
        const userData = localStorage.getItem('currentUser');
        return userData ? JSON.parse(userData) : null;
    }
}

// Student class inheriting from User
class Student extends User {
    constructor(username, password, course, yearLevel, firstName, lastName, email, studentNumber) {
        super(username, password, 'student');
        this.firstName = firstName;
        this.lastName = lastName;
        this.email = email;
        this.course = course;
        this.yearLevel = yearLevel;
        this.studentNumber = studentNumber;
        this.attendanceHistory = [];
    }

    // Override authenticate method for student-specific logic
    authenticate() {
        if (super.authenticate()) {
            // Add student-specific authentication logic here
            return true;
        }
        return false;
    }

    // Method to get attendance history
    getAttendanceHistory() {
        return this.attendanceHistory;
    }

    // Method to mark attendance
    markAttendance(date, status, isLate = false) {
        const attendance = {
            date: date,
            status: status,
            isLate: isLate,
            timestamp: new Date().toISOString()
        };
        this.attendanceHistory.push(attendance);
        return attendance;
    }
}

// Admin class inheriting from User
class Admin extends User {
    constructor(username, password, firstName, lastName, email) {
        super(username, password, 'admin');
        this.firstName = firstName;
        this.lastName = lastName;
        this.email = email;
        this.courses = [];
        this.students = [];
    }

    // Override authenticate method for admin-specific logic
    authenticate() {
        if (super.authenticate()) {
            // Add admin-specific authentication logic here
            return true;
        }
        return false;
    }

    // Method to add new course
    addCourse(courseName, description) {
        const course = {
            id: Date.now(),
            name: courseName,
            description: description,
            createdAt: new Date().toISOString()
        };
        this.courses.push(course);
        return course;
    }

    // Method to get all courses
    getCourses() {
        return this.courses;
    }

    // Method to get students by course and year level
    getStudentsByCourseAndYear(course, yearLevel) {
        return this.students.filter(student => 
            student.course === course && student.yearLevel === yearLevel
        );
    }

    // Method to get attendance statistics
    getAttendanceStats(course, yearLevel) {
        const students = this.getStudentsByCourseAndYear(course, yearLevel);
        const stats = {
            totalStudents: students.length,
            present: 0,
            absent: 0,
            late: 0
        };

        students.forEach(student => {
            student.attendanceHistory.forEach(attendance => {
                if (attendance.status === 'present') {
                    stats.present++;
                    if (attendance.isLate) {
                        stats.late++;
                    }
                } else if (attendance.status === 'absent') {
                    stats.absent++;
                }
            });
        });

        return stats;
    }
}

// Authentication Manager class for handling login/logout and registration
class AuthManager {
    constructor() {
        this.currentUser = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.checkAuthStatus();
    }

    bindEvents() {
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }
        
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleRegistration(e));
        }
    }

    // Reusable function for handling login
    handleLogin(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const username = formData.get('username');
        const password = formData.get('password');
        const role = formData.get('role');

        if (!username || !password || !role) {
            this.showAlert('Please fill in all fields', 'danger');
            return;
        }

        // Check if user exists in registered users
        const registeredUsers = this.getRegisteredUsers();
        const user = registeredUsers.find(u => 
            u.username === username && 
            u.password === password && 
            u.role === role
        );

        if (user) {
            // Create user instance based on role
            let userInstance;
            if (role === 'student') {
                userInstance = new Student(
                    user.username, 
                    user.password, 
                    user.course, 
                    user.yearLevel,
                    user.firstName,
                    user.lastName,
                    user.email,
                    user.studentNumber
                );
            } else if (role === 'admin') {
                userInstance = new Admin(
                    user.username, 
                    user.password,
                    user.firstName,
                    user.lastName,
                    user.email
                );
            }

            // Authenticate user
            if (userInstance && userInstance.authenticate()) {
                this.currentUser = userInstance;
                this.saveUserSession(userInstance);
                this.redirectToDashboard(role);
            } else {
                this.showAlert('Authentication failed', 'danger');
            }
        } else {
            this.showAlert('Invalid credentials. Please register first or check your username/password.', 'danger');
        }
    }

    // Handle user registration
    handleRegistration(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const role = formData.get('role');
        const firstName = formData.get('firstName');
        const lastName = formData.get('lastName');
        const email = formData.get('email');
        const username = formData.get('username');
        const password = formData.get('password');
        const confirmPassword = formData.get('confirmPassword');

        // Validation
        if (!role || !firstName || !lastName || !email || !username || !password || !confirmPassword) {
            this.showAlert('Please fill in all fields', 'danger');
            return;
        }

        if (password !== confirmPassword) {
            this.showAlert('Passwords do not match', 'danger');
            return;
        }

        if (password.length < 6) {
            this.showAlert('Password must be at least 6 characters long', 'danger');
            return;
        }

        // Check if username already exists
        const registeredUsers = this.getRegisteredUsers();
        if (registeredUsers.find(u => u.username === username)) {
            this.showAlert('Username already exists', 'danger');
            return;
        }

        // Additional validation for student registration
        if (role === 'student') {
            const course = formData.get('course');
            const yearLevel = formData.get('yearLevel');
            const studentNumber = formData.get('studentNumber');
            
            if (!course || !yearLevel || !studentNumber) {
                this.showAlert('Please fill in all student fields', 'danger');
                return;
            }
        }

        // Create user object
        const newUser = {
            username,
            password,
            role,
            firstName,
            lastName,
            email,
            createdAt: new Date().toISOString()
        };

        // Add role-specific fields
        if (role === 'student') {
            newUser.course = formData.get('course');
            newUser.yearLevel = formData.get('yearLevel');
            newUser.studentNumber = formData.get('studentNumber');
        }

        // Save user to localStorage
        this.saveRegisteredUser(newUser);
        
        this.showAlert('Registration successful! You can now login.', 'success');
        
        // Clear form and switch to login tab
        event.target.reset();
        document.getElementById('studentFields').style.display = 'none';
        
        // Switch to login tab
        const loginTab = document.getElementById('login-tab');
        const loginTabInstance = new bootstrap.Tab(loginTab);
        loginTabInstance.show();
    }

    // Get registered users from localStorage
    getRegisteredUsers() {
        const users = localStorage.getItem('registeredUsers');
        return users ? JSON.parse(users) : [];
    }

    // Save registered user to localStorage
    saveRegisteredUser(user) {
        const users = this.getRegisteredUsers();
        users.push(user);
        localStorage.setItem('registeredUsers', JSON.stringify(users));
    }

    // Reusable function for showing alerts
    showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.container');
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-remove alert after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // Save user session to localStorage
    saveUserSession(user) {
        const userData = {
            username: user.username,
            role: user.role,
            course: user.course,
            yearLevel: user.yearLevel,
            firstName: user.firstName,
            lastName: user.lastName,
            email: user.email,
            studentNumber: user.studentNumber
        };
        localStorage.setItem('currentUser', JSON.stringify(userData));
    }

    // Check if user is already authenticated
    checkAuthStatus() {
        const userData = User.getCurrentUser();
        if (userData) {
            this.redirectToDashboard(userData.role);
        }
    }

    // Redirect to appropriate dashboard
    redirectToDashboard(role) {
        if (role === 'admin') {
            window.location.href = 'admin-dashboard.html';
        } else if (role === 'student') {
            window.location.href = 'student-dashboard.html';
        }
    }
}

// Toggle registration fields based on role selection
function toggleRegistrationFields() {
    const role = document.getElementById('registerRole').value;
    const studentFields = document.getElementById('studentFields');
    
    if (role === 'student') {
        studentFields.style.display = 'block';
        // Make student fields required
        document.getElementById('course').required = true;
        document.getElementById('yearLevel').required = true;
        document.getElementById('studentNumber').required = true;
    } else {
        studentFields.style.display = 'none';
        // Remove required attribute
        document.getElementById('course').required = false;
        document.getElementById('yearLevel').required = false;
        document.getElementById('studentNumber').required = false;
    }
}

// Initialize authentication manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AuthManager();
});

// Export classes for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { User, Student, Admin, AuthManager };
}
