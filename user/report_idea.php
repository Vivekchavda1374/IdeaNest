<?php
session_start();
require_once '../Login/Login/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to report ideas']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$idea_id = (int)($_POST['idea_id'] ?? 0);
$report_reason = $_POST['report_reason'] ?? '';
$report_details = $_POST['report_details'] ?? '';

// Validate input
if ($idea_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid idea ID']);
    exit;
}

if (empty($report_reason)) {
    echo json_encode(['success' => false, 'message' => 'Please select a reason for reporting']);
    exit;
}

$valid_reasons = ['spam', 'inappropriate', 'offensive', 'copyright', 'other'];
if (!in_array($report_reason, $valid_reasons)) {
    echo json_encode(['success' => false, 'message' => 'Invalid report reason']);
    exit;
}

try {
    // Check if idea exists
    $idea_check = $conn->prepare("SELECT id, user_id FROM blog WHERE id = ?");
    $idea_check->bind_param("i", $idea_id);
    $idea_check->execute();
    $idea_result = $idea_check->get_result();
    
    if ($idea_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Idea not found']);
        exit;
    }
    
    $idea_data = $idea_result->fetch_assoc();
    
    // Check if user is trying to report their own idea
    if ($idea_data['user_id'] == $user_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot report your own idea']);
        exit;
    }
    
    // Check if user has already reported this idea
    $existing_report = $conn->prepare("SELECT id FROM idea_reports WHERE idea_id = ? AND reporter_id = ?");
    $existing_report->bind_param("ii", $idea_id, $user_id);
    $existing_report->execute();
    
    if ($existing_report->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already reported this idea']);
        exit;
    }
    
    // Insert the report
    $insert_report = $conn->prepare("INSERT INTO idea_reports (idea_id, reporter_id, report_reason, report_details) VALUES (?, ?, ?, ?)");
    $insert_report->bind_param("iiss", $idea_id, $user_id, $report_reason, $report_details);
    
    if ($insert_report->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Thank you for your report. Our team will review it shortly.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit report. Please try again.']);
    }
    
} catch (Exception $e) {
    error_log("Report idea error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again later.']);
}

$conn->close();
?>