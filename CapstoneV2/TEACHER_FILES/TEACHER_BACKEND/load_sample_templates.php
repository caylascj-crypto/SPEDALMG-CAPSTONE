<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getTeacherDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Sample activity templates
$templates = [
    [
        'template_name' => 'Color Recognition Activity',
        'template_category' => 'Cognitive Development',
        'template_description' => 'Students identify and match colors in various objects',
        'template_content' => json_encode([
            'objectives' => ['Recognize primary colors', 'Match colors to objects', 'Enhance visual discrimination'],
            'materials' => ['Color cards', 'Colored objects', 'Sorting bins'],
            'instructions' => ['Show color card', 'Ask student to identify color', 'Have student find matching objects', 'Praise correct responses'],
            'duration' => 15,
            'difficulty' => 'easy'
        ]),
        'difficulty' => 'easy',
        'target_focus' => 'Visual Discrimination'
    ],
    [
        'template_name' => 'Basic Counting & Number Skills',
        'template_category' => 'Academic',
        'template_description' => 'Students learn to count objects and recognize numbers 1-10',
        'template_content' => json_encode([
            'objectives' => ['Count to 10', 'Recognize number symbols', 'Match quantity to numeral'],
            'materials' => ['Number cards', 'Counting objects (blocks, beads)', 'Worksheet'],
            'instructions' => ['Display number', 'Have student count objects', 'Practice writing numbers', 'Repeat until mastered'],
            'duration' => 20,
            'difficulty' => 'easy'
        ]),
        'difficulty' => 'easy',
        'target_focus' => 'Number Literacy'
    ],
    [
        'template_name' => 'Basic Communication Practice',
        'template_category' => 'Communication',
        'template_description' => 'Students practice identifying and naming common objects with verbal or AAC response',
        'template_content' => json_encode([
            'objectives' => ['Receptive language', 'Expressive language', 'Vocabulary building'],
            'materials' => ['Picture cards', 'Real objects', 'AAC device if needed'],
            'instructions' => ['Show object/picture', 'Ask "What is this?"', 'Wait for response', 'Reinforce correct answer'],
            'duration' => 15,
            'difficulty' => 'easy'
        ]),
        'difficulty' => 'easy',
        'target_focus' => 'Vocabulary Development'
    ],
    [
        'template_name' => 'Fine Motor Skills - Drawing',
        'template_category' => 'Fine Motor',
        'template_description' => 'Students practice drawing and tracing activities to develop hand control',
        'template_content' => json_encode([
            'objectives' => ['Improve pencil grip', 'Enhance hand-eye coordination', 'Develop hand strength'],
            'materials' => ['Pencils/crayons', 'Drawing paper', 'Tracing templates'],
            'instructions' => ['Provide writing tool', 'Show tracing template', 'Guide hand if needed', 'Practice repetition'],
            'duration' => 20,
            'difficulty' => 'easy'
        ]),
        'difficulty' => 'easy',
        'target_focus' => 'Hand Strength'
    ],
    [
        'template_name' => 'Gross Motor - Balance Activities',
        'template_category' => 'Gross Motor',
        'template_description' => 'Students practice balance and coordination through structured movements',
        'template_content' => json_encode([
            'objectives' => ['Improve balance', 'Develop coordination', 'Build strength'],
            'materials' => ['Balance beam', 'Cones', 'Mat'],
            'instructions' => ['Set up course', 'Demonstrate activity', 'Provide hand-over-hand support if needed', 'Progress difficulty'],
            'duration' => 25,
            'difficulty' => 'medium'
        ]),
        'difficulty' => 'medium',
        'target_focus' => 'Balance & Coordination'
    ],
    [
        'template_name' => 'Emotional Regulation - Calming Strategies',
        'template_category' => 'Emotional Regulation',
        'template_description' => 'Students learn and practice techniques to manage emotions and stress',
        'template_content' => json_encode([
            'objectives' => ['Recognize emotions', 'Apply calming strategies', 'Develop self-awareness'],
            'materials' => ['Emotion cards', 'Calming tools (stress ball, fidget)', 'Visual schedule'],
            'instructions' => ['Introduce emotion', 'Teach strategy', 'Practice with student', 'Reinforce use'],
            'duration' => 20,
            'difficulty' => 'medium'
        ]),
        'difficulty' => 'medium',
        'target_focus' => 'Emotional Awareness'
    ],
    [
        'template_name' => 'Social Skills - Turn Taking',
        'template_category' => 'Social Skills',
        'template_description' => 'Students practice taking turns and waiting in structured games and activities',
        'template_content' => json_encode([
            'objectives' => ['Understand turn-taking', 'Develop patience', 'Build social awareness'],
            'materials' => ['Board game', 'Visual timer', 'Reward system'],
            'instructions' => ['Explain turns', 'Use visual timer', 'Play game together', 'Praise appropriate behavior'],
            'duration' => 20,
            'difficulty' => 'medium'
        ]),
        'difficulty' => 'medium',
        'target_focus' => 'Social Interaction'
    ],
    [
        'template_name' => 'Advanced Reading Comprehension',
        'template_category' => 'Academic',
        'template_description' => 'Students read passages and answer comprehension questions',
        'template_content' => json_encode([
            'objectives' => ['Comprehend text', 'Answer questions', 'Build reading fluency'],
            'materials' => ['Reading passages', 'Question sheets', 'Vocabulary list'],
            'instructions' => ['Read passage aloud', 'Student reads passage', 'Answer comprehension questions', 'Discuss story'],
            'duration' => 30,
            'difficulty' => 'hard'
        ]),
        'difficulty' => 'hard',
        'target_focus' => 'Reading Comprehension'
    ],
    [
        'template_name' => 'Problem Solving - Decision Making',
        'template_category' => 'Cognitive Development',
        'template_description' => 'Students work through problems and make decisions in various scenarios',
        'template_content' => json_encode([
            'objectives' => ['Develop problem-solving skills', 'Practice decision-making', 'Think critically'],
            'materials' => ['Scenario cards', 'Discussion guide', 'Recording sheet'],
            'instructions' => ['Present problem', 'Brainstorm solutions', 'Evaluate options', 'Choose best solution'],
            'duration' => 25,
            'difficulty' => 'hard'
        ]),
        'difficulty' => 'hard',
        'target_focus' => 'Critical Thinking'
    ]
];

// Insert templates if they don't exist
$inserted = 0;
$skipped = 0;

foreach ($templates as $template) {
    // Check if template exists
    $checkSQL = "SELECT id FROM activity_templates WHERE template_name=?";
    $checkStmt = $conn->prepare($checkSQL);
    $checkStmt->bind_param("s", $template['template_name']);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        $skipped++;
        $checkStmt->close();
        continue;
    }
    $checkStmt->close();
    
    // Insert new template
    $sql = "INSERT INTO activity_templates (template_name, template_category, template_description, template_content, difficulty, target_focus, is_public)
            VALUES (?, ?, ?, ?, ?, ?, TRUE)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", 
        $template['template_name'],
        $template['template_category'],
        $template['template_description'],
        $template['template_content'],
        $template['difficulty'],
        $template['target_focus']
    );
    
    if ($stmt->execute()) {
        $inserted++;
    }
    $stmt->close();
}

$conn->close();

echo json_encode([
    'success' => true,
    'message' => 'Sample templates loaded successfully',
    'inserted' => $inserted,
    'skipped' => $skipped,
    'total' => count($templates)
]);
?>
