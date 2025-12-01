<?php
/**
 * AJAX Handler for Mentor Requests
 */
require_once __DIR__ . '/../includes/security_init.php';
session_start();
require_once '../Login/Login/db.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/validation.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to send mentor requests.'
    ]);
    exit();
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid security token. Please refresh the page and try again.'
    ]);
    exit();
}

// Get and validate input
$mentor_id = isset($_POST['mentor_id']) ? intval($_POST['mentor_id']) : 0;
$project_id = !empty($_POST['project_id']) ? intval($_POST['project_id']) : null;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validate inputs
if (empty($mentor_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please select a mentor.'
    ]);
    exit();
}

if (empty($message)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a message.'
    ]);
    exit();
}

if (strlen($message) < 10) {
    echo json_encode([
        'success' => false,
        'message' => 'Message must be at least 10 characters long.'
    ]);
    exit();
}

// Verify mentor exists and is actually a mentor
$verify_mentor = "SELECT id, name, email FROM register WHERE id = ? AND role = 'mentor'";
$verify_stmt = $conn->prepare($verify_mentor);

if (!$verify_stmt) {
    error_log("Failed to prepare verify mentor query: " . $conn->error);
    echo json_encode([
        'success' => false,
        'message' => 'Database error. Please try again later.'
    ]);
    exit();
}

$verify_stmt->bind_param("i", $mentor_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows == 0) {
    error_log("Mentor ID $mentor_id not found or not a mentor");
    echo json_encode([
        'success' => false,
        'message' => 'Invalid mentor selected.'
    ]);
    $verify_stmt->close();
    exit();
}

$mentor_data = $verify_result->fetch_assoc();
$verify_stmt->close();

// Check if request already exists
$check_query = "SELECT id FROM mentor_requests WHERE student_id = ? AND mentor_id = ? AND status = 'pending'";
$check_stmt = $conn->prepare($check_query);

if (!$check_stmt) {
    error_log("Failed to prepare check query: " . $conn->error);
    echo json_encode([
        'success' => false,
        'message' => 'Database error. Please try again later.'
    ]);
    exit();
}

$check_stmt->bind_param("ii", $user_id, $mentor_id);

if (!$check_stmt->execute()) {
    error_log("Failed to execute check query: " . $check_stmt->error);
    echo json_encode([
        'success' => false,
        'message' => 'Database error. Please try again later.'
    ]);
    $check_stmt->close();
    exit();
}

$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'You already have a pending request with this mentor.'
    ]);
    $check_stmt->close();
    exit();
}

$check_stmt->close();

// Begin transaction for data integrity
$conn->begin_transaction();

try {
    // Insert mentor request
    $insert_query = "INSERT INTO mentor_requests (student_id, mentor_id, project_id, message, status) VALUES (?, ?, ?, ?, 'pending')";
    $insert_stmt = $conn->prepare($insert_query);
    
    if (!$insert_stmt) {
        throw new Exception("Failed to prepare insert statement: " . $conn->error);
    }
    
    $insert_stmt->bind_param("iiis", $user_id, $mentor_id, $project_id, $message);
    
    if (!$insert_stmt->execute()) {
        throw new Exception("Failed to execute insert: " . $insert_stmt->error);
    }
    
    $request_id = $insert_stmt->insert_id;
    $insert_stmt->close();
    
    // Create notification for mentor
    $notif_query = "INSERT INTO user_notifications (user_id, notification_type, title, message, related_id, related_type, action_url, icon, color) 
                   VALUES (?, 'mentor_request', 'New Mentorship Request', ?, ?, 'mentor_request', '/mentor/student_requests.php', 'bi-person-plus', 'info')";
    $notif_stmt = $conn->prepare($notif_query);
    
    if ($notif_stmt) {
        $notif_message = "You have received a new mentorship request from a student.";
        $notif_stmt->bind_param("isi", $mentor_id, $notif_message, $request_id);
        $notif_stmt->execute();
        $notif_stmt->close();
    }
    
    // Commit transaction
    $conn->commit();
    
    error_log("Mentor request created successfully: Request ID $request_id, Student ID $user_id, Mentor ID $mentor_id");
    
    // Try to send email notification
    $email_sent = false;
    try {
        require_once dirname(__DIR__) . "/includes/autoload_simple.php";
        
        // Get student details
        $student_query = "SELECT name FROM register WHERE id = ?";
        $student_stmt = $conn->prepare($student_query);
        $student_stmt->bind_param("i", $user_id);
        $student_stmt->execute();
        $student_data = $student_stmt->get_result()->fetch_assoc();
        $student_stmt->close();
        
        if ($student_data && class_exists('SMTPMailer')) {
            $mailer = new SMTPMailer();
            $subject = 'New Mentorship Request - IdeaNest';
            $body = "<h2>New Mentorship Request</h2>
            <p>Dear {$mentor_data['name']},</p>
            <p>You have received a new mentorship request from <strong>{$student_data['name']}</strong>.</p>
            <p><strong>Message:</strong></p>
            <p style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>" . htmlspecialchars($message) . "</p>
            <p>Please log in to your account to review and respond to this request.</p>
            <p>Best regards,<br>The IdeaNest Team</p>";
            
            $mailer->send($mentor_data['email'], $subject, $body);
            $email_sent = true;
        }
    } catch (Exception $e) {
        // Email failed, but request was saved
        error_log("Email notification failed: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Mentor request sent successfully!' . ($email_sent ? ' The mentor has been notified via email.' : ''),
        'request_id' => $request_id
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Mentor request error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send request. Please try again later.'
    ]);
}

$conn->close();
?>
