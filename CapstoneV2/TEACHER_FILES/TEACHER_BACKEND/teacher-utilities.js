// Teacher Portal - Shared Utilities
// This file provides common functions used across all teacher portal pages

// Get current teacher ID from session storage
function getCurrentTeacherId() {
    return parseInt(sessionStorage.getItem('teacher_id') || '1');
}

// Get current teacher name from session storage
function getCurrentTeacherName() {
    return sessionStorage.getItem('teacher_name') || 'Teacher';
}

// Get current teacher email from session storage
function getCurrentTeacherEmail() {
    return sessionStorage.getItem('teacher_email') || '';
}

// Get current teacher school from session storage
function getCurrentTeacherSchool() {
    return sessionStorage.getItem('teacher_school') || '';
}

// Load and display teacher profile in page header
function loadTeacherProfileHeader() {
    const teacherId = getCurrentTeacherId();
    const formData = new FormData();
    formData.append('teacher_id', teacherId);
    
    fetch('TEACHER_BACKEND/teacher_get_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.teacher) {
            const teacher = data.teacher;
            
            // Store in session storage
            sessionStorage.setItem('teacher_id', teacher.id);
            sessionStorage.setItem('teacher_name', teacher.name);
            sessionStorage.setItem('teacher_email', teacher.email);
            sessionStorage.setItem('teacher_school', teacher.schoolName);
            
            // Update sidebar profile elements if they exist
            const avatarElement = document.getElementById('teacher-avatar-letter');
            if (avatarElement) {
                avatarElement.textContent = teacher.avatarLetter;
            }
            
            const nameElement = document.getElementById('teacher-name-sidebar');
            if (nameElement) {
                nameElement.textContent = teacher.name;
            }
            
            // Update greeting if it exists
            const greetingElement = document.getElementById('teacher-greeting');
            if (greetingElement) {
                const firstName = teacher.firstName || teacher.name.split(' ')[0];
                const greeting = `Good ${getGreeting()}, ${firstName} 👋`;
                greetingElement.textContent = greeting;
            }
            
            // Update greeting sub if it exists
            const greetingSubElement = document.getElementById('teacher-greeting-sub');
            if (greetingSubElement) {
                const now = new Date();
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                const dateStr = now.toLocaleDateString('en-US', options);
                const subtext = teacher.schoolName ? 
                    `${dateStr} · ${teacher.schoolName}` : 
                    dateStr;
                greetingSubElement.textContent = subtext;
            }
        }
    })
    .catch(error => console.error('Error loading teacher profile:', error));
}

// Get appropriate greeting based on time of day
function getGreeting() {
    const hour = new Date().getHours();
    if (hour < 12) return 'morning';
    if (hour < 17) return 'afternoon';
    return 'evening';
}

// Format date to readable string
function formatDate(date) {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(date).toLocaleDateString('en-US', options);
}

// Format time to show how long ago
function getTimeAgo(date) {
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + 'd ago';
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

// Show notification message
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `teacher-notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 15px 20px;
        background-color: ${type === 'error' ? '#ff4757' : type === 'success' ? '#2ed573' : '#4a90e2'};
        color: white;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        z-index: 9999;
        animation: slideIn 0.3s ease-out;
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Show loading spinner
function showLoading(show = true) {
    const loader = document.getElementById('page-loader');
    if (loader) {
        loader.style.display = show ? 'flex' : 'none';
    }
}
