<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getTeacherDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';
$teacher_id = isset($_REQUEST['teacher_id']) ? intval($_REQUEST['teacher_id']) : 1;

switch($action) {
    case 'get_activity_detail':
        getActivityDetail($conn, $teacher_id);
        break;
    case 'update_activity':
        updateActivity($conn, $teacher_id);
        break;
    case 'publish_activity':
        publishActivity($conn, $teacher_id);
        break;
    case 'delete_activity':
        deleteActivity($conn, $teacher_id);
        break;
    case 'get_activity_assignments':
        getActivityAssignments($conn, $teacher_id);
        break;
    case 'assign_activity':
        assignActivity($conn, $teacher_id);
        break;
    case 'unassign_activity':
        unassignActivity($conn, $teacher_id);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}

$conn->close();

function getActivityDetail($conn, $teacher_id) {
    $activity_id = isset($_REQUEST['activity_id']) ? intval($_REQUEST['activity_id']) : 0;
    
    if (!$activity_id) {
        echo json_encode(['success' => false, 'message' => 'Activity ID is required']);
        return;
    }
    
    $sql = "SELECT * FROM teacher_activities WHERE id=? AND teacher_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $activity_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Get assignment count
        $assignSQL = "SELECT COUNT(*) as assign_count FROM activity_assignments WHERE activity_id=?";
        $assignStmt = $conn->prepare($assignSQL);
        $assignStmt->bind_param("i", $activity_id);
        $assignStmt->execute();
        $assignResult = $assignStmt->get_result();
        $assignRow = $assignResult->fetch_assoc();
        
        // Get completion count
        $completeSQL = "SELECT COUNT(*) as complete_count FROM learner_progress WHERE activity_id=? AND score IS NOT NULL";
        $completeStmt = $conn->prepare($completeSQL);
        $completeStmt->bind_param("i", $activity_id);
        $completeStmt->execute();
        $completeResult = $completeStmt->get_result();
        $completeRow = $completeResult->fetch_assoc();
        
        $row['assignments'] = $assignRow['assign_count'];
        $row['completions'] = $completeRow['complete_count'];
        
        $assignStmt->close();
        $completeStmt->close();
        
        echo json_encode(['success' => true, 'activity' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Activity not found']);
    }
    $stmt->close();
}

function updateActivity($conn, $teacher_id) {
    $activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;
    $activity_title = isset($_POST['activity_title']) ? trim($_POST['activity_title']) : '';
    $activity_description = isset($_POST['activity_description']) ? trim($_POST['activity_description']) : '';
    $learning_materials = isset($_POST['learning_materials']) ? trim($_POST['learning_materials']) : '';
    $instructions = isset($_POST['instructions']) ? trim($_POST['instructions']) : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $grade_level = isset($_POST['grade_level']) ? trim($_POST['grade_level']) : '';
    $difficulty = isset($_POST['difficulty']) ? trim($_POST['difficulty']) : 'medium';
    $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 30;
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'draft';
    
    if (!$activity_id || !$activity_title) {
        echo json_encode(['success' => false, 'message' => 'Activity ID and title are required']);
        return;
    }
    
    $sql = "UPDATE teacher_activities 
            SET activity_title=?, activity_description=?, learning_materials=?, instructions=?,
                subject=?, grade_level=?, difficulty=?, duration=?, status=?, updated_at=NOW()
            WHERE id=? AND teacher_id=?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssiii", $activity_title, $activity_description, $learning_materials, $instructions,
                      $subject, $grade_level, $difficulty, $duration, $status, $activity_id, $teacher_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Activity updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update activity']);
    }
    $stmt->close();
}

function publishActivity($conn, $teacher_id) {
    $activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;
    
    if (!$activity_id) {
        echo json_encode(['success' => false, 'message' => 'Activity ID is required']);
        return;
    }
    
    $sql = "UPDATE teacher_activities SET status='published', updated_at=NOW() WHERE id=? AND teacher_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $activity_id, $teacher_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Activity published successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to publish activity']);
    }
    $stmt->close();
}

function deleteActivity($conn, $teacher_id) {
    $activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;
    
    if (!$activity_id) {
        echo json_encode(['success' => false, 'message' => 'Activity ID is required']);
        return;
    }
    
    $sql = "DELETE FROM teacher_activities WHERE id=? AND teacher_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $activity_id, $teacher_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Activity deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete activity']);
    }
    $stmt->close();
}

function getActivityAssignments($conn, $teacher_id) {
    $activity_id = isset($_REQUEST['activity_id']) ? intval($_REQUEST['activity_id']) : 0;
    
    if (!$activity_id) {
        echo json_encode(['success' => false, 'message' => 'Activity ID is required']);
        return;
    }
    
    $sql = "SELECT aa.*, s.student_name FROM activity_assignments aa
            LEFT JOIN students s ON aa.student_id = s.id
            WHERE aa.activity_id=? AND aa.teacher_id=?
            ORDER BY aa.assigned_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $activity_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    
    echo json_encode(['success' => true, 'assignments' => $assignments, 'count' => count($assignments)]);
    $stmt->close();
}

function assignActivity($conn, $teacher_id) {
    $activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    $due_date = isset($_POST['due_date']) ? trim($_POST['due_date']) : date('Y-m-d', strtotime('+7 days'));
    
    if (!$activity_id || !$student_id) {
        echo json_encode(['success' => false, 'message' => 'Activity ID and Student ID are required']);
        return;
    }
    
    // Check if already assigned
    $checkSQL = "SELECT id FROM activity_assignments WHERE activity_id=? AND student_id=? AND teacher_id=?";
    $checkStmt = $conn->prepare($checkSQL);
    $checkStmt->bind_param("iii", $activity_id, $student_id, $teacher_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Activity already assigned to this student']);
        $checkStmt->close();
        return;
    }
    $checkStmt->close();
    
    $sql = "INSERT INTO activity_assignments (teacher_id, activity_id, student_id, due_date)
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $teacher_id, $activity_id, $student_id, $due_date);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Activity assigned successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to assign activity']);
    }
    $stmt->close();
}

function unassignActivity($conn, $teacher_id) {
    $assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
    
    if (!$assignment_id) {
        echo json_encode(['success' => false, 'message' => 'Assignment ID is required']);
        return;
    }
    
    $sql = "DELETE FROM activity_assignments WHERE id=? AND teacher_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $assignment_id, $teacher_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Activity unassigned successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to unassign activity']);
    }
    $stmt->close();
}
?>
