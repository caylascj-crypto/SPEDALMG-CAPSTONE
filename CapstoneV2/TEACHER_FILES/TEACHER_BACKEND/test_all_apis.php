<?php
/**
 * Teacher Portal Backend APIs - Testing & Verification Utility
 * 
 * This script helps verify that all backend APIs are working correctly.
 * Access at: http://localhost/CapstoneV2/TEACHER_FILES/TEACHER_BACKEND/test_all_apis.php
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$action = isset($_GET['action']) ? trim($_GET['action']) : 'status';
$teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 1;

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'teacher_id' => $teacher_id,
    'tests' => []
];

$conn = getTeacherDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

switch($action) {
    case 'status':
        testDatabaseStatus($conn, $results);
        break;
    case 'all':
        runAllTests($conn, $teacher_id, $results);
        break;
    case 'learners':
        testLearnersAPI($conn, $teacher_id, $results);
        break;
    case 'iep':
        testIEPAPI($conn, $teacher_id, $results);
        break;
    case 'assessments':
        testAssessmentsAPI($conn, $teacher_id, $results);
        break;
    case 'notifications':
        testNotificationsAPI($conn, $teacher_id, $results);
        break;
    case 'stats':
        testStatsAPI($conn, $teacher_id, $results);
        break;
    case 'templates':
        testTemplatesAPI($conn, $teacher_id, $results);
        break;
    case 'reports':
        testReportsAPI($conn, $teacher_id, $results);
        break;
    case 'settings':
        testSettingsAPI($conn, $teacher_id, $results);
        break;
    case 'activities':
        testActivitiesAPI($conn, $teacher_id, $results);
        break;
}

$conn->close();
echo json_encode($results);

// ===== TEST FUNCTIONS =====

function testDatabaseStatus($conn, &$results) {
    $tables = [
        'students',
        'iep_materials',
        'assessment_records',
        'notifications',
        'activity_assignments',
        'teacher_settings',
        'teacher_reports',
        'lesson_plans',
        'activity_templates',
        'teacher_activities',
        'learner_progress'
    ];
    
    $results['tests']['database_tables'] = [];
    
    foreach ($tables as $table) {
        $query = $conn->query("SHOW TABLES LIKE '$table'");
        $exists = $query->num_rows > 0;
        
        // Get row count
        $countQuery = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $countQuery->fetch_assoc()['count'];
        
        $results['tests']['database_tables'][] = [
            'table' => $table,
            'exists' => $exists,
            'rows' => $count,
            'status' => $exists ? 'OK' : 'MISSING'
        ];
    }
    
    $results['success'] = true;
}

function runAllTests($conn, $teacher_id, &$results) {
    testLearnersAPI($conn, $teacher_id, $results);
    testIEPAPI($conn, $teacher_id, $results);
    testAssessmentsAPI($conn, $teacher_id, $results);
    testNotificationsAPI($conn, $teacher_id, $results);
    testStatsAPI($conn, $teacher_id, $results);
    testTemplatesAPI($conn, $teacher_id, $results);
    testReportsAPI($conn, $teacher_id, $results);
    testSettingsAPI($conn, $teacher_id, $results);
    testActivitiesAPI($conn, $teacher_id, $results);
    $results['success'] = true;
}

function testLearnersAPI($conn, $teacher_id, &$results) {
    $test = ['api' => 'teacher_manage_learners.php'];
    
    // Check if students exist
    $query = $conn->query("SELECT COUNT(*) as count FROM students WHERE teacher_id=$teacher_id");
    $row = $query->fetch_assoc();
    $test['total_students'] = $row['count'];
    
    // Check table structure
    $structQuery = $conn->query("DESCRIBE students");
    $columns = [];
    while ($col = $structQuery->fetch_assoc()) {
        $columns[] = $col['Field'];
    }
    $test['required_columns'] = [
        'id' => in_array('id', $columns),
        'student_name' => in_array('student_name', $columns),
        'disability_category' => in_array('disability_category', $columns),
        'parent_email' => in_array('parent_email', $columns)
    ];
    
    $test['status'] = array_reduce($test['required_columns'], fn($carry, $item) => $carry && $item, true) ? 'OK' : 'INCOMPLETE';
    $results['tests']['learners'] = $test;
}

function testIEPAPI($conn, $teacher_id, &$results) {
    $test = ['api' => 'teacher_manage_iep.php'];
    
    // Check table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'iep_materials'");
    $test['table_exists'] = $tableCheck->num_rows > 0;
    
    // Get IEP count
    $query = $conn->query("SELECT COUNT(*) as count FROM iep_materials WHERE teacher_id=$teacher_id");
    $row = $query->fetch_assoc();
    $test['total_ieps'] = $row['count'];
    
    $test['status'] = $test['table_exists'] ? 'OK' : 'MISSING';
    $results['tests']['iep'] = $test;
}

function testAssessmentsAPI($conn, $teacher_id, &$results) {
    $test = ['api' => 'teacher_manage_assessments.php'];
    
    // Check assessment_records table
    $tableCheck = $conn->query("SHOW TABLES LIKE 'assessment_records'");
    $test['table_exists'] = $tableCheck->num_rows > 0;
    
    if ($test['table_exists']) {
        // Get assessment count
        $query = $conn->query("SELECT COUNT(*) as count FROM assessment_records WHERE teacher_id=$teacher_id");
        $row = $query->fetch_assoc();
        $test['total_assessments'] = $row['count'];
        
        // Get average score
        $avgQuery = $conn->query("SELECT ROUND(AVG(score), 2) as avg_score FROM assessment_records WHERE teacher_id=$teacher_id");
        $avgRow = $avgQuery->fetch_assoc();
        $test['average_score'] = $avgRow['avg_score'] ?: 0;
    }
    
    $test['status'] = $test['table_exists'] ? 'OK' : 'MISSING';
    $results['tests']['assessments'] = $test;
}

function testNotificationsAPI($conn, $teacher_id, &$results) {
    $test = ['api' => 'teacher_manage_notifications.php'];
    
    $tableCheck = $conn->query("SHOW TABLES LIKE 'notifications'");
    $test['table_exists'] = $tableCheck->num_rows > 0;
    
    if ($test['table_exists']) {
        $query = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_read=0 THEN 1 ELSE 0 END) as unread FROM notifications WHERE teacher_id=$teacher_id");
        $row = $query->fetch_assoc();
        $test['total_notifications'] = $row['total'];
        $test['unread_count'] = $row['unread'];
    }
    
    $test['status'] = $test['table_exists'] ? 'OK' : 'MISSING';
    $results['tests']['notifications'] = $test;
}

function testStatsAPI($conn, $teacher_id, &$results) {
    $test = ['api' => 'teacher_get_comprehensive_stats.php'];
    
    try {
        // Get stats with error handling
        $learnersQuery = $conn->query("SELECT COUNT(*) as c FROM students WHERE teacher_id=$teacher_id");
        $learners = $learnersQuery ? $learnersQuery->fetch_assoc()['c'] : 0;
        
        $activitiesQuery = $conn->query("SELECT COUNT(*) as c FROM teacher_activities WHERE teacher_id=$teacher_id AND status='published'");
        $activities = $activitiesQuery ? $activitiesQuery->fetch_assoc()['c'] : 0;
        
        $assessmentsQuery = $conn->query("SELECT COUNT(*) as c FROM assessment_records WHERE teacher_id=$teacher_id");
        $assessments = $assessmentsQuery ? $assessmentsQuery->fetch_assoc()['c'] : 0;
        
        $avgQuery = $conn->query("SELECT ROUND(AVG(score), 2) as avg FROM assessment_records WHERE teacher_id=$teacher_id");
        $avg_score = $avgQuery ? ($avgQuery->fetch_assoc()['avg'] ?: 0) : 0;
        
        $test['total_learners'] = $learners;
        $test['total_activities'] = $activities;
        $test['total_assessments'] = $assessments;
        $test['average_score'] = $avg_score;
        $test['status'] = 'OK';
    } catch (Exception $e) {
        $test['status'] = 'ERROR: ' . $e->getMessage();
    }
    
    $results['tests']['stats'] = $test;
}

function testTemplatesAPI($conn, $teacher_id, &$results) {
    $test = ['api' => 'teacher_activity_templates.php'];
    
    $tableCheck = $conn->query("SHOW TABLES LIKE 'activity_templates'");
    $test['table_exists'] = $tableCheck->num_rows > 0;
    
    if ($test['table_exists']) {
        $query = $conn->query("SELECT COUNT(*) as count FROM activity_templates WHERE is_public=TRUE");
        $row = $query->fetch_assoc();
        $test['total_templates'] = $row['count'];
        
        // Get breakdown by category
        $categoryQuery = $conn->query("SELECT template_category, COUNT(*) as count FROM activity_templates WHERE is_public=TRUE GROUP BY template_category");
        $test['templates_by_category'] = [];
        while ($cat = $categoryQuery->fetch_assoc()) {
            $test['templates_by_category'][] = $cat;
        }
    }
    
    $test['status'] = $test['table_exists'] ? 'OK' : 'MISSING';
    $results['tests']['templates'] = $test;
}

function testReportsAPI($conn, $teacher_id, &$results) {
    $test = ['api' => 'teacher_reports_management.php'];
    
    $tableCheck = $conn->query("SHOW TABLES LIKE 'teacher_reports'");
    $test['table_exists'] = $tableCheck->num_rows > 0;
    
    if ($test['table_exists']) {
        $query = $conn->query("SELECT COUNT(*) as count FROM teacher_reports WHERE teacher_id=$teacher_id");
        $row = $query->fetch_assoc();
        $test['total_reports'] = $row['count'];
    }
    
    $test['status'] = $test['table_exists'] ? 'OK' : 'MISSING';
    $results['tests']['reports'] = $test;
}

function testSettingsAPI($conn, $teacher_id, &$results) {
    $test = ['api' => 'teacher_settings_management.php'];
    
    try {
        // Check teacher_accounts table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'teacher_accounts'");
        $test['teacher_accounts_exists'] = $tableCheck && $tableCheck->num_rows > 0;
        
        if ($test['teacher_accounts_exists']) {
            // Check if teacher exists
            $query = $conn->query("SELECT id, teacher_name, email FROM teacher_accounts WHERE id=$teacher_id");
            if ($query) {
                $teacher = $query->fetch_assoc();
                $test['teacher_exists'] = $teacher !== null;
                if ($teacher) {
                    $test['teacher_name'] = $teacher['teacher_name'];
                    $test['email'] = $teacher['email'];
                }
            }
        }
        
        // Check teacher_settings table
        $settingsTableCheck = $conn->query("SHOW TABLES LIKE 'teacher_settings'");
        $test['settings_table_exists'] = $settingsTableCheck && $settingsTableCheck->num_rows > 0;
        
        $test['status'] = ($test['teacher_accounts_exists'] || $test['settings_table_exists']) ? 'OK' : 'MISSING';
    } catch (Exception $e) {
        $test['status'] = 'ERROR: ' . $e->getMessage();
    }
    
    $results['tests']['settings'] = $test;
}

function testActivitiesAPI($conn, $teacher_id, &$results) {
    $test = ['api' => 'teacher_activity_advanced.php'];
    
    // Check activity_assignments table
    $tableCheck = $conn->query("SHOW TABLES LIKE 'activity_assignments'");
    $test['assignments_table_exists'] = $tableCheck->num_rows > 0;
    
    // Get activities
    $actQuery = $conn->query("SELECT COUNT(*) as count FROM teacher_activities WHERE teacher_id=$teacher_id");
    $act = $actQuery->fetch_assoc();
    $test['total_activities'] = $act['count'];
    
    if ($test['assignments_table_exists']) {
        $assignQuery = $conn->query("SELECT COUNT(*) as count FROM activity_assignments WHERE teacher_id=$teacher_id");
        $assign = $assignQuery->fetch_assoc();
        $test['total_assignments'] = $assign['count'];
    }
    
    $test['status'] = $test['assignments_table_exists'] ? 'OK' : 'MISSING';
    $results['tests']['activities'] = $test;
}
?>
