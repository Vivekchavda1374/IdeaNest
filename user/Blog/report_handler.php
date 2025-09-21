<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../../Login/Login/db.php';

// Check if table exists, if not create it
$check_table = $conn->query("SHOW TABLES LIKE 'idea_reports'");
if ($check_table->num_rows == 0) {
    $create_sql = "CREATE TABLE idea_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        idea_id INT NOT NULL,
        reporter_id INT NOT NULL,
        report_type VARCHAR(50) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending'
    )";
    $conn->query($create_sql);
}

// Handle report submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_report') {
    header('Content-Type: application/json');
    
    try {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Please login to report ideas']);
            exit;
        }
    
    $idea_id = isset($_POST['idea_id']) ? (int)$_POST['idea_id'] : 0;
    $report_type = isset($_POST['report_type']) ? trim($_POST['report_type']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $reporter_id = $_SESSION['user_id'];
    
    if ($idea_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid idea ID']);
        exit;
    }
    
    if (empty($report_type)) {
        echo json_encode(['success' => false, 'message' => 'Please select a report type']);
        exit;
    }
    
        // Check if user already reported this idea
        $check_sql = "SELECT id FROM idea_reports WHERE idea_id = ? AND reporter_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        if (!$check_stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        
        $check_stmt->bind_param("ii", $idea_id, $reporter_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'You have already reported this idea']);
            exit;
        }
        
        // Insert report
        $insert_sql = "INSERT INTO idea_reports (idea_id, reporter_id, report_type, description) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        if (!$insert_stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        
        $insert_stmt->bind_param("iiss", $idea_id, $reporter_id, $report_type, $description);
        
        if ($insert_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Report submitted successfully']);
        } else {
            throw new Exception('Execute failed: ' . $insert_stmt->error);
        }
        
        $insert_stmt->close();
        $check_stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// If no valid action, return error
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>