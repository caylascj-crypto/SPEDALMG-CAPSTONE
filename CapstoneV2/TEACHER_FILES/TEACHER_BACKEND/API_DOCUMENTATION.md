# Teacher Portal Backend APIs - Complete Documentation

## Overview
This document provides complete reference for all backend APIs created for the teacher portal system.

---

## 1. Learner Management API
**File:** `teacher_manage_learners.php`

### Actions:
- **add** - Create new student
  - Parameters: `student_name`, `parent_name`, `parent_email`, `parent_phone`, `disability_type`, `disability_category`, `grade_level`, `age`
  - Returns: `student_id`

- **edit** - Update student information
  - Parameters: `student_id`, `student_name`, `parent_name`, `parent_email`, `parent_phone`, `disability_type`, `disability_category`, `grade_level`, `age`

- **delete** - Remove student
  - Parameters: `student_id`

- **list** - Get all students for teacher
  - Returns: Array of all students with full details

- **get** - Get single student details
  - Parameters: `student_id`

---

## 2. IEP Management API
**File:** `teacher_manage_iep.php`

### Actions:
- **create** - Create IEP for student
  - Parameters: `student_id`, `iep_goal`, `learning_objective`, `strategies`, `materials`, `assessment_method`
  - Returns: `iep_id`

- **update** - Modify existing IEP
  - Parameters: `iep_id`, `iep_goal`, `learning_objective`, `strategies`, `materials`, `assessment_method`, `status`

- **delete** - Remove IEP
  - Parameters: `iep_id`

- **list** - Get all IEPs for teacher
  - Returns: Array with student names

- **get** - Get single IEP with student info
  - Parameters: `iep_id`

- **list_by_student** - Get all IEPs for specific student
  - Parameters: `student_id`

---

## 3. Assessment Management API
**File:** `teacher_manage_assessments.php`

### Actions:
- **record_assessment** - Record new assessment
  - Parameters: `student_id`, `activity_id`, `assessment_date`, `score`, `feedback`, `strengths`, `areas_for_improvement`, `status`
  - Returns: `assessment_id`
  - Updates: `learner_progress` table automatically

- **update_assessment** - Modify existing assessment
  - Parameters: `assessment_id`, `score`, `feedback`, `strengths`, `areas_for_improvement`, `status`

- **get_assessments** - Get recent assessments
  - Parameters: `limit` (default: 50)
  - Returns: Assessments with student and activity names

- **get_student_assessments** - Get all assessments for student
  - Parameters: `student_id`
  - Returns: Assessments + average_score, total_score

- **get_progress** - Get overall progress stats for student
  - Parameters: `student_id`
  - Returns: total_activities, average_score, highest_score, lowest_score, last_assessment

---

## 4. Notification Management API
**File:** `teacher_manage_notifications.php`

### Actions:
- **create** - Send notification
  - Parameters: `notification_type`, `title`, `message`
  - Returns: `notification_id`

- **list** - Get notifications
  - Parameters: `limit` (default: 20), `offset`
  - Returns: Array of notifications with total count

- **mark_read** - Mark notification as read
  - Parameters: `notification_id`

- **delete** - Delete notification
  - Parameters: `notification_id`

- **get_unread_count** - Get count of unread notifications
  - Returns: `unread_count`

---

## 5. Comprehensive Statistics API
**File:** `teacher_get_comprehensive_stats.php`

### Single Endpoint (GET/POST)
Returns aggregated statistics:
- `total_learners` - Count of active students
- `total_activities` - Count of published activities
- `total_assessments` - Count of all assessments
- `active_students` - Count of active students
- `average_score` - Overall average assessment score
- `recent_activities` - Last 5 assessments with details
- `learner_summary` - Per-student stats (name, count, avg_score)
- `performance_breakdown` - Grouped by score ranges (excellent, good, satisfactory, needs_improvement)

---

## 6. Activity Templates API
**File:** `teacher_activity_templates.php`

### Actions:
- **generate_from_template** - Create activity from template
  - Parameters: `template_id`, `activity_title`, `activity_description`, `subject`, `grade_level`, `difficulty`
  - Returns: `activity_id`
  - Logs: Adds to admin_activities table

- **list_templates** - Get available templates
  - Parameters: `category` (optional), `difficulty` (optional)
  - Returns: Array of public templates

- **get_template** - Get single template details
  - Parameters: `template_id`

- **create_custom_template** - Create custom template
  - Parameters: `template_name`, `template_category`, `template_description`, `template_content`, `difficulty`, `target_focus`
  - Returns: `template_id`

- **get_difficulty_levels** - Get all difficulty options
  - Returns: ['easy', 'medium', 'hard', 'very_hard']

- **get_categories** - Get all activity categories
  - Returns: Array of 11 predefined categories

---

## 7. Reports Management API
**File:** `teacher_reports_management.php`

### Actions:
- **generate_report** - Generate summary report
  - Parameters: `report_type`, `start_date`, `end_date`
  - Returns: Report data with aggregates

- **get_student_report** - Get detailed report for student
  - Parameters: `student_id`
  - Returns: Student info, assessments, IEP

- **get_progress_report** - Get progress over time
  - Parameters: `student_id`, `months` (default: 3)
  - Returns: Month-by-month progress data

- **save_report** - Save report to database
  - Parameters: `student_id`, `report_title`, `report_content`, `report_type`
  - Returns: `report_id`

- **list_reports** - Get saved reports
  - Parameters: `student_id` (optional)
  - Returns: Array of reports with student names

---

## 8. Settings Management API
**File:** `teacher_settings_management.php`

### Actions:
- **get_profile** - Get teacher profile
  - Returns: Teacher info (without password)

- **update_profile** - Update teacher information
  - Parameters: `teacher_name`, `email`, `phone`, `school_name`, `grade_level`, `specialization`

- **get_settings** - Get teacher preferences
  - Returns: Default + stored settings (notifications, theme, language, display, hints)

- **update_settings** - Save teacher preferences
  - Parameters: `settings` (JSON object)

- **change_password** - Change teacher password
  - Parameters: `current_password`, `new_password`, `confirm_password`
  - Validation: Min 6 chars, must match

---

## 9. Advanced Activity Management API
**File:** `teacher_activity_advanced.php`

### Actions:
- **get_activity_detail** - Get full activity details
  - Parameters: `activity_id`
  - Returns: Activity + assignments count + completions count

- **update_activity** - Modify activity
  - Parameters: `activity_id`, `activity_title`, `activity_description`, `learning_materials`, `instructions`, `subject`, `grade_level`, `difficulty`, `duration`, `status`

- **publish_activity** - Publish activity
  - Parameters: `activity_id`
  - Sets status to 'published'

- **delete_activity** - Remove activity
  - Parameters: `activity_id`

- **get_activity_assignments** - Get students assigned to activity
  - Parameters: `activity_id`
  - Returns: Array of assignments with student names

- **assign_activity** - Assign activity to student
  - Parameters: `activity_id`, `student_id`, `due_date`
  - Prevents: Duplicate assignments

- **unassign_activity** - Remove assignment
  - Parameters: `assignment_id`

---

## Database Tables Created/Enhanced

### New Tables:
1. **activity_assignments** - Track activity assignments
2. **teacher_settings** - Store teacher preferences
3. **teacher_reports** - Save generated reports
4. **lesson_plans** - Detailed lesson planning

### Enhanced Tables:
- **students** - Added: disability_category, updated_at
- **iep_materials** - Added: last_reviewed, updated_at
- **learner_progress** - Added: feedback, assessment_date
- **teacher_activities** - Added: learning_materials, instructions, duration, updated_at
- **notifications** - Created for teacher notifications
- **assessment_records** - Created for detailed assessments
- **activity_templates** - Created for activity templates (9 samples loaded)

---

## Sample Activity Templates (9)
1. Color Recognition Activity (easy, Cognitive)
2. Basic Counting & Number Skills (easy, Academic)
3. Basic Communication Practice (easy, Communication)
4. Fine Motor Skills - Drawing (easy, Fine Motor)
5. Gross Motor - Balance Activities (medium, Gross Motor)
6. Emotional Regulation - Calming Strategies (medium, Emotional Regulation)
7. Social Skills - Turn Taking (medium, Social Skills)
8. Advanced Reading Comprehension (hard, Academic)
9. Problem Solving - Decision Making (hard, Cognitive)

---

## Common Response Format

### Success Response:
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {...}
}
```

### Error Response:
```json
{
  "success": false,
  "message": "Error description"
}
```

---

## Key Features Implemented:
✅ Complete learner/student CRUD operations
✅ Full IEP material management
✅ Comprehensive assessment tracking
✅ Notification system
✅ Dashboard statistics aggregation
✅ Activity template library
✅ Report generation (multiple types)
✅ Teacher settings & preferences
✅ Advanced activity management with assignments
✅ Progress analytics over time
✅ Database schema with all required tables
✅ Sample templates for quick activity creation

---

## Authentication Note:
All APIs currently default to `teacher_id=1` when not provided. Integrate with session-based authentication:
```php
$teacher_id = isset($_SESSION['teacher_id']) ? intval($_SESSION['teacher_id']) : 1;
```

---

## Integration Pattern Example:
```javascript
// Fetch assessments for dashboard
fetch('TEACHER_BACKEND/teacher_manage_assessments.php', {
  method: 'POST',
  body: new FormData({
    action: 'get_assessments',
    teacher_id: teacher_id,
    limit: 10
  })
})
.then(r => r.json())
.then(data => {
  if (data.success) {
    // Display data
  }
});
```

---

**Last Updated:** Current Session
**Status:** All APIs functional and database-ready
