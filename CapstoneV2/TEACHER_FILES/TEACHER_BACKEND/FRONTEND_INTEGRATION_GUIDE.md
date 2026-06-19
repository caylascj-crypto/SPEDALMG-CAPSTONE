# Frontend Integration Guide - Teacher Portal Backend APIs

## Quick Start: Integrating Backend APIs with Frontend

### 1. Dashboard Integration (Teacher_dashboard.html)

```javascript
// Load comprehensive statistics on page load
function loadDashboardStats() {
    fetch('TEACHER_BACKEND/teacher_get_comprehensive_stats.php', {
        method: 'POST',
        body: new FormData(Object.assign(new FormData(), {
            teacher_id: getCurrentTeacherId()
        }))
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const stats = data.stats;
            
            // Update dashboard elements
            document.getElementById('total-learners').textContent = stats.total_learners;
            document.getElementById('total-activities').textContent = stats.total_activities;
            document.getElementById('avg-score').textContent = stats.average_score;
            document.getElementById('total-assessments').textContent = stats.total_assessments;
            
            // Render recent activities table
            renderRecentActivities(stats.recent_activities);
            
            // Render learner summary
            renderLearnerSummary(stats.learner_summary);
            
            // Render performance breakdown chart
            renderPerformanceChart(stats.performance_breakdown);
        }
    });
}

function renderRecentActivities(activities) {
    const tbody = document.getElementById('recent-activities-tbody');
    tbody.innerHTML = '';
    
    activities.forEach(activity => {
        const row = `
            <tr>
                <td>${activity.student_name}</td>
                <td>${activity.activity_title}</td>
                <td>${activity.score}</td>
                <td>${new Date(activity.assessment_date).toLocaleDateString()}</td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

// Call on page load
document.addEventListener('DOMContentLoaded', loadDashboardStats);
```

---

### 2. Learners/Students Management (Teacher_learners.html)

```javascript
// Load all learners
function loadLearners() {
    const formData = new FormData();
    formData.append('action', 'list');
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_manage_learners.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderLearnersTable(data.learners);
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function renderLearnersTable(learners) {
    const tbody = document.getElementById('learners-tbody');
    tbody.innerHTML = '';
    
    learners.forEach(learner => {
        const row = `
            <tr>
                <td>${learner.student_name}</td>
                <td>${learner.grade_level}</td>
                <td>${learner.disability_type}</td>
                <td>${learner.parent_name}</td>
                <td>${learner.parent_phone}</td>
                <td>
                    <button onclick="editLearner(${learner.id})">Edit</button>
                    <button onclick="deleteLearner(${learner.id})">Delete</button>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

// Add new learner
function addLearner() {
    const formData = new FormData(document.getElementById('add-learner-form'));
    formData.append('action', 'add');
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_manage_learners.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Learner added successfully!');
            loadLearners();
            document.getElementById('add-learner-form').reset();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// Update learner
function editLearner(studentId) {
    const newName = prompt('Enter new student name:');
    if (!newName) return;
    
    const formData = new FormData();
    formData.append('action', 'edit');
    formData.append('student_id', studentId);
    formData.append('student_name', newName);
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_manage_learners.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            loadLearners();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// Delete learner
function deleteLearner(studentId) {
    if (!confirm('Are you sure you want to delete this learner?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('student_id', studentId);
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_manage_learners.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            loadLearners();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
```

---

### 3. Activity Generation (Teacher_generate.html)

```javascript
// Load activity templates
function loadTemplates() {
    const formData = new FormData();
    formData.append('action', 'list_templates');
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_activity_templates.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderTemplatesList(data.templates);
        }
    });
}

function renderTemplatesList(templates) {
    const container = document.getElementById('templates-list');
    container.innerHTML = '';
    
    templates.forEach(template => {
        const card = `
            <div class="template-card">
                <h3>${template.template_name}</h3>
                <p><strong>Category:</strong> ${template.template_category}</p>
                <p><strong>Difficulty:</strong> ${template.difficulty}</p>
                <p>${template.template_description}</p>
                <button onclick="generateFromTemplate(${template.id})">Use Template</button>
            </div>
        `;
        container.innerHTML += card;
    });
}

// Generate activity from template
function generateFromTemplate(templateId) {
    const activityTitle = prompt('Enter activity title:');
    if (!activityTitle) return;
    
    const formData = new FormData();
    formData.append('action', 'generate_from_template');
    formData.append('template_id', templateId);
    formData.append('activity_title', activityTitle);
    formData.append('activity_description', prompt('Enter activity description:'));
    formData.append('subject', document.getElementById('subject-select').value);
    formData.append('grade_level', document.getElementById('grade-level-select').value);
    formData.append('difficulty', document.getElementById('difficulty-select').value);
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_activity_templates.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Activity generated! ID: ' + data.activity_id);
            loadTemplates();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
```

---

### 4. IEP Management (Teacher_IEP.html)

```javascript
// Load IEPs
function loadIEPs() {
    const formData = new FormData();
    formData.append('action', 'list');
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_manage_iep.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderIEPsList(data.ieps);
        }
    });
}

// Create new IEP
function createIEP() {
    const formData = new FormData(document.getElementById('create-iep-form'));
    formData.append('action', 'create');
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_manage_iep.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('IEP created successfully!');
            loadIEPs();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// Get IEPs for specific student
function getStudentIEPs(studentId) {
    const formData = new FormData();
    formData.append('action', 'list_by_student');
    formData.append('student_id', studentId);
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_manage_iep.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderStudentIEPs(data.ieps);
        }
    });
}
```

---

### 5. Activity Management (Teacher_activies.html)

```javascript
// Load activities with details
function loadActivitiesWithDetails() {
    const formData = new FormData();
    formData.append('action', 'list');
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_list_activities.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderActivitiesTable(data.activities);
        }
    });
}

// Get activity details
function viewActivityDetails(activityId) {
    const formData = new FormData();
    formData.append('action', 'get_activity_detail');
    formData.append('activity_id', activityId);
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_activity_advanced.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            displayActivityModal(data.activity);
        }
    });
}

// Assign activity to student
function assignActivityToStudent(activityId) {
    const studentId = prompt('Enter Student ID:');
    if (!studentId) return;
    
    const formData = new FormData();
    formData.append('action', 'assign_activity');
    formData.append('activity_id', activityId);
    formData.append('student_id', studentId);
    formData.append('due_date', prompt('Enter due date (YYYY-MM-DD):'));
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_activity_advanced.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Activity assigned!');
            loadActivitiesWithDetails();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
```

---

### 6. Reports (Teacher_report.html)

```javascript
// Generate summary report
function generateSummaryReport() {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    
    const formData = new FormData();
    formData.append('action', 'generate_report');
    formData.append('start_date', startDate);
    formData.append('end_date', endDate);
    formData.append('report_type', 'summary');
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_reports_management.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            displayReport(data);
        }
    });
}

// Generate student report
function generateStudentReport(studentId) {
    const formData = new FormData();
    formData.append('action', 'get_student_report');
    formData.append('student_id', studentId);
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_reports_management.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            displayStudentReportModal(data);
        }
    });
}
```

---

### 7. Notifications (Teacher_notif.html)

```javascript
// Load notifications
function loadNotifications() {
    const formData = new FormData();
    formData.append('action', 'list');
    formData.append('teacher_id', getCurrentTeacherId());
    formData.append('limit', 20);
    
    fetch('TEACHER_BACKEND/teacher_manage_notifications.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderNotifications(data.notifications);
            updateNotificationBadge(data.total);
        }
    });
}

// Get unread count
function getUnreadCount() {
    const formData = new FormData();
    formData.append('action', 'get_unread_count');
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_manage_notifications.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById('notif-badge');
            if (badge) badge.textContent = data.unread_count;
        }
    });
}

// Mark notification as read
function markNotificationRead(notificationId) {
    const formData = new FormData();
    formData.append('action', 'mark_read');
    formData.append('notification_id', notificationId);
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_manage_notifications.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    });
}
```

---

### 8. Settings (Teacher_settings.html)

```javascript
// Load profile
function loadTeacherProfile() {
    const formData = new FormData();
    formData.append('action', 'get_profile');
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_settings_management.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const profile = data.profile;
            document.getElementById('name').value = profile.teacher_name;
            document.getElementById('email').value = profile.email;
            document.getElementById('phone').value = profile.phone || '';
            document.getElementById('school').value = profile.school_name || '';
        }
    });
}

// Save profile
function saveTeacherProfile() {
    const formData = new FormData(document.getElementById('profile-form'));
    formData.append('action', 'update_profile');
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_settings_management.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Profile updated successfully!');
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// Change password
function changePassword() {
    const formData = new FormData(document.getElementById('password-form'));
    formData.append('action', 'change_password');
    formData.append('teacher_id', getCurrentTeacherId());
    
    fetch('TEACHER_BACKEND/teacher_settings_management.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Password changed successfully!');
            document.getElementById('password-form').reset();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
```

---

## Helper Function: Get Teacher ID

```javascript
function getCurrentTeacherId() {
    // TODO: Replace with actual session-based teacher ID
    // For now, defaults to 1 (testing)
    return parseInt(sessionStorage.getItem('teacher_id') || '1');
}
```

---

## Session Storage Setup

Add this to login/authentication page:
```javascript
// After successful login, store teacher ID
sessionStorage.setItem('teacher_id', response.teacher_id);

// On logout
sessionStorage.removeItem('teacher_id');
```

---

## Common Patterns

### Pagination
```javascript
function loadNotificationsPage(page = 1) {
    const limit = 10;
    const offset = (page - 1) * limit;
    
    const formData = new FormData();
    formData.append('action', 'list');
    formData.append('limit', limit);
    formData.append('offset', offset);
    
    // ... fetch and render
}
```

### Search/Filter
```javascript
function filterActivities(searchTerm) {
    const formData = new FormData();
    formData.append('action', 'list_templates');
    formData.append('category', document.getElementById('category-filter').value);
    formData.append('difficulty', document.getElementById('difficulty-filter').value);
    
    // ... fetch and filter results
}
```

### Error Handling
```javascript
function handleAPIResponse(response) {
    if (!response.success) {
        console.error('API Error:', response.message);
        showNotification(response.message, 'error');
        return null;
    }
    return response;
}
```

---

## Status: Integration Guide Complete ✅
All 8 portal pages have integration examples with complete code snippets ready to copy.
